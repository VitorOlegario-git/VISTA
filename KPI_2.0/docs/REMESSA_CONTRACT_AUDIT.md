# Inventário — Auditoria do Contrato de Remessa

Data: 2026-01-23

Escopo: varredura completa do workspace para identificar uso de `codigo_remessa`, `remessa` e `resumo_id`, classificar por contexto (SELECT/INSERT/WHERE/JOIN/payload/frontend/relatório), avaliar impacto e propor um plano de padronização sem alterar código.

Resumo executivo
- Situação atual: o código usa três formas para identificar remessas: (1) `resumo_id` (inteiro, referência a `resumo_geral.id`), (2) `codigo_remessa` (string/código na tabela `resumo_geral`) e (3) campos/vínculos `remessa` (campo texto usado em `inventario_conciliacoes` e payloads frontend). A conciliação foi parcialmente centralizada em `BackEnd/Inventario/ConsolidacaoApi.php` que já tenta checar `remessa OR codigo_remessa`.
- Risco principal: inconsistência entre usar `resumo_id` (ID numérico) vs. código string causa falsos "INEXISTENTE", divergência de status entre inventário e DB, e relatórios que mesclam colunas. Inventário e conciliação são fluxos sensíveis.

1) Varredura de uso — ocorrências encontradas (resumo)
- Total ocorrências encontradas nas buscas: 12 ocorrências de `codigo_remessa`, 42 ocorrências de `resumo_id`, 200+ ocorrências de `remessa` (amostra retornada). Abaixo uma tabela agrupada por identificador com exemplos.

Tabela de ocorrências (exemplos representativos)

