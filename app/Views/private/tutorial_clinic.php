<?php
$title = 'Tutorial (Dono da Clínica)';
ob_start();
?>
<div class="lc-grid">
    <div class="lc-card">
        <div class="lc-card__title">Tutorial: Dono da Clínica</div>
        <div class="lc-card__body">
            <p><strong>Objetivo:</strong> este guia explica o uso do sistema no dia a dia da clínica (agenda, pacientes, financeiro, relatórios).</p>
            <h3>1) Login e permissões</h3>
            <p>Entre com seu usuário. Seu acesso é definido por permissões e papéis (ex.: admin, receptionist, professional).</p>
            <h3>2) Agenda (Agendamentos)</h3>
            <ul>
                <li>Criar agendamento</li>
                <li>Reagendar e cancelar</li>
                <li>Atualizar status (confirmado, em atendimento, concluído, no-show, etc.)</li>
            </ul>
            <h3>3) Pacientes</h3>
            <p>Cadastre e consulte pacientes. Use busca e histórico quando aplicável.</p>
            <h3>4) Financeiro (Vendas/Pagamentos)</h3>
            <ul>
                <li>Criar venda</li>
                <li>Adicionar itens (serviços/pacotes/planos)</li>
                <li>Lançar pagamentos e estornos</li>
            </ul>
            <h3>5) Métricas e relatórios</h3>
            <ul>
                <li>Dashboard JSON: <code>/dashboard/clinic</code></li>
                <li>Export CSV: <code>/reports/metrics.csv</code></li>
            </ul>
        </div>
    </div>
</div>
<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
