---
title: Agenda, atendimento do dia e configuracoes de horarios
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

## Visao geral da Agenda
<!-- perm: scheduling.read -->
**Onde fica:** Menu > Agenda > Agenda (/schedule).

A Agenda tem tres visoes, escolhidas nos botoes **Dia**, **Semana** e **Mes** no topo, e um botao **Operacao** (para quem tem scheduling.ops). A visao padrao ao abrir /schedule e a Semana.

**Filtros (cartao Filtros)**
| Campo | Funcao |
|---|---|
| Data (ou Semana de / Mes de) | Dia de referencia da visao |
| Profissional | Todos ou um profissional. Quem tem o papel Profissional ve sempre apenas a propria agenda e nao ve esse filtro |
Clique em **Aplicar filtros** para atualizar.

**Visao Dia**
- Cartao **Agendamentos**: tabela com Inicio, Fim, Paciente, Profissional, Servico (com a bolinha da cor do servico), Status e Acoes, com paginacao (Anterior / Proxima).
- Cartao **Bloqueios do dia**: lista os bloqueios e feriados do dia.
- Cartao **Horarios do dia**: grade por profissional em linhas de 15 minutos. Clicar em um horario livre abre o formulario de novo agendamento ja com data, hora e profissional preenchidos (so para quem tem scheduling.create e nao tem o papel Profissional). Blocos tracejados "Bloqueado" sao bloqueios; "Feriado/Recesso" marca dia fechado.
- Clique em um agendamento na grade para abrir os **Detalhes do agendamento** (paciente, servico, profissional, inicio, fim, status e observacoes). Se o status for Faltou aparece o aviso "Paciente faltou. Reagende para recuperar o atendimento." com o botao Reagendar.

**Visao Semana:** colunas de 7 dias, mesmos cartoes coloridos por status e um painel de detalhes com todos os botoes de acao (Reagendar, Logs, Confirmar, Chegou (check-in), Iniciar atendimento, Atender, Concluir, Faltou, Reabrir, Cancelar). Dias sem atendimento mostram "Sem atendimento", dias livres "Dia livre" e feriados "Feriado/Recesso".

**Visao Mes:** calendario do mes com ate alguns agendamentos por dia e o link **+ mais...** que abre o dia. Clicar em um dia abre um popup com **Abrir agenda do dia**, **+ Novo agendamento** e **Cancelar**. Clicar em um agendamento abre o popup de detalhes. Setas **Anterior** e **Proximo** trocam de mes.

**Cores de status:** Agendado, Confirmado, Em atendimento, Concluido, Faltou e Cancelado tem cores proprias e um rotulo no cartao.

## Criar um agendamento
<!-- perm: scheduling.create -->
**Onde:** botao **Novo agendamento** (topo da Agenda) ou clique em um horario livre na grade do dia.

**Passo a passo**
1. Em **Paciente**, digite nome, e-mail ou telefone e escolha o paciente na lista (obrigatorio). Se nao encontrar, cadastre antes em Pacientes (ver 04-pacientes).
2. Escolha o **Servico** (obrigatorio). A duracao vem do cadastro do servico.
3. Escolha o **Profissional** (obrigatorio).
4. Escolha a **Data**. O campo **Horario** lista somente horarios livres para aquele servico e profissional. Se o servico tem tempo de preparo antes ou depois, esse tempo tambem e reservado.
5. Opcional: **Observacoes** e **Etapa do funil**. Se o paciente tem pacote de sessoes, aparece o campo de pacote, com sessoes restantes.
6. Clique em **Agendar**. A agenda mostra "Agendamento criado. ID: ..." e o status inicial e **Agendado**.

**Por que o campo Horario aparece vazio?** Um horario so e oferecido quando existe: (a) horario de funcionamento da clinica naquele dia da semana, (b) regra de atendimento do profissional nesse dia, (c) nenhum bloqueio, e (d) nenhum outro agendamento no mesmo intervalo. A grade anda de acordo com o **Intervalo (min)** da regra do profissional, com minimo de 5 minutos. Veja as secoes Horarios de funcionamento, Regras de agenda e Bloqueios.

**Erros**
- "Servico e obrigatorio.", "Profissional e obrigatorio.", "Paciente e obrigatorio.", "Horario e obrigatorio." (campo nao preenchido)
- "Conflito de horario." (outra pessoa reservou o mesmo horario, escolha outro)
- "Pacote invalido." ou "Pacote sem sessoes disponiveis."
- "Etapa do funil invalida."

