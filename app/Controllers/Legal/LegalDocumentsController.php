<?php

declare(strict_types=1);

namespace App\Controllers\Legal;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Services\Auth\AuthService;
use App\Services\Legal\LegalDocumentService;
use App\Services\Legal\LegalDocumentSignatureService;

final class LegalDocumentsController extends Controller
{
    public function required(Request $request)
    {
        $auth = new AuthService($this->container);
        if ($auth->userId() === null) {
            return $this->redirect('/login');
        }

        $svc = new LegalDocumentService($this->container);
        $pending = $svc->listPendingRequiredForCurrentUser();

        return $this->view('legal/required', [
            'pending' => $pending,
        ]);
    }

    public function accept(Request $request)
    {
        $auth = new AuthService($this->container);
        if ($auth->userId() === null) {
            return $this->redirect('/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/legal/required');
        }

        try {
            (new LegalDocumentService($this->container))->acceptForCurrentUser($id, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/legal/required?error=' . urlencode($e->getMessage()));
        }

        return $this->redirect('/legal/required');
    }

    public function sign(Request $request)
    {
        $auth = new AuthService($this->container);
        if ($auth->userId() === null) {
            return $this->redirect('/login');
        }

        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/legal/required');
        }

        try {
            $data = (new LegalDocumentSignatureService($this->container))->getDocumentForSigningAsUser($id);
        } catch (\RuntimeException $e) {
            return $this->redirect('/legal/required?error=' . urlencode($e->getMessage()));
        }

        return $this->view('legal/sign', [
            'doc' => $data['doc'],
        ]);
    }

    public function signStore(Request $request)
    {
        $auth = new AuthService($this->container);
        if ($auth->userId() === null) {
            return $this->redirect('/login');
        }

        $id = (int)$request->input('id', 0);
        $sig = (string)$request->input('signature_data_url', '');
        if ($id <= 0) {
            return $this->redirect('/legal/required');
        }

        try {
            (new LegalDocumentSignatureService($this->container))->signAsUser($id, $sig, $request->ip(), $request->header('user-agent'));
        } catch (\RuntimeException $e) {
            return $this->redirect('/legal/sign?id=' . $id . '&error=' . urlencode($e->getMessage()));
        }

        return $this->redirect('/legal/required');
    }
}
