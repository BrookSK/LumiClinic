---
title: LGPD, consentimentos, assinaturas, certificações, senhas e boas práticas de segurança
routes:
  - /settings/lgpd
  - /clinic/legal-signatures
  - /compliance/certifications
  - /consent
  - /legal/required
---

# LGPD, segurança e privacidade

## Visão geral
A clínica trata dados de saúde, que são dados sensíveis na LGPD. O sistema ajuda com:
- **Consentimentos** do paciente (termos por procedimento, termos do portal, termos da equipe), com registro de data, hora, IP e integridade do texto assinado.
- **Documentos LGPD** que você mesmo escreve e controla (políticas e termos).
- **Certificações**: um espaço para registrar políticas e controles de segurança da clínica.
- **Permissões por papel**, para que cada pessoa veja só o que precisa.
- **Pedidos do titular** (o paciente pede exportação, exclusão ou revogação pelo portal).

## Consentimentos e termos do paciente
<!-- perm: consent_terms.manage (cadastrar), consent_terms.accept (coletar aceite e assinatura) -->
- Termos por procedimento e a coleta do aceite com assinatura estão em 05-prontuarios-e-atendimento (seção Termos de consentimento) e na aba **Termos** do paciente (/consent?patient_id=...).
- Termos para o portal e para a equipe ficam em Configurações > Documentos & Termos > **Documentos LGPD** (/settings/lgpd). Passo a passo em 11-configuracoes-usuarios-permissoes.
- Cada aceite guarda quem aceitou, a versão do texto, data e hora, IP de origem, e uma verificação de integridade do conteúdo. Em Configurações > Documentos & Termos > **Assinaturas** você consulta tudo, com **✓ Íntegro** ou **✗ Alterado**.
- Se o texto de um documento já assinado é editado, o sistema cria uma **nova versão**. Quem assinou a versão antiga continua com o registro dela.
- Documento marcado como **Aceite obrigatório** bloqueia o acesso até a pessoa aceitar. Para a equipe a tela é **Antes de continuar** (/legal/required, "Para usar o sistema, você precisa aceitar os termos obrigatórios.") com o botão **Assinar**. Para o paciente é **Antes de continuar** no portal.

**Boa prática de consentimento**
1. Colete o aceite antes do procedimento, não depois.
2. Guarde a assinatura do paciente quando o termo exigir.
3. Registre no cadastro se o paciente aceita contato por WhatsApp (**Receber lembretes por WhatsApp**) e só envie campanhas para quem aceitou.

## Certificações (políticas e controles)
<!-- perm: compliance.policies.read (ver), compliance.policies.create, compliance.policies.update, compliance.controls.create, compliance.controls.update -->
**Onde fica:** Menu > Relatórios > **Certificações** (/compliance/certifications). Gerencia as políticas de segurança e compliance da clínica (ex.: LGPD, ISO 27001).

**Políticas** (documentos que definem regras, como Política de Privacidade e Política de Segurança da Informação)
- **+ Nova política**: **Código** (ex.: lgpd-01), **Título**, **Status** (**Rascunho** ou **Ativa**). Em **Mais opções**: **Descrição**, **Responsável**, **Versão**, **Revisado em** e **Próxima revisão**. Clique em **Criar política**.
- Cada política mostra título, código, status (Rascunho, Ativa ou Desativada), versão, responsável e datas de revisão.
- **Editar política**: Título, Status, Versão, Responsável, Revisado em e Próxima revisão. **Salvar**.

**Controles** (ações que garantem as políticas, como criptografia, backup diário e controle de acesso)
- **+ Novo controle**: **Política** (opcional), **Código** (ex.: ctrl-01), **Título**, **Status** (**Planejado**, **Implementado** ou **Testado**). Em **Mais opções**: **Descrição**, **Responsável** e **Link de evidência**. Clique em **Criar controle**.
- **Editar controle**: Política, Título, Status (também **Falhou**), Responsável, Evidência e **Último teste**.
- Use datas de revisão para lembrar a próxima conferência. O sistema não cria alertas automáticos sobre elas.

## Pedidos de LGPD dos pacientes
- O paciente cria o pedido em /portal/lgpd: **Exportar dados**, **Solicitar exclusão** ou **Revogar consentimento**, com observação opcional, e acompanha o status (ID, Tipo, Status, Criado em).
- O tratamento desses pedidos (exportar, anonimizar, excluir) é feito pela equipe de suporte do sistema, que também cuida dos registros de incidentes de segurança. Esses itens não ficam no menu da clínica. Se um paciente fizer um pedido, informe a equipe de suporte (90-quando-falar-com-a-equipe).
- Dentro da clínica você consegue: **excluir um paciente** (Excluir paciente na ficha, exige patients.delete; não é possível se ele tem agendamentos futuros: "Este paciente possui agendamentos futuros. Cancele ou remova os agendamentos antes de excluir.") e **exportar** a lista de pacientes em CSV (permissão patients.export). Trate esses arquivos como dados sensíveis.

