-- Revisao das entradas da base de conhecimento do suporte: avisos do verificador e marca de exportacao para markdown
ALTER TABLE support_kb_entries
    ADD COLUMN warnings TEXT NULL AFTER answer,
    ADD COLUMN exported_at DATETIME NULL AFTER reviewed_at;
