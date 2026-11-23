---
title: Pacientes, ficha, documentos, portal e relacionamento
routes:
  - /patients
  - /doc/sign
  - /reports/follow-up
---

# Pacientes

O nome "Paciente" pode estar diferente na sua clinica (por exemplo Cliente). Em Configuracoes > Geral > Terminologia a clinica escolhe o rotulo e o menu acompanha. Neste texto usamos Paciente.

## Lista de pacientes
<!-- perm: patients.read -->
**Onde fica:** Menu > Pacientes > Pacientes (/patients).

- **Busca:** campo "Buscar por nome, email ou telefone" e filtro **Todas origens** (origens cadastradas em Configuracoes > Operacional). Clique em **Buscar**. A busca rapida do topo do sistema abre esta mesma lista ja filtrada (aparece o aviso "Busca rapida: procurando ...").
- **Tabela:** ID, Nome (com foto ou iniciais), E-mail, Telefone, Origem, Status (Ativo, Desativado, Inativo) e **Acoes**: **Abrir** (perfil) e **Editar**.
- **Paginacao:** botoes Anterior e Proxima (25 por pagina; aceita ate 100).
- **Exportar:** botao **Exportar** baixa um CSV (/patients/export.csv) com ID, Nome, E-mail, Telefone, CPF, Data de nascimento, Sexo, Endereco, Origem, Status e Cadastrado em, respeitando o filtro de busca e de origem. O arquivo abre direto no Excel.
- **Novo:** botao **Novo** abre o cadastro (precisa de patients.create).
- Depois de excluir um paciente a lista mostra "Paciente excluido. O historico e mantido de forma restrita conforme os prazos legais de guarda."

## Cadastrar paciente
<!-- perm: patients.create -->
**Onde fica:** Pacientes > Novo (/patients/create).

| Campo | Obrigatorio | Observacao |
|---|---|---|
| Nome | Sim | Unico campo obrigatorio |
| E-mail | Nao | Usado para o login do Portal e para envios |
| Telefone | Nao | Usado nos lembretes de WhatsApp |
| Acesso ao Portal do Paciente (senha) | Nao | Se preencher a senha e o e-mail, o acesso ao portal e criado agora. Botao **Gerar senha** sugere uma |
| WhatsApp: Receber lembretes por WhatsApp | Nao | Marque so se o paciente autorizou |
| Data de nascimento | Nao | Mostra a idade ao lado |
| Sexo | Nao | Feminino, Masculino ou Outro |
| CPF | Nao | |
| Rua, Numero, Complemento, Bairro, Cidade, UF, CEP | Nao | O sistema junta tudo em um unico endereco |
| Profissional de referencia | Nao | Lista de profissionais ativos |
| Origem do paciente | Nao | Gerenciar origens leva a Configuracoes > Operacional |
| Observacoes | Nao | Texto livre |

Clique em **Criar**. O sistema abre o perfil do paciente. Se o acesso ao portal falhar, o cadastro continua salvo (crie o acesso depois, veja Acesso ao Portal).
Erro: "Nome e obrigatorio."

## Perfil do paciente
<!-- perm: patients.read -->
**Onde fica:** Pacientes > Abrir (/patients/view?id=...).
No topo ficam os atalhos para as demais areas do paciente. Cada atalho so aparece com a permissao indicada:
| Botao | Leva para | Permissao |
|---|---|---|
| Anamnese | /anamnesis | anamnesis.fill |
| Alertas Clinicos | /patients/clinical-sheet | patients.read |
| Orcamentos | /finance/sales do paciente | finance.sales.read |
| Prontuario | /medical-records | medical_records.read |
| Imagens | /medical-images | medical_images.read |
| Linha do tempo | /patients/timeline | patients.read |
| Receituario | /patients/prescriptions | medical_records.read |
| Documentos | /patients/documents | patients.read |
| Enviar p/ Assinatura | /patients/document-sign | patients.read |
| Termos | /consent | consent_terms.accept |

