---
title: Servicos, categorias, materiais, procedimentos e profissionais
routes:
  - /services
  - /procedures
  - /professionals
---

# Servicos, procedimentos e profissionais

## Servico, procedimento e profissional: qual a diferenca
- **Servico** e o que o paciente contrata e aparece na Agenda e nas vendas. Tem nome, duracao, preco, categoria e cor.
- **Procedimento (protocolo clinico)** e o roteiro tecnico do atendimento: contraindicacoes, orientacoes pre e pos e etapas. Serve de consulta para o profissional durante a consulta quando esta vinculado a um servico.
- **Profissional** e quem atende. Todo profissional esta ligado a um **usuario** de login.

Ordem sugerida de cadastro: profissionais e usuarios, regras de agenda (03-agenda), categorias, servicos, procedimentos e, se usar estoque, o vinculo de materiais.

## Servicos
<!-- perm: services.manage -->
**Onde fica:** Menu > Configuracoes > Servicos > Cadastro (/services).

A tela abre com a lista de servicos: bolinha de cor, Nome (com o procedimento vinculado, se houver), Categoria, Duracao em minutos, Preco e as acoes **Editar**, **Materiais** e **Excluir**.

### Criar servico
1. Clique em **+ Novo servico**.
2. Preencha os campos:
| Campo | Obrigatorio | Observacao |
|---|---|---|
| Nome do servico | Sim | Ex.: Consulta Dermatologica, Limpeza de Pele |
| Duracao (minutos) | Sim | Tempo que o servico ocupa na agenda. Precisa ser maior que zero |
| Preco (R$) | Nao | Formato 0,00 |
| Categoria | Nao | Veja Categorias de servico |
| Cor na agenda | Nao | Cor que identifica o servico nos cartoes da agenda |
| Procedimento vinculado | Nao | Liga o servico a um protocolo clinico e permite rastrear custos de materiais |
3. Clique em **Criar servico**.
Se o nome estiver vazio, a duracao for zero ou o preco tiver formato invalido, o sistema volta para a lista sem criar o servico e sem mensagem. Revise esses campos.

### Editar servico
Na linha do servico clique em **Editar**: abre a edicao na propria linha com Nome, Duracao (min), Preco (R$), Cor, Categoria e Protocolo clinico. **Salvar** grava, **Cancelar** fecha.

### Excluir servico
Botao **Excluir** (confirma "Excluir este servico?"). O servico some da lista e deixa de ser oferecido na agenda e nas vendas. Agendamentos ja feitos continuam existindo.

### Efeito do servico na agenda
A duracao define o tamanho do bloco do agendamento e quais horarios livres sao oferecidos. Servicos precisam existir antes de agendar.

## Categorias de servico
<!-- perm: services.manage -->
**Onde fica:** /services/categories (link **Gerenciar categorias de servico** no rodape da lista de servicos).
- Em **Nova categoria** informe o **Nome** e clique em **Salvar**. Erro: "Categoria ja existe."
- A lista mostra as categorias com o botao **Excluir**.
- Use as categorias para agrupar servicos (ex.: Facial, Corporal, Consultas). Elas tambem servem de filtro na Operacao da Agenda.

## Vinculo de materiais com o servico (estoque)
<!-- perm: services.manage -->
**Onde fica:** Menu > Configuracoes > Servicos > Vinculo com estoque (/services/materials) ou o botao **Materiais** na linha do servico.
Define quais materiais o servico consome por sessao. Ao concluir o atendimento, o sistema baixa esses materiais do estoque.
1. Em **Selecione um servico** escolha o servico e clique em **Abrir**.
2. Clique em **+ Adicionar material**, escolha o **Material** e a **Qtd por sessao** (ex.: 5) e clique em **Adicionar**.
3. Na lista **Materiais vinculados** use **Editar** para mudar a quantidade (**Salvar** ou **Cancelar**) e **Remover** para desvincular.
Na hora de concluir o atendimento a quantidade pode ser ajustada e podem ser acrescentados materiais extras (ver Finalizar sessao em 03-agenda). Erros: "Servico invalido.", "Material invalido."

## Procedimentos (protocolos clinicos)
<!-- perm: procedures.manage -->
**Onde fica:** Menu > Configuracoes > Servicos > Procedimentos (/procedures).

### Lista
- **+ Novo procedimento**: informe o **Nome do procedimento** (ex.: Botox) e clique em **Criar**. Erro: "Informe o nome."
- A lista mostra cada procedimento com o selo Desativado quando for o caso, a Duracao media (soma das etapas), **Editar** e **Excluir** (exclui de forma permanente, com confirmacao).
- A lista exibe todos, inclusive os desativados.

### Editar procedimento
/procedures/edit?id=... tem estes blocos, cada um com seu botao **Salvar**:
- **Informacoes do protocolo:** Nome e Status (Ativo ou Desativado).
- **Contraindicacoes**, **Orientacoes pre-procedimento** e **Orientacoes pos-procedimento**: textos livres que o profissional ve durante a consulta.
- **Protocolos e etapas:** **+ Novo protocolo** (Nome do protocolo e Notas opcionais). Dentro de cada protocolo: renomear (**Salvar**), **Remover** (apaga o protocolo e todas as etapas) e a lista de etapas numeradas. Para cada etapa informe **Titulo da etapa** e **Duracao** em minutos e clique em **Adicionar**; o x remove a etapa.
Mensagens: "Criado.", "Salvo.", "Protocolo criado.", "Protocolo salvo.", "Protocolo removido.", "Etapa criada.", "Etapa salva.", "Etapa removida.", "Protocolo invalido.", "Etapa invalida.", "Procedimento excluido."

