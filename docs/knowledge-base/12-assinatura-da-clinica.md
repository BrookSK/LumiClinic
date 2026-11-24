---
title: Assinatura da clínica, plano, cobranças, bloqueio por pendência e troca de plano
routes:
  - /billing/subscription
  - /billing/subscription/checkout
---

# Assinatura da clínica

## Visão geral
A página **Minha assinatura** mostra o plano da clínica, a situação da cobrança, os limites do plano e o histórico de cobranças. Também é onde se troca de plano, se paga com cartão e se cancela.

- **Só o dono (papel Owner) acessa.** O item **Assinatura** do menu aparece apenas para ele. Outros usuários que abrirem o endereço veem "Acesso negado.". Se você não é o dono e a clínica está bloqueada por assinatura, peça ao dono para regularizar.
- Esta página não mostra dados de infraestrutura nem de plataforma. Fale com a equipe de suporte se precisar de algo que não está aqui.

## Minha assinatura
<!-- perm: papel Owner -->
**Onde fica:** Menu > Assinatura (/billing/subscription).

**Bloco do plano**
| Informação | O que significa |
|---|---|
| Plano | Nome do plano contratado |
| Valor | Preço mensal (aparece "/mês"). Se há desconto de clínica piloto, aparece também **Desconto piloto** com o valor com desconto |
| Status | **Teste** (período de avaliação), **Ativa**, **Em atraso**, **Suspensa** ou **Cancelada** |
| Período vencido em ... | A data em que o ciclo venceu |
| Aguardando renovação automática | A renovação está em andamento |
| Até ... | Data em que o período atual termina |

**Limites do plano**
- **Transcrição de áudio**: **Não incluso** ("Não disponível no seu plano atual."), **Ilimitado** ou o uso do mês no formato usado / limite (em minutos). Quando acaba aparece "Limite atingido. Faça upgrade para continuar.". Caso contrário aparece "N restantes este mês".
- Cada plano também limita **Usuários** e **Pacientes** (ou Ilimitado). Ao atingir o limite o sistema avisa "Limite de usuários do plano atingido." ou "Limite de pacientes do plano atingido.". O armazenamento de arquivos também tem limite: "Limite de armazenamento do plano atingido.".

**Desconto de clínica piloto (quando existir)**: faixa "Condição exclusiva para clientes piloto" com o percentual e a duração do desconto. O desconto vale para o **primeiro plano contratado**. Se a clínica mudar de plano, perde o benefício, e ele não volta ao retornar para o plano anterior. Ao fim do período o plano passa a ser cobrado pelo valor normal.

**Downgrade agendado**: quando você escolhe um plano menor, aparece "Downgrade agendado: seu plano será alterado para ... em (data) (próximo ciclo de cobrança)". O botão **Cancelar downgrade** desfaz ("Downgrade cancelado. Você permanece no plano atual.").

## Planos disponíveis e troca de plano
**Planos disponíveis**: cartões com nome, valor por mês, transcrição, usuários e pacientes. O plano atual aparece como **Plano atual**. Nos demais clique em **Selecionar**.
1. Clique em **Selecionar** no plano desejado. Abre o painel **Assinar plano** com um aviso (e, para clínica piloto, "ao trocar de plano sua clínica perde o desconto").
2. Preencha os **Dados do cartão de crédito** (obrigatórios): **Nome no cartão**, **CPF do titular**, **Número do cartão**, **Mês**, **Ano**, **CVV**, **CEP do titular**, **Número do endereço** e **Celular (opcional)**. Os dados são enviados com segurança diretamente ao processador de pagamento e o sistema não guarda os dados do cartão.
3. Clique em **Confirmar assinatura** (ou **Cancelar**).
- **Upgrade (plano maior)**: o sistema leva ao pagamento. A tela **Checkout - Alterar plano** (/billing/subscription/checkout) mostra o plano selecionado e o **Valor a cobrar agora**, com os campos do cartão e o botão **Pagar e alterar plano**. A cobrança é feita por cartão de crédito e o plano só é efetivado depois da confirmação do pagamento. Mensagens: "Pagamento aprovado e plano alterado.", "Pagamento não aprovado. Status: ...".
- **Downgrade (plano menor)**: fica agendado para o próximo ciclo de cobrança ("Downgrade agendado para o próximo ciclo.").
- Se o plano escolhido já é o atual: "Plano já está selecionado.". Se o plano é inválido: "Plano inválido.".

## Cobranças
A tabela **Cobranças (N)** lista Vencimento, Valor, Status e Pago em, com os links **Fatura** e **Boleto** de cada cobrança. Para pagar um boleto pendente abra o link **Boleto**. Para ver o comprovante abra **Fatura**.

