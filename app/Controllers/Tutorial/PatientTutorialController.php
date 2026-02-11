<?php

declare(strict_types=1);

namespace App\Controllers\Tutorial;

use App\Controllers\Controller;
use App\Core\Http\Request;

final class PatientTutorialController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('tutorial/patient');
    }

    public function portal(Request $request)
    {
        return $this->view('tutorial/patient_portal');
    }

    public function agenda(Request $request)
    {
        return $this->view('tutorial/patient_agenda');
    }

    public function documentos(Request $request)
    {
        return $this->view('tutorial/patient_documentos');
    }

    public function uploads(Request $request)
    {
        return $this->view('tutorial/patient_uploads');
    }

    public function notificacoes(Request $request)
    {
        return $this->view('tutorial/patient_notificacoes');
    }

    public function perfil(Request $request)
    {
        return $this->view('tutorial/patient_perfil');
    }

    public function seguranca(Request $request)
    {
        return $this->view('tutorial/patient_seguranca');
    }

    public function lgpd(Request $request)
    {
        return $this->view('tutorial/patient_lgpd');
    }

    public function apiTokens(Request $request)
    {
        return $this->view('tutorial/patient_api_tokens');
    }

    public function busca(Request $request)
    {
        return $this->view('tutorial/patient_busca');
    }
}
