---
title: Marketing, automação de campanhas, WhatsApp da clínica, templates, logs e e-mails
routes:
  - /marketing/calendar
  - /marketing/automation/segments
  - /marketing/automation/campaigns
  - /marketing/automation/logs
  - /settings/whatsapp
  - /whatsapp-templates
  - /whatsapp-logs
  - /settings/tuquinha
---

# Marketing, WhatsApp e comunicação com o paciente

## Visão geral
O sistema se comunica com o paciente por **WhatsApp** (lembretes, confirmação, orçamentos, campanhas) e por **e-mail** (orçamento, documentos, anamnese, acesso ao portal, campanhas). Tudo sai com o nome da clínica.

- O menu **Marketing** tem **Agenda de Marketing** (calendário de conteúdos), **Automação - Segmentos** e **Automação - Campanhas**. O histórico das campanhas fica em **Logs de envio** (link dentro das telas de automação).
- A conexão do WhatsApp, os templates e os logs de WhatsApp ficam em **Configurações > Geral** (cartões **WhatsApp**, **Templates WhatsApp**, **Logs WhatsApp**).
- Para o paciente receber mensagens por WhatsApp ele precisa ter telefone e a marcação **Receber lembretes por WhatsApp** (opt-in) no cadastro.

## Conectar o WhatsApp da clínica (QR Code)
<!-- perm: settings.read (ver), settings.update (conectar, testar, remover) -->
**Onde fica:** Menu > Configurações > Geral > cartão **WhatsApp** (/settings/whatsapp).

A página mostra o título **WhatsApp** ("Conecte o WhatsApp da clínica para enviar lembretes, confirmações e campanhas automaticamente.") e uma etiqueta de situação: **Configurado** (✅) ou **Não configurado** (⚠).

**Caso 1: a plataforma já deixou o WhatsApp pronto para a sua clínica (o mais comum)**
1. Clique em **Conectar WhatsApp**. Aparece o cartão **Escaneie o QR Code**.
2. No celular da clínica abra o WhatsApp > **Menu (⋮)** > **Aparelhos conectados** > **Conectar aparelho** e escaneie o QR Code da tela.
3. A tela confere a conexão a cada poucos segundos. O texto da situação muda: **Conectando...**, **Conectado ✓**, **Desconectado**, **Recusado**, **Timeout** ou **Aguardando**.
4. O QR Code é renovado automaticamente a cada cerca de 25 segundos enquanto não conecta. Se aparecer um **Código de pareamento**, é uma alternativa ao QR Code: no WhatsApp escolha conectar com código e digite o código mostrado. O contador **Tentativa: N** mostra quantas renovações já aconteceram.
5. Depois de conectado aparece o botão **Desconectar** (use para trocar de número). Ao desconectar, a tela já gera um novo QR Code.
6. Em **Detalhes técnicos** (link pequeno) aparece o nome da **Instância** da clínica, que identifica a conexão. Não precisa mexer nele.

**Caso 2: a tela pede a configuração manual**
Se a plataforma não deixou a conexão pronta, a página mostra os campos **Instância** (nome da instância) e a chave de acesso, que a equipe do sistema fornece. Preencha, clique em **Salvar** e depois em **Testar conexão**. O link pequeno **Remover configuração** > **Confirmar remoção** apaga os dados. Peça os dados à equipe de suporte, a clínica não deve inventá-los.

**Ferramentas** (cartão no fim da página)
- **Diagnosticar problemas**: roda verificações e mostra ✅ ou ❌ em: configuração, conexão, **Templates de lembrete** (se os modelos reminder_24h e reminder_2h existem e estão ativos) e **Falhas recentes (últimos 7 dias)**. Cada item tem um botão para abrir a tela relacionada.
- **Gerenciar templates**: abre /whatsapp-templates.
- **Ver logs de envio**: abre /whatsapp-logs.

