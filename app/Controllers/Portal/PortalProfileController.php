<?php

declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\ClinicRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PatientProfileChangeRequestRepository;
use App\Services\Portal\PatientAuthService;
use App\Services\Portal\PatientProfileChangeRequestService;

final class PortalProfileController extends Controller
{
    public function index(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $pdo = $this->container->get(\PDO::class);
        $patient = (new PatientRepository($pdo))->findClinicalById($clinicId, $patientId);
        $clinic = (new ClinicRepository($pdo))->findById($clinicId);
        $pending = (new PatientProfileChangeRequestRepository($pdo))->findLatestPendingByPatient($clinicId, $patientId);

        return $this->view('portal/profile', [
            'patient' => $patient,
            'clinic' => $clinic,
            'pending_request' => $pending,
        ]);
    }

    public function requestChange(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        if ($auth->patientUserId() === null) {
            return $this->redirect('/portal/login');
        }

        try {
            $svc = new PatientProfileChangeRequestService($this->container);
            $svc->createForCurrentPatient([
                'name' => $request->input('name', ''),
                'email' => $request->input('email', ''),
                'phone' => $request->input('phone', ''),
                'birth_date' => $request->input('birth_date', ''),
                'address_street' => $request->input('address_street', ''),
                'address_number' => $request->input('address_number', ''),
                'address_complement' => $request->input('address_complement', ''),
                'address_district' => $request->input('address_district', ''),
                'address_city' => $request->input('address_city', ''),
                'address_state' => $request->input('address_state', ''),
                'address_zip' => $request->input('address_zip', ''),
            ], $request->ip());

            return $this->redirect('/portal/perfil?success=' . urlencode('Solicitação enviada para revisão da clínica.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/portal/perfil?error=' . urlencode($e->getMessage()));
        }
    }

    public function changePassword(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $patientUserId = $auth->patientUserId();
        if ($patientUserId === null) {
            return $this->redirect('/portal/login');
        }

        $currentPassword = (string)$request->input('current_password', '');
        $newPassword = (string)$request->input('new_password', '');

        if ($currentPassword === '' || $newPassword === '') {
            return $this->redirect('/portal/perfil?error=' . urlencode('Preencha a senha atual e a nova senha.'));
        }

        if (strlen($newPassword) < 6) {
            return $this->redirect('/portal/perfil?error=' . urlencode('A nova senha deve ter pelo menos 6 caracteres.'));
        }

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare("SELECT password_hash FROM patient_users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $patientUserId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, (string)$row['password_hash'])) {
            return $this->redirect('/portal/perfil?error=' . urlencode('Senha atual incorreta.'));
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE patient_users SET password_hash = :hash, updated_at = NOW() WHERE id = :id LIMIT 1")
            ->execute(['hash' => $newHash, 'id' => $patientUserId]);

        return $this->redirect('/portal/perfil?success=' . urlencode('Senha alterada com sucesso.'));
    }

    public function uploadPhoto(Request $request)
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return $this->redirect('/portal/login');
        }

        $file = $_FILES['photo'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->redirect('/portal/perfil?error=' . urlencode('Selecione uma foto.'));
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $mime = (string)($file['type'] ?? '');
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            return $this->redirect('/portal/perfil?error=' . urlencode('Formato inválido. Use JPG, PNG ou WebP.'));
        }

        $bytes = file_get_contents($tmp);
        if ($bytes === false || $bytes === '') {
            return $this->redirect('/portal/perfil?error=' . urlencode('Arquivo inválido.'));
        }

        if (strlen($bytes) > 5 * 1024 * 1024) {
            return $this->redirect('/portal/perfil?error=' . urlencode('A foto deve ter no máximo 5MB.'));
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $relative = 'patient_photos/patient_' . $patientId . '.' . $ext;
        \App\Services\Storage\PrivateStorage::put($clinicId, $relative, $bytes);

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare('UPDATE patients SET photo_path = :photo WHERE id = :id AND clinic_id = :clinic');
        $stmt->execute(['photo' => $relative, 'id' => $patientId, 'clinic' => $clinicId]);

        return $this->redirect('/portal/perfil?success=' . urlencode('Foto atualizada.'));
    }

    public function servePhoto(Request $request): \App\Core\Http\Response
    {
        $auth = new PatientAuthService($this->container);
        $clinicId = $auth->clinicId();
        $patientId = $auth->patientId();
        if ($clinicId === null || $patientId === null) {
            return \App\Core\Http\Response::html('', 404);
        }

        $pdo = $this->container->get(\PDO::class);
        $stmt = $pdo->prepare('SELECT photo_path FROM patients WHERE id = :id AND clinic_id = :clinic AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $patientId, 'clinic' => $clinicId]);
        $row = $stmt->fetch();

        $photoPath = trim((string)($row['photo_path'] ?? ''));
        if ($photoPath === '') {
            return \App\Core\Http\Response::html('', 404);
        }

        $fullPath = \App\Services\Storage\PrivateStorage::fullPath($clinicId, $photoPath);
        if (!is_file($fullPath)) {
            return \App\Core\Http\Response::html('', 404);
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return \App\Core\Http\Response::raw((string)file_get_contents($fullPath), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
