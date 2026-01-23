# Error / Debug Exposure Scan — KPI_2.0

Scope: varredura de todo o workspace por pontos que podem retornar mensagens de erro, exceções ou debug diretamente ao cliente.

Metodologia: busca por padrões comuns: `->getMessage`, `echo` contendo mensagens de exceção, `echo`/`print_r`/`var_dump`, `debug_backtrace`, `die()`/`exit()` com mensagens, e uso de `display_errors`.

Resumo rápido:
- Resultados totais (amostra): 200 ocorrências encontradas (diversos níveis: logging-only, outputs para cliente, configurações de debug).  

---

## Critical / High exposures (returnam texto de exceção ao cliente ou expõem ambiente)

- Arquivo: `BackEnd/Analise/salvar_dados_no_banco.php`  
  Linha: 86  
  Tipo de risco: exceção retornada ao cliente via JSON (`echo json_encode([... $e->getMessage()])`)  
  Severidade: Crítico  
  Sugestão: retornar uma mensagem genérica (ex.: "Erro interno") ao cliente e enviar o detalhe para `error_log()`; evitar incluir `$e->getMessage()` no payload público.

- Arquivo: `BackEnd/db_connection_test.php`  
  Linha: 17  
  Tipo de risco: exceção impressa ao cliente (`echo 'ERROR: ' . htmlspecialchars($e->getMessage(), ...)`)  
  Severidade: Crítico  
  Sugestão: retornar mensagem genérica e logar o detalhe; proteger endpoint de uso público.

- Arquivo: `BackEnd/Qualidade/Qualidade.php`  
  Linha: 26  
  Tipo de risco: exceção retornada ao cliente via JSON (`echo json_encode([... "Exception: " . $e->getMessage() ...])`)  
  Severidade: Crítico  
  Sugestão: remover `$e->getMessage()` do JSON público; logar internamente.

- Arquivo: `scripts/cleanup_expired_tokens.php`  
  Linha: 103  
  Tipo de risco: saída para STDOUT com `$e->getMessage()` (`echo "Erro: " . $e->getMessage() . "\n"`) — pode vazar segredos se script for chamado por pipelines expostos.  
  Severidade: Alto  
  Sugestão: escrever erro detalhado apenas em log de sistema; devolver somente códigos de erro ao caller automatizado.

- Arquivo: `BackEnd/endpoint-helpers.php`  
  Linha: 191  
  Tipo de risco: chamada `enviarErro(400, $e->getMessage())` — presumivelmente envia a mensagem ao cliente.  
  Severidade: Alto  
  Sugestão: padronizar `enviarErro()` para aceitar uma mensagem pública e registrar o detalhe internamente; auditá-lo para impedir vazamento direto de mensagens de exceção.

- Arquivo: `BackEnd/reveal_env.php`  
  Linha: 11  
  Tipo de risco: exposição de valores de ambiente/`display_errors` via rota pública  
  Severidade: Crítico  
  Sugestão: remover rota do roteador em produção ou proteger estritamente por autenticação/ACL.

- Arquivo: `BackEnd/Analise/Analise.php`, `BackEnd/Expedicao/*`, `BackEnd/Reparo/*`, `FrontEnd/cadastrar_cliente.php`, `scripts/test_consolidacao.php` etc.  
  Tipo de risco: `ini_set('display_errors', 1)` em código — ativa exibição de erros em runtime  
  Severidade: Crítico (em produção)  
  Sugestão: centralizar configuração de `display_errors` em `config.php`/bootstrap; garantir `display_errors=0` em ambiente de produção e log via `error_log`.

---

## High (funções que provavelmente retornam mensagens derivadas de exceções para clientes)

- Arquivo: `DashBoard/backendDash/*` vários arquivos (ex.: `grafico-tempo-operador.php`, `grafico-evolucao-reparos.php`, `grafico-volume-diario.php`)  
  Linhas: várias (ex.: sendError("...: " . $e->getMessage(), 500))  
  Tipo de risco: inclusão de `$e->getMessage()` nas respostas via `sendError()`  
  Severidade: Alto  
  Sugestão: alterar `sendError()`/wrapper para não incluir `$e->getMessage()` no payload público; logar detalhe.

