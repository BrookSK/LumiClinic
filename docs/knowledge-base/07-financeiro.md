---
title: Financeiro, orçamentos, pagamentos, caixa, contas a pagar, plano de contas e relatórios
routes:
  - /finance/sales
  - /finance/cashflow
  - /finance/accounts-payable
  - /finance/cost-centers
  - /finance/chart-of-accounts
  - /finance/reports
  - /finance/accounting
---

# Financeiro

## Visão geral do módulo
O grupo **Financeiro** do menu lateral reúne: **Orçamentos**, **Fluxo de Caixa**, **Contas a Pagar**, **Centros de Custo**, **Plano de Contas** e **Relatórios contábeis**. O relatório **Financeiro** (receitas, margem, exportações) fica no grupo **Relatórios** do menu.

- O grupo só aparece para quem tem a permissão de ver orçamentos (finance.sales.read). Cada item interno tem a sua própria permissão, mas quem tem finance.sales.read enxerga os itens mesmo sem a permissão específica.
- O fluxo é: criar o **orçamento** do paciente, adicionar os serviços, aprovar, registrar os **pagamentos**. Cada pagamento recebido gera automaticamente um lançamento de entrada no **Fluxo de Caixa**.
- Despesas (aluguel, fornecedores, equipamentos) são cadastradas em **Contas a Pagar**. Ao pagar uma parcela, o sistema lança uma saída no Fluxo de Caixa.
- **Centros de Custo** e **Plano de Contas** organizam os lançamentos para a contabilidade.
- Valores usam o formato brasileiro: 1.500,00. Nos campos de valor digite, por exemplo, 150,00.
- Não existe cadastro de comissões por profissional nem cadastro de formas de pagamento: as formas disponíveis são fixas (Dinheiro, Pix, Cartão de crédito, Cartão de débito, Boleto, e nos lançamentos também Transferência, Cheque e Outro).
- Quem tem o papel Profissional vê apenas os orçamentos em que ele mesmo atende e não pode alterar status, registrar pagamento, cancelar ou excluir. O sistema responde "Acesso negado.".

## Orçamentos (lista)
<!-- perm: finance.sales.read (ver), finance.sales.create (criar) -->
**Onde fica:** Menu > Financeiro > Orçamentos (/finance/sales). Também abre pelo botão de orçamentos na ficha do paciente (com o filtro daquele paciente).

**Tela**
- Título **Orçamentos**. Quando filtrado por paciente aparece o nome do paciente, o link **Ver todos** e **← Voltar ao paciente**.
- Botão **+ Novo orçamento**.
- Filtros por status em forma de botões: **Todos**, **📝 Rascunho**, **📤 Enviado**, **⏸ Em espera**, **✅ Aprovado**, **❌ Recusado**, **🏁 Concluído**. Quando um filtro está ativo aparece "Filtrando por: ..." e o link **✕ Limpar filtro**.
- A tabela mostra Data, Paciente, Status, Total (com o desconto entre parênteses) e a situação do pagamento: **💰 Pago**, **Cancelado**, **✓ Pago R$ ...** e **⏳ Falta R$ ... em N parcela(s)**. O botão **Abrir** leva ao detalhe.
- Paginação com **Anterior** e **Próxima**.

### Criar orçamento
1. Clique em **+ Novo orçamento**.
2. No campo **Paciente** digite nome, e-mail ou telefone ("Buscar por nome, e-mail ou telefone") e escolha o paciente na lista. É obrigatório.
3. Opcional: **Observações** (ex.: Pacote verão, desconto indicação...).
4. Clique em **Criar orçamento**. O sistema abre o orçamento para você adicionar os itens. **Cancelar** fecha o formulário.
Erro: "Paciente é obrigatório." (você não escolheu um paciente da lista).

## Orçamento (detalhe)
<!-- perm: finance.sales.read (ver), finance.sales.update (editar itens, status, enviar), finance.payments.create (pagamentos), finance.sales.cancel (cancelar e excluir) -->
**Onde fica:** Orçamentos > **Abrir** (/finance/sales/view?id=...). O título é **Orçamento #número**, com o selo do status e o selo do pagamento (💰 Pago, 📋 Pagamento em andamento (x/y) ou Cancelado).

