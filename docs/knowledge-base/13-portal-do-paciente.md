---
title: Portal do Paciente, o que o paciente vê e o que a clínica precisa saber
routes:
  - /portal
  - /portal/login
  - /portal/agenda
  - /portal/documentos
  - /tutorial/paciente
---

# Portal do Paciente

## Visão geral
O **Portal do Paciente** é uma área separada, em /portal, onde o paciente entra com o próprio e-mail e senha para ver consultas, documentos, notificações, enviar fotos, atualizar o perfil e fazer pedidos de LGPD. A equipe da clínica não entra por ali: a entrada da equipe é /login.

- O paciente só entra se a clínica criou o acesso dele (veja a seção Como o paciente ganha acesso).
- O portal é um acesso diferente do sistema: o mesmo e-mail pode existir como paciente e como funcionário. Se isso acontecer, após o login o sistema mostra **Escolha como entrar** (veja 01-primeiros-passos).
- Se você está logado como equipe no mesmo navegador, o portal avisa "Você está logado na área da clínica. Saia do sistema para entrar no Portal do Paciente.". Saia ou use outra janela anônima.

## Como o paciente ganha acesso
<!-- perm: patients.update (criar acesso na ficha) -->
**Quem faz:** a equipe da clínica. Detalhes e botões em 04-pacientes.
1. Abra o paciente e vá para o **Acesso ao Portal** (/patients/portal-access?patient_id=...) ou preencha o campo de senha no cadastro do paciente.
2. Confirme o **E-mail para login no Portal** do paciente (obrigatório: "E-mail do paciente é obrigatório para criar acesso.").
3. Clique em **Criar/Atualizar acesso e gerar redefinição**. O sistema envia ao paciente um e-mail "Seu acesso ao Portal do Paciente - nome da clínica" com um link para definir a senha. A tela também mostra o link para você copiar e enviar por WhatsApp.
4. O link vale **1 hora**. Se vencer, gere outro com o mesmo botão.
5. O paciente abre o link (tela **Primeiro acesso**), define a senha em **Defina uma senha** e clica em **Salvar e entrar**.

**Dicas**
- O botão **Esqueci minha senha / primeiro acesso** da tela de login do portal também serve para o paciente pedir o link sozinho (digite o e-mail e clique em **Gerar link**). Por segurança a resposta é sempre "Se o e-mail existir, você receberá instruções para redefinir a senha.", mesmo se o e-mail não existe.
- A senha deve ter pelo menos 8 caracteres.
- Se o paciente é excluído da clínica, o acesso ao portal é removido (04-pacientes).

## Login do portal
**Onde fica:** /portal/login. Título **Portal do Paciente**, "Faça login na sua conta".
- Campos **E-mail** e **Senha**, caixa **Lembrar dados de acesso** e botão **Entrar**.
- Link **Esqueci minha senha / primeiro acesso** (/portal/forgot): digite o e-mail e clique em **Gerar link**; depois o paciente usa o link do e-mail (/portal/reset) para criar a **Nova senha** e clica em **Salvar**.
- Link **Entrar na Área da clínica** leva ao login da equipe.
- Erros: "Credenciais inválidas." (e-mail ou senha errados), "Link inválido ou expirado.", "Token inválido ou expirado." (link de senha vencido, gere outro), "Senha deve ter pelo menos 8 caracteres.".

