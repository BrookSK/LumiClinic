<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Core\Container\Container;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ConsentAcceptanceRepository;
use App\Repositories\MedicalImageRepository;
use App\Repositories\PatientAppointmentRequestRepository;
use App\Repositories\PatientNotificationRepository;
use App\Repositories\PatientUploadRepository;
use App\Repositories\SignatureRepository;

final class PortalSearchService
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array{
     *   q:string,
     *   agenda:list<array<string,mixed>>,
     *   documentos:list<array<string,mixed>>,
     *   notificacoes:list<array<string,mixed>>,
     *   uploads:list<array<string,mixed>>
     * }
     */
    public function search(int $clinicId, int $patientId, string $q, string $ip, ?string $userAgent = null): array
    {
        $q = $this->normalizeQuery($q);
        if ($q === '') {
            return [
                'q' => '',
                'agenda' => [],
                'documentos' => [],
                'notificacoes' => [],
                'uploads' => [],
            ];
        }

        $pdo = $this->container->get(\PDO::class);

        $audit = new AuditLogRepository($pdo);
        $audit->log(null, $clinicId, 'portal.search', ['patient_id' => $patientId, 'q' => $q], $ip, null, 'patient', $patientId, $userAgent);

        $agenda = $this->searchAgenda($pdo, $clinicId, $patientId, $q);
        $documentos = $this->searchDocumentos($pdo, $clinicId, $patientId, $q);
        $notificacoes = $this->searchNotificacoes($pdo, $clinicId, $patientId, $q);
        $uploads = $this->searchUploads($pdo, $clinicId, $patientId, $q);

        return [
            'q' => $q,
            'agenda' => $agenda,
            'documentos' => $documentos,
            'notificacoes' => $notificacoes,
            'uploads' => $uploads,
        ];
    }

    private function normalizeQuery(string $q): string
    {
        $q = trim($q);
        $q = preg_replace('/\s+/', ' ', $q) ?? '';
        return mb_substr($q, 0, 80);
    }

    private function haystackContains(string $haystack, string $needle): bool
    {
        $haystack = mb_strtolower($haystack);
        $needle = mb_strtolower($needle);
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }

    /** @return list<array<string,mixed>> */
    private function searchAgenda(\PDO $pdo, int $clinicId, int $patientId, string $q): array
    {
        $apptRepo = new AppointmentRepository($pdo);
        $reqRepo = new PatientAppointmentRequestRepository($pdo);

        $appointments = $apptRepo->listUpcomingByPatient($clinicId, $patientId, 50, 0);
        $requests = $reqRepo->listPendingByPatient($clinicId, $patientId, 50, 0);

        $out = [];

        foreach ($appointments as $a) {
            $text = implode(' | ', [
                (string)($a['service_name'] ?? ''),
                (string)($a['professional_name'] ?? ''),
                (string)($a['start_at'] ?? ''),
                (string)($a['status'] ?? ''),
                (string)($a['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'appointment',
                    'id' => (int)($a['id'] ?? 0),
                    'title' => (string)($a['service_name'] ?? ''),
                    'subtitle' => (string)($a['professional_name'] ?? ''),
                    'at' => (string)($a['start_at'] ?? ''),
                    'status' => (string)($a['status'] ?? ''),
                ];
            }
        }

        foreach ($requests as $r) {
            $text = implode(' | ', [
                (string)($r['type'] ?? ''),
                (string)($r['note'] ?? ''),
                (string)($r['requested_start_at'] ?? ''),
                (string)($r['appointment_id'] ?? ''),
                (string)($r['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'request',
                    'id' => (int)($r['id'] ?? 0),
                    'title' => (string)($r['type'] ?? 'Solicitação'),
                    'subtitle' => 'Agendamento #' . (int)($r['appointment_id'] ?? 0),
                    'at' => (string)($r['requested_start_at'] ?? ''),
                    'status' => 'pendente',
                ];
            }
        }

        return array_slice($out, 0, 12);
    }

    /** @return list<array<string,mixed>> */
    private function searchDocumentos(\PDO $pdo, int $clinicId, int $patientId, string $q): array
    {
        $accRepo = new ConsentAcceptanceRepository($pdo);
        $sigRepo = new SignatureRepository($pdo);
        $imgRepo = new MedicalImageRepository($pdo);

        $acceptances = $accRepo->listByPatient($clinicId, $patientId, 120);
        $signatures = $sigRepo->listByPatient($clinicId, $patientId, 120);
        $images = $imgRepo->listVisibleToPatient($clinicId, $patientId, 200);

        $out = [];

        foreach ($acceptances as $a) {
            $text = implode(' | ', [
                (string)($a['procedure_type'] ?? ''),
                (string)($a['accepted_at'] ?? ''),
                (string)($a['term_id'] ?? ''),
                (string)($a['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'acceptance',
                    'id' => (int)($a['id'] ?? 0),
                    'title' => 'Termo #' . (int)($a['term_id'] ?? 0),
                    'subtitle' => (string)($a['procedure_type'] ?? ''),
                    'at' => (string)($a['accepted_at'] ?? ''),
                ];
            }
        }

        foreach ($signatures as $s) {
            $text = implode(' | ', [
                (string)($s['created_at'] ?? ''),
                (string)($s['term_acceptance_id'] ?? ''),
                (string)($s['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'signature',
                    'id' => (int)($s['id'] ?? 0),
                    'title' => 'Assinatura #' . (int)($s['id'] ?? 0),
                    'subtitle' => 'Aceite #' . (string)($s['term_acceptance_id'] ?? ''),
                    'at' => (string)($s['created_at'] ?? ''),
                    'href' => '/portal/signatures/file?id=' . (int)($s['id'] ?? 0),
                ];
            }
        }

        foreach ($images as $img) {
            $text = implode(' | ', [
                (string)($img['kind'] ?? ''),
                (string)($img['taken_at'] ?? ''),
                (string)($img['procedure_type'] ?? ''),
                (string)($img['created_at'] ?? ''),
                (string)($img['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'image',
                    'id' => (int)($img['id'] ?? 0),
                    'title' => 'Imagem #' . (int)($img['id'] ?? 0),
                    'subtitle' => (string)($img['kind'] ?? ''),
                    'at' => (string)($img['taken_at'] ?? $img['created_at'] ?? ''),
                    'href' => '/portal/medical-images/file?id=' . (int)($img['id'] ?? 0),
                ];
            }
        }

        return array_slice($out, 0, 12);
    }

    /** @return list<array<string,mixed>> */
    private function searchNotificacoes(\PDO $pdo, int $clinicId, int $patientId, string $q): array
    {
        $repo = new PatientNotificationRepository($pdo);
        $items = $repo->listLatestByPatient($clinicId, $patientId, 80);

        $out = [];
        foreach ($items as $n) {
            $text = implode(' | ', [
                (string)($n['title'] ?? ''),
                (string)($n['body'] ?? ''),
                (string)($n['type'] ?? ''),
                (string)($n['created_at'] ?? ''),
                (string)($n['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'notification',
                    'id' => (int)($n['id'] ?? 0),
                    'title' => (string)($n['title'] ?? ''),
                    'subtitle' => (string)($n['type'] ?? ''),
                    'at' => (string)($n['created_at'] ?? ''),
                    'read_at' => $n['read_at'] ?? null,
                ];
            }
        }

        return array_slice($out, 0, 12);
    }

    /** @return list<array<string,mixed>> */
    private function searchUploads(\PDO $pdo, int $clinicId, int $patientId, string $q): array
    {
        $repo = new PatientUploadRepository($pdo);
        $items = $repo->listByPatient($clinicId, $patientId, 80);

        $out = [];
        foreach ($items as $u) {
            $text = implode(' | ', [
                (string)($u['kind'] ?? ''),
                (string)($u['status'] ?? ''),
                (string)($u['note'] ?? ''),
                (string)($u['taken_at'] ?? ''),
                (string)($u['created_at'] ?? ''),
                (string)($u['id'] ?? ''),
            ]);
            if ($this->haystackContains($text, $q)) {
                $out[] = [
                    'type' => 'upload',
                    'id' => (int)($u['id'] ?? 0),
                    'title' => 'Upload #' . (int)($u['id'] ?? 0),
                    'subtitle' => (string)($u['kind'] ?? ''),
                    'at' => (string)($u['taken_at'] ?? $u['created_at'] ?? ''),
                    'status' => (string)($u['status'] ?? ''),
                ];
            }
        }

        return array_slice($out, 0, 12);
    }
}
