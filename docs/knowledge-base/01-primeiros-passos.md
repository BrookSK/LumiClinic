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
**Onde fica:** /login (pagina inicial para quem ainda nao entrou).

**Passo a passo**
1. Digite o **E-mail** e a **Senha** cadastrados.
2. Se quiser, marque **Lembrar dados de acesso**.
3. Clique em **Entrar**. Voce vai para o Dashboard (/dashboard). Se tentou abrir outra pagina antes de entrar, o sistema leva voce de volta para ela.

**Links na tela**
- **Esqueci minha senha / primeiro acesso**: leva para /forgot.
- **Criar conta gratis**: leva para /criar-conta (cadastro de uma nova clinica).
- **Entrar no Portal do Paciente**: leva para /portal. E a entrada dos pacientes, nao da equipe.

**Erros comuns**
- "Credenciais invalidas.": e-mail ou senha errados, ou usuario desativado. Confira e tente de novo, ou use Esqueci minha senha.
- "Voce esta logado no Portal do Paciente. Saia do portal para entrar na area da clinica.": o navegador tem uma sessao de paciente aberta. Saia do portal e tente de novo.
- "Muitas requisicoes. Tente novamente mais tarde.": depois de 10 tentativas de login em 10 minutos o acesso e bloqueado por 15 minutos. Aguarde e tente de novo.

## Escolha como entrar
**Onde fica:** /choose-access, aparece sozinha depois do login.

Quando o mesmo e-mail e senha servem para mais de um acesso (por exemplo duas clinicas, ou uma clinica e o Portal do Paciente), o sistema mostra a tela **Escolha como entrar**.
1. Marque a opcao desejada. Cada opcao mostra o nome da clinica e se e a area da equipe ou o portal do paciente.
2. Clique em **Entrar**.
3. Para voltar ao login, clique em **Voltar**.
Para trocar de clinica depois, saia do sistema e entre novamente escolhendo a outra opcao.

## Esqueci a senha e primeiro acesso
<!-- perm: nenhuma (tela publica) -->
**Onde fica:** /forgot e /reset.

**Passo a passo**
1. Em /forgot digite o **E-mail** e clique em **Gerar link**.
2. A tela sempre mostra "Se o e-mail existir, voce recebera instrucoes para redefinir a senha." (por seguranca ela nao revela se o e-mail existe).
3. Abra o e-mail "Redefinicao de senha - LumiClinic" e clique no link.
4. Em /reset digite a **Nova senha** (minimo 8 caracteres) e clique em **Salvar**.
5. Volte ao login e entre com a nova senha.

**Primeiro acesso de um usuario novo:** o dono cria o usuario em Configuracoes > Usuarios e Acesso > Usuarios e informa e-mail e senha inicial. O usuario pode entrar direto, ou usar Esqueci minha senha para definir a propria senha.

**Erros comuns**
- "Token invalido." ou "Token invalido ou expirado.": o link expira em 1 hora e so pode ser usado uma vez. Gere um novo em /forgot.
- "Senha deve ter pelo menos 8 caracteres."
- Nao chegou e-mail: veja a caixa de spam e confira se o e-mail digitado e o mesmo do cadastro do usuario.

## Criar conta de uma nova clinica
<!-- perm: nenhuma (tela publica) -->
**Onde fica:** /criar-conta.
1. Em **Seus dados** preencha Nome completo, Telefone, E-mail, Senha (minimo 8 caracteres) e CPF ou CNPJ.
2. Em **Sua clinica** informe o Nome da clinica.
3. Em **Escolha seu plano** selecione um plano. Planos com periodo de teste mostram os dias gratis.
4. Preencha os dados da secao **Cartao de credito**.
5. Clique em **Criar minha conta**. A conta e criada com voce como dono (Owner) da clinica e o sistema abre a pagina inicial.
Erros: "Preencha todos os campos obrigatorios.", "E-mail invalido.", "Senha deve ter pelo menos 8 caracteres.", "Ja existe uma conta com este e-mail."

