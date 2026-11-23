<?php
declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SaasPlanRepository;
use App\Services\System\SystemSettingsService;

/**
 * Programa de clinicas piloto: configuracao global, marcacao da clinica,
 * regras de troca de plano e sincronizacao do valor com o gateway.
 * A regra pura (calculo, prazos, perda do beneficio) fica em PilotDiscountService.
 *
 * Todas as leituras toleram o banco sem as migrations 0176/0177: nesse caso
 * o desconto simplesmente nao existe e o restante da cobranca segue igual.
 */
final class PilotProgramService
{
    private const KEY_PERCENT = 'billing.pilot.discount_percent';
    private const KEY_DURATION = 'billing.pilot.duration_value';
    private const KEY_UNIT = 'billing.pilot.duration_unit';

    private static ?bool $schemaReady = null;

    public function __construct(private readonly Container $container) {}

    private function pdo(): \PDO
    {
        return $this->container->get(\PDO::class);
    }

    /** As colunas das migrations 0176 e 0177 existem? */
    public function schemaReady(): bool
    {
        if (self::$schemaReady !== null) {
            return self::$schemaReady;
        }

        try {
            $this->pdo()->query("
                SELECT c.is_pilot, cs.pilot_discount_percent, cs.pilot_discount_ends_at,
                       cs.pilot_discount_plan_id, cs.pilot_discount_forfeited,
                       cs.pilot_discount_duration_value, cs.pilot_discount_duration_unit,
                       cs.pilot_discount_gateway_state
                FROM clinics c
                LEFT JOIN clinic_subscriptions cs ON cs.clinic_id = c.id
                LIMIT 0
            ");
            self::$schemaReady = true;
        } catch (\Throwable $e) {
            self::$schemaReady = false;
        }

        return self::$schemaReady;
    }

    // ------------------------------------------------------------------
    // Configuracao global (Super Admin > Configuracoes > Clinicas piloto)
    // ------------------------------------------------------------------

    /** @return array{percent:float,duration:int,unit:string,configured:bool} */
    public function getSettings(): array
    {
        $s = new SystemSettingsService($this->container);
        $percent = (float)str_replace(',', '.', (string)($s->getText(self::KEY_PERCENT) ?? '0'));
        $duration = (int)($s->getText(self::KEY_DURATION) ?? '0');
        $unit = (string)($s->getText(self::KEY_UNIT) ?? 'months');
        if (!in_array($unit, PilotDiscountService::UNITS, true)) {
            $unit = 'months';
        }

        $configured = $percent > 0
            && $percent <= PilotDiscountService::MAX_PERCENT
            && $duration > 0
            && $duration <= PilotDiscountService::MAX_DURATION[$unit];

        return ['percent' => $percent, 'duration' => $duration, 'unit' => $unit, 'configured' => $configured];
    }

    public function saveSettings(string $percentRaw, string $durationRaw, string $unit, string $ip): void
    {
        $percentRaw = str_replace(',', '.', trim($percentRaw));
        if ($percentRaw === '' || !is_numeric($percentRaw)) {
            throw new \RuntimeException('Informe a porcentagem de desconto.');
        }
        $percent = round((float)$percentRaw, 2);
        if ($percent <= 0 || $percent > PilotDiscountService::MAX_PERCENT) {
            throw new \RuntimeException('A porcentagem deve ser maior que 0 e no máximo 99,99%.');
        }

        if (!in_array($unit, PilotDiscountService::UNITS, true)) {
            throw new \RuntimeException('Unidade de duração inválida.');
        }

        $durationRaw = trim($durationRaw);
        if ($durationRaw === '' || !ctype_digit($durationRaw) || (int)$durationRaw <= 0) {
            throw new \RuntimeException('Informe a duração como um número inteiro maior que zero.');
        }
        $duration = (int)$durationRaw;
        if ($duration > PilotDiscountService::MAX_DURATION[$unit]) {
            throw new \RuntimeException('Duração muito longa. Máximo: ' . PilotDiscountService::durationLabel(PilotDiscountService::MAX_DURATION[$unit], $unit) . '.');
        }

        $s = new SystemSettingsService($this->container);
        $s->setText(self::KEY_PERCENT, number_format($percent, 2, '.', ''));
        $s->setText(self::KEY_DURATION, (string)$duration);
        $s->setText(self::KEY_UNIT, $unit);

        (new AuditLogRepository($this->pdo()))->log((int)($_SESSION['user_id'] ?? 0) ?: null, null, 'system.pilot.settings', [
            'percent' => $percent,
            'duration' => $duration,
            'unit' => $unit,
        ], $ip);
    }

    // ------------------------------------------------------------------
    // Leitura do snapshot da clinica
    // ------------------------------------------------------------------

    /** @return array<string,mixed>|null clinics.is_pilot + colunas pilot_* da assinatura */
    public function loadRow(int $clinicId): ?array
    {
        if ($clinicId <= 0 || !$this->schemaReady()) {
            return null;
        }

        try {
            $stmt = $this->pdo()->prepare("
                SELECT c.id AS clinic_id, c.is_pilot,
                       cs.id AS subscription_id, cs.plan_id, cs.gateway_provider, cs.asaas_subscription_id,
                       cs.pilot_discount_percent, cs.pilot_discount_starts_at, cs.pilot_discount_ends_at,
                       cs.pilot_discount_plan_id, cs.pilot_discount_forfeited,
                       cs.pilot_discount_duration_value, cs.pilot_discount_duration_unit,
                       cs.pilot_discount_gateway_state
                FROM clinics c
                LEFT JOIN clinic_subscriptions cs ON cs.clinic_id = c.id
                WHERE c.id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $clinicId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return is_array($row) ? $row : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Preco que a clinica paga hoje pelo plano informado (centavos).
     *
     * @param array<string,mixed>|null $plan
     */
    public function effectivePriceCents(int $clinicId, ?array $plan): int
    {
        if ($plan === null) {
            return 0;
        }

        $price = max(0, (int)($plan['price_cents'] ?? 0));
        $row = $this->loadRow($clinicId);
        if ($row === null) {
            return $price;
        }

        return PilotDiscountService::effectivePriceCents($row, $plan);
    }

    // ------------------------------------------------------------------
    // Marcacao da clinica (Super Admin > Clinicas > Editar)
    // ------------------------------------------------------------------

    /**
     * Marca ou desmarca a clinica como piloto.
     * Ao marcar, grava o snapshot com a configuracao atual (porcentagem, duracao, inicio agora).
     * O plano do beneficio e o plano atual, se for pago; senao, o primeiro plano pago que a clinica contratar.
     *
     * @return array{changed:bool,gateway_ok:bool}
     */
    public function setPilot(int $clinicId, bool $isPilot, string $ip): array
    {
        if (!$this->schemaReady()) {
            if ($isPilot) {
                throw new \RuntimeException('O banco de dados ainda não tem as colunas do programa piloto. Abra Servidor → Migrações do banco e execute as pendentes.');
            }
            return ['changed' => false, 'gateway_ok' => true];
        }

        $row = $this->loadRow($clinicId);
        if ($row === null) {
            throw new \RuntimeException('Clínica inválida.');
        }

        $wasPilot = (int)($row['is_pilot'] ?? 0) === 1;
        if ($wasPilot === $isPilot) {
            return ['changed' => false, 'gateway_ok' => true];
        }

        $pdo = $this->pdo();
        $userId = (int)($_SESSION['user_id'] ?? 0) ?: null;

        if ($isPilot) {
            $cfg = $this->getSettings();
            if (!$cfg['configured']) {
                throw new \RuntimeException('Configure a porcentagem e a duração do desconto em Configurações → Clínicas piloto antes de marcar uma clínica como piloto.');
            }

            $data = (new BillingService($this->container))->getOrCreateClinicSubscription($clinicId);
            $plan = $data['plan'];
            $planId = is_array($plan) ? (int)($plan['id'] ?? 0) : 0;
            $planPrice = is_array($plan) ? (int)($plan['price_cents'] ?? 0) : 0;
            $benefitPlanId = ($planId > 0 && $planPrice > 0) ? $planId : null;

            $now = new \DateTimeImmutable('now');
            $endsAt = PilotDiscountService::computeEndsAt($now, $cfg['duration'], $cfg['unit']);

            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE clinics SET is_pilot = 1 WHERE id = :id')->execute(['id' => $clinicId]);
                $pdo->prepare("
                    UPDATE clinic_subscriptions
                    SET pilot_discount_percent = :percent,
                        pilot_discount_starts_at = :starts_at,
                        pilot_discount_ends_at = :ends_at,
                        pilot_discount_plan_id = :plan_id,
                        pilot_discount_forfeited = 0,
                        pilot_discount_duration_value = :duration,
                        pilot_discount_duration_unit = :unit,
                        pilot_discount_gateway_state = NULL,
                        updated_at = NOW()
                    WHERE clinic_id = :clinic_id
                    LIMIT 1
                ")->execute([
                    'percent' => number_format($cfg['percent'], 2, '.', ''),
                    'starts_at' => $now->format('Y-m-d H:i:s'),
                    'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                    'plan_id' => $benefitPlanId,
                    'duration' => $cfg['duration'],
                    'unit' => $cfg['unit'],
                    'clinic_id' => $clinicId,
                ]);
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            (new AuditLogRepository($pdo))->log($userId, null, 'system.clinics.pilot_enroll', [
                'clinic_id' => $clinicId,
                'percent' => $cfg['percent'],
                'duration' => $cfg['duration'],
                'unit' => $cfg['unit'],
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                'benefit_plan_id' => $benefitPlanId,
            ], $ip);
        } else {
            $pdo->prepare('UPDATE clinics SET is_pilot = 0 WHERE id = :id')->execute(['id' => $clinicId]);
            (new AuditLogRepository($pdo))->log($userId, null, 'system.clinics.pilot_unenroll', ['clinic_id' => $clinicId], $ip);
        }

        return ['changed' => true, 'gateway_ok' => $this->syncGateway($clinicId)];
    }

    // ------------------------------------------------------------------
    // Troca de plano
    // ------------------------------------------------------------------

    /**
     * Chamar logo depois de gravar o novo plan_id e ANTES de sincronizar o valor com o gateway.
     * - Ainda sem plano com beneficio e novo plano pago: o novo plano passa a ter o beneficio.
     * - Ja tinha plano com beneficio e mudou para outro: perde o beneficio definitivamente.
     */
    public function onPlanChanged(int $clinicId, int $newPlanId, ?string $ip = null): void
    {
        $row = $this->loadRow($clinicId);
        if ($row === null) {
            return;
        }

        $plan = $newPlanId > 0 ? (new SaasPlanRepository($this->pdo()))->findById($newPlanId) : null;
        $price = is_array($plan) ? (int)($plan['price_cents'] ?? 0) : 0;

        $action = PilotDiscountService::planChangeAction($row, $newPlanId, $price);
        if ($action === 'none') {
            return;
        }

        $pdo = $this->pdo();
        if ($action === 'bind') {
            $pdo->prepare('UPDATE clinic_subscriptions SET pilot_discount_plan_id = :plan_id, updated_at = NOW() WHERE clinic_id = :clinic_id LIMIT 1')
                ->execute(['plan_id' => $newPlanId, 'clinic_id' => $clinicId]);
        } else {
            $pdo->prepare('UPDATE clinic_subscriptions SET pilot_discount_forfeited = 1, updated_at = NOW() WHERE clinic_id = :clinic_id LIMIT 1')
                ->execute(['clinic_id' => $clinicId]);
        }

        try {
            (new AuditLogRepository($pdo))->log(
                (int)($_SESSION['user_id'] ?? 0) ?: null,
                $clinicId,
                $action === 'bind' ? 'billing.pilot.bind_plan' : 'billing.pilot.forfeit',
                ['benefit_plan_id' => (int)($row['pilot_discount_plan_id'] ?? 0) ?: null, 'new_plan_id' => $newPlanId],
                $ip
            );
        } catch (\Throwable $e) {
            // auditoria nao pode impedir a troca de plano
        }
    }

    // ------------------------------------------------------------------
    // Gateway
    // ------------------------------------------------------------------

    /** Estado que o gateway deveria estar cobrando agora: 'discount' ou 'full'. */
    private function desiredState(array $row, ?array $plan): string
    {
        if ($plan === null || (int)($plan['price_cents'] ?? 0) <= 0) {
            return 'full';
        }

        return PilotDiscountService::isActive($row) ? 'discount' : 'full';
    }

    /** Grava o estado depois que o valor foi enviado ao gateway com sucesso. */
    public function markGatewaySynced(int $clinicId, ?array $plan): void
    {
        $row = $this->loadRow($clinicId);
        if ($row === null || $row['pilot_discount_percent'] === null) {
            return;
        }

        try {
            $this->pdo()->prepare('UPDATE clinic_subscriptions SET pilot_discount_gateway_state = :state WHERE clinic_id = :clinic_id LIMIT 1')
                ->execute(['state' => $this->desiredState($row, $plan), 'clinic_id' => $clinicId]);
        } catch (\Throwable $e) {
            // sem a coluna: nada a fazer
        }
    }

    /** Envia o valor atual (com ou sem desconto) ao gateway, atualizando tambem cobrancas pendentes. */
    private function syncGateway(int $clinicId): bool
    {
        try {
            (new BillingGatewayService($this->container))->syncGatewaySubscriptionAmount($clinicId, true);
            return true;
        } catch (\Throwable $e) {
            error_log('[LumiClinic] Pilot discount gateway sync failed for clinic ' . $clinicId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Corrige no gateway o valor das clinicas piloto cujo desconto venceu, foi perdido,
     * foi removido ou ainda nao foi enviado. Chamado pelo cron (cron-queue.php / bin/queue_cron.php).
     *
     * @return int quantidade de assinaturas atualizadas
     */
    public function syncPendingGatewayValues(int $limit = 50): int
    {
        if (!$this->schemaReady()) {
            return 0;
        }

        try {
            $stmt = $this->pdo()->query("
                SELECT c.id AS clinic_id, c.is_pilot,
                       cs.plan_id, cs.pilot_discount_percent, cs.pilot_discount_ends_at,
                       cs.pilot_discount_plan_id, cs.pilot_discount_forfeited,
                       cs.pilot_discount_gateway_state,
                       p.id AS p_id, p.price_cents AS p_price_cents
                FROM clinic_subscriptions cs
                JOIN clinics c ON c.id = cs.clinic_id
                LEFT JOIN saas_plans p ON p.id = cs.plan_id
                WHERE cs.pilot_discount_percent IS NOT NULL
                  AND cs.gateway_provider = 'asaas'
                  AND cs.asaas_subscription_id IS NOT NULL
                  AND cs.asaas_subscription_id <> ''
                  AND c.deleted_at IS NULL
            ");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return 0;
        }

        $done = 0;
        foreach ($rows as $row) {
            if ($done >= $limit) {
                break;
            }
            $plan = $row['p_id'] !== null ? ['id' => (int)$row['p_id'], 'price_cents' => (int)$row['p_price_cents']] : null;
            $desired = $this->desiredState($row, $plan);
            if ((string)($row['pilot_discount_gateway_state'] ?? '') === $desired) {
                continue;
            }
            if ($this->syncGateway((int)$row['clinic_id'])) {
                $done++;
            }
        }

        return $done;
    }

    /** Mesma correcao, so para uma clinica (ao abrir a tela de assinatura). */
    public function syncClinicIfNeeded(int $clinicId): void
    {
        $row = $this->loadRow($clinicId);
        if ($row === null || $row['pilot_discount_percent'] === null) {
            return;
        }
        if ((string)($row['gateway_provider'] ?? '') !== 'asaas' || trim((string)($row['asaas_subscription_id'] ?? '')) === '') {
            return;
        }

        $planId = (int)($row['plan_id'] ?? 0);
        $plan = $planId > 0 ? (new SaasPlanRepository($this->pdo()))->findById($planId) : null;
        if ((string)($row['pilot_discount_gateway_state'] ?? '') !== $this->desiredState($row, $plan)) {
            $this->syncGateway($clinicId);
        }
    }

    // ------------------------------------------------------------------
    // Exibicao
    // ------------------------------------------------------------------

    /**
     * Dados da condicao piloto para a tela "Minha assinatura".
     * Retorna null quando a clinica nao e piloto, o prazo venceu ou o beneficio foi perdido.
     *
     * @param list<array<string,mixed>> $plans
     * @return array{percent:float,percent_label:string,duration_label:string,ends_at:string,plan_id:?int,prices:array<int,int>}|null
     */
    public function clinicOffer(int $clinicId, array $plans): ?array
    {
        $row = $this->loadRow($clinicId);
        if ($row === null || !PilotDiscountService::isWithinPeriod($row)) {
            return null;
        }

        $benefitPlanId = (int)($row['pilot_discount_plan_id'] ?? 0);
        $currentPlanId = (int)($row['plan_id'] ?? 0);
        if ($benefitPlanId > 0 && $currentPlanId !== $benefitPlanId) {
            // Nao deveria acontecer (a troca de plano marca a perda), mas nao exibe condicao invalida.
            return null;
        }

        $prices = [];
        foreach ($plans as $p) {
            $pid = (int)($p['id'] ?? 0);
            $price = (int)($p['price_cents'] ?? 0);
            if ($pid <= 0 || $price <= 0) {
                continue;
            }
            $eff = PilotDiscountService::effectivePriceCents($row, $p);
            if ($eff < $price) {
                $prices[$pid] = $eff;
            }
        }

        $percent = (float)$row['pilot_discount_percent'];
        $duration = (int)($row['pilot_discount_duration_value'] ?? 0);
        $unit = (string)($row['pilot_discount_duration_unit'] ?? '');

        return [
            'percent' => $percent,
            'percent_label' => PilotDiscountService::percentLabel($percent),
            'duration_label' => PilotDiscountService::durationLabel($duration, $unit),
            'ends_at' => (string)$row['pilot_discount_ends_at'],
            'plan_id' => $benefitPlanId > 0 ? $benefitPlanId : null,
            'prices' => $prices,
        ];
    }

    /**
     * Situacao do programa piloto para a tela de edicao da clinica no Super Admin.
     *
     * @return array<string,mixed>
     */
    public function adminInfo(int $clinicId): array
    {
        if (!$this->schemaReady()) {
            return ['schema_ready' => false];
        }

        $row = $this->loadRow($clinicId) ?? [];
        $info = [
            'schema_ready' => true,
            'is_pilot' => (int)($row['is_pilot'] ?? 0) === 1,
            'has_snapshot' => isset($row['pilot_discount_percent']) && $row['pilot_discount_percent'] !== null,
            'settings' => $this->getSettings(),
        ];

        if ($info['has_snapshot']) {
            $info += $this->describeRow($row);
        }

        return $info;
    }

    /** @return list<array<string,mixed>> clinicas que ja foram marcadas como piloto */
    public function listPilotClinics(): array
    {
        if (!$this->schemaReady()) {
            return [];
        }

        try {
            $rows = $this->pdo()->query("
                SELECT c.id AS clinic_id, c.name, c.is_pilot,
                       cs.plan_id, cs.pilot_discount_percent, cs.pilot_discount_starts_at, cs.pilot_discount_ends_at,
                       cs.pilot_discount_plan_id, cs.pilot_discount_forfeited,
                       cs.pilot_discount_duration_value, cs.pilot_discount_duration_unit
                FROM clinics c
                LEFT JOIN clinic_subscriptions cs ON cs.clinic_id = c.id
                WHERE c.deleted_at IS NULL
                  AND (c.is_pilot = 1 OR cs.pilot_discount_percent IS NOT NULL)
                ORDER BY c.is_pilot DESC, cs.pilot_discount_ends_at DESC, c.name
                LIMIT 300
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $item = ['clinic_id' => (int)$row['clinic_id'], 'name' => (string)$row['name'], 'is_pilot' => (int)$row['is_pilot'] === 1];
            if ($row['pilot_discount_percent'] !== null) {
                $item += $this->describeRow($row);
            }
            $out[] = $item;
        }

        return $out;
    }

    /** @param array<string,mixed> $row */
    private function describeRow(array $row): array
    {
        $benefitPlanId = (int)($row['pilot_discount_plan_id'] ?? 0);
        $planName = null;
        if ($benefitPlanId > 0) {
            $p = (new SaasPlanRepository($this->pdo()))->findById($benefitPlanId);
            $planName = $p !== null ? (string)($p['name'] ?? $p['code'] ?? ('Plano #' . $benefitPlanId)) : ('Plano #' . $benefitPlanId);
        }

        if ((int)($row['is_pilot'] ?? 0) !== 1) {
            $status = 'removed';
        } elseif ((int)($row['pilot_discount_forfeited'] ?? 0) === 1) {
            $status = 'forfeited';
        } elseif (PilotDiscountService::isWithinPeriod($row)) {
            $status = 'active';
        } else {
            $status = 'expired';
        }

        $percent = (float)($row['pilot_discount_percent'] ?? 0);

        return [
            'status' => $status,
            'percent_label' => PilotDiscountService::percentLabel($percent),
            'duration_label' => PilotDiscountService::durationLabel((int)($row['pilot_discount_duration_value'] ?? 0), (string)($row['pilot_discount_duration_unit'] ?? '')),
            'starts_at' => (string)($row['pilot_discount_starts_at'] ?? ''),
            'ends_at' => (string)($row['pilot_discount_ends_at'] ?? ''),
            'benefit_plan_name' => $planName,
        ];
    }
}
