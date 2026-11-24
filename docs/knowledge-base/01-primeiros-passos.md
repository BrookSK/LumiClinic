---
title: Primeiros passos, acesso e conta
routes:
  - /login
  - /forgot
  - /reset
  - /choose-access
  - /criar-conta
  - /legal
  - /me
  - /clinic
  - /users
  - /rbac
  - /settings
  - /tutorial
---

# Primeiros passos, acesso e conta

## Entrar no sistema (login)
<!-- perm: nenhuma (tela publica) -->
**Onde fica:** /login (página inicial para quem ainda não entrou).

**Passo a passo**
1. Digite o **E-mail** e a **Senha** cadastrados.
2. Se quiser, marque **Lembrar dados de acesso**.
3. Clique em **Entrar**. Você vai para o Dashboard (/dashboard). Se tentou abrir outra página antes de entrar, o sistema leva você de volta para ela.

**Links na tela**
- **Esqueci minha senha / primeiro acesso**: leva para /forgot.
- **Criar conta grátis**: leva para /criar-conta (cadastro de uma nova clínica).
- **Entrar no Portal do Paciente**: leva para /portal. É a entrada dos pacientes, não da equipe.

**Erros comuns**
- "Credenciais inválidas.": e-mail ou senha errados, ou usuário desativado. Confira e tente de novo, ou use Esqueci minha senha.
- "Você está logado no Portal do Paciente. Saia do portal para entrar na área da clínica.": o navegador tem uma sessão de paciente aberta. Saia do portal e tente de novo.
- "Muitas requisições. Tente novamente mais tarde.": depois de 10 tentativas de login em 10 minutos o acesso é bloqueado por 15 minutos. Aguarde e tente de novo.

## Escolha como entrar
**Onde fica:** /choose-access, aparece sozinha depois do login.

Quando o mesmo e-mail e senha servem para mais de um acesso (por exemplo duas clínicas, ou uma clínica e o Portal do Paciente), o sistema mostra a tela **Escolha como entrar**.
1. Marque a opção desejada. Cada opção mostra o nome da clínica e se é a área da equipe ou o portal do paciente.
2. Clique em **Entrar**.
3. Para voltar ao login, clique em **Voltar**.
Para trocar de clínica depois, saia do sistema e entre novamente escolhendo a outra opção.

## Esqueci a senha e primeiro acesso
<!-- perm: nenhuma (tela publica) -->
**Onde fica:** /forgot e /reset.

**Passo a passo**
1. Em /forgot digite o **E-mail** e clique em **Gerar link**.
2. A tela sempre mostra "Se o e-mail existir, você receberá instruções para redefinir a senha." (por segurança ela não revela se o e-mail existe).
3. Abra o e-mail "Redefinição de senha - LumiClinic" e clique no link.
4. Em /reset digite a **Nova senha** (mínimo 8 caracteres) e clique em **Salvar**.
5. Volte ao login e entre com a nova senha.

**Primeiro acesso de um usuário novo:** o dono cria o usuário em Configurações > Usuários & Acesso > Usuários e informa e-mail e senha inicial. O usuário pode entrar direto, ou usar Esqueci minha senha para definir a própria senha.

**Erros comuns**
- "Token inválido." ou "Token inválido ou expirado.": o link expira em 1 hora e só pode ser usado uma vez. Gere um novo em /forgot.
- "Senha deve ter pelo menos 8 caracteres."
- Não chegou e-mail: veja a caixa de spam e confira se o e-mail digitado é o mesmo do cadastro do usuário.

## Criar conta de uma nova clínica
<!-- perm: nenhuma (tela publica) -->
**Onde fica:** /criar-conta.
1. Em **Seus dados** preencha Nome completo, Telefone, E-mail, Senha (mínimo 8 caracteres) e CPF ou CNPJ.
2. Em **Sua clínica** informe o Nome da clínica.
3. Em **Escolha seu plano** selecione um plano. Planos com período de teste mostram os dias grátis.
4. Preencha os dados da seção **Cartão de crédito**.
5. Clique em **Criar minha conta**. A conta é criada com você como dono (Owner) da clínica e o sistema abre a página inicial.
Erros: "Preencha todos os campos obrigatórios.", "E-mail inválido.", "Senha deve ter pelo menos 8 caracteres.", "Já existe uma conta com este e-mail."