## Status do agendamento e acoes
<!-- perm: scheduling.finalize para confirmar, atender, concluir e marcar Faltou; scheduling.update para check-in e reagendar; scheduling.cancel para cancelar -->
Na visao Dia, a coluna **Acoes** tem **Reagendar** (scheduling.update), **Logs** (scheduling.logs) e o menu **Acoes**.

| Acao | Quando aparece | O que faz | Permissao |
|---|---|---|---|
| Confirmar | Status Agendado | Muda para Confirmado. Tambem agenda o envio da anamnese ao paciente (portal, WhatsApp e e-mail) | scheduling.finalize |
| Chegou (check-in) | Agendado, Confirmado ou Em atendimento sem check-in | Registra a hora de chegada. O profissional recebe uma notificacao e o paciente entra na Fila de chegada | scheduling.finalize, ou scheduling.update para quem nao e profissional |
| Iniciar atendimento | Agendado ou Confirmado sem inicio | Faz check-in automatico se faltar, muda para Em atendimento e abre o novo registro de prontuario ja preenchido | scheduling.finalize |
| Atender | Agendado, Confirmado ou Concluido | Muda para Em atendimento. Em um concluido aparece como **Reabrir** | scheduling.finalize |
| Concluir | Em atendimento | Abre a tela Finalizar sessao (materiais usados e observacao) | scheduling.finalize |
| Faltou | Agendado, Confirmado ou Em atendimento | Marca como Faltou (no-show). O paciente nao compareceu | scheduling.finalize |
| Cancelar | Qualquer agendamento | Muda para Cancelado | scheduling.cancel |

**Sequencia normal:** Agendado > Confirmado > (Chegou) > Em atendimento > Concluido.
**Transicoes permitidas:** Agendado vai para Confirmado, Em atendimento, Cancelado ou Faltou. Confirmado vai para Em atendimento, Cancelado ou Faltou. Em atendimento vai para Concluido, Cancelado ou Faltou. Concluido so volta para Em atendimento (Reabrir). Cancelado e Faltou sao finais: nao e possivel mudar de novo, entao reagende criando ou ajustando outro horario.
Mensagem de erro para movimento fora das regras: "Transicao de status invalida."

**Marcar Faltou:** use quando o paciente nao compareceu. O agendamento fica com a marca Faltou, os lembretes pendentes de WhatsApp sao cancelados e o aviso "Paciente faltou. Reagende para recuperar o atendimento." aparece nos detalhes. Faltas contam como no-show no Painel Gestor.

**Ao concluir o atendimento:** a tela Finalizar sessao baixa o estoque e, se o servico estiver ligado a um pacote do paciente, consome uma sessao do pacote. Tambem pode enviar orientacoes de pos-atendimento ao paciente.

## Finalizar sessao (concluir atendimento)
<!-- perm: scheduling.finalize -->
**Onde fica:** /schedule/complete-materials, aberta ao clicar em **Concluir** (ou em **Finalizar atendimento** dentro do novo prontuario).
1. Revise **Materiais padrao do servico**: para cada material ligado ao servico aparece a quantidade padrao, que voce pode alterar. Se o servico nao tem materiais ligados aparece "Nenhum material padrao configurado para este servico."
2. Em **Materiais extras** clique em **+ Adicionar material**, escolha o material e a **Qtd**. Use o x para remover a linha.
3. Em **Observacao** descreva o que foi realizado (campo obrigatorio na tela).
4. Clique em **Finalizar sessao**. O sistema ajusta o estoque, registra uma saida financeira "Custo de materiais" quando o custo e maior que zero, registra o procedimento realizado e muda o status para Concluido.
Erro "Somente atendimentos em andamento podem ser concluidos.": inicie o atendimento antes. Se faltar estoque, a conclusao continua e o aviso fica no registro do sistema.
Voltar leva de volta a agenda.

## Reagendar
<!-- perm: scheduling.update -->
**Onde fica:** botao **Reagendar** na agenda (/schedule/reschedule?id=...).
1. A tela mostra o agendamento atual (Paciente, Servico, Data/hora atual).
2. Em **Novo horario** escolha Servico, Profissional, **Nova data** e **Novo horario** (so aparecem horarios livres).
3. Clique em **Confirmar reagendamento**. A agenda mostra "Atualizado. ID: ...". **Cancelar** volta sem alterar.
Se aparecer "Sem permissao para reagendar." falta scheduling.update. Erros: "Conflito de horario.", "Agendamento invalido.". Agendamentos cancelados ou com Faltou nao podem mudar de status, entao reagende antes de cancelar quando quiser manter a vaga do paciente. Dica: se o botao Reagendar do historico do paciente levar de volta a agenda, abra o agendamento direto pela Agenda e use Reagendar la.

