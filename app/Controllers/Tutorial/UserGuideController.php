<?php

declare(strict_types=1);

namespace App\Controllers\Tutorial;

use App\Controllers\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;

final class UserGuideController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('tutorial/user_guide', []);
    }

    public function exportPdf(Request $request): Response
    {
        $dompdfClass = 'Dompdf\\Dompdf';
        if (!class_exists($dompdfClass)) {
            return Response::html('PDF indisponível. Instale dompdf/dompdf via Composer.', 501);
        }

        @ini_set('memory_limit', '512M');

        $html = $this->buildPdfHtml();

        /** @var object $dompdf */
        $dompdf = new $dompdfClass();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        return Response::raw((string)$pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Guia_LumiClinic_' . date('Ymd') . '.pdf"',
        ]);
    }

    private function buildPdfHtml(): string
    {
        $css = '
body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#333;margin:0;padding:30px 35px;line-height:1.6}
h1{font-size:20px;color:#815901;border-bottom:3px solid #eeb810;padding-bottom:8px;margin:30px 0 12px}
h2{font-size:15px;color:#815901;margin:22px 0 8px;border-left:4px solid #eeb810;padding-left:10px}
h3{font-size:12px;color:#4b5563;margin:14px 0 6px;font-weight:bold}
p{margin:4px 0 8px}
ol,ul{margin:4px 0 10px;padding-left:22px}
li{margin-bottom:4px}
.cover{text-align:center;padding:80px 0 40px}
.cover-title{font-size:28px;font-weight:bold;color:#815901;margin-bottom:6px}
.cover-sub{font-size:14px;color:#6b7280;margin-bottom:30px}
.cover-line{width:120px;height:3px;background:#eeb810;margin:0 auto 20px}
.tip{background:#fffbeb;border:1px solid #fde68a;padding:8px 12px;margin:8px 0;font-size:10px;color:#92400e}
.note{background:#f0f9ff;border:1px solid #93c5fd;padding:8px 12px;margin:8px 0;font-size:10px;color:#1e40af}
.footer{text-align:center;font-size:9px;color:#9ca3af;margin-top:30px;border-top:1px solid #e5e7eb;padding-top:8px}
.page-break{page-break-before:always}
';

        $h = '<!doctype html><html><head><meta charset="utf-8"/><style>' . $css . '</style></head><body>';

        // Cover
        $h .= '<div class="cover">';
        $h .= '<div class="cover-title">LumiClinic</div>';
        $h .= '<div class="cover-line"></div>';
        $h .= '<div class="cover-sub">Guia Completo do Usuário</div>';
        $h .= '<div style="font-size:12px;color:#9ca3af;margin-top:40px;">Versão 1.0 · ' . date('d/m/Y') . '</div>';
        $h .= '</div>';

        // TOC
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>Índice</h1>';
        $h .= '<ol style="font-size:12px;line-height:2.2">';
        $h .= '<li>Primeiros Passos</li>';
        $h .= '<li>Dashboard</li>';
        $h .= '<li>Agenda</li>';
        $h .= '<li>Pacientes</li>';
        $h .= '<li>Prontuários</li>';
        $h .= '<li>Imagens Clínicas</li>';
        $h .= '<li>Orçamentos e Financeiro</li>';
        $h .= '<li>Estoque</li>';
        $h .= '<li>Prescrições</li>';
        $h .= '<li>Anamnese</li>';
        $h .= '<li>Marketing</li>';
        $h .= '<li>Configurações</li>';
        $h .= '<li>Portal do Paciente</li>';
        $h .= '</ol>';

        // 1. Primeiros Passos
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>1. Primeiros Passos</h1>';
        $h .= '<p>Bem-vindo ao LumiClinic! Este guia vai te ajudar a usar todas as funções do sistema de forma simples e rápida.</p>';
        $h .= '<h2>Como acessar o sistema</h2>';
        $h .= '<ol>';
        $h .= '<li>Abra o navegador (Chrome, Edge ou Firefox).</li>';
        $h .= '<li>Digite o endereço do sistema fornecido pela sua clínica.</li>';
        $h .= '<li>Na tela de login, digite seu <strong>e-mail</strong> e <strong>senha</strong>.</li>';
        $h .= '<li>Clique em <strong>Entrar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<div class="tip">💡 Se esqueceu a senha, clique em "Esqueci minha senha" na tela de login.</div>';
        $h .= '<h2>Navegação principal</h2>';
        $h .= '<p>O menu fica no lado esquerdo da tela. Clique em qualquer item para abrir a seção. Os itens com seta (▸) têm sub-menus — clique para expandir.</p>';

        // 2. Dashboard
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>2. Dashboard</h1>';
        $h .= '<p>O Dashboard é a primeira tela que aparece ao entrar no sistema. Ele mostra um resumo do dia.</p>';
        $h .= '<h2>O que você vê no Dashboard</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Atendimentos hoje</strong> — Quantas consultas estão marcadas para hoje, quantas foram confirmadas, estão em andamento ou já foram concluídas.</li>';
        $h .= '<li><strong>Pacientes do dia</strong> — Quantos pacientes diferentes têm consulta hoje.</li>';
        $h .= '<li><strong>Receita do mês</strong> — Quanto a clínica já recebeu (pagamentos confirmados) no mês atual.</li>';
        $h .= '<li><strong>Alertas de estoque</strong> — Se algum material está acabando, zerado ou vencendo.</li>';
        $h .= '</ul>';
        $h .= '<div class="tip">💡 Clique nos links dentro de cada card para ir direto à seção correspondente.</div>';

        // 3. Agenda
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>3. Agenda</h1>';
        $h .= '<p>A agenda é onde você gerencia todos os agendamentos da clínica.</p>';
        $h .= '<h2>Como agendar uma consulta</h2>';
        $h .= '<ol>';
        $h .= '<li>No menu, clique em <strong>Agenda → Agenda</strong>.</li>';
        $h .= '<li>Escolha a visualização: <strong>Dia</strong>, <strong>Semana</strong> ou <strong>Mês</strong>.</li>';
        $h .= '<li>Clique no botão <strong>+ Novo agendamento</strong>.</li>';
        $h .= '<li>Preencha: paciente, serviço, profissional, data e horário.</li>';
        $h .= '<li>Clique em <strong>Agendar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<h2>Status dos agendamentos</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Pendente</strong> — Agendado, aguardando confirmação.</li>';
        $h .= '<li><strong>Confirmado</strong> — O paciente confirmou presença.</li>';
        $h .= '<li><strong>Em andamento</strong> — O atendimento começou.</li>';
        $h .= '<li><strong>Concluído</strong> — Atendimento finalizado.</li>';
        $h .= '<li><strong>Cancelado</strong> — A consulta foi cancelada.</li>';
        $h .= '</ul>';
        $h .= '<h2>Fila de chegada</h2>';
        $h .= '<p>Em <strong>Agenda → Fila de chegada</strong>, você controla a ordem de atendimento dos pacientes que já chegaram na clínica.</p>';
        $h .= '<h2>Operação da Agenda</h2>';
        $h .= '<p>Em <strong>Agenda → Operação da Agenda</strong>, você tem uma visão geral de todos os agendamentos do dia com filtros por profissional e status.</p>';

        // 4. Pacientes
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>4. Pacientes</h1>';
        $h .= '<h2>Como cadastrar um paciente</h2>';
        $h .= '<ol>';
        $h .= '<li>No menu, clique em <strong>Pacientes → Pacientes</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Novo paciente</strong>.</li>';
        $h .= '<li>Preencha os dados: nome (obrigatório), e-mail, telefone, data de nascimento, CPF e endereço.</li>';
        $h .= '<li>Se quiser criar acesso ao Portal do Paciente, clique em <strong>Gerar senha</strong> na seção "Acesso ao Portal".</li>';
        $h .= '<li>Clique em <strong>Salvar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<h2>Como buscar um paciente</h2>';
        $h .= '<p>Use a barra de busca no topo da tela. Você pode buscar por <strong>nome</strong>, <strong>e-mail</strong> ou <strong>telefone</strong>.</p>';
        $h .= '<h2>Ficha do paciente</h2>';
        $h .= '<p>Ao clicar em um paciente, você vê a ficha completa com botões para:</p>';
        $h .= '<ul>';
        $h .= '<li><strong>Agendar</strong> — Criar um novo agendamento.</li>';
        $h .= '<li><strong>Prontuário</strong> — Abrir prontuários.</li>';
        $h .= '<li><strong>Imagens</strong> — Ver imagens clínicas.</li>';
        $h .= '<li><strong>Orçamentos</strong> — Ver/criar orçamentos.</li>';
        $h .= '<li><strong>Timeline</strong> — Histórico completo do paciente.</li>';
        $h .= '<li><strong>Documentos</strong> — Documentos anexados.</li>';
        $h .= '</ul>';
        $h .= '<h2>Aniversariantes e Follow-up</h2>';
        $h .= '<p>Em <strong>Pacientes → Aniversariantes / Follow-up</strong>, você vê pacientes que fazem aniversário no mês e pacientes que não retornam há muito tempo.</p>';

        // 5. Prontuários
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>5. Prontuários</h1>';
        $h .= '<h2>Como criar um prontuário</h2>';
        $h .= '<ol>';
        $h .= '<li>Na ficha do paciente, clique em <strong>Prontuário</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Novo prontuário</strong>.</li>';
        $h .= '<li>Escolha um modelo (se houver) ou escreva livremente.</li>';
        $h .= '<li>Preencha as informações do atendimento.</li>';
        $h .= '<li>Clique em <strong>Salvar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<div class="tip">💡 Você pode gravar áudio durante o atendimento. O sistema transcreve automaticamente usando inteligência artificial.</div>';
        $h .= '<h2>Modelos de prontuário</h2>';
        $h .= '<p>Em <strong>Configurações → Modelos de prontuário</strong>, você pode criar modelos pré-formatados para agilizar o preenchimento.</p>';

        // 6. Imagens
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>6. Imagens Clínicas</h1>';
        $h .= '<h2>Como enviar uma imagem</h2>';
        $h .= '<ol>';
        $h .= '<li>Na ficha do paciente, clique em <strong>Imagens</strong>.</li>';
        $h .= '<li>Na seção "Enviar imagem", escolha o tipo (Foto, Acompanhamento, etc.).</li>';
        $h .= '<li>Selecione o arquivo e clique em <strong>Enviar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<h2>Comparação Antes/Depois</h2>';
        $h .= '<p>Você pode enviar um par de imagens (antes e depois) para comparação lado a lado. Use a seção "Comparação Antes / Depois" na mesma tela.</p>';
        $h .= '<h2>Marcações e anotações</h2>';
        $h .= '<p>Clique em uma imagem e depois em <strong>Marcações</strong>. Você pode desenhar retângulos, círculos, setas, traços livres e adicionar textos sobre a imagem.</p>';

        // 7. Financeiro
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>7. Orçamentos e Financeiro</h1>';
        $h .= '<h2>Como criar um orçamento</h2>';
        $h .= '<ol>';
        $h .= '<li>No menu, vá em <strong>Financeiro → Vendas/Orçamentos</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Novo orçamento</strong>.</li>';
        $h .= '<li>Busque e selecione o paciente.</li>';
        $h .= '<li>Clique em <strong>Criar orçamento</strong>.</li>';
        $h .= '<li>Na tela do orçamento, adicione os itens (serviços) e valores.</li>';
        $h .= '</ol>';
        $h .= '<div class="tip">💡 Você também pode criar um orçamento direto da ficha do paciente clicando no botão "Orçamentos".</div>';
        $h .= '<h2>Status do orçamento</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Rascunho</strong> — Ainda sendo montado.</li>';
        $h .= '<li><strong>Enviado</strong> — Enviado ao paciente para aprovação.</li>';
        $h .= '<li><strong>Aprovado</strong> — O paciente aceitou.</li>';
        $h .= '<li><strong>Em espera</strong> — Aguardando decisão.</li>';
        $h .= '<li><strong>Recusado</strong> — O paciente não aceitou.</li>';
        $h .= '<li><strong>Concluído</strong> — Tratamento finalizado.</li>';
        $h .= '</ul>';
        $h .= '<h2>Pagamentos</h2>';
        $h .= '<p>Dentro do orçamento, você pode registrar pagamentos (parcelas). O sistema controla automaticamente quanto já foi pago e quanto falta.</p>';
        $h .= '<h2>Contas a Pagar</h2>';
        $h .= '<p>Em <strong>Financeiro → Contas a Pagar</strong>, você gerencia as despesas da clínica (fornecedores, aluguel, etc.).</p>';
        $h .= '<h2>Relatórios Financeiros</h2>';
        $h .= '<p>Em <strong>Relatórios → Financeiro</strong>, você vê receitas, despesas e fluxo de caixa com filtros por período. Pode exportar em PDF ou planilha.</p>';

        // 8. Estoque
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>8. Estoque</h1>';
        $h .= '<h2>Como cadastrar um material</h2>';
        $h .= '<ol>';
        $h .= '<li>No menu, vá em <strong>Estoque → Materiais</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Novo material</strong>.</li>';
        $h .= '<li>Preencha: nome, unidade de medida, estoque mínimo e valor unitário.</li>';
        $h .= '<li>Clique em <strong>Salvar</strong>.</li>';
        $h .= '</ol>';
        $h .= '<h2>Entradas e saídas</h2>';
        $h .= '<p>Na ficha do material, registre entradas (compras) e saídas (uso). O sistema atualiza o saldo automaticamente.</p>';
        $h .= '<h2>Alertas</h2>';
        $h .= '<p>Em <strong>Estoque → Alertas</strong>, o sistema mostra materiais com estoque baixo, zerado, vencendo ou vencido.</p>';

        // 9. Prescrições
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>9. Prescrições</h1>';
        $h .= '<h2>Como criar uma prescrição</h2>';
        $h .= '<ol>';
        $h .= '<li>Na ficha do paciente, clique em <strong>Prescrições</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Nova prescrição</strong>.</li>';
        $h .= '<li>Preencha os medicamentos, posologia e orientações.</li>';
        $h .= '<li>Clique em <strong>Salvar</strong>.</li>';
        $h .= '<li>Para imprimir, clique em <strong>Imprimir</strong>.</li>';
        $h .= '</ol>';

        // 10. Anamnese
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>10. Anamnese</h1>';
        $h .= '<h2>O que é anamnese</h2>';
        $h .= '<p>A anamnese é um questionário de saúde que o paciente preenche antes da consulta. Ajuda o profissional a conhecer o histórico do paciente.</p>';
        $h .= '<h2>Como criar um modelo de anamnese</h2>';
        $h .= '<ol>';
        $h .= '<li>Vá em <strong>Configurações → Anamnese</strong>.</li>';
        $h .= '<li>Clique em <strong>+ Novo modelo</strong>.</li>';
        $h .= '<li>Dê um nome ao modelo e adicione as perguntas.</li>';
        $h .= '<li>Salve o modelo.</li>';
        $h .= '</ol>';
        $h .= '<h2>Como enviar anamnese ao paciente</h2>';
        $h .= '<p>Ao confirmar um agendamento, o sistema pode enviar automaticamente a anamnese por WhatsApp ou e-mail (se configurado no serviço). O paciente preenche pelo link ou pelo Portal do Paciente.</p>';

        // 11. Marketing
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>11. Marketing</h1>';
        $h .= '<h2>Calendário de Marketing</h2>';
        $h .= '<p>Em <strong>Marketing → Calendário</strong>, você planeja ações de marketing por mês (datas comemorativas, campanhas, etc.).</p>';
        $h .= '<h2>Campanhas automáticas</h2>';
        $h .= '<p>Em <strong>Marketing → Campanhas</strong>, você cria campanhas que enviam mensagens automáticas por WhatsApp para grupos de pacientes (ex: aniversariantes, pacientes inativos).</p>';
        $h .= '<ol>';
        $h .= '<li>Crie um <strong>Segmento</strong> (grupo de pacientes com critérios específicos).</li>';
        $h .= '<li>Crie uma <strong>Campanha</strong> vinculada ao segmento.</li>';
        $h .= '<li>Escolha o template de WhatsApp e o gatilho (manual ou automático).</li>';
        $h .= '</ol>';

        // 12. Configurações
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>12. Configurações</h1>';
        $h .= '<p>As configurações ficam no menu <strong>Configurações</strong>. Aqui você ajusta tudo sobre a clínica.</p>';
        $h .= '<h2>Configurações Gerais</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Geral</strong> — Configurações operacionais, WhatsApp, IA e terminologia.</li>';
        $h .= '<li><strong>Clínica</strong> — Dados da clínica (nome, endereço, CNPJ, logo).</li>';
        $h .= '<li><strong>Horários</strong> — Horário de funcionamento por dia da semana.</li>';
        $h .= '<li><strong>Feriados</strong> — Dias em que a clínica não funciona.</li>';
        $h .= '</ul>';
        $h .= '<h2>Usuários e Permissões</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Usuários</strong> — Cadastro de funcionários que acessam o sistema.</li>';
        $h .= '<li><strong>Papéis e Permissões</strong> — Controle o que cada tipo de usuário pode ver e fazer.</li>';
        $h .= '</ul>';
        $h .= '<h2>Serviços e Procedimentos</h2>';
        $h .= '<ul>';
        $h .= '<li><strong>Serviços</strong> — Cadastre os serviços oferecidos pela clínica (consulta, limpeza, etc.).</li>';
        $h .= '<li><strong>Procedimentos</strong> — Cadastre procedimentos com contraindicações e orientações pré/pós.</li>';
        $h .= '</ul>';
        $h .= '<h2>Documentos e Termos</h2>';
        $h .= '<p>Cadastre termos de consentimento, políticas de privacidade e outros documentos legais que os pacientes precisam assinar.</p>';

        // 13. Portal do Paciente
        $h .= '<div class="page-break"></div>';
        $h .= '<h1>13. Portal do Paciente</h1>';
        $h .= '<p>O Portal do Paciente é uma área exclusiva onde seus pacientes podem acessar informações de forma autônoma.</p>';
        $h .= '<h2>O que o paciente pode fazer no portal</h2>';
        $h .= '<ul>';
        $h .= '<li>Ver consultas agendadas e confirmar presença.</li>';
        $h .= '<li>Preencher anamnese antes da consulta.</li>';
        $h .= '<li>Ver documentos compartilhados pela clínica.</li>';
        $h .= '<li>Enviar fotos para acompanhamento.</li>';
        $h .= '<li>Receber notificações da clínica.</li>';
        $h .= '<li>Assinar termos e consentimentos.</li>';
        $h .= '</ul>';
        $h .= '<h2>Como ativar o portal para um paciente</h2>';
        $h .= '<ol>';
        $h .= '<li>Ao cadastrar um novo paciente, clique em <strong>Gerar senha</strong> na seção "Acesso ao Portal".</li>';
        $h .= '<li>O sistema cria o acesso e envia um e-mail ao paciente com as credenciais.</li>';
        $h .= '<li>Para pacientes já cadastrados, vá na ficha do paciente e clique em <strong>Portal de Acesso</strong>.</li>';
        $h .= '</ol>';

        // Footer
        $h .= '<div class="footer">LumiClinic · Guia do Usuário · Gerado em ' . date('d/m/Y') . '</div>';
        $h .= '</body></html>';

        return $h;
    }
}
