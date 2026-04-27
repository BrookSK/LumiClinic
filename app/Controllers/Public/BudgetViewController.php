<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class BudgetViewController extends Controller
{
    /**
     * Generate a public token for a sale ID.
     * Uses HMAC so no DB column needed — deterministic and secure.
     */
    public static function generateToken(int $saleId): string
    {
        $secret = 'lumiclinic_budget_' . ($_ENV['APP_KEY'] ?? 'default_key_2026');
        return substr(hash_hmac('sha256', 'sale_' . $saleId, $secret), 0, 32);
    }

    public static function buildPublicUrl(int $saleId): string
    {
        $token = self::generateToken($saleId);
        $base = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        return rtrim($base, '/') . '/pub/budget?id=' . $saleId . '&token=' . $token;
    }

    public function show(Request $request): Response
    {
        $id = (int)$request->input('id', 0);
        $token = trim((string)$request->input('token', ''));

        if ($id <= 0 || $token === '') {
            return Response::html('Link inválido.', 400);
        }

        $expected = self::generateToken($id);
        if (!hash_equals($expected, $token)) {
            return Response::html('Link inválido ou expirado.', 403);
        }

        $pdo = $this->container->get(\PDO::class);

        // Find sale without clinic context
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();
        if (!$sale) {
            return Response::html('Orçamento não encontrado.', 404);
        }

        $clinicId = (int)$sale['clinic_id'];

        // Patient
        $patient = null;
        if ($sale['patient_id'] !== null) {
            $pStmt = $pdo->prepare("SELECT * FROM patients WHERE id = :id AND clinic_id = :c AND deleted_at IS NULL LIMIT 1");
            $pStmt->execute(['id' => (int)$sale['patient_id'], 'c' => $clinicId]);
            $patient = $pStmt->fetch() ?: null;
        }
        $sale['patient_name'] = $patient ? (string)($patient['name'] ?? '') : '';
        $sale['patient_email'] = $patient ? trim((string)($patient['email'] ?? '')) : '';
        $sale['patient_phone'] = $patient ? trim((string)($patient['phone'] ?? '')) : '';

        // Clinic
        $cStmt = $pdo->prepare("SELECT * FROM clinics WHERE id = :id LIMIT 1");
        $cStmt->execute(['id' => $clinicId]);
        $clinic = $cStmt->fetch() ?: null;

        // Items
        $iStmt = $pdo->prepare("SELECT * FROM sale_items WHERE sale_id = :id AND deleted_at IS NULL ORDER BY id");
        $iStmt->execute(['id' => $id]);
        $items = $iStmt->fetchAll();

        // Payments
        $pyStmt = $pdo->prepare("SELECT * FROM payments WHERE sale_id = :id AND deleted_at IS NULL ORDER BY due_date, id");
        $pyStmt->execute(['id' => $id]);
        $payments = $pyStmt->fetchAll();

        // Services map
        $svcStmt = $pdo->prepare("SELECT id, name FROM services WHERE clinic_id = :c AND deleted_at IS NULL");
        $svcStmt->execute(['c' => $clinicId]);
        $services = $svcStmt->fetchAll();

        // Professionals map
        $proStmt = $pdo->prepare("SELECT id, name FROM professionals WHERE clinic_id = :c AND deleted_at IS NULL");
        $proStmt->execute(['c' => $clinicId]);
        $professionals = $proStmt->fetchAll();

        // Render the view directly (sale_print is a standalone HTML page, no layout needed)
        $sale_data = $sale;
        $items_data = $items;
        $payments_data = $payments;
        $services_data = $services;
        $professionals_data = $professionals;

        // Extract variables for the view
        $viewVars = [
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments,
            'services' => $services,
            'packages' => [],
            'plans' => [],
            'professionals' => $professionals,
            'clinic' => $clinic,
            'patient' => $patient,
        ];
        extract($viewVars);

        ob_start();
        require dirname(__DIR__, 2) . '/Views/finance/sale_print.php';
        $html = (string)ob_get_clean();

        return Response::html($html);
    }
}