**Boas práticas**
- Use um número dedicado da clínica e deixe o celular com internet.
- Se o QR Code expirar, ele é substituído sozinho. Se o celular ficar muito tempo desconectado, volte à tela e clique em **Conectar WhatsApp** de novo.
- Mensagens só saem se o status for **Conectado**.
Mensagens de erro que podem aparecer: "O WhatsApp ainda não foi configurado pelo administrador do sistema.", "O WhatsApp está configurado pelo administrador do sistema." (quando você tenta salvar a configuração manual mas a conexão já é da plataforma), "Falha ao preparar instância do WhatsApp. Tente novamente.", "QR Code gerado. Escaneie com o WhatsApp para conectar.", o aviso para informar a instância e a chave antes de salvar (só na configuração manual), "Falha ao testar WhatsApp. Verifique as credenciais e tente novamente." (as credenciais são da equipe do sistema, avise o suporte).

## Templates de WhatsApp
<!-- perm: settings.read, settings.update -->
**Onde fica:** Menu > Configurações > Geral > **Templates WhatsApp** (/whatsapp-templates).
- Lista de modelos com nome, situação (**Ativo** ou **Desativado**) e **Código**.
- **+ Novo template** (/whatsapp-templates/create): **Código identificador** (sem espaços, use underline, ex.: reminder_24h), **Nome do template** (ex.: Lembrete 24h antes) e **Mensagem**. Clique em **Criar template**.
- **Editar** (/whatsapp-templates/edit): Código, Nome, **Status** (**Ativo** ou **Desativado**) e Mensagem. **Salvar**.
- **Variáveis** que você pode usar na mensagem: {nome_paciente} (nome do paciente), {data} (data da consulta), {horario} (horário da consulta), {nome_clinica} (nome da clínica) e {link} (link rastreável das campanhas).
- Exemplo: "Olá {nome_paciente}, lembramos que sua consulta está marcada para {data} às {horario}. Clínica {nome_clinica}."
- Os lembretes automáticos usam os templates com os códigos **confirm_request**, **reminder_24h** e **reminder_2h**. Se um desses estiver desativado ou não existir, a mensagem não é enviada ("Template desativado.").

## Lembretes automáticos
Para cada agendamento futuro o sistema programa o pedido de confirmação e os lembretes de 24 horas e de 2 horas antes. Paciente sem telefone ou sem opt-in fica como **Ignorado**. Cancelamento, conclusão ou falta cancelam os lembretes pendentes. Veja detalhes em 03-agenda.

## Logs de WhatsApp
<!-- perm: settings.read -->
**Onde fica:** Menu > Configurações > Geral > **Logs WhatsApp** (/whatsapp-logs).
- Mostra o histórico de cada mensagem: Paciente, Template, **Status** (**Pendente**, **Enviando**, **Enviado**, **Falhou**, **Ignorado**, **Cancelado**), Agendado e Enviado.
- Filtros por status, template e datas.
- **Ver** abre o detalhe com a mensagem e a resposta. **Reenviar** tenta de novo. **Verificar status** confere se os lembretes dos agendamentos estão completos.
- Dica: **Ignorado** quase sempre quer dizer paciente sem telefone ou sem opt-in. **Falhou** costuma ser WhatsApp desconectado ou número inválido.

