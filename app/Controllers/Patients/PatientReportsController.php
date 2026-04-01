<?php

declare(strict_types=1);

namespace App\Controllers\Patients;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\PatientRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Auth\AuthService;

final class PatientReportsController extends Controller
{
    private function redirectSuperAdminWithoutClinicContext(): ?\App\Core\Http\Response
    {
        $isSuperAdmin = isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1;
        if (!$isSuperAdmin) {
            return null;
        }

        $auth = new AuthService($this->container);
        if ($auth->clinicId() === null) {
            return $this->redirect('/sys/clinics');
        }

        return null;
    }

    private function getWaTemplates(int $clinicId): array
    {
        return (new WhatsappTemplateRepository($this->container->get(\PDO::class)))->listByClinic($clinicId);
    }

    public function birthdays(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $month = (int)$request->input('month', (int)date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patients = $repo->listBirthdaysByMonth($clinicId, $month);
        $waTemplates = $this->getWaTemplates($clinicId);

        return $this->view('patients/birthdays', [
            'patients'    => $patients,
            'month'       => $month,
            'wa_templates' => $waTemplates,
        ]);
    }

    public function followUp(Request $request)
    {
        $this->authorize('patients.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $days = (int)$request->input('days', 180);
        if ($days < 30) {
            $days = 30;
        }
        if ($days > 730) {
            $days = 730;
        }

        $repo = new PatientRepository($this->container->get(\PDO::class));
        $patients = $repo->listInactivePatients($clinicId, $days);
        $waTemplates = $this->getWaTemplates($clinicId);

        return $this->view('patients/follow_up', [
            'patients'    => $patients,
            'days'        => $days,
            'wa_templates' => $waTemplates,
        ]);
    }
}
