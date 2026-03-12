-- Migration: 0144_fix_demo_user_passwords
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

START TRANSACTION;

-- Set demo password (ChangeMe123!) for demo clinic users (idempotent)
UPDATE users u
JOIN clinics c ON c.id = u.clinic_id AND c.tenant_key = 'demo'
   SET u.password_hash = '$2y$10$K8R7uO5K8xW7XHk7vM9p9e0cK8tZ5m8m7cKQd3Rr8mJQx4S1S2sUu',
       u.updated_at = NOW()
 WHERE u.deleted_at IS NULL
   AND u.email IN (
      'owner@demo.local',
      'admin@demo.local',
      'reception@demo.local',
      'finance@demo.local',
      'pro1@demo.local'
   );

-- Patient portal demo password (ChangeMe123!)
UPDATE patient_users pu
JOIN clinics c ON c.id = pu.clinic_id AND c.tenant_key = 'demo'
   SET pu.password_hash = '$2y$10$K8R7uO5K8xW7XHk7vM9p9e0cK8tZ5m8m7cKQd3Rr8mJQx4S1S2sUu',
       pu.updated_at = NOW()
 WHERE pu.deleted_at IS NULL
   AND pu.email IN (
      'mariana@paciente.local',
      'joao@paciente.local'
   );

COMMIT;
