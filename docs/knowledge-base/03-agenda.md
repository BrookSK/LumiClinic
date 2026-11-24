---
title: Agenda, atendimento do dia e configurações de horários
routes:
  - /schedule
  - /schedule/ops
  - /schedule/queue
  - /schedule/reschedule
  - /schedule/logs
  - /schedule/complete-materials
  - /blocks
  - /schedule-rules
  - /clinic/working-hours
  - /clinic/closed-days
  - /a/confirm
  - /settings/google-calendar
  - /whatsapp-templates
  - /whatsapp-logs
---

# Agenda

## Visão geral da Agenda
<!-- perm: scheduling.read -->
**Onde fica:** Menu > Agenda > Agenda (/schedule).

A Agenda tem três visões, escolhidas nas abas **Dia**, **Semana** e **Mês** no topo da tela, e um botão **Operação** ao lado das abas (para quem tem scheduling.ops). A visão padrão ao abrir /schedule é a Semana.

**Importante: o botão Novo agendamento só existe na aba Dia.** Ele fica no topo à direita, ao lado de Operação, e só aparece para quem tem scheduling.create e não tem o papel Profissional. Nas abas **Semana** (a que abre primeiro) e **Mês** não existe botão Novo agendamento. Veja a seção Criar um agendamento.

**Filtros**
| Campo | Função |
|---|---|
| Data (aba Dia), Semana de (aba Semana) ou Mês de (aba Mês) | Dia de referência da visão |
| Profissional | Todos ou um profissional. Quem tem o papel Profissional vê sempre apenas a própria agenda e não vê esse filtro |
Na aba Dia os filtros ficam no cartão **Filtros** e o botão é **Aplicar filtros**. Nas abas Semana e Mês o botão é só **Aplicar**.

**Visão Dia**
- Cartão **Agendamentos**: tabela com Início, Fim, Paciente, Profissional, Serviço (com a bolinha da cor do serviço), Status e Ações, com paginação (Anterior / Próxima).
- Cartão **Bloqueios do dia**: aparece só quando há bloqueios no dia e lista horário e motivo.
- Cartão **Horários do dia**: grade com uma linha a cada 15 minutos, horários na coluna da esquerda. Clicar em uma linha vazia da grade abre o formulário Novo agendamento já com data e horário (só para quem tem scheduling.create e não tem o papel Profissional). Blocos tracejados "Bloqueado" são bloqueios; "Feriado/Recesso" marca dia fechado. Nesta grade cada agendamento aparece como "Agendamento #número", com horário, profissional e serviço.
- Clique em um agendamento na grade para abrir os **Detalhes do agendamento** (paciente, serviço, profissional, início, fim, status e observações). Se o status for Faltou aparece o aviso "Paciente faltou. Reagende para recuperar o atendimento." com o botão Reagendar.

**Visão Semana:** sete colunas (Dom a Sáb) com os horários na coluna da esquerda e linhas vazias de 15 minutos. Cada agendamento é um cartão colorido por status com o nome do paciente. Clicar no cartão abre uma janela chamada **Agendamento**, com Paciente, Serviço, Profissional, Início, Fim, Status e os botões de ação (Reagendar, Logs, Confirmar, Chegou (check-in), Iniciar atendimento, Atender, Concluir, Faltou, Reabrir, Cancelar). Cada botão só aparece quando faz sentido para o status. Dias sem atendimento mostram "Sem atendimento", dias livres "Dia livre" e feriados "Feriado/Recesso". **Esta visão não tem botão Novo agendamento: para agendar, clique em uma linha vazia da grade (veja Criar um agendamento).**

**Visão Mês:** calendário do mês com até 3 agendamentos por dia e, quando há mais, o link **+N mais...** (por exemplo "+2 mais...") que abre o dia. Clicar em um dia abre uma janela com **Abrir agenda do dia**, **+ Novo agendamento** e **Cancelar**. Clicar em um agendamento abre uma janela com Paciente, Serviço, Profissional, Data, Horário, Status e os botões **Ver na agenda do dia** e **Fechar**. Para confirmar, atender ou cancelar, use **Ver na agenda do dia**. Os botões **Anterior** e **Próximo** trocam de mês.

**Cores de status:** Agendado, Confirmado, Em atendimento, Concluído, Faltou e Cancelado têm cores próprias e um rótulo no cartão.

