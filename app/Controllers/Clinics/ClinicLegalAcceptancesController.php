<?php

declare(strict_types=1);

namespace App\Controllers\Clinics;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentAcceptanceRepository;
use App\Services\Auth\AuthService;

final class ClinicLegalAcceptancesController extends Controller
{
    public function portal(Request $request)
    {
        $this->authorize('clinics.read');

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            return $this->redirect('/');
        }

        $limit = (int)$request->input('limit', 300);
        $limit = max(50, min(1000, $limit));

        $pdo = $this->container->get(\PDO::class);
        $rows = (new LegalDocumentAcceptanceRepository($pdo))->listPortalAcceptanceSummaryByClinic($clinicId, $limit);

        return $this->view('clinic/legal_acceptances_portal', [
            'rows' => $rows,
            'limit' => $limit,
        ]);
    }
}
