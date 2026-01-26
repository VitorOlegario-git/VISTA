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
        if ($locker === '' || $locker === null) {
            unset($store['lockers'][(string)$resumo_id]);
        } else {
            $store['lockers'][(string)$resumo_id] = $locker;
        }
        $saveStore($store);
        jsonResponse(['success'=>true,'locker'=>$locker]);
    }

    if ($action === 'create_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Accept extended fields for manual insertion
        $razao = trim($_POST['razao_social'] ?? '');
        $nf = trim($_POST['nota_fiscal'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');
        $qtd = (int)($_POST['quantidade'] ?? 1);
        $status = trim($_POST['status'] ?? 'aguardando_pg');
        $data_ultimo_registro = trim($_POST['data_ultimo_registro'] ?? '');
        $codigo_rastreio_entrada = trim($_POST['codigo_rastreio_entrada'] ?? '');
        $codigo_rastreio_envio = trim($_POST['codigo_rastreio_envio'] ?? '');
        $nota_fiscal_retorno = trim($_POST['nota_fiscal_retorno'] ?? '');
        $numero_orcamento = trim($_POST['numero_orcamento'] ?? '');
        $valor_orcamento = $_POST['valor_orcamento'] ?? null;
        $setor = trim($_POST['setor'] ?? '');
        $confirmado = isset($_POST['confirmado']) ? (int)$_POST['confirmado'] : 0;
        $armario_id = isset($_POST['armario_id']) && $_POST['armario_id'] !== '' ? (int)$_POST['armario_id'] : null;
        $ultima_confirmacao_inventario = trim($_POST['ultima_confirmacao_inventario'] ?? '');

        if ($razao === '' || $nf === '') jsonError('razao_social e nota_fiscal são obrigatórios', 400);
        $store = $loadStore();
        $id = $store['next_manual_id'] ?? -1;
        $item = [
            'id' => (int)$id,
            'cnpj' => $cnpj,
            'razao_social' => $razao,
            'nota_fiscal' => $nf,
            'quantidade' => $qtd,
            'status' => $status,
            'data_ultimo_registro' => $data_ultimo_registro ?: null,
            'codigo_rastreio_entrada' => $codigo_rastreio_entrada,
            'codigo_rastreio_envio' => $codigo_rastreio_envio,
            'nota_fiscal_retorno' => $nota_fiscal_retorno,
            'numero_orcamento' => $numero_orcamento,
            'valor_orcamento' => $valor_orcamento !== null ? (string)$valor_orcamento : null,
            'setor' => $setor,
            'confirmado' => $confirmado ? 1 : 0,
            'armario_id' => $armario_id,
            'locker' => $armario_id !== null ? (string)$armario_id : null,
            'ultima_confirmacao_inventario' => $ultima_confirmacao_inventario ?: null,
            'created_by' => getUsuarioId(),
            'created_at' => date('c')
        ];
        $store['manual'][] = $item;
        $store['next_manual_id'] = $id - 1;
        $saveStore($store);
        jsonResponse(['success'=>true,'item'=>$item]);
    }

    if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resumo_id = $_POST['resumo_id'] ?? null;
        if ($resumo_id === null) jsonError('resumo_id é obrigatório', 400);
        $store = $loadStore();
        // mark as confirmed in overrides
        $store['status_overrides'][(string)$resumo_id] = 'confirmado';
        $saveStore($store);
        jsonResponse(['success'=>true]);
    }

    if ($action === 'list') {
        // Return all remessas from resumo_geral with basic fields
        $sql = "SELECT id, razao_social, nota_fiscal, COALESCE(quantidade,1) AS quantidade, status FROM resumo_geral WHERE cnpj IS NOT NULL AND nota_fiscal IS NOT NULL AND TRIM(nota_fiscal) <> '' ORDER BY id DESC";
        $rows = $db->fetchAll($sql, []);

        // Normalize to expected fields: razao_social, nota_fiscal, quantidade, status
        $items = array_map(function($r){
            return [
                'id' => isset($r['id']) ? (int)$r['id'] : 0,
                'razao_social' => $r['razao_social'] ?? ($r['razao_social'] ?? ''),
                'nota_fiscal' => $r['nota_fiscal'] ?? '',
                'quantidade' => isset($r['quantidade']) ? (int)$r['quantidade'] : 1,
                'status' => $r['status'] ?? ''
            ];
        }, $rows);

        // Merge with store (manual entries + lockers + overrides)
        $store = $loadStore();
        $final = [];
        foreach ($items as $it) {
            $key = (string)$it['id'];
            // skip confirmed overrides
            if (isset($store['status_overrides'][$key]) && $store['status_overrides'][$key] === 'confirmado') continue;
            if (isset($store['lockers'][$key])) $it['locker'] = $store['lockers'][$key]; else $it['locker'] = null;
            $final[] = $it;
        }
        // append manual entries
        foreach ($store['manual'] as $m) {
            $key = (string)$m['id'];
            if (isset($store['status_overrides'][$key]) && $store['status_overrides'][$key] === 'confirmado') continue;
            $final[] = [
                'id' => $m['id'],
                'razao_social' => $m['razao_social'] ?? '',
                'nota_fiscal' => $m['nota_fiscal'] ?? '',
                'quantidade' => isset($m['quantidade']) ? (int)$m['quantidade'] : 1,
                'status' => $m['status'] ?? '',
                'locker' => $m['locker'] ?? null,
                'manual' => true
            ];
        }

        // Return top-level items (frontend expects json.items)
        jsonResponse(['items' => $final]);
    }

    jsonError('Ação inválida', 400);

} catch (Exception $e) {
    error_log($e->getMessage());
    jsonError('Erro interno');
}

?>