## E-mails da clínica
Os e-mails enviados aos pacientes e à equipe usam o mesmo visual, com o **nome da clínica no cabeçalho**:
| E-mail | Quando sai | Assunto |
|---|---|---|
| Orçamento | Botão **📧 Enviar E-mail** no orçamento | "Orçamento - nome da clínica" |
| Documento para assinar | Envio de documento pela ficha do paciente (opção e-mail) | "Documento para assinatura: nome do documento" |
| Anamnese | Ao confirmar o agendamento e no envio manual | "Preencha sua anamnese - nome da clínica" |
| Acesso ao Portal | Ao criar ou atualizar o acesso do paciente | "Seu acesso ao Portal do Paciente - nome da clínica" |
| Redefinição de senha do paciente | Esqueci minha senha no portal | "Redefinição de senha - Portal do Paciente" |
| Redefinição de senha da equipe | Esqueci minha senha no login | "Redefinição de senha - LumiClinic" |
| Cuidados pós-procedimento | Mensagem automática de orientações depois do atendimento | "Cuidados pos-procedimento - nome do procedimento" |
| Campanha de marketing | Campanha com canal E-mail | Assunto definido na campanha |
Em documentos, o nome do remetente é o da clínica e as respostas vão para o e-mail da clínica (cadastrado em Configurações > Clínica). Por isso mantenha o e-mail e o nome da clínica atualizados.
Se o paciente diz que não recebeu: peça para olhar a caixa de spam, confirme o e-mail no cadastro do paciente e tente reenviar.

## Agenda de Marketing (calendário de conteúdos)
<!-- perm: marketing.calendar.read (ver), marketing.calendar.manage (criar, editar, excluir) -->
**Onde fica:** Menu > Marketing > Agenda de Marketing (/marketing/calendar).
- Calendário do mês com navegação **← Anterior** e **Próximo →** e o contador "N conteúdos planejados".
- **+ Novo conteúdo**: **Data** (obrigatória), **Título** (obrigatório, ex.: Post sobre Botox, Reel de resultados...), **Tipo** (**Post**, **Story**, **Reel**, **Vídeo**, **Email**, **Anúncio**, **Outro**), **Status** (**Planejado**, **Produzido**, **Postado**), **Responsável (opcional)** (um usuário da clínica), **Cor** e **Notas (opcional)**. Clique em **Criar**.
- Clique em um item do calendário para abrir os detalhes. **Editar** (/marketing/calendar/edit) permite alterar Título, Data, Responsável, Tipo, Status (também **Cancelado**), Cor, Notas e **Links de referência** (botão **+ Adicionar link**, **✕** remove) e **Salvar alterações**.
- **Excluir este item**: **Confirmar exclusão** ("Esta ação não pode ser desfeita.").
- Erros: "Título é obrigatório.", "Data inválida.", "Cor inválida.", "Item inválido.".

### Integração com o Tuquinha (opcional)
<!-- perm: settings.update -->
**Onde fica:** Menu > Configurações > Geral > **Calendário Marketing** (/settings/tuquinha).
- Conecte a clínica ao Tuquinha colando a chave de integração gerada no painel dele e clicando em **Conectar** (depois vira **Atualizar chave** e aparece **✓ Conectado**).
- Escolha o **Mês** e use **⬇ Importar do Tuquinha** (traz os eventos do mês para o calendário local; os já importados são atualizados) ou **⬆ Enviar para o Tuquinha** (cria lá os eventos locais do mês que ainda não foram sincronizados).
- Se o sistema avisar que a chave do Tuquinha não está configurada, conecte a chave primeiro.

## Automação de marketing: segmentos
<!-- perm: marketing.automation.read (ver), marketing.automation.manage (criar e editar) -->
**Onde fica:** Menu > Marketing > Automação - Segmentos (/marketing/automation/segments). Abas no topo: **Segmentos**, **Campanhas**, **Logs de envio**.

Segmentos são grupos de pacientes filtrados por regras. Você cria o segmento e depois o usa em uma campanha.
- **+ Novo segmento**: **Nome do segmento** (ex.: Pacientes ativos com WhatsApp), **Status** (**Ativo** ou **Desativado**) e as regras de filtro **Aceitou WhatsApp**, **Tem telefone** e **Tem e-mail**. Clique em **Criar segmento**.
- Editar (/marketing/automation/segment/edit): **Nome**, **Status do segmento**, **Status do paciente** (**Ativo** ou **Inativo**) e as regras **Aceitou receber WhatsApp** (opt-in), **Tem telefone cadastrado** (obrigatório para campanhas de WhatsApp) e **Tem e-mail cadastrado** (obrigatório para campanhas de e-mail). Só entram pacientes que atendem a todas as regras marcadas. Clique em **Salvar segmento**.
- A tela mostra a **Audiência estimada** (quantidade de pacientes) e as **Regras ativas**. Confira sempre esse número antes de disparar.
- Erros: "Nome é obrigatório.", "Segmento inválido.".

