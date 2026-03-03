# LumiClinic — Macroprocessos Operacionais (2.1)

Este documento define, de forma formal e padronizada, os **macroprocessos** do sistema e suas **etapas**, além de uma proposta de **Configuração Operacional** (funil de atendimento e categorias) para uso por equipes não técnicas.

---

## 1) Objetivo

- Padronizar “como a clínica trabalha” dentro do sistema.
- Evitar telas e termos ambíguos para usuários leigos.
- Facilitar treinamento, auditoria e melhoria contínua.

---

## 2) Conceitos e nomenclatura

- **Macroprocesso**: um fluxo grande do negócio (ex.: Atendimento, Financeiro).
- **Etapa**: um passo dentro do macroprocesso (ex.: Agendamento confirmado).
- **Configuração operacional**: ajustes que mudam o comportamento do dia a dia (ex.: status/etapas do funil, categorias, motivos).

---

## 3) Macroprocessos e etapas (formal)

### 3.1 Captação / Pré-atendimento (Funil)

**Objetivo**
- Organizar a entrada de pacientes e oportunidades até virarem um agendamento.

**Etapas sugeridas (padrão)**
1. Novo contato
2. Triagem
3. Orçamento / proposta
4. Agendamento (criado)
5. Convertido (atendido)
6. Perdido / desistência

**Saídas**
- Criação de paciente.
- Criação de agendamento na Agenda.

---

### 3.2 Atendimento (Agenda)

**Objetivo**
- Planejar, confirmar e executar atendimentos.

**Etapas (padrão do sistema)**
1. Agendamento criado
2. Confirmado
3. Em atendimento
4. Concluído
5. Não compareceu (no-show)
6. Cancelado
7. Reagendado

**Regras e controles associados**
- Horários de funcionamento / indisponibilidades (bloqueios)
- Profissionais
- Serviços

**Saídas**
- Registro no prontuário.
- Consumo de materiais (quando aplicável).
- Gatilhos para cobrança/financeiro (quando aplicável).

---

### 3.3 Prontuário / Registro clínico

**Objetivo**
- Registrar atendimentos, procedimentos e histórico.

**Etapas sugeridas**
1. Atendimento realizado (origem: Agenda)
2. Registro criado
3. Registro revisado/validado (quando aplicável)

**Saídas**
- Base para auditoria clínica.
- Integração com imagens e anexos.

#### 3.3.1 Automação com IA (não implementado / opcional)

**Objetivo**
- Acelerar a criação de registros clínicos estruturados a partir de áudio do atendimento, com revisão humana, rastreabilidade e consentimento.

**Fluxo proposto (futuro)**
1. Captura de áudio do atendimento
   - Origem: tela de atendimento (Agenda) ou prontuário.
   - Controle: início/parada explícitos + indicador visual de gravação.
2. Consentimento e base legal
   - Registro explícito do consentimento do paciente (ou outra base legal aplicável) antes de gravar/transcrever.
   - Registro de quem coletou o consentimento, data/hora, e contexto (paciente/atendimento).
3. Upload/armazenamento do áudio
   - Armazenar como anexo privado (controle de acesso e trilha de auditoria).
   - Retenção configurável por clínica (prazo e política de expurgo).
4. Transcrição (serviço externo)
   - Enviar áudio para provedor de transcrição (ex.: Whisper/Deepgram/AWS Transcribe) via integração.
   - Armazenar:
     - texto transcrito;
     - metadados (provedor, idioma, confiança/score, tempo de processamento);
     - identificadores para auditoria e troubleshooting.
5. Pipeline de extração (campos estruturados)
   - A partir da transcrição, sugerir campos para:
     - queixa principal;
     - anamnese dirigida;
     - conduta;
     - evolução;
     - procedimentos e materiais (quando aplicável);
     - alertas clínicos e sinais de risco.
   - Resultado deve ser “sugestão”, nunca gravação automática final sem revisão.
6. Revisão humana + assinatura/validação
   - Profissional revisa sugestões, edita e confirma.
   - Registro de “aprovado por” + data/hora + versão (antes/depois) para auditoria.
