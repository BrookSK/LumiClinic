---
title: Solução de problemas, mensagens de erro e o que fazer em cada caso
routes:
  - /login
  - /forgot
  - /schedule
  - /settings/whatsapp
  - /billing/subscription
---

# Solução de problemas

Como usar: procure a mensagem exata que apareceu na tela ou descreva o sintoma. Cada item tem **Causa** e **O que fazer**. Se nada resolver, veja 90-quando-falar-com-a-equipe.

## Entrada e acesso

### 1. Não consigo entrar: "Credenciais inválidas."
**Causa:** e-mail ou senha errados, ou o usuário está desativado ou arquivado. **O que fazer:** confira o e-mail, digite a senha com calma (atenção ao Caps Lock) e, se não lembra, use **Esqueci minha senha / primeiro acesso**. Se o usuário foi arquivado, peça ao dono da clínica para reativar em Configurações > Usuários & Acesso > Usuários.

### 2. "Muitas requisições. Tente novamente mais tarde."
**Causa:** 10 tentativas de login em 10 minutos. **O que fazer:** aguarde 15 minutos e tente de novo. Em vez de insistir, use **Esqueci minha senha**.

### 3. Esqueci a senha
**O que fazer:** na tela de login clique em **Esqueci minha senha / primeiro acesso**, digite o e-mail e use o link que chega por e-mail (vale 1 hora). O sistema responde sempre "Se o e-mail existir, você receberá instruções para redefinir a senha.", mesmo se o e-mail não está cadastrado. Olhe também a caixa de spam.

### 4. O e-mail de redefinição de senha não chegou
**Causa:** e-mail digitado errado, e-mail na caixa de spam, ou o usuário não existe com esse e-mail. **O que fazer:** confira o e-mail no cadastro do usuário (Configurações > Usuários & Acesso > Usuários), peça para olhar o spam e tente de novo. Se for o primeiro acesso de um paciente, gere o link na tela /patients/portal-access?patient_id=... do paciente.

### 5. "Senha deve ter pelo menos 8 caracteres."
**O que fazer:** use 8 caracteres ou mais, com letras e números.

### 6. "Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica."
**Causa:** o navegador tem uma sessão de paciente aberta. **O que fazer:** saia do portal ou use uma janela anônima. O contrário vale para o portal: "Você está logado na área da clínica. Saia do sistema para entrar no Portal do Paciente.".

### 7. Aparece "Escolha como entrar"
**Causa:** o mesmo e-mail e senha servem para mais de um acesso (duas clínicas, ou clínica e portal). **O que fazer:** marque a opção desejada e clique em **Entrar**.

### 8. "Acesso negado." ou o menu não mostra uma tela
**Causa:** o seu papel não tem a permissão daquela tela. **O que fazer:** diga ao dono da clínica qual tela e qual ação você precisa. Ele ajusta em Configurações > Usuários & Acesso > Papéis & Permissões. Depois saia e entre de novo.

### 9. Não vejo o menu Financeiro, Estoque ou Marketing
**Causa:** faltam as permissões de leitura (finance.sales.read, stock.materials.read, marketing.calendar.read ou marketing.automation.read). **O que fazer:** peça ao dono para liberar.

### 10. Apareceu "Acesso bloqueado" (assinatura)
**Causa:** assinatura pendente ou vencida. **O que fazer:** o dono abre **Ver minha assinatura** e regulariza (12-assinatura-da-clinica). Os demais usuários avisam o dono.

### 11. "Algo deu errado" (500) ou "Sistema indisponível" (503)
**O que fazer:** aguarde um minuto, atualize a página e tente de novo. Se continuar, avise a equipe de suporte com o horário e o que você estava fazendo.

## Agenda

### 12. Não aparecem horários livres ao agendar
**Causa:** falta configurar um destes itens. **O que fazer, nesta ordem:** 1) Horários de funcionamento da clínica no dia (Configurações > Agenda > Horários; dias sem horário aparecem como Fechado), 2) Regras do profissional naquele dia (Configurações > Agenda > Regras), 3) Feriados e Bloqueios que cobrem a data, 4) se o serviço escolhido tem duração maior que o intervalo livre.

### 13. "Horário indisponível (bloqueio)"
**Causa:** existe um bloqueio ou feriado naquele horário para a clínica ou para o profissional. **O que fazer:** veja Configurações > Agenda > Bloqueios e Feriados e escolha outro horário.

