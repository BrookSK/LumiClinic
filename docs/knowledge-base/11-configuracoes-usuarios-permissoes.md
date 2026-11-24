---
title: Configurações avançadas, usuários, papéis e permissões, documentos LGPD, importador e IA
routes:
  - /settings
  - /settings/operational
  - /settings/ai
  - /settings/importer
  - /settings/lgpd
  - /clinic/legal-signatures
  - /users
  - /rbac
---

# Configurações da clínica

## Mapa do grupo Configurações
O grupo **Configurações** do menu lateral reúne, conforme a permissão de cada pessoa:
| Item | Caminho | Permissão | Onde está explicado |
|---|---|---|---|
| Geral | Configurações > Geral (/settings) | settings.read | 01 e este arquivo |
| Modelos de prontuário | Configurações > Modelos de prontuário | medical_record_templates.manage | 05 |
| Clínica | Configurações > Clínica (/clinic) | clinics.read | 01 |
| Agenda: Horários, Feriados, Regras, Bloqueios | Configurações > Agenda | clinics.read, schedule_rules.manage, blocks.manage | 03 |
| Usuários | Configurações > Usuários e Acesso > Usuários | users.read | 01 e este arquivo |
| Papéis & Permissões | Configurações > Usuários e Acesso | rbac.manage | 01 e este arquivo |
| Documentos LGPD e Assinaturas | Configurações > Documentos e Termos | clinics.read | este arquivo e 14 |
| Anamnese | Configurações > Anamnese | anamnesis.manage | 05 |
| Serviços (Cadastro, Vínculo com estoque, Procedimentos) | Configurações > Serviços | services.manage, procedures.manage | 06 |
| Assinatura (somente Owner) | Menu > Assinatura | papel Owner | 12 |

## Configurações gerais
<!-- perm: settings.read (ver), settings.update (salvar) -->
**Onde fica:** Menu > Configurações > Geral (/settings). A página tem dois blocos.

**Configurações gerais (formulário)**
| Campo | O que faz |
|---|---|
| Paciente / Cliente | Nome usado no sistema para quem é atendido (ex.: Cliente). Obrigatório |
| Consulta / Sessão | Nome usado para o atendimento (ex.: Sessão). Obrigatório |
| Profissional / Especialista | Nome usado para quem atende (ex.: Especialista). Obrigatório |
| Início da semana | Dia em que a semana começa na agenda |
| Anamnese padrão | Modelo de anamnese enviado automaticamente ao confirmar uma consulta. **Nenhum** desliga o envio |
Clique em **Salvar configurações**. Os rótulos mudam em todo o menu e nas telas. A página /settings/terminology tem o mesmo formulário de terminologia.

**Integrações e configurações avançadas** (cartões de atalho)
| Cartão | Para que serve | Onde está explicado |
|---|---|---|
| 💬 WhatsApp | Conexão e configuração do WhatsApp da clínica | 09 |
| 📝 Templates WhatsApp | Modelos de mensagem | 09 |
| 🤖 Inteligência Artificial | Transcrição de áudio no prontuário e geração de feriados | abaixo |
| ⚙ Operacional | Funil, motivos de perda e origens de paciente | abaixo |
| 📅 Google Calendar | Sincronização da agenda | 03 |
| 📋 Logs WhatsApp | Histórico de envios | 09 |
| 📥 Importador Clinicorp | Migrar dados de outro sistema | abaixo |
| 🔌 Calendário Marketing | Integração com o Tuquinha | 09 |

## Configuração operacional (funil, motivos, origens)
<!-- perm: settings.read (ver), settings.update (alterar) -->
**Onde fica:** Menu > Configurações > Geral > **Operacional** (/settings/operational). Título **Configuração operacional** ("Gerencie as listas usadas no dia a dia. Arraste os itens para reordenar.").

Três abas:
- **Etapas do funil**: o funil organiza o paciente desde o primeiro contato até a conversão. Campo **Nova etapa** (ex.: Triagem) e **Adicionar**.
- **Motivos de perda**: por que pacientes desistem. Campo **Novo motivo** (ex.: Preço alto, Sem orçamento, Desistiu) e **Adicionar**.
- **Origem do paciente**: de onde os pacientes vêm. Campo **Nova origem** (ex.: Instagram, Indicação, Google) e **Adicionar**. Aparece no cadastro do paciente e ajuda a medir a origem nos relatórios.
- Arraste o ícone ⠿ para reordenar. Cada item tem um botão para remover. Mensagem de sucesso: "Salvo com sucesso.".