## Criar um agendamento
<!-- perm: scheduling.create -->
**Onde:** não há botão Novo agendamento na visão Semana (a que abre primeiro). Há três formas, todas para quem tem scheduling.create e não tem o papel Profissional:
- **Visão Semana ou Dia:** clique em uma linha vazia da grade, na coluna do dia e na linha do horário desejado. A janela **Novo agendamento** abre com o horário e o dia já escolhidos. Na Semana só dá para clicar em dias que têm horário de funcionamento e não são feriado.
- **Visão Dia:** também há o botão **Novo agendamento** no topo à direita, ao lado de Operação.
- **Visão Mês:** clique no dia e depois em **+ Novo agendamento** na janela que abre.

**Passo a passo**
1. Em **Paciente**, digite nome, e-mail ou telefone e escolha o paciente na lista (obrigatório). Se não encontrar, cadastre antes em Pacientes (ver 04-pacientes).
2. Escolha o **Serviço** (obrigatório). A duração vem do cadastro do serviço.
3. Escolha o **Profissional** (obrigatório).
4. O campo **Data** só existe na janela aberta pela visão Dia. Nas visões Semana e Mês não há campo Data: vale o dia em que você clicou. O campo **Horário** lista somente horários livres para aquele serviço, profissional e data e mostra "Selecione um serviço + profissional + data" enquanto falta algo. Se o serviço tem tempo de preparo antes ou depois, esse tempo também é reservado.
5. Opcional: **Observações (opcional)** e **Etapa do funil (opcional)**.
6. Clique em **Agendar**. Na visão Dia a agenda mostra "Agendamento criado. ID: ..." (nas visões Semana e Mês a mensagem é "Atualizado. ID: ..."). O status inicial é **Agendado**.

**Por que o campo Horário aparece vazio?** Um horário só é oferecido quando existe: (a) horário de funcionamento da clínica naquele dia da semana, (b) regra de atendimento do profissional nesse dia, (c) nenhum bloqueio, e (d) nenhum outro agendamento no mesmo intervalo. A grade anda de acordo com o **Intervalo (min)** da regra do profissional, com mínimo de 5 minutos. Veja as seções Horários de funcionamento, Regras de agenda e Bloqueios.

**Erros**
- "Serviço é obrigatório.", "Profissional é obrigatório.", "Paciente é obrigatório.", "Horário é obrigatório." (campo não preenchido).
- Se você não escolher o paciente na lista, a janela mostra "Selecione um paciente da lista." e não envia.
- "Conflito de horário." (outra pessoa reservou o mesmo horário, escolha outro)
- "Pacote inválido." ou "Pacote sem sessões disponíveis."
- "Etapa do funil inválida."

## Status do agendamento e ações
<!-- perm: scheduling.finalize para confirmar, atender, concluir e marcar Faltou; scheduling.update para check-in e reagendar; scheduling.cancel para cancelar -->
Na visão Dia, a última coluna da tabela (sem título) tem os botões **Reagendar** (scheduling.update), **Logs** (scheduling.logs) e **Ações**. **Ações** abre um pequeno menu com os demais botões. Na visão Semana não há esse menu: clique no cartão do agendamento e use os botões da janela **Agendamento**.

| Ação | Quando aparece | O que faz | Permissão |
|---|---|---|---|
| Confirmar | Status Agendado | Muda para Confirmado. Também agenda o envio da anamnese ao paciente (portal, WhatsApp e e-mail) | scheduling.finalize |
| Chegou (check-in) | Agendado, Confirmado ou Em atendimento sem check-in | Registra a hora de chegada. O profissional recebe uma notificação e o paciente entra na Fila de chegada | scheduling.finalize, ou scheduling.update para quem não e profissional |
| Iniciar atendimento | Agendado ou Confirmado sem início | Faz check-in automático se faltar, muda para Em atendimento e abre o novo registro de prontuário já preenchido | scheduling.finalize |
| Atender | Agendado, Confirmado ou Concluído | Muda para Em atendimento. Em um concluído aparece como **Reabrir** | scheduling.finalize |
| Concluir | Em atendimento | Abre a tela Finalizar sessão (materiais usados e observação) | scheduling.finalize |
| Faltou | Agendado, Confirmado ou Em atendimento | Marca como Faltou (no-show). O paciente não compareceu | scheduling.finalize |
| Cancelar | Visão Dia: sempre aparece no menu Ações. Visão Semana: só para Agendado, Confirmado ou Em atendimento | Muda para Cancelado | scheduling.cancel |