**Botões do topo**
- **🖨 Imprimir**: abre a versão para impressão (/finance/sales/print) com os dados da clínica, paciente, itens, desconto, total e pagamentos, e os botões **Imprimir** e **Fechar**.
- **📱 Enviar WhatsApp**: envia ao paciente uma mensagem com o nome da clínica, a lista de itens, o total e o link público do orçamento. Precisa de telefone no cadastro do paciente e do WhatsApp da clínica conectado.
- **📧 Enviar E-mail**: envia um e-mail com o nome da clínica, os itens, o total e um botão com o link público. Precisa de e-mail no cadastro do paciente.
- **Ver paciente** e **Voltar**.
- Se o orçamento ainda estava em **Rascunho**, depois de enviar com sucesso o status muda sozinho para **Enviado ao paciente**.
- Erros de envio: "Paciente sem telefone cadastrado.", "Paciente sem e-mail cadastrado.", "Falha ao enviar WhatsApp: ..." e "Falha ao enviar e-mail: ...". Veja a seção de solução de problemas.

### Serviços / Itens
1. Clique em **+ Adicionar**.
2. Escolha o **Serviço** (obrigatório). O **Valor unit. (R$)** é preenchido com o preço do serviço e pode ser alterado.
3. Informe a **Qtd** (mínimo 1, vira o número de sessões planejadas) e, se quiser, o **Profissional (opcional)**.
4. Clique em **Adicionar**.
Para remover um item use o **✕** ao lado dele e confirme "Remover este item?". Só é possível adicionar ou remover itens enquanto o orçamento está aberto: "A venda não está aberta." e "Não é possível remover itens de um orçamento que não está aberto." aparecem quando ele já está pago ou cancelado.

### Totais
A área de totais mostra **Subtotal**, **Desconto**, **Total**, **Pago (x/y)**, **Pendente (n parcelas)** e **Restante**.

### Status do orçamento e desconto
- **Status do orçamento**: escolha **Rascunho**, **Enviado ao paciente**, **Aprovado**, **Em espera**, **Recusado** ou **Concluído** e clique em **Salvar**. Esse status é o acompanhamento comercial (follow-up). Ele é diferente do status financeiro (aberto, pago, cancelado), que o sistema calcula pelos pagamentos.
- **Desconto**: digite o valor, escolha **R$** (fixo) ou **%** (percentual) e clique em **Aplicar**. O botão **Aplicar** grava na hora o desconto (o percentual é calculado sobre o subtotal) e mantém o status atual. O total líquido nunca fica negativo.
- Quando o orçamento está totalmente pago aparece "💰 Orçamento pago integralmente. Não é possível editar." e itens e desconto ficam travados. O mesmo vale para orçamento cancelado.

### Pagamentos
<!-- perm: finance.payments.create (registrar, editar, excluir), finance.payments.refund (estornar) -->
1. Clique em **+ Registrar** na seção **Pagamentos**.
2. Preencha:
| Campo | Observação |
|---|---|
| Método | **PIX**, **Cartão**, **Dinheiro** ou **Boleto** |
| Valor total (R$) | Obrigatório. Já vem preenchido com o valor que falta |
| Tipo do cartão | **Crédito** ou **Débito** (só para Cartão) |
| Parcelas | De 1x até 48x. Divide o valor em parcelas mensais |
| Data do pagamento | Data da primeira parcela. Vem com a data de hoje |
| Status | **Pago** ou **Pendente** |
3. Clique em **Salvar**.

**Como as parcelas funcionam**
- O valor é dividido em parcelas iguais (a última absorve o arredondamento). As parcelas seguem de mês em mês a partir da data informada.
- Somente a primeira parcela recebe o status escolhido. As demais ficam como **Pendente** e aparecem com a etiqueta 2/6, 3/6 e assim por diante.
- Quando o status da primeira é **Pago**, o sistema cria um lançamento de entrada no Fluxo de Caixa com o valor total e a descrição "Pagamento de orcamento #número - nome do paciente".
- O orçamento fica **Pago** quando a soma dos pagamentos com status Pago alcança o total líquido. Enquanto não alcança, aparece **Pagamento em andamento (x/y)**.

