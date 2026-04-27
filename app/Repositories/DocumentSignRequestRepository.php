<?php

declare(strict_types=1);

namespace App\Repositories;

final class DocumentSignRequestRepository
{
    public function __construct(private readonly \PDO $pdo) {}

    public function create(int $clinicId, int $patientId, string $title, ?string $body, ?string $filePath, ?string $fileName, ?string $fileMime, string $token, ?int $createdBy): int
    {
        $sql = "INSERT INTO document_sign_requests (clinic_id, patient_id, title, body, file_path, file_name, file_mime, token, created_by_user_id)
                VALUES (:c, :p, :t, :b, :fp, :fn, :fm, :tok, :cb)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'c' => $clinicId, 'p' => $patientId, 't' => $title, 'b' => $body,
            'fp' => $filePath, 'fn' => $fileName, 'fm' => $fileMime,
            'tok' => $token, 'cb' => $createdBy,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT d.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone, c.name AS clinic_name
            FROM document_sign_requests d
            JOIN patients p ON p.id = d.patient_id AND p.clinic_id = d.clinic_id
            JOIN clinics c ON c.id = d.clinic_id
            WHERE d.token = :t LIMIT 1");
        $stmt->execute(['t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $clinicId, int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT d.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone
            FROM document_sign_requests d
            JOIN patients p ON p.id = d.patient_id AND p.clinic_id = d.clinic_id
            WHERE d.id = :id AND d.clinic_id = :c LIMIT 1");
        $stmt->execute(['id' => $id, 'c' => $clinicId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function listByPatient(int $clinicId, int $patientId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM document_sign_requests WHERE clinic_id = :c AND patient_id = :p ORDER BY id DESC LIMIT " . (int)$limit);
        $stmt->execute(['c' => $clinicId, 'p' => $patientId]);
        return $stmt->fetchAll();
    }

    public function markSent(int $id, string $via): void
    {
        $this->pdo->prepare("UPDATE document_sign_requests SET sent_via = :v, sent_at = NOW() WHERE id = :id")
            ->execute(['v' => $via, 'id' => $id]);
    }

    public function sign(int $id, string $signatureData, string $ip, string $ua): void
    {
        $this->pdo->prepare("UPDATE document_sign_requests SET status = 'signed', signature_data = :s, signed_at = NOW(), signed_ip = :ip, signed_user_agent = :ua WHERE id = :id")
            ->execute(['s' => $signatureData, 'ip' => $ip, 'ua' => $ua, 'id' => $id]);
    }
}
