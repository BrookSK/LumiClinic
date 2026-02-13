<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Core\Container\Container;
use App\Repositories\AdminUserRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\MarketingCalendarRepository;
use App\Services\Auth\AuthService;

final class MarketingCalendarService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listByMonth(string $monthYmd): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        return $repo->listByMonth((int)$clinicId, $monthYmd, 5000);
    }

    /** @return list<array<string,mixed>> */
    public function listUsers(): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $usersRepo = new AdminUserRepository($this->container->get(\PDO::class));
        return $usersRepo->listByClinic((int)$clinicId, 500, 0);
    }

    /** @return array<string,mixed>|null */
    public function get(int $id): ?array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        return $repo->findById((int)$clinicId, $id);
    }

    public function create(array $data, string $ip, ?string $userAgent = null): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $entryDate = trim((string)($data['entry_date'] ?? ''));
        $contentType = trim((string)($data['content_type'] ?? 'post'));
        $status = trim((string)($data['status'] ?? 'planned'));
        $title = trim((string)($data['title'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $assignedUserId = (int)($data['assigned_user_id'] ?? 0);

        if ($entryDate === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $entryDate) === false) {
            throw new \RuntimeException('Data inválida.');
        }
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $contentType = $this->sanitizeContentType($contentType);
        $status = $this->sanitizeStatus($status);

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        $id = $repo->create(
            (int)$clinicId,
            $entryDate,
            $contentType,
            $status,
            $title,
            ($notes === '' ? null : $notes),
            ($assignedUserId > 0 ? $assignedUserId : null),
            $actorId
        );

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, (int)$clinicId, 'marketing.calendar.create', ['marketing_calendar_entry_id' => $id], $ip, $roleCodes, 'marketing_calendar_entry', $id, $userAgent);

        return $id;
    }

    public function update(int $id, array $data, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $entryDate = trim((string)($data['entry_date'] ?? ''));
        $contentType = trim((string)($data['content_type'] ?? 'post'));
        $status = trim((string)($data['status'] ?? 'planned'));
        $title = trim((string)($data['title'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $assignedUserId = (int)($data['assigned_user_id'] ?? 0);

        if ($id <= 0) {
            throw new \RuntimeException('Item inválido.');
        }
        if ($entryDate === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $entryDate) === false) {
            throw new \RuntimeException('Data inválida.');
        }
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $contentType = $this->sanitizeContentType($contentType);
        $status = $this->sanitizeStatus($status);

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        $repo->update(
            (int)$clinicId,
            $id,
            $entryDate,
            $contentType,
            $status,
            $title,
            ($notes === '' ? null : $notes),
            ($assignedUserId > 0 ? $assignedUserId : null)
        );

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, (int)$clinicId, 'marketing.calendar.update', ['marketing_calendar_entry_id' => $id], $ip, $roleCodes, 'marketing_calendar_entry', $id, $userAgent);
    }

    public function delete(int $id, string $ip, ?string $userAgent = null): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        if ($id <= 0) {
            return;
        }

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        $repo->softDelete((int)$clinicId, $id);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, (int)$clinicId, 'marketing.calendar.delete', ['marketing_calendar_entry_id' => $id], $ip, $roleCodes, 'marketing_calendar_entry', $id, $userAgent);
    }

    private function sanitizeContentType(string $type): string
    {
        $type = trim($type);
        $allowed = ['post', 'story', 'reel', 'video', 'email', 'blog', 'ad', 'other'];
        return in_array($type, $allowed, true) ? $type : 'post';
    }

    private function sanitizeStatus(string $status): string
    {
        $status = trim($status);
        $allowed = ['planned', 'produced', 'posted', 'cancelled'];
        return in_array($status, $allowed, true) ? $status : 'planned';
    }
}