## O que o paciente vê no portal
| Tela | Endereço | Para que serve |
|---|---|---|
| Início | /portal | Boas-vindas, **Próximas consultas**, atalhos **Agenda**, **Documentos**, **Enviar fotos**, **Meu perfil** e **Avisos recentes** |
| Minha agenda | /portal/agenda | Lista as consultas com status (Agendado, Confirmado, Em andamento, Concluído, Cancelado, Faltou). Botões **Confirmar**, **Solicitar reagendamento** e **Solicitar cancelamento**. **Solicitações pendentes** mostra o que já foi pedido |
| Meus documentos | /portal/documentos | **Termos aceitos**, **Assinaturas** (abrir arquivo) e **Imagens clínicas** (antes e depois) liberadas pela clínica |
| Anamnese | /portal/anamnese | **Pendentes** (botão **Preencher**) e **Preenchidas** (botão **PDF**, selo **Assinada**) |
| Buscar | /portal/busca | Busca por consulta, assinatura, upload ou data em agenda, documentos, notificações e uploads |
| Enviar fotos | /portal/uploads | Envio de fotos para a clínica. **Tipo**: **Antes**, **Depois** ou **Outro**; **Observação**. Passam por moderação antes de ficarem disponíveis |
| Notificações | /portal/notificacoes | Avisos da clínica, **Marcar como lida** e **Notificações push** (botão **Ativar**, para receber no celular) |
| Meu perfil | /portal/perfil | Dados, foto, **Solicitar alteração de dados**, **Alterar senha** e contatos da clínica |
| Acesso | /portal/seguranca | E-mail de login e botão **Enviar link para redefinir minha senha** (com o link para copiar) |
| Conteúdos | /portal/conteudos | Materiais que a clínica liberou para o paciente (orientações, vídeos, links) |
| Resumo | /portal/metricas | Retenção (dias desde a última consulta), recorrência e serviços realizados |
| LGPD | /portal/lgpd | Pedidos sobre os dados do paciente |
| Tokens de API | /portal/api-tokens | Chaves de acesso pessoais do paciente |
| Termos obrigatórios | /portal/required-consents | Termos que precisam ser aceitos antes de usar o portal |

**Termos obrigatórios**: quando existe um documento obrigatório pendente (Configurações > Documentos LGPD) o paciente vê **Antes de continuar** com os botões **Ler** e **Assinar**. Enquanto houver termo pendente ele não navega no portal. Depois de aceitar aparece "Tudo certo!" e **Continuar**.

## O que a clínica faz com os pedidos do paciente
- **Reagendamento ou cancelamento** pedido pelo paciente: aparece em **Solicitações do portal** na Agenda, com os botões **Aprovar** e **Rejeitar** (03-agenda). Aprovar um reagendamento só marca o pedido; a clínica ainda precisa reagendar o horário na Agenda.
- **Solicitação de alteração de dados**: Menu > Pacientes > Solicitações de perfil (/patients/profile-requests), com **aprovar** ou **rejeitar** (04-pacientes). O paciente vê "Você tem uma solicitação de alteração pendente" até a clínica responder.
- **Fotos enviadas**: ficam em moderação em /medical-images/moderation (05-prontuarios-e-atendimento). Só depois de aprovadas entram nos documentos do paciente.
- **Documentos e conteúdos**: a clínica libera pela ficha do paciente (Documentos, Conteúdos) com a opção **Disponibilizar no Portal**.
- **Confirmação**: o botão **Confirmar** do paciente muda a consulta para Confirmado.
- **Pedidos de LGPD** (exportar dados, solicitar exclusão, revogar consentimento): o paciente cria em /portal/lgpd e acompanha o histórico (ID, Tipo, Status, Criado em). Esses pedidos são tratados pela equipe de suporte do sistema. Se um paciente reclamar de demora ou perguntar algo, passe a informação para o suporte (90-quando-falar-com-a-equipe).

## Perfil, senha e foto (paciente)
- **Alterar senha**: informe a senha atual e a nova (mínimo 6 caracteres nesta tela). Mensagens: "Preencha a senha atual e a nova senha.", "A nova senha deve ter pelo menos 6 caracteres.", "Senha atual incorreta.", "Senha alterada com sucesso.".
- **Foto**: JPG, PNG ou WebP, até 5 MB. Mensagens: "Selecione uma foto.", "Formato inválido. Use JPG, PNG ou WebP.", "A foto deve ter no máximo 5MB.", "Foto atualizada.".
- **Dados**: não são alterados na hora. O paciente envia **Enviar solicitação** e a clínica aprova. Mensagem: "Solicitação enviada para revisão da clínica.". Se nada mudou: "Nenhuma alteração foi detectada.".

## Envio de fotos e limites
- Formatos aceitos: JPG, PNG e WEBP. Erros: "Formato não suportado. Use JPG/PNG/WEBP.", "Falha no upload.", "Arquivo inválido.".
- O espaço de armazenamento depende do plano. Quando acaba: "Limite de armazenamento do plano atingido.". Faça upgrade (12-assinatura-da-clinica) ou apague arquivos antigos.

## Agenda no portal: erros comuns
- "Não é possível confirmar este agendamento.", "Não é possível solicitar cancelamento.", "Não é possível solicitar reagendamento.": o agendamento já passou, foi cancelado ou concluído, ou já há pedido.
- "Informe a data/hora desejada.": no reagendamento é preciso informar a nova data.

