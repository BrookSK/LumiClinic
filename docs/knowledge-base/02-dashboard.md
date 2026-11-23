---
title: Dashboard e Painel Gestor
routes:
  - /dashboard
  - /manager/panel
---

# Dashboard e Painel Gestor

## Dashboard (tela inicial)
<!-- perm: nenhuma especifica (cada cartao depende da permissao indicada) -->
**Onde fica:** Menu > Dashboard (/ ou /dashboard). E a tela que abre logo depois do login.

O Dashboard e um resumo do dia da clinica. Ele mostra uma saudacao com seu nome (Bom dia, Boa tarde ou Boa noite conforme a hora), a data por extenso e a frase "Aqui esta o resumo do dia". Cada bloco so aparece se o seu papel tem a permissao correspondente, por isso o Dashboard da recepcao, do profissional e do dono sao diferentes.

### Cartoes de indicadores (parte de cima)
| Cartao | O que mostra | Permissao | Link do cartao |
|---|---|---|---|
| Atendimentos hoje | Total de agendamentos de hoje (todos os status) e a contagem de confirmados, em andamento e concluidos | scheduling.read | Abrir agenda (/schedule) |
| Pacientes do dia | Numero de pacientes diferentes com agendamento hoje (ate 30) | patients.read | Ver pacientes (/patients) |
| Receita do mes | Soma do valor liquido das vendas com status pago, criadas do dia 1 do mes ate hoje | finance.sales.read | Ver financeiro (/finance/sales) |
| Alertas de estoque | Quantidade total de alertas de estoque | stock.alerts.read | Ver alertas (/stock/alerts) |

### Secoes de detalhe (parte de baixo)
- **Proximos atendimentos** (scheduling.read): ate 10 agendamentos de hoje que ainda nao aconteceram (horario igual ou posterior a agora), sem cancelados, em ordem de horario. Cada linha mostra hora, paciente, servico e status (Confirmado, Em andamento, Concluido, Pendente). Mensagem vazia: "Nenhum atendimento agendado para hoje."
- **Pacientes do dia** (patients.read): lista de pacientes de hoje com avatar, nome, contato (telefone ou e-mail), horario do primeiro atendimento e quantidade de agendamentos. Mensagem vazia: "Nenhum paciente agendado para hoje."
- **Detalhes do estoque** (stock.alerts.read, so aparece se houver algum alerta): quatro contadores **Estoque baixo**, **Zerado**, **Vencendo** (vence em ate 30 dias) e **Vencido**, com o link **Ver todos os alertas**.

### Observacoes
- Os numeros sao calculados na hora que a pagina abre. Para atualizar, recarregue a pagina.
- O cartao "Atendimentos hoje" conta todos os agendamentos do dia, inclusive cancelados e faltas. Cancelados nao entram em "Proximos atendimentos" nem em "Pacientes do dia".
- A receita do mes considera apenas vendas pagas; orcamentos em aberto nao entram.

### Erros e duvidas comuns
- **Um cartao nao aparece**: falta a permissao daquele cartao (veja a coluna Permissao). Peca ao dono em Configuracoes > Usuarios e Acesso > Papeis e Permissoes.
- **Receita do mes zerada mas ha vendas**: a venda precisa estar com status pago e criada neste mes. Veja Financeiro > Orcamentos.
- **Atendimentos de hoje nao aparecem em Proximos atendimentos**: a lista mostra so os que ainda nao comecaram e nao foram cancelados.
- **Nada aparece e surge a mensagem Selecione uma clinica**: a sessao esta sem clinica ativa. Saia e entre novamente.

## Painel Gestor
<!-- perm: scheduling.read -->
**Onde fica:** /manager/panel. Nao ha item no menu lateral, abra pelo endereco ou por um favorito. Mostra indicadores operacionais da agenda para gestores.

**Filtros**
| Campo | Opcoes |
|---|---|
| Visao | Semana, Dia, Periodo |
| Data (base) | Data de referencia da Semana ou do Dia |
| Inicio e Fim | Usados na visao Periodo |
| Profissional | Todos ou um profissional |
Clique em **Aplicar**; **Limpar** volta ao padrao (semana atual).

**Cartoes**
- **Taxa de ocupacao**: percentual do tempo ocupado sobre a capacidade (mostra Ocupado e Capacidade).
- **Comparecimento**: total de agendamentos e quantidade de no-show (Faltou).
- **Atendimentos concluidos**: concluidos e em andamento.
- **Agendados/Confirmados**: quantidade em cada status.

**Tabelas**
- **Ocupacao por dia**: Dia, Ocupacao, Capacidade, Ocupado e No-show. A capacidade usa blocos de 15 minutos dentro do horario de funcionamento da clinica, descontando bloqueios.
- **Carga por profissional**: tempo total, quantidade de atendimentos e no-show de cada profissional (consultas nao canceladas).
- **Previsao (proximos 14 dias)**: ocupacao estimada com base nos agendamentos futuros.

**Dica:** para o painel fazer sentido, mantenha em dia Configuracoes > Clinica > Horarios e as Regras de cada profissional (ver 03-agenda).

## Outras telas do painel que aparecem no menu Relatorios
O grupo Relatorios do menu pode mostrar Financeiro, Estoque e Follow-up conforme as permissoes. O relatorio de **Follow-up** (/reports/follow-up) esta descrito em 04-pacientes. Os relatorios Financeiro e Estoque ficam nos arquivos de Financeiro e Estoque.

## Rotas internas do dashboard
- /dashboard e / mostram o Dashboard. /manager/panel mostra o Painel Gestor.
- /dashboard/clinic e /dashboard/professional sao respostas tecnicas (JSON) usadas pelo sistema, nao sao telas para a equipe.

## Perguntas frequentes
**Por que meu Dashboard e diferente do de um colega?** Cada cartao depende de permissao. A recepcao costuma ver Agenda e Pacientes; o financeiro ve Receita; quem controla estoque ve os alertas.

**O que significa "Pacientes do dia"?** E a lista de pacientes diferentes que tem agendamento hoje, mesmo que tenham mais de um servico. A coluna de quantidade mostra quantos agendamentos cada um tem.

**Por que o numero de Atendimentos hoje e maior que o de Proximos atendimentos?** Proximos atendimentos mostra apenas ate 10 itens futuros de hoje; o cartao conta o dia todo, incluindo os que ja aconteceram.

**Como vejo a receita de outro mes?** O Dashboard mostra so o mes corrente. Para outros periodos use Relatorios > Financeiro.

**Como vejo estoque baixo?** O cartao Alertas de estoque leva para Estoque > Alertas, onde cada material em alerta aparece em detalhe.

**O Dashboard nao atualiza sozinho.** Recarregue a pagina (F5) para ver os numeros mais recentes.

**Onde vejo a taxa de faltas (no-show)?** No Painel Gestor (/manager/panel), cartao Comparecimento e tabela Ocupacao por dia.

**Nao tenho acesso ao Painel Gestor.** Precisa da permissao de ver a agenda (scheduling.read). Fale com o dono da clinica.
