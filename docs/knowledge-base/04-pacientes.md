---
title: Pacientes, ficha, documentos, portal e relacionamento
routes:
  - /patients
  - /doc/sign
  - /reports/follow-up
---

# Pacientes

O nome "Paciente" pode estar diferente na sua clínica (por exemplo Cliente). Em Configurações > Geral, bloco Terminologia a clínica escolhe o rótulo e o menu acompanha. Neste texto usamos Paciente.

## Lista de pacientes
<!-- perm: patients.read -->
**Onde fica:** Menu > Pacientes > Pacientes (/patients).

- **Busca:** campo "Buscar por nome, email ou telefone" e filtro **Todas origens** (origens cadastradas em Configurações > Geral, cartão Operacional). Clique em **Buscar**. A busca rápida do topo do sistema abre esta mesma lista já filtrada (aparece o aviso "Busca rápida: procurando ...").
- **Tabela:** ID, Nome (com foto ou iniciais), E-mail, Telefone, Origem, Status (Ativo, Desativado, Inativo) e **Ações**: **Abrir** (perfil) e **Editar**.
- **Paginação:** botões Anterior e Próxima (25 por página; aceita até 100).
- **Exportar:** botão **Exportar** baixa um CSV (/patients/export.csv) com ID, Nome, E-mail, Telefone, CPF, Data de nascimento, Sexo, Endereço, Origem, Status e Cadastrado em, respeitando o filtro de busca e de origem. O arquivo abre direto no Excel.
- **Novo:** botão **Novo** abre o cadastro (precisa de patients.create).
- Depois de excluir um paciente a lista mostra "Paciente excluído. O histórico é mantido de forma restrita conforme os prazos legais de guarda."

## Cadastrar paciente
<!-- perm: patients.create -->
**Onde fica:** Pacientes > Novo (/patients/create).

| Campo | Obrigatório | Observação |
|---|---|---|
| Nome | Sim | Único campo obrigatório |
| E-mail | Não | Usado para o login do Portal e para envios |
| Telefone | Não | Usado nos lembretes de WhatsApp |
| Acesso ao Portal do Paciente (senha) | Não | Se preencher a senha e o e-mail, o acesso ao portal é criado agora. Botão **Gerar senha** sugere uma |
| WhatsApp: Receber lembretes por WhatsApp | Não | Marque só se o paciente autorizou |
| Data de nascimento | Não | Mostra a idade ao lado |
| Sexo | Não | Feminino, Masculino ou Outro |
| CPF | Não | |
| Rua, Número, Complemento, Bairro, Cidade, UF, CEP | Não | O sistema junta tudo em um único endereço |
| Profissional de referência | Não | Lista de profissionais ativos |
| Origem do paciente | Não | Gerenciar origens leva a Configurações > Geral, cartão Operacional |
| Observações | Não | Texto livre |

Clique em **Criar**. O sistema abre o perfil do paciente. Se o acesso ao portal falhar, o cadastro continua salvo (crie o acesso depois, veja Acesso ao Portal).
Erro: "Nome é obrigatório."

## Perfil do paciente
<!-- perm: patients.read -->
**Onde fica:** Pacientes > Abrir (/patients/view?id=...).
No topo ficam os atalhos para as demais áreas do paciente. Cada atalho só aparece com a permissão indicada:
| Botão | Leva para | Permissão |
|---|---|---|
| Anamnese | /anamnesis | anamnesis.fill |
| Alertas Clínicos | /patients/clinical-sheet | patients.read |
| Orçamentos | /finance/sales do paciente | finance.sales.read |
| Prontuário | /medical-records | medical_records.read |
| Imagens | /medical-images | medical_images.read |
| Linha do tempo | /patients/timeline | patients.read |
| Receituário | /patients/prescriptions | medical_records.read |
| Documentos | /patients/documents | patients.read |
| Enviar p/ Assinatura | /patients/document-sign | patients.read |
| Termos | /consent | consent_terms.accept |