## Configuração inicial (checklist)
<!-- perm: nenhuma -->
**Onde fica:** Menu > Configuração inicial (item no fim do menu lateral, depois de Ajuda e Guia Completo; /tutorial/sistema/configuracao-inicial).
A tela mostra uma barra de progresso e 7 passos, cada um com um botão que leva direto para a tela certa:
1. **Dados da clínica** (/clinic).
2. **Horários de funcionamento** (/clinic/working-hours).
3. **Cadastrar usuários** (/users).
4. **Regras de agenda**: horários de atendimento de cada profissional (/schedule-rules).
5. **Cadastrar serviços** (/services).
6. **Primeiro paciente** (/patients/create).
7. **Primeiro agendamento** (/schedule).
Siga a ordem: sem horários da clínica e regras do profissional a agenda não mostra horários livres.

## Termos obrigatórios antes de continuar
<!-- perm: nenhuma -->
**Onde fica:** /legal/required.
Se a clínica exigir aceite de documentos legais, aparece a tela **Antes de continuar** com a lista de documentos.
1. Clique em **Assinar** no documento.
2. Em /legal/sign leia o texto, assine no quadro e clique em **Assinar** (use **Limpar** para refazer; **Voltar** retorna a lista).
3. Quando todos estiverem assinados aparece "Tudo certo." com o botão **Continuar**.

## Acesso bloqueado por assinatura
Se aparecer a tela **Acesso bloqueado** (código 402), a assinatura da clínica está pendente ou vencida. Clique em **Ver minha assinatura** (/billing/subscription) para regularizar. Somente o Owner vê o item Assinatura no menu. Pessoas sem esse acesso devem falar com o dono da clínica.

## Telas de erro
- **Acesso negado** (403): você não tem permissão para a tela. Peça ao dono para liberar a permissão em Papéis & Permissões.
- **Página não encontrada** (404): endereço errado. Use Voltar ao início.
- **Algo deu errado** (500) e **Sistema indisponível** (503): tente de novo em instantes. Se persistir, informe o suporte com o horário e o que estava fazendo.

## Menu lateral e topo da tela
<!-- perm: nenhuma -->
**Menu lateral:** os grupos que aparecem dependem das suas permissões.
- **Dashboard** (/).
- **Assinatura** (somente Owner).
- **Agenda**: Agenda, Operação da Agenda, Fila de chegada.
- **Pacientes** (o nome acompanha a terminologia da clínica): lista, Solicitações de perfil, Aniversariantes / Follow-up.
- **Financeiro**, **Estoque**, **Marketing**, **Relatórios** (ver os outros arquivos).
- **Configurações**: Geral, Modelos de prontuário, Clínica, Agenda (Horários, Feriados, Regras, Bloqueios), Usuários & Acesso (Usuários, Papéis & Permissões), Documentos & Termos, Anamnese, Serviços (Cadastro, Vínculo com estoque, Procedimentos).
- **Ajuda**, **Guia Completo** e **Configuração inicial** no final do menu.
Os grupos abrem e fecham clicando no título. O botão de três barras no topo recolhe ou expande o menu e o navegador lembra sua escolha. No celular o menu abre como painel deslizante.

**Topo da tela**
- Título da página atual.
- **Busca rápida**: campo "Buscar paciente (nome, e-mail ou telefone)...". Digite e pressione **Enter** para abrir a lista de pacientes já filtrada (/patients?q=...). Se você não tem permissão de ver pacientes, a busca não abre a lista.
- Selo **CLÍNICA**.
- **Menu do usuário** (ícone de pessoa): **Perfil** (/me) e **Sair**.
**Assistente de suporte:** no canto inferior direito fica um botão redondo com o desenho de uma conversa (ao passar o mouse aparece "Precisa de ajuda?"). Ele abre o painel **Assistente LumiClinic**, onde você digita a dúvida em "Digite sua dúvida..." e clica em **Enviar**. No painel há **Nova conversa**, **Fechar**, o botão **Falar com a equipe** (para abrir um chamado) e o link **Guia completo**. Quando a plataforma não ativa o assistente, no mesmo lugar aparece o botão verde **Falar com suporte**, que abre o WhatsApp do suporte.

## Minha conta (Perfil)
<!-- perm: nenhuma (qualquer usuario logado) -->
**Onde fica:** ícone de usuário no topo > Perfil (/me).

**Dados pessoais (botão Salvar)**
| Campo | Observação |
|---|---|
| Nome completo | Obrigatório |
| E-mail | Obrigatório |
| Telefone | Formato (00) 00000-0000 |
| Tipo de documento | CPF ou CNPJ |
| Número do documento | Segue o tipo escolhido |
| CEP, Rua, Número, Complemento, Bairro, Cidade, UF | Endereço opcional, usado em cobranças e notas |
**Alterar senha** (formulário separado): Senha atual, Nova senha (mínimo 8 caracteres) e Confirmar nova senha, depois **Alterar senha**.
Clique em **Voltar** para retornar ao Dashboard.