**Sequência normal:** Agendado > Confirmado > (Chegou) > Em atendimento > Concluído.
**Transições permitidas:** Agendado vai para Confirmado, Em atendimento, Cancelado ou Faltou. Confirmado vai para Em atendimento, Cancelado ou Faltou. Em atendimento vai para Concluído, Cancelado ou Faltou. Concluído só volta para Em atendimento (Reabrir). Cancelado e Faltou são finais: não é possível mudar de novo, então reagende criando ou ajustando outro horário.
Mensagem de erro para movimento fora das regras: "Transição de status inválida."

**Marcar Faltou:** use quando o paciente não compareceu. O agendamento fica com a marca Faltou, os lembretes pendentes de WhatsApp são cancelados e o aviso "Paciente faltou. Reagende para recuperar o atendimento." aparece nos detalhes. Faltas contam como no-show no Painel Gestor.

**Ao concluir o atendimento:** a tela Finalizar sessão baixa o estoque e, se o serviço estiver ligado a um pacote do paciente, consome uma sessão do pacote. Também pode enviar orientações de pós-atendimento ao paciente.

## Finalizar sessão (concluir atendimento)
<!-- perm: scheduling.finalize -->
**Onde fica:** /schedule/complete-materials, aberta ao clicar em **Concluir** (ou em **Finalizar atendimento** dentro do novo prontuário). O título da tela é escrito sem acento na tela: "Finalizar sessao".
1. Revise **Materiais padrao do servico** (escrito sem acento na tela): para cada material ligado ao serviço aparece a quantidade padrão, que você pode alterar. Se o serviço não tem materiais ligados aparece "Nenhum material padrao configurado para este servico."
2. Em **Materiais extras** clique em **+ Adicionar material**, escolha o material e preencha o campo **Qtd**. Use o × ao lado da linha para remover.
3. Em **Observacao** (sem acento na tela) descreva o que foi realizado (campo obrigatório na tela).
4. Clique em **✓ Finalizar sessao** (botão grande no fim da tela). O sistema ajusta o estoque, registra uma saída financeira "Custo de materiais" quando o custo é maior que zero, registra o procedimento realizado e muda o status para Concluído.
Erro "Somente atendimentos em andamento podem ser concluídos.": inicie o atendimento antes. Se faltar estoque, a conclusão continua e o aviso fica no registro do sistema.
O botão **Voltar**, no topo da tela, leva de volta à agenda.

## Reagendar
<!-- perm: scheduling.update -->
**Onde fica:** botão **Reagendar** na agenda: na linha do agendamento (visão Dia), na janela **Agendamento** (visão Semana, clique no cartão) ou nos Detalhes de um agendamento Faltou (/schedule/reschedule?id=...). Na visão Mês não há botão Reagendar: use **Ver na agenda do dia**.
1. A tela mostra o cartão **Agendamento atual** (Paciente, Serviço, Data/hora atual).
2. No cartão **Novo horário** escolha Serviço, Profissional, **Nova data** e **Novo horário** (só aparecem horários livres; antes de escolher a data o campo mostra "Selecione a data primeiro").
3. Clique em **Confirmar reagendamento**. A agenda mostra "Atualizado. ID: ...". **Cancelar** volta sem alterar, e **Voltar à agenda** (no topo) também.
Se aparecer "Sem permissão para reagendar." falta scheduling.update. Erros: "Conflito de horário.", "Agendamento inválido.". Agendamentos cancelados ou com Faltou não podem mudar de status, então reagende antes de cancelar quando quiser manter a vaga do paciente. Dica: se o botão Reagendar do histórico do paciente levar de volta à agenda, abra o agendamento direto pela Agenda e use Reagendar lá.

## Histórico (Logs) do agendamento
<!-- perm: scheduling.logs -->
**Onde fica:** botão **Logs** (/schedule/logs?appointment_id=...). Mostra o cartão do agendamento (Início, Fim, Status) e a tabela **Histórico (imutável)** com ID, Ação, Em, Usuário e IP. Sem registros aparece "Sem logs.". Registra criação, status, check-in, início e reagendamento. Botão **Forçar sync Google Calendar** enfileira de novo a sincronização desse agendamento. **Voltar** retorna à agenda.

