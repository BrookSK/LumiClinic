<?php

declare(strict_types=1);

namespace App\Controllers\Tutorial;

use App\Controllers\Controller;
use App\Core\Http\Request;

final class SystemTutorialController extends Controller
{
    public function index(Request $request)
    {
        return $this->view('tutorial/system');
    }

    public function primeirosPassos(Request $request)
    {
        return $this->view('tutorial/system_primeiros_passos');
    }

    public function dashboard(Request $request)
    {
        return $this->view('tutorial/system_dashboard');
    }

    public function agenda(Request $request)
    {
        return $this->view('tutorial/system_agenda');
    }

    public function pacientes(Request $request)
    {
        return $this->view('tutorial/system_pacientes');
    }

    public function prontuarios(Request $request)
    {
        return $this->view('tutorial/system_prontuarios');
    }

    public function imagens(Request $request)
    {
        return $this->view('tutorial/system_imagens');
    }

    public function financeiro(Request $request)
    {
        return $this->view('tutorial/system_financeiro');
    }

    public function estoque(Request $request)
    {
        return $this->view('tutorial/system_estoque');
    }

    public function servicos(Request $request)
    {
        return $this->view('tutorial/system_servicos');
    }

    public function profissionais(Request $request)
    {
        return $this->view('tutorial/system_profissionais');
    }

    public function configuracoes(Request $request)
    {
        return $this->view('tutorial/system_configuracoes');
    }

    public function seguranca(Request $request)
    {
        return $this->view('tutorial/system_seguranca');
    }

    public function portalPaciente(Request $request)
    {
        return $this->view('tutorial/system_portal_paciente');
    }

    public function integracoesApi(Request $request)
    {
        return $this->view('tutorial/system_integracoes_api');
    }
}