- Arquivo: `BackEnd/Analise/salvar_dados_no_banco.php` (outros trechos)  
  Tipo de risco: `exit()` após `echo` com mensagem de exceção  
  Severidade: Alto  
  Sugestão: retornar código HTTP apropriado com payload público mínimo.

---

## Medium / Low (logging-only ou exits sem mensagem sensível)

Observação: muitos arquivos fazem `error_log($e->getMessage())` — isso é aceitável (logs) mas deve ser combinado com respostas genéricas ao cliente. Listei os caminhos detectados; esses devem ser revisados para garantir que não coexistam com outputs para o cliente.

Exemplos (logging-only) encontrados:
- `BackEnd/EmailService.php` (linhas 60,84) — `error_log("Erro ao enviar email: " . $e->getMessage());`  
- `BackEnd/Inventario/Ciclos.php` (linhas 40,69,157) — `error_log('[Inventario/Ciclos] ...: ' . $e->getMessage());`  
- `BackEnd/Inventario/RelatorioFinalApi.php` (linhas 98,111) — `error_log('[Inventario/RelatorioFinal] ...: ' . $e->getMessage());`  
- `PHPMailer/*` (várias linhas) — internal library logs/edebug; manter conforme lib.  

Exit/die calls without message (common pattern — often control flow, lower risk unless preceded by `echo $e->getMessage()`):
- Muitos arquivos listados use `exit();` ou `exit(1);` sem mensagem. Recomendo revisar apenas se aparecem imediatamente após `echo $e->getMessage()`.

---

## Full occurrences (amostra)
Lista (amostra dos primeiros 200 matches retornados pela varredura automatizada):

