<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

header('Content-Type: application/json; charset=utf-8');

// API should not redirect to HTML login
if (!verificarSessao(false)) {
    jsonUnauthorized();
}
    
// Debug helper: return minimal diagnostic information when ?debug=1
if (isset($_GET['debug']) && ($_GET['debug'] === '1' || $_GET['debug'] === 'true')) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $hdrs = [];
    if (function_exists('getallheaders')) {
        $hdrs = getallheaders();
    }
    $sessInfo = [
        'status' => session_status(),
        'has_user' => isset($_SESSION['username']),
        'usuario_id' => $_SESSION['usuario_id'] ?? null,
        'last_activity' => $_SESSION['last_activity'] ?? null
    ];
    jsonResponse([
        'debug' => true,
        'method' => $_SERVER['REQUEST_METHOD'] ?? null,
        'headers' => $hdrs,
        'cookies' => $_COOKIE ?? [],
        'session' => $sessInfo,
        'query' => $_GET ?? []
    ]);
}

definirHeadersSeguranca();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $db = getDb();

    // File-backed store for manual remessas and locker assignments
    // NOTE: inventario_store.json não é mais fonte de verdade.
    // It is retained only for backward-compatibility and temporary/non-critical data
    // (e.g., UI-only lockers or manual drafts). Do NOT persist or rely on this
    // file for authoritative `status` or `quantidade` values.
    $storeFile = __DIR__ . '/inventario_store.json';
    $loadStore = function() use ($storeFile) {
        if (!file_exists($storeFile)) return ['lockers'=>[], 'manual'=>[], 'next_manual_id'=>-1, 'status_overrides'=>[]];
        $txt = file_get_contents($storeFile);
        $data = json_decode($txt, true);
        if (!is_array($data)) return ['lockers'=>[], 'manual'=>[], 'next_manual_id'=>-1, 'status_overrides'=>[]];
        return $data;
    };
    $saveStore = function($data) use ($storeFile) {
        $dir = dirname($storeFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        file_put_contents($storeFile, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
    };

    if ($action === 'assign_locker' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resumo_id = $_POST['resumo_id'] ?? null;
        $locker = isset($_POST['locker']) ? trim((string)$_POST['locker']) : null;
        if ($resumo_id === null) jsonError('resumo_id é obrigatório', 400);
        $store = $loadStore();
        // locker may be empty to clear
        // We keep locker info in the store for UI convenience only. It MUST NOT
        // be used as authoritative armário assignment (resumo_geral.armario_id
        // remains the source of truth in the DB).
        if ($locker === '' || $locker === null) {
            unset($store['lockers'][(string)$resumo_id]);
        } else {
            $store['lockers'][(string)$resumo_id] = $locker;
        }
        $saveStore($store);
        jsonResponse(['success'=>true,'locker'=>$locker]);
    }

    if ($action === 'create_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create a cadastro record for inventory in `resumo_geral`.
        // IMPORTANT: ignore any `status` value submitted by the frontend.
        // Status is calculated dynamically by the VIEW vw_resumo_estado_real
        // and must not be set or overridden here.

        $razao = trim($_POST['razao_social'] ?? '');
        $nf = trim($_POST['nota_fiscal'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $qtd = (int)($_POST['quantidade'] ?? 1);
        $data_ultimo_registro = trim($_POST['data_ultimo_registro'] ?? '');
        $codigo_rastreio_entrada = trim($_POST['codigo_rastreio_entrada'] ?? '');
        $codigo_rastreio_envio = trim($_POST['codigo_rastreio_envio'] ?? '');
        $nota_fiscal_retorno = trim($_POST['nota_fiscal_retorno'] ?? '');
        $numero_orcamento = trim($_POST['numero_orcamento'] ?? '');
        $valor_orcamento = $_POST['valor_orcamento'] ?? null;
        $setor = trim($_POST['setor'] ?? '');
        $armario_id = isset($_POST['armario_id']) && $_POST['armario_id'] !== '' ? (int)$_POST['armario_id'] : null;

        if ($razao === '' || $nf === '') jsonError('razao_social e nota_fiscal são obrigatórios', 400);

        try {
            // Insert cadastro into resumo_geral. Do NOT set or update `status` here.
            $sql = "INSERT INTO resumo_geral
                        (cnpj, razao_social, nota_fiscal, quantidade, data_ultimo_registro,
                         codigo_rastreio_entrada, codigo_rastreio_envio, nota_fiscal_retorno,
                         numero_orcamento, valor_orcamento, setor, armario_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        razao_social = VALUES(razao_social),
                        quantidade = VALUES(quantidade),
                        data_ultimo_registro = VALUES(data_ultimo_registro),
                        codigo_rastreio_entrada = VALUES(codigo_rastreio_entrada),
                        codigo_rastreio_envio = VALUES(codigo_rastreio_envio),
                        nota_fiscal_retorno = VALUES(nota_fiscal_retorno),
                        numero_orcamento = VALUES(numero_orcamento),
                        valor_orcamento = VALUES(valor_orcamento),
                        setor = VALUES(setor),
                        armario_id = VALUES(armario_id)";

            $params = [
                $cnpj,
                $razao,
                $nf,
                $qtd,
                $data_ultimo_registro ?: null,
                $codigo_rastreio_entrada ?: null,
                $codigo_rastreio_envio ?: null,
                $nota_fiscal_retorno ?: null,
                $numero_orcamento ?: null,
                $valor_orcamento !== null ? (string)$valor_orcamento : null,
                $setor ?: null,
                $armario_id
            ];

            $db->insert($sql, $params, 'sssisssssssi');

            // Fetch the authoritative representation from the view so response
            // reflects the dynamically calculated status/quantidade.
            $row = $db->fetchOne(
                "SELECT resumo_id, cnpj, razao_social, nota_fiscal, quantidade_real, status_real, armario_id, data_envio_expedicao, codigo_rastreio_envio, setor
                 FROM vw_resumo_estado_real_normalized WHERE cnpj = ? AND nota_fiscal = ? LIMIT 1",
                [$cnpj, $nf], 'ss'
            );

            if ($row) {
                $item = [
                    'id' => isset($row['resumo_id']) ? (int)$row['resumo_id'] : 0,
                    'resumo_id' => isset($row['resumo_id']) ? (int)$row['resumo_id'] : 0,
                    'cnpj' => $row['cnpj'] ?? null,
                    'razao_social' => $row['razao_social'] ?? '',
                    'nota_fiscal' => $row['nota_fiscal'] ?? '',
                    'quantidade' => isset($row['quantidade_real']) ? (int)$row['quantidade_real'] : 0,
                    'status' => $row['status_real'] ?? '',
                    'locker' => isset($row['armario_id']) && $row['armario_id'] !== '' ? $row['armario_id'] : null,
                    'armario_id' => isset($row['armario_id']) && $row['armario_id'] !== '' ? $row['armario_id'] : null,
                    'data_envio_expedicao' => $row['data_envio_expedicao'] ?? null,
                    'codigo_rastreio_envio' => $row['codigo_rastreio_envio'] ?? null,
                    'setor' => $row['setor'] ?? null
                ];
            } else {
                // Fallback: return the submitted cadastral data (status unknown)
                $item = [
                    'id' => 0,
                    'resumo_id' => 0,
                    'cnpj' => $cnpj,
                    'razao_social' => $razao,
                    'nota_fiscal' => $nf,
                    'quantidade' => $qtd,
                    'status' => '',
                    'locker' => $armario_id !== null ? (string)$armario_id : null,
                    'armario_id' => $armario_id,
                    'data_envio_expedicao' => null,
                    'codigo_rastreio_envio' => null,
                    'setor' => $setor
                ];
            }

            jsonResponse(['success' => true, 'item' => $item]);

        } catch (Exception $e) {
            error_log('[InventarioApi:create_manual] ' . $e->getMessage());
            jsonError('Erro ao criar remessa');
        }
    }

    if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resumo_id = $_POST['resumo_id'] ?? null;
        if ($resumo_id === null) jsonError('resumo_id é obrigatório', 400);
        // Previously this endpoint wrote a status override into inventario_store.json
        // to hide items from the UI. That behavior is now disabled because the
        // canonical source is the DB view `vw_resumo_estado_real`.
        // We keep this endpoint for compatibility but it will NOT modify
        // `status_overrides` or affect `status_real`/`quantidade_real`.
        jsonResponse(['success'=>true,'note'=>'confirm no-op for store; use DB flows for authoritative changes']);
    }

    /*
     * Relatório técnico: análise do endpoint GET ?action=list
     *
     * 1) Campos retornados hoje (top-level dentro de json.items[*]):
     *    - id (int)            -> mapa de resumo_id
     *    - resumo_id (int)
     *    - cnpj (string|null)
     *    - razao_social (string)
     *    - nota_fiscal (string)
     *    - quantidade (int)    -> mapeado a partir de quantidade_real
     *    - status (string)     -> mapeado a partir de status_real
     *    - locker (mixed|null) -> provê valor de armario/locker (pode ser string ou null)
     *    - armario_id (mixed|null)
     *    - data_envio_expedicao (string|null)
     *    - codigo_rastreio_envio (string|null)
     *    - setor (string|null)
     *
     * 2) Possíveis inconsistências observadas (inputs/valores que podem ocorrer):
     *    - Nulls: vários campos (cnpj, data_envio_expedicao, codigo_rastreio_envio, setor,
     *      armario_id/locker) são explicitamente nulos quando não existem.
     *    - Tipos errados: o código trata quantidade_real como inteiro, mas a VIEW pode
     *      expor valores numéricos em formatos inesperados (string), portanto é convertido
     *      com (int). 'locker' e 'armario_id' são repassados sem casting consistente (podem
     *      aparecer como '' (string vazia), '0', 0 ou null). Isto causa checagens fracas
     *      no frontend (ex: `r.locker ? ... : '—'`).
     *    - Status inválidos / não padronizados: o campo status_real provém da VIEW;
     *      não há validação contra um enum fechado no backend. Pode conter strings vazias,
     *      valores arbitrários, ou variações de capitalização/acentuação que o frontend
     *      talvez não reconheça (ex: 'SEM_ATRIBUICAO' vs 'sem_atribuicao').
     *    - Falta de garantia de `items`: o código sempre devolve `['items' => $items]` no
     *      caminho normal, mesmo quando $items é array vazio; portanto o campo top-level
     *      `items` sempre existe quando a ação é 'list' (salvo erro/exception onde jsonError
     *      é chamado). Contudo, se ocorrer uma exceção antes do jsonResponse, a estrutura
     *      de erro será diferente (`['error'=>...]`). Frontend deve verificar isso.
     *    - armario_id pode vir nulo, string vazia, ou valor numérico; não há validação
     *      de intervalo (ex: 1..5) nem tipo garantido. O frontend atualmente testa
     *      `isset($r['armario_id']) && $r['armario_id'] !== ''` para decidir null.
     *
     * 3) `items` sempre presente?
     *    - Sim: no fluxo normal (sem exceção) o retorno será `jsonResponse(['items'=>$items])`.
     *      `$items` será um array (possivelmente vazio). Em caso de erro/exception o backend
     *      responde com `jsonError(...)` (estrutura `{ error: '...' }`) — portanto o cliente
     *      deve tratar ambos os formatos.
     *
     * 4) Status retornados pertencem a enum fechado?
     *    - Não há enum rígido no backend. `status_real` é obtido da view `vw_resumo_estado_real`.
     *      A correspondência entre status esperados (frontend) e valores reais deve ser
     *      documentada e/ou validada. Recomenda-se normalizar status (lowercase, map) ou
     *      implementar enum no banco/VIEW.
     *
     * 5) armario_id pode vir nulo ou inválido?
     *    - Sim: a VIEW pode expor NULL ou string vazia. Além disso, valores inválidos
     *      (ex: 0, negativo, números fora do intervalo real de armários) podem existir
     *      se a VIEW não validar. O backend hoje apenas transmite este valor sem validação.
     *
     * Observações adicionais / recomendações (não implementadas aqui):
     *    - Padronizar tipos na VIEW: garantir que quantidade_real seja NUMERIC/INT,
     *      armario_id seja NULL ou INT, e status_real pertença a um conjunto conhecido.
     *    - Fornecer `status_code` (enum) juntamente com `status_real` para facilitar
     *      mapeamentos no frontend e evitar dependência de strings livres.
     *    - Uniformizar locker/armario_id: devolver sempre `armario_id` como INT|null e
     *      `locker` como string|null (ou remover locker se duplicado).
     *    - Documentar o contrato JSON em README ou schema (ex: JSON Schema) e adicionar
     *      testes automatizados que validem tipos/valores.
     */

    if ($action === 'list') {
        // Inventário agora é derivado da VIEW vw_resumo_estado_real (fonte única de verdade)
        // Read-only: do not access resumo_geral, inventario_store.json, or perform any writes.

        // The view is expected to expose at least the following columns:
        // resumo_id, cnpj, razao_social, nota_fiscal, quantidade_real, status_real,
        // armario_id, data_envio_expedicao, codigo_rastreio_envio, setor

        $sql = "SELECT
                    resumo_id,
                    cnpj,
                    razao_social,
                    nota_fiscal,
                    quantidade_real,
                    status_real,
                    armario_id,
                    data_envio_expedicao,
                    codigo_rastreio_envio,
                    setor
                FROM vw_resumo_estado_real_normalized
                ORDER BY resumo_id DESC";

        $rows = $db->fetchAll($sql, []);

        // Normalize to the frontend expected shape (preserve keys used by inventory UI)
        $items = array_map(function($r){
            return [
                // frontend expects `id` as the key for resumo identifier
                'id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
                'resumo_id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
                'cnpj' => $r['cnpj'] ?? null,
                'razao_social' => $r['razao_social'] ?? '',
                'nota_fiscal' => $r['nota_fiscal'] ?? '',
                // keep numeric quantity as integer
                'quantidade' => isset($r['quantidade_real']) ? (int)$r['quantidade_real'] : 0,
                'status' => $r['status_real'] ?? '',
                // preserve locker/armario id for the frontend; may be null
                'locker' => isset($r['armario_id']) && $r['armario_id'] !== '' ? $r['armario_id'] : null,
                'armario_id' => isset($r['armario_id']) && $r['armario_id'] !== '' ? $r['armario_id'] : null,
                'data_envio_expedicao' => $r['data_envio_expedicao'] ?? null,
                'codigo_rastreio_envio' => $r['codigo_rastreio_envio'] ?? null,
                'setor' => $r['setor'] ?? null
            ];
        }, $rows ?: []);

        // Return top-level items (frontend expects json.items)
        jsonResponse(['items' => $items]);
    }

    jsonError('Ação inválida', 400);

} catch (Exception $e) {
    error_log($e->getMessage());
    jsonError('Erro interno');
}

?>
