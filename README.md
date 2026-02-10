
# LumiClinic

Sistema de gestão para clínicas (multi-clínica) com módulos de:

- **Agenda**
- **Pacientes**
- **Prontuário / Imagens / Uploads**
- **Financeiro**
- **Estoque**
- **Portal do Paciente**
- **Super Admin** (plataforma SaaS)

---

## 1) Requisitos

- PHP (com extensões comuns: `pdo_mysql`, `mbstring`, `openssl`)
- MySQL/MariaDB
- Servidor web (Apache/Nginx) apontando a **DocumentRoot para `public/`**
- Composer

---

## 2) Instalação (ambiente local)

### 2.1 Configurar variáveis de ambiente

1. Copie o exemplo:

   - `.env.example` -> `.env`

2. Preencha no `.env` (mínimo):

   - Credenciais do banco
   - URL/base do app (conforme ambiente)

### 2.2 Instalar dependências

```bash
composer install
```

### 2.3 Criar banco e aplicar migrations

As migrations ficam em `database/migrations/*.sql`.

- **Importante:** aplique em ordem (por número).

Sugestão de fluxo (MySQL/MariaDB):

1. Crie o banco vazio.
2. Aplique os arquivos `database/migrations/*.sql` em ordem crescente.
3. Após atualizar o repositório, reaplique apenas as migrations novas.

Novidades relevantes:

- `0086_users_email_per_clinic.sql`
  - e-mail de usuário agora é **único por clínica** (multi-clínica)
- `0087_stock_material_categories_and_units.sql`
  - cria `material_categories` e `material_units`

### 2.4 Subir o servidor

O projeto é servido via `public/index.php`.

- Em Apache, configure o VirtualHost com DocumentRoot `.../public`
- Em Nginx, configure a raiz para `.../public` e `try_files` apontando para `index.php`

---

## 3) Acessos e Contexto (clínica / portal / super admin)

### 3.1 Área da Clínica (staff)

- Login:
  - `GET /login`
- Recuperar senha:
  - `GET /forgot`
- Reset:
  - `GET /reset`

### 3.2 Portal do Paciente

- Login:
  - `GET /portal/login`

### 3.3 Super Admin (plataforma)

- Área do sistema para administrar clínicas, assinaturas e planos.
- Se o usuário for Super Admin e estiver **sem contexto de clínica ativo**, algumas telas redirecionam para seleção de clínica.

### 3.4 Primeiros passos (passo a passo de uso)

1. **Acesse como Super Admin** (se aplicável) e crie/ative uma clínica.
2. **Selecione o contexto da clínica** (Super Admin -> lista de clínicas).
3. **Crie usuários da clínica** (Owner/Admin/Recepção/Profissional) e faça login na área staff:

   - `GET /login`

4. **Configure a clínica**:

   - Horários de funcionamento
   - Feriados/recesso

5. **Cadastre profissionais** e configure:

   - Regras de agenda (`/schedule-rules`)

6. **Cadastre serviços** e (opcional) materiais padrão do serviço.
7. **Use a Agenda** (`/schedule`) para criar e gerenciar agendamentos.
8. **Estoque**:

   - Cadastre **Unidades** (`/stock/units`)
   - Cadastre **Categorias** (`/stock/categories`)
   - Cadastre **Materiais** (`/stock/materials`)
   - Lance movimentações (`/stock/movements`)

9. **Portal do Paciente**:

   - Login em `GET /portal/login`
   - A disponibilidade pode ser bloqueada por plano (ver seção de Planos).

---

## 4) Planos SaaS e enforcement (limits_json)

O sistema lê os benefícios/limites do plano ativo da clínica a partir de `limits_json`.

Pontos principais:

- **Portal** pode ser bloqueado por plano.
- Limites de:
  - **usuários**
  - **pacientes**
  - **armazenamento** (uploads/imagens)

Configuração de planos (Super Admin):

- `GET /sys/plans`

---

## 5) Estoque: Categorias e Unidades de Materiais

Para melhorar consistência, categorias e unidades são gerenciadas em listas por clínica.

### 5.1 Telas de gerenciamento

- Categorias:
  - `GET /stock/categories`
- Unidades:
  - `GET /stock/units`

### 5.2 Cadastro de materiais

- `GET /stock/materials`

O cadastro de material usa **select** de:

- Categoria (opcional)
- Unidade (obrigatória)

E o backend valida que:

- unidade existe e está ativa
- categoria (quando informada) existe e está ativa

---

## 6) Branding (logo / favicon)

Arquivos esperados em `public/`:

- `Principal_1.png` (logo)
- `icone_1.png` (ícone / favicon)

Se o favicon/ícone não carregar em produção, confira:

- DocumentRoot apontando para `public/`
- Se os arquivos estão sendo publicados no deploy

---

## 7) Segurança: CSP e scripts `blob:`

Existe uma política de segurança via header `Content-Security-Policy`.

