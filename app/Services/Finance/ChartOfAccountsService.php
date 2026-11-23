<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ChartAccountRepository;
use App\Repositories\CostCenterRepository;
use App\Services\Auth\AuthService;

final class ChartOfAccountsService
{
    public const NATURES = [
        'asset' => 'Ativo',
        'liability' => 'Passivo',
        'equity' => 'Patrimônio Líquido',
        'revenue' => 'Receita',
        'expense' => 'Custo / Despesa',
    ];

    public const TYPES = [
        'synthetic' => 'Sintética (grupo)',
        'analytic' => 'Analítica (recebe lançamentos)',
    ];

    /** Mesmos códigos de forma de pagamento usados em pagamentos e lançamentos do app. */
    public const PAYMENT_METHODS = [
        'cash' => 'Dinheiro',
        'pix' => 'Pix',
        'credit_card' => 'Cartão de crédito',
        'debit_card' => 'Cartão de débito',
        'card' => 'Cartão (não especificado)',
        'boleto' => 'Boleto',
        'transfer' => 'Transferência',
        'check' => 'Cheque',
        'other' => 'Outro',
    ];

    public const DEFAULT_FOR = [
        'revenue' => 'Receitas sem classificação',
        'expense' => 'Despesas sem classificação',
        'financial' => 'Conta financeira de lançamentos sem forma de pagamento',
    ];

    /** Naturezas que podem ser vinculadas a centros de custo (conta de resultado do lançamento). */
    public const COST_CENTER_NATURES = ['revenue', 'expense', 'liability'];

    /** Natureza exigida para cada opção de conta padrão. */
    private const DEFAULT_FOR_NATURE = [
        'revenue' => 'revenue',
        'expense' => 'expense',
        'financial' => 'asset',
    ];

    /** Natureza pelo primeiro segmento do código no modelo padrão. */
    private const TEMPLATE_NATURE_BY_ROOT = [
        '1' => 'asset',
        '2' => 'liability',
        '3' => 'equity',
        '4' => 'revenue',
        '5' => 'expense',
    ];