**Editar pagamento**: use o **✏** ao lado do pagamento. Dá para alterar Método (PIX, Cartão Crédito, Cartão Débito, Dinheiro, Boleto), Valor, Data e Status (**Pago**, **Pendente** ou **Estornado**) e clicar em **Salvar**. Para marcar uma parcela pendente como recebida, edite e troque o status para Pago.
**Excluir pagamento**: **✕** e confirme "Remover este pagamento?".
Erros: "Método inválido.", "Status inválido.", "Valor inválido.", "Venda cancelada.", "Pagamento inválido.".

### Cancelar e excluir orçamento
<!-- perm: finance.sales.cancel -->
- Na seção **Cancelar orçamento** clique em **Confirmar cancelamento** (e confirme "Tem certeza que deseja cancelar este orçamento?"). O orçamento fica **Cancelado** e trava.
- Somente orçamentos já cancelados mostram o botão **🗑 Excluir orçamento** ("Excluir permanentemente este orçamento? Esta ação não pode ser desfeita."). Ele some da lista. Se tentar excluir sem cancelar antes: "Apenas orcamentos cancelados podem ser excluidos.".

### Planejamento de sessões
Quando o orçamento tem itens com sessões planejadas, a seção **Planejamento de sessões** mostra Serviço, Profissional, Sessões (usadas / total) e Status, com o link **Agendar** que abre a Agenda. Cada atendimento concluído consome uma sessão. Orçamentos aprovados também podem gerar os agendamentos automaticamente, usando o primeiro horário livre do profissional nos próximos 90 dias, mas o botão para isso não aparece na tela; na prática use o link **Agendar**. Mensagens possíveis: "Orçamento precisa estar aprovado.", "Sem disponibilidade para procedimento ... (até 90 dias)." e "profissional não definido".

### Histórico de alterações
No final aparece **Histórico de alterações** com Data e Ação de tudo que foi feito no orçamento (itens, pagamentos, status).

### Exportar a lista de orçamentos
No relatório **Financeiro** existe o bloco **Exportar relatório de orçamentos**: escolha o status (ou **Todos os status**) e clique em **📥 Exportar planilha**. Gera um arquivo .xlsx (/finance/sales/export.csv).

### Link público do orçamento
O paciente recebe um link que abre o orçamento sem login (/pub/budget). Mostra a clínica, os itens, o desconto, o total, as formas de pagamento e os pagamentos já feitos. O link é único e não pode ser adivinhado. Se o paciente vir "Link inválido ou expirado." peça para reenviar o orçamento pelo botão de WhatsApp ou e-mail.

## Fluxo de Caixa
<!-- perm: finance.entries.read (ver), finance.entries.create (criar e editar), finance.entries.delete (excluir) -->
**Onde fica:** Menu > Financeiro > Fluxo de Caixa (/finance/cashflow).

**Resumo e filtros**
- Três cartões: **Entradas**, **Saídas** e **Saldo** do período.
- Filtros: **De**, **Até** (padrão: do dia 1 do mês até hoje) e **Tipo** (**Todos**, **Entradas**, **Saídas**). Clique em **Filtrar**.
- A lista traz Data, Descrição, Centro de custo e Valor (positivo para entrada e negativo para saída), com **Editar** e **✕** (excluir). Paginação com **Anterior** e **Próxima** (padrão 100 por página).

**Criar lançamento manual**
1. Clique em **+ Lançamento** (abre **Novo lançamento**).
2. Escolha o **Tipo**: **Entrada** ou **Saída**.
3. Preencha **Descrição** (ex.: Pagamento consulta, Aluguel...), **Valor (R$)** (obrigatório), **Data** (hoje por padrão).
4. Opcional: **Centro de custo (opcional)** e **Forma de pagamento (opcional)**.
5. Clique em **Salvar** (ou **Cancelar**).
Erros: "Valor inválido.", "Data inválida.", "Forma de pagamento inválida.", "Centro de custo inválido.".

**Editar lançamento** (/finance/cashflow/edit): clique em **Editar** na linha. Altere Tipo, Descrição, Valor, Data, Centro de custo e Forma de pagamento e clique em **Salvar**, ou **Voltar**.
- Lançamentos gerados automaticamente por uma venda, um pagamento ou uma conta a pagar mostram o aviso "Este lançamento foi gerado por uma venda, pagamento ou conta a pagar. Tipo, valor e data não podem ser alterados aqui; ajuste na origem do lançamento." Neles só dá para mudar descrição, centro de custo e forma de pagamento. Se tentar mudar o resto: "Lançamento gerado por venda, pagamento ou conta a pagar: só é possível alterar descrição, centro de custo e forma de pagamento."