## Operação da Agenda (recepção)
<!-- perm: scheduling.ops -->
**Onde fica:** Menu > Agenda > Operação da Agenda (/schedule/ops). Tela feita para a recepção conduzir o dia.
- **Filtros:** Data, Buscar paciente (nome), Profissional, com botões **Buscar** e **Limpar** (a tela também aceita filtro por categoria de serviço, serviço, CPF e faixa de horário pelo endereço).
- **Contadores:** Aguardando (agendados + confirmados), Em atendimento, Concluídos, Faltou / Cancelado.
- **Solicitações do portal:** pedidos de **Reagendamento** ou **Cancelamento** feitos pelo paciente no Portal. Cada linha tem Tipo (Reagendamento ou Cancelamento), Paciente, Agendamento (link para a agenda), Profissional, Observação e os botões **✓ Aprovar** e **✕ Rejeitar**. Aprovar um cancelamento cancela o agendamento. Aprovar um reagendamento marca o pedido como aprovado, mas você ainda precisa reagendar o horário na Agenda.
- **Lista de agendamentos do dia:** Horário, Paciente (com a marca **Chegou** quando houve check-in), Serviço, Profissional, Status e botões rápidos: **✓ Chegou**, **▶ Iniciar**, **✓ Concluir** e **Faltou**, conforme o status. O botão **Ver agenda** no topo volta para a Agenda.

## Fila de chegada
<!-- perm: scheduling.finalize -->
**Onde fica:** Menu > Agenda > Fila de chegada (/schedule/queue). Lista os pacientes que já fizeram check-in e ainda não iniciaram o atendimento (status Agendado, Confirmado ou Em atendimento). Cada cartão mostra paciente, serviço, profissional, horário e o tempo de espera ("Esperando"). Quem e profissional vê só a própria fila; owner e admin veem todas. Botão **▶ Iniciar atendimento** abre o prontuário. **↻ Atualizar** recarrega a lista (ela não se atualiza sozinha). Quando vazia mostra "Nenhum paciente aguardando".

## Bloqueios de agenda
<!-- perm: blocks.manage -->
**Onde fica:** Menu > Configurações > Agenda > Bloqueios (/blocks). Use para férias, manutenção, reuniões ou qualquer período sem atendimento, para a clínica toda ou um profissional.
1. Clique em **+ Novo bloqueio**.
2. Preencha **Quem** (Clínica inteira ou um profissional), **Início**, **Fim**, **Tipo** (Manual, Feriado ou Manutenção) e **Motivo** (obrigatório).
3. Clique em **Criar bloqueio**.
Filtros da lista: **De**, **Até**, **Profissional** e **Filtrar**. Mensagem vazia: "Nenhum bloqueio no período."
Erros: "Início e fim são obrigatórios.", "Data/hora inválida.", "Motivo é obrigatório.", "Fim deve ser após o início."
Importante: o bloqueio impede novos horários livres. Ele não cancela agendamentos que já existem no período, trate esses manualmente. Não há botão de excluir bloqueio na tela.

## Regras de agenda (horários de cada profissional)
<!-- perm: schedule_rules.manage -->
**Onde fica:** Menu > Configurações > Agenda > Regras (/schedule-rules).
1. Escolha o profissional em **Selecione o profissional**.
2. Clique em **+ Adicionar horário**.
3. Preencha **Dia** (domingo a sábado), **Início**, **Fim** e **Intervalo (min)**, depois **Adicionar**. O intervalo é a duração de cada horário oferecido (30 min = horários de 30 em 30 minutos).
4. Cada dia pode ter várias faixas (por exemplo manhã e tarde). Para remover uma faixa use o ✕ ao lado dela (pede confirmação "Excluir?"). Em cada dia, o botão de lápis ✏ (dica "Adicionar horário") abre o formulário já com o dia escolhido e o botão 🚫 (dica "Marcar como não atende") remove todas as faixas do dia.
Sem regra o profissional não tem horários livres. O horário do profissional vale dentro do horário de funcionamento da clínica.

## Horários de funcionamento da clínica
<!-- perm: clinics.read (ver), clinics.update (alterar) -->
**Onde fica:** Menu > Configurações > Agenda > Horários (/clinic/working-hours). Dias sem horário aparecem como **Fechado**.
1. **+ Adicionar horário**, escolha **Dia**, **Início**, **Fim** e **Adicionar**. Várias faixas no mesmo dia são permitidas.
2. Remova uma faixa com o botão de remover ao lado dela (dica "Remover horário"). Em cada dia, o lápis (dica "Adicionar horário para" e o nome do dia) abre o formulário com o dia escolhido e o botão de proibido (dica "Marcar como fechado") remove todas as faixas do dia.
Se o dia da clínica está fechado, nenhum profissional terá horário nele.

