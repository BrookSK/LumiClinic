---
title: Índice geral da base de conhecimento do LumiClinic
routes:
  - /
  - /dashboard
  - /tutorial
---

# Índice geral do LumiClinic

## Para que serve esta base
Este material explica, tela por tela, como usar o LumiClinic na rotina de uma clínica: agenda, pacientes, prontuário, serviços, profissionais, financeiro, estoque e configurações. Cada arquivo traz o caminho no menu, o passo a passo, os campos, os botões, as permissões necessárias, os erros mais comuns e perguntas frequentes.

## Como ler os caminhos de menu
O texto "Menu > Agenda > Agenda" significa: no menu lateral esquerdo, abra o grupo Agenda e clique no item Agenda. Itens que não aparecem para você normalmente indicam que o seu papel não tem a permissão necessária. Peça ao responsável da clínica para ajustar em Configurações > Usuários & Acesso > Papéis & Permissões.

## Módulos e onde encontrar
| Arquivo | Assunto | Caminho no menu | URL principal |
|---|---|---|---|
| 01-primeiros-passos | Login, senha, menu, busca rápida, perfil, dados da clínica, usuários e permissões | Tela de login, ícone de usuário no topo, Configurações | /login, /me, /clinic, /users, /rbac |
| 02-dashboard | Painel inicial e Painel Gestor | Dashboard | /dashboard, /manager/panel |
| 03-agenda | Agenda dia/semana/mês, agendar, status, recepção, fila, bloqueios, horários, feriados, regras, lembretes | Menu > Agenda; Configurações > Agenda | /schedule, /schedule/ops, /schedule/queue, /blocks, /schedule-rules, /clinic/working-hours, /clinic/closed-days |
| 04-pacientes | Cadastro, busca, ficha, alertas clínicos, documentos, assinatura, portal, aniversariantes e follow-up | Pacientes | /patients, /patients/view, /patients/birthdays |
| 05-prontuários-e-atendimento | Prontuário, modelos, atendimento, receitas, anamnese, termos, imagens clínicas | Dentro do paciente; Configurações > Modelos de prontuário, Anamnese | /medical-records, /anamnesis, /consent, /medical-images |
| 06-serviços-profissionais | Serviços, categorias, materiais por serviço, procedimentos (protocolos), profissionais | Configurações > Serviços | /services, /procedures, /professionals |
| 07-financeiro | Orçamentos, pagamentos, fluxo de caixa, contas a pagar, centros de custo, plano de contas, relatórios contábeis e financeiro | Financeiro; Relatórios > Financeiro | /finance/sales, /finance/cashflow, /finance/accounts-payable, /finance/cost-centers, /finance/chart-of-accounts, /finance/accounting, /finance/reports |
| 08-estoque | Materiais, categorias, unidades, movimentações, inventário, alertas e relatório de estoque | Estoque; Relatórios > Estoque | /stock/materials, /stock/movements, /stock/inventory, /stock/alerts, /stock/reports |
| 09-marketing-whatsapp-comunicação | Conectar o WhatsApp por QR Code, templates, logs, e-mails, agenda de marketing, segmentos e campanhas | Configurações > Geral; Marketing | /settings/whatsapp, /whatsapp-templates, /whatsapp-logs, /marketing/calendar, /marketing/automation/campaigns |
| 10-relatórios-e-bi | Mapa dos relatórios, follow-up, exportações | Relatórios | /reports/follow-up, /finance/reports, /stock/reports |
| 11-configurações-usuários-permissões | Configurações gerais, operacional, IA, importador, usuários, papéis, documentos LGPD e assinaturas | Configurações | /settings, /settings/operational, /settings/importer, /settings/lgpd, /users, /rbac |
| 12-assinatura-da-clínica | Plano, cobranças, troca de plano, cartão, bloqueio por pendência | Assinatura (somente Owner) | /billing/subscription |
| 13-portal-do-paciente | Como o paciente acessa o portal e o que ele vê | Portal: login em /portal. Tela da equipe /patients/portal-access (sem item no menu, abra pelo endereço) | /portal, /patients/portal-access |
| 14-lgpd-segurança-e-privacidade | Consentimentos, assinaturas, certificações, senhas e boas práticas | Configurações > Documentos & Termos > Documentos LGPD; Relatórios > Certificações | /settings/lgpd, /clinic/legal-signatures, /compliance/certifications |
| 15-solução-de-problemas | Mais de 60 problemas comuns com causa e solução | Todas as telas | /login, /schedule, /settings/whatsapp |
| 90-quando-falar-com-a-equipe | Quando abrir chamado e quais dados reunir | Assistente de suporte (botão redondo no canto inferior direito) | /tutorial/sistema |
| 99-faq-resoluções | Resoluções aprovadas de chamados | Todas as telas | /tutorial/sistema |

