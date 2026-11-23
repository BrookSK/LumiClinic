---
title: Prontuario, atendimento, receitas, anamnese, termos e imagens clinicas
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

# Prontuario e atendimento

## Como o atendimento flui
1. Na Agenda, o paciente chega e a recepcao clica em **Chegou (check-in)**.
2. O profissional clica em **Iniciar atendimento** (na agenda ou na Fila de chegada). O sistema muda o status para Em atendimento e abre o formulario de **novo registro de prontuario** ja com paciente, data e hora, profissional e procedimento preenchidos.
3. O profissional registra descricao, evolucao, materiais, imagens e notas, e salva.
4. Ao terminar, clica em **Finalizar atendimento** (ou em Concluir na agenda). A tela Finalizar sessao confirma materiais e observacao e conclui o agendamento (ver 03-agenda).
Alertas clinicos do paciente (alergias, contraindicacoes, avisos) aparecem em vermelho no topo do prontuario para nao passarem despercebidos.

## Lista de registros do prontuario
<!-- perm: medical_records.read -->
**Onde fica:** perfil do paciente > **Prontuario** (/medical-records?patient_id=...).
- Cabecalho com o nome do paciente e selos dos alertas clinicos ("Sem alertas clinicos" quando nao ha).
- Botoes **Voltar ao paciente** e **+ Novo registro** (precisa de medical_records.create).
- Cada registro aparece como uma linha com data, procedimento, profissional e um resumo. Clique na linha para abrir os blocos **Descricao clinica**, **Evolucao** e **Notas**. O botao **Editar** abre a edicao.
- Sem registros aparece "Nenhum registro ainda" com o link **Criar primeiro registro**.

## Criar registro de prontuario
<!-- perm: medical_records.create -->
**Onde fica:** /medical-records/create?patient_id=... (botao + Novo registro ou Iniciar atendimento na agenda).

| Campo | Obrigatorio | Observacao |
|---|---|---|
| Data/hora | Sim | Quando o atendimento aconteceu. Vem preenchido pela agenda |
| Profissional | Nao | Vem fixo quando o registro nasce de um agendamento. Senao e uma lista (opcional) |
| Procedimento | Nao | Texto livre, ex.: Botox, Limpeza. Vem do nome do servico quando criado pela agenda |
| Descricao clinica | Nao | Texto longo com botao de microfone |
| Evolucao | Nao | Texto longo com botao de microfone |
| Produtos e materiais usados | Nao | Linhas com Material, Qtd, Lote (opcional) e Descricao (opcional). **+ Adicionar material** cria uma linha |
| Anexar imagens | Nao | Varias imagens ou PDF de uma vez, ligadas a este prontuario |
| Notas | Nao | Texto longo com botao de microfone |

- **Salvar registro** grava e abre a tela de edicao do registro. **Cancelar** descarta.
- Botao **Finalizar atendimento** no cabecalho (aparece quando veio de um agendamento) abre a tela Finalizar sessao.
- Abaixo do formulario fica o **Historico** dos ultimos registros do paciente, com **Mostrar mais** e **Ver tudo**.
- **Materiais descontam do estoque** na hora de salvar. Se a quantidade pedida e maior que o saldo, aquela linha nao e gravada (sem aviso na tela), entao confira o estoque antes. Cada baixa cria uma movimentacao de estoque de saida ligada ao prontuario.
- Imagens que nao puderem ser enviadas (formato invalido ou limite do plano) ficam de fora do registro, mas o texto e salvo.
Erro: "Data/hora do atendimento e obrigatoria."

### Ditado por voz (microfone)
O botao do microfone ao lado de Descricao clinica, Evolucao e Notas grava sua voz e transcreve para o texto.
1. Clique no microfone (o navegador pede permissao de uso do microfone).
2. Fale. A tela mostra "Gravando..." com o tempo.
3. Clique de novo para parar. Aparece "Transcrevendo..." e o texto entra no campo.
Mensagens: "Sem suporte a microfone.", "Nao suportado neste navegador.", "Restam N min de transcricao este mes.", "Limite de transcricao atingido. Faca upgrade do plano.", "Transcricao vazia.", "Falha ao transcrever." (a IA precisa estar disponivel para a clinica; o limite depende do plano). Exige medical_records.create.