No cartão do paciente:
- **Foto**: botão **Foto** envia imagem JPG, PNG ou WEBP de até 5 MB. Arquivos fora desses limites são ignorados sem mensagem.
- **Editar dados** (patients.update): abre o formulário no próprio perfil com Nome, E-mail, Telefone, Data de nascimento, Sexo, CPF, Origem, Endereço e Observações; **Salvar** ou **Cancelar**.
- **Excluir paciente** (patients.delete): ver a seção abaixo.
- Dados exibidos: E-mail, Telefone, Data de nascimento, Sexo, CPF, Origem, Status, Endereço e Observações.
- Cartão **LGPD / Termos do Portal**: lista cada documento legal com Obrigatório, Status (Aceito ou Não aceito) e Aceito em. O botão **Configurar textos** (clinics.read) leva a /clinic/legal-documents. Se o paciente ainda não tem portal aparece "Este paciente ainda não tem acesso ao portal."

## Editar paciente
<!-- perm: patients.update -->
**Onde fica:** Pacientes > Editar (/patients/edit?id=...) ou Editar dados no perfil. A tela completa tem os mesmos campos do cadastro mais **Status** (Ativo ou Desativado) e o checkbox de WhatsApp. Clique em **Salvar**. Erro: "Preencha os campos obrigatórios." (nome vazio) ou mensagem do sistema.

## Excluir paciente
<!-- perm: patients.delete -->
**Onde fica:** perfil do paciente > **Excluir paciente**. A permissão patients.delete é separada das demais e, por padrão, só Owner e Admin têm.
O que acontece ao confirmar:
- O paciente sai de listas, buscas e da agenda (exclusão lógica, nada e apagado de verdade).
- O acesso dele ao portal é removido e os tokens de API do paciente são revogados.
- O histórico clínico e financeiro é mantido de forma restrita pelo prazo legal de guarda (LGPD e conselhos).
- A exclusão fica registrada na auditoria.
Bloqueio: se o paciente tem agendamentos futuros ativos o sistema não exclui e mostra "Este paciente possui agendamentos futuros. Cancele ou remova os agendamentos antes de excluir." Cancele os agendamentos na Agenda e tente de novo.
Para apenas impedir novos usos sem excluir, edite o paciente e troque o **Status** para Desativado.

## Consultas do paciente (histórico de agendamentos)
<!-- perm: patients.read -->
**Onde fica:** /patients/appointments?patient_id=... (não há botão no perfil, abra pelo endereço). Filtros: **Status** (Todos, Agendado, Confirmado, Em atendimento, Concluído, No-show, Cancelado), **De** e **Até**. A tabela mostra Início, Fim, Serviço, Profissional, Plano (pacote), Orçamento (link) e Status, com os botões **Agenda**, **Reagendar**, **Logs** e **Executar**. **Executar** abre a tela de execução da consulta (ver abaixo).

## Execução da consulta (anotar e anexar)
<!-- perm: patients.update (ver e salvar); files.read para abrir anexos -->
**Onde fica:** /patients/consultation?appointment_id=... (botão Executar acima).
- Mostra os **Alertas clínicos ativos** do paciente e, quando o serviço tem procedimento vinculado, o cartão **Protocolo** com Contraindicações, Orientações pré e pós e a lista de etapas com tempo.
- **Dados do agendamento:** Início, Fim, Status, Origem.
- **Registrar execução:** campos **Executada em** (data e hora, obrigatório), **Profissional** (obrigatório) e **Observações**; botão **Salvar execução** ("Execução registrada.").
- **Anexos:** envie um **Arquivo** com **Nota** opcional e clique em **Anexar** ("Arquivo anexado."). A lista mostra ID, Arquivo, Nota, Tipo, Tamanho e Criado em, com **Abrir**.
Erros: "Profissional é obrigatório.", "Data/hora é obrigatória.", "Data/hora inválida.", "Arquivo inválido."