**Excluir**: botão **✕** na linha (permissão finance.entries.delete). O lançamento sai do caixa e o histórico da versão anterior fica guardado.

## Contas a Pagar
<!-- perm: finance.ap.read (ver), finance.ap.manage (criar, pagar, excluir) -->
**Onde fica:** Menu > Financeiro > Contas a Pagar (/finance/accounts-payable). Quem tem permissão de vendas (finance.sales.read e finance.sales.create) também consegue usar.

**Tela**
- Título **Contas a Pagar** com **Total no período**.
- Filtros **De**, **Até** e **Status** (**Em aberto**, **Paga**, **Todas**) e **Filtrar**.
- Botão **+ Nova conta**.
- A tabela mostra Vencimento (com "(vencida)" quando passou), Título, Fornecedor, Parcela, Valor, Status (Em aberto ou Paga) e as ações **✓ Pagar** (com a escolha de **Forma de pagamento**) e **✕** (excluir).

**Criar conta a pagar**
1. Clique em **+ Nova conta**.
2. Preencha:
| Campo | Obrigatório | Observação |
|---|---|---|
| Título | Sim | Ex.: Aluguel, Equipamento laser... |
| Fornecedor (opcional) | Não | Ex.: Imobiliária, Fornecedor X |
| Tipo | Sim | **Conta única** (paga uma vez), **Parcelada** (divide em X parcelas mensais) ou **Recorrente mensal** (repete todo mês) |
| Valor (R$) | Sim | É o valor de **cada parcela** (ou de cada mês na recorrente) |
| 1º vencimento | Sim | Data da primeira parcela |
| Nº de parcelas | Só na parcelada | De 1 a 60 |
| Recorrente até | Só na recorrente | Se ficar vazio, o sistema cria 12 meses |
| Centro de custo (opcional) | Não | Classifica a despesa |
| Descrição (opcional) | Não | |
3. Clique em **Criar** (ou **Cancelar**).
Erros: "Título é obrigatório.", "Vencimento inválido.", "Valor inválido.", "Centro de custo inválido.".

**Pagar**: na linha da parcela escolha a **Forma de pagamento** e clique em **✓ Pagar**. A parcela vira **Paga** e o sistema cria uma saída no Fluxo de Caixa. Mensagem: "Pagamento registrado.". Erros: "Parcela inválida.", "Parcela já está paga/cancelada.", "Forma de pagamento inválida.".
**Excluir**: **✕** exclui a conta inteira (todas as parcelas): "Conta excluída.".

## Centros de Custo
<!-- perm: finance.cost_centers.manage -->
**Onde fica:** Menu > Financeiro > Centros de Custo (/finance/cost-centers).

Centros de custo categorizam receitas e despesas (ex.: Aluguel, Marketing, Materiais, Salários).
- Atalhos no topo: **Plano de Contas** e **Fluxo de Caixa**.
- **Criar**: preencha **Nome do centro de custo** (obrigatório), **Código (opcional)** (ex.: CC-001), **Conta contábil (opcional)** (escolha uma conta do Plano de Contas; se a clínica ainda não tem plano aparece o link **Criar plano de contas**) e **Categoria** (**Despesas com pessoal**, **Despesas administrativas**, **Despesas financeiras**, **Investimentos**, **Custos operacionais** ou **Sem categoria**). Clique em **Criar**.
- Cada centro mostra o selo **Ativo** ou **Inativo**, o código e a conta contábil. Ações: **Editar**, **Inativar** ou **Ativar**.
- **Editar** (/finance/cost-centers/edit): Nome, Código, Categoria, Conta contábil e **Descrição / observação para a contabilidade**. Clique em **Salvar** ou **Voltar**.
- Só dá para vincular contas analíticas de Receita, Custo / Despesa ou Passivo.
Erros: "Já existe um centro de custo com este nome.", "Já existe um centro de custo com este código.", "O nome deve ter no máximo 190 caracteres.", "O código do centro de custo deve ter no máximo 32 caracteres.", "Conta contábil inválida.", "A conta contábil selecionada está inativa.", "Categoria inválida.", "Centro de custo inválido.".
Mensagens de sucesso: "Status atualizado." e "Excluído.".

