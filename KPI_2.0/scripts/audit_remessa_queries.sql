-- READ ONLY AUDIT — DO NOT WRITE
--
-- Purpose: forensic read-only queries to audit mapping
--          codigo_remessa -> resumo_id in `resumo_geral`.
--
-- Usage: run in staging/production by a DBA or with a read-only DB user.
-- WARNING: this file contains ONLY SELECT statements. Do NOT run as a user
-- with write privileges in an automated migration step.

/*
1.1 Mapeamento completo
 - Retorna: resumo_id, codigo_remessa, comprimento, indicador de whitespace nas bordas
*/
SELECT
  id AS resumo_id,
  codigo_remessa,
  CHAR_LENGTH(codigo_remessa) AS len,
  (codigo_remessa REGEXP '^[[:space:]]|[[:space:]]$') AS has_edge_whitespace
FROM resumo_geral
ORDER BY codigo_remessa, id;

/*
1.2 Duplicidades exatas
 - Grupa por codigo_remessa e mostra casos com mais de 1 id.
 - Essas linhas BLOQUEIAM a criação de UNIQUE sobre `codigo_remessa`.
*/
SELECT
  codigo_remessa,
  COUNT(*) AS qtd,
  GROUP_CONCAT(id ORDER BY id) AS resumo_ids
FROM resumo_geral
WHERE codigo_remessa IS NOT NULL AND codigo_remessa <> ''
GROUP BY codigo_remessa
HAVING COUNT(*) > 1
ORDER BY qtd DESC, codigo_remessa;

/*
1.3 Vazios / nulos / somente whitespace
 - Valores que precisam ser removidos/normalizados antes de migrações de integridade.
*/
SELECT
  id AS resumo_id,
  codigo_remessa
FROM resumo_geral
WHERE codigo_remessa IS NULL
   OR codigo_remessa = ''
   OR TRIM(codigo_remessa) = '';

/*
1.4 Colisões por normalização (case/whitespace)
 - Detecta colisões ocultas que quebrariam um UNIQUE futuro após TRIM+LOWER.
 - Retorna valores originais para inspeção e decisão de limpeza.
*/
SELECT
  LOWER(TRIM(codigo_remessa)) AS codigo_norm,
  COUNT(*) AS qtd,
  GROUP_CONCAT(id ORDER BY id) AS resumo_ids,
  GROUP_CONCAT(codigo_remessa ORDER BY id SEPARATOR ' | ') AS valores_originais
FROM resumo_geral
WHERE codigo_remessa IS NOT NULL AND TRIM(codigo_remessa) <> ''
GROUP BY LOWER(TRIM(codigo_remessa))
HAVING COUNT(*) > 1
ORDER BY qtd DESC, codigo_norm;

-- ===================================================================
-- Interpretação / instruções (leia antes de executar alterações)
-- ===================================================================
--  - Qualquer linha retornada em 1.2 (duplicidades exatas) impede a criação
--    segura de um índice UNIQUE sobre `codigo_remessa` até que as duplicidades
--    sejam resolvidas.
--
--  - Linhas em 1.3 (vazios/nulos/whitespace) devem ser limpas antes de qualquer
--    alteração de esquema ou criação de constraints; entradas vazias podem
--    colidir entre si e causar comportamento indesejado.
--
--  - Entradas listadas em 1.4 mostram colisões por normalização (ex.: "ABC" vs "abc" ou
--    entradas com espaços leading/trailing). Essas colisões também bloqueiam
--    UNIQUE definido sobre LOWER(TRIM(codigo_remessa)).
--
-- Recomendações rápidas após a execução dos SELECTs
--  - Se 1.2 ou 1.4 retornarem linhas, NÃO aplicar UNIQUE ainda. Gere ticket
--    de limpeza e liste os ids afetados para revisão manual.
--  - Se 1.3 retornar linhas, remova/normalize essas linhas (por exemplo, definir
--    valor explícito ou associar ao `resumo_id` correto) antes de qualquer alteração.
--  - Utilize os resultados para construir um script de limpeza com backup e
--    plano de rollback; preferir operação em janela de manutenção.

-- Como executar (exemplo com cliente mysql):
--  mysql -h <host> -u <user> -p <database> < scripts/audit_remessa_queries.sql

-- Saída esperada: quatro conjuntos de resultados correspondentes às seções
-- 1.1..1.4. Salve ou exporte para CSV conforme necessário.

-- Fim do arquivo — READ ONLY