### Como o profissional usa
Vincule o procedimento ao servico (Servicos > Editar > Protocolo clinico). Na execucao da consulta (/patients/consultation) aparecem as contraindicacoes, orientacoes e etapas. O nome do servico tambem vai como Procedimento no novo registro de prontuario.

## Profissionais
<!-- perm: professionals.manage -->
**Onde fica:** /professionals. Nao existe item proprio no menu lateral: digite o endereco ou use o link do aviso de numero de conselho na tela de receitas. Especialidade e Numero do conselho tambem podem ser ajustados em Configuracoes > Usuarios e Acesso > Usuarios > Editar, na area Dados profissionais.

### Cadastrar profissional
1. Em **Como sera o acesso do profissional?** escolha:
   - **Vincular a um usuario ja cadastrado**: selecione o **usuario de login** na lista.
   - **Criar novo usuario (com e-mail e senha)**: preencha **Nome do usuario**, **E-mail de login** e **Senha**.
2. Informe a **Especialidade** (texto livre) e **Agendamento online?** (Nao ou Sim).
3. Clique em **Salvar**.
Erros: "Preencha nome, e-mail e senha do usuario.", "E-mail invalido.", "Falha ao salvar senha.", "Selecione um usuario para vincular.", "Usuario nao encontrado.", "Este usuario ja esta vinculado a um profissional."

### Lista de profissionais
Colunas Nome, Especialidade, Usuario, Online e as acoes **Editar** e **Excluir**.

### Editar
/professionals/edit?id=...: **Nome completo** (obrigatorio), **Especialidade** e **No do conselho** (CRM, CRO etc., aparece no receituario e na impressao de receitas), depois **Salvar**. Erros: "Preencha o nome.", "Profissional nao encontrado."

### Excluir (inativar)
Botao **Excluir** ou, na edicao, **Excluir profissional > Confirmar exclusao**. O profissional e desativado e nao aparece mais na agenda. Os agendamentos antigos permanecem.

### Horarios do profissional
Os dias e horarios de atendimento de cada profissional ficam em Configuracoes > Agenda > Regras (ver 03-agenda). Sem regra, o profissional nao tem horarios livres.

### Permissoes de quem atende
Usuarios com o papel **Profissional** veem apenas a propria agenda. Isso so funciona se o usuario estiver vinculado a um profissional. Sem o vinculo a agenda mostra o erro "Profissional nao vinculado ao usuario."

### Especialidades e comissoes
- A especialidade e um texto livre em cada profissional. Nao existe cadastro separado de especialidades.
- O sistema nao possui cadastro de comissoes de profissionais.

## Mapa de rotas e acoes de servicos e profissionais
- Servicos: /services (lista), /services/create (Criar servico), /services/update (Salvar), /services/delete (Excluir).
- Categorias: /services/categories, /services/categories/create, /services/categories/delete.
- Materiais do servico: /services/materials, /services/materials/create (Adicionar), /services/materials/update (Salvar quantidade), /services/materials/delete (Remover).
- Procedimentos: /procedures, /procedures/create, /procedures/edit (Salvar), /procedures/delete, /procedures/protocols/create, /procedures/protocols/update, /procedures/protocols/delete, /procedures/steps/create, /procedures/steps/update, /procedures/steps/delete.
- Profissionais: /professionals, /professionals/create (Salvar), /professionals/edit, /professionals/delete (Excluir).

## Perguntas frequentes
**Como cadastro um novo servico?** Configuracoes > Servicos > Cadastro > + Novo servico. Informe nome e duracao (obrigatorios), preco, categoria e cor e clique em Criar servico.

**Cliquei em Criar servico e nada aconteceu.** Confira o nome e a duracao. Sem eles, ou com preco em formato invalido, a tela volta sem criar e sem mensagem.

**Qual a diferenca entre Servico e Procedimento?** Servico e o que aparece na agenda e nas vendas, com duracao e preco. Procedimento e o roteiro clinico (contraindicacoes, orientacoes, etapas) que se liga a um servico.

**O servico nao aparece ao agendar.** Verifique se ele nao foi excluido. Se aparece, mas sem horarios, revise as Regras do profissional e os horarios da clinica.

**Como controlo o consumo de material por servico?** Configuracoes > Servicos > Vinculo com estoque. Escolha o servico e adicione os materiais com a quantidade por sessao. Ao concluir o atendimento o estoque baixa.

**Como cadastro um profissional?** Acesse /professionals, escolha vincular a um usuario existente ou criar um usuario novo, informe a especialidade e salve. Depois cadastre os horarios em Configuracoes > Agenda > Regras.

**O profissional nao aparece na agenda.** Confira se ele esta cadastrado e ativo em /professionals e se tem regras de horario nos dias desejados.

**Onde coloco o CRM do profissional para sair na receita?** Em /professionals/edit (No do conselho) ou em Usuarios > Editar > Dados profissionais.

**Como desativo um profissional que saiu da clinica?** Em /professionals use Excluir (ele e inativado, nao apagado). Se for preciso, arquive tambem o usuario dele em Usuarios.

**Existe cadastro de comissoes?** Nao. O sistema nao tem comissoes por profissional.

**Como altero o preco de um servico?** Servicos > Editar na linha do servico, altere o Preco (R$) e clique em Salvar. Agendamentos e orcamentos ja feitos nao mudam.

**Como excluo uma categoria de servico?** /services/categories > Excluir na linha. Se a categoria ja existe com o mesmo nome, o sistema avisa "Categoria ja existe." ao criar.

**Como o profissional ve as orientacoes do procedimento?** Vincule o procedimento ao servico. Na execucao da consulta do paciente aparecem contraindicacoes, orientacoes pre e pos e as etapas.
