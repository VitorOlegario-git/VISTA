**Workspace Audit — KPI_2.0**

**Scope**: análise estrutural, segurança e organização do workspace com foco em fluxos de inventário (BackEnd/Inventario) e riscos cross-cutting.

**Resumo Executivo**
- **Estado geral**: Código PHP monolítico com mistura de rotas procedurais e controllers, muitas dependências diretas por require/include e padrões inconsistentes de tratamento de erros e validação.
- **Impacto crítico**: endpoints de diagnóstico/ambiente expostos e resposta de erro com mensagens de exceção retornadas ao cliente (risco de leak de segredos/arquitetura).
- **Mudança recente**: consolidação do `ConsolidacaoApi.php` e desativação do `compare_armario` em `InventarioApi.php` (Phase 1) — sintaxe validada.

**Findings (prioritizados)**

**Critical (Ação imediata)**
- **Rota de exposição do ambiente**: [BackEnd/reveal_env.php](BackEnd/reveal_env.php) é registrada em [router.php](router.php) — remove/acle de produção ou proteja por autenticação.  
- **Detalhes de erro expostos ao cliente**: vários arquivos retornam `Exception->getMessage()` diretamente em JSON/HTML (ex.: [BackEnd/Qualidade/Qualidade.php](BackEnd/Qualidade/Qualidade.php), [BackEnd/Analise/salvar_dados_no_banco.php](BackEnd/Analise/salvar_dados_no_banco.php), [scripts/cleanup_expired_tokens.php](scripts/cleanup_expired_tokens.php)). Isso pode vazar informação sensível.  
- **display_errors em produção**: vários pontos do código ativam `display_errors` ou imprimem stacks; configure um único comportamento (desativar em produção, logar em error_log).  

**High (corrigir em curto prazo)**
- **Entradas e superglobais usadas sem consistência**: muitos arquivos usam `$_GET/$_POST/$_REQUEST` sem padronização; aplique validação/sanitização central (ver `BackEnd/helpers.php`).  
- **SQL concatenado / inconsistências de colunas**: a base já usa prepared statements frequentemente, mas há ocorrências de SQL construído por concatenação e inconsistência entre `remessa` e `codigo_remessa` (causa de resultados INEXISTENTE). Priorizar revisão de queries que constroem SQL dinamicamente.  
- **Sessões/CSRF parcialmente aplicados**: existe CSRF central (`BackEnd/helpers.php`) mas não garantido que todos os endpoints de escrita o verifiquem. Faça varredura por endpoints mutantes e aplique verificação.  

**Medium (melhorias arquiteturais)**
- **Rotas procedurais espalhadas**: router mapeia arquivos diretamente (ex.: [router.php](router.php)). Considere migrar para controllers padronizados e um pequeno middleware para autenticação/CSRF/logging.  
- **Testes e CI ausentes**: adicionar lint (php -l), static analysis (phpstan/psalm) e testes de integração para os endpoints críticos (inventário).  

**Low (refatoração / limpeza)**
- **declare(strict_types=1)**: uso inconsistente; adotar gradualmente em novos módulos.  
- **Duplicação de lógica**: havia duplicação no fluxo de conciliação (resolvido parcial — `ConsolidacaoApi.php` central). Fazer auditoria para eliminar pontos remanescentes.  

**Evidências e arquivos relevantes**
- Core DB wrapper: [BackEnd/Database.php](BackEnd/Database.php) — centralizar logs e mensagens não sensíveis.
- CSRF / helpers: [BackEnd/helpers.php](BackEnd/helpers.php) — ponto para reforçar validações.
- API base: [BackEnd/Core/ApiController.php](BackEnd/Core/ApiController.php)
- Inventário consolidado (alterado): [BackEnd/Inventario/ConsolidacaoApi.php](BackEnd/Inventario/ConsolidacaoApi.php)
- Inventário original (compare desativado): [BackEnd/Inventario/InventarioApi.php](BackEnd/Inventario/InventarioApi.php)
- Router: [router.php](router.php) / [router_public.php](router_public.php)
- Exemplos de exposição de erros: [BackEnd/Qualidade/Qualidade.php](BackEnd/Qualidade/Qualidade.php), [BackEnd/Analise/salvar_dados_no_banco.php](BackEnd/Analise/salvar_dados_no_banco.php)

**Prioritized Remediation Plan (concreto e sequencial)**
1. **Immediate lockdown (hours)**
   - Remove ou restrinja acesso a [BackEnd/reveal_env.php](BackEnd/reveal_env.php) no roteador; se necessário, proteja com autenticação de administrador.  
   - Substitua retornos diretos de `$e->getMessage()` por mensagens genéricas ao cliente e logue o detalhe em `error_log`.  
   - Auditoria rápida: varredura por `getMessage\(|echo\s*\$e->getMessage|print_r\(|var_dump\(|debug_backtrace` e corrigir retornos ao cliente.  
2. **Standardize error & debug config (1 day)**
   - Centralizar configuração de `display_errors` em arquivo de bootstrap/config (`config.php`), desativar em produção.  
   - Garantir `error_log` consistente e rotate (syslog/archivo).  
3. **Inventory contract & data hygiene (2–3 days)**
   - Padronizar identificador de remessa (`remessa` vs `codigo_remessa`) em código e DB; criar script de migração e adicionar índice/constraint único apropriado.  
   - Completar a migração do frontend para chamar `ConsolidacaoApi.php` (já criado).  
4. **Security: input validation & CSRF (2–4 days)**
   - Escanear endpoints mutantes e aplicar `verificarCSRF()` e validações centralizadas (use `BackEnd/helpers.php`).  
   - Revisar todas as queries dinâmicas para garantir prepared statements.  
5. **Quality & automation (ongoing)**
   - Integrar `php -l` + `phpstan`/`psalm` no CI; adicionar integração básica que executa smoke tests contra um sandbox DB.  

**Quick checklist / commands**
- Lint changed PHP files: `php -l BackEnd/Inventario/ConsolidacaoApi.php`  
- Find exposed getMessage/var_dump: `grep -R "getMessage\(|var_dump\(|print_r(" -n BackEnd | sed -n '1,200p'`  
- Search for reveal_env route: open [router.php](router.php) and remove mapping to `BackEnd/reveal_env.php` if present.  

**Appendix — Next recommended deliverables**
- Small PR: remove `reveal_env` route + replace client-visible exception messages (critical).  
- PR: enforce CSRF on mutating endpoints (grouped change).  
- DB migration plan draft (unique index on remessa identifier) and rollback script.  
- Add CI pipeline with php-lint and phpstan baseline.  

If you want, I can: 1) open the immediate PR (lockdown + error-message changes), 2) produce the DB migration script draft, or 3) run a targeted grep/scan and produce a line-by-line file list for every endpoint that echoes exceptions. Which should I do next?