## Feriados e recesso
<!-- perm: clinics.read (ver), clinics.update (alterar) -->
**Onde fica:** Menu > Configurações > Agenda > Feriados (/clinic/closed-days). Nesses dias a agenda fica bloqueada automaticamente.
- **+ Adicionar feriado:** abre o formulário "Adicionar feriado/recesso" com **Data**, **Motivo** (ex.: Natal) e **Status** (Fechado ou Aberto), depois **Adicionar**.
- **Editar** (link na linha) abre o formulário "Editar feriado" com Data, Motivo e Status, botões **Salvar** e **Cancelar**. Use status Aberto para reabrir um feriado.
- **Remover** exclui o feriado.
- **🤖 Gerar feriados com IA** cria os feriados do ano automaticamente (precisa que a IA esteja disponível para a clínica). Confira a lista depois.
Erro: "Data é obrigatória."

## Confirmação pelo paciente (link)
<!-- perm: nenhuma (link publico) -->
**Onde fica:** /a/confirm?token=... O paciente recebe um link por mensagem. A página mostra Clínica, Data e Hora e dois botões: **Confirmar consulta** e **Cancelar consulta**. O link vale até 2 dias após o horário da consulta. Mensagens: "Link inválido ou expirado.", "Não é possível confirmar esta consulta.", "Não é possível cancelar esta consulta."

## Anamnese pelo link do agendamento
Quando um agendamento e confirmado, o sistema envia ao paciente o pedido de anamnese (usa o modelo definido em Configurações > Geral, campo Anamnese padrão). O paciente preenche em /a/anamnese. Veja 05-prontuários-e-atendimento.

## Lembretes por WhatsApp
<!-- perm: settings.update -->
- Para cada agendamento o sistema agenda três mensagens: **confirm_request** e **reminder_24h** (24 horas antes) e **reminder_2h** (2 horas antes), desde que o horário ainda esteja no futuro. Agendamentos cancelados, concluídos ou com Faltou cancelam os lembretes pendentes.
- Paciente sem telefone ou sem permissão de WhatsApp ("Receber lembretes por WhatsApp" desmarcado no cadastro) fica com a mensagem como **Ignorado**.
- **Templates de WhatsApp** (/whatsapp-templates): botão **+ Novo template**, modelos com **Código identificador**, **Nome do template** e **Mensagem** (ao editar, o Status é Ativo ou Desativado). Variáveis: {nome_paciente}, {data}, {horario}, {nome_clinica}, {link}.
- **Logs de WhatsApp** (/whatsapp-logs): histórico com Paciente, Template, Status (Pendente, Enviando, Enviado, Falhou, Ignorado, Cancelado), Agendado e Enviado. Filtros por status, template e datas. **Ver** abre o detalhe com payload e resposta. Na lista, **Reenviar** tenta novamente e **Verificar status** confere os lembretes. No detalhe os botões se chamam **Tentar enviar novamente** e **Verificar lembretes agora**.
- A conexão do WhatsApp da clínica fica em Configurações > Geral, no cartão **WhatsApp** (/settings/whatsapp).

## Google Calendar da clínica
<!-- perm: settings.read (ver), settings.update (conectar/desconectar) -->
**Onde fica:** Configurações > Geral, cartão **Google Calendar** (/settings/google-calendar).
1. Clique em **Conectar Google Calendar** e autorize a conta Google. Os agendamentos passam a ser sincronizados com a **agenda principal** dessa conta.
2. Depois de conectado aparece "Sincronização ativa", o link **Ver logs** e o botão **Desconectar Google Calendar**, que pede **Confirmar desconexão** (não apaga eventos já criados).
3. Os logs (/settings/google-calendar/logs) têm filtros de Status, Ação, Appointment ID, User ID e datas, e o cartão **Forçar sync** (informe o ID do agendamento e clique em **Enfileirar sync**).
Se a tela diz "Google Calendar ainda não está disponível", a integração ainda não foi habilitada na plataforma. Fale com o suporte.