7. Auditoria, segurança e conformidade
   - Logar eventos críticos:
     - início/fim gravação;
     - envio ao provedor;
     - recebimento da transcrição;
     - aplicação do extrator;
     - revisão/aprovação.
   - Permissões dedicadas (ex.: gravar/transcrever) e escopo por clínica.
   - Possibilidade de exportar os dados (LGPD) e rastrear acessos.

---

### 3.4 Imagens e anexos clínicos

**Objetivo**
- Armazenar imagens e evidências (ex.: fotos clínicas, exames).

**Etapas (padrão)**
1. Upload de imagem
2. Classificação (tipo / procedimento / profissional)
3. Vincular ao prontuário (opcional)
4. Comparação Antes x Depois (opcional)

**Saídas**
- Arquivo acessível por link interno.
- Comparação disponível no histórico do paciente.

---

### 3.5 Anamnese (modelos e preenchimento)

**Objetivo**
- Coletar dados padronizados do paciente.

**Etapas**
1. Criar template
2. Configurar campos
3. Preencher para um paciente
4. Consultar respostas

---

### 3.6 Financeiro (Vendas e recebimentos)

**Objetivo**
- Controlar vendas, pagamentos, estornos e relatórios.

**Etapas sugeridas**
1. Venda criada
2. Itens/serviços informados
3. Pagamento lançado
4. Conciliação (quando aplicável)
5. Estorno/cancelamento (quando aplicável)

**Saídas**
- Indicadores e relatórios.

---

### 3.7 Estoque (materiais)

**Objetivo**
- Controlar materiais, categorias, unidades e movimentações.

**Etapas**
1. Cadastro de unidades
2. Cadastro de categorias
3. Cadastro de materiais
4. Movimentações (entrada/saída/perda)
5. Vínculo de materiais por serviço (padrão por sessão)

**Saídas**
- Alertas (mínimo/validade, quando aplicável).
- Consumo vinculado a sessões.

---

### 3.8 LGPD / Compliance (Políticas e Controles)

**Objetivo**
- Padronizar políticas e controles (auditoria e governança).

**Etapas sugeridas**
1. Política criada (rascunho)
2. Política ativa
3. Política desativada

**Controles**
1. Planejado
2. Implementado
3. Testado
4. Falhou (exige ação)

**Saídas**
- Evidências e histórico de conformidade.

---

## 4) Configuração operacional (Parcial)

### 4.1 Funil de atendimento (etapas e motivos)

**O que é**
- Um conjunto de etapas para organizar a entrada do paciente até virar agendamento.

**Configurações necessárias**
- Lista de **etapas do funil** (nome, ordem, cor/ícone opcional, ativo/inativo)
- Lista de **motivos de perda** (ex.: sem orçamento, sem agenda, desistiu)

**Regras (padrão recomendado)**
- Etapas devem ser poucas (5 a 8), nomes simples.
- Motivos de perda obrigatórios apenas ao marcar como “Perdido”.

### 4.2 Categorias (pontos e classificações)

**Objetivo**
- Evitar campos soltos e permitir filtros/relatórios.

**Categorias sugeridas (exemplos)**
- Origem do contato (Instagram, Indicação, Google)
- Tipo de procedimento (Harmonização, Odonto, Dermatologia)
- Canal de atendimento (WhatsApp, Telefone, Presencial)

---

## 5) Padrão de implementação no sistema (proposta)

Para o item “Configuração operacional”, a recomendação é criar um menu em:
- **Configurações → Operacional**

Com as telas:
- Funil de atendimento (etapas)
- Motivos de perda
- Categorias (por tipo)

Regras de UX:
- Linguagem simples
- Botões claros (“Adicionar etapa”, “Salvar”, “Desativar”)
- Ajuda curta em cada tela (“Para que serve”)

---

## 6) Pendências do 2.1 (para completar)

- Definir se o funil será ligado a:
  - Paciente (cadastro)
  - Agendamento
  - Ambos
- Definir onde as categorias serão usadas primeiro (mínimo viável):
  - Origem do paciente
  - Motivo de cancelamento
  - Motivo de reagendamento