## Sair
Ícone de usuário no topo > **Sair**. Em computadores compartilhados sempre saia ao terminar. Após sair você volta para /login.

## Dados da clínica
<!-- perm: clinics.read (ver), clinics.update (salvar) -->
**Onde fica:** Menu > Configurações > Clínica (/clinic).
**Campos**
| Seção | Campos |
|---|---|
| Identificação | Nome da clínica (obrigatório) |
| Logo | Escolher imagem (PNG, JPG ou WEBP, até 2 MB), aparece no cabeçalho dos orçamentos. Botão Remover logo |
| Contato | E-mail, Telefone, WhatsApp |
| Endereço | CEP, Rua, Número, Complemento, Bairro, Cidade, UF |
| Redes sociais e site | Site, Instagram, Facebook |
Clique em **Salvar**. Para aplicar o logo é preciso salvar depois de escolher a imagem. Erros: "Nome é obrigatório.", "Selecione uma imagem."
Dentro do grupo Configurações > Agenda ficam Horários e Feriados (ver 03-agenda).

## Configurações gerais
<!-- perm: settings.read (ver), settings.update (salvar) -->
**Onde fica:** Menu > Configurações > Geral (/settings).
- **Terminologia**: troque os rótulos Paciente/Cliente, Consulta/Sessão e Profissional/Especialista. O menu e as telas passam a usar os novos nomes. Também existe a página /settings/terminology com o mesmo formulário.
- **Agenda e anamnese**: **Início da semana** (dia em que a semana começa na agenda) e **Anamnese padrão** (modelo enviado automaticamente ao confirmar uma consulta).
- Atalhos para WhatsApp, Templates WhatsApp, Inteligência Artificial, Operacional (funil, motivos, origens), Google Calendar, Logs WhatsApp e Importador.
Clique em **Salvar configurações**.

## Usuários
<!-- perm: users.read (lista), users.create, users.update, users.delete -->
**Onde fica:** Menu > Configurações > Usuários & Acesso > Usuários (/users).
A lista mostra Nome, E-mail, Papel, Status (Ativo, Desativado, Inativo) e Criado em, com paginação.

**Criar usuário** (botão **+ Novo usuário**, /users/create)
1. Nome completo, E-mail de login, Senha, Papel (obrigatórios).
2. Clique em **Criar usuário**.
Erros: "Preencha todos os campos.", "Limite de usuários do plano atingido." (faça upgrade do plano em Assinatura).

**Editar** (/users/edit): Nome completo, E-mail, Papel, Status, Nova senha (vazio mantém a atual), e em **Dados profissionais**: Especialidade e **Nº do conselho (CRM, CRO, etc.)** (aparece nas receitas). Na parte de baixo da tela há o bloco **Desativar usuário** com o botão **Confirmar desativação**.

**Arquivar, Reativar, Excluir** (exigem users.delete)
- **Arquivar**: o usuário perde o acesso mas pode ser reativado depois.
- **Reativar**: devolve o acesso.
- **Excluir**: some da lista e não entra mais.
Regras que geram erro: você não pode arquivar/excluir o seu próprio usuário e não pode arquivar/excluir o último proprietário da clínica.

## Papéis e permissões
<!-- perm: rbac.manage -->
**Onde fica:** Menu > Configurações > Usuários & Acesso > Papéis & Permissões (/rbac). Aparece só para quem tem rbac.manage.
- A lista mostra cada papel com o selo **Sistema** (papéis de fábrica) ou **Personalizado**, e **Somente leitura** para o Owner.
- **+ Novo papel**: informe o nome (ex.: Enfermeiro) e clique em Criar. O papel nasce sem permissões.
- **Editar permissões** (/rbac/edit): as permissões ficam agrupadas por módulo (Agenda, Pacientes, Prontuários, Imagens Clínicas, Anamnese, Consentimento, Financeiro, Estoque, Marketing, Permissões, Usuários, Configurações, Clínica, Auditoria, Compliance, Relatórios, Procedimentos, Regras de Agenda, Profissionais, Bloqueios, Serviços, Modelos Prontuário). Em cada permissão marque **Permitir** ou **Negar**. **Negar** vence sempre sobre Permitir.
- Botões **Permitir tudo** e **Limpar tudo** marcam todas ou nenhuma.
- **Salvar permissões** grava.
- **Clonar este papel**: cria um novo papel com as mesmas permissões.
- **Resetar para padrão**: restaura as permissões de fábrica do papel.
- Depois de mudar um papel, os usuários precisam sair e entrar de novo para valer.
Erros: "Nome é obrigatório.", "Acesso negado." (papel Owner não pode ser editado).

