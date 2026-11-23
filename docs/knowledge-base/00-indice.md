---
title: Indice geral da base de conhecimento do LumiClinic
routes:
  - /
  - /dashboard
  - /tutorial
---

# Indice geral do LumiClinic

## Para que serve esta base
Este material explica, tela por tela, como usar o LumiClinic na rotina de uma clinica: agenda, pacientes, prontuario, servicos, profissionais, financeiro, estoque e configuracoes. Cada arquivo traz o caminho no menu, o passo a passo, os campos, os botoes, as permissoes necessarias, os erros mais comuns e perguntas frequentes.

## Como ler os caminhos de menu
O texto "Menu > Agenda > Agenda" significa: no menu lateral esquerdo, abra o grupo Agenda e clique no item Agenda. Itens que nao aparecem para voce normalmente indicam que o seu papel nao tem a permissao necessaria. Peca ao responsavel da clinica para ajustar em Configuracoes > Usuarios e Acesso > Papeis e Permissoes.

## Modulos e onde encontrar
| Arquivo | Assunto | Caminho no menu | URL principal |
|---|---|---|---|
| 01-primeiros-passos | Login, senha, menu, busca rapida, perfil, dados da clinica, usuarios e permissoes | Tela de login, icone de usuario no topo, Configuracoes | /login, /me, /clinic, /users, /rbac |
| 02-dashboard | Painel inicial e Painel Gestor | Dashboard | /dashboard, /manager/panel |
| 03-agenda | Agenda dia/semana/mes, agendar, status, recepcao, fila, bloqueios, horarios, feriados, regras, lembretes | Agenda; Configuracoes > Agenda | /schedule, /schedule/ops, /schedule/queue, /blocks, /schedule-rules, /clinic/working-hours, /clinic/closed-days |
| 04-pacientes | Cadastro, busca, ficha, alertas clinicos, documentos, assinatura, portal, aniversariantes e follow-up | Pacientes | /patients, /patients/view, /patients/birthdays |
| 05-prontuarios-e-atendimento | Prontuario, modelos, atendimento, receitas, anamnese, termos, imagens clinicas | Dentro do paciente; Configuracoes > Modelos de prontuario, Anamnese | /medical-records, /anamnesis, /consent, /medical-images |
| 06-servicos-profissionais | Servicos, categorias, materiais por servico, procedimentos (protocolos), profissionais | Configuracoes > Servicos | /services, /procedures, /professionals |

## Glossario
- **Clinica**: o cadastro da sua empresa no sistema. Tudo que voce ve pertence a uma unica clinica.
- **Paciente**: pessoa atendida. O nome do rotulo pode ser trocado pela clinica (ex.: Cliente) em Configuracoes > Geral, e o menu acompanha.
- **Profissional**: quem atende na agenda (medico, dentista, esteticista). Precisa estar vinculado a um usuario de login.
- **Usuario**: pessoa com e-mail e senha para entrar no sistema. Tem um papel.
- **Papel**: conjunto de permissoes (Owner, Admin, Profissional, Recepcao, Financeiro ou papeis criados pela clinica).
- **Servico**: o que o paciente contrata e aparece na agenda, com duracao, preco e cor.
- **Procedimento (protocolo clinico)**: roteiro tecnico do atendimento com contraindicacoes, orientacoes e etapas. Pode ser vinculado a um servico.
- **Agendamento**: reserva de um servico com um profissional em data e hora.
- **Check-in ("Chegou")**: registro de que o paciente chegou na clinica.
- **Faltou (no-show)**: status para paciente que nao compareceu.
- **Prontuario**: registro clinico de cada atendimento (descricao, evolucao, notas, materiais, imagens).
- **Anamnese**: questionario de saude do paciente, montado a partir de modelos.
- **Termo de consentimento**: texto que o paciente aceita e assina.
- **Ficha clinica / Alertas clinicos**: alergias, contraindicacoes, condicoes e avisos importantes do paciente.
- **Linha do tempo**: historico cronologico de tudo do paciente.
- **Portal do paciente**: area onde o paciente entra com e-mail e senha para ver agenda e documentos.
- **Orcamento / Venda**: proposta ou venda de servicos ao paciente (modulo Financeiro).
- **Conta a pagar, centro de custo, plano de contas**: conceitos do Financeiro (despesas, onde o gasto e alocado, classificacao contabil).
- **Material**: item de estoque que pode ser consumido em atendimentos.
- **Funil**: etapas de captacao do paciente (opcional em Configuracoes > Operacional).

