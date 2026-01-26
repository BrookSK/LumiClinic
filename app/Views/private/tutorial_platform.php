<?php
$title = 'Tutorial (Dono do SaaS / Super Admin)';
ob_start();
?>
<div class="lc-grid">
    <div class="lc-card">
        <div class="lc-card__title">Tutorial: Dono do SaaS / Super Admin</div>
        <div class="lc-card__body">
            <p><strong>Objetivo:</strong> este guia explica como administrar a plataforma (multi-clínicas), planos/assinaturas e recursos de sistema.</p>
            <h3>1) Acesso Super Admin</h3>
            <p>Use um usuário com permissão de Super Admin. Ao entrar, você pode operar sem contexto de clínica e/ou selecionar uma clínica.</p>
            <h3>2) Gestão de clínicas</h3>
            <p>No menu do sistema, acesse a listagem de clínicas para visualizar e selecionar o contexto correto.</p>
            <h3>3) Billing (Planos e Assinaturas)</h3>
            <p>Use o backoffice do sistema para:</p>
            <ul>
                <li>Listar clínicas e o status de assinatura</li>
                <li>Trocar plano/status/gateway</li>
                <li>Forçar sincronização de assinatura no gateway</li>
            </ul>
            <h3>4) Observabilidade</h3>
            <p>Recursos disponíveis:</p>
            <ul>
                <li>Dashboards JSON: <code>/dashboard/platform</code> e <code>/dashboard/system-health</code></li>
                <li>Export CSV: <code>/reports/performance.csv</code></li>
                <li>Jobs: <code>metrics.*</code>, <code>alerts.evaluate</code>, <code>observability.purge</code></li>
            </ul>
            <h3>5) Alertas</h3>
            <p>Cadastre regras em <code>alert_rules</code> e execute o job <code>alerts.evaluate</code>. Alertas disparam eventos <code>alert.triggered</code>.</p>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