No cartao do paciente:
- **Foto**: botao **Foto** envia imagem JPG, PNG ou WEBP de ate 5 MB. Arquivos fora desses limites sao ignorados sem mensagem.
- **Editar dados** (patients.update): abre o formulario no proprio perfil com Nome, E-mail, Telefone, Data de nascimento, Sexo, CPF, Origem, Endereco e Observacoes; **Salvar** ou **Cancelar**.
- **Excluir paciente** (patients.delete): ver a secao abaixo.
- Dados exibidos: E-mail, Telefone, Data de nascimento, Sexo, CPF, Origem, Status, Endereco e Observacoes.
- Cartao **LGPD / Termos do Portal**: lista cada documento legal com Obrigatorio, Status (Aceito ou Nao aceito) e Aceito em. O botao **Configurar textos** (clinics.read) leva a /clinic/legal-documents. Se o paciente ainda nao tem portal aparece "Este paciente ainda nao tem acesso ao portal."

## Editar paciente
<!-- perm: patients.update -->
**Onde fica:** Pacientes > Editar (/patients/edit?id=...) ou Editar dados no perfil. A tela completa tem os mesmos campos do cadastro mais **Status** (Ativo ou Desativado) e o checkbox de WhatsApp. Clique em **Salvar**. Erro: "Preencha os campos obrigatorios." (nome vazio) ou mensagem do sistema.

## Excluir paciente
<!-- perm: patients.delete -->
**Onde fica:** perfil do paciente > **Excluir paciente**. A permissao patients.delete e separada das demais e, por padrao, so Owner e Admin tem.
O que acontece ao confirmar:
- O paciente sai de listas, buscas e da agenda (exclusao logica, nada e apagado de verdade).
- O acesso dele ao portal e removido e os tokens de API do paciente sao revogados.
- O historico clinico e financeiro e mantido de forma restrita pelo prazo legal de guarda (LGPD e conselhos).
- A exclusao fica registrada na auditoria.
Bloqueio: se o paciente tem agendamentos futuros ativos o sistema nao exclui e mostra "Este paciente possui agendamentos futuros. Cancele ou remova os agendamentos antes de excluir." Cancele os agendamentos na Agenda e tente de novo.
Para apenas impedir novos usos sem excluir, edite o paciente e troque o **Status** para Desativado.

## Consultas do paciente (historico de agendamentos)
<!-- perm: patients.read -->
**Onde fica:** /patients/appointments?patient_id=... (nao ha botao no perfil, abra pelo endereco). Filtros: **Status** (Todos, Agendado, Confirmado, Em atendimento, Concluido, No-show, Cancelado), **De** e **Ate**. A tabela mostra Inicio, Fim, Servico, Profissional, Plano (pacote), Orcamento (link) e Status, com os botoes **Agenda**, **Reagendar**, **Logs** e **Executar**. **Executar** abre a tela de execucao da consulta (ver abaixo).

## Execucao da consulta (anotar e anexar)
<!-- perm: patients.update (ver e salvar); files.read para abrir anexos -->
**Onde fica:** /patients/consultation?appointment_id=... (botao Executar acima).
- Mostra os **Alertas clinicos ativos** do paciente e, quando o servico tem procedimento vinculado, o cartao **Protocolo** com Contraindicacoes, Orientacoes pre e pos e a lista de etapas com tempo.
- **Dados do agendamento:** Inicio, Fim, Status, Origem.
- **Registrar execucao:** campos **Executada em** (data e hora, obrigatorio), **Profissional** (obrigatorio) e **Observacoes**; botao **Salvar execucao** ("Execucao registrada.").
- **Anexos:** envie um **Arquivo** com **Nota** opcional e clique em **Anexar** ("Arquivo anexado."). A lista mostra ID, Arquivo, Nota, Tipo, Tamanho e Criado em, com **Abrir**.
Erros: "Profissional e obrigatorio.", "Data/hora e obrigatoria.", "Data/hora invalida.", "Arquivo invalido."

