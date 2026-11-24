-- Migration: 0184_support_tickets
-- Chat de suporte, chamados, telefones dos desenvolvedores, entregas de notificação e base de conhecimento (resoluções).
-- IMPORTANT: Não edite este arquivo após criado. Crie uma nova migration .sql.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS support_chat_conversations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'open', -- open, closed, ticketed
    page_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_conv_clinic_user (clinic_id, user_id, created_at),
    KEY idx_support_conv_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    conversation_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(16) NOT NULL, -- user, assistant, system
    content TEXT NOT NULL,
    kb_chunk_ids VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_msg_conv (conversation_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_code VARCHAR(20) NOT NULL,
    clinic_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    title VARCHAR(190) NOT NULL,
    summary TEXT NULL,
    category VARCHAR(24) NOT NULL DEFAULT 'outro', -- bug, duvida, acesso, financeiro, integracao, outro
    severity VARCHAR(16) NOT NULL DEFAULT 'media', -- baixa, media, alta, critica
    status VARCHAR(24) NOT NULL DEFAULT 'novo', -- novo, em_analise, aguardando_usuario, resolvido, fechado
    context_json MEDIUMTEXT NULL,
    steps_tried TEXT NULL,
    expected TEXT NULL,
    actual TEXT NULL,
    page_path VARCHAR(255) NULL,
    assigned_to_name VARCHAR(120) NULL,
    resolution_text TEXT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_support_tickets_code (public_code),
    KEY idx_support_tickets_clinic_created (clinic_id, created_at),
    KEY idx_support_tickets_status (status, created_at),
    KEY idx_support_tickets_conv (conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    type VARCHAR(24) NOT NULL, -- created, status_change, note, notification, resolution
    body TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_events_ticket (ticket_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_dev_phones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_support_dev_phones_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_ticket_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    channel VARCHAR(16) NOT NULL, -- whatsapp, email
    destination VARCHAR(190) NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'skipped', -- sent, failed, skipped
    error VARCHAR(500) NULL,
    attempts INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_notif_ticket (ticket_id),
    KEY idx_support_notif_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_kb_entries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_ticket_id BIGINT UNSIGNED NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft', -- draft, approved, rejected
    created_by BIGINT UNSIGNED NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_support_kb_status (status),
    KEY idx_support_kb_ticket (source_ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
