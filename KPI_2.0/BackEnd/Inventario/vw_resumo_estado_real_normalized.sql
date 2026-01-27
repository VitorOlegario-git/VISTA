-- vw_resumo_estado_real_normalized.sql
-- Normalized view for inventory UI
-- Source: wraps existing vw_resumo_estado_real and applies deterministic normalizations
-- IMPORTANT: This file only defines the desired normalized view. Do NOT apply
-- automatically in production without review and testing.

CREATE OR REPLACE VIEW vw_resumo_estado_real_normalized AS
SELECT
    -- Ensure numeric identifier
    COALESCE(CAST(v.resumo_id AS SIGNED), 0) AS resumo_id,

    -- Normalize text fields: empty strings -> NULL (for cnpj) or '' for others
    NULLIF(TRIM(v.cnpj), '') AS cnpj,
    COALESCE(TRIM(v.razao_social), '') AS razao_social,
    COALESCE(TRIM(v.nota_fiscal), '') AS nota_fiscal,

    -- quantidade_real: guarantee INT NOT NULL (fallback to 0)
    COALESCE(CAST(NULLIF(TRIM(COALESCE(v.quantidade_real,'')), '') AS SIGNED), 0) AS quantidade_real,

    -- status_real: deterministic mapping required by spec
    CASE
        WHEN UPPER(TRIM(COALESCE(v.status_real, ''))) = 'SEM_ATRIBUICAO' THEN 'AGUARDANDO_PG'
        WHEN UPPER(TRIM(COALESCE(v.status_real, ''))) = 'EXPEDIDO' THEN 'CONFIRMADO'
        WHEN TRIM(COALESCE(v.status_real, '')) = '' THEN 'AGUARDANDO_PG'
        ELSE UPPER(REPLACE(TRIM(v.status_real), ' ', '_'))
    END AS status_real,

    -- armario_id: keep NULL when missing/invalid; cast to SIGNED when valid (>0)
    CASE
        WHEN v.armario_id IS NULL THEN NULL
        WHEN TRIM(COALESCE(CAST(v.armario_id AS CHAR), '')) = '' THEN NULL
        WHEN CAST(TRIM(COALESCE(CAST(v.armario_id AS CHAR), '0')) AS SIGNED) <= 0 THEN NULL
        ELSE CAST(TRIM(COALESCE(CAST(v.armario_id AS CHAR), '0')) AS SIGNED)
    END AS armario_id,

    -- Locker: textual representation for UI compatibility (NULL when no armario_id)
    CASE
        WHEN v.armario_id IS NULL THEN NULL
        WHEN TRIM(COALESCE(CAST(v.armario_id AS CHAR), '')) = '' THEN NULL
        WHEN CAST(TRIM(COALESCE(CAST(v.armario_id AS CHAR), '0')) AS SIGNED) <= 0 THEN NULL
        ELSE CAST(CAST(TRIM(COALESCE(CAST(v.armario_id AS CHAR), '0')) AS SIGNED) AS CHAR)
    END AS locker,

    -- Preserve other fields, normalized to NULL when empty
    NULLIF(TRIM(COALESCE(v.data_envio_expedicao, '')), '') AS data_envio_expedicao,
    NULLIF(TRIM(COALESCE(v.codigo_rastreio_envio, '')), '') AS codigo_rastreio_envio,
    NULLIF(TRIM(COALESCE(v.setor, '')), '') AS setor

FROM vw_resumo_estado_real AS v;

-- End of view definition