```
FrontEnd/CadastroUsuario.php:56 - error_log("Exception ao enviar email de cadastro: " . $e->getMessage());
FrontEnd/CadastroUsuario.php:62 - exit();
BackEnd/Analise/salvar_dados_no_banco.php:10 - ini_set('display_errors', 1);
BackEnd/Analise/salvar_dados_no_banco.php:19 - exit();
BackEnd/Analise/salvar_dados_no_banco.php:25 - exit();
BackEnd/Analise/salvar_dados_no_banco.php:41 - exit();
BackEnd/Analise/salvar_dados_no_banco.php:54 - exit();
BackEnd/Analise/salvar_dados_no_banco.php:86 - echo json_encode(["error" => "Erro na gravação: " . $e->getMessage()]);
BackEnd/Analise/salvar_dados_no_banco.php:92 - exit();
BackEnd/config.php:67 - ini_set('display_errors', 1);
BackEnd/config.php:71 - ini_set('display_errors', 0);
BackEnd/Consulta/consulta_status.php:2 - ini_set('display_errors', 1);
BackEnd/diagnostico_db.php:111 - $mysqliTest['error'] = $e->getMessage();
FrontEnd/tela_login.php:24 - exit();
BackEnd/db_connection_test.php:13 - error_log('[DB_TEST] Exception: ' . $e->getMessage());
BackEnd/db_connection_test.php:17 - echo 'ERROR: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
BackEnd/Database.php:241 - error_log(sprintf('[Database] GETDB ERROR host=%s db=%s user=%s error=%s', DB_HOST, DB_NAME, DB_USERNAME, $e->getMessage()));
BackEnd/EmailService.php:60 - error_log("Erro ao enviar email: " . $e->getMessage());
BackEnd/EmailService.php:84 - error_log("Erro ao enviar email: " . $e->getMessage());
BackEnd/Consulta/consulta_resumo_geral.php:2 - ini_set('display_errors', 1);
FrontEnd/confirmar_cadastro.php:62 - error_log("Erro ao remover token expirado: " . $e->getMessage());
FrontEnd/confirmar_cadastro.php:91 - error_log("Não foi possível gravar log de token expirado: " . $e2->getMessage());
FrontEnd/confirmar_cadastro.php:110 - error_log("Erro ao enviar notificação de token expirado: " . $e->getMessage());
FrontEnd/confirmar_cadastro.php:136 - error_log("Erro ao confirmar cadastro: " . $e->getMessage());
FrontEnd/confirmar_cadastro.php:144 - error_log("Erro na confirmação: " . $e->getMessage());
FrontEnd/html/analise.php:13 - exit();
FrontEnd/html/analise.php:18 - exit();
BackEnd/reveal_env.php:11 - 'display_errors' => ini_get('display_errors'),
BackEnd/Qualidade/Qualidade.php:17 - ini_set('display_errors', '0');
BackEnd/Qualidade/Qualidade.php:26 - echo json_encode(["success"=>false,"error"=>"Exception: ". $e->getMessage()]);
BackEnd/Recebimento/Recebimento.php:3 - ini_set('display_errors', 1);
BackEnd/Recebimento/Recebimento.php:126 - exit();
BackEnd/Reparo/salvar_dados_no_banco_2.php:3 - ini_set('display_errors', 1);
BackEnd/Reparo/salvar_dados_no_banco_2.php:23 - exit();
BackEnd/Reparo/salvar_dados_no_banco_2.php:29 - exit();
BackEnd/Reparo/salvar_dados_no_banco_2.php:55 - exit();
BackEnd/Reparo/salvar_dados_no_banco_2.php:66 - exit();
BackEnd/Reparo/salvar_dados_no_banco_2.php:77 - exit();
BackEnd/Reparo/Reparo.php:3 - ini_set('display_errors', 1);
BackEnd/Reparo/Reparo.php:19 - exit();
BackEnd/Reparo/Reparo.php:24 - exit();
BackEnd/Reparo/Reparo.php:63 - exit();
BackEnd/Reparo/Reparo.php:72 - exit();
BackEnd/Reparo/Reparo.php:91 - exit();
BackEnd/Reparo/Reparo.php:104 - exit();
BackEnd/Reparo/Reparo.php:128 - exit();
BackEnd/Reparo/Reparo.php:144 - exit();
BackEnd/Reparo/Reparo.php:154 - exit();
BackEnd/Reparo/Reparo.php:168 - exit();
BackEnd/Reparo/Reparo.php:186 - exit();
BackEnd/Reparo/Reparo.php:212 - exit();
BackEnd/Reparo/Reparo.php:221 - exit();
BackEnd/Reparo/Reparo.php:235 - exit();
PHPMailer/Exception.php:38 - return '<strong>' . htmlspecialchars($this->getMessage(), ENT_COMPAT | ENT_HTML401) . "</strong><br />\n";
inventario_status.php:78 - error_log('inventario_status error: ' . $e->getMessage());
inventario_status.php:79 - $error = $e->getMessage();
scripts/test_consolidacao.php:24 - ini_set('display_errors', '1');
scripts/cleanup_expired_tokens.php:34 - exit(1);
scripts/cleanup_expired_tokens.php:71 - error_log("Erro ao gravar log de token expirado: " . $e2->getMessage());
scripts/cleanup_expired_tokens.php:92 - error_log("Erro ao notificar email para token expirado: " . $e->getMessage());
scripts/cleanup_expired_tokens.php:96 - error_log("Erro ao remover token expirado ({$r['token']}): " . $e->getMessage());
scripts/cleanup_expired_tokens.php:101 - exit(0);
scripts/cleanup_expired_tokens.php:103 - echo "Erro: " . $e->getMessage() . "\n";
scripts/cleanup_expired_tokens.php:104 - exit(2);
BackEnd/Inventario/InventarioApi.php:179 - error_log($e->getMessage());
BackEnd/Inventario/InventarioApi.php:188 - error_log($e->getMessage());
BackEnd/Inventario/InventarioApi.php:191 - $msg = $e->getMessage();
BackEnd/Inventario/debug_login.php:6 - ini_set('display_errors', 1);
BackEnd/Inventario/Ciclos.php:40 - error_log('[Inventario/Ciclos] DB CONNECTION FAIL: ' . $e->getMessage());
BackEnd/Inventario/Ciclos.php:69 - error_log('[Inventario/Ciclos] GET ERROR: ' . $e->getMessage());
BackEnd/Inventario/Ciclos.php:157 - error_log('[Inventario/Ciclos] POST ERROR: ' . $e->getMessage());
PHPMailer/PHPMailer.php:1571 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:1726 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:1763 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:1764 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:2337 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:3366 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:3367 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:3523 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:3524 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:3862 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:3863 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:3935 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:3936 - $this->edebug($exc->getMessage());
PHPMailer/PHPMailer.php:3996 - $this->setError($exc->getMessage());
PHPMailer/PHPMailer.php:3997 - $this->edebug($exc->getMessage());
FrontEnd/html/expedicao.php:11 - exit();
FrontEnd/html/expedicao.php:15 - exit();
BackEnd/Inventario/AtribuirArmario.php:45 - error_log($e->getMessage());
BackEnd/Inventario/AtribuirArmario.php:46 - $lower = strtolower($e->getMessage());
BackEnd/Inventario/InventarioCron.php:17 - exit(1);
BackEnd/Inventario/InventarioCron.php:161 - $err = 'Erro InventarioCron: ' . $ex->getMessage();
BackEnd/Inventario/InventarioCron.php:164 - exit(1);
_OLD_FILES/analise_old.php:17 - exit();
_OLD_FILES/analise_old.php:23 - exit();
BackEnd/Inventario/Armario.php:36 - error_log($e->getMessage());
BackEnd/Inventario/Armario.php:37 - $lower = strtolower($e->getMessage());
FrontEnd/html/consulta_id.php:11 - exit();
FrontEnd/html/consulta_id.php:24 - exit();
FrontEnd/html/consulta_id.php:34 - exit();
FrontEnd/html/consulta_id.php:42 - exit();
BackEnd/helpers.php:41 - exit();
BackEnd/helpers.php:53 - exit();
BackEnd/helpers.php:168 - exit();
FrontEnd/html/cadastro_excel_entrada.php:16 - exit();
FrontEnd/html/cadastro_excel_entrada.php:22 - exit();
_OLD_FILES/DashRecebimento_old.php:17 - exit();
_OLD_FILES/DashRecebimento_old.php:23 - exit();
BackEnd/Inventario/RelatorioFinalApi.php:98 - error_log('[Inventario/RelatorioFinal] ERROR: ' . $e->getMessage());
BackEnd/Inventario/RelatorioFinalApi.php:111 - error_log('[Inventario/RelatorioFinal] CSV ERROR: ' . $e->getMessage());
BackEnd/Inventario/RelatorioFinalApi.php:123 - exit();
BackEnd/Inventario/listar_comparacoes.php:100 - exit();
FrontEnd/html/cadastrar_cliente.php:2 - ini_set('display_errors', 1);
BackEnd/endpoint-helpers.php:191 - enviarErro(400, $e->getMessage());
BackEnd/endpoint-helpers.php:869 - error_log("ERRO ao gravar log de KPI: " . $e->getMessage());
BackEnd/endpoint-helpers.php:999 - error_log("AVISO: Falha ao registrar auditoria de KPI: " . $e->getMessage());
BackEnd/buscar_cliente.php:2 - ini_set('display_errors', 1);
BackEnd/buscar_cliente.php:12 - exit();
inventario-status.php:11 - exit();
inventario-status.php:23 - exit();
inventario-status.php:50 - exit();
BackEnd/auth-middleware.php:15 - die(json_encode(['error' => 'Acesso direto não permitido']));
BackEnd/Analise/Analise.php:5 - ini_set('display_errors', 1);
BackEnd/Analise/Analise.php:59 - exit();
BackEnd/Analise/Analise.php:102 - exit();
BackEnd/Analise/Analise.php:113 - exit();
...
```

Observação: posso gerar export completo (arquivo CSV/Markdown com todas as ocorrências) ou segmentar por tipo (ex.: todas as linhas `ini_set('display_errors', 1)`), se preferir.

---

Próximo passo sugerido: diga qual ação prefere: (A) relatório completo exportado, (B) PR imediato para bloquear `reveal_env` + sanitize mensagens, ou (C) extração por categoria para revisão em lote.