## Tokens de API do paciente
O paciente pode criar chaves pessoais em /portal/api-tokens (**Gerar**, com **Nome (opcional)**). A chave aparece **uma única vez**, na tela, logo depois de criada, e a lista mostra ID, Nome, Criado em, Revogado em e o botão **Revogar**. É um recurso opcional para integrações do próprio paciente. A clínica não vê nem gerencia essas chaves. Se o paciente for excluído da clínica, as chaves dele são revogadas. Nunca peça ao paciente para enviar a chave por mensagem.

## Tutoriais do paciente (para enviar a ele)
Páginas públicas com passo a passo, em linguagem simples, que você pode mandar por WhatsApp:
- /tutorial/paciente (início), /tutorial/paciente/portal, /tutorial/paciente/busca, /tutorial/paciente/agenda, /tutorial/paciente/documentos, /tutorial/paciente/uploads, /tutorial/paciente/notificacoes, /tutorial/paciente/perfil, /tutorial/paciente/seguranca, /tutorial/paciente/lgpd, /tutorial/paciente/api-tokens e /tutorial/api-tokens/paciente.

## Perguntas frequentes
**O paciente diz que não consegue entrar.** Confirme se o acesso foi criado (Acesso ao Portal), se o e-mail está certo e peça para usar **Esqueci minha senha / primeiro acesso**. Se o link venceu (1 hora), gere outro na ficha do paciente.

**O paciente não recebeu o e-mail com o link.** Confira o e-mail no cadastro, peça para olhar o spam e reenvie. Você também pode copiar o link na tela de Acesso ao Portal e mandar por WhatsApp.

**Como o paciente confirma a consulta?** No portal, **Minha agenda**, botão **Confirmar**. Também pode confirmar pelo link do WhatsApp ou e-mail.

**O paciente pediu para cancelar pelo portal. O que acontece?** O pedido vira uma solicitação pendente na Agenda. A clínica usa **Aprovar** ou **Rejeitar**. O cancelamento só vale depois da aprovação.

**Quais documentos o paciente vê?** Termos aceitos, assinaturas e imagens clínicas liberadas, além de documentos disponibilizados no portal e anamneses.

**O paciente enviou fotos e elas não aparecem.** As fotos passam por moderação. Aprove em Imagens clínicas > moderação.

**Posso ver o portal como o paciente vê?** Não existe visualização como paciente. Peça um print ao paciente ou use os tutoriais de /tutorial/paciente.

**O paciente esqueceu a senha.** Use **Esqueci minha senha / primeiro acesso** no portal, ou gere o link pela ficha do paciente.

**O paciente quer apagar os dados dele.** Ele registra o pedido em /portal/lgpd (**Solicitar exclusão**). Avise a equipe de suporte para acompanhar o pedido.

**Notificações push não chegam.** O paciente precisa ativar em **Notificações > Ativar** e permitir no navegador. Se o botão não funciona, avise o suporte.

## Mapa de rotas
- Acesso: /portal/login, /portal/register, /portal/forgot, /portal/reset, /portal/logout.
- Telas: /portal, /portal/agenda, /portal/documentos, /portal/anamnese, /portal/anamnese/preencher, /portal/busca, /portal/uploads, /portal/notificacoes, /portal/perfil, /portal/seguranca, /portal/conteudos, /portal/metricas, /portal/lgpd, /portal/api-tokens, /portal/required-consents.
- Ações: /portal/agenda/confirm, /portal/agenda/cancel-request, /portal/agenda/reschedule-request, /portal/perfil/request-change, /portal/perfil/change-password, /portal/perfil/photo, /portal/notificacoes/read, /portal/legal/read, /portal/legal/accept, /portal/legal/sign, /portal/seguranca/reset, /portal/api-tokens/create, /portal/api-tokens/revoke, /portal/push/config, /portal/push/subscribe, /portal/push/unsubscribe, /portal/push/test, /portal/signatures/file, /portal/medical-images/file, /portal/anamnesis/export-pdf.
- Tutoriais: /tutorial/paciente e subpáginas.
- Telas da equipe relacionadas: /patients/portal-access, /patients/profile-requests, /patients/content, /medical-images/moderation.
