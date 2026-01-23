Consolidação Inventário / Conciliação — Fase 1

Data: 2026-01-23
Autor: alterações automáticas pelo time/assistente

Resumo das mudanças aplicadas:

1) Centralização de comparação
- Arquivo modificado: `BackEnd/Inventario/InventarioApi.php`
  - A ação `compare_armario` foi DESATIVADA neste endpoint e agora retorna HTTP 410 com instrução para usar `inventario/conciliacao-api`.

- Arquivo modificado: `BackEnd/Inventario/ConsolidacaoApi.php`
  - Implementada a ação única `compare_armario` (caso já existente):
    - Normalização de `remessas` com `trim` e remoção de entradas vazias.
    - SELECT que verifica `remessa` ou `codigo_remessa` para reduzir quebras por colunas inconsistentes.
    - Inserção/atualização em `inventario_conciliacoes` envolvida por `try/catch` e uso de `updated_em` no ON DUPLICATE KEY UPDATE.
  - Validações reforçadas: limite de 500 remessas, tamanho máximo dos códigos via `substr(...,64)`.

2) Testes e validação
- Adicionado `scripts/test_consolidacao.php` como runner de teste CLI (agora utiliza conexão real via `getDb()`).
- Adicionado `scripts/consolidacao_http_test.md` com comandos `curl` e PowerShell para testes HTTP.

3) Limpeza
- Removido arquivo de debug temporário `scripts/test_debug.txt`.

Impacto operacional:
- Frontend deve ser atualizado para apontar apenas para `inventario/conciliacao-api?action=compare_armario`.
- O endpoint `InventarioApi::compare_armario` responderá 410; ajustar clientes.

Próximos passos recomendados (Fase 1 → Fase 2):
- Atualizar frontends para usar o endpoint consolidado.
- Adicionar UNIQUE(ciclo_id, armario_id, remessa) em `inventario_conciliacoes` (após validar dados existentes).
- Planejar migração dos dados de `inventario_comparacoes` para `inventario_conciliacoes` (backup + script de migração).
- Criar testes de integração HTTP autenticados (login programático) e CI pipeline para rodá-los.

Arquivos alterados nesta mudança:
- BackEnd/Inventario/ConsolidacaoApi.php
- BackEnd/Inventario/InventarioApi.php
- scripts/test_consolidacao.php
- scripts/consolidacao_http_test.md
- docs/CONSOLIDATION_PHASE1_CHANGELOG.md

Comentários de segurança:
- As mudanças preservam uso de prepared statements; recomenda-se revisar logs para eventuais exceções de DB e transicionar para `updated_em`/`created_em` consistente.

