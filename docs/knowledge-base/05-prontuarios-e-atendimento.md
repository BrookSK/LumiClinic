---
title: Prontuário, atendimento, receitas, anamnese, termos e imagens clínicas
routes:
  - /medical-records
  - /medical-record-templates
  - /anamnesis
  - /a/anamnese
  - /consent
  - /consent-terms
  - /signatures
  - /medical-images
  - /patients/prescriptions
  - /patients/prescription
  - /patients/consultation
---

# Prontuário e atendimento

## Como o atendimento flui
1. Na Agenda, o paciente chega e a recepção clica em **Chegou (check-in)**.
2. O profissional clica em **Iniciar atendimento** (na agenda ou na Fila de chegada). O sistema muda o status para Em atendimento e abre o formulário de **novo registro de prontuário** já com paciente, data e hora, profissional e procedimento preenchidos.
3. O profissional registra descrição, evolução, materiais, imagens e notas, e salva.
4. Ao terminar, clica em **Finalizar atendimento** (ou em Concluir na agenda). A tela Finalizar sessão confirma materiais e observação e conclui o agendamento (ver 03-agenda).
Alertas clínicos do paciente (alergias, contraindicações, avisos) aparecem em vermelho no topo do prontuário para não passarem despercebidos.

## Lista de registros do prontuário
<!-- perm: medical_records.read -->
**Onde fica:** perfil do paciente > **Prontuário** (/medical-records?patient_id=...).
- Cabeçalho com o nome do paciente e selos dos alertas clínicos ("Sem alertas clínicos" quando não há).
- Botões **Voltar ao paciente** e **+ Novo registro** (precisa de medical_records.create).
- Cada registro aparece como uma linha com data, procedimento, profissional e um resumo. Clique na linha para abrir os blocos **Descrição clínica**, **Evolução** e **Notas**. O botão **Editar** abre a edição.
- Sem registros aparece "Nenhum registro ainda" com o link **Criar primeiro registro**.

## Criar registro de prontuário
<!-- perm: medical_records.create -->
**Onde fica:** /medical-records/create?patient_id=... (botão + Novo registro ou Iniciar atendimento na agenda).

| Campo | Obrigatório | Observação |
|---|---|---|
| Data/hora | Sim | Quando o atendimento aconteceu. Vem preenchido pela agenda |
| Profissional | Não | Vem fixo quando o registro nasce de um agendamento. Senão é uma lista (opcional) |
| Procedimento | Não | Texto livre, ex.: Botox, Limpeza. Vem do nome do serviço quando criado pela agenda |
| Descrição clínica | Não | Texto longo com botão de microfone (🎤) |
| Evolução | Não | Texto longo com botão de microfone |
| Produtos e materiais usados | Não | Linhas com Material, Qtd, Lote (opcional) e Descrição (opcional). **+ Adicionar material** cria uma linha |
| 📷 Anexar imagens ao prontuário | Não | Várias imagens ou PDF de uma vez, ligadas a este prontuário |
| Notas | Não | Texto longo com botão de microfone |

- **Salvar registro** grava e abre a tela de edição do registro. **Cancelar** descarta.
- Botão **✓ Finalizar atendimento** no cabeçalho (aparece quando veio de um agendamento) abre a tela Finalizar sessão.
- Abaixo do formulário fica o **Histórico** dos últimos registros do paciente, com **Mostrar mais** e **Ver tudo**.
- **Materiais descontam do estoque** na hora de salvar. Se a quantidade pedida é maior que o saldo, aquela linha não é gravada (sem aviso na tela), então confira o estoque antes. Cada baixa cria uma movimentação de estoque de saída ligada ao prontuário.
- Imagens que não puderem ser enviadas (formato inválido ou limite do plano) ficam de fora do registro, mas o texto e salvo.
Erro: "Data/hora do atendimento é obrigatória."

### Ditado por voz (microfone)
O botão 🎤 (dica "Gravar") ao lado de Descrição clínica, Evolução e Notas grava sua voz e transcreve para o texto.
1. Clique no 🎤 (o navegador pede permissão de uso do microfone).
2. Fale. A tela mostra "Gravando..." com o tempo.
3. Clique de novo (o botão vira ■, dica "Parar") para parar. Aparece "Transcrevendo..." e o texto entra no campo.
Mensagens: "Sem suporte a microfone.", "Não suportado neste navegador.", "Restam N min de transcrição este mês.", "Limite de transcrição atingido. Faça upgrade do plano.", "Transcrição vazia.", "Falha ao transcrever." (a IA precisa estar disponível para a clínica; o limite depende do plano). Exige medical_records.create.

