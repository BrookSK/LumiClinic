<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\LegalDocumentAcceptanceRepository;

final class SystemLegalOwnerAcceptancesController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $pdo = $this->container->get(\PDO::class);
        $rows = (new LegalDocumentAcceptanceRepository($pdo))->listClinicOwnerAcceptanceSummary(300);

        return $this->view('system/legal_owner_acceptances', [
            'rows' => $rows,
        ]);
    }
}