## Alertas clínicos (ficha clínica)
<!-- perm: patients.read (ver), patients.update (adicionar, resolver, remover) -->
**Onde fica:** perfil > Alertas Clínicos (/patients/clinical-sheet?patient_id=...). Três cartões lado a lado:
Os formulários abrem ao clicar em **+ Adicionar** em cada cartão e não têm rótulos acima dos campos: cada campo é identificado pelo texto cinza dentro dele (placeholder). Cada formulário tem **Salvar** e um ✕ para fechar.
- **Alertas:** campo "Título do alerta" (obrigatório), "Detalhes (opcional)" e uma lista com a gravidade (Atenção, Crítico, Info). Cada alerta salvo tem o botão ✕ para remover. Alertas ativos aparecem em destaque no prontuário e na execução da consulta.
- **Alergias:** lista com o tipo (Alergia ou Contraindicação), campo "Ex: Dipirona" (o agente, obrigatório), "Reação (opcional)" e "Severidade".
- **Condições:** campo "Ex: Hipertensão" (a condição, obrigatória), lista de status (Ativa, Inativa, Resolvida), um campo de data (início, mostrado depois como "Desde ...") e "Notas (opcional)".
Remover pede confirmação. Mensagens de retorno: "Salvo." e "Removido."

## Linha do tempo
<!-- perm: patients.read (ver), patients.export (CSV) -->
**Onde fica:** perfil > Linha do tempo (/patients/timeline?patient_id=...). Reúne em ordem cronológica agendamentos, prontuários, consultas, imagens, alertas clínicos, alergias, condições, aceites de termo e assinaturas.
- **Mostrar:** marque os tipos que quer ver.
- **De** e **Até** limitam as datas; **Filtrar** aplica e **Limpar** zera.
- Cada evento tem o botão **Abrir** para ir ao registro original. Vazio: "Nenhum evento encontrado para os filtros selecionados."
- Exportar CSV: /patients/timeline/export.csv exige patients.export (por padrão Owner e Admin).

## Documentos do paciente (arquivos)
<!-- perm: patients.read (ver, abrir), patients.update (enviar, excluir) -->
**Onde fica:** perfil > Documentos (/patients/documents?patient_id=...).
1. Clique em **+ Enviar documento**.
2. Escolha o **Arquivo** e, se quiser, o **Título** (ex.: Exame de sangue, RG, Laudo).
3. Clique em **Enviar** ("Documento enviado.").
A tabela mostra Data, Título, Arquivo, Tamanho, **Abrir** e o botão ✕ de excluir, que pede confirmação "Excluir documento?" ("Documento excluído."). Erros: "Selecione um arquivo.", "Arquivo inválido.", "Limite de armazenamento do plano atingido." (libere espaço ou faça upgrade).

## Enviar documento para assinatura
<!-- perm: patients.read (ver), patients.update (enviar) -->
**Onde fica:** perfil > Enviar p/ Assinatura (/patients/document-sign?patient_id=...).
1. Em **Título do documento *** informe o nome (obrigatório).
2. Envie um **Arquivo (PDF, imagem, opcional)** (a lista aceita PDF, JPG, PNG, DOC ou DOCX) e/ou cole o texto em **Conteúdo / Texto do documento (opcional)**.
3. Não há botão "Enviar" único: clique em um destes três botões, que já enviam: **📱 Enviar via WhatsApp** (precisa de telefone), **📧 Enviar via E-mail** (precisa de e-mail) ou **🌐 Disponibilizar no Portal**. Se o contato escolhido não existir, o documento fica no Portal.
4. O paciente recebe um link (/doc/sign), lê o documento, desenha a assinatura, marca o aceite e clica em **Assinar documento**. Depois ele pode abrir ou baixar o arquivo.
A lista **Documentos enviados** mostra Título, Enviado via, Status (Aguardando, Assinado, Expirado, Cancelado), Data envio, Assinado em e **Ver arquivo** ou **Baixar**.
Erros: "Título é obrigatório.", "Formato de arquivo não permitido. Envie PDF, JPG, PNG, DOC ou DOCX."

## Acesso ao Portal do Paciente
<!-- perm: patients.update -->
**Onde fica:** /patients/portal-access?patient_id=... (abra pelo endereço; no cadastro também é possível criar a senha).
- A tela mostra Status do acesso e o E-mail.
- Informe o **E-mail para login no Portal** e clique em **Criar/Atualizar acesso e gerar redefinição**.
- Aparece o **Link de redefinição de senha** com **Copiar link** e **Abrir link**. Envie ao paciente por WhatsApp. O link expira em 1 hora.
- Mensagem de sucesso: "Acesso criado/atualizado. Envie o link de redefinição ao paciente." Erros: "E-mail do paciente é obrigatório para criar acesso.", "Paciente não encontrado."
O paciente entra em /portal com e-mail e senha.

