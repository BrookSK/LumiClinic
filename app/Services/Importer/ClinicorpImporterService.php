<?php

declare(strict_types=1);

namespace App\Services\Importer;

use App\Core\Container\Container;

final class ClinicorpImporterService
{
    private \PDO $pdo;

    /** All supported import types with metadata */
    public const TYPES = [
        'pacientes' => [
            'label' => 'Cadastro de Pacientes',
            'group' => 'Pacientes',
            'icon'  => '👤',
            'desc'  => 'Importação completa de pacientes com nome, e-mail, telefone, endereço, data de nascimento e sexo.',
            'clinicorp_cols' => 'Name, Email, MobilePhone, BirthDate, Address, AddressNumber, City, Neighborhood, Zip, state, Sex, DocumentId',
        ],
        'pacientes_localizacao' => [
            'label' => 'Localização de Pacientes',
            'group' => 'Pacientes',
            'icon'  => '📍',
            'desc'  => 'Cidade e bairro dos pacientes. Cria o paciente se não existir e atualiza o endereço.',
            'clinicorp_cols' => 'Cidade, Bairro, Nome',
        ],
        'pacientes_cobrancas' => [
            'label' => 'Cobranças e Pagamentos',
            'group' => 'Pacientes',
            'icon'  => '💳',
            'desc'  => 'Cobranças e pagamentos vinculados a pacientes.',
            'clinicorp_cols' => 'Status, Lançamento, Vencimento, Pagamento, Paciente, Forma Pagamento, CPF Responsável, CPF Paciente, Nome Responsável, Valor',
        ],
        'agendamentos_geral' => [
            'label' => 'Agendamentos - Geral',
            'group' => 'Agendamentos',
            'icon'  => '📅',
            'desc'  => 'Agendamentos com data, paciente, horário, profissional e categoria.',
            'clinicorp_cols' => 'Data, Paciente, Contato, Horário, Agendado por, Status, Profissional, Equipamento*, Sala*, Categoria',
            'ignored' => 'Equipamento, Sala (não suportados)',
        ],
        'agendamentos_desmarcacoes' => [
            'label' => 'Agendamentos - Desmarcações',
            'group' => 'Agendamentos',
            'icon'  => '❌',
            'desc'  => 'Agendamentos desmarcados com motivo.',
            'clinicorp_cols' => 'Data, Nome, Contato, Categoria, Quem desmarcou, Motivo, Usuário',
        ],
        'agendamentos_categorias' => [
            'label' => 'Categorias de Agendamento',
            'group' => 'Agendamentos',
            'icon'  => '🏷️',
            'desc'  => 'Categorias que serão importadas como serviços.',
            'clinicorp_cols' => 'Data, Paciente, Categoria',
        ],
        'agendamentos_primeira_consulta' => [
            'label' => 'Primeira Consulta',
            'group' => 'Agendamentos',
            'icon'  => '🩺',
            'desc'  => 'Primeiras consultas com status e observações.',
            'clinicorp_cols' => 'Data, Status, Nome, Como conheceu?*, Observações',
            'ignored' => 'Como conheceu? (não suportado)',
        ],
        'financeiro_contas_pagar' => [
            'label' => 'Contas a Pagar',
            'group' => 'Financeiro',
            'icon'  => '📑',
            'desc'  => 'Contas a pagar com vencimento, valor e categoria.',
            'clinicorp_cols' => 'Competência, Vencimento, CPF/CNPJ Fornecedor, Valor, Forma de Pgto, Tipo, Classificação, Descrição, Categoria, Pagamento, Clínica, Observações, Valor Total',
        ],
        'financeiro_recibos' => [
            'label' => 'Parcelas e Pagamentos',
            'group' => 'Financeiro',
            'icon'  => '🧾',
            'desc'  => 'Parcelas de pagamentos de pacientes (exportação de dados Clinicorp: PaymentItem).',
            'clinicorp_cols' => 'Amount, PatientId, Date, DueDate, PaymentDate, Type, OwnerName, OwnerCPF, InstallmentNumber',
        ],
        'orcamentos' => [
            'label' => 'Orçamentos',
            'group' => 'Pacientes',
            'icon'  => '📋',
            'desc'  => 'Orçamentos de pacientes com procedimentos e valores.',
            'clinicorp_cols' => 'Data Criação, Data, Status, Motivo, Profissional, Paciente, Telefone, Procedimentos, Valor, Valor Total Com Desconto, Observações, Desconto',
        ],
        'estoque_produtos' => [
            'label' => 'Produtos',
            'group' => 'Estoque',
            'icon'  => '📦',
            'desc'  => 'Cadastro de produtos/materiais.',
            'clinicorp_cols' => 'Nome, Descrição, Unidade de Medida, Valor Total',
            'status' => 'construction',
        ],
        'estoque_entradas' => [
            'label' => 'Entradas de Estoque',
            'group' => 'Estoque',
            'icon'  => '📥',
            'desc'  => 'Movimentações de entrada de estoque.',
            'clinicorp_cols' => 'Data de Entrada, Validade, Produto, Lote*, Valor, Quantidade, Consumido*, Fornecedor*',
            'ignored' => 'Lote, Consumido, Fornecedor (em notes)',
            'status' => 'construction',
        ],
        'estoque_saidas' => [
            'label' => 'Saídas de Estoque',
            'group' => 'Estoque',
            'icon'  => '📤',
            'desc'  => 'Movimentações de saída de estoque.',
            'clinicorp_cols' => 'Data, Produto, Lote*, Descrição, Destino*, Movimentações, Quantidade, Unidade de Medida, Valor, Fornecedor*',
            'ignored' => 'Lote, Destino, Fornecedor',
            'status' => 'construction',
        ],
        'estoque_quantidades' => [
            'label' => 'Quantidades em Estoque',
            'group' => 'Estoque',
            'icon'  => '📊',
            'desc'  => 'Snapshot de quantidades atuais.',
            'clinicorp_cols' => 'Produto, Descrição, Quantidade Disponível, Unidade de Medida',
            'status' => 'construction',
        ],
        'estoque_validades' => [
            'label' => 'Validades de Estoque',
            'group' => 'Estoque',
            'icon'  => '⏰',
            'desc'  => 'Validades dos produtos em estoque.',
            'clinicorp_cols' => 'Validade, Data de Entrada, Produto, Quantidade, Quantidade Consumida*, Fornecedor*, Local de Armazenamento*, Marca*',
            'ignored' => 'Quantidade Consumida, Fornecedor, Local de Armazenamento, Marca',
            'status' => 'construction',
        ],
        'tratamentos_executados' => [
            'label' => 'Procedimentos Executados',
            'group' => 'Tratamentos',
            'icon'  => '✅',
            'desc'  => 'Procedimentos já realizados nos pacientes.',
            'clinicorp_cols' => 'Executado, Paciente, Telefone, Profissional, Procedimento, Valor',
        ],
        'tratamentos_nao_executados' => [
            'label' => 'Procedimentos Não Executados',
            'group' => 'Tratamentos',
            'icon'  => '⏳',
            'desc'  => 'Procedimentos pendentes de execução.',
            'clinicorp_cols' => 'Data da Venda, Data Criação, Paciente, Celular, Procedimento, Valor, Profissional, Origem',
        ],
        'tratamentos_em_aberto' => [
            'label' => 'Tratamentos em Aberto',
            'group' => 'Tratamentos',
            'icon'  => '🔄',
            'desc'  => 'Tratamentos aprovados ainda em andamento.',
            'clinicorp_cols' => 'Data de Aprovação, Paciente, Quantidade, WhatsApp, Última Consulta, Próxima Consulta',
            'status' => 'construction',
        ],
        'profissionais' => [
            'label' => 'Profissionais / Funcionários',
            'group' => 'Configurações',
            'icon'  => '👨‍⚕️',
            'desc'  => 'Importa profissionais e funcionários como usuários do sistema. O campo "Type" da Clinicorp define o papel: DENTIST/DOCTOR/NURSE → Profissional (aparece na agenda), ASSISTANT/RECEPTIONIST/SECRETARY → Recepção, ADMIN/MANAGER → Administrador. Após importar, cada usuário deve usar "Esqueci minha senha" na tela de login para definir sua senha e acessar o sistema.',
            'clinicorp_cols' => 'Name, Email, MobilePhone, BirthDate, Sex, Active, Type, City, OtherDocumentId',
        ],
    ];

