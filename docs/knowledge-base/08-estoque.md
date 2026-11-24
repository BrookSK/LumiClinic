---
title: Estoque, materiais, movimentações, inventário, alertas e relatório
routes:
  - /stock/materials
  - /stock/categories
  - /stock/units
  - /stock/movements
  - /stock/inventory
  - /stock/alerts
  - /stock/reports
---

# Estoque

## Visão geral
O grupo **Estoque** do menu lateral tem **Materiais** (cadastro, movimentações, categorias e unidades em abas) e **Alertas**. O **Inventário** abre pela tela de movimentações. O relatório de estoque fica em Menu > Relatórios > Estoque.

- O grupo aparece para quem tem a permissão de ver materiais (stock.materials.read). O item **Alertas** exige stock.alerts.read.
- O estoque baixa sozinho quando um atendimento é concluído e o serviço tem materiais vinculados (veja 06-servicos-profissionais, **Vínculo com estoque**). Quando não há saldo suficiente a baixa automática não acontece e o sistema avisa "Estoque insuficiente para baixa automática.".
- Tipos de movimentação: **Entrada**, **Saída**, **Ajuste (define estoque)**, **Perda** e **Vencimento**.
- Não existe tela para editar ou excluir um material depois de criado. Para corrigir quantidade use uma movimentação de **Ajuste** ou um **Inventário**.

## Materiais
<!-- perm: stock.materials.read (ver), stock.materials.manage (cadastrar, categorias, unidades) -->
**Onde fica:** Menu > Estoque > Materiais (/stock/materials). A tela tem quatro abas: **📦 Materiais**, **↕ Movimentações**, **🏷 Categorias** e **📏 Unidades**. No topo há o link **Ver alertas**.

**Lista de materiais**
Colunas: Material, Categoria, Unidade, Estoque, Mínimo, Custo, Validade e Status (**Ativo** ou **Inativo**). Quando o estoque está zerado aparece **⚠ zerado**, e quando está abaixo do mínimo aparece **⚠ baixo**.

**Cadastrar material**
1. Clique em **+ Novo material**.
2. Preencha:
| Campo | Obrigatório | Observação |
|---|---|---|
| Nome | Sim | Ex.: Botox 100U, Ácido Hialurônico... |
| Categoria | Sim | Escolha uma categoria já cadastrada |
| Unidade | Sim | Escolha uma unidade já cadastrada (un, ml, g...) |
| Estoque mínimo | Não | Quando o estoque chegar nessa quantidade o sistema gera um alerta. Padrão 0 |
| Estoque inicial | Não | Quantidade que já existe hoje. Padrão 0 |
| Custo unitário (R$) | Não | Usado para calcular o custo de cada baixa |
| Validade (opcional) | Não | Data de validade. Não pode ser uma data que já passou |
3. Clique em **Salvar** (ou **Cancelar**).
Erros: "Nome obrigatório.", "Nome e unidade são obrigatórios.", "Categoria é obrigatória.", "Categoria inválida.", "Unidade inválida.", "Nome muito longo.", "Validade inválida.", "Validade não pode estar vencida.".

**Cadastre antes as categorias e as unidades.** Sem elas os campos Categoria e Unidade ficam vazios e o material não é salvo.

## Categorias e unidades
<!-- perm: stock.materials.manage -->
**Onde fica:** abas **🏷 Categorias** e **📏 Unidades** de /stock/materials (também em /stock/categories e /stock/units).
- **Categoria**: digite o **Nova categoria** (ex.: Injetáveis, Descartáveis...) e clique em **Criar**. Para remover use o **✕** (ou **Excluir**).
- **Unidade**: informe o **Código** (un, ml, g...; obrigatório) e, se quiser, o **Nome (opcional)** (Unidade, Mililitro...) e clique em **Criar**. Remova com o **✕**.
Erros: "Código obrigatório.", "Código muito longo.", "Nome obrigatório.".

## Movimentações
<!-- perm: stock.movements.read (ver), stock.movements.create (registrar) -->
**Onde fica:** aba **↕ Movimentações** de Materiais, ou /stock/movements (também aparece no relatório de estoque).

**Filtros:** **De**, **Até** e **Filtrar**. Colunas: Data, Material, Tipo, Qtd, Custo total, Motivo e Obs. Paginação com **Anterior** e **Próxima**.

**Registrar movimentação**
1. Clique em **+ Nova movimentação** (ou use o formulário **Nova movimentação**).
2. Escolha o **Material** (aparece o estoque atual entre parênteses).
3. Escolha o **Tipo**:
| Tipo | Efeito |
|---|---|
| Entrada | Soma a quantidade ao estoque (compra, reposição) |
| Saída | Subtrai a quantidade (uso, consumo) |
| Ajuste (define estoque) | A quantidade informada passa a ser o novo estoque, não é somada |
| Perda | Subtrai e registra o motivo da perda |
| Vencimento | Subtrai material que venceu |
4. Informe a **Quantidade** (obrigatória).
5. Em perdas escolha o **Motivo perda**: **Vencimento**, **Quebra**, **Contaminação** ou **Erro operacional**.
6. Se quiser, preencha **Observações**.
7. Clique em **Registrar** (ou **Cancelar**).
Erros: "Material inválido.", "Tipo inválido.", "Quantidade inválida." e "Estoque insuficiente para a operação." (a saída ou perda é maior que o saldo; o estoque nunca fica negativo).