## Inteligência Artificial da clínica
<!-- perm: settings.read (ver), settings.update (salvar, testar, remover) -->
**Onde fica:** Menu > Configurações > Geral > **Inteligência Artificial** (/settings/ai). Usada para a **transcrição de áudio no prontuário** (veja 05) e para **gerar feriados** (03).

A página mostra a situação:
- **IA ativa (configuração global do sistema)**: a IA já está pronta, você não precisa configurar nada. O limite de uso depende do plano de assinatura (veja 12).
- **Chave própria configurada**: a clínica usa a própria chave de acesso do provedor de IA.
- **IA não configurada**: informe a chave no campo e clique em **Salvar**. A chave é guardada criptografada. Para apagar a chave use **Remover chave** > **Confirmar**.
Erros: "Informe a chave para salvar." e "Falha ao testar IA. Verifique a chave e tente novamente.". Nunca compartilhe a chave por e-mail ou mensagem.
Para limites do plano veja 12-assinatura-da-clinica: "Limite de transcrição atingido. Faça upgrade do plano." aparece quando o limite mensal de minutos acaba.

## Importador de dados (Clinicorp)
<!-- perm: settings.update -->
**Onde fica:** Menu > Configurações > Geral > **Importador Clinicorp** (/settings/importer). Serve para trazer para o LumiClinic os dados exportados em planilha .xlsx do Clinicorp.

**Como exportar do Clinicorp** (guia na própria tela "📖 Como exportar dados do Clinicorp")
1. No sistema do Clinicorp abra o menu lateral (três pontinhos) e vá em **Relatórios e Indicadores**.
2. Escolha a seção (Agendamentos, Estoque, Financeiro, Pacientes ou Tratamentos) e o relatório específico (ex.: Geral, Contas a Pagar).
3. Configure os filtros e clique em **Listar**.
4. Clique no ícone de download (📥) acima da tabela para baixar o .xlsx.

**Como importar**
1. Na tela do importador escolha o tipo de dado (cartão com ícone, descrição, **Colunas** esperadas e, quando existir, **⚠ Ignorados** com as colunas que o sistema não usa).
2. Em **📤 Upload do arquivo** selecione o .xlsx e clique em **Importar**.
3. Ao terminar aparece **✅ Importação concluída** (ou **❌ Erro na importação**) com "N registros importados · N ignorados · N erros". Use **Ver erros** e **Ver ignorados e motivo** para abrir as listas (mostra as 10 primeiras e o link **Ver todos os detalhes**).

**Tipos disponíveis**
| Grupo | Tipo | O que importa |
|---|---|---|
| Pacientes | Cadastro de Pacientes | Nome, e-mail, telefone, endereço, nascimento e sexo |
| Pacientes | Localização de Pacientes | Cidade e bairro (cria o paciente se não existir) |
| Pacientes | Cobranças e Pagamentos | Cobranças e pagamentos ligados a pacientes |
| Pacientes | Orçamentos | Orçamentos com procedimentos e valores |
| Agendamentos | Agendamentos - Geral | Agendamentos com data, paciente, horário, profissional e categoria |
| Agendamentos | Agendamentos - Desmarcações | Desmarcados com motivo |
| Agendamentos | Categorias de Agendamento | Entram como serviços |
| Agendamentos | Primeira Consulta | Primeiras consultas com status e observações |
| Financeiro | Contas a Pagar | Vencimento, valor e categoria |
| Financeiro | Parcelas e Pagamentos | Parcelas de pagamentos de pacientes |
| Tratamentos | Procedimentos Executados e Não Executados | Procedimentos realizados ou pendentes |
| Configurações | Profissionais / Funcionários | Cria usuários e profissionais |
Alguns tipos aparecem como **🚧 Em breve** (Produtos, Entradas, Saídas, Quantidades e Validades de Estoque, Tratamentos em Aberto) e ainda não importam.

