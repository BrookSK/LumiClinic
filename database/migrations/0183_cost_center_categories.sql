-- Categoria do centro de custo (agrupamento): personnel, administrative, financial, investment, operational
ALTER TABLE cost_centers
    ADD COLUMN category VARCHAR(32) NULL AFTER description,
    ADD KEY idx_cost_centers_category (clinic_id, category);

-- Preenchimento inicial por palavras-chave no nome (melhor esforco, editavel depois). Nomes ambiguos (ex.: Taxa, Aluguel de equipamento) ficam sem categoria
UPDATE cost_centers SET category = 'personnel'
 WHERE category IS NULL AND deleted_at IS NULL
   AND (name LIKE '%Salário%' OR name LIKE '%Salario%' OR name LIKE '%Férias%' OR name LIKE '%Ferias%'
        OR name LIKE '%13º%' OR name LIKE '%Benefício%' OR name LIKE '%Beneficio%' OR name LIKE '%FGTS%'
        OR name LIKE '%INSS%' OR name LIKE '%Pró-labore%' OR name LIKE '%Pro-labore%' OR name LIKE '%Comiss%');

UPDATE cost_centers SET category = 'administrative'
 WHERE category IS NULL AND deleted_at IS NULL
   AND (name LIKE '%Aluguel%' OR name LIKE '%Água%' OR name LIKE '%Agua%' OR name LIKE '%Energia%'
        OR name LIKE '%Internet%' OR name LIKE '%Telefone%' OR name LIKE '%Contab%' OR name LIKE '%Marketing%')
   AND name NOT LIKE '%Equipamento%';

UPDATE cost_centers SET category = 'financial'
 WHERE category IS NULL AND deleted_at IS NULL
   AND (name LIKE '%Juros%' OR name LIKE '%Tarifa%' OR name LIKE '%Empréstimo%' OR name LIKE '%Emprestimo%');

UPDATE cost_centers SET category = 'investment'
 WHERE category IS NULL AND deleted_at IS NULL
   AND (name LIKE '%Equipamento%' OR name LIKE '%Reforma%' OR name LIKE '%Investimento%');

UPDATE cost_centers SET category = 'operational'
 WHERE category IS NULL AND deleted_at IS NULL
   AND (name LIKE '%Material%' OR name LIKE '%Materiais%' OR name LIKE '%Insumo%' OR name LIKE '%Limpeza%');