    public function __construct(private readonly Container $container)
    {
        $this->pdo = $container->get(\PDO::class);
    }

    /**
     * skipped_details lista cada registro ignorado com a linha da planilha, a identificação e o motivo.
     * log_id é o id do registro em clinicorp_import_logs (ausente quando o arquivo está vazio).
     *
     * @return array{imported: int, skipped: int, errors: list<string>, skipped_details: list<array{line: int, name: string, reason: string}>, log_id?: int}
     */
    public function import(int $clinicId, int $userId, string $type, string $filePath, string $fileName): array
    {
        $rows = XlsxReader::read($filePath);
        if (count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Arquivo vazio ou sem dados.'], 'skipped_details' => []];
        }

        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);

        $result = match ($type) {
            'pacientes'                      => $this->importPacientesCadastro($clinicId, $headers, $dataRows),
            'pacientes_localizacao'          => $this->importPacientes($clinicId, $headers, $dataRows),
            'pacientes_cobrancas'            => $this->importCobrancas($clinicId, $headers, $dataRows),
            'agendamentos_geral'            => $this->importAgendamentosGeral($clinicId, $headers, $dataRows),
            'agendamentos_desmarcacoes'      => $this->importAgendamentosDesmarcacoes($clinicId, $headers, $dataRows),
            'agendamentos_categorias'        => $this->importAgendamentosCategorias($clinicId, $headers, $dataRows),
            'agendamentos_primeira_consulta' => $this->importPrimeiraConsulta($clinicId, $headers, $dataRows),
            'financeiro_contas_pagar'        => $this->importContasPagar($clinicId, $headers, $dataRows),
            'financeiro_recibos'             => $this->importParcelas($clinicId, $headers, $dataRows),
            'orcamentos'                     => $this->importOrcamentos($clinicId, $headers, $dataRows),
            'tratamentos_executados'         => $this->importTratamentosExecutados($clinicId, $headers, $dataRows),
            'tratamentos_nao_executados'     => $this->importTratamentosNaoExecutados($clinicId, $headers, $dataRows),
            'profissionais'                  => $this->importProfissionais($clinicId, $headers, $dataRows),
            default => ['imported' => 0, 'skipped' => 0, 'errors' => ['Tipo de importação não suportado: ' . $type], 'skipped_details' => []],
        };
        $result['skipped_details'] = $result['skipped_details'] ?? [];

        // Log
        $result['log_id'] = $this->logImport($clinicId, $userId, $type, $fileName, count($dataRows), $result);

