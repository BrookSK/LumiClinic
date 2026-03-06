<?php

declare(strict_types=1);

namespace App\Controllers\Marketing;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Repositories\MarketingCampaignMessageRepository;
use App\Repositories\MarketingCampaignMessageQueryRepository;
use App\Repositories\MarketingCampaignRepository;
use App\Repositories\MarketingSegmentRepository;
use App\Repositories\WhatsappTemplateRepository;
use App\Services\Auth\AuthService;
use App\Services\Queue\QueueService;

final class MarketingAutomationUiController extends Controller
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

    private function clinicIdOrFail(): int
    {
        $auth = new AuthService($this->container);
        $clinicId = $auth->clinicId();
        if ($clinicId === null) {
            throw new \RuntimeException('Contexto inválido.');
        }
        return (int)$clinicId;
    }

    private function buildSegmentRulesFromRequest(Request $request): array
    {
        return [
            'status' => trim((string)$request->input('rule_status', 'active')),
            'whatsapp_opt_in' => (int)$request->input('rule_whatsapp_opt_in', 1) ? 1 : 0,
            'has_phone' => (int)$request->input('rule_has_phone', 1) ? 1 : 0,
            'has_email' => (int)$request->input('rule_has_email', 0) ? 1 : 0,
        ];
    }

    private function normalizeDateTimeLocal(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Accept both "YYYY-mm-ddTHH:ii" (datetime-local) and "YYYY-mm-dd HH:ii:ss".
        if (str_contains($value, 'T')) {
            $value = str_replace('T', ' ', $value);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $value) === 1) {
            $value .= ':00';
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);
        if ($dt === false) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    public function segments(Request $request)
    {
        $this->authorize('marketing.automation.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $pdo = $this->container->get(\PDO::class);

        $repo = new MarketingSegmentRepository($pdo);
        $rows = $repo->listByClinic($clinicId, 200);

        return $this->view('marketing/automation_segments', [
            'rows' => $rows,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function segmentCreate(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $auth = new AuthService($this->container);
        $userId = $auth->userId();

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirect('/marketing/automation/segments?error=' . urlencode('Nome é obrigatório.'));
        }

        $status = trim((string)$request->input('status', 'active'));
        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $rules = $this->buildSegmentRulesFromRequest($request);

        $id = (new MarketingSegmentRepository($this->container->get(\PDO::class)))->create($clinicId, $name, $status, $rules, $userId);

        return $this->redirect('/marketing/automation/segment/edit?id=' . $id . '&success=' . urlencode('Criado.'));
    }

    public function segmentEdit(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/segments');
        }

        $repo = new MarketingSegmentRepository($this->container->get(\PDO::class));
        $row = $repo->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/marketing/automation/segments?error=' . urlencode('Segmento inválido.'));
        }

        $rules = [];
        $rulesJson = (string)($row['rules_json'] ?? '');
        $decoded = json_decode($rulesJson, true);
        if (is_array($decoded)) {
            $rules = $decoded;
        }

        $audienceCount = 0;
        try {
            $filters = $this->buildSegmentRulesFromRequest($request);
            $filters = $rules !== [] ? $rules : $filters;

            $where = [
                'clinic_id = :clinic_id',
                'deleted_at IS NULL',
            ];
            $params = ['clinic_id' => $clinicId];

            $st = isset($filters['status']) ? trim((string)$filters['status']) : 'active';
            if ($st !== '') {
                $where[] = 'status = :status';
                $params['status'] = $st;
            }

            if ((int)($filters['whatsapp_opt_in'] ?? 0) === 1) {
                $where[] = 'whatsapp_opt_in = 1';
            }

            if ((int)($filters['has_phone'] ?? 0) === 1) {
                $where[] = 'phone IS NOT NULL';
                $where[] = "TRIM(phone) <> ''";
            }

            if ((int)($filters['has_email'] ?? 0) === 1) {
                $where[] = 'email IS NOT NULL';
                $where[] = "TRIM(email) <> ''";
            }

            $sql = "SELECT COUNT(*) AS c FROM patients WHERE " . implode(' AND ', $where);
            $stmt = $this->container->get(\PDO::class)->prepare($sql);
            $stmt->execute($params);
            $r = $stmt->fetch();
            $audienceCount = (int)($r['c'] ?? 0);
        } catch (\Throwable $e) {
            $audienceCount = 0;
        }

        return $this->view('marketing/automation_segment_edit', [
            'row' => $row,
            'rules' => $rules,
            'audience_count' => $audienceCount,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function segmentUpdate(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/segments');
        }

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirect('/marketing/automation/segment/edit?id=' . $id . '&error=' . urlencode('Nome é obrigatório.'));
        }

        $status = trim((string)$request->input('status', 'active'));
        if (!in_array($status, ['active', 'disabled'], true)) {
            $status = 'active';
        }

        $rules = $this->buildSegmentRulesFromRequest($request);

        (new MarketingSegmentRepository($this->container->get(\PDO::class)))->update($clinicId, $id, $name, $status, $rules);

        return $this->redirect('/marketing/automation/segment/edit?id=' . $id . '&success=' . urlencode('Salvo.'));
    }

    public function campaigns(Request $request)
    {
        $this->authorize('marketing.automation.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $pdo = $this->container->get(\PDO::class);

        $rows = (new MarketingCampaignRepository($pdo))->listByClinic($clinicId, 200);
        $segments = (new MarketingSegmentRepository($pdo))->listByClinic($clinicId, 200);

        return $this->view('marketing/automation_campaigns', [
            'rows' => $rows,
            'segments' => $segments,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function campaignCreate(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $auth = new AuthService($this->container);
        $userId = $auth->userId();

        $name = trim((string)$request->input('name', ''));
        $channel = trim((string)$request->input('channel', 'whatsapp'));
        if (!in_array($channel, ['whatsapp', 'email'], true)) {
            $channel = 'whatsapp';
        }

        if ($name === '') {
            return $this->redirect('/marketing/automation/campaigns?error=' . urlencode('Nome é obrigatório.'));
        }

        $segmentId = (int)$request->input('segment_id', 0);
        $segmentId = $segmentId > 0 ? $segmentId : null;

        $whatsappTemplateCode = trim((string)$request->input('whatsapp_template_code', ''));
        $emailSubject = trim((string)$request->input('email_subject', ''));
        $emailBody = (string)$request->input('email_body', '');
        $clickUrl = trim((string)$request->input('click_url', ''));

        $status = trim((string)$request->input('status', 'draft'));
        if (!in_array($status, ['draft', 'scheduled', 'running', 'paused', 'completed', 'cancelled'], true)) {
            $status = 'draft';
        }

        $scheduledFor = $this->normalizeDateTimeLocal((string)$request->input('scheduled_for', ''));

        $triggerEvent = trim((string)$request->input('trigger_event', ''));
        $triggerEvent = $triggerEvent === '' ? null : $triggerEvent;
        $triggerDelay = (int)$request->input('trigger_delay_minutes', 0);
        $triggerDelay = $triggerDelay > 0 ? $triggerDelay : null;

        $repo = new MarketingCampaignRepository($this->container->get(\PDO::class));
        $id = $repo->create(
            $clinicId,
            $name,
            $channel,
            $segmentId,
            ($whatsappTemplateCode === '' ? null : $whatsappTemplateCode),
            ($emailSubject === '' ? null : $emailSubject),
            ($emailBody === '' ? null : $emailBody),
            ($clickUrl === '' ? null : $clickUrl),
            $status,
            $scheduledFor,
            $userId
        );

        if ($triggerEvent !== null) {
            $this->container->get(\PDO::class)->prepare(
                "UPDATE marketing_campaigns SET trigger_event = :e, trigger_delay_minutes = :d WHERE clinic_id = :clinic_id AND id = :id LIMIT 1"
            )->execute([
                'e' => $triggerEvent,
                'd' => $triggerDelay,
                'clinic_id' => $clinicId,
                'id' => $id,
            ]);
        }

        return $this->redirect('/marketing/automation/campaign/edit?id=' . $id . '&success=' . urlencode('Criada.'));
    }

    public function campaignEdit(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/campaigns');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MarketingCampaignRepository($pdo);
        $row = $repo->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/marketing/automation/campaigns?error=' . urlencode('Campanha inválida.'));
        }

        $segments = (new MarketingSegmentRepository($pdo))->listByClinic($clinicId, 200);
        $templates = (new WhatsappTemplateRepository($pdo))->listByClinic($clinicId);

        return $this->view('marketing/automation_campaign_edit', [
            'row' => $row,
            'segments' => $segments,
            'templates' => $templates,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function campaignUpdate(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/campaigns');
        }

        $pdo = $this->container->get(\PDO::class);

        $row = (new MarketingCampaignRepository($pdo))->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/marketing/automation/campaigns?error=' . urlencode('Campanha inválida.'));
        }

        $name = trim((string)$request->input('name', ''));
        if ($name === '') {
            return $this->redirect('/marketing/automation/campaign/edit?id=' . $id . '&error=' . urlencode('Nome é obrigatório.'));
        }

        $channel = trim((string)$request->input('channel', 'whatsapp'));
        if (!in_array($channel, ['whatsapp', 'email'], true)) {
            $channel = 'whatsapp';
        }

        $segmentId = (int)$request->input('segment_id', 0);
        $segmentId = $segmentId > 0 ? $segmentId : null;

        $whatsappTemplateCode = trim((string)$request->input('whatsapp_template_code', ''));
        $emailSubject = trim((string)$request->input('email_subject', ''));
        $emailBody = (string)$request->input('email_body', '');
        $clickUrl = trim((string)$request->input('click_url', ''));

        $status = trim((string)$request->input('status', 'draft'));
        if (!in_array($status, ['draft', 'scheduled', 'running', 'paused', 'completed', 'cancelled'], true)) {
            $status = 'draft';
        }

        $scheduledFor = $this->normalizeDateTimeLocal((string)$request->input('scheduled_for', ''));

        $triggerEvent = trim((string)$request->input('trigger_event', ''));
        $triggerEvent = $triggerEvent === '' ? null : $triggerEvent;
        $triggerDelay = (int)$request->input('trigger_delay_minutes', 0);
        $triggerDelay = $triggerDelay > 0 ? $triggerDelay : null;

        $sql = "
            UPDATE marketing_campaigns
               SET name = :name,
                   channel = :channel,
                   segment_id = :segment_id,
                   whatsapp_template_code = :whatsapp_template_code,
                   email_subject = :email_subject,
                   email_body = :email_body,
                   click_url = :click_url,
                   status = :status,
                   scheduled_for = :scheduled_for,
                   trigger_event = :trigger_event,
                   trigger_delay_minutes = :trigger_delay_minutes,
                   updated_at = NOW()
             WHERE clinic_id = :clinic_id
               AND id = :id
               AND deleted_at IS NULL
             LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'clinic_id' => $clinicId,
            'id' => $id,
            'name' => $name,
            'channel' => $channel,
            'segment_id' => $segmentId,
            'whatsapp_template_code' => ($whatsappTemplateCode === '' ? null : $whatsappTemplateCode),
            'email_subject' => ($emailSubject === '' ? null : $emailSubject),
            'email_body' => ($emailBody === '' ? null : $emailBody),
            'click_url' => ($clickUrl === '' ? null : $clickUrl),
            'status' => $status,
            'scheduled_for' => $scheduledFor,
            'trigger_event' => $triggerEvent,
            'trigger_delay_minutes' => $triggerDelay,
        ]);

        return $this->redirect('/marketing/automation/campaign/edit?id=' . $id . '&success=' . urlencode('Salva.'));
    }

    public function campaignRunNow(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/campaigns');
        }

        (new QueueService($this->container))->enqueue(
            'marketing.run_campaign',
            ['campaign_id' => $id, 'ip' => $request->ip()],
            $clinicId,
            'notifications',
            null,
            10
        );

        return $this->redirect('/marketing/automation/campaign/edit?id=' . $id . '&success=' . urlencode('Enfileirada para execução.'));
    }

    public function logs(Request $request)
    {
        $this->authorize('marketing.automation.read');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $pdo = $this->container->get(\PDO::class);

        $status = trim((string)$request->input('status', ''));
        $campaignId = (int)$request->input('campaign_id', 0);
        $q = trim((string)$request->input('q', ''));

        $filters = [
            'status' => $status,
            'campaign_id' => $campaignId,
            'q' => $q,
        ];

        $rows = (new MarketingCampaignMessageQueryRepository($pdo))->search($clinicId, $filters, 200, 0);
        $campaigns = (new MarketingCampaignRepository($pdo))->listByClinic($clinicId, 200);

        return $this->view('marketing/automation_logs', [
            'rows' => $rows,
            'campaigns' => $campaigns,
            'status' => $status,
            'campaign_id' => $campaignId,
            'q' => $q,
            'error' => trim((string)$request->input('error', '')),
            'success' => trim((string)$request->input('success', '')),
        ]);
    }

    public function logRetry(Request $request)
    {
        $this->authorize('marketing.automation.manage');

        $redirect = $this->redirectSuperAdminWithoutClinicContext();
        if ($redirect !== null) {
            return $redirect;
        }

        $clinicId = $this->clinicIdOrFail();
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            return $this->redirect('/marketing/automation/logs');
        }

        $pdo = $this->container->get(\PDO::class);
        $repo = new MarketingCampaignMessageRepository($pdo);
        $row = $repo->findById($clinicId, $id);
        if ($row === null) {
            return $this->redirect('/marketing/automation/logs?error=' . urlencode('Log inválido.'));
        }

        $pdo->prepare("UPDATE marketing_campaign_messages SET status = 'queued', error_message = NULL, updated_at = NOW() WHERE clinic_id = :clinic_id AND id = :id LIMIT 1")
            ->execute(['clinic_id' => $clinicId, 'id' => $id]);

        (new QueueService($this->container))->enqueue(
            'marketing.send_message',
            ['message_id' => $id],
            $clinicId,
            'notifications',
            null,
            10
        );

        return $this->redirect('/marketing/automation/logs?success=' . urlencode('Reenfileirado.'));
    }
}
