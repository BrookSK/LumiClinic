---
title: Serviços, categorias, materiais, procedimentos e profissionais
routes:
  - /services
  - /procedures
  - /professionals
---

# Serviços, procedimentos e profissionais

## Serviço, procedimento e profissional: qual a diferença
- **Serviço** é o que o paciente contrata e aparece na Agenda e nas vendas. Têm nome, duração, preço, categoria e cor.
- **Procedimento (protocolo clínico)** é o roteiro técnico do atendimento: contraindicações, orientações pré e pós e etapas. Serve de consulta para o profissional durante a consulta quando está vinculado a um serviço.
- **Profissional** e quem atende. Todo profissional está ligado a um **usuário** de login.

Ordem sugerida de cadastro: profissionais e usuários, regras de agenda (03-agenda), categorias, serviços, procedimentos e, se usar estoque, o vínculo de materiais.

## Serviços
<!-- perm: services.manage -->
**Onde fica:** Menu > Configurações > Serviços > Cadastro (/services).

A tela abre com a lista de serviços: bolinha de cor, Nome (com o procedimento vinculado, se houver), Categoria, Duração em minutos, Preço e as ações **Editar**, **Materiais** e **Excluir**.

### Criar serviço
1. Clique em **+ Novo serviço**.
2. Preencha os campos:
| Campo | Obrigatório | Observação |
|---|---|---|
| Nome do serviço | Sim | Ex.: Consulta Dermatológica, Limpeza de Pele |
| Duração (minutos) | Sim | Tempo que o serviço ocupa na agenda. Precisa ser maior que zero |
| Preço (R$) | Não | Formato 0,00 |
| Categoria | Não | Veja Categorias de serviço |
| Cor na agenda | Não | Cor que identifica o serviço nos cartões da agenda |
| Procedimento vinculado | Não | Liga o serviço a um protocolo clínico e permite rastrear custos de materiais |
3. Clique em **Criar serviço**.
Se o nome estiver vazio, a duração for zero ou o preço tiver formato inválido, o sistema volta para a lista sem criar o serviço e sem mensagem. Revise esses campos.

### Editar serviço
Na linha do serviço clique em **Editar**: abre a edição na própria linha com Nome, Duração (min), Preço (R$), Cor, Categoria e Protocolo clínico. **Salvar** grava, **Cancelar** fecha.

### Excluir serviço
Botão **Excluir** (confirma "Excluir este serviço?"). O serviço some da lista e deixa de ser oferecido na agenda e nas vendas. Agendamentos já feitos continuam existindo.

### Efeito do serviço na agenda
A duração define o tamanho do bloco do agendamento e quais horários livres são oferecidos. Serviços precisam existir antes de agendar.

## Categorias de serviço
<!-- perm: services.manage -->
**Onde fica:** /services/categories (link **Gerenciar categorias de serviço** no rodapé da lista de serviços).
- Em **Nova categoria** informe o **Nome** e clique em **Salvar**. Erro: "Categoria já existe."
- A lista mostra as categorias com o botão **Excluir**.
- Use as categorias para agrupar serviços (ex.: Facial, Corporal, Consultas). Elas também servem de filtro na Operação da Agenda.

## Vínculo de materiais com o serviço (estoque)
<!-- perm: services.manage -->
**Onde fica:** Menu > Configurações > Serviços > Vínculo com estoque (/services/materials) ou o botão **Materiais** na linha do serviço.
Define quais materiais o serviço consome por sessão. Ao concluir o atendimento, o sistema baixa esses materiais do estoque.
1. Em **Selecione um serviço** escolha o serviço e clique em **Abrir**.
2. Clique em **+ Adicionar material**, escolha o **Material** e a **Qtd por sessão** (ex.: 5) e clique em **Adicionar**.
3. Na lista **Materiais vinculados** use **Editar** para mudar a quantidade (**Salvar** ou **Cancelar**) e **Remover** para desvincular.
Na hora de concluir o atendimento a quantidade pode ser ajustada e podem ser acrescentados materiais extras (ver Finalizar sessão em 03-agenda). Erros: "Serviço inválido.", "Material inválido."

## Procedimentos (protocolos clínicos)
<!-- perm: procedures.manage -->
**Onde fica:** Menu > Configurações > Serviços > Procedimentos (/procedures).

### Lista
- **+ Novo procedimento**: informe o **Nome do procedimento** (ex.: Botox) e clique em **Criar**. Erro: "Informe o nome."
- A lista mostra cada procedimento com o selo Desativado quando for o caso, a Duração média (soma das etapas), **Editar** e **Excluir** (exclui de forma permanente, com confirmação).
- A lista exibe todos, inclusive os desativados.

### Editar procedimento
/procedures/edit?id=... tem estes blocos, cada um com seu botão **Salvar**:
- **Informações do protocolo:** Nome e Status (Ativo ou Desativado).
- **Contraindicações**, **Orientações pré-procedimento** e **Orientações pós-procedimento**: textos livres que o profissional vê durante a consulta.
- **Protocolos e etapas:** **+ Novo protocolo** (Nome do protocolo e Notas opcionais). Dentro de cada protocolo: renomear (**Salvar**), **Remover** (apaga o protocolo e todas as etapas) e a lista de etapas numeradas. Para cada etapa informe **Título da etapa** e **Duração** em minutos e clique em **Adicionar**; o x remove a etapa.
Mensagens: "Criado.", "Salvo.", "Protocolo criado.", "Protocolo salvo.", "Protocolo removido.", "Etapa criada.", "Etapa salva.", "Etapa removida.", "Protocolo inválido.", "Etapa inválida.", "Procedimento excluído."