## Ajuda, Guia Completo e tutoriais do sistema
<!-- perm: nenhuma -->
**Onde fica:** Menu > Ajuda (/tutorial/sistema), Guia Completo (/tutorial/guia-completo) e Configuração inicial (/tutorial/sistema/configuracao-inicial).
- **Ajuda** é o índice de tutoriais por assunto: Primeiros passos (/tutorial/sistema/primeiros-passos), Dashboard (/tutorial/sistema/dashboard), Agenda (/tutorial/sistema/agenda), Pacientes (/tutorial/sistema/pacientes), Prontuários (/tutorial/sistema/prontuarios), Imagens (/tutorial/sistema/imagens), Financeiro (/tutorial/sistema/financeiro), Estoque (/tutorial/sistema/estoque), Serviços (/tutorial/sistema/servicos), Profissionais (/tutorial/sistema/profissionais), Configurações (/tutorial/sistema/configuracoes), Segurança (/tutorial/sistema/seguranca), Portal do paciente (/tutorial/sistema/portal-paciente) e Integrações e API (/tutorial/sistema/integracoes-api). Cada página tem o botão **Voltar para o índice** e **Ir para o sistema**.
- **Guia Completo** reúne o manual em uma página e tem exportação em PDF (/tutorial/guia-completo/export.pdf).
- Existem tutoriais voltados ao paciente em /tutorial/paciente, que explicam o Portal do Paciente para quem recebe o acesso.

## Mapa de rotas e ações desta área
- /login (entrar), /logout (sair, botão Sair), /forgot e /reset (recuperar senha), /choose-access (escolher acesso), /criar-conta (nova clínica).
- /legal/required (lista de termos), /legal/sign (assinar), /legal/accept (aceite direto de um documento, usado pelo sistema; a tela atual pede assinatura em /legal/sign).
- /me (perfil), /me/change-password (botão Alterar senha).
- /clinic (dados da clínica), /clinic/logo (mostra o logo atual e recebe o envio), /clinic/logo/remove (botão Remover logo).
- /users, /users/create, /users/edit, /users/disable (Arquivar), /users/reactivate (Reativar), /users/delete (Excluir).
- /rbac, /rbac/edit, /rbac/create (Novo papel), /rbac/clone (Clonar), /rbac/reset (Resetar para padrão).
- /settings (Configurações gerais) e /settings/terminology (só a terminologia).

## Perguntas frequentes
**Esqueci minha senha, o que faço?** Em /login clique em Esqueci minha senha, digite o e-mail e clique em Gerar link. Abra o e-mail recebido e crie uma nova senha com no mínimo 8 caracteres. O link vale 1 hora.

**O link de redefinição diz "Token inválido ou expirado".** O link vale 1 hora e funciona uma vez. Gere um novo em /forgot.

**Não vejo um item de menu (por exemplo Financeiro ou Papéis & Permissões).** O menu mostra apenas o que o seu papel permite. Peça ao dono para liberar a permissão em Configurações > Usuários & Acesso > Papéis & Permissões e depois saia e entre de novo.

**Apareceu "Acesso negado".** Falta a permissão daquela tela. Informe ao dono a tela e o que você queria fazer.

**Como troco de clínica?** O login e por clínica. Saia e, na tela Escolha como entrar, selecione a outra clínica (aparece quando o mesmo e-mail e senha existem em mais de uma).

**Como mudo meu telefone ou minha senha?** Clique no ícone de pessoa no topo da tela e depois em **Perfil**. Dados pessoais e Alterar senha ficam em formulários separados, cada um com seu botão.

**Como troco "Paciente" por "Cliente" no sistema?** Menu > Configurações > Geral, bloco **Terminologia**: altere os campos e clique em **Salvar configurações**.

**Como cadastro um novo funcionário?** Configurações > Usuários & Acesso > Usuários > + Novo usuário, escolha o papel. Se for profissional de atendimento, cadastre também em /professionals (ver 06-serviços-profissionais).

**O login diz "Muitas requisições".** Houve muitas tentativas. Espere 15 minutos.

**Por onde começo a configurar a clínica?** No fim do menu lateral clique em **Configuração inicial** e siga os 7 passos.
