<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\SupportDevPhoneRepository;
use App\Repositories\SupportTicketNotificationRepository;
use App\Services\Support\SupportWhatsappService;

/**
 * Tela do super admin: conexão por QR Code da instância de WhatsApp do suporte e números dos desenvolvedores.
 * Usa a Evolution API global (/sys/settings/whatsapp); nunca credenciais de clínica.
 */
final class SystemSupportWhatsappController extends Controller
{
    public const MAX_PHONES = 20;

    private function ensureSuperAdmin(): void
    {
        if (!isset($_SESSION['is_super_admin']) || (int)$_SESSION['is_super_admin'] !== 1) {
            throw new \RuntimeException('Acesso negado.');
        }
    }

    private function phones(): SupportDevPhoneRepository
    {
        return new SupportDevPhoneRepository($this->container->get(\PDO::class));
    }

    private function back(string $key, string $value): string
    {
        return '/sys/support/whatsapp?' . $key . '=' . urlencode($value);
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $svc = new SupportWhatsappService($this->container);
        $pdo = $this->container->get(\PDO::class);
        $saved = trim((string)$request->input('saved', ''));
        $error = trim((string)$request->input('error', ''));

        $editId = (int)$request->input('edit', 0);

        return $this->view('system/support/whatsapp', [
            'global_configured' => $svc->isGlobalConfigured(),
            'instance' => $svc->instanceName(),
            'phones' => $this->phones()->listAll(),
            'edit_phone' => $editId > 0 ? $this->phones()->findById($editId) : null,
            'max_phones' => self::MAX_PHONES,
            'deliveries' => (new SupportTicketNotificationRepository($pdo))->listLatest(30),
            'success' => $saved !== '' ? $saved : null,
            'error' => $error !== '' ? $error : null,
        ]);
    }

    /** GET JSON: estado atual da conexão (usado no polling a cada 3 s). */
    public function statusJson(Request $request): Response
    {
        $this->ensureSuperAdmin();

        $st = (new SupportWhatsappService($this->container))->status();

        return Response::json([
            'ok' => $st['error'] === null,
            'configured' => $st['configured'],
            'connected' => $st['connected'],
            'state' => $st['state'],
            'label' => $st['label'],
            'error' => $st['error'],
        ]);
    }

    /** POST JSON (X-CSRF-Token): gera o QR Code. */
    public function connect(Request $request): Response
    {
        $this->ensureSuperAdmin();

        $svc = new SupportWhatsappService($this->container);
        if (!$svc->isGlobalConfigured()) {
            return Response::json(['ok' => false, 'error' => 'Configure a Evolution API global em /sys/settings/whatsapp'], 400);
        }

        try {
            $qr = $svc->connect();
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 400)], 502);
        }

        return Response::json(['ok' => true, 'image' => $qr['image'], 'code' => $qr['code'], 'pairing' => $qr['pairing']]);
    }

    /** POST JSON (X-CSRF-Token): desconecta a sessão do WhatsApp do suporte. */
    public function disconnect(Request $request): Response
    {
        $this->ensureSuperAdmin();

        try {
            (new SupportWhatsappService($this->container))->disconnect();
        } catch (\Throwable $e) {
            return Response::json(['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 400)], 502);
        }

        return Response::json(['ok' => true]);
    }

    public function phoneSave(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $name = mb_substr(trim((string)$request->input('name', '')), 0, 120);
        $phone = SupportWhatsappService::normalizePhone((string)$request->input('phone', ''));

        if ($name === '') {
            return $this->redirect($this->back('error', 'Informe o nome do desenvolvedor.'));
        }
        if ($phone === null) {
            return $this->redirect($this->back('error', 'Telefone inválido. Informe DDD e número (ex.: 11 91234-5678).'));
        }

        $repo = $this->phones();
        $all = $repo->listAll();
        foreach ($all as $row) {
            if ((string)$row['phone'] === $phone && (int)$row['id'] !== $id) {
                return $this->redirect($this->back('error', 'Este número já está cadastrado.'));
            }
        }

        try {
            if ($id > 0) {
                if ($repo->findById($id) === null) {
                    return $this->redirect($this->back('error', 'Número não encontrado.'));
                }
                $repo->update($id, $name, $phone);
            } else {
                if (count($all) >= self::MAX_PHONES) {
                    return $this->redirect($this->back('error', 'Limite de ' . self::MAX_PHONES . ' números atingido.'));
                }
                $repo->create($name, $phone);
            }
        } catch (\PDOException $e) {
            return $this->redirect($this->back('error', 'Não foi possível salvar o número.'));
        }

        return $this->redirect($this->back('saved', 'Número salvo.'));
    }

    public function phoneDelete(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        if ($id > 0) {
            $this->phones()->delete($id);
        }

        return $this->redirect($this->back('saved', 'Número removido.'));
    }

    public function phoneToggle(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $row = $id > 0 ? $this->phones()->findById($id) : null;
        if ($row === null) {
            return $this->redirect($this->back('error', 'Número não encontrado.'));
        }
        $this->phones()->setActive($id, (int)$row['is_active'] !== 1);

        return $this->redirect($this->back('saved', 'Situação do número atualizada.'));
    }

    /** Envia uma mensagem curta de teste para um número cadastrado. */
    public function test(Request $request)
    {
        $this->ensureSuperAdmin();

        $id = (int)$request->input('id', 0);
        $row = $id > 0 ? $this->phones()->findById($id) : null;
        if ($row === null) {
            return $this->redirect($this->back('error', 'Número não encontrado.'));
        }

        try {
            (new SupportWhatsappService($this->container))->sendToNumber(
                (string)$row['phone'],
                "✅ *LumiClinic Suporte*\nMensagem de teste. Você receberá aqui os novos chamados de suporte.",
                15
            );
        } catch (\Throwable $e) {
            return $this->redirect($this->back('error', 'Falha no teste: ' . mb_substr($e->getMessage(), 0, 250)));
        }

        return $this->redirect($this->back('saved', 'Mensagem de teste enviada.'));
    }
}
