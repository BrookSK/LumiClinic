<?php

declare(strict_types=1);

namespace App\Services\Stock;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\MaterialCategoryRepository;
use App\Repositories\MaterialRepository;
use App\Repositories\MaterialUnitRepository;
use App\Repositories\ServiceMaterialDefaultRepository;
use App\Repositories\StockInventoryItemRepository;
use App\Repositories\StockInventoryRepository;
use App\Repositories\StockMovementRepository;
use App\Services\Auth\AuthService;

final class StockService
{
    public function __construct(private readonly Container $container) {}

    /**
     * Reconcilia baixa de estoque para uma sessão (agendamento).
     *
     * Caso já existam movimentos (reference_type='session', reference_id=appointmentId),
     * aplica apenas o delta necessário (saída extra ou entrada de estorno).
     *
     * @param array<int,string> $desiredQtyByMaterialId map material_id => qty string
     * @return array{movement_ids:list<int>,total_cost:float}
     */
    public function reconcileForAppointment(int $appointmentId, int $serviceId, array $desiredQtyByMaterialId, string $note, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($appointmentId <= 0 || $serviceId <= 0) {
            return ['movement_ids' => [], 'total_cost' => 0.0];
        }

        $note = trim($note);
        if ($note === '') {
            $note = 'Ajuste automático de consumo';
        }

        $desired = [];
        foreach ($desiredQtyByMaterialId as $mid => $qtyStr) {
            $materialId = (int)$mid;
            if ($materialId <= 0) {
                continue;
            }

            $qty = (float)str_replace(',', '.', trim((string)$qtyStr));
            if ($qty <= 0) {
                continue;
            }
            $desired[$materialId] = $qty;
        }

        $pdo = $this->container->get(\PDO::class);
        $movRepo = new StockMovementRepository($pdo);
        $matRepo = new MaterialRepository($pdo);

        try {
            $pdo->beginTransaction();

            $existing = $movRepo->listByReference($clinicId, 'session', $appointmentId);
            $already = [];
            foreach ($existing as $m) {
                $materialId = (int)($m['material_id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }
                $type = (string)($m['type'] ?? '');
                if ($type !== 'exit' && $type !== 'entry') {
                    continue;
                }
                $qty = (float)($m['quantity'] ?? 0);
                if (!isset($already[$materialId])) {
                    $already[$materialId] = 0.0;
                }
                $already[$materialId] += ($type === 'exit' ? $qty : -$qty);
            }

            $materialIds = array_unique(array_merge(array_keys($desired), array_keys($already)));
            sort($materialIds);

            $movementIds = [];
            $totalCostDelta = 0.0;

            foreach ($materialIds as $materialId) {
                $target = (float)($desired[$materialId] ?? 0.0);
                $currentConsumed = (float)($already[$materialId] ?? 0.0);

                $delta = $target - $currentConsumed;
                if (abs($delta) < 0.0005) {
                    continue;
                }

                $mat = $matRepo->findByIdForUpdate($clinicId, $materialId);
                if ($mat === null) {
                    continue;
                }

                $stockCurrent = (float)($mat['stock_current'] ?? 0);
                $unitCost = (float)($mat['unit_cost'] ?? 0);

                if ($delta > 0) {
                    $newStock = $stockCurrent - $delta;
                    if ($newStock < 0) {
                        throw new \RuntimeException('Estoque insuficiente para baixa automática.');
                    }
                    $matRepo->updateStockCurrent($clinicId, $materialId, number_format($newStock, 3, '.', ''));

                    $totalCost = round($unitCost * $delta, 2);
                    $totalCostDelta += $totalCost;

                    $movementIds[] = $movRepo->create(
                        $clinicId,
                        $materialId,
                        'exit',
                        number_format($delta, 3, '.', ''),
                        'session',
                        $appointmentId,
                        null,
                        number_format($unitCost, 2, '.', ''),
                        number_format($totalCost, 2, '.', ''),
                        $note,
                        $userId
                    );
                } else {
                    $qtyIn = abs($delta);
                    $newStock = $stockCurrent + $qtyIn;
                    $matRepo->updateStockCurrent($clinicId, $materialId, number_format($newStock, 3, '.', ''));

                    $totalCost = round($unitCost * $qtyIn, 2);
                    $totalCostDelta -= $totalCost;

                    $movementIds[] = $movRepo->create(
                        $clinicId,
                        $materialId,
                        'entry',
                        number_format($qtyIn, 3, '.', ''),
                        'session',
                        $appointmentId,
                        null,
                        number_format($unitCost, 2, '.', ''),
                        number_format($totalCost, 2, '.', ''),
                        $note,
                        $userId
                    );
                }
            }

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.session_reconcile', [
                'appointment_id' => $appointmentId,
                'service_id' => $serviceId,
                'movement_ids' => $movementIds,
                'total_cost_delta' => $totalCostDelta,
                'note' => $note,
            ], $ip);

            $pdo->commit();

            $netCost = 0.0;
            $after = $movRepo->listByReference($clinicId, 'session', $appointmentId);
            foreach ($after as $m) {
                $type = (string)($m['type'] ?? '');
                if ($type !== 'exit' && $type !== 'entry') {
                    continue;
                }
                $cost = (float)($m['total_cost_snapshot'] ?? 0);
                $netCost += ($type === 'exit' ? $cost : -$cost);
            }

            return ['movement_ids' => $movementIds, 'total_cost' => $netCost];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function addInventoryItem(int $inventoryId, int $materialId, ?string $qtyCountedRaw, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($inventoryId <= 0) {
            throw new \RuntimeException('Inventário inválido.');
        }
        if ($materialId <= 0) {
            throw new \RuntimeException('Material inválido.');
        }

        $qtyCounted = null;
        if ($qtyCountedRaw !== null) {
            $s = trim($qtyCountedRaw);
            if ($s !== '') {
                $qtyCounted = $this->parseQty($s);
                if ($qtyCounted < 0) {
                    $qtyCounted = 0.0;
                }
            }
        }

        $pdo = $this->container->get(\PDO::class);
        $invRepo = new StockInventoryRepository($pdo);
        $itemRepo = new StockInventoryItemRepository($pdo);
        $matRepo = new MaterialRepository($pdo);

        try {
            $pdo->beginTransaction();

            $inventory = $invRepo->findById($clinicId, $inventoryId);
            if ($inventory === null) {
                throw new \RuntimeException('Inventário inválido.');
            }
            if ((string)($inventory['status'] ?? '') !== 'draft') {
                throw new \RuntimeException('Inventário não está em rascunho.');
            }

            $mat = $matRepo->findById($clinicId, $materialId);
            if ($mat === null) {
                throw new \RuntimeException('Material inválido.');
            }

            $itemRepo->createForMaterialIfMissing(
                $clinicId,
                $inventoryId,
                $materialId,
                $qtyCounted
            );

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.inventory_items.add', [
                'inventory_id' => $inventoryId,
                'material_id' => $materialId,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return list<array<string,mixed>> */
    public function listMaterials(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MaterialRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId, 500);
    }

    /** @return list<array<string,mixed>> */
    public function listInventories(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new StockInventoryRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId, 50);
    }

    public function createInventory(?string $notes, string $ip): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $notes = $notes === null ? null : trim($notes);
        if ($notes === '') {
            $notes = null;
        }

        $pdo = $this->container->get(\PDO::class);
        $invRepo = new StockInventoryRepository($pdo);

        try {
            $pdo->beginTransaction();

            $id = $invRepo->create($clinicId, $notes, $userId);

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.inventory.create', [
                'inventory_id' => $id,
            ], $ip);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array{inventory:array<string,mixed>,items:list<array<string,mixed>>} */
    public function getInventory(int $inventoryId): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($inventoryId <= 0) {
            throw new \RuntimeException('Inventário inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $invRepo = new StockInventoryRepository($pdo);
        $inventory = $invRepo->findById($clinicId, $inventoryId);
        if ($inventory === null) {
            throw new \RuntimeException('Inventário inválido.');
        }

        $items = (new StockInventoryItemRepository($pdo))->listByInventoryDetailed($clinicId, $inventoryId);

        return [
            'inventory' => $inventory,
            'items' => $items,
        ];
    }

    /** @param array<int,mixed> $qtyByMaterialId */
    public function updateInventoryCounts(int $inventoryId, array $qtyByMaterialId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($inventoryId <= 0) {
            throw new \RuntimeException('Inventário inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $invRepo = new StockInventoryRepository($pdo);
        $inventory = $invRepo->findById($clinicId, $inventoryId);
        if ($inventory === null) {
            throw new \RuntimeException('Inventário inválido.');
        }
        if ((string)($inventory['status'] ?? '') !== 'draft') {
            throw new \RuntimeException('Inventário não está em rascunho.');
        }

        $itemRepo = new StockInventoryItemRepository($pdo);

        try {
            $pdo->beginTransaction();

            foreach ($qtyByMaterialId as $mid => $qtyRaw) {
                $materialId = (int)$mid;
                if ($materialId <= 0) {
                    continue;
                }

                $qtyStr = trim((string)$qtyRaw);
                if ($qtyStr === '') {
                    continue;
                }

                $qty = $this->parseQty($qtyStr);
                if ($qty < 0) {
                    $qty = 0.0;
                }

                $itemRepo->updateCounted($clinicId, $inventoryId, $materialId, $qty);
            }

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.inventory.update_counts', [
                'inventory_id' => $inventoryId,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function confirmInventory(int $inventoryId, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($inventoryId <= 0) {
            throw new \RuntimeException('Inventário inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $invRepo = new StockInventoryRepository($pdo);
        $itemRepo = new StockInventoryItemRepository($pdo);
        $movRepo = new StockMovementRepository($pdo);
        $matRepo = new MaterialRepository($pdo);

        try {
            $pdo->beginTransaction();

            $inventory = $invRepo->findById($clinicId, $inventoryId);
            if ($inventory === null) {
                throw new \RuntimeException('Inventário inválido.');
            }
            if ((string)($inventory['status'] ?? '') !== 'draft') {
                throw new \RuntimeException('Inventário já confirmado.');
            }

            if ($movRepo->existsForReference($clinicId, 'inventory', $inventoryId)) {
                throw new \RuntimeException('Inventário já gerou movimentos.');
            }

            $items = $itemRepo->listByInventoryDetailed($clinicId, $inventoryId);
            $movementIds = [];

            foreach ($items as $it) {
                $materialId = (int)($it['material_id'] ?? 0);
                if ($materialId <= 0) {
                    continue;
                }

                $qtyCounted = (float)($it['qty_counted'] ?? 0);

                $mat = $matRepo->findByIdForUpdate($clinicId, $materialId);
                if ($mat === null) {
                    continue;
                }

                $current = (float)($mat['stock_current'] ?? 0);
                $delta = $qtyCounted - $current;
                if (abs($delta) < 0.0005) {
                    continue;
                }

                $unitCost = (float)($mat['unit_cost'] ?? 0);
                $type = $delta > 0 ? 'entry' : 'exit';
                $qtyMove = abs($delta);

                $newStock = $qtyCounted;
                if ($newStock < 0) {
                    $newStock = 0.0;
                }
                $matRepo->updateStockCurrent($clinicId, $materialId, number_format($newStock, 3, '.', ''));

                $totalCost = 0.0;
                if ($type === 'exit') {
                    $totalCost = round($unitCost * $qtyMove, 2);
                }

                $movementIds[] = $movRepo->create(
                    $clinicId,
                    $materialId,
                    $type,
                    number_format($qtyMove, 3, '.', ''),
                    'inventory',
                    $inventoryId,
                    null,
                    number_format($unitCost, 2, '.', ''),
                    number_format($totalCost, 2, '.', ''),
                    'Ajuste de inventário #' . $inventoryId,
                    $userId
                );
            }

            $invRepo->confirm($clinicId, $inventoryId, $userId);

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.inventory.confirm', [
                'inventory_id' => $inventoryId,
                'movement_ids' => $movementIds,
            ], $ip);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Baixa automática usando materiais reais registrados para a sessão.
     * Idempotente por (reference_type='session', reference_id=appointmentId).
     *
     * @param array<int,string> $qtyByMaterialId map material_id => qty string
     * @return array{movement_ids:list<int>,total_cost:float}
     */
    public function autoConsumeForAppointmentAdjusted(int $appointmentId, int $serviceId, array $qtyByMaterialId, string $ip): array
    {
        return $this->reconcileForAppointment($appointmentId, $serviceId, $qtyByMaterialId, 'Baixa automática por sessão', $ip);
    }

    /**
     * @return array{
     *   low_stock:list<array<string,mixed>>,
     *   out_of_stock:list<array<string,mixed>>,
     *   expiring_soon:list<array<string,mixed>>,
     *   expired:list<array<string,mixed>>
     * }
     */
    public function alerts(int $days): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $days = max(1, min(365, $days));

        $repo = new MaterialRepository($this->container->get(\PDO::class));
        return [
            'low_stock' => $repo->listLowStock($clinicId, 300),
            'out_of_stock' => $repo->listOutOfStock($clinicId, 300),
            'expiring_soon' => $repo->listExpiringSoon($clinicId, $days, 300),
            'expired' => $repo->listExpired($clinicId, 300),
        ];
    }

    /**
     * @return array{
     *   from:string,
     *   to:string,
     *   summary:array<string,mixed>,
     *   by_material:list<array<string,mixed>>,
     *   losses_by_reason:list<array<string,mixed>>,
     *   losses_by_material:list<array<string,mixed>>,
     *   by_service:list<array<string,mixed>>,
     *   by_professional:list<array<string,mixed>>
     * }
     */
    public function reports(string $from, string $to): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = $from === '' ? date('Y-m-01') : $from;
        $to = $to === '' ? date('Y-m-d') : $to;

        $repo = new StockMovementRepository($this->container->get(\PDO::class));
        return [
            'from' => $from,
            'to' => $to,
            'summary' => $repo->summarizeCosts($clinicId, $from, $to),
            'by_material' => $repo->aggregateByMaterial($clinicId, $from, $to, 200),
            'losses_by_reason' => $repo->aggregateLossCostByReason($clinicId, $from, $to),
            'losses_by_material' => $repo->aggregateLossCostByMaterial($clinicId, $from, $to, 200),
            'by_service' => $repo->aggregateSessionCostByService($clinicId, $from, $to, 200),
            'by_professional' => $repo->aggregateSessionCostByProfessional($clinicId, $from, $to, 200),
        ];
    }

    /**
     * Consumo para finalizar sessão com ajuste manual.
     * Idempotente por (reference_type='session', reference_id=appointmentId).
     *
     * @param array<int,string> $qtyByMaterialId map material_id => qty string
     * @return array{movement_ids:list<int>,total_cost:float}
     */
    public function consumeForAppointmentAdjusted(int $appointmentId, int $serviceId, array $qtyByMaterialId, string $note, string $ip): array
    {
        $note = trim($note);
        if ($note === '') {
            throw new \RuntimeException('Observação obrigatória.');
        }

        return $this->reconcileForAppointment($appointmentId, $serviceId, $qtyByMaterialId, $note, $ip);
    }

    public function createMaterial(
        string $name,
        ?string $category,
        string $unit,
        string $stockMinimum,
        string $initialStock,
        string $unitCost,
        ?string $validityDate,
        string $ip
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $name = trim($name);
        $unit = trim($unit);
        if ($name === '' || $unit === '') {
            throw new \RuntimeException('Nome e unidade são obrigatórios.');
        }

        $category = $category === null ? null : trim($category);
        if ($category === '') {
            $category = null;
        }

        if ($category === null) {
            throw new \RuntimeException('Categoria é obrigatória.');
        }

        $min = $this->parseQty($stockMinimum);
        if ($min < 0) {
            $min = 0.0;
        }

        $init = $this->parseQty($initialStock);
        if ($init < 0) {
            $init = 0.0;
        }

        $cost = $this->parseMoney($unitCost);
        if ($cost < 0) {
            $cost = 0.0;
        }

        if ($validityDate !== null && trim((string)$validityDate) !== '') {
            $vd = \DateTimeImmutable::createFromFormat('Y-m-d', trim((string)$validityDate));
            if ($vd === false) {
                throw new \RuntimeException('Validade inválida.');
            }
            $today = new \DateTimeImmutable(date('Y-m-d'));
            if ($vd < $today) {
                throw new \RuntimeException('Validade não pode estar vencida.');
            }
        }

        $pdo = $this->container->get(\PDO::class);

        $unitsRepo = new MaterialUnitRepository($pdo);
        if (!$unitsRepo->existsActiveByClinicAndCode($clinicId, $unit)) {
            throw new \RuntimeException('Unidade inválida.');
        }

        $catRepo = new MaterialCategoryRepository($pdo);
        if (!$catRepo->existsActiveByClinicAndName($clinicId, (string)$category)) {
            throw new \RuntimeException('Categoria inválida.');
        }

        $repo = new MaterialRepository($pdo);
        try {
            $pdo->beginTransaction();

            $id = $repo->create(
                $clinicId,
                $name,
                $category,
                $unit,
                number_format($min, 3, '.', ''),
                number_format($cost, 2, '.', ''),
                $validityDate
            );

            if ($init > 0) {
                $current = 0.0;
                $newStock = $current + $init;
                $repo->updateStockCurrent($clinicId, $id, number_format($newStock, 3, '.', ''));

                $moveRepo = new StockMovementRepository($pdo);
                $moveRepo->create(
                    $clinicId,
                    $id,
                    'entry',
                    number_format($init, 3, '.', ''),
                    'material',
                    $id,
                    null,
                    number_format($cost, 2, '.', ''),
                    number_format(0.0, 2, '.', ''),
                    'Estoque inicial',
                    $userId
                );
            }

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.materials.create', ['material_id' => $id], $ip);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array{from:string,to:string,movements:list<array<string,mixed>>} */
    public function listMovements(string $from, string $to, int $limit = 200, int $offset = 0): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = $from === '' ? date('Y-m-01') : $from;
        $to = $to === '' ? date('Y-m-d') : $to;

        $limit = max(25, min($limit, 500));
        $offset = max(0, $offset);

        $repo = new StockMovementRepository($this->container->get(\PDO::class));
        return [
            'from' => $from,
            'to' => $to,
            'movements' => $repo->listByClinic($clinicId, $from, $to, $limit, $offset),
        ];
    }

    public function createMovement(
        int $materialId,
        string $type,
        string $quantityStr,
        ?string $lossReason,
        ?string $notes,
        ?string $referenceType,
        ?int $referenceId,
        string $ip
    ): int {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $allowedTypes = ['entry', 'exit', 'adjustment', 'loss', 'expiration'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new \RuntimeException('Tipo inválido.');
        }

        if ($materialId <= 0) {
            throw new \RuntimeException('Material inválido.');
        }

        $qty = $this->parseQty($quantityStr);
        if ($qty <= 0) {
            throw new \RuntimeException('Quantidade inválida.');
        }

        $pdo = $this->container->get(\PDO::class);
        $matRepo = new MaterialRepository($pdo);
        $mat = $matRepo->findById($clinicId, $materialId);
        if ($mat === null) {
            throw new \RuntimeException('Material inválido.');
        }

        $current = (float)$mat['stock_current'];
        $unitCost = (float)$mat['unit_cost'];

        $newStock = $current;
        if ($type === 'entry') {
            $newStock = $current + $qty;
        }
        if ($type === 'exit' || $type === 'loss' || $type === 'expiration') {
            $newStock = $current - $qty;
        }
        if ($type === 'adjustment') {
            $newStock = $qty;
        }

        if ($newStock < 0) {
            throw new \RuntimeException('Estoque insuficiente para a operação.');
        }

        $totalCost = 0.0;
        if ($type === 'exit' || $type === 'loss' || $type === 'expiration') {
            $totalCost = round($unitCost * $qty, 2);
        }

        try {
            $pdo->beginTransaction();

            $matRepo->updateStockCurrent($clinicId, $materialId, number_format($newStock, 3, '.', ''));

            $moveRepo = new StockMovementRepository($pdo);
            $id = $moveRepo->create(
                $clinicId,
                $materialId,
                $type,
                number_format($qty, 3, '.', ''),
                $referenceType,
                $referenceId,
                $lossReason,
                number_format($unitCost, 2, '.', ''),
                number_format($totalCost, 2, '.', ''),
                $notes,
                $userId
            );

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.movements.create', [
                'movement_id' => $id,
                'material_id' => $materialId,
                'type' => $type,
                'quantity' => $qty,
            ], $ip);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Baixa automática baseada no procedimento (service) quando uma sessão/agendamento é finalizado.
     * Idempotente por (reference_type='session', reference_id=appointmentId).
     *
     * @return array{movement_ids:list<int>,total_cost:float}
     */
    public function autoConsumeForAppointment(int $appointmentId, int $serviceId, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($appointmentId <= 0 || $serviceId <= 0) {
            return ['movement_ids' => [], 'total_cost' => 0.0];
        }

        $pdo = $this->container->get(\PDO::class);
        $defaultsRepo = new ServiceMaterialDefaultRepository($pdo);
        $defaults = $defaultsRepo->listByService($clinicId, $serviceId);
        if ($defaults === []) {
            return ['movement_ids' => [], 'total_cost' => 0.0];
        }

        $movRepo = new StockMovementRepository($pdo);

        try {
            $pdo->beginTransaction();

            if ($movRepo->existsForReference($clinicId, 'session', $appointmentId)) {
                $pdo->commit();
                return ['movement_ids' => [], 'total_cost' => 0.0];
            }

            $matRepo = new MaterialRepository($pdo);
            $movementIds = [];
            $totalCostAll = 0.0;

            foreach ($defaults as $d) {
                $materialId = (int)$d['material_id'];
                $qty = (float)$d['quantity_per_session'];
                if ($materialId <= 0 || $qty <= 0) {
                    continue;
                }

                $mat = $matRepo->findByIdForUpdate($clinicId, $materialId);
                if ($mat === null) {
                    continue;
                }

                $current = (float)$mat['stock_current'];
                $unitCost = (float)$mat['unit_cost'];
                $newStock = $current - $qty;
                if ($newStock < 0) {
                    throw new \RuntimeException('Estoque insuficiente para baixa automática.');
                }

                $matRepo->updateStockCurrent($clinicId, $materialId, number_format($newStock, 3, '.', ''));

                $totalCost = round($unitCost * $qty, 2);
                $totalCostAll += $totalCost;

                $mid = $movRepo->create(
                    $clinicId,
                    $materialId,
                    'exit',
                    number_format($qty, 3, '.', ''),
                    'session',
                    $appointmentId,
                    null,
                    number_format($unitCost, 2, '.', ''),
                    number_format($totalCost, 2, '.', ''),
                    'Baixa automática por sessão',
                    $userId
                );

                $movementIds[] = $mid;
            }

            (new AuditLogRepository($pdo))->log($userId, $clinicId, 'stock.auto_consume', [
                'appointment_id' => $appointmentId,
                'service_id' => $serviceId,
                'movement_ids' => $movementIds,
                'total_cost' => $totalCostAll,
            ], $ip);

            $pdo->commit();
            return ['movement_ids' => $movementIds, 'total_cost' => $totalCostAll];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function parseMoney(string $raw): float
    {
        $s = trim($raw);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace(['R$', ' '], '', $s);

        if (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9.\-]/', '', $s);
        $s = $s === null ? '' : $s;

        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        return (float)$s;
    }

    private function parseQty(string $raw): float
    {
        $s = trim($raw);
        if ($s === '') {
            return 0.0;
        }

        if (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
            $s = str_replace(',', '.', $s);
        }

        $s = preg_replace('/[^0-9.\-]/', '', $s);
        $s = $s === null ? '' : $s;

        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        return (float)$s;
    }
}