**Registros ignorados (por quê)**: a linha é ignorada, não é erro. Motivos frequentes, que aparecem na lista com o número da linha da planilha (a linha 1 é o cabeçalho):
- "Nome do paciente vazio", "Data do agendamento vazia", "Data inválida: ...", "Valor zerado ou inválido: ...", "Procedimento vazio", "Categoria vazia", "Parcela cancelada".
- "Paciente já cadastrado com este nome" (evita duplicar), "Serviço já cadastrado com este nome", "Profissional já cadastrado com este nome e já vinculado a um usuário", "Categoria repetida no arquivo".
- "Não foi possível identificar paciente/serviço" (o nome da planilha não bate com um cadastro existente: importe os pacientes antes dos agendamentos e orçamentos).
- "Nenhum profissional ativo cadastrado na clínica" (cadastre um profissional antes de importar agendamentos).

**Histórico de importações**: tabela com Tipo, Arquivo, Total, Importados, Ignorados, Erros, Data e **Ver detalhes** (/settings/importer/log). O detalhe mostra o resumo por motivo, a lista de ignorados com filtro por nome ou motivo, os erros e o botão **Exportar CSV**. Por proteção de dados (LGPD), a identificação dos registros ignorados (nome) é removida 90 dias depois da importação; a linha e o motivo continuam.

**Dicas**
- Importe nesta ordem: Pacientes, Profissionais, Categorias de Agendamento (serviços), depois Agendamentos, Orçamentos e Financeiro.
- Depois de importar **Profissionais / Funcionários**, cada pessoa deve usar **Esqueci minha senha** no login para criar a senha. O papel vem da coluna Type (DENTIST, DOCTOR e NURSE viram Profissional; ASSISTANT, RECEPTIONIST e SECRETARY viram Recepção; ADMIN e MANAGER viram Administrador).
- Apenas arquivos .xlsx são aceitos. Erros: "Apenas arquivos .xlsx são aceitos.", "Arquivo inválido ou não enviado.", "Arquivo vazio ou sem dados.".
- Reimportar o mesmo arquivo não duplica pacientes com o mesmo nome (eles são ignorados).

## Usuários
<!-- perm: users.read (lista), users.create, users.update, users.delete -->
**Onde fica:** Menu > Configurações > Usuários e Acesso > Usuários (/users). Passo a passo completo em 01-primeiros-passos. Resumo:
- **+ Novo usuário**: Nome completo, E-mail de login, Senha, Papel. Erros: "Preencha todos os campos.", "Limite de usuários do plano atingido.".
- **Editar**: nome, e-mail, papel, status, nova senha (vazio mantém a atual) e dados profissionais (especialidade e número do conselho).
- **Arquivar**: o usuário perde o acesso, mas pode ser reativado. **Reativar**: devolve o acesso. **Excluir**: some da lista.
- Você não pode arquivar ou excluir o seu próprio usuário nem o último proprietário da clínica.
- Para quem atende na agenda, o usuário também precisa ser vinculado a um **Profissional** (06-servicos-profissionais).
- O limite de usuários depende do plano da clínica (12-assinatura-da-clinica).

## Papéis e permissões (RBAC)
<!-- perm: rbac.manage -->
**Onde fica:** Menu > Configurações > Usuários e Acesso > Papéis & Permissões (/rbac). Passo a passo em 01-primeiros-passos.

- Papéis de fábrica: **Owner** (dono, não pode ser editado), **Admin**, **Profissional**, **Recepção** e **Financeiro**. A clínica pode criar papéis próprios (ex.: Enfermeiro).
- Em cada permissão marque **Permitir** ou **Negar**. **Negar** sempre vence **Permitir**.
- **Clonar este papel** copia as permissões. **Resetar para padrão** volta às permissões de fábrica.
- Depois de mudar permissões a pessoa deve sair e entrar de novo.