## Como atualizar o cartão
Não existe uma tela só para trocar o cartão. Para atualizar a forma de pagamento:
1. Abra Menu > Assinatura.
2. Clique em **Selecionar** no plano atual ou em outro plano e preencha os campos do cartão no painel **Assinar plano**.
3. Confirme. Se aparecer erro de pagamento, confira número, validade, CVV, CPF e CEP do titular, e tente de novo.
Se continuar sem funcionar, fale com a equipe de suporte informando o horário da tentativa e a mensagem exibida (nunca envie o número do cartão).

## Cancelar a assinatura
Na seção **Cancelar assinatura**: "O cancelamento encerra sua assinatura ao fim do período atual. Você perderá acesso ao sistema." Clique em **Confirmar cancelamento**. A mensagem "Assinatura cancelada." confirma. Pense bem: depois do fim do período o acesso é bloqueado.

## Bloqueio por pendência (tela Acesso bloqueado)
Quando a assinatura está vencida ou pendente, qualquer tela do sistema mostra **Acesso bloqueado** ("O acesso ao sistema foi temporariamente bloqueado porque a assinatura da sua clínica está pendente ou vencida."). Nela há **Ver minha assinatura**, botões de contato com o suporte (WhatsApp e e-mail, quando cadastrados) e **Sair**.

**Quando o bloqueio acontece**
- **Teste** ou **Ativa**: bloqueia se o período de teste ou o período pago já passou da data de término.
- **Em atraso**, **Suspensa** ou **Cancelada**: há uma tolerância de **7 dias** a partir do início da pendência. Passados os 7 dias, o acesso é bloqueado.
- Páginas de login, assinatura, portal do paciente, links públicos e documentos para assinar continuam abertas durante o bloqueio.

**O que fazer**
1. O dono entra e abre **Ver minha assinatura**.
2. Pague o boleto pendente pelo link **Boleto** da cobrança em atraso, ou troque para pagamento por cartão em **Selecionar** (plano atual).
3. Depois da confirmação do pagamento o acesso é liberado. Pode levar alguns minutos. Saia e entre de novo.
4. Se pagou e continua bloqueado depois de uns 30 minutos, fale com a equipe de suporte informando o nome da clínica, a data e o valor do pagamento.

## Perguntas frequentes
**Quem pode ver a assinatura?** Somente o dono (Owner). O menu Assinatura não aparece para os outros papéis.

**Apareceu "Acesso bloqueado". O que faço?** O dono deve abrir **Ver minha assinatura** e regularizar o pagamento. Os demais usuários devem avisar o dono.

**Quanto tempo tenho depois que o pagamento atrasa?** A tolerância é de 7 dias para assinaturas em atraso, suspensas ou canceladas. Depois disso o acesso é bloqueado.

**Como troco de plano?** Menu Assinatura > **Selecionar** no plano desejado > **Confirmar assinatura**. Para plano maior, siga o pagamento por cartão. Para plano menor, a troca vale no próximo ciclo.

**Perdi o desconto de clínica piloto.** O desconto vale só para o primeiro plano contratado e some ao trocar de plano. Não é possível recuperar.

**Como pago o boleto?** Em Cobranças, clique em **Boleto** na linha da cobrança.

**Como atualizo o cartão?** Selecione o plano atual (ou outro) e preencha os dados do cartão no painel **Assinar plano**. Veja a seção Como atualizar o cartão.

**Atingi o limite de usuários.** Faça upgrade de plano ou arquive usuários que não usam mais o sistema (Configurações > Usuários).

**Atingi o limite de transcrição de áudio.** O limite é mensal. Faça upgrade para um plano com mais minutos ou aguarde o próximo mês.

**Posso cancelar a qualquer momento?** Sim, em **Cancelar assinatura**. O acesso termina ao fim do período já pago.

**Preciso informar o cartão novamente em cada mês?** Não. A cobrança mensal é automática depois que a forma de pagamento foi cadastrada.

## Limitações conhecidas
- Não há tela para trocar apenas o cartão sem passar pela seleção de plano.
- O botão que sincroniza a cobrança com o processador de pagamento não aparece na tela; se a cobrança parece desatualizada, fale com a equipe de suporte.

## Mapa de rotas
- /billing/subscription (página principal), /billing/subscription/change-plan, /billing/subscription/checkout, /billing/subscription/cancel, /billing/subscription/cancel-downgrade, /billing/subscription/ensure-gateway.
- Tela de bloqueio (402): aparece em qualquer rota protegida quando a assinatura está pendente.