## Editar registro de prontuário
<!-- perm: medical_records.update -->
**Onde fica:** /medical-records/edit?patient_id=...&id=... (botão Editar no registro).
- Campos editáveis: Data/hora, Procedimento, Profissional, Descrição clínica, Evolução e Notas. Clique em **Salvar**.
- Cada edição guarda uma **versão** do registro, então o histórico do que foi alterado não se perde.
- A tela mostra também **Materiais usados neste prontuário** (Material, Quantidade, Lote, Descrição) e **Imagens vinculadas a este prontuário** (clique para marcar a imagem).
- Atalhos no cabeçalho: **Ficha clínica**, **Imagens** e **Voltar**.
Erros: "Preencha os campos obrigatórios.", "Data/hora do atendimento é obrigatória."

## Modelos de prontuário
<!-- perm: medical_record_templates.manage -->
**Onde fica:** Menu > Configurações > Modelos de prontuário (/medical-record-templates).
- A lista mostra ID, Nome, Status e **Editar**. **Novo template** abre o cadastro.
- No cadastro informe o **Nome** (obrigatório) e monte os **Campos**: clique em **Adicionar campo** e defina Ordem, Rótulo, Tipo (Texto, Texto longo, Checkbox, Select, Número, Data), Obrigatório (Sim ou Não) e, para Select, as Opções. Clique em **Criar**.
- Na edição o modelo pode ser ativado ou desativado.
- Observação: o formulário atual de Novo registro usa os campos fixos Descrição clínica, Evolução e Notas. Os modelos ficam cadastrados para padronizar a clínica.
Erros: "Nome é obrigatório.", "Preencha os campos obrigatórios."

## Receituário (prescrições)
<!-- perm: medical_records.read (ver, imprimir), medical_records.create (criar, editar, excluir) -->
**Onde fica:** perfil do paciente > **Receituário** (/patients/prescriptions?patient_id=...).
1. No cartão **Nova receita** confira os dados do **Paciente** (avisos "⚠ CPF não cadastrado" e "⚠ Endereço não cadastrado" com link **cadastrar**) e do **Prescritor**.
2. Escolha o **Profissional que prescreve**. Se o profissional não tem o campo **Nº do conselho (CRM, CRO, etc.)** preenchido no cadastro dele, aparece o aviso "⚠ Nº do conselho não cadastrado" com link para cadastrar.
3. Preencha **Título** (padrão Receita), **Data de emissão** e **Conteúdo da receita** (obrigatório).
4. Clique em **Salvar receita** ("Receita criada.").
Na lista **Receitas emitidas** há **Editar** (/patients/prescription/edit, campos Título, Data de emissão e Conteúdo), **Imprimir** (/patients/prescription/print, abre a página de impressão com cabeçalho da clínica, dados do paciente, texto e linha de assinatura com nome e conselho do profissional; use o botão Imprimir) e **Excluir** ("Receita excluída.").
Erros: "Preencha os campos obrigatórios.", "Dados inválidos."

## Anamnese: modelos
<!-- perm: anamnesis.manage -->
**Onde fica:** Menu > Configurações > Anamnese (/anamnesis/templates).
- Lista de cartões com nome, status e quantidade de campos, com **Editar**. **+ Novo template** cria um.
- No cadastro informe o **Nome do template** (obrigatório) e clique em **+ Adicionar campo** para cada pergunta (campo **Pergunta**, com o tipo ao lado e, para múltipla escolha, **Opções (uma por linha)**). Tipos de campo: **Texto curto**, **Texto longo**, **Sim/Não**, **Múltipla escolha** (uma opção por linha), **Número** e **Data**. Clique em **Criar template**.
- Na edição é possível mudar o **Status** (Ativo ou inativo) e os campos.
Erros: "Nome é obrigatório.", "Preencha os campos obrigatórios."

## Anamnese: preencher ou enviar ao paciente
<!-- perm: anamnesis.fill -->
**Onde fica:** perfil do paciente > **Anamnese** (/anamnesis?patient_id=...).
1. Em **Nova anamnese** escolha o **Template**.
2. **Preencher aqui** abre o formulário na clínica (/anamnesis/fill): responda os campos, escolha o **Profissional** (opcional), peça ao paciente para assinar no quadro (dedo ou mouse; **Limpar** refaz) e clique em **Salvar anamnese**.
3. **Enviar ao paciente ▾** abre o painel de envio. O paciente recebe um link para preencher e assinar. Marque **Não exigir login para preenchimento** para ele abrir direto pelo link. Escolha o canal e clique no botão dele:
   - **📱 WhatsApp** (botão **Enviar via WhatsApp**): precisa de telefone e de permissão de WhatsApp do paciente. Mensagens: "Paciente sem telefone cadastrado." ou "Paciente sem opt-in de WhatsApp."
   - **✉ E-mail** (botão **Enviar por e-mail**): precisa de e-mail ("Paciente sem e-mail cadastrado.").
   - **🌐 Portal** (botão **Disponibilizar no portal**): disponibiliza no portal do paciente.
   O link vale 7 dias.