## Automação de marketing: campanhas
<!-- perm: marketing.automation.read (ver), marketing.automation.manage (criar, editar, disparar) -->
**Onde fica:** Menu > Marketing > Automação - Campanhas (/marketing/automation/campaigns).

**Passo a passo**
1. Crie um segmento (se ainda não tem).
2. **+ Nova campanha**: **Nome da campanha**, **Canal de envio** (**WhatsApp** ou **E-mail**), **Quem recebe** (**Todos os pacientes** ou um segmento) e **Quando enviar?** (**Salvar como rascunho** ou **Agendar para uma data** com **Data e horário do envio**). Clique em **Criar campanha**.
3. Abra a campanha para configurar o conteúdo (/marketing/automation/campaign/edit).
   - Dados básicos: **Nome da campanha**, **Canal de envio**, **Quem recebe**, **Status** (**Rascunho** = ainda não envia; **Agendada** = envia na data definida; **Rodando** = em execução; também **Pausada**, **Concluída**, **Cancelada**) e o agendamento.
   - Conteúdo de **WhatsApp**: escolha o **Template de WhatsApp** (o texto vem do template; variáveis {nome_paciente} e {link}).
   - Conteúdo de **E-mail**: **Assunto do e-mail** e **Corpo do e-mail** (variáveis {nome_paciente} e {link}).
   - **Opções avançadas**: **Disparo automático por evento** (**Nenhum (manual)**, **Após consulta concluída**, **Após falta (no-show)**, **Após cancelamento**, **Após confirmação**) com **Atraso (minutos)** (ex.: 60 = envia 1 hora depois do evento) e **Link rastreável (opcional)** (o sistema gera um link único por paciente e nos logs você vê quem clicou).
4. Clique em **Salvar campanha**.
5. Para disparar na hora use **▶ Rodar agora** ("Pronto para disparar? Clique para enviar a campanha para todos os pacientes do segmento."). A mensagem "Enfileirada para execução." confirma. O envio é feito aos poucos pela fila do sistema.
- Na lista cada campanha mostra o status (Rascunho, Agendada, Rodando, Pausada, Concluída, Cancelada), o canal, a data agendada e a última execução.
- **Ver logs de envio desta campanha** abre os logs filtrados.
- Erros: "Campanha inválida.", "Canal inválido.", "Nome é obrigatório.".

## Logs de envio das campanhas
<!-- perm: marketing.automation.read (ver), marketing.automation.manage (reenviar) -->
**Onde fica:** Menu > Marketing > Automação > aba **Logs de envio** (/marketing/automation/logs).
- Filtros: **Status**, **Buscar paciente** (nome, e-mail ou telefone) e **Campanha**, e **Filtrar**.
- Colunas: Paciente, Campanha, Canal, Status, Erro e Data. Status possíveis: **Na fila**, **Processando**, **Enviado**, **Falhou**, **Entregue**, **Lido**, **Clicou**, **Ignorado** e **Cancelado**.
- O botão **Reenviar** tenta de novo as mensagens com falha. Erro "Log inválido.".
- Mensagem "Nenhum log encontrado." quando o filtro não traz resultados.

## Perguntas frequentes
**Como conecto o WhatsApp da clínica?** Configurações > Geral > WhatsApp > **Conectar WhatsApp**, escaneie o QR Code com o celular da clínica (WhatsApp > Aparelhos conectados > Conectar aparelho). Aguarde aparecer **Conectado ✓**.