## Historico (Logs) do agendamento
<!-- perm: scheduling.logs -->
**Onde fica:** botao **Logs** (/schedule/logs?appointment_id=...). Mostra o cartao do agendamento (Inicio, Fim, Status) e a tabela imutavel **Historico** com ID, Acao, Em, Usuario e IP. Registra criacao, status, check-in, inicio e reagendamento. Botao **Forcar sync Google Calendar** enfileira de novo a sincronizacao desse agendamento. **Voltar** retorna a agenda.

## Operacao da Agenda (recepcao)
<!-- perm: scheduling.ops -->
**Onde fica:** Menu > Agenda > Operacao da Agenda (/schedule/ops). Tela feita para a recepcao conduzir o dia.
- **Filtros:** Data, Buscar paciente (nome), Profissional, com botoes **Buscar** e **Limpar** (a tela tambem aceita filtro por categoria de servico, servico, CPF e faixa de horario pelo endereco).
- **Contadores:** Aguardando (agendados + confirmados), Em atendimento, Concluidos, Faltou / Cancelado.
- **Solicitacoes do portal:** pedidos de **Reagendamento** ou **Cancelamento** feitos pelo paciente no Portal. Cada linha tem Tipo, Paciente, Agendamento (link para a agenda), Profissional, Observacao e os botoes **Aprovar** e **Rejeitar**. Aprovar um cancelamento cancela o agendamento. Aprovar um reagendamento marca o pedido como aprovado, mas voce ainda precisa reagendar o horario na Agenda.
- **Lista de agendamentos do dia:** Horario, Paciente (com a marca **Chegou** quando houve check-in), Servico, Profissional, Status e botoes rapidos: **Chegou**, **Iniciar**, **Concluir** e **Faltou**, conforme o status.

## Fila de chegada
<!-- perm: scheduling.finalize -->
**Onde fica:** Menu > Agenda > Fila de chegada (/schedule/queue). Lista os pacientes que ja fizeram check-in e ainda nao iniciaram o atendimento (status Agendado, Confirmado ou Em atendimento). Cada cartao mostra paciente, servico, profissional, horario e o tempo de espera ("Esperando"). Quem e profissional ve so a propria fila; owner e admin veem todas. Botao **Iniciar atendimento** abre o prontuario. **Atualizar** recarrega a lista (ela nao se atualiza sozinha). Quando vazia mostra "Nenhum paciente aguardando".

## Bloqueios de agenda
<!-- perm: blocks.manage -->
**Onde fica:** Menu > Configuracoes > Agenda > Bloqueios (/blocks). Use para ferias, manutencao, reunioes ou qualquer periodo sem atendimento, para a clinica toda ou um profissional.
1. Clique em **+ Novo bloqueio**.
2. Preencha **Quem** (Clinica inteira ou um profissional), **Inicio**, **Fim**, **Tipo** (Manual, Feriado ou Manutencao) e **Motivo** (obrigatorio).
3. Clique em **Criar bloqueio**.
Filtros da lista: **De**, **Ate**, **Profissional** e **Filtrar**. Mensagem vazia: "Nenhum bloqueio no periodo."
Erros: "Inicio e fim sao obrigatorios.", "Data/hora invalida.", "Motivo e obrigatorio.", "Fim deve ser apos o inicio."
Importante: o bloqueio impede novos horarios livres. Ele nao cancela agendamentos que ja existem no periodo, trate esses manualmente. Nao ha botao de excluir bloqueio na tela.

## Regras de agenda (horarios de cada profissional)
<!-- perm: schedule_rules.manage -->
**Onde fica:** Menu > Configuracoes > Agenda > Regras (/schedule-rules).
1. Escolha o profissional em **Selecione o profissional**.
2. Clique em **+ Adicionar horario**.
3. Preencha **Dia** (domingo a sabado), **Inicio**, **Fim** e **Intervalo (min)**, depois **Adicionar**. O intervalo e a duracao de cada horario oferecido (30 min = horarios de 30 em 30 minutos).
4. Cada dia pode ter varias faixas (por exemplo manha e tarde). Para remover uma faixa use o x ao lado dela. Em cada dia, o botao de lapis (Adicionar horario para aquele dia) abre o formulario ja com o dia escolhido e o botao de proibido (Marcar como fechado) remove todas as faixas do dia.
Sem regra o profissional nao tem horarios livres. O horario do profissional vale dentro do horario de funcionamento da clinica.

