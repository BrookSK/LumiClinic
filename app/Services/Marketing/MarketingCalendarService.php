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
        $color = trim((string)($data['color'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $linkUrl = $this->parseLinksToJson($data['link_url'] ?? null, $data['links'] ?? null);
        $assignedUserId = (int)($data['assigned_user_id'] ?? 0);

        if ($entryDate === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $entryDate) === false) {
            throw new \RuntimeException('Data inválida.');
        }
        if ($title === '') {
            throw new \RuntimeException('Título é obrigatório.');
        }

        $contentType = $this->sanitizeContentType($contentType);
        $status = $this->sanitizeStatus($status);
        $color = $this->sanitizeColor($color);

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        $id = $repo->create(
            (int)$clinicId,
            $entryDate,
            $contentType,
            $status,
            $color,
            $title,
            ($notes === '' ? null : $notes),
            ($linkUrl === '' ? null : $linkUrl),
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
        $color = trim((string)($data['color'] ?? ''));
        $title = trim((string)($data['title'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));
        $linkUrl = $this->parseLinksToJson($data['link_url'] ?? null, $data['links'] ?? null);
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
        $color = $this->sanitizeColor($color);

        $repo = new MarketingCalendarRepository($this->container->get(\PDO::class));
        $repo->update(
            (int)$clinicId,
            $id,
            $entryDate,
            $contentType,
            $status,
            $color,
            $title,
            ($notes === '' ? null : $notes),
            ($linkUrl === '' ? null : $linkUrl),
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

    private function sanitizeColor(string $color): ?string
    {
        $color = trim($color);
        if ($color === '') {
            return null;
        }

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            throw new \RuntimeException('Cor inválida.');
        }

        return strtolower($color);
    }

    /**
     * Recebe links como array (do campo links[]) ou string única (link_url legado)
     * e retorna JSON serializado ou null.
     * @param mixed $linkUrlLegacy
     * @param mixed $linksArray
     */
    private function parseLinksToJson(mixed $linkUrlLegacy, mixed $linksArray): ?string
    {
        $links = [];

        // Novo formato: array de links enviados como links[]
        if (is_array($linksArray)) {
            foreach ($linksArray as $l) {
                $l = trim((string)$l);
                if ($l !== '') {
                    $links[] = $l;
                }
            }
        }

        // Legado: campo único link_url (pode ser JSON antigo ou URL simples)
        if ($links === [] && $linkUrlLegacy !== null) {
            $raw = trim((string)$linkUrlLegacy);
            if ($raw !== '') {
                // Tentar decodificar como JSON existente
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $l) {
                        $l = trim((string)$l);
                        if ($l !== '') {
                            $links[] = $l;
                        }
                    }
                } else {
                    $links[] = $raw;
                }
            }
        }

        if ($links === []) {
            return null;
        }

        return (string)json_encode(array_values($links), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decodifica o campo link_url (JSON ou string legada) para array de strings.
     * @return list<string>
     */
    public static function decodeLinks(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $raw = trim($raw);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
        // Legado: string simples
        return [$raw];
    }

    // ─── Tuquinha Integration ────────────────────────────────────

    public function getTuquinhaApiKey(): string
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare('SELECT tuquinha_api_key FROM clinic_settings WHERE clinic_id = ? LIMIT 1');
        $stmt->execute([$clinicId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return trim((string)($row['tuquinha_api_key'] ?? ''));
    }

    public function setTuquinhaApiKey(string $apiKey, string $ip): void
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare('UPDATE clinic_settings SET tuquinha_api_key = ?, updated_at = NOW() WHERE clinic_id = ? LIMIT 1');
        $stmt->execute([trim($apiKey) !== '' ? trim($apiKey) : null, $clinicId]);

        // Ensure row exists
        if ($stmt->rowCount() === 0) {
            $pdo->prepare('INSERT INTO clinic_settings (clinic_id, tuquinha_api_key) VALUES (?, ?) ON DUPLICATE KEY UPDATE tuquinha_api_key = VALUES(tuquinha_api_key)')
                ->execute([$clinicId, trim($apiKey) !== '' ? trim($apiKey) : null]);
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'marketing.tuquinha.config', ['action' => 'set_api_key'], $ip, $roleCodes);
    }

    /** @return array{imported:int,updated:int,skipped:int,errors:list<string>} */
    public function syncFromTuquinha(int $year, int $month, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $apiKey = $this->getTuquinhaApiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('API Key do Tuquinha não configurada.');
        }

        $client = new TuquinhaClient($apiKey);
        $remoteEvents = $client->listEvents($year, $month);

        $pdo = $this->container->get(\PDO::class);
        $repo = new MarketingCalendarRepository($pdo);

        // Map content types from Tuquinha to local
        $typeMap = ['post'=>'post','story'=>'story','reels'=>'reel','video'=>'video','email'=>'email','anuncio'=>'ad','outro'=>'other'];
        $statusMap = ['planejado'=>'planned','produzido'=>'produced','postado'=>'posted'];

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($remoteEvents as $ev) {
            try {
                $remoteId = (int)($ev['id'] ?? 0);
                $title = trim((string)($ev['title'] ?? ''));
                $eventDate = trim((string)($ev['event_date'] ?? ''));
                $eventType = trim((string)($ev['event_type'] ?? 'post'));
                $status = trim((string)($ev['status'] ?? 'planejado'));
                $color = trim((string)($ev['color'] ?? ''));
                $notes = trim((string)($ev['notes'] ?? ''));
                $responsible = trim((string)($ev['responsible'] ?? ''));
                $links = is_array($ev['reference_links'] ?? null) ? $ev['reference_links'] : [];

                if ($title === '' || $eventDate === '') { $skipped++; continue; }

                $localType = $typeMap[$eventType] ?? 'other';
                $localStatus = $statusMap[$status] ?? 'planned';
                $localColor = (preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : null);
                $linksJson = $links !== [] ? json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
                $fullNotes = $notes;
                if ($responsible !== '') {
                    $fullNotes = ($fullNotes !== '' ? $fullNotes . "\n" : '') . 'Responsável: ' . $responsible;
                }
                $fullNotes .= "\n[tuquinha_id:" . $remoteId . ']';

                // Check if already imported (by tuquinha_id in notes)
                $existing = $this->findByTuquinhaId($clinicId, $remoteId);

                if ($existing !== null) {
                    $repo->update($clinicId, (int)$existing['id'], $eventDate, $localType, $localStatus, $localColor, $title, $fullNotes !== '' ? $fullNotes : null, $linksJson, null);
                    $updated++;
                } else {
                    $repo->create($clinicId, $eventDate, $localType, $localStatus, $localColor, $title, $fullNotes !== '' ? $fullNotes : null, $linksJson, null, $actorId);
                    $imported++;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Evento #' . ($ev['id'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        $audit = new AuditLogRepository($pdo);
        $roleCodes = isset($_SESSION['role_codes']) && is_array($_SESSION['role_codes']) ? $_SESSION['role_codes'] : null;
        $audit->log($actorId, $clinicId, 'marketing.tuquinha.sync', ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'year' => $year, 'month' => $month], $ip, $roleCodes);

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors];
    }

    /** @return array<string,mixed>|null */
    private function findByTuquinhaId(int $clinicId, int $tuquinhaId): ?array
    {
        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare("SELECT id FROM marketing_calendar_entries WHERE clinic_id = ? AND deleted_at IS NULL AND notes LIKE ? LIMIT 1");
        $stmt->execute([$clinicId, '%[tuquinha_id:' . $tuquinhaId . ']%']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return array{pushed:int,errors:list<string>} */
    public function pushToTuquinha(int $year, int $month, string $ip): array
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $actorId = $auth->userId();
        if ($clinicId === null || $actorId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $apiKey = $this->getTuquinhaApiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('API Key do Tuquinha não configurada.');
        }

        $monthYmd = sprintf('%04d-%02d-01', $year, $month);
        $localEvents = $this->listByMonth($monthYmd);

        $client = new TuquinhaClient($apiKey);
        $typeMap = ['post'=>'post','story'=>'story','reel'=>'reels','video'=>'video','email'=>'email','ad'=>'anuncio','other'=>'outro','blog'=>'outro'];
        $statusMap = ['planned'=>'planejado','produced'=>'produzido','posted'=>'postado','cancelled'=>'planejado'];

        $pushed = 0;
        $errors = [];

        foreach ($localEvents as $ev) {
            try {
                $notes = (string)($ev['notes'] ?? '');
                // Skip if already has tuquinha_id (was imported from there)
                if (preg_match('/\[tuquinha_id:\d+\]/', $notes)) { continue; }

                $title = trim((string)($ev['title'] ?? ''));
                $entryDate = trim((string)($ev['entry_date'] ?? ''));
                if ($title === '' || $entryDate === '') { continue; }

                $localType = (string)($ev['content_type'] ?? 'post');
                $localStatus = (string)($ev['status'] ?? 'planned');
                $links = self::decodeLinks($ev['link_url'] ?? null);

                $payload = [
                    'title' => $title,
                    'event_date' => $entryDate,
                    'event_type' => $typeMap[$localType] ?? 'outro',
                    'status' => $statusMap[$localStatus] ?? 'planejado',
                    'color' => (string)($ev['color'] ?? ''),
                    'notes' => $notes,
                    'reference_links' => $links,
                ];

                $client->createEvent($payload);
                $pushed++;
            } catch (\Throwable $e) {
                $errors[] = 'Evento local #' . ($ev['id'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        return ['pushed' => $pushed, 'errors' => $errors];
    }
}