### Como o profissional usa
Vincule o procedimento ao serviço (Serviços > Editar > Protocolo clínico). Na execução da consulta (/patients/consultation) aparecem as contraindicações, orientações e etapas. O nome do serviço também vai como Procedimento no novo registro de prontuário.

## Profissionais
<!-- perm: professionals.manage -->
**Onde fica:** /professionals. Não existe item próprio no menu lateral: digite o endereço ou use o link do aviso de número de conselho na tela de receitas. Especialidade e Número do conselho também podem ser ajustados em Configurações > Usuários e Acesso > Usuários > Editar, na área Dados profissionais.

### Cadastrar profissional
1. Em **Como será o acesso do profissional?** escolha:
   - **Vincular a um usuário já cadastrado**: selecione o **usuário de login** na lista.
   - **Criar novo usuário (com e-mail e senha)**: preencha **Nome do usuário**, **E-mail de login** e **Senha**.
2. Informe a **Especialidade** (texto livre) e **Agendamento online?** (Não ou Sim).
3. Clique em **Salvar**.
Erros: "Preencha nome, e-mail e senha do usuário.", "E-mail inválido.", "Falha ao salvar senha.", "Selecione um usuário para vincular.", "Usuário não encontrado.", "Este usuário já está vinculado a um profissional."

### Lista de profissionais
Colunas Nome, Especialidade, Usuário, Online e as ações **Editar** e **Excluir**.

### Editar
/professionals/edit?id=...: **Nome completo** (obrigatório), **Especialidade** e **No do conselho** (CRM, CRO etc., aparece no receituário e na impressão de receitas), depois **Salvar**. Erros: "Preencha o nome.", "Profissional não encontrado."

### Excluir (inativar)
Botão **Excluir** ou, na edição, **Excluir profissional > Confirmar exclusão**. O profissional é desativado e não aparece mais na agenda. Os agendamentos antigos permanecem.

### Horários do profissional
Os dias e horários de atendimento de cada profissional ficam em Configurações > Agenda > Regras (ver 03-agenda). Sem regra, o profissional não tem horários livres.

### Permissões de quem atende
Usuários com o papel **Profissional** veem apenas a própria agenda. Isso só funciona se o usuário estiver vinculado a um profissional. Sem o vínculo a agenda mostra o erro "Profissional não vinculado ao usuário."

### Especialidades e comissões
- A especialidade é um texto livre em cada profissional. Não existe cadastro separado de especialidades.
- O sistema não possui cadastro de comissões de profissionais.

## Mapa de rotas e ações de serviços e profissionais
- Serviços: /services (lista), /services/create (Criar serviço), /services/update (Salvar), /services/delete (Excluir).
- Categorias: /services/categories, /services/categories/create, /services/categories/delete.
- Materiais do serviço: /services/materials, /services/materials/create (Adicionar), /services/materials/update (Salvar quantidade), /services/materials/delete (Remover).
- Procedimentos: /procedures, /procedures/create, /procedures/edit (Salvar), /procedures/delete, /procedures/protocols/create, /procedures/protocols/update, /procedures/protocols/delete, /procedures/steps/create, /procedures/steps/update, /procedures/steps/delete.
- Profissionais: /professionals, /professionals/create (Salvar), /professionals/edit, /professionals/delete (Excluir).

## Perguntas frequentes
**Como cadastro um novo serviço?** Configurações > Serviços > Cadastro > + Novo serviço. Informe nome e duração (obrigatórios), preço, categoria e cor e clique em Criar serviço.

**Cliquei em Criar serviço e nada aconteceu.** Confira o nome e a duração. Sem eles, ou com preço em formato inválido, a tela volta sem criar e sem mensagem.

**Qual a diferença entre Serviço e Procedimento?** Serviço é o que aparece na agenda e nas vendas, com duração e preço. Procedimento é o roteiro clínico (contraindicações, orientações, etapas) que se liga a um serviço.

**O serviço não aparece ao agendar.** Verifique se ele não foi excluído. Se aparece, mas sem horários, revise as Regras do profissional e os horários da clínica.

**Como controlo o consumo de material por serviço?** Configurações > Serviços > Vínculo com estoque. Escolha o serviço e adicione os materiais com a quantidade por sessão. Ao concluir o atendimento o estoque baixa.

**Como cadastro um profissional?** Acesse /professionals, escolha vincular a um usuário existente ou criar um usuário novo, informe a especialidade e salve. Depois cadastre os horários em Configurações > Agenda > Regras.

**O profissional não aparece na agenda.** Confira se ele está cadastrado e ativo em /professionals e se tem regras de horário nos dias desejados.

**Onde coloco o CRM do profissional para sair na receita?** Em /professionals/edit (No do conselho) ou em Usuários > Editar > Dados profissionais.

**Como desativo um profissional que saiu da clínica?** Em /professionals use Excluir (ele é inativado, não apagado). Se for preciso, arquive também o usuário dele em Usuários.

**Existe cadastro de comissões?** Não. O sistema não tem comissões por profissional.

**Como altero o preço de um serviço?** Serviços > Editar na linha do serviço, altere o Preço (R$) e clique em Salvar. Agendamentos e orçamentos já feitos não mudam.

**Como excluo uma categoria de serviço?** /services/categories > Excluir na linha. Se a categoria já existe com o mesmo nome, o sistema avisa "Categoria já existe." ao criar.

**Como o profissional vê as orientações do procedimento?** Vincule o procedimento ao serviço. Na execução da consulta do paciente aparecem contraindicações, orientações pré e pós e as etapas.