## Horarios de funcionamento da clinica
<!-- perm: clinics.read (ver), clinics.update (alterar) -->
**Onde fica:** Menu > Configuracoes > Agenda > Horarios (/clinic/working-hours). Dias sem horario aparecem como **Fechado**.
1. **+ Adicionar horario**, escolha **Dia**, **Inicio**, **Fim** e **Adicionar**. Varias faixas no mesmo dia sao permitidas.
2. Remova uma faixa com o x. Em cada dia, o lapis (Adicionar horario para aquele dia) abre o formulario com o dia escolhido e o botao de proibido (Marcar como fechado) remove todas as faixas do dia.
Se o dia da clinica esta fechado, nenhum profissional tera horario nele.

## Feriados e recesso
<!-- perm: clinics.read (ver), clinics.update (alterar) -->
**Onde fica:** Menu > Configuracoes > Agenda > Feriados (/clinic/closed-days). Nesses dias a agenda fica bloqueada automaticamente.
- **+ Adicionar feriado:** **Data**, **Motivo** (ex.: Natal) e **Status** (Fechado ou Aberto), depois **Adicionar**.
- **Editar** (link na linha) abre o formulario com Data, Motivo e Status, botoes **Salvar** e **Cancelar**. Use status Aberto para reabrir um feriado.
- **Remover** exclui o feriado.
- **Gerar feriados com IA** cria os feriados do ano automaticamente (precisa que a IA esteja disponivel para a clinica). Confira a lista depois.
Erro: "Data e obrigatoria."

## Confirmacao pelo paciente (link)
<!-- perm: nenhuma (link publico) -->
**Onde fica:** /a/confirm?token=... O paciente recebe um link por mensagem. A pagina mostra Clinica, Data e Hora e dois botoes: **Confirmar consulta** e **Cancelar consulta**. O link vale ate 2 dias apos o horario da consulta. Mensagens: "Link invalido ou expirado.", "Nao e possivel confirmar esta consulta.", "Nao e possivel cancelar esta consulta."

## Anamnese pelo link do agendamento
Quando um agendamento e confirmado, o sistema envia ao paciente o pedido de anamnese (usa o modelo definido em Configuracoes > Geral > Anamnese padrao). O paciente preenche em /a/anamnese. Veja 05-prontuarios-e-atendimento.

## Lembretes por WhatsApp
<!-- perm: settings.update -->
- Para cada agendamento o sistema agenda tres mensagens: **confirm_request** e **reminder_24h** (24 horas antes) e **reminder_2h** (2 horas antes), desde que o horario ainda esteja no futuro. Agendamentos cancelados, concluidos ou com Faltou cancelam os lembretes pendentes.
- Paciente sem telefone ou sem permissao de WhatsApp ("Receber lembretes por WhatsApp" desmarcado no cadastro) fica com a mensagem como **Ignorado**.
- **Templates de WhatsApp** (/whatsapp-templates): modelos com **Codigo identificador**, **Nome do template**, **Mensagem** e status Ativo ou inativo. Variaveis: {nome_paciente}, {data}, {horario}, {nome_clinica}, {link}.
- **Logs de WhatsApp** (/whatsapp-logs): historico com Paciente, Template, Status (Pendente, Enviando, Enviado, Falhou, Ignorado, Cancelado), Agendado e Enviado. Filtros por status, template e datas. **Ver** abre o detalhe com payload e resposta, **Reenviar** tenta novamente e **Verificar status** confere os lembretes.
- A conexao do WhatsApp da clinica fica em Configuracoes > Geral > WhatsApp (/settings/whatsapp).

## Google Calendar da clinica
<!-- perm: settings.read (ver), settings.update (conectar/desconectar) -->
**Onde fica:** Configuracoes > Geral > Google Calendar (/settings/google-calendar).
1. Clique em **Conectar Google Calendar** e autorize a conta Google. Os agendamentos passam a ser sincronizados com a **agenda principal** dessa conta.
2. Depois de conectado aparece "Sincronizacao ativa", o link **Ver logs** e a opcao **Desconectar Google Calendar** (nao apaga eventos ja criados).
3. Os logs (/settings/google-calendar/logs) tem filtros de Status, Acao, Appointment ID, User ID e datas, e o cartao **Forcar sync** (informe o ID do agendamento e clique em **Enfileirar sync**).
Se a tela diz "Google Calendar ainda nao esta disponivel", a integracao ainda nao foi habilitada na plataforma. Fale com o suporte.