## Editar registro de prontuario
<!-- perm: medical_records.update -->
**Onde fica:** /medical-records/edit?patient_id=...&id=... (botao Editar no registro).
- Campos editaveis: Data/hora, Procedimento, Profissional, Descricao clinica, Evolucao e Notas. Clique em **Salvar**.
- Cada edicao guarda uma **versao** do registro, entao o historico do que foi alterado nao se perde.
- A tela mostra tambem **Materiais usados neste prontuario** (Material, Quantidade, Lote, Descricao) e **Imagens vinculadas a este prontuario** (clique para marcar a imagem).
- Atalhos no cabecalho: **Ficha clinica**, **Imagens** e **Voltar**.
Erros: "Preencha os campos obrigatorios.", "Data/hora do atendimento e obrigatoria."

## Modelos de prontuario
<!-- perm: medical_record_templates.manage -->
**Onde fica:** Menu > Configuracoes > Modelos de prontuario (/medical-record-templates).
- A lista mostra ID, Nome, Status e **Editar**. **Novo template** abre o cadastro.
- No cadastro informe o **Nome** (obrigatorio) e monte os **Campos**: clique em **Adicionar campo** e defina Ordem, Rotulo, Tipo (Texto, Texto longo, Checkbox, Select, Numero, Data), Obrigatorio (Sim ou Nao) e, para Select, as Opcoes. Clique em **Criar**.
- Na edicao o modelo pode ser ativado ou desativado.
- Observacao: o formulario atual de Novo registro usa os campos fixos Descricao clinica, Evolucao e Notas. Os modelos ficam cadastrados para padronizar a clinica.
Erros: "Nome e obrigatorio.", "Preencha os campos obrigatorios."

## Receituario (prescricoes)
<!-- perm: medical_records.read (ver, imprimir), medical_records.create (criar, editar, excluir) -->
**Onde fica:** perfil do paciente > **Receituario** (/patients/prescriptions?patient_id=...).
1. No cartao **Nova receita** confira os dados do **Paciente** (aviso amarelo se CPF ou Endereco nao estiverem cadastrados, com link para completar) e do **Prescritor**.
2. Escolha o **Profissional que prescreve**. Se o profissional nao tem **Numero do conselho** (CRM, CRO etc.), aparece um aviso com link para cadastrar.
3. Preencha **Titulo** (padrao Receita), **Data de emissao** e **Conteudo da receita** (obrigatorio).
4. Clique em **Salvar receita** ("Receita criada.").
Na lista **Receitas emitidas** ha **Editar** (/patients/prescription/edit, campos Titulo, Data de emissao e Conteudo), **Imprimir** (/patients/prescription/print, abre a pagina de impressao com cabecalho da clinica, dados do paciente, texto e linha de assinatura com nome e conselho do profissional; use o botao Imprimir) e **Excluir** ("Receita excluida.").
Erros: "Preencha os campos obrigatorios.", "Dados invalidos."

## Anamnese: modelos
<!-- perm: anamnesis.manage -->
**Onde fica:** Menu > Configuracoes > Anamnese (/anamnesis/templates).
- Lista de cartoes com nome, status e quantidade de campos, com **Editar**. **+ Novo template** cria um.
- No cadastro informe o **Nome do template** (obrigatorio) e clique em **+ Adicionar campo** para cada pergunta. Tipos de campo: **Texto curto**, **Texto longo**, **Sim/Nao**, **Multipla escolha** (uma opcao por linha), **Numero** e **Data**. Clique em **Criar template**.
- Na edicao e possivel mudar o **Status** (Ativo ou inativo) e os campos.
Erros: "Nome e obrigatorio.", "Preencha os campos obrigatorios."