## Configuracao inicial (checklist)
<!-- perm: nenhuma -->
**Onde fica:** Menu > Ajuda > Configuracao inicial (/tutorial/sistema/configuracao-inicial).
A tela mostra uma barra de progresso e 7 passos, cada um com um botao que leva direto para a tela certa:
1. **Dados da clinica** (/clinic).
2. **Horarios de funcionamento** (/clinic/working-hours).
3. **Cadastrar usuarios** (/users).
4. **Regras de agenda**: horarios de atendimento de cada profissional (/schedule-rules).
5. **Cadastrar servicos** (/services).
6. **Primeiro paciente** (/patients/create).
7. **Primeiro agendamento** (/schedule).
Siga a ordem: sem horarios da clinica e regras do profissional a agenda nao mostra horarios livres.

## Termos obrigatorios antes de continuar
<!-- perm: nenhuma -->
**Onde fica:** /legal/required.
Se a clinica exigir aceite de documentos legais, aparece a tela **Antes de continuar** com a lista de documentos.
1. Clique em **Assinar** no documento.
2. Em /legal/sign leia o texto, assine no quadro e clique em **Assinar** (use **Limpar** para refazer; **Voltar** retorna a lista).
3. Quando todos estiverem assinados aparece "Tudo certo." com o botao **Continuar**.

## Acesso bloqueado por assinatura
Se aparecer a tela **Acesso bloqueado** (codigo 402), a assinatura da clinica esta pendente ou vencida. Clique em **Ver minha assinatura** (/billing/subscription) para regularizar. Somente o Owner ve o item Assinatura no menu. Pessoas sem esse acesso devem falar com o dono da clinica.

## Telas de erro
- **Acesso negado** (403): voce nao tem permissao para a tela. Peca ao dono para liberar a permissao em Papeis e Permissoes.
- **Pagina nao encontrada** (404): endereco errado. Use Voltar ao inicio.
- **Algo deu errado** (500) e **Sistema indisponivel** (503): tente de novo em instantes. Se persistir, informe o suporte com o horario e o que estava fazendo.

## Menu lateral e topo da tela
<!-- perm: nenhuma -->
**Menu lateral:** os grupos que aparecem dependem das suas permissoes.
- **Dashboard** (/).
- **Assinatura** (somente Owner).
- **Agenda**: Agenda, Operacao da Agenda, Fila de chegada.
- **Pacientes** (o nome acompanha a terminologia da clinica): lista, Solicitacoes de perfil, Aniversariantes / Follow-up.
- **Financeiro**, **Estoque**, **Marketing**, **Relatorios** (ver os outros arquivos).
- **Configuracoes**: Geral, Modelos de prontuario, Clinica, Agenda (Horarios, Feriados, Regras, Bloqueios), Usuarios e Acesso (Usuarios, Papeis e Permissoes), Documentos e Termos, Anamnese, Servicos (Cadastro, Vinculo com estoque, Procedimentos).
- **Ajuda**, **Guia Completo** e **Configuracao inicial** no final do menu.
Os grupos abrem e fecham clicando no titulo. O botao de tres barras no topo recolhe ou expande o menu e o navegador lembra sua escolha. No celular o menu abre como painel deslizante.

**Topo da tela**
- Titulo da pagina atual.
- **Busca rapida**: campo "Buscar paciente (nome, e-mail ou telefone)...". Digite e pressione **Enter** para abrir a lista de pacientes ja filtrada (/patients?q=...). Se voce nao tem permissao de ver pacientes, a busca nao abre a lista.
- Selo **CLINICA**.
- **Menu do usuario** (icone de pessoa): **Perfil** (/me) e **Sair**.
Botao verde **Falar com suporte** no canto inferior direito, quando a plataforma o habilita, abre o WhatsApp do suporte.

## Minha conta (Perfil)
<!-- perm: nenhuma (qualquer usuario logado) -->
**Onde fica:** icone de usuario no topo > Perfil (/me).

**Dados pessoais (botao Salvar)**
| Campo | Observacao |
|---|---|
| Nome completo | Obrigatorio |
| E-mail | Obrigatorio |
| Telefone | Formato (00) 00000-0000 |
| Tipo de documento | CPF ou CNPJ |
| Numero do documento | Segue o tipo escolhido |
| CEP, Rua, Numero, Complemento, Bairro, Cidade, UF | Endereco opcional, usado em cobrancas e notas |
**Alterar senha** (formulario separado): Senha atual, Nova senha (minimo 8 caracteres) e Confirmar nova senha, depois **Alterar senha**.
Clique em **Voltar** para retornar ao Dashboard.

## Sair
Icone de usuario no topo > **Sair**. Em computadores compartilhados sempre saia ao terminar. Apos sair voce volta para /login.