**Glossário das permissões que mais aparecem**
| Código | O que libera |
|---|---|
| scheduling.read / create / update / cancel / finalize | Ver, criar, editar, cancelar e concluir ou confirmar agendamentos |
| scheduling.ops | Painel operacional da agenda |
| scheduling.logs | Logs de agendamento |
| patients.read / create / update / delete / export | Listar, cadastrar, editar, excluir e exportar pacientes |
| medical_records.read / create / update | Ver, criar e editar prontuários |
| medical_images.read / upload | Ver e enviar imagens clínicas |
| anamnesis.manage / fill | Gerenciar modelos de anamnese / preencher anamnese |
| consent_terms.manage / accept | Gerenciar termos / aceitar e assinar |
| services.manage, procedures.manage, professionals.manage | Cadastro de serviços, procedimentos e profissionais |
| schedule_rules.manage, blocks.manage | Regras de agenda e bloqueios |
| finance.sales.read / create / update / cancel | Ver, criar, editar e cancelar orçamentos |
| finance.payments.read / create / refund | Ver, registrar e estornar pagamentos |
| finance.entries.read / create / delete | Ver, criar e excluir lançamentos do caixa |
| finance.ap.read / manage | Ver e gerenciar contas a pagar |
| finance.cost_centers.manage | Centros de custo e plano de contas |
| finance.reports.read | Relatórios financeiros e contábeis |
| stock.materials.read / manage | Ver estoque / criar materiais, categorias e unidades |
| stock.movements.read / create | Ver e registrar movimentações e inventário |
| stock.alerts.read, stock.reports.read | Alertas e relatório de estoque |
| marketing.calendar.read / manage | Ver e gerenciar a agenda de marketing |
| marketing.automation.read / manage | Ver e gerenciar segmentos e campanhas |
| settings.read / update | Ver e alterar configurações gerais, WhatsApp, IA, importador |
| clinics.read / update | Ver e alterar dados da clínica, documentos LGPD e assinaturas |
| users.read / create / update / delete | Gerenciar usuários |
| rbac.manage | Gerenciar papéis e permissões |
| compliance.policies.read / create / update | Ver e gerenciar políticas (Certificações) |
| compliance.controls.create / update | Cadastrar e atualizar controles |

## Documentos LGPD e Assinaturas
<!-- perm: clinics.read -->
**Onde fica:** Menu > Configurações > Documentos e Termos > **Documentos LGPD** (/settings/lgpd) e **Assinaturas** (/clinic/legal-signatures).

**Documentos LGPD** ("Gerencie os termos e políticas que precisam ser aceitos. Documentos marcados como obrigatórios bloqueiam o acesso até serem aceitos.")
- Dois blocos: **Termos do Portal do Paciente** (pacientes aceitam ao entrar no portal) e **Termos da Equipe Interna** (funcionários aceitam ao entrar no sistema).
- **+ Novo** em cada bloco. Campos: **Quem precisa aceitar?** (**Portal do Paciente** ou **Equipe Interna**), **Status** (**Ativo** ou **Inativo**), **Título do documento** (ex.: Política de Privacidade, Termo de Consentimento), **Conteúdo** (texto completo), **Aceite obrigatório** (bloqueia o acesso até aceitar) e **Papel alvo (equipe)** (**Todos os papéis** ou um papel específico). Clique em **Salvar** (mensagem "Salvo."). Erro: "Preencha título e conteúdo.".
- **Editar** em cada documento. Se o documento já foi assinado por alguém, ao salvar o sistema cria uma **nova versão** automaticamente, e os aceites antigos continuam ligados à versão que a pessoa assinou.
- Cada linha mostra o título, o papel alvo (equipe), a etiqueta **Obrigatório** e **Ativo** ou **Inativo**.
- Existem também as telas mais antigas **Documentos legais do Portal do Paciente** (/clinic/legal-documents) e **Documentos legais do Sistema (usuários internos)** (/settings/legal-documents), com lista de ID, Título, Obrigatório, Status e **Editar**. Prefira Documentos LGPD.

