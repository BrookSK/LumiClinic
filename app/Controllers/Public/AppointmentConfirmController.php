<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AppointmentConfirmationTokenRepository;
use App\Repositories\AppointmentLogRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\ClinicRepository;
use App\Repositories\ClinicSettingsRepository;
use App\Services\Whatsapp\WhatsappReminderSchedulerService;

final class AppointmentConfirmController extends Controller
{
    public function show(Request $request)
    {
        $token = trim((string)$request->input('token', ''));
        if ($token === '') {
            return $this->view('public/appointment_confirm', ['error' => 'Link inválido ou expirado.']);
        }

        $pdo = $this->container->get(\PDO::class);
        $tokenHash = hash('sha256', $token);

        $tokenRepo = new AppointmentConfirmationTokenRepository($pdo);
        $row = $tokenRepo->findValidByTokenHash($tokenHash);
        if ($row === null) {
            return $this->view('public/appointment_confirm', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$row['clinic_id'];
        $appointmentId = (int)$row['appointment_id'];

        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            return $this->view('public/appointment_confirm', ['error' => 'Agendamento inválido.']);
        }

        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $clinicName = $clinic !== null ? (string)($clinic['name'] ?? '') : '';

        $tz = 'America/Sao_Paulo';
        $settings = (new ClinicSettingsRepository($pdo))->findByClinicId($clinicId);
        if ($settings !== null && isset($settings['timezone']) && trim((string)$settings['timezone']) !== '') {
            $tz = (string)$settings['timezone'];
        }

        $startAt = (string)($appt['start_at'] ?? '');
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        if ($dt === false) {
            $dt = new \DateTimeImmutable('now');
        }
        try {
            $dt = $dt->setTimezone(new \DateTimeZone($tz));
        } catch (\Throwable $e) {
        }

        return $this->view('public/appointment_confirm', [
            'token' => $token,
            'clinic_name' => $clinicName,
            'appointment' => $appt,
            'start_date' => $dt->format('d/m/Y'),
            'start_time' => $dt->format('H:i'),
        ]);
    }

    public function submit(Request $request)
    {
        $token = trim((string)$request->input('token', ''));
        $action = trim((string)$request->input('action', ''));

        if ($token === '' || !in_array($action, ['confirm', 'cancel'], true)) {
            return $this->redirect('/a/confirm?token=' . urlencode($token));
        }

        $pdo = $this->container->get(\PDO::class);
        $tokenHash = hash('sha256', $token);

        $tokenRepo = new AppointmentConfirmationTokenRepository($pdo);
        $row = $tokenRepo->findValidByTokenHash($tokenHash);
        if ($row === null) {
            return $this->view('public/appointment_confirm', ['error' => 'Link inválido ou expirado.']);
        }

        $clinicId = (int)$row['clinic_id'];
        $appointmentId = (int)$row['appointment_id'];

        $apptRepo = new AppointmentRepository($pdo);
        $appt = $apptRepo->findById($clinicId, $appointmentId);
        if ($appt === null) {
            return $this->view('public/appointment_confirm', ['error' => 'Agendamento inválido.']);
        }

        $currentStatus = (string)($appt['status'] ?? '');
        $toStatus = $action === 'confirm' ? 'confirmed' : 'cancelled';

        if ($action === 'confirm' && !in_array($currentStatus, ['scheduled', 'confirmed'], true)) {
            $tokenRepo->markUsed((int)$row['id'], 'invalid');
            return $this->view('public/appointment_confirm', ['error' => 'Não é possível confirmar esta consulta.']);
        }

        if ($action === 'cancel' && in_array($currentStatus, ['cancelled', 'completed', 'no_show'], true)) {
            $tokenRepo->markUsed((int)$row['id'], 'invalid');
            return $this->view('public/appointment_confirm', ['error' => 'Não é possível cancelar esta consulta.']);
        }

        if ($currentStatus !== $toStatus) {
            $apptRepo->updateStatus($clinicId, $appointmentId, $toStatus);

            (new AppointmentLogRepository($pdo))->log(
                $clinicId,
                $appointmentId,
                $action === 'confirm' ? 'public_confirm' : 'public_cancel',
                ['status' => $currentStatus],
                ['status' => $toStatus],
                null,
                $request->ip()
            );

            (new AuditLogRepository($pdo))->log(
                null,
                $clinicId,
                $action === 'confirm' ? 'appointments.public_confirm' : 'appointments.public_cancel',
                ['appointment_id' => $appointmentId, 'from' => $currentStatus, 'to' => $toStatus],
                $request->ip()
            );

            (new WhatsappReminderSchedulerService($this->container))->scheduleForAppointment($clinicId, $appointmentId);
        }

        $tokenRepo->markUsed((int)$row['id'], $action);

        return $this->view('public/appointment_confirm', [
            'success' => $action === 'confirm'
                ? 'Consulta confirmada com sucesso.'
                : 'Consulta cancelada com sucesso.',
        ]);
    }
}
