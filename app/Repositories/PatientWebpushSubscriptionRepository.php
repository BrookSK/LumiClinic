<?php

declare(strict_types=1);

namespace App\Repositories;

final class PatientWebpushSubscriptionRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    /**
     * @param array{endpoint:string,keys:array{p256dh:string,auth:string}} $subscription
     */
    public function upsert(
        int $clinicId,
        int $patientId,
        int $patientUserId,
        array $subscription,
        ?string $ip,
        ?string $userAgent
    ): int {
        $endpoint = trim((string)($subscription['endpoint'] ?? ''));
        $keys = isset($subscription['keys']) && is_array($subscription['keys']) ? $subscription['keys'] : [];
        $p256dh = trim((string)($keys['p256dh'] ?? ''));
        $auth = trim((string)($keys['auth'] ?? ''));

        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            throw new \RuntimeException('Subscription inválida.');
        }

        $sql = "
            UPDATE patient_webpush_subscriptions
               SET p256dh = :p256dh,
                   auth = :auth,
                   user_agent = :user_agent,
                   ip = :ip,
                   updated_at = NOW(),
                   deleted_at = NULL
             WHERE clinic_id = :clinic_id
               AND patient_user_id = :patient_user_id
               AND endpoint = :endpoint
             LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_user_id' => $patientUserId,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'user_agent' => ($userAgent !== null ? substr($userAgent, 0, 255) : null),
            'ip' => ($ip !== null ? substr($ip, 0, 64) : null),
        ]);

        if ($stmt->rowCount() > 0) {
            $row = $this->findByEndpoint($clinicId, $patientUserId, $endpoint);
            return (int)($row['id'] ?? 0);
        }

        $sql2 = "
            INSERT INTO patient_webpush_subscriptions (
                clinic_id, patient_id, patient_user_id,
                endpoint, p256dh, auth,
                user_agent, ip,
                created_at, updated_at, deleted_at
            ) VALUES (
                :clinic_id, :patient_id, :patient_user_id,
                :endpoint, :p256dh, :auth,
                :user_agent, :ip,
                NOW(), NOW(), NULL
            )
        ";

        $stmt2 = $this->pdo->prepare($sql2);
        $stmt2->execute([
            'clinic_id' => $clinicId,
            'patient_id' => $patientId,
            'patient_user_id' => $patientUserId,
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'user_agent' => ($userAgent !== null ? substr($userAgent, 0, 255) : null),
            'ip' => ($ip !== null ? substr($ip, 0, 64) : null),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function softDeleteByEndpoint(int $clinicId, int $patientUserId, string $endpoint): void
    {
        $sql = "
            UPDATE patient_webpush_subscriptions
               SET deleted_at = NOW(),
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND patient_user_id = :patient_user_id
               AND endpoint = :endpoint
               AND deleted_at IS NULL
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_user_id' => $patientUserId,
            'endpoint' => trim($endpoint),
        ]);
    }

    /** @return list<array{endpoint:string,p256dh:string,auth:string}> */
    public function listActiveByPatient(int $clinicId, int $patientId, int $limit = 10): array
    {
        $sql = "
            SELECT endpoint, p256dh, auth
            FROM patient_webpush_subscriptions
            WHERE clinic_id = :clinic_id
              AND patient_id = :patient_id
              AND deleted_at IS NULL
            ORDER BY id DESC
            LIMIT " . (int)$limit . "
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['clinic_id' => $clinicId, 'patient_id' => $patientId]);

        /** @var list<array{endpoint:string,p256dh:string,auth:string}> */
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    private function findByEndpoint(int $clinicId, int $patientUserId, string $endpoint): ?array
    {
        $sql = "
            SELECT id, endpoint
            FROM patient_webpush_subscriptions
            WHERE clinic_id = :clinic_id
              AND patient_user_id = :patient_user_id
              AND endpoint = :endpoint
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'patient_user_id' => $patientUserId,
            'endpoint' => $endpoint,
        ]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}