    /**
     * Modelo padrão para clínicas: [código, nome, tipo (S = sintética, A = analítica), forma de pagamento, conta padrão para].
     * A conta pai é resolvida pelo prefixo do código e a natureza pelo primeiro segmento.
     *
     * @var list<array{0:string,1:string,2:string,3:?string,4:?string}>
     */
    public const DEFAULT_TEMPLATE = [
        ['1', 'ATIVO', 'S', null, null],
        ['1.1', 'ATIVO CIRCULANTE', 'S', null, null],
        ['1.1.1', 'Caixa', 'S', null, null],
        ['1.1.1.01', 'Caixa Geral', 'A', 'cash', null],
        ['1.1.2', 'Bancos e Cartões', 'S', null, null],
        ['1.1.2.01', 'Boleto', 'A', 'boleto', null],
        ['1.1.2.02', 'Cartão de Crédito', 'A', 'credit_card', null],
        ['1.1.2.03', 'Cartão de Débito', 'A', 'debit_card', null],
        ['1.1.2.04', 'Cheque', 'A', 'check', null],
        ['1.1.2.05', 'Conta Banco', 'A', 'other', 'financial'],
        ['1.1.2.06', 'Cartão (não especificado)', 'A', 'card', null],
        ['1.1.2.07', 'Transferência', 'A', 'transfer', null],
        ['1.1.2.08', 'Pix', 'A', 'pix', null],
        ['1.1.3', 'Contas a Receber', 'S', null, null],
        ['1.1.3.01', 'Clientes', 'A', null, null],
        ['1.1.3.02', 'Outras Contas a Receber', 'A', null, null],
        ['1.1.3.03', 'Planos de Saúde', 'A', null, null],
        ['1.1.3.04', 'Financiamento', 'A', null, null],
        ['1.1.4', 'Planos e Convênios', 'S', null, null],
        ['1.1.4.01', 'Convênios', 'A', null, null],

        ['2', 'PASSIVO', 'S', null, null],
        ['2.1', 'PASSIVO CIRCULANTE', 'S', null, null],
        ['2.1.01', 'Comissões Profissionais', 'A', null, null],
        ['2.1.02', 'Provisão Comissões Profissionais', 'A', null, null],
        ['2.1.2', 'Contas a Pagar', 'S', null, null],
        ['2.1.2.01', 'Fornecedores', 'A', null, null],
        ['2.1.2.02', 'Salários a Pagar', 'A', null, null],
        ['2.1.2.03', 'Encargos Sociais a Pagar (INSS/FGTS)', 'A', null, null],
        ['2.1.2.04', 'Impostos a Recolher', 'A', null, null],

        ['3', 'PATRIMÔNIO LÍQUIDO', 'S', null, null],
        ['3.1', 'Capital Social', 'A', null, null],
        ['3.2', 'Lucros ou Prejuízos Acumulados', 'A', null, null],

        ['4', 'RECEITAS', 'S', null, null],
        ['4.1', 'Receita Operacional', 'S', null, null],
        ['4.1.01', 'Procedimentos Estéticos', 'A', null, 'revenue'],
        ['4.1.02', 'Consultas e Avaliações', 'A', null, null],
        ['4.1.03', 'Venda de Produtos', 'A', null, null],
        ['4.1.04', 'Pacotes e Assinaturas', 'A', null, null],
        ['4.2', 'Receitas Financeiras', 'S', null, null],
        ['4.2.01', 'Rendimentos de Aplicações', 'A', null, null],
        ['4.3', 'Deduções da Receita', 'S', null, null],
        ['4.3.01', 'Descontos Concedidos', 'A', null, null],
        ['4.3.02', 'Impostos sobre Serviços (ISS / Simples Nacional)', 'A', null, null],

        ['5', 'CUSTOS E DESPESAS', 'S', null, null],
        ['5.1', 'Custos dos Serviços', 'S', null, null],
        ['5.1.01', 'Materiais e Insumos', 'A', null, null],
        ['5.1.02', 'Comissões sobre Procedimentos', 'A', null, null],
        ['5.2', 'Despesas com Pessoal', 'S', null, null],
        ['5.2.01', 'Salários', 'A', null, null],
        ['5.2.02', '13º Salário', 'A', null, null],
        ['5.2.03', 'Férias', 'A', null, null],
        ['5.2.04', 'Encargos Sociais (INSS/FGTS)', 'A', null, null],
        ['5.2.05', 'Benefícios (VT/VR)', 'A', null, null],
        ['5.2.06', 'Pró-labore', 'A', null, null],
        ['5.3', 'Despesas Administrativas', 'S', null, null],
        ['5.3.01', 'Aluguel e Condomínio', 'A', null, null],
        ['5.3.02', 'Energia Elétrica', 'A', null, null],
        ['5.3.03', 'Água e Esgoto', 'A', null, null],
        ['5.3.04', 'Telefone e Internet', 'A', null, null],
        ['5.3.05', 'Honorários Contábeis', 'A', null, null],
        ['5.3.06', 'Softwares e Sistemas', 'A', null, null],
        ['5.3.07', 'Material de Escritório e Limpeza', 'A', null, null],
        ['5.3.08', 'Manutenção e Reparos', 'A', null, null],
        ['5.4', 'Despesas Comerciais', 'S', null, null],
        ['5.4.01', 'Marketing e Publicidade', 'A', null, null],
        ['5.5', 'Despesas Financeiras', 'S', null, null],
        ['5.5.01', 'Tarifas Bancárias', 'A', null, null],
        ['5.5.02', 'Taxas de Cartão', 'A', null, null],
        ['5.5.03', 'Juros e Multas', 'A', null, null],
        ['5.6', 'Impostos e Taxas', 'S', null, null],
        ['5.6.01', 'Impostos e Taxas Diversas', 'A', null, null],
        ['5.9', 'Outras Despesas', 'S', null, null],
        ['5.9.01', 'Outras Despesas', 'A', null, 'expense'],
    ];