**Assinaturas** (/clinic/legal-signatures)
- "Registro de todos os aceites e assinaturas de documentos LGPD."
- Filtro **Filtrar por tipo**: **Todos**, **Portal do Paciente**, **Equipe Interna** ou **Owner**. Botão **Filtrar** e link **Gerenciar documentos**.
- Cada aceite mostra o documento, a versão (v1, v2...), o signatário e a situação de integridade: **✓ Íntegro** ou **✗ Alterado**. **Ver** abre o **Detalhe da Assinatura**: documento, versão, assinante, data e hora, **IP de origem**, **Integridade do documento** (**✓ Verificado**: o conteúdo não foi alterado desde a assinatura), assinatura capturada (ou "Assinatura não disponível (aceite por checkbox)"), conteúdo assinado (versão imutável) e detalhes técnicos de verificação.
- **Aceites de termos obrigatórios (Portal)** (/clinic/legal-acceptances/portal): lista cada paciente com E-mail (Portal), **Obrigatórios**, **Aceitos**, **Pendentes** e **Último aceite**, com o botão **Ver paciente**. Atalhos **Configurar textos** e **Assinaturas**.

## Tokens de API do paciente (pointer)
Os tokens de API são do paciente, criados por ele em /portal/api-tokens. A clínica não gerencia tokens. O tutorial para o paciente fica em /tutorial/api-tokens/paciente. Veja 13-portal-do-paciente.

## Perguntas frequentes
**Como troco "Paciente" por "Cliente" no sistema?** Configurações > Geral, campo **Paciente / Cliente**, depois **Salvar configurações**.

**Como mudo o primeiro dia da semana da agenda?** Configurações > Geral, campo **Início da semana**.

**Como crio um novo funcionário com acesso?** Configurações > Usuários e Acesso > Usuários > **+ Novo usuário**. Escolha o papel certo. Se ele atende, vincule a um profissional.

**Um funcionário saiu. O que faço?** Em Usuários use **Arquivar**. O acesso é bloqueado e o histórico continua. Se ele também era profissional, inative-o em /professionals.

**Como libero uma tela para alguém?** Configurações > Papéis & Permissões > Editar permissões do papel dela. Depois peça para sair e entrar de novo. Se o papel é de fábrica, prefira clonar e ajustar o clone.

**Qual a diferença entre Negar e não marcar?** Não marcar apenas não concede. **Negar** bloqueia mesmo que outra regra conceda.

**Como crio um termo que o paciente precisa aceitar no portal?** Configurações > Documentos LGPD > bloco **Termos do Portal do Paciente** > **+ Novo**, marque **Aceite obrigatório** e salve.

**O que acontece se eu editar um termo que já foi assinado?** Uma nova versão é criada. As assinaturas antigas continuam válidas para a versão antiga.

**Como vejo quem aceitou o termo?** Configurações > Documentos e Termos > **Assinaturas**.

**Como importo meus pacientes do Clinicorp?** Configurações > Geral > Importador Clinicorp, escolha **Cadastro de Pacientes**, selecione o arquivo .xlsx e clique em **Importar**. Os ignorados aparecem com o motivo.

**A importação ignorou registros.** Veja **Ver ignorados e motivo**. Os mais comuns são nome vazio, data inválida, valor zerado e paciente já cadastrado. Corrija a planilha e reimporte.

**Posso usar o importador para estoque?** Ainda não: os tipos de estoque estão marcados como Em breve.

**Para que serve a Inteligência Artificial em Configurações?** Para transcrever áudio no prontuário e gerar feriados. Se aparecer IA ativa (configuração global), não precisa fazer nada.

**O que é a lista Operacional?** São as listas de etapas do funil, motivos de perda e origem do paciente usadas nos cadastros.

## Mapa de rotas
- /settings, /settings/terminology, /settings/operational, /settings/operational/funnel-stages/create, /settings/operational/funnel-stages/delete, /settings/operational/lost-reasons/create, /settings/operational/lost-reasons/delete, /settings/operational/patient-origins/create, /settings/operational/patient-origins/delete, /settings/operational/reorder, /settings/ai, /settings/ai/clear, /settings/importer, /settings/importer/upload, /settings/importer/log, /settings/importer/log/export.csv, /settings/tuquinha.
- /settings/lgpd, /settings/lgpd/edit, /settings/lgpd/save, /settings/legal-documents, /settings/legal-documents/edit, /settings/legal-documents/save, /clinic/legal-documents, /clinic/legal-documents/edit, /clinic/legal-documents/save, /clinic/legal-signatures, /clinic/legal-signatures/view, /clinic/legal-acceptances/portal.
- /users, /rbac (veja 01-primeiros-passos).
