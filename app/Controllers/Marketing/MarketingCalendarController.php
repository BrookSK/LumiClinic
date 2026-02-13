<?php

declare(strict_types=1);

namespace App\Controllers\Marketing;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Marketing\MarketingCalendarService;

final class MarketingCalendarController extends Controller
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

    public function index(Request $request)
    {
        $this->authorize('marketing.calendar.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $month = trim((string)$request->input('month', ''));
        if ($month === '' || \DateTimeImmutable::createFromFormat('Y-m-d', $month) === false) {
            $month = (new \DateTimeImmutable('first day of this month'))->format('Y-m-01');
        }

        $svc = new MarketingCalendarService($this->container);
        $rows = $svc->listByMonth($month);
        $users = $svc->listUsers();

        return $this->view('marketing/calendar', [
            'rows' => $rows,
            'users' => $users,
            'month' => $month,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('marketing.calendar.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $month = trim((string)$request->input('month', ''));
        $month = $month !== '' ? $month : (new \DateTimeImmutable('first day of this month'))->format('Y-m-01');

        try {
            (new MarketingCalendarService($this->container))->create([
                'entry_date' => trim((string)$request->input('entry_date', '')),
                'content_type' => trim((string)$request->input('content_type', 'post')),
                'status' => trim((string)$request->input('status', 'planned')),
                'title' => trim((string)$request->input('title', '')),
                'notes' => trim((string)$request->input('notes', '')),
                'assigned_user_id' => (int)$request->input('assigned_user_id', 0),
            ], $request->ip(), $request->header('user-agent'));

            return $this->redirect('/marketing/calendar?month=' . urlencode($month) . '&success=' . urlencode('Criado.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/marketing/calendar?month=' . urlencode($month) . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function edit(Request $request)
    {
        $this->authorize('marketing.calendar.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/calendar');
        }

        $svc = new MarketingCalendarService($this->container);
        $row = $svc->get($id);
        if ($row === null) {
            return $this->redirect('/marketing/calendar?error=' . urlencode('Item inválido.'));
        }

        $users = $svc->listUsers();

        return $this->view('marketing/calendar_edit', [
            'row' => $row,
            'users' => $users,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('marketing.calendar.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $month = trim((string)$request->input('month', ''));

        try {
            (new MarketingCalendarService($this->container))->update($id, [
                'entry_date' => trim((string)$request->input('entry_date', '')),
                'content_type' => trim((string)$request->input('content_type', 'post')),
                'status' => trim((string)$request->input('status', 'planned')),
                'title' => trim((string)$request->input('title', '')),
                'notes' => trim((string)$request->input('notes', '')),
                'assigned_user_id' => (int)$request->input('assigned_user_id', 0),
            ], $request->ip(), $request->header('user-agent'));

            if ($month !== '') {
                return $this->redirect('/marketing/calendar?month=' . urlencode($month) . '&success=' . urlencode('Salvo.'));
            }

            return $this->redirect('/marketing/calendar/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/marketing/calendar/edit?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }
    }

    public function delete(Request $request)
    {
        $this->authorize('marketing.calendar.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $id = (int)$request->input('id', 0);
        $month = trim((string)$request->input('month', ''));

        try {
            (new MarketingCalendarService($this->container))->delete($id, $request->ip(), $request->header('user-agent'));
            $to = '/marketing/calendar?success=' . urlencode('Excluído.');
            if ($month !== '') {
                $to = '/marketing/calendar?month=' . urlencode($month) . '&success=' . urlencode('Excluído.');
            }
            return $this->redirect($to);
        } catch (\RuntimeException $e) {
            $to = '/marketing/calendar?error=' . urlencode($e->getMessage());
            if ($month !== '') {
                $to = '/marketing/calendar?month=' . urlencode($month) . '&error=' . urlencode($e->getMessage());
            }
            return $this->redirect($to);
        }
    }
}