## Alertas clinicos (ficha clinica)
<!-- perm: patients.read (ver), patients.update (adicionar, resolver, remover) -->
**Onde fica:** perfil > Alertas Clinicos (/patients/clinical-sheet?patient_id=...). Tres cartoes lado a lado:
- **Alertas:** botao **+ Adicionar** com **Titulo** (obrigatorio), **Detalhes** e **Gravidade** (Atencao, Critico, Info). Cada alerta tem o botao x para remover. Alertas ativos aparecem em destaque no prontuario e na execucao da consulta.
- **Alergias:** **Tipo** (Alergia ou Contraindicacao), **Agente** (obrigatorio, ex.: Dipirona), **Reacao** e **Severidade**.
- **Condicoes:** **Condicao** (obrigatorio, ex.: Hipertensao), **Status** (Ativa, Inativa, Resolvida), **Data de inicio** e **Notas**.
Remover pede confirmacao. Mensagens de retorno: "Salvo." e "Removido."

## Linha do tempo
<!-- perm: patients.read (ver), patients.export (CSV) -->
**Onde fica:** perfil > Linha do tempo (/patients/timeline?patient_id=...). Reune em ordem cronologica agendamentos, prontuarios, consultas, imagens, alertas clinicos, alergias, condicoes, aceites de termo e assinaturas.
- **Mostrar:** marque os tipos que quer ver.
- **De** e **Ate** limitam as datas; **Filtrar** aplica e **Limpar** zera.
- Cada evento tem o botao **Abrir** para ir ao registro original. Vazio: "Nenhum evento encontrado para os filtros selecionados."
- Exportar CSV: /patients/timeline/export.csv exige patients.export (por padrao Owner e Admin).

## Documentos do paciente (arquivos)
<!-- perm: patients.read (ver, abrir), patients.update (enviar, excluir) -->
**Onde fica:** perfil > Documentos (/patients/documents?patient_id=...).
1. Clique em **+ Enviar documento**.
2. Escolha o **Arquivo** e, se quiser, o **Titulo** (ex.: Exame de sangue, RG, Laudo).
3. Clique em **Enviar** ("Documento enviado.").
A tabela mostra Data, Titulo, Arquivo, Tamanho, **Abrir** e o x de excluir ("Documento excluido."). Erros: "Selecione um arquivo.", "Arquivo invalido.", "Limite de armazenamento do plano atingido." (libere espaco ou faca upgrade).

## Enviar documento para assinatura
<!-- perm: patients.read (ver), patients.update (enviar) -->
**Onde fica:** perfil > Enviar p/ Assinatura (/patients/document-sign?patient_id=...).
1. Em **Titulo do documento** informe o nome (obrigatorio).
2. Envie um **Arquivo** (PDF, JPG, PNG, DOC ou DOCX) e/ou cole o texto em **Conteudo / Texto do documento**.
3. Escolha como enviar: **Enviar via WhatsApp** (precisa de telefone), **Enviar via E-mail** (precisa de e-mail) ou **Disponibilizar no Portal**. Se o contato escolhido nao existir, o documento fica no Portal.
4. O paciente recebe um link (/doc/sign), le o documento, desenha a assinatura, marca o aceite e clica em **Assinar documento**. Depois ele pode abrir ou baixar o arquivo.
A lista **Documentos enviados** mostra Titulo, Enviado via, Status (Aguardando, Assinado, Expirado, Cancelado), Data envio, Assinado em e **Ver arquivo** ou **Baixar**.
Erros: "Titulo e obrigatorio.", "Formato de arquivo nao permitido. Envie PDF, JPG, PNG, DOC ou DOCX."

## Acesso ao Portal do Paciente
<!-- perm: patients.update -->
**Onde fica:** /patients/portal-access?patient_id=... (abra pelo endereco; no cadastro tambem e possivel criar a senha).
- A tela mostra Status do acesso e o E-mail.
- Informe o **E-mail para login no Portal** e clique em **Criar/Atualizar acesso e gerar redefinicao**.
- Aparece o **Link de redefinicao de senha** com **Copiar link** e **Abrir link**. Envie ao paciente por WhatsApp. O link expira em 1 hora.
- Mensagem de sucesso: "Acesso criado/atualizado. Envie o link de redefinicao ao paciente." Erros: "E-mail do paciente e obrigatorio para criar acesso.", "Paciente nao encontrado."
O paciente entra em /portal com e-mail e senha.

