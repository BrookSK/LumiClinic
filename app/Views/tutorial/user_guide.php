<?php
$title = 'Guia Completo do Usuário';
ob_start();
?>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
    <div>
        <div style="font-weight:850;font-size:20px;color:rgba(31,41,55,.94);">📖 Guia Completo do Usuário</div>
        <div style="font-size:13px;color:rgba(31,41,55,.45);margin-top:2px;">Passo a passo de todas as funções do sistema, organizado por módulos.</div>
    </div>
    <div style="display:flex;gap:8px;">
        <a class="lc-btn lc-btn--primary" href="/tutorial/guia-completo/export.pdf" target="_blank">📄 Baixar PDF</a>
        <a class="lc-btn lc-btn--secondary" href="/tutorial/sistema">Tutoriais interativos</a>
    </div>
</div>

<style>
.ug-toc{padding:20px;border-radius:14px;border:1px solid rgba(238,184,16,.15);background:rgba(253,229,159,.06);margin-bottom:20px}
.ug-toc__title{font-weight:800;font-size:14px;color:#815901;margin-bottom:10px}
.ug-toc ol{margin:0;padding-left:20px;font-size:13px;line-height:2.2;color:rgba(31,41,55,.75)}
.ug-toc a{color:#815901;text-decoration:none;font-weight:600}
.ug-toc a:hover{text-decoration:underline}
.ug-section{padding:22px;border-radius:14px;border:1px solid rgba(17,24,39,.06);background:var(--lc-surface);box-shadow:0 2px 10px rgba(17,24,39,.03);margin-bottom:16px}
.ug-section h2{font-weight:850;font-size:17px;color:#815901;margin:0 0 6px;display:flex;align-items:center;gap:8px}
.ug-section h3{font-weight:700;font-size:14px;color:rgba(31,41,55,.80);margin:16px 0 6px}
.ug-section p{font-size:13px;color:rgba(31,41,55,.65);line-height:1.7;margin:4px 0 8px}
.ug-section ol,.ug-section ul{font-size:13px;color:rgba(31,41,55,.65);line-height:1.8;padding-left:20px;margin:4px 0 10px}
.ug-tip{padding:10px 14px;border-radius:10px;background:rgba(253,229,159,.15);border:1px solid rgba(238,184,16,.2);font-size:12px;color:#92400e;margin:10px 0;line-height:1.5}
</style>

<!-- Índice -->
<div class="ug-toc">
    <div class="ug-toc__title">Índice</div>
    <ol>
        <li><a href="#s1">Primeiros Passos</a></li>
        <li><a href="#s2">Dashboard</a></li>
        <li><a href="#s3">Agenda</a></li>
        <li><a href="#s4">Pacientes</a></li>
        <li><a href="#s5">Prontuários</a></li>
        <li><a href="#s6">Imagens Clínicas</a></li>
        <li><a href="#s7">Orçamentos e Financeiro</a></li>
        <li><a href="#s8">Estoque</a></li>
        <li><a href="#s9">Prescrições</a></li>
        <li><a href="#s10">Anamnese</a></li>
        <li><a href="#s11">Marketing</a></li>
        <li><a href="#s12">Configurações</a></li>
        <li><a href="#s13">Portal do Paciente</a></li>
    </ol>
</div>

<!-- 1 -->
<div class="ug-section" id="s1">
    <h2>1. Primeiros Passos</h2>
    <h3>Como acessar o sistema</h3>
    <ol>
        <li>Abra o navegador (Chrome, Edge ou Firefox).</li>
        <li>Digite o endereço do sistema fornecido pela sua clínica.</li>
        <li>Na tela de login, digite seu <strong>e-mail</strong> e <strong>senha</strong>.</li>
        <li>Clique em <strong>Entrar</strong>.</li>
    </ol>
    <div class="ug-tip">💡 Se esqueceu a senha, clique em "Esqueci minha senha" na tela de login. Você receberá um e-mail para redefinir.</div>
    <h3>Navegação</h3>
    <p>O menu fica no lado esquerdo da tela. Clique em qualquer item para abrir a seção. Os itens com seta (▸) têm sub-menus — clique para expandir.</p>
</div>

<!-- 2 -->
<div class="ug-section" id="s2">
    <h2>2. Dashboard</h2>
    <p>O Dashboard é a primeira tela que aparece ao entrar. Mostra um resumo do dia:</p>
    <ul>
        <li><strong>Atendimentos hoje</strong> — Quantas consultas estão marcadas, confirmadas, em andamento ou concluídas.</li>
        <li><strong>Pacientes do dia</strong> — Quantos pacientes diferentes têm consulta hoje.</li>
        <li><strong>Receita do mês</strong> — Quanto a clínica já recebeu no mês atual.</li>
        <li><strong>Alertas de estoque</strong> — Materiais acabando, zerados ou vencendo.</li>
    </ul>
    <div class="ug-tip">💡 Clique nos links dentro de cada card para ir direto à seção correspondente.</div>
</div>

<!-- 3 -->
<div class="ug-section" id="s3">
    <h2>3. Agenda</h2>
    <h3>Como agendar uma consulta</h3>
    <ol>
        <li>No menu, clique em <strong>Agenda → Agenda</strong>.</li>
        <li>Escolha a visualização: <strong>Dia</strong>, <strong>Semana</strong> ou <strong>Mês</strong>.</li>
        <li>Clique no botão <strong>+ Novo agendamento</strong>.</li>
        <li>Preencha: paciente, serviço, profissional, data e horário.</li>
        <li>Clique em <strong>Agendar</strong>.</li>
    </ol>
    <h3>Status dos agendamentos</h3>
    <ul>
        <li><strong>Pendente</strong> — Agendado, aguardando confirmação.</li>
        <li><strong>Confirmado</strong> — O paciente confirmou presença.</li>
        <li><strong>Em andamento</strong> — O atendimento começou.</li>
        <li><strong>Concluído</strong> — Atendimento finalizado.</li>
        <li><strong>Cancelado</strong> — A consulta foi cancelada.</li>
    </ul>
    <h3>Fila de chegada</h3>
    <p>Em <strong>Agenda → Fila de chegada</strong>, controle a ordem de atendimento dos pacientes que já chegaram.</p>
    <h3>Operação da Agenda</h3>
    <p>Em <strong>Agenda → Operação da Agenda</strong>, veja todos os agendamentos do dia com filtros por profissional e status.</p>
</div>

<!-- 4 -->
<div class="ug-section" id="s4">
    <h2>4. Pacientes</h2>
    <h3>Como cadastrar um paciente</h3>
    <ol>
        <li>No menu, clique em <strong>Pacientes → Pacientes</strong>.</li>
        <li>Clique em <strong>+ Novo paciente</strong>.</li>
        <li>Preencha: nome (obrigatório), e-mail, telefone, data de nascimento, CPF e endereço.</li>
        <li>Se quiser criar acesso ao Portal, clique em <strong>Gerar senha</strong>.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
    <h3>Buscar paciente</h3>
    <p>Use a barra de busca no topo. Busque por <strong>nome</strong>, <strong>e-mail</strong> ou <strong>telefone</strong>.</p>
    <h3>Ficha do paciente</h3>
    <p>Ao clicar em um paciente, veja a ficha completa com botões para: Agendar, Prontuário, Imagens, Orçamentos, Timeline e Documentos.</p>
</div>

<!-- 5 -->
<div class="ug-section" id="s5">
    <h2>5. Prontuários</h2>
    <h3>Como criar um prontuário</h3>
    <ol>
        <li>Na ficha do paciente, clique em <strong>Prontuário</strong>.</li>
        <li>Clique em <strong>+ Novo prontuário</strong>.</li>
        <li>Escolha um modelo (se houver) ou escreva livremente.</li>
        <li>Preencha as informações do atendimento.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
    <div class="ug-tip">💡 Você pode gravar áudio durante o atendimento. O sistema transcreve automaticamente usando inteligência artificial.</div>
</div>

<!-- 6 -->
<div class="ug-section" id="s6">
    <h2>6. Imagens Clínicas</h2>
    <h3>Como enviar uma imagem</h3>
    <ol>
        <li>Na ficha do paciente, clique em <strong>Imagens</strong>.</li>
        <li>Escolha o tipo (Foto, Acompanhamento, etc.).</li>
        <li>Selecione o arquivo e clique em <strong>Enviar</strong>.</li>
    </ol>
    <h3>Comparação Antes/Depois</h3>
    <p>Envie um par de imagens para comparação lado a lado usando a seção "Comparação Antes / Depois".</p>
    <h3>Marcações</h3>
    <p>Clique em uma imagem e depois em <strong>Marcações</strong>. Desenhe retângulos, círculos, setas, traços livres e adicione textos sobre a imagem.</p>
</div>

<!-- 7 -->
<div class="ug-section" id="s7">
    <h2>7. Orçamentos e Financeiro</h2>
    <h3>Como criar um orçamento</h3>
    <ol>
        <li>Vá em <strong>Financeiro → Vendas/Orçamentos</strong>.</li>
        <li>Clique em <strong>+ Novo orçamento</strong>.</li>
        <li>Busque e selecione o paciente.</li>
        <li>Clique em <strong>Criar orçamento</strong>.</li>
        <li>Adicione os itens (serviços) e valores.</li>
    </ol>
    <div class="ug-tip">💡 Você também pode criar um orçamento direto da ficha do paciente clicando em "Orçamentos".</div>
    <h3>Pagamentos</h3>
    <p>Dentro do orçamento, registre pagamentos (parcelas). O sistema controla quanto já foi pago e quanto falta.</p>
    <h3>Contas a Pagar</h3>
    <p>Em <strong>Financeiro → Contas a Pagar</strong>, gerencie despesas da clínica.</p>
    <h3>Relatórios</h3>
    <p>Em <strong>Relatórios → Financeiro</strong>, veja receitas, despesas e fluxo de caixa. Exporte em PDF ou planilha.</p>
</div>

<!-- 8 -->
<div class="ug-section" id="s8">
    <h2>8. Estoque</h2>
    <h3>Como cadastrar um material</h3>
    <ol>
        <li>Vá em <strong>Estoque → Materiais</strong>.</li>
        <li>Clique em <strong>+ Novo material</strong>.</li>
        <li>Preencha: nome, unidade de medida, estoque mínimo e valor.</li>
        <li>Clique em <strong>Salvar</strong>.</li>
    </ol>
    <h3>Alertas</h3>
    <p>Em <strong>Estoque → Alertas</strong>, veja materiais com estoque baixo, zerado, vencendo ou vencido.</p>
</div>

<!-- 9 -->
<div class="ug-section" id="s9">
    <h2>9. Prescrições</h2>
    <ol>
        <li>Na ficha do paciente, clique em <strong>Prescrições</strong>.</li>
        <li>Clique em <strong>+ Nova prescrição</strong>.</li>
        <li>Preencha medicamentos, posologia e orientações.</li>
        <li>Clique em <strong>Salvar</strong> e depois em <strong>Imprimir</strong>.</li>
    </ol>
</div>

<!-- 10 -->
<div class="ug-section" id="s10">
    <h2>10. Anamnese</h2>
    <p>A anamnese é um questionário de saúde que o paciente preenche antes da consulta.</p>
    <h3>Criar modelo</h3>
    <ol>
        <li>Vá em <strong>Configurações → Anamnese</strong>.</li>
        <li>Clique em <strong>+ Novo modelo</strong>.</li>
        <li>Dê um nome e adicione as perguntas.</li>
        <li>Salve.</li>
    </ol>
    <h3>Enviar ao paciente</h3>
    <p>Ao confirmar um agendamento, o sistema pode enviar a anamnese automaticamente por WhatsApp ou e-mail. O paciente preenche pelo link ou pelo Portal.</p>
</div>

<!-- 11 -->
<div class="ug-section" id="s11">
    <h2>11. Marketing</h2>
    <h3>Calendário</h3>
    <p>Em <strong>Marketing → Calendário</strong>, planeje ações de marketing por mês.</p>
    <h3>Campanhas automáticas</h3>
    <ol>
        <li>Crie um <strong>Segmento</strong> (grupo de pacientes).</li>
        <li>Crie uma <strong>Campanha</strong> vinculada ao segmento.</li>
        <li>Escolha o template de WhatsApp e o gatilho.</li>
    </ol>
</div>

<!-- 12 -->
<div class="ug-section" id="s12">
    <h2>12. Configurações</h2>
    <ul>
        <li><strong>Geral</strong> — WhatsApp, IA, terminologia e configurações operacionais.</li>
        <li><strong>Clínica</strong> — Dados da clínica, horários e feriados.</li>
        <li><strong>Usuários</strong> — Cadastro de funcionários e controle de permissões.</li>
        <li><strong>Serviços</strong> — Serviços oferecidos e vínculo com estoque.</li>
        <li><strong>Procedimentos</strong> — Procedimentos com contraindicações e orientações.</li>
        <li><strong>Documentos</strong> — Termos de consentimento e documentos legais.</li>
        <li><strong>Modelos de prontuário</strong> — Templates pré-formatados.</li>
        <li><strong>Anamnese</strong> — Modelos de questionário.</li>
    </ul>
</div>

<!-- 13 -->
<div class="ug-section" id="s13">
    <h2>13. Portal do Paciente</h2>
    <p>Área exclusiva onde seus pacientes acessam informações de forma autônoma.</p>
    <h3>O que o paciente pode fazer</h3>
    <ul>
        <li>Ver consultas e confirmar presença.</li>
        <li>Preencher anamnese.</li>
        <li>Ver documentos da clínica.</li>
        <li>Enviar fotos.</li>
        <li>Receber notificações.</li>
        <li>Assinar termos.</li>
    </ul>
    <h3>Como ativar para um paciente</h3>
    <ol>
        <li>Ao cadastrar, clique em <strong>Gerar senha</strong> na seção "Acesso ao Portal".</li>
        <li>O sistema envia e-mail com as credenciais.</li>
        <li>Para pacientes já cadastrados: ficha do paciente → <strong>Portal de Acesso</strong>.</li>
    </ol>
</div>

<?php
$content = (string)ob_get_clean();
require dirname(__DIR__) . '/layout/app.php';