## Mapa de rotas e ações da agenda
- Telas: /schedule (Dia, Semana, Mês), /schedule/ops, /schedule/queue, /schedule/reschedule, /schedule/logs, /schedule/complete-materials, /blocks, /schedule-rules, /clinic/working-hours, /clinic/closed-days, /settings/google-calendar, /settings/google-calendar/logs, /whatsapp-templates, /whatsapp-logs, /a/confirm.
- Ações dos botões: /schedule/create (Agendar), /schedule/cancel (Cancelar), /schedule/check-in (Chegou), /schedule/start (Iniciar atendimento), /schedule/status (Confirmar, Atender, Reabrir, Concluir, Faltou), /schedule/complete-materials (Finalizar sessão), /schedule/reschedule (Confirmar reagendamento), /schedule/ops/request/resolve (Aprovar ou Rejeitar pedido do portal), /schedule/gcal/force-sync (Forçar sync Google Calendar).
- Apoio da tela: /schedule/available (lista de horários livres do campo Horário) e /schedule/details (detalhes do agendamento no popup).
- Bloqueios: /blocks/create (Criar bloqueio). Regras: /schedule-rules/create (Adicionar), /schedule-rules/delete (x), /schedule-rules/delete-day (Marcar como fechado).
- Horários da clínica: /clinic/working-hours (Adicionar), /clinic/working-hours/delete (x), /clinic/working-hours/delete-day (Marcar como fechado).
- Feriados: /clinic/closed-days (Adicionar), /clinic/closed-days/update (Salvar), /clinic/closed-days/delete (Remover), /clinic/closed-days/ai (Gerar feriados com IA).
- WhatsApp: /whatsapp-templates/create e /whatsapp-templates/edit, /whatsapp-logs/show (Ver), /whatsapp-logs/retry-send (Reenviar), /whatsapp-logs/force-reconcile (Verificar status).
- Google Calendar: /settings/google-calendar/connect, /settings/google-calendar/callback (retorno do Google), /settings/google-calendar/disconnect, /settings/google-calendar/logs/force-sync.

## Perguntas frequentes
**Como marco que o paciente faltou?** Na Agenda (visão Dia), clique em **Ações** na linha do agendamento e depois em **Faltou**. Na visão Semana, clique no cartão do agendamento e use o botão **Faltou** da janela. Precisa da permissão scheduling.finalize. O status vira Faltou e não muda mais.

**Não consigo marcar um agendamento como concluído.** Só é possível concluir quem está Em atendimento. Use Iniciar atendimento (ou Atender) antes e depois Concluir. Quem concluiu precisa da permissão scheduling.finalize.

**Não aparecem horários livres ao agendar.** Verifique, nesta ordem: 1) Horários de funcionamento da clínica no dia (Configurações > Agenda > Horários), 2) Regras do profissional naquele dia (Regras), 3) Feriados e Bloqueios, 4) se o horário já está ocupado por outro agendamento.

**Como cancelo um agendamento?** Visão Dia: **Ações** na linha e depois **Cancelar**. Visão Semana: clique no cartão do agendamento e use **Cancelar** na janela. Precisa de scheduling.cancel. Cancelado não volta, então crie novo agendamento se precisar.

**Como reagendo?** Visão Dia: botão **Reagendar** na linha. Visão Semana: clique no cartão e use **Reagendar** na janela. Escolha novo serviço, profissional, data e horário e confirme.

**Não encontro o botão Novo agendamento.** Na visão Semana e na visão Mês ele não existe. Na visão Semana clique em uma linha vazia da grade, no dia e horário desejados. Na visão Dia o botão fica no topo à direita. Quem tem o papel Profissional não vê o botão nem consegue clicar na grade: a recepção ou um admin cria.

**O que é o check-in?** É o aviso de que o paciente chegou. Ele coloca o paciente na Fila de chegada e notifica o profissional.

**Como vejo quem alterou um agendamento?** Botão Logs (permissão scheduling.logs).

**O paciente não recebeu o lembrete.** Abra Configurações > Geral, cartão **Logs WhatsApp**, e veja o status. Ignorado significa sem telefone ou sem permissão de WhatsApp. Falhou permite Reenviar. Verifique também a conexão em /settings/whatsapp.

**Como bloqueio férias de um profissional?** Configurações > Agenda > Bloqueios > **+ Novo bloqueio**, escolha o profissional, Início e Fim e informe o motivo. Agendamentos já marcados nesse período precisam ser reagendados ou cancelados manualmente.

**Como cadastro os feriados do ano?** Configurações > Agenda > Feriados. Adicione um a um ou use Gerar feriados com IA.

**A semana começa no dia errado.** Ajuste em Configurações > Geral, campo **Início da semana**, e clique em **Salvar configurações**.
