---
title: Dashboard e Painel Gestor
routes:
  - /dashboard
  - /manager/panel
---

# Dashboard e Painel Gestor

## Dashboard (tela inicial)
<!-- perm: nenhuma especifica (cada cartao depende da permissao indicada) -->
**Onde fica:** Menu > Dashboard (/ ou /dashboard). É a tela que abre logo depois do login.

O Dashboard é um resumo do dia da clínica. Ele mostra uma saudação com seu nome (Bom dia, Boa tarde ou Boa noite conforme a hora), a data por extenso e a frase "Aqui está o resumo do dia". Cada bloco só aparece se o seu papel tem a permissão correspondente, por isso o Dashboard da recepção, do profissional e do dono são diferentes.

### Cartões de indicadores (parte de cima)
| Cartão | O que mostra | Permissão | Link do cartão |
|---|---|---|---|
| Atendimentos hoje | Total de agendamentos de hoje (todos os status) e a contagem de confirmados, em andamento e concluídos | scheduling.read | Abrir agenda (/schedule) |
| Pacientes do dia | Número de pacientes diferentes com agendamento hoje (até 30) | patients.read | Ver pacientes (/patients) |
| Receita do mês | Soma do valor líquido das vendas com status pago, criadas do dia 1 do mês até hoje | finance.sales.read | Ver financeiro (/finance/sales) |
| Alertas de estoque | Quantidade total de alertas de estoque | stock.alerts.read | Ver alertas (/stock/alerts) |

### Seções de detalhe (parte de baixo)
- **Próximos atendimentos** (scheduling.read): até 10 agendamentos de hoje que ainda não aconteceram (horário igual ou posterior a agora), sem cancelados, em ordem de horário. Cada linha mostra hora, paciente, serviço e status (Confirmado, Em andamento, Concluído, Pendente). Mensagem vazia: "Nenhum atendimento agendado para hoje."
- **Pacientes do dia** (patients.read): lista de pacientes de hoje com avatar, nome, contato (telefone ou e-mail), horário do primeiro atendimento e quantidade de agendamentos. Mensagem vazia: "Nenhum paciente agendado para hoje."
- **Detalhes do estoque** (stock.alerts.read, só aparece se houver algum alerta): quatro contadores **Estoque baixo**, **Zerado**, **Vencendo** (vence em até 30 dias) e **Vencido**, com o link **Ver todos os alertas**.

### Observações
- Os números são calculados na hora que a página abre. Para atualizar, recarregue a página.
- O cartão "Atendimentos hoje" conta todos os agendamentos do dia, inclusive cancelados e faltas. Cancelados não entram em "Próximos atendimentos" nem em "Pacientes do dia".
- A receita do mês considera apenas vendas pagas; orçamentos em aberto não entram.

### Erros e dúvidas comuns
- **Um cartão não aparece**: falta a permissão daquele cartão (veja a coluna Permissão). Peça ao dono em Configurações > Usuários & Acesso > Papéis & Permissões.
- **Receita do mês zerada mas há vendas**: a venda precisa estar com status pago e criada neste mês. Veja Financeiro > Orçamentos.
- **Atendimentos de hoje não aparecem em Próximos atendimentos**: a lista mostra só os que ainda não começaram e não foram cancelados.
- **Nada aparece e surge a mensagem Selecione uma clínica**: a sessão está sem clínica ativa. Saia e entre novamente.

## Painel Gestor
<!-- perm: scheduling.read -->
**Onde fica:** /manager/panel. Não há item no menu lateral, abra pelo endereço ou por um favorito. Mostra indicadores operacionais da agenda para gestores.

**Filtros**
| Campo | Opções |
|---|---|
| Visão | Semana, Dia, Período |
| Data (base) | Data de referência da Semana ou do Dia |
| Início e Fim | Usados na visão Período |
| Profissional | Todos ou um profissional |
Clique em **Aplicar**; **Limpar** volta ao padrão (semana atual).

**Cartões**
- **Taxa de ocupação**: percentual do tempo ocupado sobre a capacidade (mostra Ocupado e Capacidade).
- **Comparecimento**: total de agendamentos e quantidade de no-show (Faltou).
- **Atendimentos concluídos**: concluídos e em andamento.
- **Agendados/Confirmados**: quantidade em cada status.

**Tabelas**
- **Ocupação por dia**: Dia, Ocupação, Capacidade, Ocupado e No-show. A capacidade usa blocos de 15 minutos dentro do horário de funcionamento da clínica, descontando bloqueios.
- **Carga por profissional**: tempo total, quantidade de atendimentos e no-show de cada profissional (consultas não canceladas).
- **Previsão (próximos 14 dias)**: ocupação estimada com base nos agendamentos futuros.

**Dica:** para o painel fazer sentido, mantenha em dia Configurações > Agenda > Horários e as Regras de cada profissional (ver 03-agenda).

## Outras telas do painel que aparecem no menu Relatórios
O grupo Relatórios do menu pode mostrar Financeiro, Estoque e Follow-up conforme as permissões. O relatório de **Follow-up** (/reports/follow-up) está descrito em 04-pacientes. Os relatórios Financeiro e Estoque ficam nos arquivos de Financeiro e Estoque.

## Rotas internas do dashboard
- /dashboard e / mostram o Dashboard. /manager/panel mostra o Painel Gestor.
- /dashboard/clinic e /dashboard/professional são respostas técnicas (JSON) usadas pelo sistema, não são telas para a equipe.

## Perguntas frequentes
**Por que meu Dashboard é diferente do de um colega?** Cada cartão depende de permissão. A recepção costuma ver Agenda e Pacientes; o financeiro vê Receita; quem controla estoque vê os alertas.

**O que significa "Pacientes do dia"?** É a lista de pacientes diferentes que têm agendamento hoje, mesmo que tenham mais de um serviço. A coluna de quantidade mostra quantos agendamentos cada um tem.

**Por que o número de Atendimentos hoje é maior que o de Próximos atendimentos?** Próximos atendimentos mostra apenas até 10 itens futuros de hoje; o cartão conta o dia todo, incluindo os que já aconteceram.

**Como vejo a receita de outro mês?** O Dashboard mostra só o mês corrente. Para outros períodos use Relatórios > Financeiro.

**Como vejo estoque baixo?** O cartão Alertas de estoque leva para Estoque > Alertas, onde cada material em alerta aparece em detalhe.

**O Dashboard não atualiza sozinho.** Recarregue a página (F5) para ver os números mais recentes.

**Onde vejo a taxa de faltas (no-show)?** No Painel Gestor (/manager/panel), cartão Comparecimento e tabela Ocupação por dia.

**Não tenho acesso ao Painel Gestor.** Precisa da permissão de ver a agenda (scheduling.read). Fale com o dono da clínica.