## Anamnese: preencher ou enviar ao paciente
<!-- perm: anamnesis.fill -->
**Onde fica:** perfil do paciente > **Anamnese** (/anamnesis?patient_id=...).
1. Em **Nova anamnese** escolha o **Template**.
2. **Preencher aqui** abre o formulario na clinica (/anamnesis/fill): responda os campos, escolha o **Profissional** (opcional), peca ao paciente para assinar no quadro (dedo ou mouse; **Limpar** refaz) e clique em **Salvar anamnese**.
3. **Enviar ao paciente** abre o painel de envio. O paciente recebe um link para preencher e assinar. Marque **Nao exigir login para preenchimento** para ele abrir direto pelo link. Escolha o canal:
   - **WhatsApp**: precisa de telefone e de permissao de WhatsApp do paciente. Mensagens: "Paciente sem telefone cadastrado." ou "Paciente sem opt-in de WhatsApp."
   - **E-mail**: precisa de e-mail ("Paciente sem e-mail cadastrado.").
   - **Portal**: disponibiliza no portal do paciente.
   O link vale 7 dias.
4. O paciente abre o link (/a/anamnese), preenche, assina e clica em **Enviar anamnese**. Aparece "Anamnese enviada!". Link vencido mostra "Link invalido ou expirado."
5. Em **Respostas** ficam todas as anamneses do paciente com Data, Template e Assinatura (Assinado ou Sem assinatura), **Ver** (/anamnesis/response) e **PDF** (/anamnesis/export-pdf).
Quando uma consulta e confirmada na agenda, o modelo definido em Configuracoes > Geral > Anamnese padrao e enviado automaticamente.

## Termos de consentimento
<!-- perm: consent_terms.manage (cadastrar termos), consent_terms.accept (coletar aceite e assinatura) -->
**Cadastrar termos:** abra /consent-terms (nao ha item no menu; a tela e identificada como Consentimento (Legado)). A lista mostra ID, Procedimento, Titulo, Status e **Editar**. **Novo termo**: escolha o **Procedimento** (ou **Outro (digitar)**), informe **Titulo** e **Corpo do termo** e clique em **Criar**. Erro: "Preencha todos os campos."

**Coletar aceite do paciente:** perfil do paciente > **Termos** (/consent?patient_id=...). A tela lista os **Aceites registrados** (Data, Termo, Procedimento, Assinatura) com **Ver** (/consent/export, mostra o texto aceito e a assinatura) e **Assinatura** (imagem). Para um novo aceite a tela de assinatura (/consent/accept?patient_id=...&term_id=...) mostra o texto do termo, o quadro de assinatura (**Limpar**) e **Confirmar e salvar**. Se o botao **+ Novo aceite** da lista voltar para a lista de pacientes, use o envio de documento para assinatura (Pacientes > perfil > Enviar p/ Assinatura) ou abra o termo desejado pelo endereco com o term_id.
Abrir a imagem da assinatura exige files.read.

## Imagens clinicas
<!-- perm: medical_images.read (ver), medical_images.upload (enviar, marcar, recortar), files.read (abrir arquivos) -->
**Onde fica:** perfil do paciente > **Imagens** (/medical-images?patient_id=...).
- **+ Imagem**: campo **Arquivo** (JPG, PNG ou WebP, obrigatorio), **Tipo** (Foto, Acompanhamento, Exame, Outro), **Procedimento** (opcional), **Data** (opcional) e **Descricao** (opcional, com microfone para ditar). Clique em **Enviar**. As imagens sao comprimidas e convertidas para WebP, e reduzidas quando passam de 2048 pixels, para economizar espaco.
- **+ Antes/Depois**: envie **Antes** e **Depois** (obrigatorios), **Procedimento** e **Data** e clique em **Criar comparacao**.
- A secao **Comparacoes** mostra cartoes que abrem o comparador. A **galeria** mostra miniaturas com **Ver**, **Marcar** e o link para o prontuario de origem.
- **Linha do tempo das imagens** (/medical-images/timeline): imagens agrupadas por procedimento e sessao, com Tipo, Data, Angulo/Posicao, **Abrir** e **Comparar**.
- **Comparador** (/medical-images/compare): arraste o controle central para ver Antes e Depois lado a lado; atalhos **Marcar Antes** e **Marcar Depois**.
- **Marcacoes** (/medical-images/annotate): ferramentas Retangulo, Circulo, Seta, Linha, Traco livre, Ponto, Texto e Medida; campo **Texto** (com microfone), **Cor** e **Salvar marcacao**. A lista **Marcacoes** permite remover. **Recortar** ativa o modo de recorte: arraste sobre a imagem e escolha **Recortar esta imagem** ou **Salvar como copia**. **Ver original** abre o arquivo.
- **Moderacao de uploads do portal** (/medical-images/moderation): imagens enviadas pelo paciente ficam pendentes com **Aprovar** (Obs opcional) e **Rejeitar** (Motivo opcional). Abra pelo endereco.
Erros: "Formato nao suportado. Use JPG/PNG/WEBP.", "Limite de armazenamento do plano atingido.", "Arquivo invalido.", "Falha no upload."

