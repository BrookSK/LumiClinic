# LumiClinic — Tutorial Privado

Este documento é destinado aos donos do software (SaaS / Super Admin) e aos donos de clínicas.

## Acesso ao Tutorial Privado (link protegido)

- Rotas:
  - `/private/tutorial/platform`
  - `/private/tutorial/clinic`

Senha padrão: `lumiclinic`

## Dono do SaaS / Super Admin

### 1) Conceitos

- O sistema é multi-clínicas.
- O Super Admin pode operar sem contexto de clínica e também pode selecionar uma clínica.

### 2) Gestão de clínicas

- Acesse a listagem de clínicas no menu do sistema.
- Selecione a clínica quando necessário para operar recursos dependentes de contexto.

### 3) Billing / Assinaturas

No backoffice do sistema:

- Visualize assinatura por clínica
- Ajuste plano/status/gateway
- Execute sincronização com gateways (quando aplicável)

### 4) Observabilidade

- Dashboards:
  - `/dashboard/platform`
  - `/dashboard/system-health`
- Export:
  - `/reports/performance.csv`
- Jobs:
  - `metrics.daily` / `metrics.weekly` / `metrics.monthly`
  - `alerts.evaluate`
  - `observability.purge`

## Dono da Clínica

### 1) Login e permissões

- Usuários têm papéis e permissões.
- Perfis comuns: admin, receptionist, professional.

### 2) Agenda

- Criar agendamento
- Reagendar / cancelar
- Atualizar status (confirmado, em atendimento, concluído, no-show)

### 3) Pacientes

- Cadastro e consulta
- Manter dados atualizados

### 4) Financeiro

- Criar venda
- Inserir itens
- Lançar pagamentos e estornos

### 5) Relatórios e métricas

- Dashboard: `/dashboard/clinic`
- Export: `/reports/metrics.csv`