## Plano de Contas
<!-- perm: finance.cost_centers.manage -->
**Onde fica:** Menu > Financeiro > Plano de Contas (/finance/chart-of-accounts).

Estrutura as contas contábeis da clínica para que os relatórios saiam organizados para o contador.
- Atalhos: **Centros de Custo**, **Relatórios contábeis** e **Exportar plano (CSV)**.
- **Usar modelo padrão para clínicas**: aparece quando a clínica ainda não tem contas. Cria um plano pronto com Ativo, Passivo, Patrimônio Líquido, Receitas e Custos/Despesas, com as contas de Caixa, Bancos e Cartões já ligadas às formas de pagamento (Dinheiro, Pix, Cartão, Boleto...). Centros de custo com o mesmo nome de uma conta (ex.: 13º Salário, Aluguel e Condomínio) são vinculados automaticamente. Só funciona uma vez: "O plano de contas já possui contas. Para usar o modelo padrão, a clínica não pode ter contas cadastradas."
- **+ Nova conta**: **Conta pai (opcional)**, **Código** (ex.: 1.1.2.09), **Nome**, **Natureza** (**Ativo**, **Passivo**, **Patrimônio Líquido**, **Receita**, **Custo / Despesa**), **Tipo** (**Sintética (grupo)** ou **Analítica (recebe lançamentos)**), **Forma de pagamento**, **Conta padrão para**, **Código externo (contador)** e **Descrição**. Clique em **Criar conta**.
- **Regras**: contas sintéticas agrupam outras contas; só as analíticas recebem lançamentos. O código da conta filha começa com o código da pai e um ponto (1.1.2 vira 1.1.2.09). A forma de pagamento só vale para contas analíticas do Ativo (ex.: Caixa Geral, Pix, Cartão) e indica a conta de entrada ou saída do dinheiro.
- **Conta padrão para** tem três opções: **Receitas sem classificação** e **Despesas sem classificação** (recebem lançamentos sem centro de custo ligado a uma conta) e **Conta financeira de lançamentos sem forma de pagamento** (é a conta financeira padrão, uma conta analítica do Ativo).
- A tabela mostra Código, Conta, Natureza, Tipo, Forma de pagamento vinculada, Código externo, Centros de custo vinculados, Status (**Ativa** ou **Inativa**) e as ações **Editar**, **Inativar** ou **Ativar**, **Excluir** e **Ver**.
- **Editar conta** (/finance/chart-of-accounts/edit): mesmos campos. Contas que têm contas filhas não podem mudar de código, natureza ou tipo.
Erros mais comuns: "Código é obrigatório.", "Nome é obrigatório.", "Código inválido. Use apenas números separados por ponto (ex: 1.1.2.08).", "Já existe uma conta com este código.", "Este código pertence a uma conta excluída. Para reutilizá-lo, crie uma nova conta com este código (a conta excluída será restaurada).", "A conta pai deve ser sintética (grupo).", "A conta deve ter a mesma natureza da conta pai (...).", "Selecione a natureza da conta (ou uma conta pai).", "O código deve começar com o código da conta pai seguido de ponto (ex: ...)", "Não é possível excluir: a conta possui contas filhas. Exclua ou mova as contas filhas primeiro.", "Não é possível excluir: há ... centro(s) de custo vinculado(s)...", "Forma de pagamento só pode ser vinculada a contas analíticas do Ativo (ex: Caixa Geral, Pix, Cartão).", "Opção de conta padrão inválida.", "Selecione uma conta analítica (que recebe lançamentos), não uma conta de grupo.", "Não é possível tornar a conta sintética: há ...".

## Relatórios contábeis
<!-- perm: finance.reports.read -->
**Onde fica:** Menu > Financeiro > Relatórios contábeis (/finance/accounting).