**O QR Code não aparece.** Clique em **Conectar WhatsApp**. Se a tela diz que o WhatsApp não foi configurado, avise a equipe de suporte.

**O QR Code expirou.** Ele se renova sozinho a cada cerca de 25 segundos. Se não renovou, clique em **Conectar WhatsApp** novamente.

**Troquei de celular, como reconecto?** Clique em **Desconectar** e depois em **Conectar WhatsApp** para gerar um novo QR Code com o novo aparelho.

**O WhatsApp aparece Desconectado.** O celular perdeu a sessão. Conecte de novo pelo QR Code. Enquanto estiver desconectado, lembretes e orçamentos por WhatsApp não saem.

**Como sei se uma mensagem foi enviada?** Configurações > Geral > Logs WhatsApp. Procure o paciente e veja o status.

**Mensagem com status Ignorado.** O paciente está sem telefone ou sem a marcação **Receber lembretes por WhatsApp**.

**Como mudo o texto do lembrete?** Configurações > Geral > Templates WhatsApp > **Editar** no template reminder_24h ou reminder_2h.

**Posso desligar os lembretes?** Desative os templates (Status **Desativado**). A mensagem não é enviada enquanto o template estiver desativado.

**Como faço uma campanha de WhatsApp?** Marketing > Automação - Segmentos (crie o público), depois Automação - Campanhas (crie a campanha, escolha o template) e **▶ Rodar agora** ou agende.

**A campanha não enviou para ninguém.** Confira a audiência do segmento, se os pacientes têm telefone e opt-in e se o WhatsApp está conectado. Veja os motivos em Logs de envio.

**Como sei quem clicou no link?** Preencha **Link rastreável (opcional)** na campanha e veja o status **Clicou** nos logs.

**Como programo um envio automático depois da consulta?** Na campanha, em **Opções avançadas**, escolha **Após consulta concluída** e o **Atraso (minutos)**.

**Para que serve a Agenda de Marketing?** Para planejar posts, stories, reels e anúncios por dia, com responsável e status. Não envia nada aos pacientes.

**Não vejo o menu Marketing.** Faltam as permissões marketing.calendar.read e marketing.automation.read.

## Limitações conhecidas
- Nas regras do segmento, desmarcar **Aceitou WhatsApp** ou **Tem telefone** pode não desligar a regra (o padrão do sistema é ligado). Confira o quadro **Regras ativas** e a **Audiência estimada** na tela de edição.
- Não existe tela de conversa. O sistema envia mensagens, mas as respostas dos pacientes chegam apenas no celular conectado.

## Mapa de rotas
- WhatsApp: /settings/whatsapp, /settings/whatsapp/connect, /settings/whatsapp/connect-reset, /settings/whatsapp/connect-json, /settings/whatsapp/status-json, /settings/whatsapp/disconnect, /settings/whatsapp/test, /settings/whatsapp/clear, /settings/whatsapp/diagnose (ações Conectar, Desconectar, Testar conexão, Diagnosticar e Remover configuração), /whatsapp-templates, /whatsapp-templates/create, /whatsapp-templates/edit, /whatsapp-logs.
- Calendário: /marketing/calendar, /marketing/calendar/create, /marketing/calendar/edit, /marketing/calendar/update, /marketing/calendar/delete, /marketing/calendar/tuquinha-config, /marketing/calendar/tuquinha-sync, /settings/tuquinha.
- Segmentos: /marketing/automation/segments, /marketing/automation/segment/create, /marketing/automation/segment/edit, /marketing/automation/segment/update.
- Campanhas: /marketing/automation/campaigns, /marketing/automation/campaign/create, /marketing/automation/campaign/edit, /marketing/automation/campaign/update, /marketing/automation/campaign/run.
- Logs: /marketing/automation/logs, /marketing/automation/log/retry.
- Link rastreável: /m/click (aberto pelo paciente).