Se você vir erro do tipo:

- `Loading the script 'blob:...' violates Content Security Policy`

verifique o middleware:

- `app/Middleware/SecurityHeadersMiddleware.php`

---

## 8) Troubleshooting rápido

- **404 em assets (png/css/js)**
  - confirme DocumentRoot em `public/`

- **Mudou permissões e não refletiu**
  - faça logout/login (permissões ficam na sessão)
  - em produção, reinicie PHP-FPM/Apache (OPcache)

---

## 9) Checklist de Testes (QA)

Este documento também contém um roteiro **prático** para testar o sistema ponta‑a‑ponta (funcionalidades, permissões e visualizações) em ambiente local/staging/produção.

---

## 1) Pré‑requisitos

- PHP/Servidor web configurado (Apache/Nginx)
- Banco MySQL/MariaDB
- Projeto configurado e acessível via navegador

Recomendação para testar permissões corretamente:

- Faça os testes em **aba anônima** ou em **navegadores diferentes** (evita sessão/cache confundindo resultados).
- Sempre que mudar permissões/roles no banco, faça **logout/login** (permissões são carregadas na sessão no login).

---

## 2) Setup inicial (dados mínimos)

### 2.1 Criar/selecionar clínica

- Se você for **super admin**, selecione o contexto da clínica (ex.: tela de clínicas do sistema) antes de acessar os módulos.

### 2.2 Criar usuários de teste (recomendado)

Crie pelo menos estes perfis (um login para cada):

- **Owner (Dono)**: acesso máximo na clínica.
- **Admin**: gestão e operação.
- **Recepção**: agendamento e atendimento ao paciente.
- **Profissional**: acesso limitado aos próprios atendimentos.
- **Financeiro** (se existir no seu fluxo): módulo financeiro.

> Dica: use e-mails fáceis e senhas padrão em staging (ex.: `owner@teste.com`, `admin@teste.com`, etc.).

### 2.3 Cadastrar profissionais

Para testar Agenda, o “usuário” precisa existir como **Profissional**.

- Cadastre o profissional
- Marque como **Ativo**
- Vincule à clínica correta

---

## 3) Checklist de Permissões (RBAC)

O sistema usa permissões (ex.: `stock.materials.manage`) associadas a roles/perfis.

### 3.1 Como validar rapidamente se a permissão está carregada

1. Faça login com o usuário
2. Verifique se a tela/ação aparece (ex.: botão/formulário)
3. Se você acabou de mudar permissões e não refletiu:
   - faça **logout/login**
   - em produção com cache: reinicie o PHP/Apache/PHP‑FPM (OPcache)

### 3.2 Casos críticos para validar

- **Estoque**
  - `stock.materials.read`: ver lista de materiais
  - `stock.materials.manage`: ver **form/botão** de “Novo material” e conseguir cadastrar

- **Agenda**
  - `scheduling.read`: visualizar agenda
  - `scheduling.update`: reagendar/alterar
  - `scheduling.cancel`: cancelar
  - `scheduling.finalize`: confirmar/atender/no-show/concluir

- **Regras de agenda**
  - `schedule_rules.manage`: cadastrar horários de profissionais

- **Clínica**
  - `clinics.read`: visualizar dados
  - `clinics.update`: alterar dados e cadastrar horários de funcionamento

> Observação: os nomes exatos das permissões podem variar conforme sua base. Se quiser conferir, valide a tabela `permissions` no banco.

---

## 4) Testes de Agenda (principal)

### 4.1 Configurar horários (obrigatório)

Para existir horário disponível, precisa bater **ao mesmo tempo**:

- **Horário de funcionamento da clínica** (Clínica > Horários de funcionamento)
- **Regra de agenda do profissional** (Agenda > Regras de Agenda)
- O serviço precisa ter **duração válida** (e buffers, se existirem, não podem estourar a janela)

#### 4.1.1 Cadastrar horário da clínica

1. Acesse **Clínica > Horários de funcionamento**
2. Adicione o horário por dia da semana (ex.: Domingo 09:00–18:00)
3. Confirme se aparece na tabela de horários cadastrados

#### 4.1.2 Cadastrar horário do profissional

1. Acesse **Agenda > Regras de Agenda** (`/schedule-rules`)
2. Selecione o profissional
3. Crie a regra do dia (ex.: Domingo 09:00–18:00)
4. Salve e confirme na lista

### 4.2 Criar serviço

1. Cadastre um serviço (ex.: “Corte de cabelo”)
2. Garanta:
   - `duration_minutes` > 0 (ex.: 30)
   - buffers (se houver) em 0 para teste inicial

### 4.3 Criar agendamento

1. Acesse **Agenda** (`/schedule`)
2. Escolha uma data
3. Em “Criar agendamento”:
   - selecione **Serviço**
   - selecione **Profissional**
   - aguarde carregar o select **Horário**