## Mapa de rotas e acoes da agenda
- Telas: /schedule (Dia, Semana, Mes), /schedule/ops, /schedule/queue, /schedule/reschedule, /schedule/logs, /schedule/complete-materials, /blocks, /schedule-rules, /clinic/working-hours, /clinic/closed-days, /settings/google-calendar, /settings/google-calendar/logs, /whatsapp-templates, /whatsapp-logs, /a/confirm.
- Acoes dos botoes: /schedule/create (Agendar), /schedule/cancel (Cancelar), /schedule/check-in (Chegou), /schedule/start (Iniciar atendimento), /schedule/status (Confirmar, Atender, Reabrir, Concluir, Faltou), /schedule/complete-materials (Finalizar sessao), /schedule/reschedule (Confirmar reagendamento), /schedule/ops/request/resolve (Aprovar ou Rejeitar pedido do portal), /schedule/gcal/force-sync (Forcar sync Google Calendar).
- Apoio da tela: /schedule/available (lista de horarios livres do campo Horario) e /schedule/details (detalhes do agendamento no popup).
- Bloqueios: /blocks/create (Criar bloqueio). Regras: /schedule-rules/create (Adicionar), /schedule-rules/delete (x), /schedule-rules/delete-day (Marcar como fechado).
- Horarios da clinica: /clinic/working-hours (Adicionar), /clinic/working-hours/delete (x), /clinic/working-hours/delete-day (Marcar como fechado).
- Feriados: /clinic/closed-days (Adicionar), /clinic/closed-days/update (Salvar), /clinic/closed-days/delete (Remover), /clinic/closed-days/ai (Gerar feriados com IA).
- WhatsApp: /whatsapp-templates/create e /whatsapp-templates/edit, /whatsapp-logs/show (Ver), /whatsapp-logs/retry-send (Reenviar), /whatsapp-logs/force-reconcile (Verificar status).
- Google Calendar: /settings/google-calendar/connect, /settings/google-calendar/callback (retorno do Google), /settings/google-calendar/disconnect, /settings/google-calendar/logs/force-sync.

## Perguntas frequentes
**Como marco que o paciente faltou?** Na Agenda (visao Dia), abra Acoes na linha do agendamento e clique em **Faltou**. Precisa da permissao scheduling.finalize. O status vira Faltou e nao muda mais.

**Nao consigo marcar um agendamento como concluido.** So e possivel concluir quem esta Em atendimento. Use Iniciar atendimento (ou Atender) antes e depois Concluir. Quem concluiu precisa da permissao scheduling.finalize.

**Nao aparecem horarios livres ao agendar.** Verifique, nesta ordem: 1) Horarios de funcionamento da clinica no dia (Configuracoes > Agenda > Horarios), 2) Regras do profissional naquele dia (Regras), 3) Feriados e Bloqueios, 4) se o horario ja esta ocupado por outro agendamento.

**Como cancelo um agendamento?** Visao Dia > Acoes > Cancelar, ou botao Cancelar nos detalhes da visao Semana. Precisa de scheduling.cancel. Cancelado nao volta, entao crie novo agendamento se precisar.

**Como reagendo?** Botao Reagendar na linha do agendamento. Escolha novo servico, profissional, data e horario e confirme.

**O profissional nao enxerga o botao Novo agendamento.** Quem tem o papel Profissional ve apenas a propria agenda e nao cria agendamentos por ali. A recepcao ou um admin cria.

**O que e o check-in?** E o aviso de que o paciente chegou. Ele coloca o paciente na Fila de chegada e notifica o profissional.

**Como vejo quem alterou um agendamento?** Botao Logs (permissao scheduling.logs).

**O paciente nao recebeu o lembrete.** Abra Configuracoes > Geral > Logs WhatsApp e veja o status. Ignorado significa sem telefone ou sem permissao de WhatsApp. Falhou permite Reenviar. Verifique tambem a conexao em /settings/whatsapp.

**Como bloqueio ferias de um profissional?** Configuracoes > Agenda > Bloqueios > + Novo bloqueio, escolha o profissional, Inicio e Fim e informe o motivo. Agendamentos ja marcados nesse periodo precisam ser reagendados ou cancelados manualmente.

**Como cadastro os feriados do ano?** Configuracoes > Agenda > Feriados. Adicione um a um ou use Gerar feriados com IA.

**A semana comeca no dia errado.** Ajuste em Configuracoes > Geral > Inicio da semana.
