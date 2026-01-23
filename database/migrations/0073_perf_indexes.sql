-- Migration: 0073_perf_indexes
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Patients: busca/listagem por clínica
CREATE INDEX idx_patients_clinic_deleted_name ON patients (clinic_id, deleted_at, name);
CREATE INDEX idx_patients_clinic_deleted_email ON patients (clinic_id, deleted_at, email);
CREATE INDEX idx_patients_clinic_deleted_phone ON patients (clinic_id, deleted_at, phone);

-- Appointments: listagens por clínica e data
CREATE INDEX idx_appointments_clinic_deleted_start ON appointments (clinic_id, deleted_at, start_at);
CREATE INDEX idx_appointments_clinic_deleted_status_start ON appointments (clinic_id, deleted_at, status, start_at);

-- Sales: listagem por clínica e ordenação por id
CREATE INDEX idx_sales_clinic_deleted_id ON sales (clinic_id, deleted_at, id);
CREATE INDEX idx_sales_clinic_deleted_status_created ON sales (clinic_id, deleted_at, status, created_at);

-- Payments: listagem por venda e por clínica
CREATE INDEX idx_payments_clinic_sale_deleted ON payments (clinic_id, sale_id, deleted_at);
CREATE INDEX idx_payments_clinic_status_deleted ON payments (clinic_id, status, deleted_at);

-- Audit logs: filtros por clínica, ação e período
CREATE INDEX idx_audit_logs_clinic_deleted_id ON audit_logs (clinic_id, deleted_at, id);
CREATE INDEX idx_audit_logs_clinic_action_created ON audit_logs (clinic_id, action, created_at);