## Senhas, sessão e acesso
- **Senha da equipe**: mínimo de **8 caracteres** ("Senha deve ter pelo menos 8 caracteres."). Use letras, números e símbolos e nunca compartilhe.
- **Senha do paciente**: mínimo de 8 caracteres ao definir ou redefinir pelo link e 6 ao trocar dentro do perfil do portal. Oriente o paciente a usar 8 ou mais.
- **Tentativas de login**: depois de 10 tentativas em 10 minutos o acesso é bloqueado por 15 minutos ("Muitas requisições. Tente novamente mais tarde.").
- **Sessão**: dura até 24 horas. Ao usar um computador compartilhado, clique em **Sair** e não marque **Lembrar dados de acesso**.
- **Redefinição de senha**: por e-mail, com link de uso único válido por 1 hora. Por segurança a resposta é a mesma exista ou não o e-mail.
- **Verificação em duas etapas**: o sistema não possui. Proteja o e-mail usado no login com a verificação em duas etapas do próprio provedor de e-mail.
- **Cada pessoa com o seu usuário**: não compartilhe login. Assim o histórico mostra quem fez cada coisa.
- **Papéis e permissões**: dê a cada pessoa só o que ela precisa (11-configuracoes-usuarios-permissoes). Quando alguém sai da clínica, arquive o usuário no mesmo dia.
- **Arquivos privados**: imagens clínicas, assinaturas e documentos só abrem para quem está logado e tem permissão. Nunca envie esses links por mensagem para outras pessoas.

## Boas práticas para a equipe
1. Acesse o prontuário apenas dos pacientes que você atende.
2. Não tire foto da tela nem compartilhe dados de pacientes por aplicativos pessoais.
3. Envie orçamentos e documentos pelo próprio sistema (WhatsApp ou e-mail da clínica) e não por contas pessoais.
4. Mantenha o celular do WhatsApp da clínica com bloqueio de tela.
5. Revise periodicamente a lista de usuários e arquive quem saiu.
6. Em caso de suspeita de vazamento (perda de celular, senha divulgada), troque a senha na hora e avise a equipe de suporte.

## Perguntas frequentes
**Onde vejo quem assinou o termo?** Configurações > Documentos & Termos > **Assinaturas**.

**O que significa "✗ Alterado" numa assinatura?** O texto guardado não confere com o que foi assinado. Fale com a equipe de suporte com o ID da assinatura.

**Preciso de consentimento para enviar WhatsApp?** Sim. Marque no cadastro **Receber lembretes por WhatsApp** só se o paciente autorizou. Campanhas de segmento filtram quem aceitou.

**Como crio uma política de privacidade que o paciente precisa aceitar?** Menu > Configurações > Documentos & Termos > Documentos LGPD, bloco **Termos do Portal do Paciente** > **+ Novo**, marque **Aceite obrigatório** e salve.

**O paciente pediu para apagar os dados dele.** Ele registra em /portal/lgpd ou avisa a clínica. Informe a equipe de suporte para tratar o pedido. Não exclua prontuários por conta própria: há obrigações de guarda do prontuário.

**Como excluo um paciente?** Na ficha, **Excluir paciente**. Antes cancele os agendamentos futuros.

**Esqueci minha senha.** Na tela de login clique em **Esqueci minha senha / primeiro acesso**, digite o e-mail e use o link recebido (vale 1 hora).

**O sistema tem verificação em duas etapas?** Não.

**Quanto tempo fico logado?** Até 24 horas, ou até clicar em **Sair**.

**Alguém usou meu login.** Troque a senha imediatamente (ícone de pessoa no topo > **Perfil** > **Alterar senha**, ou **Esqueci minha senha**) e avise a equipe de suporte.

**Posso mandar o prontuário por WhatsApp?** Prefira disponibilizar pelo **Portal do Paciente**, que exige login do paciente.

**O que são Certificações?** Um quadro para registrar políticas e controles de segurança da clínica, útil para auditorias e certificações. Não altera o funcionamento do sistema.

## Mapa de rotas
- Documentos e aceites: /settings/lgpd, /settings/lgpd/edit, /settings/lgpd/save, /clinic/legal-signatures, /clinic/legal-signatures/view, /clinic/legal-acceptances/portal, /legal/required, /legal/accept, /legal/sign.
- Certificações: /compliance/certifications, /compliance/certifications/policies/create, /compliance/certifications/policies/update, /compliance/certifications/controls/create, /compliance/certifications/controls/update.
- Consentimento do paciente: /consent, /consent/accept, /consent-terms.
- Portal: /portal/lgpd, /portal/required-consents.