## Glossário
- **Clínica**: o cadastro da sua empresa no sistema. Tudo que você vê pertence a uma única clínica.
- **Paciente**: pessoa atendida. O nome do rótulo pode ser trocado pela clínica (ex.: Cliente) em Configurações > Geral, e o menu acompanha.
- **Profissional**: quem atende na agenda (médico, dentista, esteticista). Precisa estar vinculado a um usuário de login.
- **Usuário**: pessoa com e-mail e senha para entrar no sistema. Tem um papel.
- **Papel**: conjunto de permissões (Owner, Admin, Profissional, Recepção, Financeiro ou papéis criados pela clínica).
- **Serviço**: o que o paciente contrata e aparece na agenda, com duração, preço e cor.
- **Procedimento (protocolo clínico)**: roteiro técnico do atendimento com contraindicações, orientações e etapas. Pode ser vinculado a um serviço.
- **Agendamento**: reserva de um serviço com um profissional em data e hora.
- **Check-in ("Chegou")**: registro de que o paciente chegou na clínica.
- **Faltou (no-show)**: status para paciente que não compareceu.
- **Prontuário**: registro clínico de cada atendimento (descrição, evolução, notas, materiais, imagens).
- **Anamnese**: questionário de saúde do paciente, montado a partir de modelos.
- **Termo de consentimento**: texto que o paciente aceita e assina.
- **Ficha clínica / Alertas clínicos**: alergias, contraindicações, condições e avisos importantes do paciente.
- **Linha do tempo**: histórico cronológico de tudo do paciente.
- **Portal do paciente**: área onde o paciente entra com e-mail e senha para ver agenda e documentos.
- **Orçamento / Venda**: proposta ou venda de serviços ao paciente (módulo Financeiro).
- **Conta a pagar, centro de custo, plano de contas**: conceitos do Financeiro (despesas, onde o gasto é alocado, classificação contábil).
- **Material**: item de estoque que pode ser consumido em atendimentos.
- **Funil**: etapas de captação do paciente (opcional em Configurações > Geral, cartão Operacional).

## Papéis padrão e permissões
Papéis criados junto com a clínica:
- **Owner**: acesso total e não pode ser editado. É o único que vê o item Assinatura no menu.
- **Admin**: acesso a tudo, exceto gerenciar papéis e permissões (rbac.manage).
- **Profissional**: vê a própria agenda, atende (iniciar e concluir), usa prontuário, imagens, anamnese, termos e consulta estoque e orçamentos.
- **Recepção**: agenda, cadastro de pacientes, anamnese e termos, orçamentos e pagamentos, movimentação de estoque. Não vê o fluxo de caixa por padrão.
- **Financeiro**: orçamentos, pagamentos, fluxo de caixa e relatórios financeiros.
Esses são os padrões de fábrica. A clínica pode mudar qualquer papel (menos Owner) ou criar novos.

Permissões que mais aparecem nas telas:
| Código | O que libera |
|---|---|
| scheduling.read | Ver a agenda |
| scheduling.create | Criar agendamentos |
| scheduling.update | Reagendar, check-in pela recepção |
| scheduling.cancel | Cancelar agendamentos |
| scheduling.finalize | Confirmar, iniciar, concluir atendimento, marcar Faltou, Fila de chegada |
| scheduling.ops | Tela Operação da Agenda (recepção) |
| scheduling.logs | Histórico imutável do agendamento |
| blocks.manage | Bloqueios de agenda |
| schedule_rules.manage | Regras (horários) de cada profissional |
| clinics.read / clinics.update | Ver / editar dados da clínica, horários e feriados |
| patients.read | Listar e abrir pacientes, linha do tempo, documentos |
| patients.create | Cadastrar pacientes |
| patients.update | Editar pacientes, alertas clínicos, documentos, portal |
| patients.delete | Excluir pacientes |
| patients.export | Exportar a linha do tempo em CSV |
| medical_records.read / create / update | Ver, criar, editar prontuário e receitas |
| medical_record_templates.manage | Modelos de prontuário |
| medical_images.read / upload | Ver / enviar e marcar imagens clínicas |
| files.read | Abrir arquivos privados (fotos, anexos, assinaturas) |
| anamnesis.manage / anamnesis.fill | Criar modelos / preencher e enviar anamnese |
| consent_terms.manage / consent_terms.accept | Criar termos / coletar aceite e assinatura |
| services.manage | Serviços, categorias, materiais do serviço |
| procedures.manage | Procedimentos (protocolos clínicos) |
| professionals.manage | Cadastro de profissionais |
| users.read / create / update / delete | Ver, criar, editar, arquivar e excluir usuários |
| rbac.manage | Papéis e permissões |
| settings.read / settings.update | Ver / alterar configurações gerais, WhatsApp, Google Calendar |

