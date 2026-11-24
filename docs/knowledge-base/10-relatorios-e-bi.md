---
title: Relatórios disponíveis para a clínica e onde encontrar cada indicador
routes:
  - /finance/reports
  - /stock/reports
  - /reports/follow-up
  - /dashboard
  - /manager/panel
---

# Relatórios e indicadores

## Visão geral
O grupo **Relatórios** do menu lateral mostra, conforme as permissões do seu papel:
| Item do menu | O que mostra | Permissão | Arquivo da base |
|---|---|---|---|
| Financeiro | Receita, entradas e saídas, ticket médio, conversão, orçamentos por status, receita por profissional e por serviço | finance.reports.read | 07-financeiro |
| Estoque | Consumo, perdas, custos, alertas e sugestão de compra | stock.reports.read | 08-estoque |
| Follow-up | Pacientes que não voltam há muito tempo | patients.read | este arquivo e 04-pacientes |
| Certificações | Políticas e controles de segurança da clínica | compliance.policies.read | 14-lgpd-seguranca-e-privacidade |

O grupo só aparece se você tiver pelo menos uma dessas permissões. O **Dashboard** e o **Painel Gestor** trazem os números do dia a dia (veja 02-dashboard).

## Relatório de Follow-up
<!-- perm: patients.read -->
**Onde fica:** Menu > Relatórios > Follow-up (/reports/follow-up).

Lista os pacientes que não retornam e precisam de contato.
- Título **Relatório de Follow-up** ("Pacientes que não retornaram e precisam de contato.").
- Cartões: **Total de pacientes**, **Nunca vieram**, **Com WhatsApp** e **Com telefone**.
- Filtro **Sem consulta há mais de** com as opções 60, 90, 120, 180, 270, 365, 540 e 730 dias (o padrão é 180) e o botão **Filtrar**. O sistema aceita de 30 a 730 dias.
- Tabela: #, **Paciente**, **Última consulta**, **Dias sem retorno**, **Telefone**, **WhatsApp** (Sim ou Não) e o botão **Ver**, que abre o paciente.
- **📄 Exportar PDF** gera o relatório em PDF (/reports/follow-up/export.pdf).
- Quando está tudo em dia: "Nenhum paciente encontrado para este critério. Todos em dia!".
- Para falar com os pacientes use a aba **Follow-up** em Pacientes > Aniversariantes / Follow-up, que tem o botão WhatsApp em cada linha.

## Relatório Financeiro
Está descrito em detalhe em 07-financeiro (seção Relatório Financeiro). Resumo: filtros **De**, **Até** e **Profissional**, indicadores de receita e caixa, exportação em planilha e PDF. Use os relatórios contábeis para enviar dados ao contador.

## Relatório de Estoque
Está descrito em 08-estoque (seção Relatório de estoque). Resumo: custo de saídas e perdas, consumo por material, serviço e profissional, alertas de validade e sugestão de compra.

## Dashboard e Painel Gestor
O **Dashboard** mostra cartões do mês corrente (agendamentos, receita, pacientes) e o **Painel Gestor** (/manager/panel) reúne indicadores de gestão. Para períodos diferentes use Relatórios > Financeiro. Veja 02-dashboard.

## Auditoria, BI e Insights de IA
Estas telas não aparecem no menu da clínica. Se você precisa de um histórico de quem alterou algo (por exemplo um lançamento do caixa), o próprio detalhe do orçamento tem o **Histórico de alterações**, e a agenda tem os **Logs** de cada agendamento (veja 03-agenda). Para uma análise que não encontrou nos relatórios acima, fale com a equipe de suporte.

## Exportações em resumo
| Onde | Formato | Botão |
|---|---|---|
| Relatório Financeiro | Planilha e PDF | 📊 Exportar planilha, 📄 Exportar PDF |
| Relatórios contábeis | CSV | Lançamentos (CSV), Resumo por conta (CSV), Plano de contas (CSV) |
| Lista de orçamentos (no Relatório Financeiro) | Planilha .xlsx | 📥 Exportar planilha |
| Relatório de Estoque | Planilha e PDF | 📊 Planilha, 📄 PDF |
| Follow-up | PDF | 📄 Exportar PDF |
| Linha do tempo do paciente | CSV | Exportar na linha do tempo (04-pacientes) |
| Logs de importação | CSV | Exportar CSV |
Os arquivos baixam no seu navegador. Se o arquivo vier vazio, ajuste o período. As exportações ficam registradas no sistema por segurança.

## Perguntas frequentes
**Onde vejo o faturamento do mês?** Relatórios > Financeiro (cartão **Receita (vendas pagas)**) ou o Dashboard.

**Qual a diferença entre Receita e Entradas no caixa?** Receita conta vendas com pagamento confirmado. Entradas no caixa somam os lançamentos de entrada do Fluxo de Caixa, inclusive os lançados manualmente.

**O relatório veio vazio.** Confira o período em **De** e **Até** e o filtro de **Profissional**. Sem vendas pagas no período aparece "Sem dados no período.".

**Como vejo quem não volta à clínica há meses?** Relatórios > Follow-up. Escolha **Sem consulta há mais de** e clique em **Filtrar**.

**Como vejo o lucro por serviço?** Relatórios > Financeiro, bloco **Receita por serviço** (coluna **Margem**, que desconta o custo dos materiais).

**Como baixo os dados em planilha?** Use **📊 Exportar planilha** nos relatórios Financeiro e de Estoque.

**Não vejo o grupo Relatórios.** Faltam permissões de relatório. Peça em Configurações > Usuários e Acesso > Papéis e Permissões.

**Existe relatório de comissão?** Não.

**Como vejo quem alterou um dado?** Veja o histórico dentro do próprio registro (orçamento, agendamento). Para casos que o histórico não resolve, fale com a equipe de suporte.

## Mapa de rotas
- /finance/reports, /finance/reports/export.csv, /finance/reports/export.pdf, /finance/accounting.
- /stock/reports, /stock/reports/export.csv, /stock/reports/export.pdf.
- /reports/follow-up, /reports/follow-up/export.pdf.
- /dashboard, /manager/panel.
- /reports/metrics.csv: exportação de métricas internas do sistema, sem tela própria e sem uso no dia a dia.