        return $result;
    }

    /** @return array<string, int> column name => index */
    private function mapCols(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $key = mb_strtolower(trim($h), 'UTF-8');
            $key = preg_replace('/\s+/', ' ', $key);
            $map[$key] = $i;
        }
        return $map;
    }

    private function col(array $row, array $colMap, string $name): string
    {
        $key = mb_strtolower(trim($name), 'UTF-8');
        if (!isset($colMap[$key])) return '';
        $idx = $colMap[$key];
        return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
    }

    /**
     * Registra um registro ignorado (linha da planilha, identificação legível e motivo).
     *
     * @param list<array{line: int, name: string, reason: string}> $skippedList
     */
    private function skip(array &$skippedList, int $line, string $identifier, string $reason, string $extra = ''): void
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            $identifier = '(sem nome)';
        }
        $extra = trim($extra);
        if ($extra !== '') {
            $identifier .= ' (' . $extra . ')';
        }
        $skippedList[] = ['line' => $line, 'name' => $identifier, 'reason' => $reason];
    }

    /** Data legível (dd/mm/aaaa) a partir do valor bruto da planilha, para identificar registros ignorados. */
    private function displayDate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        $date = preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw, $m) ? $m[1] : XlsxReader::excelDateToString($raw);
        if ($date && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $d)) {
            return $d[3] . '/' . $d[2] . '/' . $d[1];
        }
        return $raw;
    }

    private function findOrCreatePatient(int $clinicId, string $name, string $phone = '', string $email = ''): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        // Clean name: remove trailing codes like "(93)" or "(115)"
        $name = preg_replace('/\s*\(\d+\)\s*$/', '', $name);
        $name = trim($name);

        // Try find by name
        $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$clinicId, $name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];

        // Create
        $phone = preg_replace('/[^\d]/', '', $phone);
        $stmt = $this->pdo->prepare('INSERT INTO patients (clinic_id, name, phone, email, status, created_at) VALUES (?, ?, ?, ?, \'active\', NOW())');
        $stmt->execute([$clinicId, $name, $phone ?: null, $email ?: null]);
        return (int)$this->pdo->lastInsertId();
    }

    private function findOrCreateProfessional(int $clinicId, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        $stmt = $this->pdo->prepare('SELECT id FROM professionals WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$clinicId, $name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];

        $stmt = $this->pdo->prepare('INSERT INTO professionals (clinic_id, name, status, created_at) VALUES (?, ?, \'active\', NOW())');
        $stmt->execute([$clinicId, $name]);
        return (int)$this->pdo->lastInsertId();
    }

    private function findOrCreateService(int $clinicId, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;

        $stmt = $this->pdo->prepare('SELECT id FROM services WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([$clinicId, $name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];

        $stmt = $this->pdo->prepare('INSERT INTO services (clinic_id, name, duration_minutes, status, created_at) VALUES (?, ?, 30, \'active\', NOW())');
        $stmt->execute([$clinicId, $name]);
        return (int)$this->pdo->lastInsertId();
    }

    // ─── Pacientes ───────────────────────────────────────────────

    private function importPacientes(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $name = $this->col($row, $colMap, 'Nome');
                if ($name === '') {
                    $name = $this->col($row, $colMap, 'Paciente');
                }
                if ($name === '') {
                    $this->skip($skippedDetails, $i + 2, '', 'Nome do paciente vazio');
                    $skipped++; continue;
                }

                $cidade = $this->col($row, $colMap, 'Cidade');
                $bairro = $this->col($row, $colMap, 'Bairro');
                $phone  = $this->col($row, $colMap, 'Contato');
                if ($phone === '') $phone = $this->col($row, $colMap, 'Telefone');
                if ($phone === '') $phone = $this->col($row, $colMap, 'Celular');
                $email  = $this->col($row, $colMap, 'Email');
                if ($email === '') $email = $this->col($row, $colMap, 'E-mail');

                $address = trim(($bairro ? $bairro . ', ' : '') . $cidade);

                $name = preg_replace('/\s*\(\d+\)\s*$/', '', trim($name));

                // Check existing
                $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
                $stmt->execute([$clinicId, $name]);
                if ($stmt->fetch()) {
                    // Update address if we have it
                    if ($address !== '') {
                        $this->pdo->prepare('UPDATE patients SET address = COALESCE(NULLIF(address, \'\'), ?) WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL')
                            ->execute([$address, $clinicId, $name]);
                        $reason = 'Paciente já cadastrado com este nome (endereço preenchido se estava vazio)';
                    } else {
                        $reason = 'Paciente já cadastrado com este nome';
                    }
                    $this->skip($skippedDetails, $i + 2, $name, $reason);
                    $skipped++;
                    continue;
                }

                $phoneClean = preg_replace('/[^\d]/', '', $phone);
                $stmt = $this->pdo->prepare('INSERT INTO patients (clinic_id, name, phone, email, address, status, created_at) VALUES (?, ?, ?, ?, ?, \'active\', NOW())');
                $stmt->execute([$clinicId, $name, $phoneClean ?: null, $email ?: null, $address ?: null]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Agendamentos Geral ──────────────────────────────────────

    private function importAgendamentosGeral(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $dateRaw = $this->col($row, $colMap, 'Data');
                $patientName = $this->col($row, $colMap, 'Paciente');
                $contact = $this->col($row, $colMap, 'Contato');
                $horario = $this->col($row, $colMap, 'Horário');
                $status = $this->col($row, $colMap, 'Status');
                $profName = $this->col($row, $colMap, 'Profissional');
                $categoria = $this->col($row, $colMap, 'Categoria');

                if ($patientName === '' || $dateRaw === '') {
                    $reason = $patientName === '' && $dateRaw === '' ? 'Paciente e data vazios' : ($patientName === '' ? 'Nome do paciente vazio' : 'Data do agendamento vazia');
                    $this->skip($skippedDetails, $i + 2, $patientName, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $date = XlsxReader::excelDateToString($dateRaw);
                if (!$date) {
                    $this->skip($skippedDetails, $i + 2, $patientName, 'Data inválida: ' . $dateRaw);
                    $skipped++; continue;
                }

                // Parse horário "13:15 às 14:00"
                $startTime = '08:00';
                $endTime = '08:30';
                if (preg_match('/(\d{1,2}:\d{2})\s*(?:às|a|-)\s*(\d{1,2}:\d{2})/', $horario, $m)) {
                    $startTime = $m[1];
                    $endTime = $m[2];
                }

                $startAt = $date . ' ' . $startTime . ':00';
                $endAt = $date . ' ' . $endTime . ':00';

                $patientId = $this->findOrCreatePatient($clinicId, $patientName, $contact);
                $profId = $this->findOrCreateProfessional($clinicId, $profName);
                $serviceId = $this->findOrCreateService($clinicId, $categoria ?: 'Consulta');

                if (!$patientId || !$profId || !$serviceId) {
                    $missing = [];
                    if (!$patientId) $missing[] = 'paciente';
                    if (!$profId) $missing[] = 'profissional';
                    if (!$serviceId) $missing[] = 'serviço';
                    $reason = 'Não foi possível identificar ' . implode('/', $missing);
                    if (!$profId && trim($profName) === '') $reason .= ' (coluna Profissional vazia)';
                    $this->skip($skippedDetails, $i + 2, $patientName, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $dbStatus = $this->mapAppointmentStatus($status);

                $stmt = $this->pdo->prepare('INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'import\', ?, NOW())');
                $stmt->execute([$clinicId, $profId, $serviceId, $patientId, $startAt, $endAt, $dbStatus, 'Importado da Clinicorp']);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    private function mapAppointmentStatus(string $raw): string
    {
        $raw = mb_strtolower(trim($raw), 'UTF-8');
        return match (true) {
            str_contains($raw, 'desmarcado'), str_contains($raw, 'cancelado') => 'cancelled',
            str_contains($raw, 'falta'), str_contains($raw, 'faltou') => 'no_show',
            str_contains($raw, 'confirmado') => 'confirmed',
            str_contains($raw, 'atendido'), str_contains($raw, 'finalizado') => 'completed',
            str_contains($raw, 'agendado') => 'scheduled',
            default => 'scheduled',
        };
    }

    // ─── Agendamentos Desmarcações ───────────────────────────────

    private function importAgendamentosDesmarcacoes(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $dateRaw = $this->col($row, $colMap, 'Data');
                $nome = $this->col($row, $colMap, 'Nome');
                $contato = $this->col($row, $colMap, 'Contato');
                $categoria = $this->col($row, $colMap, 'Categoria');
                $motivo = $this->col($row, $colMap, 'Motivo');
                $quem = $this->col($row, $colMap, 'Quem desmarcou');

                if ($nome === '' || $dateRaw === '') {
                    $reason = $nome === '' && $dateRaw === '' ? 'Nome e data vazios' : ($nome === '' ? 'Nome do paciente vazio' : 'Data vazia');
                    $this->skip($skippedDetails, $i + 2, $nome, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                // Clean " - Desmarcado" from name
                $nome = preg_replace('/\s*-\s*Desmarcado\s*$/i', '', $nome);

                $date = XlsxReader::excelDateToString($dateRaw);
                if (!$date) {
                    $this->skip($skippedDetails, $i + 2, $nome, 'Data inválida: ' . $dateRaw);
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $nome, $contato);
                $serviceId = $this->findOrCreateService($clinicId, $categoria ?: 'Consulta');
                // Use first professional available
                $profId = $this->getFirstProfessional($clinicId);

                if (!$patientId || !$serviceId || !$profId) {
                    if (!$profId) {
                        $reason = 'Nenhum profissional ativo cadastrado na clínica';
                    } else {
                        $reason = 'Não foi possível identificar ' . (!$patientId ? 'paciente' : 'serviço');
                    }
                    $this->skip($skippedDetails, $i + 2, $nome, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $notes = 'Importado da Clinicorp (Desmarcação)';
                if ($motivo) $notes .= ' | Motivo: ' . $motivo;
                if ($quem) $notes .= ' | Desmarcado por: ' . $quem;

                $stmt = $this->pdo->prepare('INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, \'cancelled\', \'import\', ?, NOW())');
                $stmt->execute([$clinicId, $profId, $serviceId, $patientId, $date . ' 08:00:00', $date . ' 08:30:00', $notes]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Categorias de Agendamento → Services ────────────────────

    private function importAgendamentosCategorias(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $seen = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $categoria = $this->col($row, $colMap, 'Categoria');
                if ($categoria === '' || isset($seen[$categoria])) {
                    $reason = $categoria === '' ? 'Categoria vazia' : 'Categoria repetida no arquivo (linha ' . $seen[$categoria] . ')';
                    $this->skip($skippedDetails, $i + 2, $categoria, $reason);
                    $skipped++; continue;
                }
                $seen[$categoria] = $i + 2;

                // Check if service already exists
                $stmt = $this->pdo->prepare('SELECT id FROM services WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
                $stmt->execute([$clinicId, $categoria]);
                if ($stmt->fetch()) {
                    $this->skip($skippedDetails, $i + 2, $categoria, 'Serviço já cadastrado com este nome');
                    $skipped++; continue;
                }

                $stmt = $this->pdo->prepare('INSERT INTO services (clinic_id, name, duration_minutes, status, created_at) VALUES (?, ?, 30, \'active\', NOW())');
                $stmt->execute([$clinicId, $categoria]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Primeira Consulta ───────────────────────────────────────

    private function importPrimeiraConsulta(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $dateRaw = $this->col($row, $colMap, 'Data');
                $status = $this->col($row, $colMap, 'Status');
                $nome = $this->col($row, $colMap, 'Nome');
                $obs = $this->col($row, $colMap, 'Observações');

                if ($nome === '' || $dateRaw === '') {
                    $reason = $nome === '' && $dateRaw === '' ? 'Nome e data vazios' : ($nome === '' ? 'Nome do paciente vazio' : 'Data vazia');
                    $this->skip($skippedDetails, $i + 2, $nome, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $date = XlsxReader::excelDateToString($dateRaw);
                if (!$date) {
                    $this->skip($skippedDetails, $i + 2, $nome, 'Data inválida: ' . $dateRaw);
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $nome);
                $serviceId = $this->findOrCreateService($clinicId, 'Primeira Consulta');
                $profId = $this->getFirstProfessional($clinicId);

                if (!$patientId || !$serviceId || !$profId) {
                    if (!$profId) {
                        $reason = 'Nenhum profissional ativo cadastrado na clínica';
                    } else {
                        $reason = 'Não foi possível identificar ' . (!$patientId ? 'paciente' : 'serviço');
                    }
                    $this->skip($skippedDetails, $i + 2, $nome, $reason, $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $dbStatus = $this->mapAppointmentStatus($status);
                $notes = 'Importado da Clinicorp (Primeira Consulta)';
                if ($obs) $notes .= ' | ' . $obs;

                $stmt = $this->pdo->prepare('INSERT INTO appointments (clinic_id, professional_id, service_id, patient_id, start_at, end_at, status, origin, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, \'import\', ?, NOW())');
                $stmt->execute([$clinicId, $profId, $serviceId, $patientId, $date . ' 08:00:00', $date . ' 08:30:00', $dbStatus, $notes]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Financeiro - Cobranças e Pagamentos ─────────────────────

    private function importCobrancas(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $paciente = $this->col($row, $colMap, 'Paciente');
                $valorRaw = $this->col($row, $colMap, 'Valor');
                $forma = $this->col($row, $colMap, 'Forma Pagamento');
                $statusRaw = $this->col($row, $colMap, 'Status');
                $lancRaw = $this->col($row, $colMap, 'Lançamento');
                $vencRaw = $this->col($row, $colMap, 'Vencimento');

                if ($paciente === '' || $valorRaw === '') {
                    $reason = $paciente === '' && $valorRaw === '' ? 'Paciente e valor vazios' : ($paciente === '' ? 'Nome do paciente vazio' : 'Valor vazio');
                    $this->skip($skippedDetails, $i + 2, $paciente, $reason, $this->displayDate($vencRaw ?: $lancRaw));
                    $skipped++; continue;
                }

                $valor = XlsxReader::parseMoney($valorRaw);
                if ($valor <= 0) {
                    $this->skip($skippedDetails, $i + 2, $paciente, 'Valor zerado ou inválido: ' . $valorRaw, $this->displayDate($vencRaw ?: $lancRaw));
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $paciente);
                if (!$patientId) {
                    $this->skip($skippedDetails, $i + 2, $paciente, 'Não foi possível identificar o paciente');
                    $skipped++; continue;
                }

                $saleStatus = mb_strtolower($statusRaw) === 'em aberto' ? 'open' : 'closed';

                // Create sale
                $stmt = $this->pdo->prepare('INSERT INTO sales (clinic_id, patient_id, total_bruto, desconto, total_liquido, status, origin, notes, created_at) VALUES (?, ?, ?, 0, ?, ?, \'import\', \'Importado da Clinicorp\', NOW())');
                $stmt->execute([$clinicId, $patientId, $valor, $valor, $saleStatus]);
                $saleId = (int)$this->pdo->lastInsertId();

                // Create payment if paid
                if ($saleStatus === 'closed') {
                    $method = $this->mapPaymentMethod($forma);
                    $paidAt = XlsxReader::excelDateTimeToString($lancRaw) ?: date('Y-m-d H:i:s');
                    $stmt = $this->pdo->prepare('INSERT INTO payments (clinic_id, sale_id, method, amount, status, fees, paid_at, created_at) VALUES (?, ?, ?, ?, \'paid\', 0, ?, NOW())');
                    $stmt->execute([$clinicId, $saleId, $method, $valor, $paidAt]);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    private function mapPaymentMethod(string $raw): string
    {
        $raw = mb_strtolower(trim($raw), 'UTF-8');
        return match (true) {
            str_contains($raw, 'pix') => 'pix',
            str_contains($raw, 'cartão'), str_contains($raw, 'cartao'), str_contains($raw, 'credito'), str_contains($raw, 'crédito') => 'credit_card',
            str_contains($raw, 'débito'), str_contains($raw, 'debito') => 'debit_card',
            str_contains($raw, 'boleto') => 'boleto',
            str_contains($raw, 'dinheiro') => 'cash',
            str_contains($raw, 'transferência'), str_contains($raw, 'transferencia') => 'transfer',
            str_contains($raw, 'cheque') => 'check',
            default => 'other',
        };
    }

    // ─── Financeiro - Contas a Pagar ─────────────────────────────

    private function importContasPagar(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $vencRaw = $this->col($row, $colMap, 'Vencimento');
                $valorRaw = $this->col($row, $colMap, 'Valor');
                $forma = $this->col($row, $colMap, 'Forma de Pgto');
                $descricao = $this->col($row, $colMap, 'Descrição');
                $categoria = $this->col($row, $colMap, 'Categoria');
                $obs = $this->col($row, $colMap, 'Observações');
                $fornecedor = $this->col($row, $colMap, 'CPF/CNPJ Fornecedor');
                $valorTotalRaw = $this->col($row, $colMap, 'Valor Total');

                if ($descricao === '' && $categoria === '') {
                    $this->skip($skippedDetails, $i + 2, $fornecedor, 'Descrição e categoria vazias', $this->displayDate($vencRaw));
                    $skipped++; continue;
                }
                $payableLabel = $descricao ?: $categoria;

                $venc = XlsxReader::excelDateToString($vencRaw);
                if (!$venc) {
                    $this->skip($skippedDetails, $i + 2, $payableLabel, $vencRaw === '' ? 'Data de vencimento vazia' : 'Data de vencimento inválida: ' . $vencRaw);
                    $skipped++; continue;
                }

                $valor = XlsxReader::parseMoney($valorRaw ?: $valorTotalRaw);
                if ($valor <= 0) {
                    $this->skip($skippedDetails, $i + 2, $payableLabel, 'Valor zerado ou inválido: ' . ($valorRaw ?: $valorTotalRaw), $this->displayDate($vencRaw));
                    $skipped++; continue;
                }

                $title = $descricao ?: $categoria;
                $description = '';
                if ($categoria && $descricao) $description = 'Categoria: ' . $categoria;
                if ($obs) $description .= ($description ? ' | ' : '') . $obs;

                $stmt = $this->pdo->prepare('INSERT INTO accounts_payable (clinic_id, vendor_name, title, description, payable_type, status, start_due_date, created_at) VALUES (?, ?, ?, ?, \'single\', \'active\', ?, NOW())');
                $stmt->execute([$clinicId, $fornecedor ?: null, $title, $description ?: null, $venc]);
                $payableId = (int)$this->pdo->lastInsertId();

                // Create single installment
                $stmt = $this->pdo->prepare('INSERT INTO accounts_payable_installments (clinic_id, payable_id, installment_no, due_date, amount, status, created_at) VALUES (?, ?, 1, ?, ?, \'pending\', NOW())');
                $stmt->execute([$clinicId, $payableId, $venc, $valor]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Orçamentos ──────────────────────────────────────────────

    private function importOrcamentos(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $paciente = $this->col($row, $colMap, 'Paciente');
                $telefone = $this->col($row, $colMap, 'Telefone');
                $profissional = $this->col($row, $colMap, 'Profissional');
                $procedimentos = $this->col($row, $colMap, 'Procedimentos');
                $valorRaw = $this->col($row, $colMap, 'Valor');
                $valorDescRaw = $this->col($row, $colMap, 'Valor Total Com Desconto');
                $obs = $this->col($row, $colMap, 'Observações');
                $statusRaw = $this->col($row, $colMap, 'Status');

                if ($paciente === '' || $valorRaw === '') {
                    $reason = $paciente === '' && $valorRaw === '' ? 'Paciente e valor vazios' : ($paciente === '' ? 'Nome do paciente vazio' : 'Valor do orçamento vazio');
                    $this->skip($skippedDetails, $i + 2, $paciente, $reason, $this->displayDate($this->col($row, $colMap, 'Data')));
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $paciente, $telefone);
                if (!$patientId) {
                    $this->skip($skippedDetails, $i + 2, $paciente, 'Não foi possível identificar o paciente');
                    $skipped++; continue;
                }

                $totalBruto = XlsxReader::parseMoney($valorRaw);
                $totalLiquido = $valorDescRaw ? XlsxReader::parseMoney($valorDescRaw) : $totalBruto;
                $desconto = $totalBruto - $totalLiquido;
                if ($desconto < 0) $desconto = 0;

                $saleStatus = mb_strtoupper(trim($statusRaw)) === 'APPROVED' ? 'open' : 'open';

                $notes = 'Importado da Clinicorp (Orçamento)';
                if ($procedimentos) $notes .= ' | Procedimentos: ' . $procedimentos;
                if ($obs) $notes .= ' | ' . $obs;

                $stmt = $this->pdo->prepare('INSERT INTO sales (clinic_id, patient_id, total_bruto, desconto, total_liquido, status, origin, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, \'import\', ?, NOW())');
                $stmt->execute([$clinicId, $patientId, $totalBruto, $desconto, $totalLiquido, $saleStatus, $notes]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Tratamentos Executados ──────────────────────────────────

    private function importTratamentosExecutados(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $dateRaw = $this->col($row, $colMap, 'Executado');
                $paciente = $this->col($row, $colMap, 'Paciente');
                $telefone = $this->col($row, $colMap, 'Telefone');
                $profissional = $this->col($row, $colMap, 'Profissional');
                $procedimento = $this->col($row, $colMap, 'Procedimento');
                $valorRaw = $this->col($row, $colMap, 'Valor');

                if ($paciente === '' || $procedimento === '') {
                    $reason = $paciente === '' && $procedimento === '' ? 'Paciente e procedimento vazios' : ($paciente === '' ? 'Nome do paciente vazio' : 'Procedimento vazio');
                    $this->skip($skippedDetails, $i + 2, $paciente, $reason, $procedimento !== '' ? $procedimento : $this->displayDate($dateRaw));
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $paciente, $telefone);
                $profId = $this->findOrCreateProfessional($clinicId, $profissional);
                $serviceId = $this->findOrCreateService($clinicId, $procedimento);

                if (!$patientId || !$serviceId) {
                    $this->skip($skippedDetails, $i + 2, $paciente, 'Não foi possível identificar ' . (!$patientId ? 'paciente' : 'serviço'), $procedimento);
                    $skipped++; continue;
                }

                $stmt = $this->pdo->prepare('INSERT INTO patient_procedures (clinic_id, patient_id, service_id, professional_id, total_sessions, used_sessions, status, created_at) VALUES (?, ?, ?, ?, 1, 1, \'completed\', NOW())');
                $stmt->execute([$clinicId, $patientId, $serviceId, $profId]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Tratamentos Não Executados ──────────────────────────────

    private function importTratamentosNaoExecutados(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $paciente = $this->col($row, $colMap, 'Paciente');
                $celular = $this->col($row, $colMap, 'Celular');
                $profissional = $this->col($row, $colMap, 'Profissional');
                $procedimento = $this->col($row, $colMap, 'Procedimento');

                if ($paciente === '' || $procedimento === '') {
                    $reason = $paciente === '' && $procedimento === '' ? 'Paciente e procedimento vazios' : ($paciente === '' ? 'Nome do paciente vazio' : 'Procedimento vazio');
                    $this->skip($skippedDetails, $i + 2, $paciente, $reason, $procedimento);
                    $skipped++; continue;
                }

                $patientId = $this->findOrCreatePatient($clinicId, $paciente, $celular);
                $profId = $this->findOrCreateProfessional($clinicId, $profissional);
                $serviceId = $this->findOrCreateService($clinicId, $procedimento);

                if (!$patientId || !$serviceId) {
                    $this->skip($skippedDetails, $i + 2, $paciente, 'Não foi possível identificar ' . (!$patientId ? 'paciente' : 'serviço'), $procedimento);
                    $skipped++; continue;
                }

                $stmt = $this->pdo->prepare('INSERT INTO patient_procedures (clinic_id, patient_id, service_id, professional_id, total_sessions, used_sessions, status, created_at) VALUES (?, ?, ?, ?, 1, 0, \'active\', NOW())');
                $stmt->execute([$clinicId, $patientId, $serviceId, $profId]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Parcelas / Pagamentos (PaymentItem) ───────────────────

    private function importParcelas(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0; $skipped = 0; $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $amountRaw = $this->col($row, $colMap, 'Amount');
                $ownerName = $this->col($row, $colMap, 'OwnerName');
                $ownerCpf = $this->col($row, $colMap, 'OwnerCPF');
                $dateRaw = $this->col($row, $colMap, 'Date');
                $dueDateRaw = $this->col($row, $colMap, 'DueDate');
                $paymentDateRaw = $this->col($row, $colMap, 'PaymentDate');
                $receivedDateRaw = $this->col($row, $colMap, 'ReceivedDate');
                $type = $this->col($row, $colMap, 'Type');
                $installmentNo = $this->col($row, $colMap, 'InstallmentNumber');
                $installmentsCount = $this->col($row, $colMap, 'InstallmentsCount');
                $canceled = $this->col($row, $colMap, 'Canceled');
                $paymentConfirmed = $this->col($row, $colMap, 'PaymentConfirmed');
                $paymentReceived = $this->col($row, $colMap, 'PaymentReceived');
                $description = $this->col($row, $colMap, 'PaymentDescription');
                if ($description === '') $description = $this->col($row, $colMap, 'Description');

                $parcelaExtra = $installmentNo !== '' ? 'parcela ' . $installmentNo . ($installmentsCount !== '' ? '/' . $installmentsCount : '') : '';

                $amount = XlsxReader::parseMoney($amountRaw);
                if ($amount <= 0) {
                    $this->skip($skippedDetails, $i + 2, $ownerName, $amountRaw === '' ? 'Valor vazio' : 'Valor zerado ou inválido: ' . $amountRaw, $parcelaExtra);
                    $skipped++; continue;
                }

                // Parse dates (ISO format: 2025-06-17T21:00:00.000Z or atomic: 20250617)
                $occurredOn = null;
                $dateStr = $paymentDateRaw ?: $dateRaw ?: $dueDateRaw;
                if ($dateStr !== '') {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $dateStr, $m)) {
                        $occurredOn = $m[1];
                    } elseif (preg_match('/^(\d{8})$/', $dateStr)) {
                        $occurredOn = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
                    } else {
                        $occurredOn = XlsxReader::excelDateToString($dateStr);
                    }
                }
                if (!$occurredOn) {
                    $this->skip($skippedDetails, $i + 2, $ownerName, $dateStr === '' ? 'Data vazia (PaymentDate, Date e DueDate)' : 'Data inválida: ' . $dateStr, $parcelaExtra);
                    $skipped++; continue;
                }

                // Skip canceled
                if (strtoupper($canceled) === 'X') {
                    $this->skip($skippedDetails, $i + 2, $ownerName, 'Parcela cancelada', trim($parcelaExtra . ($parcelaExtra !== '' ? ', ' : '') . $this->displayDate($occurredOn)));
                    $skipped++; continue;
                }

                // Map payment method
                $method = match (true) {
                    str_contains(strtolower($type), 'credit_card') => 'credit_card',
                    str_contains(strtolower($type), 'debit_card') => 'debit_card',
                    str_contains(strtolower($type), 'pix') => 'pix',
                    str_contains(strtolower($type), 'boleto') => 'boleto',
                    str_contains(strtolower($type), 'cash') => 'cash',
                    str_contains(strtolower($type), 'transfer') => 'transfer',
                    str_contains(strtolower($type), 'check') => 'check',
                    default => 'other',
                };

                $status = (strtoupper($paymentConfirmed) === 'X' || strtoupper($paymentReceived) === 'X') ? 'paid' : 'pending';

                $notes = 'Importado da Clinicorp';
                if ($ownerName !== '') $notes .= ' | ' . $ownerName;
                if ($installmentNo !== '') $notes .= ' | Parcela ' . $installmentNo . ($installmentsCount !== '' ? '/' . $installmentsCount : '');
                if ($description !== '') $notes .= ' | ' . $description;

                // Create as financial_entry (income)
                $stmt = $this->pdo->prepare('INSERT INTO financial_entries (clinic_id, kind, occurred_on, amount, method, status, description, created_at) VALUES (?, \'income\', ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$clinicId, $occurredOn, $amount, $method, $status, $notes]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Pacientes Cadastro (exportação de dados) ─────────────

    private function importPacientesCadastro(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0; $skipped = 0; $errors = [];
        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $name = $this->col($row, $colMap, 'Name');
                if ($name === '') {
                    $this->skip($skippedDetails, $i + 2, $this->col($row, $colMap, 'Email'), 'Nome do paciente vazio');
                    $skipped++; continue;
                }
                $name = preg_replace('/\s*\(\d+\)\s*$/', '', trim($name));

                $email = $this->col($row, $colMap, 'Email');
                $phone = $this->col($row, $colMap, 'MobilePhone');
                $birthRaw = $this->col($row, $colMap, 'BirthDate');
                $sex = strtoupper(substr($this->col($row, $colMap, 'Sex'), 0, 1));
                $address = $this->col($row, $colMap, 'Address');
                $addressNum = $this->col($row, $colMap, 'AddressNumber');
                $city = $this->col($row, $colMap, 'City');
                $neighborhood = $this->col($row, $colMap, 'Neighborhood');
                $zip = $this->col($row, $colMap, 'Zip');
                $state = $this->col($row, $colMap, 'state');
                $cpf = $this->col($row, $colMap, 'DocumentId');

                // Parse birth date (ISO format: 1972-04-03T03:00:00.000Z)
                $birthDate = null;
                if ($birthRaw !== '') {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $birthRaw, $m)) {
                        $birthDate = $m[1];
                    } else {
                        $birthDate = XlsxReader::excelDateToString($birthRaw);
                    }
                }

                $phoneClean = preg_replace('/\D+/', '', $phone);
                $cpfClean = preg_replace('/\D+/', '', $cpf);

                // Check existing by name
                $stmt = $this->pdo->prepare('SELECT id FROM patients WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
                $stmt->execute([$clinicId, $name]);
                if ($stmt->fetch()) {
                    $this->skip($skippedDetails, $i + 2, $name, 'Paciente já cadastrado com este nome');
                    $skipped++; continue;
                }

                $fullAddress = trim(($address ? $address : '') . ($addressNum ? ', ' . $addressNum : '') . ($neighborhood ? ' - ' . $neighborhood : '') . ($city ? ', ' . $city : '') . ($state ? '/' . $state : '') . ($zip ? ' CEP: ' . $zip : ''));

                $stmt = $this->pdo->prepare('INSERT INTO patients (clinic_id, name, email, phone, birth_date, sex, cpf, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'active\', NOW())');
                $stmt->execute([
                    $clinicId, $name,
                    $email ?: null, $phoneClean ?: null,
                    $birthDate, $sex ?: null,
                    $cpfClean ?: null, $fullAddress ?: null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Profissionais ───────────────────────────────────────────

    private function importProfissionais(int $clinicId, array $headers, array $dataRows): array
    {
        $colMap = $this->mapCols($headers);
        $imported = 0; $skipped = 0; $errors = [];

        // Map Clinicorp Type → system role code
        // DENTIST, HYGIENIST, etc → professional (creates professional record)
        // ASSISTANT, RECEPTIONIST, SECRETARY → reception
        // ADMIN, MANAGER, OWNER → admin
        // Others → professional (default)
        $typeToRole = [
            'dentist' => 'professional', 'hygienist' => 'professional', 'doctor' => 'professional',
            'nurse' => 'professional', 'therapist' => 'professional', 'nutritionist' => 'professional',
            'physiotherapist' => 'professional', 'psychologist' => 'professional', 'dermatologist' => 'professional',
            'assistant' => 'reception', 'receptionist' => 'reception', 'secretary' => 'reception',
            'admin' => 'admin', 'manager' => 'admin', 'owner' => 'owner',
        ];

        // Pre-load role IDs
        $roleIds = [];
        $roleStmt = $this->pdo->prepare("SELECT id, code FROM roles WHERE clinic_id = ? AND deleted_at IS NULL");
        $roleStmt->execute([$clinicId]);
        foreach ($roleStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $roleIds[(string)$r['code']] = (int)$r['id'];
        }

        $skippedDetails = [];

        foreach ($dataRows as $i => $row) {
            try {
                $name = $this->col($row, $colMap, 'Name');
                if ($name === '') {
                    $this->skip($skippedDetails, $i + 2, $this->col($row, $colMap, 'Email'), 'Nome do profissional vazio');
                    $skipped++; continue;
                }

                $email = strtolower(trim($this->col($row, $colMap, 'Email')));
                $phone = preg_replace('/[^\d]/', '', $this->col($row, $colMap, 'MobilePhone'));
                $birthDateRaw = $this->col($row, $colMap, 'BirthDate');
                $sex = strtolower(trim($this->col($row, $colMap, 'Sex')));
                $active = strtolower(trim($this->col($row, $colMap, 'Active')));
                $type = strtolower(trim($this->col($row, $colMap, 'Type')));
                $cpf = preg_replace('/[^\d]/', '', $this->col($row, $colMap, 'OtherDocumentId'));
                $city = trim($this->col($row, $colMap, 'City'));

                // Determine role
                $roleCode = $typeToRole[$type] ?? 'professional';
                $isProfessionalType = in_array($roleCode, ['professional'], true);

                // Check existing user by email
                $existingUserId = null;
                if ($email !== '') {
                    $existUser = $this->pdo->prepare('SELECT id FROM users WHERE clinic_id = ? AND email = ? AND deleted_at IS NULL LIMIT 1');
                    $existUser->execute([$clinicId, $email]);
                    $existUserRow = $existUser->fetch(\PDO::FETCH_ASSOC);
                    if ($existUserRow) {
                        $existingUserId = (int)$existUserRow['id'];
                    }
                }

                // Check existing professional by name — if exists WITHOUT user, upgrade it
                $existingProfId = null;
                if ($isProfessionalType) {
                    $existProf = $this->pdo->prepare('SELECT id, user_id FROM professionals WHERE clinic_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
                    $existProf->execute([$clinicId, $name]);
                    $profRow = $existProf->fetch(\PDO::FETCH_ASSOC);
                    if ($profRow) {
                        if ($profRow['user_id'] !== null && (int)$profRow['user_id'] > 0) {
                            $this->skip($skippedDetails, $i + 2, $name, 'Profissional já cadastrado com este nome e já vinculado a um usuário', $email);
                            $skipped++; continue;
                        }
                        $existingProfId = (int)$profRow['id'];
                    }
                }

                // If both user and professional already exist, just link them
                if ($existingUserId !== null && $existingProfId !== null) {
                    $this->pdo->prepare('UPDATE professionals SET user_id = ?, updated_at = NOW() WHERE id = ? AND clinic_id = ?')
                        ->execute([$existingUserId, $existingProfId, $clinicId]);
                    $imported++; continue;
                }

                // If user already exists but no professional, skip user creation
                $userId = $existingUserId;

                // Parse birth date (format YYYYMMDD or other)
                $birthDate = null;
                if ($birthDateRaw !== '') {
                    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $birthDateRaw, $m)) {
                        $birthDate = $m[1] . '-' . $m[2] . '-' . $m[3];
                    } else {
                        $birthDate = XlsxReader::excelDateToString($birthDateRaw);
                    }
                }

                // 1) Create user (only if doesn't exist yet)
                if ($userId === null) {
                    $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $userStatus = ($active === 'false' || $active === '0' || $active === 'não' || $active === 'no' || $active === 'inactive') ? 'disabled' : 'active';
                    $userStmt = $this->pdo->prepare('INSERT INTO users (clinic_id, name, email, phone, password_hash, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                    $userStmt->execute([$clinicId, $name, $email ?: null, $phone ?: null, $password, $userStatus]);
                    $userId = (int)$this->pdo->lastInsertId();
                }

                // 2) Assign role (if not already assigned)
                $assignRoleId = $roleIds[$roleCode] ?? ($roleIds['professional'] ?? null);
                if ($assignRoleId !== null && $userId > 0) {
                    $existRole = $this->pdo->prepare('SELECT 1 FROM user_roles WHERE clinic_id = ? AND user_id = ? AND role_id = ? LIMIT 1');
                    $existRole->execute([$clinicId, $userId, $assignRoleId]);
                    if (!$existRole->fetch()) {
                        $this->pdo->prepare('INSERT INTO user_roles (clinic_id, user_id, role_id, created_at) VALUES (?, ?, ?, NOW())')
                            ->execute([$clinicId, $userId, $assignRoleId]);
                    }
                }

                // 3) Create or link professional record (only for professional-type roles)
                if ($isProfessionalType && $userId > 0) {
                    $profStatus = $userStatus === 'active' ? 'ativo' : 'inativo';
                    if ($existingProfId !== null) {
                        // Link existing professional to the new user
                        $this->pdo->prepare('UPDATE professionals SET user_id = ?, status = ?, updated_at = NOW() WHERE id = ? AND clinic_id = ?')
                            ->execute([$userId, $profStatus, $existingProfId, $clinicId]);
                    } else {
                        $this->pdo->prepare('INSERT INTO professionals (clinic_id, user_id, name, status, created_at) VALUES (?, ?, ?, ?, NOW())')
                            ->execute([$clinicId, $userId, $name, $profStatus]);
                    }
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Linha ' . ($i + 2) . ': ' . $e->getMessage();
            }
        }
        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors, 'skipped_details' => $skippedDetails];
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function getFirstProfessional(int $clinicId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM professionals WHERE clinic_id = ? AND deleted_at IS NULL AND status = \'active\' ORDER BY id LIMIT 1');
        $stmt->execute([$clinicId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    /** @return list<array{id: int, import_type: string, file_name: string, total_rows: int, imported_rows: int, skipped_rows: int, error_rows: int, status: string, created_at: string, created_by_user_id: ?int, created_by_name: ?string}> */
    public function getImportHistory(int $clinicId): array
    {
        $stmt = $this->pdo->prepare('SELECT l.id, l.import_type, l.file_name, l.total_rows, l.imported_rows, l.skipped_rows, l.error_rows, l.status, l.created_at, l.created_by_user_id, u.name AS created_by_name FROM clinicorp_import_logs l LEFT JOIN users u ON u.id = l.created_by_user_id WHERE l.clinic_id = ? ORDER BY l.created_at DESC, l.id DESC LIMIT 50');
        $stmt->execute([$clinicId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Um registro do histórico de importações da clínica, com erros e ignorados decodificados.
     * skipped_list é null quando a importação foi feita antes do registro dos ignorados (skipped_json NULL).
     *
     * @return array<string, mixed>|null
     */
    public function getImportLog(int $clinicId, int $logId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT l.id, l.import_type, l.file_name, l.total_rows, l.imported_rows, l.skipped_rows, l.error_rows, l.errors_json, l.skipped_json, l.status, l.created_at, l.created_by_user_id, u.name AS created_by_name FROM clinicorp_import_logs l LEFT JOIN users u ON u.id = l.created_by_user_id WHERE l.id = ? AND l.clinic_id = ? LIMIT 1');
        $stmt->execute([$logId, $clinicId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return null;

        $errors = [];
        if ($row['errors_json'] !== null && $row['errors_json'] !== '') {
            $decoded = json_decode((string)$row['errors_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $e) {
                    if (is_scalar($e)) $errors[] = (string)$e;
                }
            }
        }

        $skipped = null;
        if ($row['skipped_json'] !== null && $row['skipped_json'] !== '') {
            $skipped = [];
            $decoded = json_decode((string)$row['skipped_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $s) {
                    if (!is_array($s)) continue;
                    $skipped[] = [
                        'line'   => (int)($s['line'] ?? 0),
                        'name'   => (string)($s['name'] ?? ''),
                        'reason' => (string)($s['reason'] ?? ''),
                    ];
                }
            }
        }

        unset($row['errors_json'], $row['skipped_json']);
        $row['errors_list'] = $errors;
        $row['skipped_list'] = $skipped;
        return $row;
    }

    private function logImport(int $clinicId, int $userId, string $type, string $fileName, int $totalRows, array $result): int
    {
        $errorsJson = !empty($result['errors']) ? json_encode(array_slice($result['errors'], 0, 500), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null;
        $skippedDetails = $result['skipped_details'] ?? [];
        $skippedJson = !empty($skippedDetails) ? json_encode(array_slice($skippedDetails, 0, 5000), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null;
        $stmt = $this->pdo->prepare('INSERT INTO clinicorp_import_logs (clinic_id, import_type, file_name, total_rows, imported_rows, skipped_rows, error_rows, errors_json, skipped_json, status, created_by_user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'completed\', ?, NOW())');
        $stmt->execute([
            $clinicId, $type, $fileName, $totalRows,
            $result['imported'], $result['skipped'], count($result['errors']),
            $errorsJson === false ? null : $errorsJson,
            $skippedJson === false ? null : $skippedJson,
            $userId,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
