<?php

declare(strict_types=1);

namespace App\Controllers\Scheduling;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\AuditLogRepository;
use App\Repositories\ProfessionalRepository;
use App\Repositories\SchedulingBlockRepository;
use App\Services\Auth\AuthService;

final class BlockController extends Controller
{
    private function normalizeDatetimeLocal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            $value .= ':00';
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        if ($dt === false) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

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

    public function index(Request $request)
    {
        $this->authorize('blocks.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $from = trim((string)$request->input('from', date('Y-m-d')));
        $to = trim((string)$request->input('to', date('Y-m-d')));
        $professionalId = (int)$request->input('professional_id', 0);

        $fromDt = \DateTimeImmutable::createFromFormat('Y-m-d', $from);
        $toDt = \DateTimeImmutable::createFromFormat('Y-m-d', $to);
        if ($fromDt === false || $toDt === false) {
            $fromDt = new \DateTimeImmutable(date('Y-m-d'));
            $toDt = $fromDt;
        }
        if ($toDt < $fromDt) {
            $tmp = $fromDt;
            $fromDt = $toDt;
            $toDt = $tmp;
        }

        $startAt = $fromDt->format('Y-m-d 00:00:00');
        $endAt = $toDt->modify('+1 day')->format('Y-m-d 00:00:00');

        $pdo = $this->container->get(\PDO::class);
        $profRepo = new ProfessionalRepository($pdo);
        $professionals = $profRepo->listActiveByClinic($clinicId);

        $repo = new SchedulingBlockRepository($pdo);
        $blocks = $repo->listByClinicRange($clinicId, $startAt, $endAt);

        if ($professionalId > 0) {
            $blocks = array_values(array_filter($blocks, static fn ($b) => (int)($b['professional_id'] ?? 0) === 0 || (int)($b['professional_id'] ?? 0) === $professionalId));
        }

        return $this->view('scheduling/blocks', [
            'professionals' => $professionals,
            'blocks' => $blocks,
            'from' => $fromDt->format('Y-m-d'),
            'to' => $toDt->format('Y-m-d'),
            'filter_professional_id' => $professionalId,
            'error' => trim((string)$request->input('error', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('blocks.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $professionalId = (int)$request->input('professional_id', 0);
        $startAtRaw = trim((string)$request->input('start_at', ''));
        $endAtRaw = trim((string)$request->input('end_at', ''));
        $reason = trim((string)$request->input('reason', ''));
        $type = trim((string)$request->input('type', 'manual'));

        if ($startAtRaw === '' || $endAtRaw === '') {
            return $this->redirect('/blocks?error=' . urlencode('Início e fim são obrigatórios.'));
        }

        $startAt = $this->normalizeDatetimeLocal($startAtRaw);
        $endAt = $this->normalizeDatetimeLocal($endAtRaw);
        if ($startAt === null || $endAt === null) {
            return $this->redirect('/blocks?error=' . urlencode('Data/hora inválida.'));
        }

        if ($reason === '') {
            return $this->redirect('/blocks?error=' . urlencode('Motivo é obrigatório.'));
        }

        $st = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startAt);
        $en = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $endAt);
        if ($st === false || $en === false || $en <= $st) {
            return $this->redirect('/blocks?error=' . urlencode('Fim deve ser após o início.'));
        }

        $typeAllowed = ['manual', 'holiday', 'maintenance'];
        if (!in_array($type, $typeAllowed, true)) {
            $type = 'manual';
        }

        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        $userId = $auth->userId();
        if ($clinicId === null || $userId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }

        $pid = $professionalId > 0 ? $professionalId : null;

        $repo = new SchedulingBlockRepository($this->container->get(\PDO::class));
        $id = $repo->create($clinicId, $pid, $startAt, $endAt, $reason, $type, $userId);

        $audit = new AuditLogRepository($this->container->get(\PDO::class));
        $audit->log($userId, $clinicId, 'scheduling.block_create', [
            'block_id' => $id,
            'professional_id' => $pid,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'reason' => $reason,
            'type' => $type,
        ], $request->ip());

        return $this->redirect('/blocks');
    }
}