### 14. "Conflito de horário."
**Causa:** o profissional já tem outro agendamento nesse horário. **O que fazer:** escolha outro horário ou outro profissional.

### 15. "Paciente é obrigatório.", "Profissional é obrigatório.", "Serviço é obrigatório.", "Horário é obrigatório."
**Causa:** campo obrigatório vazio no formulário de agendamento. **O que fazer:** preencha, escolhendo o paciente na lista que aparece ao digitar.

### 16. "Data/hora inválida."
**Causa:** data ou horário em formato inválido. **O que fazer:** escolha a data no calendário e o horário na lista.

### 17. "Pacote sem sessões disponíveis."
**Causa:** o pacote do paciente já usou todas as sessões. **O que fazer:** venda um novo pacote ou agende sem pacote.

### 18. "Transição de status inválida." ou "Somente atendimentos em andamento podem ser concluídos."
**Causa:** o status do agendamento não permite essa ação agora. **O que fazer:** siga a ordem Confirmar > Chegou > Iniciar atendimento > Concluir (03-agenda). Use **Reabrir** se precisar voltar.

### 19. "Sem permissão para reagendar." ou "Acesso negado." ao cancelar
**Causa:** o papel não tem scheduling.update ou scheduling.cancel. **O que fazer:** peça a permissão ao dono.

### 20. O paciente faltou e a mensagem "Paciente faltou. Reagende para recuperar o atendimento." apareceu
**O que fazer:** abra o agendamento e use **Reagendar**.

### 21. Agendamento sumiu da agenda
**Causa:** foi cancelado, ou o filtro de profissional/dia está escondendo. **O que fazer:** verifique o filtro de profissional e a visão (Dia, Semana, Mês), e veja os **Logs** do agendamento ou o histórico do paciente.

## Pacientes e prontuário

### 22. Paciente duplicado
**Causa:** o sistema não bloqueia nem junta cadastros repetidos. **O que fazer:** antes de criar, procure o paciente pela busca (nome, e-mail ou telefone). Se já existem dois, use apenas um e não apague o que tem prontuário. Se precisar juntar os dados dos dois cadastros, fale com a equipe de suporte.

### 23. "Nome é obrigatório." ao cadastrar paciente
**O que fazer:** preencha o nome. É o único campo obrigatório do cadastro.

### 24. "Este paciente possui agendamentos futuros. Cancele ou remova os agendamentos antes de excluir."
**O que fazer:** cancele os agendamentos futuros do paciente e exclua de novo.

### 25. "Limite de pacientes do plano atingido."
**Causa:** o plano chegou ao número máximo de pacientes. **O que fazer:** o dono faz upgrade em Assinatura (12-assinatura-da-clinica).

### 26. "Formato não suportado. Use JPG/PNG/WEBP." ou "Falha no upload."
**Causa:** o arquivo não é imagem aceita, está corrompido ou muito grande. **O que fazer:** converta para JPG ou PNG, reduza o tamanho e tente de novo.

### 27. "Limite de armazenamento do plano atingido."
**O que fazer:** apague arquivos antigos que não precisa mais ou faça upgrade do plano.

### 28. A transcrição de áudio falhou ou "Limite de transcrição atingido. Faça upgrade do plano."
**Causa:** o limite mensal de minutos do plano acabou, ou o navegador sem microfone. **O que fazer:** veja o saldo em Assinatura; use um navegador que permita microfone (Chrome) e autorize o microfone.

### 29. O paciente não consegue entrar no portal
**O que fazer:** veja 13-portal-do-paciente. Confirme se o acesso foi criado na tela do paciente /patients/portal-access?patient_id=... (abra pelo endereço), se o e-mail está correto e gere um novo link (vale 1 hora). Mensagem "E-mail do paciente é obrigatório para criar acesso." significa que o paciente não tem e-mail no cadastro.

### 30. "Link inválido ou expirado." (confirmação, anamnese, senha do portal)
**Causa:** o link venceu ou já foi usado. **O que fazer:** gere e envie um novo link.

### 31. "Paciente sem telefone cadastrado.", "Paciente sem e-mail cadastrado." ou "Paciente sem opt-in de WhatsApp."
**O que fazer:** complete o telefone ou e-mail no cadastro e, para WhatsApp, marque **Receber lembretes por WhatsApp** se o paciente autorizou.

## WhatsApp e e-mail