## Como o dono libera uma permissão
1. Menu > Configurações > Usuários & Acesso > Papéis & Permissões (/rbac).
2. Clique em Editar permissões no papel desejado.
3. Abra o módulo, marque Permitir (ou Negar) na permissão e clique em Salvar permissões.
4. Para o usuário ver o efeito, ele deve sair e entrar de novo no sistema.
Detalhes completos em 01-primeiros-passos.

## Como pedir ajuda
No fim do menu lateral, **Ajuda** abre os tutoriais do sistema, **Guia Completo** traz o manual em uma página e **Configuração inicial** mostra o passo a passo para deixar a clínica pronta.

## Outros módulos
Financeiro, Estoque, Marketing e WhatsApp, Relatórios, Configurações avançadas, Assinatura, Portal do Paciente, LGPD e segurança e Solução de problemas ficam nos arquivos 07 a 15. Se a dúvida não estiver em nenhum arquivo, descreva a tela e o que tentou fazer: a equipe de suporte pode ajudar (veja 90-quando-falar-com-a-equipe).

## Limitações conhecidas
Comportamentos atuais do sistema que costumam gerar dúvida. Não são erros do seu uso.
- **Menu**: algumas telas não têm item no menu e abrem pelo endereço ou por botão dentro de outra tela: Profissionais (/professionals), Acesso ao Portal (/patients/portal-access), Histórico de consultas do paciente (/patients/appointments), Conteúdos do portal (/patients/content), Termos de consentimento (/consent-terms), moderação de imagens (/medical-images/moderation), Painel Gestor (/manager/panel), Movimentações (/stock/movements) e Inventário (/stock/inventory) do estoque, e as telas Buscar, Acesso, Conteúdos, Resumo, LGPD e Tokens de API dentro do portal do paciente.
- **Termo de consentimento**: o botão "+ Novo aceite" da tela Termos do paciente abre /consent/accept sem escolher o termo e volta para a lista de pacientes. Para registrar o aceite use Enviar p/ Assinatura no perfil do paciente ou abra o termo pelo endereço com o term_id (05-prontuarios-e-atendimento).
- **Remarcar pelo histórico do paciente**: o link Reagendar dentro de /patients/appointments pode não abrir o agendamento correto. Remarque pela Agenda (/schedule).
- **Alerta clínico**: a tela do paciente não mostra um botão para marcar o alerta como resolvido. Para retirar um alerta da lista use o botão ✕ de remover na ficha clínica (/patients/clinical-sheet).
- **Modelos de prontuário**: os modelos cadastrados não preenchem sozinhos o formulário de novo prontuário; use-os como roteiro de consulta.
- **Serviços**: se o nome estiver vazio ou a duração for zero, o formulário volta para a lista sem mensagem. Revise esses campos.
- **Materiais no prontuário**: materiais com quantidade maior que o saldo em estoque são ignorados ao salvar o prontuário. Confira o saldo antes.
- **Comissões e especialidades**: o sistema não tem cadastro de comissões por profissional nem catálogo de especialidades (a especialidade é um texto livre).
- **Financeiro, estoque e marketing**: veja a seção Limitações conhecidas de cada arquivo (07, 08 e 09).

## Perguntas frequentes
**Por onde começo a configurar a clínica?** No fim do menu lateral clique em **Configuração inicial**. São 7 passos: dados da clínica, horários, usuários, regras de agenda, serviços, primeiro paciente e primeiro agendamento.

**Um item do menu não aparece para mim.** O menu mostra só o que o seu papel permite. Peça ao dono para liberar a permissão em Configurações > Usuários & Acesso > Papéis & Permissões e saia e entre de novo.

**Como agendo uma consulta?** Menu > Agenda > Agenda. Na visão **Semana** (a que abre primeiro) não existe botão Novo agendamento: clique em uma linha vazia da grade, no dia e horário desejados. Na aba **Dia** há o botão **Novo agendamento** no topo à direita. Na aba **Mês** clique no dia e depois em **+ Novo agendamento**. Na janela Novo agendamento escolha o Paciente, o Serviço, o Profissional, o Horário e clique em **Agendar**.

**Como cadastro um paciente?** Menu > Pacientes > Pacientes, clique em **Novo** e preencha o formulário. Apenas o Nome é obrigatório; depois clique em **Criar**.

**Como mudo meu nome, telefone ou senha?** Clique no ícone de pessoa no topo da tela e depois em **Perfil**.

**Como o sistema chama o paciente na minha clínica?** O nome pode ser trocado em Configurações > Geral, bloco Terminologia (por exemplo Cliente).

