<?php

declare(strict_types=1);

namespace App\Services\Anamnesis;

use App\Core\Container\Container;
use App\Repositories\AppointmentAnamnesisRequestRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Repositories\PatientRepository;
use App\Services\Security\CryptoService;

final class AppointmentAnamnesisLinkService
{
    public function __construct(private readonly Container $container) {}

    /** @return array{url:string,token:string,request_id:int} */
    public function ensureLinkForAppointment(int $clinicId, int $appointmentId, ?int $actorUserId = null): array
    {
        $pdo = $this->container->get(\PDO::class);
        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            throw new \RuntimeException('Agendamento inválido.');
        }

        $patientId = (int)($appt['patient_id'] ?? 0);
        if ($patientId <= 0) {
            throw new \RuntimeException('Agendamento sem paciente.');
        }

        $patients = new PatientRepository($pdo);
        if ($patients->findById($clinicId, $patientId) === null) {
            throw new \RuntimeException('Paciente inválido.');
        }

        $settings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        $templateId = is_array($settings) && isset($settings['anamnesis_default_template_id'])
            ? (int)$settings['anamnesis_default_template_id']
            : 0;
        if ($templateId <= 0) {
            throw new \RuntimeException('Template padrão de anamnese não configurado.');
        }

        $reqRepo = new AppointmentAnamnesisRequestRepository($pdo);
        $existing = $reqRepo->findLatestValidByAppointment($clinicId, $appointmentId);
        if ($existing !== null) {
            $enc = isset($existing['token_encrypted']) ? (string)$existing['token_encrypted'] : '';
            $enc = trim($enc);
            if ($enc === '') {
                throw new \RuntimeException('Token inválido.');
            }

            $token = (new CryptoService($this->container))->decrypt($clinicId, $enc);
            $token = trim($token);
            if ($token === '') {
                throw new \RuntimeException('Token inválido.');
            }

            return [
                'url' => $this->buildUrl($token),
                'token' => $token,
                'request_id' => (int)$existing['id'],
            ];
        }

        $startAt = (string)($appt['start_at'] ?? '');
        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($start === false) {
            $start = new \DateTimeImmutable('now');
        }
        $expiresAt = $start->modify('+2 days')->format('Y-m-d H:i:s');

        $rawToken = bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $rawToken);
        $tokenEncrypted = (new CryptoService($this->container))->encrypt($clinicId, $rawToken);

        $requestId = $reqRepo->create(
            $clinicId,
            $appointmentId,
            $patientId,
            $templateId,
            $tokenHash,
            $tokenEncrypted,
            $expiresAt,
            $actorUserId
        );

        return [
            'url' => $this->buildUrl($rawToken),
            'token' => $rawToken,
            'request_id' => $requestId,
        ];
    }

    public function buildUrl(string $token): string
    {
        $token = trim($token);
        $cfg = $this->container->has('config') ? $this->container->get('config') : [];
        $baseUrl = is_array($cfg) && isset($cfg['app']) && is_array($cfg['app'])
            ? (string)($cfg['app']['base_url'] ?? '')
            : '';
        $baseUrl = rtrim($baseUrl !== '' ? $baseUrl : (string)(getenv('APP_BASE_URL') ?: ''), '/');

        if ($baseUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . (string)$_SERVER['HTTP_HOST'];
        }

        return $baseUrl . '/a/anamnese?token=' . urlencode($token);
    }
}