Mostra os lançamentos do fluxo de caixa do período classificados pelo Plano de Contas (conta de resultado pelo centro de custo e conta financeira pela forma de pagamento).
- Filtros **De** e **Até** e botão **Atualizar**.
- Exportações: **Lançamentos (CSV)**, **Resumo por conta (CSV)** e **Plano de contas (CSV)**.
- Cartões **Receitas**, **Custos e despesas**, **Resultado** (receitas menos custos e despesas) e **Fluxo de caixa do período** (entradas / saídas).
- Tabelas **Resultado por conta** e **Movimento por conta financeira**.
- Avisos que ajudam a corrigir a classificação: lançamentos **sem conta contábil** (vincule os centros de custo às contas em Centros de Custo ou defina contas padrão no Plano de Contas), lançamentos **sem conta financeira** (vincule cada forma de pagamento a uma conta analítica do Ativo) e lançamentos **sem forma de pagamento vinculada** (foram lançados na conta financeira padrão; informe a forma de pagamento ao lançar no caixa e ao pagar contas a pagar).
- Se o período tem mais lançamentos do que o limite, aparece o aviso de que os totais e as exportações estão incompletos: reduza o intervalo de datas.
- Se a clínica não tem plano de contas aparece "A clínica ainda não tem plano de contas" com o botão **Criar plano de contas**.
- Período máximo: 3 anos ("O período máximo do relatório é de 3 anos. Reduza o intervalo de datas.").

## Relatório Financeiro
<!-- perm: finance.reports.read -->
**Onde fica:** Menu > Relatórios > Financeiro (/finance/reports).

**Filtros:** **De**, **Até**, **Profissional** (**Todos** ou um profissional) e **Gerar relatório**.
**Exportações:** **📊 Exportar planilha** (/finance/reports/export.csv), **📄 Exportar PDF** (/finance/reports/export.pdf) e o atalho **Relatórios contábeis**.

**Indicadores**
| Cartão | O que significa |
|---|---|
| Receita (vendas pagas) | Total de vendas com pagamento confirmado |
| Entradas no caixa | Lançamentos de entrada no fluxo de caixa |
| Saídas do caixa | Lançamentos de saída no fluxo de caixa |
| Saldo do período | Entradas menos saídas |
| Ticket médio | Valor médio por venda paga |
| Agendamentos | Total de agendamentos no período |
| Vendas pagas | Quantidade de vendas pagas |
| Conversão | Percentual de agendamentos que viraram venda paga |
| Receita recorrente | Receita de assinaturas |

**Blocos**
- **Orçamentos por status (follow-up)**: quantidade por status (Rascunho, Enviado ao paciente, Aprovado, Em espera, Recusado, Concluído), com o link **Visualizar orçamentos** que abre a lista filtrada e o bloco **Exportar relatório de orçamentos**.
- **Receita por profissional** e **Receita por serviço**: Receita, **Custo (materiais)** e **Margem** (receita menos custo dos materiais consumidos).
- **Últimas vendas pagas** com o botão **Abrir**.
- **Últimos lançamentos do caixa** e o link **Ver fluxo de caixa completo**.

## Perguntas frequentes
**Como faço um orçamento para um paciente?** Menu Financeiro > Orçamentos > **+ Novo orçamento**, escolha o paciente, clique em **Criar orçamento**, depois **+ Adicionar** para incluir os serviços.

**Como envio o orçamento para o paciente?** No detalhe do orçamento use **📱 Enviar WhatsApp** ou **📧 Enviar E-mail**. O paciente recebe um link público para ver o orçamento.

**O botão de WhatsApp deu erro.** Confira se o paciente tem telefone e se o WhatsApp da clínica está conectado em Configurações > Geral > WhatsApp. Veja também os logs em Logs WhatsApp.

**Como dou desconto?** No detalhe do orçamento, campo **Desconto**, escolha R$ ou % e clique em **Aplicar**. Depois que o orçamento está pago ou cancelado o desconto trava.

**Como registro um pagamento parcelado no cartão?** Em **Pagamentos** clique em **+ Registrar**, escolha Cartão, tipo Crédito, o valor total e o número de **Parcelas**. O sistema cria uma parcela por mês.

**Como marco uma parcela como paga?** No pagamento pendente clique no **✏**, troque o status para **Pago** e **Salvar**.

**Como estorno um pagamento?** Edite o pagamento e escolha o status **Estornado**. A permissão de estorno é finance.payments.refund.

**Posso editar um orçamento já pago?** Não. Itens e desconto ficam travados. Para corrigir, peça a alguém com permissão para ajustar o pagamento ou cancele o orçamento.

**Como excluo um orçamento?** Primeiro cancele (**Confirmar cancelamento**) e depois use **🗑 Excluir orçamento**.