## Dados da clinica
<!-- perm: clinics.read (ver), clinics.update (salvar) -->
**Onde fica:** Menu > Configuracoes > Clinica (/clinic).
**Campos**
| Secao | Campos |
|---|---|
| Identificacao | Nome da clinica (obrigatorio) |
| Logo | Escolher imagem (PNG, JPG ou WEBP, ate 2 MB), aparece no cabecalho dos orcamentos. Botao Remover logo |
| Contato | E-mail, Telefone, WhatsApp |
| Endereco | CEP, Rua, Numero, Complemento, Bairro, Cidade, UF |
| Redes sociais e site | Site, Instagram, Facebook |
Clique em **Salvar**. Para aplicar o logo e preciso salvar depois de escolher a imagem. Erros: "Nome e obrigatorio.", "Selecione uma imagem."
Dentro do grupo Configuracoes > Agenda ficam Horarios e Feriados (ver 03-agenda).

## Configuracoes gerais
<!-- perm: settings.read (ver), settings.update (salvar) -->
**Onde fica:** Menu > Configuracoes > Geral (/settings).
- **Terminologia**: troque os rotulos Paciente/Cliente, Consulta/Sessao e Profissional/Especialista. O menu e as telas passam a usar os novos nomes. Tambem existe a pagina /settings/terminology com o mesmo formulario.
- **Agenda e anamnese**: **Inicio da semana** (dia em que a semana comeca na agenda) e **Anamnese padrao** (modelo enviado automaticamente ao confirmar uma consulta).
- Atalhos para WhatsApp, Templates WhatsApp, Inteligencia Artificial, Operacional (funil, motivos, origens), Google Calendar, Logs WhatsApp e Importador.
Clique em **Salvar configuracoes**.

## Usuarios
<!-- perm: users.read (lista), users.create, users.update, users.delete -->
**Onde fica:** Menu > Configuracoes > Usuarios e Acesso > Usuarios (/users).
A lista mostra Nome, E-mail, Papel, Status (Ativo, Desativado, Inativo) e Criado em, com paginacao.

**Criar usuario** (botao **+ Novo usuario**, /users/create)
1. Nome completo, E-mail de login, Senha, Papel (obrigatorios).
2. Clique em **Criar usuario**.
Erros: "Preencha todos os campos.", "Limite de usuarios do plano atingido." (faca upgrade do plano em Assinatura).

**Editar** (/users/edit): Nome completo, E-mail, Papel, Status, Nova senha (vazio mantem a atual), e em Dados profissionais: Especialidade e Numero do conselho (CRM, CRO etc., aparece nas receitas).

**Arquivar, Reativar, Excluir** (exigem users.delete)
- **Arquivar**: o usuario perde o acesso mas pode ser reativado depois.
- **Reativar**: devolve o acesso.
- **Excluir**: some da lista e nao entra mais.
Regras que geram erro: voce nao pode arquivar/excluir o seu proprio usuario e nao pode arquivar/excluir o ultimo proprietario da clinica.

## Papeis e permissoes
<!-- perm: rbac.manage -->
**Onde fica:** Menu > Configuracoes > Usuarios e Acesso > Papeis e Permissoes (/rbac). Aparece so para quem tem rbac.manage.
- A lista mostra cada papel com o selo **Sistema** (papeis de fabrica) ou **Personalizado**, e **Somente leitura** para o Owner.
- **+ Novo papel**: informe o nome (ex.: Enfermeiro) e clique em Criar. O papel nasce sem permissoes.
- **Editar permissoes** (/rbac/edit): as permissoes ficam agrupadas por modulo (Agenda, Pacientes, Prontuarios, Imagens Clinicas, Anamnese, Consentimento, Financeiro, Estoque, Marketing, Permissoes, Usuarios, Configuracoes, Clinica, Auditoria, Compliance, Relatorios, Procedimentos, Regras de Agenda, Profissionais, Bloqueios, Servicos, Modelos Prontuario). Em cada permissao marque **Permitir** ou **Negar**. **Negar** vence sempre sobre Permitir.
- Botoes **Permitir tudo** e **Limpar tudo** marcam todas ou nenhuma.
- **Salvar permissoes** grava.
- **Clonar este papel**: cria um novo papel com as mesmas permissoes.
- **Resetar para padrao**: restaura as permissoes de fabrica do papel.
- Depois de mudar um papel, os usuarios precisam sair e entrar de novo para valer.
Erros: "Nome e obrigatorio.", "Acesso negado." (papel Owner nao pode ser editado).