## Inventário
<!-- perm: stock.movements.read (ver), stock.movements.create (criar, contar e confirmar) -->
**Onde fica:** /stock/inventory (links **Inventário** no topo da tela de movimentações). A página tem o formulário **Novo inventário** e a lista **Inventários** (#, Status, Criado em, Confirmado em, Obs).

**Como fazer uma contagem**
1. Em **Novo inventário** escreva uma observação (ex.: Contagem mensal) e clique em **Criar**. Ele nasce como **Rascunho**.
2. Clique em **Abrir** na linha do inventário (/stock/inventory/edit).
3. Em **Adicionar item** escolha o **Inventário**, o **Material** e, se já souber, a **Qtde contada (opcional)**. Clique em **Adicionar**. O sistema guarda a **Qtde sistema (snapshot)** do momento.
4. Na tabela **Contagem** digite a **Qtde contada** de cada material e clique em **Salvar contagem**. As colunas **Divergência** e **Custo (delta)** mostram a diferença entre o sistema e a contagem.
5. Quando terminar clique em **Confirmar e ajustar estoque** (seção **Confirmar inventário**). O sistema cria os ajustes de estoque e o inventário vira **Confirmado**.
Status possíveis: **Rascunho**, **Confirmado**, **Cancelado**. Erros: "Inventário inválido.", "Inventário já confirmado.", "Inventário não está em rascunho.", "Inventário já gerou movimentos.", "Material inválido.".
Depois de confirmado a contagem fica só para consulta.

## Alertas de estoque
<!-- perm: stock.alerts.read -->
**Onde fica:** Menu > Estoque > Alertas (/stock/alerts).
- Campo **Dias** (1 a 365, padrão 30) e **Atualizar**: define a janela para "vencendo".
- Cartões: **Sem estoque**, **Estoque baixo**, **Vencidos** e **Vencendo em N dias**, e a lista de cada grupo (🚨 Sem estoque (ruptura), ⚠ Estoque baixo, ❌ Vencidos, ⏰ Vencendo em até N dias).
- Estoque baixo é quando o material tem estoque mínimo maior que zero e o estoque atual está abaixo dele.
- Quando não há nada: "Tudo em ordem. Nenhum alerta de estoque no momento.".

## Relatório de estoque
<!-- perm: stock.reports.read -->
**Onde fica:** Menu > Relatórios > Estoque (/stock/reports).
- Filtros **De**, **Até** e **Atualizar**, e **Dias para validade próxima** (1 a 365).
- Exportações: **📊 Planilha** (/stock/reports/export.csv) e **📄 PDF** (/stock/reports/export.pdf).
- Abas internas: **Resumo**, **Movimentações** e **Alertas**.
- **Resumo**: **Custo total saídas** (materiais consumidos), **Custo de perdas** (quebras, contaminação etc.), **Custo vencimento**, **Custo por sessões** (consumo em atendimentos); tabelas **Consumo por material**, **Perdas** (por motivo e por material), **Consumo por serviço** e **Consumo por profissional**. Motivos de perda mostrados: Vencimento, Quebra, Contaminação, Erro operacional e Uso interno.
- **Alertas**: **Estoque zerado** e **Estoque baixo** com a coluna **Sugestão compra** (quanto falta para chegar ao mínimo), **Validade vencida** e **Validade próxima**.
- **Movimentações**: lista com botão **+ Nova movimentação** (mesmos campos acima).

## Perguntas frequentes
**Como cadastro um material?** Estoque > Materiais > **+ Novo material**. Antes crie uma categoria e uma unidade nas abas Categorias e Unidades.

**O campo Categoria ou Unidade está vazio.** Cadastre primeiro em **🏷 Categorias** e **📏 Unidades**.

**Como dou entrada em um produto que comprei?** Movimentações > **+ Nova movimentação**, escolha o material, tipo **Entrada** e a quantidade.

**Como corrijo uma quantidade errada?** Use o tipo **Ajuste (define estoque)** com a quantidade correta, ou faça um **Inventário** e confirme.

**O que é "Ajuste"?** A quantidade digitada vira o novo estoque. Diferente de Entrada, que soma.

**Como registro um frasco que quebrou?** Movimentação tipo **Perda**, motivo **Quebra**.

**Por que o estoque não baixou depois do atendimento?** O serviço precisa ter materiais vinculados em Configurações > Serviços > Vínculo com estoque. Se o saldo era menor que o necessário, a baixa não acontece. Veja também a mensagem na tela de conclusão do atendimento.

**Como sei o que está acabando?** Estoque > Alertas, ou o relatório de estoque, aba Alertas, com a **Sugestão compra**.

**Como vejo quanto gastei de material por serviço?** Relatórios > Estoque > Resumo, tabela **Consumo por serviço**. O custo usa o **Custo unitário** cadastrado.

**Aparece "Validade não pode estar vencida.".** Não é possível cadastrar um material com validade no passado. Deixe a validade em branco ou informe uma data futura.

**Posso editar ou excluir um material?** Não existe essa tela. Para tirar o estoque use Ajuste para zero.

**Quem pode mexer no estoque?** stock.materials.manage cadastra, stock.movements.create registra movimentações e inventário. O ajuste é feito em Papéis e Permissões.

## Limitações conhecidas
- Não há edição nem exclusão de materiais depois de cadastrados.
- A exclusão de categorias e unidades pela aba só remove o item da lista; materiais antigos mantêm o nome.
- Materiais maiores que o saldo são ignorados ao salvar um prontuário (a baixa só acontece com saldo suficiente).

## Mapa de rotas
- Materiais: /stock/materials, /stock/materials/create.
- Categorias e unidades: /stock/categories, /stock/categories/create, /stock/categories/delete, /stock/units, /stock/units/create, /stock/units/delete.
- Movimentações: /stock/movements, /stock/movements/create.
- Inventário: /stock/inventory, /stock/inventory/create, /stock/inventory/edit, /stock/inventory/items/add, /stock/inventory/update, /stock/inventory/confirm.
- Alertas: /stock/alerts.
- Relatório: /stock/reports, /stock/reports/export.csv, /stock/reports/export.pdf.