- `codigo_remessa`
  - [BackEnd/Inventario/ConsolidacaoApi.php](BackEnd/Inventario/ConsolidacaoApi.php#L57) — SELECT: `SELECT status FROM resumo_geral WHERE remessa = ? OR codigo_remessa = ? LIMIT 1` (consulta usada para conciliação).  
  - [BackEnd/Inventario/InventarioApi.php](BackEnd/Inventario/InventarioApi.php#L41) — SELECT: `SELECT rg.id, rg.codigo_remessa, rg.cliente_nome, ...` (consulta de resumo).  
  - [inventario_status.php](inventario_status.php#L84) — SELECT listagem: `SELECT id, codigo_remessa, cliente_nome, ... FROM resumo_geral ...` (página de administração).  
  - [FrontEnd/html/atribuir_armario.php](FrontEnd/html/atribuir_armario.php#L11) — SELECT listagem para UI (frontend view).  

- `resumo_id` (ID que referencia `resumo_geral.id`) — evidências de uso para persistência de inventário e chaves:
  - [BackEnd/Inventario/InventarioApi.php](BackEnd/Inventario/InventarioApi.php#L65-L93) — Normaliza input: aceita `resumo_id` ou `remessa_id` do frontend; usa `resumo_id` em INSERT em `inventario_registros`, e em UPDATE `resumo_geral` para `ultima_confirmacao_inventario`. (INSERT / SELECT / UPDATE)  
  - [BackEnd/Inventario/migration_create_inventario_registros.sql](BackEnd/Inventario/migration_create_inventario_registros.sql#L7) — esquema: `resumo_id INT NULL` + UNIQUE KEY `uniq_ciclo_resumo (ciclo_id, resumo_id)`. (DB schema)  
  - [FrontEnd/html/inventario_atribuicoes.php](FrontEnd/html/inventario_atribuicoes.php#L13-L94) — UI aceita `resumo_id` em filtros e exibe `resumo_id` na tabela de atribuições. (frontend filter/display)  

- `remessa` (campo texto usado em conciliacões/relatórios/conciliacao table):
  - [BackEnd/Inventario/migration_create_inventario_conciliacoes.sql](BackEnd/Inventario/migration_create_inventario_conciliacoes.sql#L6) — Schema: `remessa VARCHAR(64) NOT NULL` (DB schema for conciliations). (persistência)  
  - [BackEnd/Inventario/listar_comparacoes.php](BackEnd/Inventario/listar_comparacoes.php#L69-L90) — SELECT/CSV export: `ic.remessa` e cabeçalho CSV com `remessa` (relatório/export). (relatório/export)  
  - [FrontEnd/html/inventario_conciliacao.php](FrontEnd/html/inventario_conciliacao.php#L21-L54) — frontend: textarea input of `remessas[]` and JS passes `remessa` strings to consolidated API and renders `r.remessa` in results table. (frontend payload/display)
  - [FrontEnd/html/inventario_relatorio_final.php](FrontEnd/html/inventario_relatorio_final.php#L83-L88) — frontend renders `r.remessa` in report table. (relatório/frontend)

2) Classificação por contexto (summary)
- Leitura do DB (SELECT): `BackEnd/Inventario/InventarioApi.php`, `inventario_status.php`, `BackEnd/Inventario/ConsolidacaoApi.php`, various KPIs and reports reference `codigo_remessa` or `remessa` in SELECTs.  
- Persistência (INSERT/UPDATE): `inventario_registros` uses `resumo_id` (INSERT), `inventario_conciliacoes` stores `remessa` (INSERT). `resumo_geral` contains `codigo_remessa`.  
- Filtros/BUSCA: frontend filters (e.g., `inventario_atribuicoes.php`) and admin pages accept `resumo_id` or selection by `codigo_remessa`.  
- Payload/API: `ConsolidacaoApi` accepts `remessas[]` (string codes) and `InventarioApi` accepts `resumo_id` OR `remessa_id` from frontend.  
- Frontend: many views use `remessa` strings in UI lists, and some display `codigo_remessa` or `remessa_id` interchangeably (see `inventario.php` where it displays `${r.codigo_remessa ?? r.remessa_id}`).  
- Relatórios/Exports: `listar_comparacoes.php` and `inventario_conciliacao_admin.php` use `remessa` column for CSV/exports.

3) Análise de impacto — onde e como a inconsistência causa problemas
- Falso INEXISTENTE: when frontend sends a remessa code but DB record only has `codigo_remessa` under a different field or the conciliation uses `resumo_id` (numeric) for lookup, code may miss matches. Example: older flows used `resumo_id` while consolidated flow checks `remessa` or `codigo_remessa` — but other endpoints may still query only `resumo_id`.  
- Divergência de status: if `inventario_registros` stores `resumo_id` and `inventario_conciliacoes` stores `remessa` string, status updates may not be synchronized if no mapping is guaranteed.  
- Relatórios incorretos: exports that rely on `remessa` column (text) may miss items that only reference `resumo_id` (or vice-versa) or double-count if mapping not deduplicated.  
- Fluxos sensíveis: conciliação (`ConsolidacaoApi`), inventário (confirm/notfound), atribuição de armário (`AtribuirArmario.php`), e relatórios finais são mais sensíveis. Operations that accept free-text remessa lists (bulk) are particularly fragile.

4) Recomendação de padronização (conceitual, sem código)
- Chave lógica oficial proposta: `resumo_id` (numeric surrogate referencing `resumo_geral.id`) as canonical internal identifier; expose a stable public identifier `remessa` (string) in APIs/UI only when necessary for human readability, but prefer `resumo_id` for all server-side mutations and joins. Rationale: numeric FK ensures referential integrity and is already present with indexes/uniqueness (migration files).  
- Mapping interno: keep `resumo_geral.codigo_remessa` (string) as the legacy code column, but treat it as an attribute — map `codigo_remessa` -> `resumo_id` at API boundary. Implement mapping lookup when receiving string remessa (lookup by `codigo_remessa` or `remessa` string) and then use `resumo_id` for all DB writes. (Recommendation only; do not code here.)  
- Where not to expose legacy key: do not require or document `codigo_remessa` as the primary API contract for mutating endpoints; accept it only for convenience on read-only endpoints or legacy imports, translating to `resumo_id` internally.  
- Minimal conceptual adjustments: ensure `ConsolidacaoApi` (already checks both) becomes the canonical lookup point for mapping string->`resumo_id`; update other endpoints conceptually to follow the same mapping pattern (accept remessa string OR resumo_id but convert to resumo_id asap).

5) Lista priorizada de ações (sem implementação) — cada item: arquivo(s) / tipo ajuste / risco / dependências

- Alta prioridade
  1. Verificar e padronizar lookup mapping no boundary APIs:
     - Arquivos: `BackEnd/Inventario/ConsolidacaoApi.php`, `BackEnd/Inventario/InventarioApi.php`, `BackEnd/Inventario/AtribuirArmario.php`, `BackEnd/Inventario/RelatorioFinalApi.php`  
     - Tipo de ajuste: padronizar comportamento de aceitar `remessa` string -> mapear para `resumo_id` e logar quando não houver correspondência; usar `resumo_id` para INSERT/UPDATE.
     - Risco: Alto (afeta conciliação e gravação).  
     - Dependências: `resumo_geral` índice em `codigo_remessa` / possível necessidade de criar índice único após limpeza de dados.

  2. Garantir contrato de leitura vs escrita para conciliacões:
     - Arquivos: `BackEnd/Inventario/listar_comparacoes.php`, `BackEnd/Inventario/migration_create_inventario_conciliacoes.sql` (planejar alteração de índice)  
     - Tipo de ajuste: garantir que `inventario_conciliacoes.remessa` seja sempre acompanhada de `resumo_id` (opcional coluna) ou registrar `resumo_id` nas conciliacões para ligar status ao `resumo_geral`.
     - Risco: Alto (relatórios e reconciliacões).  
     - Dependências: migrar/limpar dados históricos para evitar colisões de UNIQUE, revisar CSV exports.

- Médio prioridade
  3. Normalizar frontend payloads para preferir `resumo_id` quando disponível:
     - Arquivos: `FrontEnd/html/inventario.php`, `FrontEnd/html/inventario_conciliacao.php`, `FrontEnd/html/inventario_relatorio_final.php`, `FrontEnd/html/atribuir_armario.php`  
     - Tipo de ajuste: UI deve preferir enviar `resumo_id` for actions (confirm/notfound), fall back to sending `remessa` string only for manual input.
     - Risco: Médio  
     - Dependências: backend mapping behavior must be in place.

  4. Mapeamento e indexação no banco (investigar antes de aplicar):
     - Arquivos: `BackEnd/Inventario/*.sql` (migrations) and `kpi_2_0.sql` (dump)  
     - Tipo de ajuste: garantir índice em `resumo_geral.codigo_remessa` e adicionar `resumo_id` referenced in conciliacões; avaliar UNIQUE constraints after data dedup.
     - Risco: Médio (DB migrations risk data lock/locking).  
     - Dependências: backups, data-cleaning scripts.

- Baixa prioridade
  5. Limpeza e documentação de campos legados e contratos de API:
     - Arquivos: documentação e README (`docs/*`, `ARQUITETURA_FRONTEND_KPI.md`)  
     - Tipo: atualizar documentação para definir `resumo_id` como canonical, `codigo_remessa` as legacy attribute, and `remessa` as UI label.
     - Risco: Baixo

6) Plano de correção em fases (conceitual)
- Fase 0 — Preparação (analysis, backups): inventariar todas as remessas existentes, construir mapeamento `codigo_remessa -> resumo_id`, gerar report de colisões/duplicates. (1–2 dias)
- Fase 1 — Boundary mapping & read-only safety (zero-change writes): implement mapping at API edge so any endpoint receiving `remessa` string resolves to `resumo_id` for internal reads but does not change writes yet; add logs/metrics for unmatched codes. (2–3 dias)
- Fase 2 — Write-path standardization: change server-side write logic to always write `resumo_id` (and optionally store `remessa` text for human readability) for new conciliacões and inventario records; keep legacy columns as attributes. (3–5 days, requires testing)
- Fase 3 — DB hygiene & indexes: after verifying Phase 2, add/adjust indexes and optionally add UNIQUE constraints where safe; run data cleanup scripts and re-run reports. (planned maintenance window)
- Fase 4 — Frontend alignment & documentation: prefer sending `resumo_id` from UI where possible; update docs and deprecate `codigo_remessa` as public contract. (2–3 days)

Conclusão / recomendações imediatas (sem alterações de código)
- Short-term (today): declare `resumo_id` the canonical internal key in documentation and in ticketing; ensure `ConsolidacaoApi` remains the canonical mapping point.  
- Short-term (this week): run a data audit script to build `codigo_remessa -> resumo_id` mapping and list collisions (I can prepare that script draft if you want).  
- Medium-term: follow phased plan above; prioritize safe mapping at API boundary before switching write paths.

Se quiser, eu gero: (A) relatório CSV completo com todas as ocorrências (arquivo), (B) script de auditoria para gerar `codigo_remessa -> resumo_id` mapping e detectar colisões, ou (C) checklist de testes de aceitação para validar migração em staging. Diga qual prefere.

*** Fim do relatório