4. O paciente abre o link (/a/anamnese), preenche, assina e clica em **Enviar anamnese**. Aparece "Anamnese enviada!". Link vencido mostra "Link inválido ou expirado."
5. Em **Respostas** ficam todas as anamneses do paciente com Data, Template e Assinatura (Assinado ou Sem assinatura), **Ver** (/anamnesis/response) e **PDF** (/anamnesis/export-pdf).
Quando uma consulta e confirmada na agenda, o modelo definido em Configurações > Geral, campo Anamnese padrão e enviado automaticamente.

## Termos de consentimento
<!-- perm: consent_terms.manage (cadastrar termos), consent_terms.accept (coletar aceite e assinatura) -->
**Cadastrar termos:** abra /consent-terms (não há item no menu; a tela é identificada como Consentimento (Legado)). A lista mostra ID, Procedimento, Título, Status e **Editar**. **Novo termo**: escolha o **Procedimento** (ou **Outro (digitar)**), informe **Título** e **Corpo do termo** e clique em **Criar**. Erro: "Preencha todos os campos."

**Coletar aceite do paciente:** perfil do paciente > **Termos** (/consent?patient_id=...). A tela lista os **Aceites registrados** (Data, Termo, Procedimento, Assinatura) com **Ver** (/consent/export, mostra o texto aceito e a assinatura) e **Assinatura** (imagem). Para um novo aceite a tela de assinatura (/consent/accept?patient_id=...&term_id=...) mostra o texto do termo, o quadro de assinatura (**Limpar**) e **Confirmar e salvar**. Se o botão **+ Novo aceite** da lista voltar para a lista de pacientes, use o envio de documento para assinatura (Pacientes > perfil > Enviar p/ Assinatura) ou abra o termo desejado pelo endereço com o term_id.
Abrir a imagem da assinatura exige files.read.

## Imagens clínicas
<!-- perm: medical_images.read (ver), medical_images.upload (enviar, marcar, recortar), files.read (abrir arquivos) -->
**Onde fica:** perfil do paciente > **Imagens** (/medical-images?patient_id=...).
- **+ Imagem**: campo **Arquivo (JPG, PNG, WebP)** (obrigatório), **Tipo** (Foto, Acompanhamento, Exame, Outro), **Procedimento (opcional)**, **Data (opcional)** e **Descrição (opcional)** (com **Gravar áudio** para ditar). Clique em **Enviar**. As imagens são comprimidas e convertidas para WebP, e reduzidas quando passam de 2048 pixels, para economizar espaço.
- **+ Antes/Depois**: envie **Antes** e **Depois** (obrigatórios), **Procedimento** e **Data** e clique em **Criar comparação**.
- A seção **Comparações** mostra cartões que abrem o comparador. A **galeria** mostra miniaturas com **Ver**, **Marcar** e o link para o prontuário de origem.
- **Linha do tempo das imagens** (/medical-images/timeline): imagens agrupadas por procedimento e sessão, com Tipo, Data, Ângulo/Posição, Arquivo, **Abrir** e **Comparar**.
- **Comparador** (tela "Comparação Antes / Depois", /medical-images/compare): arraste o controle central para ver ANTES e DEPOIS lado a lado; atalhos **Marcar Antes** e **Marcar Depois**.
- **Marcações** (tela "Marcações na imagem", /medical-images/annotate): ferramentas Retângulo, Círculo, Seta, Linha, Traço livre, Ponto, Texto e Medida; campo **Texto** (com **Gravar áudio**), **Cor** e **Salvar marcação**. A lista **Marcações** permite remover. **✂ Recortar** ativa o "Modo recorte": arraste sobre a imagem e escolha **✂ Recortar esta imagem** ou **📋 Salvar como cópia**. **Ver original** abre o arquivo.
- **Moderação** (tela "Uploads pendentes (Portal)", /medical-images/moderation): imagens enviadas pelo paciente ficam pendentes com **Aprovar** (campo "Obs (opcional)") e **Rejeitar** (campo "Motivo (opcional)"). Não há item no menu: abra pelo endereço.
Erros: "Formato não suportado. Use JPG/PNG/WEBP.", "Limite de armazenamento do plano atingido.", "Arquivo inválido.", "Falha no upload."

