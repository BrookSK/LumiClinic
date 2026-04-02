-- Migration: 0151_plan_transcription_limit
-- Adiciona limite de transcrição (minutos/mês) ao limits_json dos planos.
-- O campo é armazenado dentro do JSON existente como "transcription_minutes".
-- Valor 0 = ilimitado.
-- Não precisa alterar a tabela, pois limits_json já é JSON.
-- Esta migration é apenas documentação.

-- Exemplo de uso no limits_json:
-- {"users":0,"patients":0,"storage_mb":0,"portal":true,"transcription_minutes":120}