    public function __construct(private readonly Container $container) {}

    /** @return array{0:int,1:int} [clinicId, actorId] */
    private function context(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return [$clinicId, $actorId];
    }

    private function clinicIdOrFail(): int
    {
        $clinicId = (new AuthService($this->container))->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return $clinicId;
    }

    /** @param array<string,mixed> $meta */
    private function audit(\PDO $pdo, int $actorId, int $clinicId, string $action, array $meta, ?int $entityId, string $ip, ?string $userAgent): void
    {
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        (new AuditLogRepository($pdo))->log($actorId, $clinicId, $action, $meta, $ip, $roleCodes, 'chart_account', $entityId, $userAgent);
    }

    /**
     * Contas da clínica ordenadas por código, com 'depth' (0 = raiz) e 'linked_cost_centers'.
     *
     * @return list<array<string,mixed>>
     */
    public function listAccounts(): array
    {
        $clinicId = $this->clinicIdOrFail();
        $rows = (new ChartAccountRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);

        $parentById = [];
        foreach ($rows as $r) {
            $parentById[(int)$r['id']] = isset($r['parent_id']) && $r['parent_id'] !== null ? (int)$r['parent_id'] : null;
        }

        foreach ($rows as $i => $r) {
            $depth = 0;
            $cursor = $parentById[(int)$r['id']] ?? null;
            while ($cursor !== null && isset($parentById[$cursor]) && $depth < 20) {
                $depth++;
                $cursor = $parentById[$cursor];
            }
            $rows[$i]['depth'] = $depth;
        }

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    public function listActiveAnalyticAccounts(): array
    {
        $clinicId = $this->clinicIdOrFail();
        return (new ChartAccountRepository($this->container->get(\PDO::class)))->listActiveAnalytic($clinicId);
    }

    /**
     * Contas ativas e analíticas que podem ser vinculadas a centros de custo (Receita, Custo / Despesa, Passivo).
     * Contas do Ativo ficam de fora: elas são a contrapartida financeira (forma de pagamento) do lançamento.
     *
     * @return list<array<string,mixed>>
     */
    public function listCostCenterAccounts(): array
    {
        return array_values(array_filter(
            $this->listActiveAnalyticAccounts(),
            static fn (array $a): bool => in_array((string)($a['nature'] ?? ''), self::COST_CENTER_NATURES, true)
        ));
    }

    /** @return array<string,mixed>|null */
    public function getAccount(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $clinicId = $this->clinicIdOrFail();
        return (new ChartAccountRepository($this->container->get(\PDO::class)))->findById($clinicId, $id);
    }

    /**
     * Valida e normaliza os dados de uma conta.
     *
     * @param array<string,mixed> $input
     * @param array<string,mixed>|null $current conta atual (edição) ou null (criação)
     * @return array{parent_id:?int,code:string,name:string,nature:string,account_type:string,payment_method:?string,default_for:?string,external_code:?string,description:?string}
     */
    private function validate(ChartAccountRepository $repo, int $clinicId, array $input, ?array $current): array
    {
        $id = $current !== null ? (int)$current['id'] : null;

        $code = trim((string)($input['code'] ?? ''));
        $name = trim((string)($input['name'] ?? ''));
        $nature = trim((string)($input['nature'] ?? ''));
        $type = trim((string)($input['account_type'] ?? ''));
        $parentId = (int)($input['parent_id'] ?? 0);
        $paymentMethod = trim((string)($input['payment_method'] ?? ''));
        $defaultFor = trim((string)($input['default_for'] ?? ''));
        $externalCode = trim((string)($input['external_code'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));

        if ($code === '') {
            throw new \RuntimeException('Código é obrigatório.');
        }
        if (!preg_match('/^\d+(\.\d+)*$/', $code)) {
            throw new \RuntimeException('Código inválido. Use apenas números separados por ponto (ex: 1.1.2.08).');
        }
        if (strlen($code) > 32) {
            throw new \RuntimeException('O código deve ter no máximo 32 caracteres.');
        }
        if ($name === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }
        if (mb_strlen($name) > 190) {
            throw new \RuntimeException('O nome deve ter no máximo 190 caracteres.');
        }
        if (mb_strlen($externalCode) > 64) {
            throw new \RuntimeException('O código externo deve ter no máximo 64 caracteres.');
        }
        if (mb_strlen($description) > 255) {
            throw new \RuntimeException('A descrição deve ter no máximo 255 caracteres.');
        }

        $parent = null;
        if ($parentId > 0) {
            $parent = $repo->findById($clinicId, $parentId);
            if ($parent === null) {
                throw new \RuntimeException('Conta pai inválida.');
            }
            if ($nature === '') {
                $nature = (string)$parent['nature'];
            }
        }

        if ($nature === '') {
            throw new \RuntimeException('Selecione a natureza da conta (ou uma conta pai).');
        }
        if (!array_key_exists($nature, self::NATURES)) {
            throw new \RuntimeException('Natureza inválida.');
        }
        if (!array_key_exists($type, self::TYPES)) {
            throw new \RuntimeException('Tipo de conta inválido.');
        }

        if ($parent !== null) {
            if ($id !== null && (int)$parent['id'] === $id) {
                throw new \RuntimeException('Uma conta não pode ser pai dela mesma.');
            }
            if ((string)$parent['account_type'] !== 'synthetic') {
                throw new \RuntimeException('A conta pai deve ser sintética (grupo).');
            }
            if ((string)$parent['nature'] !== $nature) {
                throw new \RuntimeException('A conta deve ter a mesma natureza da conta pai (' . self::NATURES[(string)$parent['nature']] . ').');
            }
            $parentCode = (string)$parent['code'];
            if (!str_starts_with($code, $parentCode . '.')) {
                throw new \RuntimeException('O código deve começar com o código da conta pai seguido de ponto (ex: ' . $parentCode . '.01).');
            }

            if ($id !== null) {
                $cursor = $parent;
                $guard = 0;
                while ($cursor !== null && $guard < 50) {
                    if ((int)$cursor['id'] === $id) {
                        throw new \RuntimeException('Uma conta não pode ficar abaixo de uma de suas contas filhas.');
                    }
                    $next = isset($cursor['parent_id']) && $cursor['parent_id'] !== null ? (int)$cursor['parent_id'] : 0;
                    $cursor = $next > 0 ? $repo->findById($clinicId, $next) : null;
                    $guard++;
                }
            }
        }

        if ($paymentMethod !== '') {
            if (!array_key_exists($paymentMethod, self::PAYMENT_METHODS)) {
                throw new \RuntimeException('Forma de pagamento inválida.');
            }
            if ($type !== 'analytic' || $nature !== 'asset') {
                throw new \RuntimeException('Forma de pagamento só pode ser vinculada a contas analíticas do Ativo (ex: Caixa Geral, Pix, Cartão).');
            }
            $other = $repo->findByPaymentMethod($clinicId, $paymentMethod);
            if ($other !== null && (int)$other['id'] !== (int)($id ?? 0)) {
                throw new \RuntimeException(
                    'A forma de pagamento "' . self::PAYMENT_METHODS[$paymentMethod] . '" já está vinculada à conta '
                    . (string)$other['code'] . ' - ' . (string)$other['name'] . '.'
                );
            }
        }

        if ($defaultFor !== '') {
            if (!array_key_exists($defaultFor, self::DEFAULT_FOR)) {
                throw new \RuntimeException('Opção de conta padrão inválida.');
            }
            if ($type !== 'analytic' || $nature !== self::DEFAULT_FOR_NATURE[$defaultFor]) {
                throw new \RuntimeException(match ($defaultFor) {
                    'revenue' => 'Somente contas analíticas de Receita podem ser a conta padrão para receitas sem classificação.',
                    'expense' => 'Somente contas analíticas de Custo / Despesa podem ser a conta padrão para despesas sem classificação.',
                    default => 'Somente contas analíticas do Ativo (ex: Conta Banco, Caixa Geral) podem ser a conta financeira padrão.',
                });
            }
        }

        if ($current !== null) {
            $hasChildren = $repo->hasChildren($clinicId, (int)$current['id']);
            if ($hasChildren) {
                if ($code !== (string)$current['code']) {
                    throw new \RuntimeException('Não é possível alterar o código de uma conta que possui contas filhas.');
                }
                if ($nature !== (string)$current['nature']) {
                    throw new \RuntimeException('Não é possível alterar a natureza de uma conta que possui contas filhas.');
                }
                if ($type !== 'synthetic') {
                    throw new \RuntimeException('Uma conta com contas filhas precisa ser sintética (grupo).');
                }
            }
            if ($type === 'synthetic' && (string)$current['account_type'] !== 'synthetic') {
                $linked = $repo->countLinkedCostCenters($clinicId, (int)$current['id']);
                if ($linked > 0) {
                    throw new \RuntimeException('Não é possível tornar a conta sintética: há ' . $linked . ' centro(s) de custo vinculado(s) a ela.');
                }
            }
            if ($nature !== (string)$current['nature'] && !in_array($nature, self::COST_CENTER_NATURES, true)) {
                $linked = $repo->countLinkedCostCenters($clinicId, (int)$current['id']);
                if ($linked > 0) {
                    throw new \RuntimeException('Não é possível mudar a natureza para ' . self::NATURES[$nature] . ': há ' . $linked . ' centro(s) de custo vinculado(s) a esta conta.');
                }
            }
        }

        return [
            'parent_id' => $parent !== null ? (int)$parent['id'] : null,
            'code' => $code,
            'name' => $name,
            'nature' => $nature,
            'account_type' => $type,
            'payment_method' => $paymentMethod === '' ? null : $paymentMethod,
            'default_for' => $defaultFor === '' ? null : $defaultFor,
            'external_code' => $externalCode === '' ? null : $externalCode,
            'description' => $description === '' ? null : $description,
        ];
    }

    /** @param array<string,mixed> $input */
    public function createAccount(array $input, string $ip, ?string $userAgent = null): int
    {
        [$clinicId, $actorId] = $this->context();
        $pdo = $this->container->get(\PDO::class);
        $repo = new ChartAccountRepository($pdo);

        $data = $this->validate($repo, $clinicId, $input, null);

        $existing = $repo->findByCodeIncludingDeleted($clinicId, $data['code']);
        $restored = false;
        if ($existing !== null) {
            $deletedAt = $existing['deleted_at'] ?? null;
            if ($deletedAt === null || trim((string)$deletedAt) === '') {
                throw new \RuntimeException('Já existe uma conta com este código.');
            }
            $id = (int)$existing['id'];
            $repo->restore($clinicId, $id);
            $repo->update($clinicId, $id, $data);
            $restored = true;
        } else {
            $id = $repo->create($clinicId, $data);
        }

        if ($data['default_for'] !== null) {
            $repo->clearDefaultFor($clinicId, $data['default_for'], $id);
        }

        $this->audit($pdo, $actorId, $clinicId, 'finance.chart_accounts.create', [
            'chart_account_id' => $id,
            'code' => $data['code'],
            'nature' => $data['nature'],
            'account_type' => $data['account_type'],
            'payment_method' => $data['payment_method'],
            'default_for' => $data['default_for'],
            'restored' => $restored,
        ], $id, $ip, $userAgent);

        return $id;
    }

    /** @param array<string,mixed> $input */
    public function updateAccount(int $id, array $input, string $ip, ?string $userAgent = null): void
    {
        [$clinicId, $actorId] = $this->context();
        $pdo = $this->container->get(\PDO::class);
        $repo = new ChartAccountRepository($pdo);

        $current = $id > 0 ? $repo->findById($clinicId, $id) : null;
        if ($current === null) {
            throw new \RuntimeException('Conta inválida.');
        }

        $data = $this->validate($repo, $clinicId, $input, $current);

        if ($data['code'] !== (string)$current['code']) {
            $existing = $repo->findByCodeIncludingDeleted($clinicId, $data['code']);
            if ($existing !== null && (int)$existing['id'] !== $id) {
                $deletedAt = $existing['deleted_at'] ?? null;
                if ($deletedAt === null || trim((string)$deletedAt) === '') {
                    throw new \RuntimeException('Já existe uma conta com este código.');
                }
                throw new \RuntimeException('Este código pertence a uma conta excluída. Para reutilizá-lo, crie uma nova conta com este código (a conta excluída será restaurada).');
            }
        }

        $repo->update($clinicId, $id, $data);

        if ($data['default_for'] !== null) {
            $repo->clearDefaultFor($clinicId, $data['default_for'], $id);
        }

        $this->audit($pdo, $actorId, $clinicId, 'finance.chart_accounts.update', [
            'chart_account_id' => $id,
            'before' => [
                'parent_id' => $current['parent_id'] !== null ? (int)$current['parent_id'] : null,
                'code' => (string)$current['code'],
                'name' => (string)$current['name'],
                'nature' => (string)$current['nature'],
                'account_type' => (string)$current['account_type'],
                'payment_method' => $current['payment_method'],
                'default_for' => $current['default_for'],
                'external_code' => $current['external_code'],
            ],
            'after' => [
                'parent_id' => $data['parent_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'nature' => $data['nature'],
                'account_type' => $data['account_type'],
                'payment_method' => $data['payment_method'],
                'default_for' => $data['default_for'],
                'external_code' => $data['external_code'],
            ],
        ], $id, $ip, $userAgent);
    }

    public function setAccountStatus(int $id, string $status, string $ip, ?string $userAgent = null): void
    {
        [$clinicId, $actorId] = $this->context();

        $status = trim($status);
        if (!in_array($status, ['active', 'disabled'], true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new ChartAccountRepository($pdo);
        $current = $id > 0 ? $repo->findById($clinicId, $id) : null;
        if ($current === null) {
            throw new \RuntimeException('Conta inválida.');
        }

        if ($status === 'active') {
            $method = (string)($current['payment_method'] ?? '');
            if ($method !== '') {
                $other = $repo->findByPaymentMethod($clinicId, $method);
                if ($other !== null && (int)$other['id'] !== $id) {
                    throw new \RuntimeException(
                        'Não é possível ativar: a forma de pagamento "' . (self::PAYMENT_METHODS[$method] ?? $method)
                        . '" já está vinculada à conta ' . (string)$other['code'] . ' - ' . (string)$other['name'] . '.'
                    );
                }
            }
        }

        $repo->setStatus($clinicId, $id, $status);

        $defaultFor = (string)($current['default_for'] ?? '');
        if ($status === 'active' && $defaultFor !== '') {
            $repo->clearDefaultFor($clinicId, $defaultFor, $id);
        }

        $this->audit($pdo, $actorId, $clinicId, 'finance.chart_accounts.set_status', [
            'chart_account_id' => $id,
            'code' => (string)$current['code'],
            'status' => $status,
        ], $id, $ip, $userAgent);
    }

    public function deleteAccount(int $id, string $ip, ?string $userAgent = null): void
    {
        [$clinicId, $actorId] = $this->context();

        $pdo = $this->container->get(\PDO::class);
        $repo = new ChartAccountRepository($pdo);
        $current = $id > 0 ? $repo->findById($clinicId, $id) : null;
        if ($current === null) {
            throw new \RuntimeException('Conta inválida.');
        }

        if ($repo->hasChildren($clinicId, $id)) {
            throw new \RuntimeException('Não é possível excluir: a conta possui contas filhas. Exclua ou mova as contas filhas primeiro.');
        }

        $linked = $repo->countLinkedCostCenters($clinicId, $id);
        if ($linked > 0) {
            throw new \RuntimeException('Não é possível excluir: há ' . $linked . ' centro(s) de custo vinculado(s) a esta conta. Altere o vínculo nos centros de custo primeiro.');
        }

        $repo->softDelete($clinicId, $id);

        $this->audit($pdo, $actorId, $clinicId, 'finance.chart_accounts.delete', [
            'chart_account_id' => $id,
            'code' => (string)$current['code'],
            'name' => (string)$current['name'],
        ], $id, $ip, $userAgent);
    }

    /**
     * Cria o plano de contas padrão (DEFAULT_TEMPLATE) e vincula centros de custo sem conta
     * cujo nome coincide (sem diferenciar maiúsculas) com uma conta analítica do modelo
     * de Receita, Custo / Despesa ou Passivo.
     *
     * @return array{accounts_created:int,cost_centers_linked:int}
     */
    public function applyDefaultTemplate(string $ip, ?string $userAgent = null): array
    {
        [$clinicId, $actorId] = $this->context();

        $pdo = $this->container->get(\PDO::class);
        $repo = new ChartAccountRepository($pdo);

        if ($repo->countByClinic($clinicId) > 0) {
            throw new \RuntimeException('O plano de contas já possui contas. Para usar o modelo padrão, a clínica não pode ter contas cadastradas.');
        }

        $ccRepo = new CostCenterRepository($pdo);
        $created = 0;
        $linked = 0;

        $pdo->beginTransaction();
        try {
            $idsByCode = [];
            $analyticByName = [];

            foreach (self::DEFAULT_TEMPLATE as $tpl) {
                [$code, $name, $typeFlag, $paymentMethod, $defaultFor] = $tpl;

                $lastDot = strrpos($code, '.');
                $parentCode = $lastDot === false ? null : substr($code, 0, $lastDot);
                $root = explode('.', $code)[0];

                $data = [
                    'parent_id' => $parentCode !== null && isset($idsByCode[$parentCode]) ? $idsByCode[$parentCode] : null,
                    'code' => $code,
                    'name' => $name,
                    'nature' => self::TEMPLATE_NATURE_BY_ROOT[$root],
                    'account_type' => $typeFlag === 'S' ? 'synthetic' : 'analytic',
                    'payment_method' => $paymentMethod,
                    'default_for' => $defaultFor,
                    'external_code' => null,
                    'description' => null,
                ];

                $existing = $repo->findByCodeIncludingDeleted($clinicId, $code);
                if ($existing !== null) {
                    // Só pode ser uma conta excluída (countByClinic = 0): restaura com os dados do modelo.
                    $id = (int)$existing['id'];
                    $repo->restore($clinicId, $id);
                    $repo->update($clinicId, $id, $data);
                } else {
                    $id = $repo->create($clinicId, $data);
                }
                $created++;
                $idsByCode[$code] = $id;

                if ($defaultFor !== null) {
                    $repo->clearDefaultFor($clinicId, $defaultFor, $id);
                }

                if ($data['account_type'] === 'analytic' && in_array($data['nature'], self::COST_CENTER_NATURES, true)) {
                    $key = mb_strtolower(trim($name), 'UTF-8');
                    if (!isset($analyticByName[$key])) {
                        $analyticByName[$key] = $id;
                    }
                }
            }

            foreach ($ccRepo->listByClinic($clinicId) as $cc) {
                if (isset($cc['chart_account_id']) && $cc['chart_account_id'] !== null) {
                    continue;
                }
                $key = mb_strtolower(trim((string)($cc['name'] ?? '')), 'UTF-8');
                if ($key !== '' && isset($analyticByName[$key])) {
                    $ccRepo->setChartAccount($clinicId, (int)$cc['id'], $analyticByName[$key]);
                    $linked++;
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $this->audit($pdo, $actorId, $clinicId, 'finance.chart_accounts.apply_template', [
            'accounts_created' => $created,
            'cost_centers_linked' => $linked,
        ], null, $ip, $userAgent);

        return ['accounts_created' => $created, 'cost_centers_linked' => $linked];
    }
}