## Ajuda, Guia Completo e tutoriais do sistema
<!-- perm: nenhuma -->
**Onde fica:** Menu > Ajuda (/tutorial/sistema), Guia Completo (/tutorial/guia-completo) e Configuracao inicial (/tutorial/sistema/configuracao-inicial).
- **Ajuda** e o indice de tutoriais por assunto: Primeiros passos (/tutorial/sistema/primeiros-passos), Dashboard (/tutorial/sistema/dashboard), Agenda (/tutorial/sistema/agenda), Pacientes (/tutorial/sistema/pacientes), Prontuarios (/tutorial/sistema/prontuarios), Imagens (/tutorial/sistema/imagens), Financeiro (/tutorial/sistema/financeiro), Estoque (/tutorial/sistema/estoque), Servicos (/tutorial/sistema/servicos), Profissionais (/tutorial/sistema/profissionais), Configuracoes (/tutorial/sistema/configuracoes), Seguranca (/tutorial/sistema/seguranca), Portal do paciente (/tutorial/sistema/portal-paciente) e Integracoes e API (/tutorial/sistema/integracoes-api). Cada pagina tem o botao **Voltar para o indice** e **Ir para o sistema**.
- **Guia Completo** reune o manual em uma pagina e tem exportacao em PDF (/tutorial/guia-completo/export.pdf).
- Existem tutoriais voltados ao paciente em /tutorial/paciente, que explicam o Portal do Paciente para quem recebe o acesso.

## Mapa de rotas e acoes desta area
- /login (entrar), /logout (sair, botao Sair), /forgot e /reset (recuperar senha), /choose-access (escolher acesso), /criar-conta (nova clinica).
- /legal/required (lista de termos), /legal/sign (assinar), /legal/accept (aceite direto de um documento, usado pelo sistema; a tela atual pede assinatura em /legal/sign).
- /me (perfil), /me/change-password (botao Alterar senha).
- /clinic (dados da clinica), /clinic/logo (mostra o logo atual e recebe o envio), /clinic/logo/remove (botao Remover logo).
- /users, /users/create, /users/edit, /users/disable (Arquivar), /users/reactivate (Reativar), /users/delete (Excluir).
- /rbac, /rbac/edit, /rbac/create (Novo papel), /rbac/clone (Clonar), /rbac/reset (Resetar para padrao).
- /settings (Configuracoes gerais) e /settings/terminology (so a terminologia).

## Perguntas frequentes
**Esqueci minha senha, o que faco?** Em /login clique em Esqueci minha senha, digite o e-mail e clique em Gerar link. Abra o e-mail recebido e crie uma nova senha com no minimo 8 caracteres. O link vale 1 hora.

**O link de redefinicao diz "Token invalido ou expirado".** O link vale 1 hora e funciona uma vez. Gere um novo em /forgot.

**Nao vejo um item de menu (por exemplo Financeiro ou Papeis e Permissoes).** O menu mostra apenas o que o seu papel permite. Peca ao dono para liberar a permissao em Configuracoes > Usuarios e Acesso > Papeis e Permissoes e depois saia e entre de novo.

**Apareceu "Acesso negado".** Falta a permissao daquela tela. Informe ao dono a tela e o que voce queria fazer.

**Como troco de clinica?** O login e por clinica. Saia e, na tela Escolha como entrar, selecione a outra clinica (aparece quando o mesmo e-mail e senha existem em mais de uma).

**Como mudo meu telefone ou minha senha?** Icone de usuario no topo > Perfil. Dados pessoais e Alterar senha ficam em formularios separados, cada um com seu botao.

**Como troco "Paciente" por "Cliente" no sistema?** Configuracoes > Geral > Terminologia, altere os campos e salve.

**Como cadastro um novo funcionario?** Configuracoes > Usuarios e Acesso > Usuarios > + Novo usuario, escolha o papel. Se for profissional de atendimento, cadastre tambem em /professionals (ver 06-servicos-profissionais).

**O login diz "Muitas requisicoes".** Houve muitas tentativas. Espere 15 minutos.

**Por onde comeco a configurar a clinica?** Ajuda > Configuracao inicial e siga os 7 passos.