4. Se aparecer “Sem horários disponíveis”:
   - confira se a data é o dia correto da semana
   - confira horários de clínica e profissional
   - confira duração/buffer do serviço

### 4.4 Ações no agendamento (status)

Na visão do dia/semana você terá botões como:

- **Confirmar** → status `confirmed`
- **Atender** → status `in_progress`
- **No-show** → status `no_show`
- **Concluir** → status `completed` (pode redirecionar para completar materiais)
- **Cancelar** → status `cancelled`

Checklist:

1. Criar agendamento → status inicial (geralmente `scheduled`)
2. Clicar **Confirmar**
3. Clicar **Atender**
4. Clicar **No-show** (valide se funciona a partir de `scheduled`, `confirmed` e `in_progress` conforme regra)
5. Clicar **Concluir** (valide o fluxo de “completar materiais”, se ativo)

> Se der erro “Transição de status inválida”, valide a regra do backend e o status atual do agendamento.

---

## 5) Visualizações da Agenda

### 5.1 Visão do Dia

URL: `GET /schedule?view=day&date=YYYY-MM-DD`

Validar:

- lista de agendamentos do dia
- criação de agendamento (se não for usuário profissional)
- botões de status funcionando

### 5.2 Visão da Semana

URL: `GET /schedule?view=week&date=YYYY-MM-DD`

Validar:

- 7 colunas (Dom–Sáb)
- cada dia mostra agendamentos
- botões de status/reagendar/logs
- filtro por profissional (quando não é profissional)

### 5.3 Visão do Mês

URL: `GET /schedule?view=month&date=YYYY-MM-DD`

Validar:

- calendário em grid (6 semanas)
- navegação **Anterior/Próximo**
- contagem de agendamentos por dia
- clique em um dia abre a visão do dia

---

## 6) Testes de Estoque (Materiais)

### 6.1 Permissões

- Usuário **sem** `stock.materials.manage`:
  - deve **ver a lista** (se tiver `stock.materials.read`)
  - deve **não ver** o formulário/botão de “Novo material”

- Usuário **com** `stock.materials.manage`:
  - deve **ver** formulário/botão
  - deve conseguir cadastrar material

### 6.2 Fluxo

1. Acesse **Estoque > Materiais** (`/stock/materials`)
2. Verifique se o formulário “Novo material” aparece apenas para quem pode
3. Cadastre um material
4. Valide se aparece na lista

---

## 7) Testes de Anamnese

### 7.1 Templates

1. Criar template
2. Adicionar campos pelo builder
3. Salvar
4. Editar template e validar que os campos carregam

### 7.2 Respostas

1. Abrir um atendimento/paciente (dependendo do fluxo)
2. Preencher anamnese
3. Salvar
4. Conferir persistência e reabertura

---

## 8) Testes de Reagendamento

1. Criar um agendamento
2. Abrir **Reagendar**
3. Trocar data/horário
4. Validar que:
   - horários disponíveis respeitam regras e bloqueios
   - não permite conflito

---

## 9) Testes de Bloqueios de Agenda (se usado)

1. Criar bloqueio global (profissional NULL) para um intervalo
2. Verificar que os horários somem do select
3. Criar bloqueio por profissional
4. Verificar que apenas aquele profissional perde horários

---

## 10) Testes por perfil (matriz rápida)

Use esta matriz como “smoke test” por perfil:

### 10.1 Owner/Admin

- Agenda: criar/editar/cancelar/finalizar
- Regras de agenda: criar regras para profissionais
- Clínica: alterar dados e horários
- Estoque: cadastrar materiais

### 10.2 Recepção

- Agenda: criar e gerenciar agendamentos
- (Opcional) sem acesso a configurações administrativas

### 10.3 Profissional

- Deve ver **apenas** a própria agenda
- Deve conseguir mudar status do próprio atendimento conforme regra
- Não deve conseguir alterar agenda de outros profissionais

### 10.4 Financeiro

- Acesso a módulos financeiros (se habilitado)
- Sem acesso a configurações clínicas/agenda avançada (depende da política)

---

## 11) Sugestão de “Roteiro de Teste Completo” (ordem recomendada)

1. Criar clínica e usuários
2. Cadastrar profissional
3. Cadastrar serviço (30 min)
4. Cadastrar horário da clínica
5. Cadastrar regra do profissional
6. Testar criação de agendamento
7. Testar mudança de status (confirmar → atender → concluir)
8. Testar no-show e cancelamento
9. Testar semana e mês
10. Testar estoque com dois usuários (com e sem permissão)
11. Testar anamnese (template + resposta)
12. Testar reagendamento

---

## 12) Como reportar bug (para corrigir rápido)

Quando algo falhar, envie:

- URL completa
- Perfil do usuário (role)
- Passo a passo para reproduzir
- Print + console/network (se for front)
- Logs do servidor (se houver)
- ID do registro (ex.: `appointment_id`)

---

## Status deste documento

- Checklist criado para cobrir permissões e visualizações principais.