**O que é o status do orçamento?** É o acompanhamento comercial: Rascunho, Enviado ao paciente, Aprovado, Em espera, Recusado e Concluído. Não confunda com Pago.

**Como lanço uma despesa avulsa?** Fluxo de Caixa > **+ Lançamento**, tipo **Saída**, descrição, valor, data e, se quiser, centro de custo e forma de pagamento.

**Como cadastro o aluguel que se repete todo mês?** Contas a Pagar > **+ Nova conta**, tipo **Recorrente mensal**, valor mensal, 1º vencimento e **Recorrente até**. Sem data final o sistema cria 12 meses.

**Como cadastro um equipamento em 12 vezes?** Contas a Pagar > **+ Nova conta**, tipo **Parcelada**, informe o valor de **cada parcela** e **Nº de parcelas** 12.

**Não consigo alterar o valor de um lançamento do caixa.** Lançamentos gerados por venda, pagamento ou conta a pagar só permitem trocar descrição, centro de custo e forma de pagamento. Corrija na origem.

**O que são centros de custo?** Categorias de despesa e receita (Aluguel, Marketing, Salários...). Ajudam a ver para onde o dinheiro vai e a classificar para a contabilidade.

**O que é o plano de contas e preciso dele?** É a lista de contas contábeis. Só é necessário se você envia relatórios ao contador. Use **Usar modelo padrão para clínicas** para começar rápido.

**Onde vejo quanto a clínica faturou no mês?** Menu Relatórios > Financeiro, ajuste **De** e **Até**. O Dashboard mostra o mês corrente.

**Como mando os dados para o contador?** Relatórios contábeis: **Lançamentos (CSV)**, **Resumo por conta (CSV)** e **Plano de contas (CSV)**. Também há a planilha e o PDF do relatório Financeiro.

**Existe comissão para profissionais?** Não. O relatório Financeiro mostra receita e margem por profissional, mas não calcula comissão.

**Não vejo o menu Financeiro.** Falta a permissão de ver orçamentos (finance.sales.read). Peça ao responsável em Papéis e Permissões.

## Limitações conhecidas
- Não existe tela para editar ou excluir o cadastro de formas de pagamento: a lista é fixa.
- O botão de estorno separado não aparece no detalhe do orçamento; o estorno é feito editando o pagamento e escolhendo o status Estornado.
- A geração automática de agendamentos a partir de um orçamento aprovado não tem botão na tela de detalhe; use o link Agendar.
- Ao registrar um pagamento parcelado já como Pago, o lançamento de entrada no caixa é criado uma vez, com o valor total. Marcar depois uma parcela pendente como Paga editando o pagamento não cria lançamento no caixa; confira o Fluxo de Caixa e, se faltar, crie um lançamento manual de Entrada.

## Mapa de rotas
- Orçamentos: /finance/sales, /finance/sales/view, /finance/sales/create, /finance/sales/items/add, /finance/sales/items/remove, /finance/sales/budget-status, /finance/sales/cancel, /finance/sales/delete, /finance/sales/send, /finance/sales/print, /finance/sales/export.csv, /finance/sales/generate-appointments, /finance/sales/print-payment-methods, /finance/sales/patients/search-json.
- Pagamentos: /finance/payments/create, /finance/payments/update, /finance/payments/delete, /finance/payments/refund.
- Caixa: /finance/cashflow, /finance/cashflow/edit, /finance/cashflow/update, /finance/entries/create, /finance/entries/delete.
- Contas a pagar: /finance/accounts-payable, /finance/accounts-payable/create, /finance/accounts-payable/pay, /finance/accounts-payable/delete.
- Centros de custo: /finance/cost-centers, /finance/cost-centers/create, /finance/cost-centers/edit, /finance/cost-centers/update, /finance/cost-centers/status, /finance/cost-centers/delete.
- Plano de contas: /finance/chart-of-accounts, /finance/chart-of-accounts/create, /finance/chart-of-accounts/edit, /finance/chart-of-accounts/update, /finance/chart-of-accounts/status, /finance/chart-of-accounts/delete, /finance/chart-of-accounts/apply-template, /finance/chart-of-accounts/export.csv.
- Relatórios: /finance/reports, /finance/reports/export.csv, /finance/reports/export.pdf, /finance/accounting, /finance/accounting/entries.csv, /finance/accounting/summary.csv.
- Link público do orçamento: /pub/budget.