## Solicitações de alteração de perfil
<!-- perm: patients.update -->
**Onde fica:** Menu > Pacientes > Solicitações de perfil (/patients/profile-requests). Quando o paciente pede pelo portal para alterar seus dados, o pedido chega aqui.
- Abas de filtro por status: Pendentes, Aprovadas, Rejeitadas.
- Cada cartão mostra o paciente (link **Ver paciente**) e os campos pedidos (Nome, E-mail, Telefone, Nascimento, Sexo, CPF, Endereço, Rua, Número, Complemento, Bairro, Cidade, UF, CEP).
- **Aprovar** aplica as mudanças no cadastro. **Rejeitar** descarta (pede confirmação).

## Conteúdos para o portal
<!-- perm: patients.update -->
**Onde fica:** /patients/content (abra pelo endereço). Cadastro de materiais de leitura para pacientes.
- **Cadastrar conteúdo:** **Tipo** (Link, PDF (estrutura), Vídeo), **Título** (obrigatório), **Descrição**, **URL**, **Procedimento** e **Público-alvo (opcional)**, depois **Salvar**.
- **Conteúdos ativos:** em cada linha, busque o paciente em **Conceder ao paciente** e clique em **Conceder** para liberar o conteúdo no portal dele.

## Aniversariantes e follow-up
<!-- perm: patients.read -->
**Onde fica:** Menu > Pacientes > Aniversariantes / Follow-up (/patients/birthdays). Duas abas:
- **Aniversariantes:** filtro **Mês**. Lista os aniversariantes com idade, botão **WhatsApp** individual e **Ver**. Em **Envio em lote** escolha o template e clique em **Enviar para todos com WhatsApp**.
- **Follow-up:** filtro **Sem consulta há mais de** (90, 120, 180, 270 ou 365 dias; padrão 180). Lista os pacientes que não voltam, com a data da última consulta, botão **WhatsApp**, **Ver** e envio em lote.
Regras do envio por WhatsApp: o paciente precisa ter telefone e ter marcado o recebimento por WhatsApp; o template precisa existir e estar ativo (Configurações > Geral, cartão Templates WhatsApp). Mensagens de erro: "Paciente sem telefone cadastrado.", "Paciente sem opt-in para WhatsApp.", "Template ... não encontrado.", "Template desativado."

**Relatório de follow-up** (/patients/follow-up e /reports/follow-up, menu Relatórios > Follow-up): mesma ideia em formato de relatório, com cartões Total de pacientes, Nunca vieram, Com WhatsApp e Com telefone, filtro **Sem consulta há mais de**, tabela com envio por WhatsApp e **Exportar PDF**. Em /patients/follow-up o envio em lote exige escolher um Template.

## LGPD do paciente
- O aceite dos documentos legais do portal aparece no cartão **LGPD / Termos do Portal** do perfil.
- Termos de consentimento assinados ficam em perfil > Termos (ver 05-prontuários-e-atendimento).
- O paciente tem uma área de LGPD no próprio portal para fazer pedidos sobre os dados dele.
- A exclusão de paciente não apaga o histórico clínico e financeiro, que fica restrito pelo prazo legal.

## Pacote de sessões
Pacotes do paciente nascem de vendas de pacote no Financeiro. Ao agendar, se o paciente tem pacote ativo, ele pode ser vinculado ao agendamento e uma sessão é consumida quando o atendimento e concluído. Sem sessões restantes aparece "Pacote sem sessões disponíveis."

