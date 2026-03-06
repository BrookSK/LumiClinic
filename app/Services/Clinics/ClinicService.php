<?php

declare(strict_types=1);

namespace App\Services\Clinics;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicClosedDaysRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicWorkingHoursRepository;
use App\Services\Ai\OpenAiClient;
use App\Services\Auth\AuthService;

final class ClinicService
{
    public function __construct(private readonly Container $container) {}

    /** @return array<string, mixed>|null */
    public function getCurrentClinic(): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            return null;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        return $repo->findById($clinicId);
    }

    public function updateClinicName(string $name, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateName($clinicId, $name);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => ['name']], $ip);
    }

    public function updateTenantKey(?string $tenantKey, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $tenantKey = $tenantKey !== null ? trim($tenantKey) : null;
        if ($tenantKey === '') {
            $tenantKey = null;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateTenantKey($clinicId, $tenantKey);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => ['tenant_key']], $ip);
    }

    /** @param array<string, string|null> $fields */
    public function updateContactFields(array $fields, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $normalized = [];
        foreach ([
            'contact_email',
            'contact_phone',
            'contact_whatsapp',
            'contact_address',
            'contact_website',
            'contact_instagram',
            'contact_facebook',
        ] as $key) {
            $value = array_key_exists($key, $fields) ? $fields[$key] : null;
            $value = $value !== null ? trim((string)$value) : null;
            if ($value === '') {
                $value = null;
            }
            $normalized[$key] = $value;
        }

        $repo = new ClinicRepository($this->container->get(\PDO::class));
        $repo->updateContactFields($clinicId, $normalized);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.update', ['fields' => array_keys($normalized)], $ip);
    }

    /** @return list<array<string, mixed>> */
    public function listWorkingHours(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    public function createWorkingHour(int $weekday, string $start, string $end, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $weekday, $start, $end);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.working_hours_create', ['id' => $id, 'weekday' => $weekday], $ip);
    }

    public function deleteWorkingHour(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicWorkingHoursRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.working_hours_delete', ['id' => $id], $ip);
    }

    /** @return list<array<string, mixed>> */
    public function listClosedDays(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();

        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        return $repo->listByClinic($clinicId);
    }

    public function createClosedDay(string $date, string $reason, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $isOpen = 0;
        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $date, $reason, $isOpen);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.closed_days_create', ['id' => $id, 'date' => $date], $ip);
    }

    public function upsertClosedDay(string $date, ?string $reason, int $isOpen, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        (new ClinicClosedDaysRepository($this->container->get(\PDO::class)))->upsert($clinicId, $date, $reason, $isOpen);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'clinics.closed_days_upsert',
            ['date' => $date, 'is_open' => $isOpen === 1 ? 1 : 0],
            $ip
        );
    }

    /** @return list<array{date:string,name:string,is_open:int}> */
    public function generateClosedDaysWithAi(int $year, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $year = max(2000, min($year, 2100));

        $messages = [
            ['role' => 'system', 'content' => 'Você gera uma lista de feriados para clínicas no Brasil. Responda SOMENTE com um JSON (array). Nada de markdown.'],
            ['role' => 'user', 'content' => 'Gere feriados nacionais do Brasil para o ano ' . $year . '. Para cada item: {"date":"YYYY-MM-DD","name":"...","is_open":0|1}. Inclua: Confraternização Universal, Carnaval (segunda e terça), Quarta-feira de Cinzas, Sexta-feira Santa, Tiradentes, Dia do Trabalho, Corpus Christi, Independência, Nossa Senhora Aparecida, Finados, Proclamação da República, Natal. Sugira is_open=0 (fechado) por padrão e is_open=1 apenas se fizer sentido para uma clínica.'],
        ];

        $resp = (new OpenAiClient($this->container))->chatCompletions('gpt-4o-mini', $messages, 0.2);
        $content = '';
        if (isset($resp['choices'][0]['message']['content'])) {
            $content = (string)$resp['choices'][0]['message']['content'];
        }
        $content = trim($content);
        if ($content === '') {
            throw new \RuntimeException('IA retornou vazio.');
        }

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', (string)$content);
            $content = trim((string)$content);
        }

        $json = json_decode($content, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida da IA (esperado JSON).');
        }

        $out = [];
        foreach ($json as $it) {
            if (!is_array($it)) {
                continue;
            }
            $date = trim((string)($it['date'] ?? ''));
            $name = trim((string)($it['name'] ?? ''));
            $isOpen = (int)($it['is_open'] ?? 0);
            if ($date === '' || $name === '') {
                continue;
            }
            $out[] = ['date' => $date, 'name' => $name, 'is_open' => ($isOpen === 1 ? 1 : 0)];
        }

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            $userId,
            $clinicId,
            'clinics.closed_days_ai_generate',
            ['year' => $year, 'count' => count($out)],
            $ip
        );

        return $out;
    }

    public function deleteClosedDay(int $id, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();

        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new ClinicClosedDaysRepository($this->container->get(\PDO::class));
        $repo->softDelete($clinicId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'clinics.closed_days_delete', ['id' => $id], $ip);
    }
}