## Solicitacoes de alteracao de perfil
<!-- perm: patients.update -->
**Onde fica:** Menu > Pacientes > Solicitacoes de perfil (/patients/profile-requests). Quando o paciente pede pelo portal para alterar seus dados, o pedido chega aqui.
- Filtros por status: Pendente, Aprovada, Rejeitada.
- Cada cartao mostra o paciente (link **Ver paciente**) e os campos pedidos (Nome, E-mail, Telefone, Nascimento, Sexo, CPF, Endereco, Rua, Numero, Complemento, Bairro, Cidade, UF, CEP).
- **Aprovar** aplica as mudancas no cadastro. **Rejeitar** descarta (pede confirmacao).

## Conteudos para o portal
<!-- perm: patients.update -->
**Onde fica:** /patients/content (abra pelo endereco). Cadastro de materiais de leitura para pacientes.
- **Cadastrar conteudo:** **Tipo** (Link, PDF, Video), **Titulo** (obrigatorio), **Descricao**, **URL**, **Procedimento** e **Publico-alvo**, depois **Salvar**.
- **Conteudos ativos:** em cada linha, busque o paciente em **Conceder ao paciente** e clique em **Conceder** para liberar o conteudo no portal dele.

## Aniversariantes e follow-up
<!-- perm: patients.read -->
**Onde fica:** Menu > Pacientes > Aniversariantes / Follow-up (/patients/birthdays). Duas abas:
- **Aniversariantes:** filtro **Mes**. Lista os aniversariantes com idade, botao **WhatsApp** individual e **Ver**. Em **Envio em lote** escolha o template e clique em **Enviar para todos com WhatsApp**.
- **Follow-up:** filtro **Sem consulta ha mais de** (90, 120, 180, 270 ou 365 dias; padrao 180). Lista os pacientes que nao voltam, com a data da ultima consulta, botao **WhatsApp**, **Ver** e envio em lote.
Regras do envio por WhatsApp: o paciente precisa ter telefone e ter marcado o recebimento por WhatsApp; o template precisa existir e estar ativo (Configuracoes > Geral > Templates WhatsApp). Mensagens de erro: "Paciente sem telefone cadastrado.", "Paciente sem opt-in para WhatsApp.", "Template ... nao encontrado.", "Template desativado."

**Relatorio de follow-up** (/patients/follow-up e /reports/follow-up, menu Relatorios > Follow-up): mesma ideia em formato de relatorio, com cartoes Total de pacientes, Nunca vieram, Com WhatsApp e Com telefone, filtro **Sem consulta ha mais de**, tabela com envio por WhatsApp e **Exportar PDF**. Em /patients/follow-up o envio em lote exige escolher um Template.

## LGPD do paciente
- O aceite dos documentos legais do portal aparece no cartao **LGPD / Termos do Portal** do perfil.
- Termos de consentimento assinados ficam em perfil > Termos (ver 05-prontuarios-e-atendimento).
- O paciente tem uma area de LGPD no proprio portal para fazer pedidos sobre os dados dele.
- A exclusao de paciente nao apaga o historico clinico e financeiro, que fica restrito pelo prazo legal.

## Pacote de sessoes
Pacotes do paciente nascem de vendas de pacote no Financeiro. Ao agendar, se o paciente tem pacote ativo, ele pode ser vinculado ao agendamento e uma sessao e consumida quando o atendimento e concluido. Sem sessoes restantes aparece "Pacote sem sessoes disponiveis."

