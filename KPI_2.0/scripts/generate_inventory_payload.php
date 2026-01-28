<?php
require_once __DIR__ . '/../BackEnd/config.php';
require_once __DIR__ . '/../BackEnd/Database.php';

try {
    $db = getDb();
} catch (Exception $e) {
    echo "DB_CONNECT_FAIL: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

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
            ORDER BY resumo_id DESC
            LIMIT 200";

try {
    $rows = $db->fetchAll($sql, []);
} catch (Exception $e) {
    echo "QUERY_FAIL: " . $e->getMessage() . PHP_EOL;
    exit(2);
}

$items = array_map(function($r){
    $status_raw = $r['status_real'] ?? '';
    $status_norm = '';
    if ($status_raw !== null && trim((string)$status_raw) !== '') {
        $status_norm = strtolower(preg_replace('/[^a-z0-9_]+/', '_', trim((string)$status_raw)));
        $status_norm = trim($status_norm, '_');
    }
    $arm_raw = $r['armario_id'] ?? null;
    $armario = ($arm_raw !== '' && $arm_raw !== null) ? (int)$arm_raw : null;

    return [
        'id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
        'resumo_id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
        'cnpj' => $r['cnpj'] ?? null,
        'razao_social' => $r['razao_social'] ?? '',
        'nota_fiscal' => $r['nota_fiscal'] ?? '',
        'quantidade' => isset($r['quantidade_real']) ? (int)$r['quantidade_real'] : 0,
        'status' => $status_norm,
        'locker' => $armario !== null ? (string)$armario : null,
        'armario_id' => $armario,
        'data_envio_expedicao' => $r['data_envio_expedicao'] ?? null,
        'codigo_rastreio_envio' => $r['codigo_rastreio_envio'] ?? null,
        'setor' => $r['setor'] ?? null
    ];
}, $rows ?: []);

$dest = __DIR__ . '/../data/inventario_server_payload.json';
if (!is_dir(dirname($dest))) @mkdir(dirname($dest), 0755, true);
file_put_contents($dest, json_encode($items, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);

echo "WROTE " . $dest . " (" . count($items) . " items)" . PHP_EOL;
?>