## Mapa de rotas e ações de pacientes
- Telas: /patients, /patients/create, /patients/view, /patients/edit, /patients/appointments, /patients/clinical-sheet, /patients/timeline, /patients/documents, /patients/document-sign, /patients/portal-access, /patients/profile-requests, /patients/content, /patients/birthdays, /patients/follow-up, /reports/follow-up, /patients/prescriptions, /patients/consultation.
- Ações: /patients/create (Criar), /patients/edit (Salvar), /patients/delete (Excluir paciente), /patients/photo/upload (Foto) e /patients/photo (mostra a foto), /patients/export.csv (Exportar).
- Apoio das telas: /patients/search-json (busca do campo Paciente na agenda) e /patients/packages-json (pacotes do paciente ao agendar).
- Ficha clínica: /patients/clinical-sheet/alerts/create, /patients/clinical-sheet/alerts/delete, /patients/clinical-sheet/alerts/resolve (existe, mas a tela atual não tem botão), /patients/clinical-sheet/allergies/create, /patients/clinical-sheet/allergies/delete, /patients/clinical-sheet/conditions/create, /patients/clinical-sheet/conditions/delete.
- Documentos: /patients/documents/upload, /patients/documents/file (Abrir), /patients/documents/delete. Assinatura: /patients/document-sign/create, /patients/document-sign/file (Ver arquivo, Baixar) e as páginas públicas do paciente /doc/sign, /doc/sign/submit (Assinar documento) e /doc/sign/file.
- Portal: /patients/portal-access/ensure (Criar/Atualizar acesso), /patients/profile-requests/approve e /patients/profile-requests/reject, /patients/content/create e /patients/content/grant.
- Consulta: /patients/consultation/save (Salvar execução), /patients/consultation/attachments/upload (Anexar) e /patients/consultation/attachments/file (Abrir).
- Receitas: /patients/prescriptions/create, /patients/prescriptions/update, /patients/prescriptions/delete, /patients/prescription/edit (tela) e /patients/prescription/print (impressão).
- Relacionamento: /patients/whatsapp/send-json (botões WhatsApp), /patients/follow-up/export.pdf e /reports/follow-up/export.pdf (Exportar PDF), /patients/timeline/export.csv.

## Perguntas frequentes
**Como cadastro um paciente?** Menu Pacientes > Novo, preencha ao menos o Nome e clique em Criar. Para o paciente receber lembretes, preencha telefone e marque Receber lembretes por WhatsApp.

**Não encontro o paciente na busca.** A busca procura por nome, e-mail e telefone (não por CPF). Tente outra grafia ou parte do telefone. Se o paciente foi excluído, ele não aparece mais. Confira também o filtro de origem.

**Como excluo um paciente?** Perfil do paciente > Excluir paciente. Exige a permissão patients.delete. Se houver agendamentos futuros, cancele-os antes.

**Aparece "Este paciente possui agendamentos futuros".** Cancele os agendamentos futuros dele na Agenda e exclua novamente.

**Não vejo o botão Excluir paciente.** Falta a permissão patients.delete. Peça ao dono em Papéis & Permissões.

**Como exporto minha lista de pacientes?** Em Pacientes clique em Exportar. O CSV respeita a busca atual.

**Como mando o link de senha do portal?** Abra /patients/portal-access?patient_id=ID, informe o e-mail, clique em Criar/Atualizar acesso e use Copiar link para enviar por WhatsApp. O link vale 1 hora.

**Como anexo um exame ao paciente?** Perfil > Documentos > + Enviar documento. Para imagens clínicas (fotos antes e depois) use Imagens.

**Como registro uma alergia?** Perfil > Alertas Clínicos > cartão Alergias > + Adicionar. Ela aparece como alerta no prontuário.

**O paciente fez pedido de alteração de dados.** Menu Pacientes > Solicitações de perfil, confira os campos e clique em Aprovar ou Rejeitar.

**Como envio uma mensagem de aniversário?** Pacientes > Aniversariantes / Follow-up > aba Aniversariantes. Escolha o template e envie individualmente ou em lote. O paciente precisa ter telefone e permissão de WhatsApp.

**Como recupero pacientes sem retorno?** Aba Follow-up (90 a 365 dias sem consulta) ou Relatórios > Follow-up, com envio por WhatsApp e exportação em PDF.

**Posso mudar o nome Paciente para Cliente?** Sim, em Configurações > Geral, bloco Terminologia.