## Mapa de rotas e acoes de pacientes
- Telas: /patients, /patients/create, /patients/view, /patients/edit, /patients/appointments, /patients/clinical-sheet, /patients/timeline, /patients/documents, /patients/document-sign, /patients/portal-access, /patients/profile-requests, /patients/content, /patients/birthdays, /patients/follow-up, /reports/follow-up, /patients/prescriptions, /patients/consultation.
- Acoes: /patients/create (Criar), /patients/edit (Salvar), /patients/delete (Excluir paciente), /patients/photo/upload (Foto) e /patients/photo (mostra a foto), /patients/export.csv (Exportar).
- Apoio das telas: /patients/search-json (busca do campo Paciente na agenda) e /patients/packages-json (pacotes do paciente ao agendar).
- Ficha clinica: /patients/clinical-sheet/alerts/create, /patients/clinical-sheet/alerts/delete, /patients/clinical-sheet/alerts/resolve (existe, mas a tela atual nao tem botao), /patients/clinical-sheet/allergies/create, /patients/clinical-sheet/allergies/delete, /patients/clinical-sheet/conditions/create, /patients/clinical-sheet/conditions/delete.
- Documentos: /patients/documents/upload, /patients/documents/file (Abrir), /patients/documents/delete. Assinatura: /patients/document-sign/create, /patients/document-sign/file (Ver arquivo, Baixar) e as paginas publicas do paciente /doc/sign, /doc/sign/submit (Assinar documento) e /doc/sign/file.
- Portal: /patients/portal-access/ensure (Criar/Atualizar acesso), /patients/profile-requests/approve e /patients/profile-requests/reject, /patients/content/create e /patients/content/grant.
- Consulta: /patients/consultation/save (Salvar execucao), /patients/consultation/attachments/upload (Anexar) e /patients/consultation/attachments/file (Abrir).
- Receitas: /patients/prescriptions/create, /patients/prescriptions/update, /patients/prescriptions/delete, /patients/prescription/edit (tela) e /patients/prescription/print (impressao).
- Relacionamento: /patients/whatsapp/send-json (botoes WhatsApp), /patients/follow-up/export.pdf e /reports/follow-up/export.pdf (Exportar PDF), /patients/timeline/export.csv.

## Perguntas frequentes
**Como cadastro um paciente?** Menu Pacientes > Novo, preencha ao menos o Nome e clique em Criar. Para o paciente receber lembretes, preencha telefone e marque Receber lembretes por WhatsApp.

**Nao encontro o paciente na busca.** A busca procura por nome, e-mail e telefone (nao por CPF). Tente outra grafia ou parte do telefone. Se o paciente foi excluido, ele nao aparece mais. Confira tambem o filtro de origem.

**Como excluo um paciente?** Perfil do paciente > Excluir paciente. Exige a permissao patients.delete. Se houver agendamentos futuros, cancele-os antes.

**Aparece "Este paciente possui agendamentos futuros".** Cancele os agendamentos futuros dele na Agenda e exclua novamente.

**Nao vejo o botao Excluir paciente.** Falta a permissao patients.delete. Peca ao dono em Papeis e Permissoes.

**Como exporto minha lista de pacientes?** Em Pacientes clique em Exportar. O CSV respeita a busca atual.

**Como mando o link de senha do portal?** Abra /patients/portal-access?patient_id=ID, informe o e-mail, clique em Criar/Atualizar acesso e use Copiar link para enviar por WhatsApp. O link vale 1 hora.

**Como anexo um exame ao paciente?** Perfil > Documentos > + Enviar documento. Para imagens clinicas (fotos antes e depois) use Imagens.

**Como registro uma alergia?** Perfil > Alertas Clinicos > cartao Alergias > + Adicionar. Ela aparece como alerta no prontuario.

**O paciente fez pedido de alteracao de dados.** Menu Pacientes > Solicitacoes de perfil, confira os campos e clique em Aprovar ou Rejeitar.

**Como envio uma mensagem de aniversario?** Pacientes > Aniversariantes / Follow-up > aba Aniversariantes. Escolha o template e envie individualmente ou em lote. O paciente precisa ter telefone e permissao de WhatsApp.

**Como recupero pacientes sem retorno?** Aba Follow-up (90 a 365 dias sem consulta) ou Relatorios > Follow-up, com envio por WhatsApp e exportacao em PDF.

**Posso mudar o nome Paciente para Cliente?** Sim, em Configuracoes > Geral > Terminologia.