### 32. O WhatsApp não envia as mensagens
**Causa:** WhatsApp da clínica desconectado, número do paciente inválido, template desativado ou paciente sem opt-in. **O que fazer:** abra Configurações > Geral, cartão WhatsApp e confira se está **Conectado ✓**. Use **Diagnosticar problemas**. Veja o status da mensagem em Logs WhatsApp (Falhou, Ignorado, Cancelado).

### 33. O QR Code expirou ou não aparece
**O que fazer:** o QR Code se renova sozinho a cada cerca de 25 segundos. Clique em **Conectar WhatsApp** de novo. Se disser "O WhatsApp ainda não foi configurado pelo administrador do sistema.", avise a equipe de suporte.

### 34. O WhatsApp aparece Desconectado
**Causa:** o celular perdeu a sessão (saiu do aparelho conectado, ficou sem internet por muito tempo). **O que fazer:** clique em **Desconectar** (se aparecer) e depois em **Conectar WhatsApp** para escanear um novo QR Code.

### 35. Mensagem com status "Ignorado"
**Causa:** paciente sem telefone ou sem permissão de WhatsApp. **O que fazer:** corrija o cadastro. Mensagens passadas não são reenviadas sozinhas; use **Reenviar**.

### 36. "Falha ao enviar WhatsApp: ..." ao enviar um orçamento
**Causa:** WhatsApp desconectado ou número inválido. **O que fazer:** reconecte o WhatsApp, confira o telefone do paciente e tente de novo. Se persistir copie a mensagem completa e envie para a equipe de suporte.

### 37. "Falha ao enviar e-mail: ..." ou o paciente diz que o e-mail não chegou
**Causa:** e-mail incorreto, caixa cheia, e-mail caiu no spam ou o serviço de e-mail do sistema falhou. **O que fazer:** confira o e-mail no cadastro, peça para olhar o spam e reenvie. Se falhar para todos, avise a equipe de suporte.

### 38. O lembrete não foi enviado ao paciente
**O que fazer:** veja Logs WhatsApp para o paciente. **Ignorado**: sem telefone ou sem opt-in. **Cancelado**: o agendamento foi cancelado, concluído ou marcado como Faltou. **Falhou**: WhatsApp desconectado. Confira também se os templates **reminder_24h** e **reminder_2h** existem e estão **Ativo**.

### 39. A campanha não enviou para ninguém
**Causa:** segmento sem pacientes, pacientes sem telefone ou opt-in, campanha em **Rascunho**, template não escolhido ou WhatsApp desconectado. **O que fazer:** veja a **Audiência estimada** do segmento, clique em **▶ Rodar agora** ou agende, escolha o **Template de WhatsApp** e confira os Logs de envio.

## Financeiro e estoque

### 40. "Apenas orcamentos cancelados podem ser excluidos."
**O que fazer:** cancele o orçamento (**Confirmar cancelamento**) e depois use **🗑 Excluir orçamento**.

### 41. Não consigo adicionar item: "A venda não está aberta." ou "Não é possível remover itens de um orçamento que não está aberto."
**Causa:** o orçamento já está pago ou cancelado. **O que fazer:** itens e desconto ficam travados. Para continuar, crie um novo orçamento.

### 42. "Método inválido.", "Valor inválido.", "Status inválido." ao registrar pagamento
**Causa:** campo em branco ou valor com formato errado. **O que fazer:** digite o valor assim: 150,00 e escolha método e status.

### 43. "Venda cancelada." ao registrar pagamento
**Causa:** o orçamento está cancelado. **O que fazer:** não é possível pagar um orçamento cancelado. Crie outro.

### 44. "Orçamento precisa estar aprovado." ao gerar agendamentos
**O que fazer:** mude o **Status do orçamento** para **Aprovado**, salve e tente de novo. Se aparecer "Sem disponibilidade para procedimento ... (até 90 dias)." verifique as regras de agenda do profissional.

### 45. Não consigo editar o valor de um lançamento do caixa
**Causa:** é um lançamento gerado por venda, pagamento ou conta a pagar. **O que fazer:** corrija na origem. Neles só se altera descrição, centro de custo e forma de pagamento.

### 46. "Parcela já está paga/cancelada."
**Causa:** a parcela da conta a pagar já foi baixada. **O que fazer:** recarregue a página. Para corrigir um pagamento errado, ajuste o lançamento de saída correspondente no Fluxo de Caixa.

