<?php

declare(strict_types=1);

namespace App\Repositories;

final class AppointmentLogRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array<string,mixed>|null $from
     * @param array<string,mixed>|null $to
     */
    public function log(
        int $clinicId,
        int $appointmentId,
        string $action,
        ?array $from,
        ?array $to,
        ?int $userId,
        string $ip
    ): int {
        $sql = "
            INSERT INTO appointment_logs (
                clinic_id, appointment_id, action,
                from_json, to_json,
                user_id, ip_address,
                created_at
            ) VALUES (
                :clinic_id, :appointment_id, :action,
                :from_json, :to_json,
                :user_id, :ip_address,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'appointment_id' => $appointmentId,
            'action' => $action,
            'from_json' => $from !== null ? json_encode($from, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'to_json' => $to !== null ? json_encode($to, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'user_id' => $userId,
            'ip_address' => ($ip === '' ? null : $ip),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return list<array<string,mixed>> */
    public function listByAppointment(int $clinicId, int $appointmentId, int $limit = 200): array
    {
        $sql = "
            SELECT id, clinic_id, appointment_id, action, from_json, to_json, user_id, ip_address, created_at
            FROM appointment_logs
            WHERE clinic_id = :clinic_id
              AND appointment_id = :appointment_id
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'appointment_id' => $appointmentId]);

        /** @var list<array<string,mixed>> */
        return $stmt->fetchAll();
    }
}