## Mapa de rotas e ações de prontuário, anamnese, termos e imagens
- Prontuário: /medical-records, /medical-records/create (Salvar registro), /medical-records/edit (Salvar), /medical-records/audio/transcribe-json (ditado por voz), /medical-records/audio/transcribe (versão sem tela), /medical-records/audio/transcription-status (confere o limite mensal).
- Modelos de prontuário: /medical-record-templates, /medical-record-templates/create, /medical-record-templates/edit.
- Anamnese: /anamnesis (lista), /anamnesis/fill (formulário e Salvar anamnese), /anamnesis/response (Ver), /anamnesis/export (versão de impressão), /anamnesis/export-pdf (PDF), /anamnesis/send-link (Enviar ao paciente), /anamnesis/templates, /anamnesis/templates/create, /anamnesis/templates/edit, e a página pública /a/anamnese.
- Termos: /consent-terms, /consent-terms/create, /consent-terms/edit, /consent, /consent/accept, /consent/export, /signatures/file (imagem da assinatura).
- Imagens: /medical-images, /medical-images/upload (Enviar), /medical-images/upload-pair (Criar comparação), /medical-images/timeline, /medical-images/compare, /medical-images/file (mostra a imagem), /medical-images/annotate, /medical-images/annotations.json (carrega marcações), /medical-images/annotations/create (Salvar marcação), /medical-images/annotations/delete, /medical-images/crop (Recortar), /medical-images/moderation, /medical-images/moderation/approve, /medical-images/moderation/reject.

## Perguntas frequentes
**Como abro o prontuário de um paciente?** Menu > Pacientes > Pacientes, clique em **Abrir** na linha do paciente e depois no botão **Prontuário**. Precisa de medical_records.read.

**Como crio um novo registro?** No prontuário do paciente clique em + Novo registro, ou inicie o atendimento pela Agenda para já vir preenchido. Preencha ao menos a data e hora e clique em Salvar registro.

**Posso editar um registro depois de salvo?** Sim, com a permissão medical_records.update. Cada edição gera uma versão, então o histórico fica preservado.

**Usei material mas o estoque não baixou.** Se a quantidade era maior que o saldo, a linha não é gravada. Confira o saldo em Menu > Estoque > Materiais e repita. Também há baixa automática ao concluir o atendimento pela tela Finalizar sessão.

**O microfone não funciona.** O navegador precisa permitir o microfone e suportar gravação. A mensagem "Limite de transcrição atingido" indica que o plano chegou ao limite mensal. "Falha ao transcrever" pode significar que a IA não está disponível para a clínica.

**Como envio a anamnese para o paciente preencher em casa?** No perfil do paciente clique em **Anamnese**, escolha o **Template**, clique em **Enviar ao paciente** e use o botão **Enviar via WhatsApp**, **Enviar por e-mail** ou **Disponibilizar no portal**. O link vale 7 dias.

**O paciente diz que o link da anamnese expirou.** Envie um novo link, o anterior vale por 7 dias.

**Como imprimo uma receita?** No perfil do paciente clique em **Receituário** e depois em **Imprimir** na linha da receita. Cadastre o **Nº do conselho (CRM, CRO, etc.)** do profissional para sair na receita (Configurações > Usuários & Acesso > Usuários > Editar, ou /professionals/edit).

**Como coleto assinatura do termo?** No perfil do paciente clique em **Termos**: a tela lista só os aceites já registrados, e o botão **+ Novo aceite** não deixa escolher o termo e volta para a lista de pacientes. Para colher a assinatura use **Enviar p/ Assinatura** no perfil do paciente (veja 04-pacientes) ou abra o termo desejado pelo endereço /consent/accept?patient_id=...&term_id=... e peça ao paciente para assinar no quadro e clicar em **Confirmar e salvar**.

**Não consigo ver as imagens do paciente.** Faltam as permissões medical_images.read e files.read. Fale com o dono da clínica.

**Como faço um antes e depois?** No perfil do paciente clique em **Imagens** e em **+ Antes/Depois**, envie as duas fotos e abra o comparador na seção Comparações.

**Posso desenhar na foto?** Sim, na galeria de Imagens clique em **Marcar** na foto e use as ferramentas (seta, círculo, texto, medida etc.).

**Como corto uma foto?** Em Marcar, clique em **✂ Recortar**, arraste a área e escolha **✂ Recortar esta imagem** ou **📋 Salvar como cópia**.