## Papeis padrao e permissoes
Papeis criados junto com a clinica:
- **Owner**: acesso total e nao pode ser editado. E o unico que ve o item Assinatura no menu.
- **Admin**: acesso a tudo, exceto gerenciar papeis e permissoes (rbac.manage).
- **Profissional**: ve a propria agenda, atende (iniciar e concluir), usa prontuario, imagens, anamnese, termos e consulta estoque e orcamentos.
- **Recepcao**: agenda, cadastro de pacientes, anamnese e termos, orcamentos e pagamentos, movimentacao de estoque. Nao ve o fluxo de caixa por padrao.
- **Financeiro**: orcamentos, pagamentos, fluxo de caixa e relatorios financeiros.
Esses sao os padroes de fabrica. A clinica pode mudar qualquer papel (menos Owner) ou criar novos.

Permissoes que mais aparecem nas telas:
| Codigo | O que libera |
|---|---|
| scheduling.read | Ver a agenda |
| scheduling.create | Criar agendamentos |
| scheduling.update | Reagendar, check-in pela recepcao |
| scheduling.cancel | Cancelar agendamentos |
| scheduling.finalize | Confirmar, iniciar, concluir atendimento, marcar Faltou, Fila de chegada |
| scheduling.ops | Tela Operacao da Agenda (recepcao) |
| scheduling.logs | Historico imutavel do agendamento |
| blocks.manage | Bloqueios de agenda |
| schedule_rules.manage | Regras (horarios) de cada profissional |
| clinics.read / clinics.update | Ver / editar dados da clinica, horarios e feriados |
| patients.read | Listar e abrir pacientes, linha do tempo, documentos |
| patients.create | Cadastrar pacientes |
| patients.update | Editar pacientes, alertas clinicos, documentos, portal |
| patients.delete | Excluir pacientes |
| patients.export | Exportar a linha do tempo em CSV |
| medical_records.read / create / update | Ver, criar, editar prontuario e receitas |
| medical_record_templates.manage | Modelos de prontuario |
| medical_images.read / upload | Ver / enviar e marcar imagens clinicas |
| files.read | Abrir arquivos privados (fotos, anexos, assinaturas) |
| anamnesis.manage / anamnesis.fill | Criar modelos / preencher e enviar anamnese |
| consent_terms.manage / consent_terms.accept | Criar termos / coletar aceite e assinatura |
| services.manage | Servicos, categorias, materiais do servico |
| procedures.manage | Procedimentos (protocolos clinicos) |
| professionals.manage | Cadastro de profissionais |
| users.read / create / update / delete | Ver, criar, editar, arquivar e excluir usuarios |
| rbac.manage | Papeis e permissoes |
| settings.read / settings.update | Ver / alterar configuracoes gerais, WhatsApp, Google Calendar |

## Como o dono libera uma permissao
1. Menu > Configuracoes > Usuarios e Acesso > Papeis e Permissoes (/rbac).
2. Clique em Editar permissoes no papel desejado.
3. Abra o modulo, marque Permitir (ou Negar) na permissao e clique em Salvar permissoes.
4. Para o usuario ver o efeito, ele deve sair e entrar de novo no sistema.
Detalhes completos em 01-primeiros-passos.

## Como pedir ajuda
Menu > Ajuda abre os tutoriais do sistema, Guia Completo traz o manual em uma pagina e Configuracao inicial mostra o passo a passo para deixar a clinica pronta.

## Outros modulos
Financeiro (orcamentos, caixa, contas a pagar, centros de custo, plano de contas), Estoque, Marketing, Relatorios, Configuracoes avancadas, Portal do Paciente, seguranca e LGPD ficam em arquivos proprios da base de conhecimento. Se a duvida for sobre um desses assuntos e voce nao encontrar a resposta aqui, descreva a tela e o que tentou fazer.

## Perguntas frequentes
**Por onde comeco a configurar a clinica?** Menu > Ajuda > Configuracao inicial. Sao 7 passos: dados da clinica, horarios, usuarios, regras de agenda, servicos, primeiro paciente e primeiro agendamento.

**Um item do menu nao aparece para mim.** O menu mostra so o que o seu papel permite. Peca ao dono para liberar a permissao em Configuracoes > Usuarios e Acesso > Papeis e Permissoes e saia e entre de novo.

**Como agendo uma consulta?** Menu Agenda > Agenda > Novo agendamento. Escolha paciente, servico, profissional, data e horario e clique em Agendar.

**Como cadastro um paciente?** Menu Pacientes > Novo. Apenas o nome e obrigatorio.

**Como mudo meu nome, telefone ou senha?** Icone de usuario no topo > Perfil.

**Como o sistema chama o paciente na minha clinica?** O nome pode ser trocado em Configuracoes > Geral > Terminologia (por exemplo Cliente).