### 47. Relatório financeiro vazio ou com valores baixos
**Causa:** período curto ou filtro de profissional ativo. **O que fazer:** ajuste **De** e **Até** e escolha **Todos** em Profissional. O relatório conta só vendas com pagamento **Pago**.

### 48. "O período máximo do relatório é de 3 anos. Reduza o intervalo de datas."
**O que fazer:** reduza o período do relatório contábil.

### 49. Relatórios contábeis incompletos ou com aviso de "sem conta contábil"
**O que fazer:** crie o plano de contas (**Usar modelo padrão para clínicas**), vincule os centros de custo às contas e vincule as formas de pagamento a contas do Ativo. Se o aviso disser que o período tem lançamentos demais, reduza o intervalo.

### 50. "Já existe uma conta com este código." ou "Código inválido. Use apenas números separados por ponto (ex: 1.1.2.08)."
**O que fazer:** use um código novo no formato 1.1.2.08. O código da conta filha começa com o da conta pai.

### 51. Não consigo escolher Categoria ou Unidade ao cadastrar material
**Causa:** nenhuma categoria ou unidade cadastrada. **O que fazer:** crie nas abas **Categorias** e **Unidades** de Menu > Estoque > Materiais.

### 52. "Estoque insuficiente para a operação."
**Causa:** a saída ou perda é maior que o saldo. **O que fazer:** confira o saldo; se o saldo real é maior, registre antes uma **Entrada** ou **Ajuste**.

### 53. O estoque não baixou ao concluir o atendimento
**Causa:** o serviço não tem materiais vinculados, ou o saldo era menor que o necessário ("Estoque insuficiente para baixa automática."). **O que fazer:** vincule os materiais em Configurações > Serviços > Vínculo com estoque e dê entrada no estoque.

### 54. "Validade não pode estar vencida."
**O que fazer:** informe uma validade futura ou deixe o campo em branco.

## Configurações, usuários e importação

### 55. "Limite de usuários do plano atingido."
**O que fazer:** arquive usuários que saíram ou faça upgrade do plano.

### 56. A importação ignorou registros
**Causa:** os registros tinham nome vazio, data ou valor inválido, ou já estavam cadastrados. **O que fazer:** abra o histórico, clique em **Ver detalhes** e veja o motivo de cada linha. Corrija a planilha e importe de novo apenas as linhas ignoradas. Para agendamentos e orçamentos importe antes os pacientes e os profissionais (veja 11-configuracoes-usuarios-permissoes).

### 57. "Apenas arquivos .xlsx são aceitos." ou "Arquivo inválido ou não enviado."
**O que fazer:** exporte o relatório do Clinicorp em .xlsx e envie o arquivo sem alterar o formato.

### 58. A nomenclatura (Paciente/Cliente) não mudou
**O que fazer:** Configurações > Geral, preencha os campos de terminologia e clique em **Salvar configurações**. Atualize a página.

### 59. A IA não funciona
**Causa:** sem IA configurada ou limite do plano atingido. **O que fazer:** abra Configurações > Geral, cartão Inteligência Artificial. Se aparece **IA não configurada**, avise o suporte; se aparece "Limite atingido", veja a Assinatura.

### 60. Usuário não aparece para vincular a um profissional
**Causa:** o usuário já está vinculado ("Este usuário já está vinculado a um profissional.") ou está arquivado. **O que fazer:** use outro usuário ou reative o arquivado.

### 61. O paciente enviou fotos pelo portal e não aparecem
**Causa:** as fotos aguardam moderação. **O que fazer:** aprove na tela Moderação ("Uploads pendentes (Portal)"), aberta pelo endereço /medical-images/moderation, pois não há item de menu (05-prontuarios-e-atendimento).

### 62. A tela "Antes de continuar" bloqueia o acesso (termo obrigatório)
**Causa:** existe um documento LGPD com **Aceite obrigatório** pendente para o seu papel. **O que fazer:** leia e clique em **Assinar** na tela **Antes de continuar**.

## Mapa de rotas
- /login, /forgot, /reset, /choose-access, /me.
- /schedule, /blocks, /schedule-rules, /clinic/working-hours, /clinic/closed-days.
- /settings/whatsapp, /whatsapp-templates, /whatsapp-logs, /marketing/automation/campaigns.
- /finance/sales, /finance/cashflow, /finance/accounting, /stock/materials.
- /settings/importer, /users, /rbac, /billing/subscription, /portal/login.