## Mapa de rotas e acoes de prontuario, anamnese, termos e imagens
- Prontuario: /medical-records, /medical-records/create (Salvar registro), /medical-records/edit (Salvar), /medical-records/audio/transcribe-json (ditado por voz), /medical-records/audio/transcribe (versao sem tela), /medical-records/audio/transcription-status (confere o limite mensal).
- Modelos de prontuario: /medical-record-templates, /medical-record-templates/create, /medical-record-templates/edit.
- Anamnese: /anamnesis (lista), /anamnesis/fill (formulario e Salvar anamnese), /anamnesis/response (Ver), /anamnesis/export (versao de impressao), /anamnesis/export-pdf (PDF), /anamnesis/send-link (Enviar ao paciente), /anamnesis/templates, /anamnesis/templates/create, /anamnesis/templates/edit, e a pagina publica /a/anamnese.
- Termos: /consent-terms, /consent-terms/create, /consent-terms/edit, /consent, /consent/accept, /consent/export, /signatures/file (imagem da assinatura).
- Imagens: /medical-images, /medical-images/upload (Enviar), /medical-images/upload-pair (Criar comparacao), /medical-images/timeline, /medical-images/compare, /medical-images/file (mostra a imagem), /medical-images/annotate, /medical-images/annotations.json (carrega marcacoes), /medical-images/annotations/create (Salvar marcacao), /medical-images/annotations/delete, /medical-images/crop (Recortar), /medical-images/moderation, /medical-images/moderation/approve, /medical-images/moderation/reject.

## Perguntas frequentes
**Como abro o prontuario de um paciente?** Pacientes > Abrir > Prontuario. Precisa de medical_records.read.

**Como crio um novo registro?** No prontuario do paciente clique em + Novo registro, ou inicie o atendimento pela Agenda para ja vir preenchido. Preencha ao menos a data e hora e clique em Salvar registro.

**Posso editar um registro depois de salvo?** Sim, com a permissao medical_records.update. Cada edicao gera uma versao, entao o historico fica preservado.

**Usei material mas o estoque nao baixou.** Se a quantidade era maior que o saldo, a linha nao e gravada. Confira o saldo em Estoque > Materiais e repita. Tambem ha baixa automatica ao concluir o atendimento pela tela Finalizar sessao.

**O microfone nao funciona.** O navegador precisa permitir o microfone e suportar gravacao. A mensagem "Limite de transcricao atingido" indica que o plano chegou ao limite mensal. "Falha ao transcrever" pode significar que a IA nao esta disponivel para a clinica.

**Como envio a anamnese para o paciente preencher em casa?** Pacientes > Anamnese > escolha o template > Enviar ao paciente > WhatsApp, E-mail ou Portal. O link vale 7 dias.

**O paciente diz que o link da anamnese expirou.** Envie um novo link, o anterior vale por 7 dias.

**Como imprimo uma receita?** Pacientes > Receituario > Imprimir na linha da receita. Cadastre o numero do conselho do profissional para sair na receita (Configuracoes > Usuarios > Editar, ou /professionals/edit).

**Como coleto assinatura do termo?** Pacientes > Termos, escolha o termo, o paciente assina no quadro e confirme.

**Nao consigo ver as imagens do paciente.** Faltam as permissoes medical_images.read e files.read. Fale com o dono da clinica.

**Como faco um antes e depois?** Pacientes > Imagens > + Antes/Depois, envie as duas fotos e abra o comparador na secao Comparacoes.

**Posso desenhar na foto?** Sim, abra Marcar na imagem e use as ferramentas (seta, circulo, texto, medida etc.).

**Como corto uma foto?** Em Marcar, clique em Recortar, arraste a area e escolha Recortar esta imagem ou Salvar como copia.
