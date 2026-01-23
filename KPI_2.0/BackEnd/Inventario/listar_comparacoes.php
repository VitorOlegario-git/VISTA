<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

// Admin listing for inventario_comparacoes
// Supports JSON output (default) and CSV export when ?export=csv

if (!verificarSessao()) {
    // verificarSessao will redirect when not API; for JSON response, return unauthorized
    jsonUnauthorized();
}

definirHeadersSeguranca();

$db = getDb();

$ciclo_id = intval($_GET['ciclo_id'] ?? 0);
$armario = sanitizeInput($_GET['armario'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$export = (isset($_GET['export']) && $_GET['export'] === 'csv');

// Date range filters (optional). Expect YYYY-MM-DD.
$data_inicio = trim($_GET['data_inicio'] ?? '');
$data_fim = trim($_GET['data_fim'] ?? '');


$params = [];
$types = '';

$where = [];
if ($ciclo_id > 0) { $where[] = 'ic.ciclo_id = ?'; $params[] = $ciclo_id; $types .= 'i'; }
if ($armario !== '') {
    // support armario code or id
    if (ctype_digit($armario)) { $where[] = 'ic.armario_id = ?'; $params[] = intval($armario); $types .= 'i'; }
    else { $where[] = 'a.codigo = ?'; $params[] = $armario; $types .= 's'; }
}
if ($status !== '') { $where[] = 'ic.status_inventario = ?'; $params[] = $status; $types .= 's'; }

// Apply date filters if valid. Use inclusive day bounds.
if ($data_inicio !== '') {
    // basic YYYY-MM-DD validation
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicio) && strtotime($data_inicio) !== false) {
        $where[] = 'ic.criado_em >= ?';
        $params[] = $data_inicio . ' 00:00:00';
        $types .= 's';
    }
}
if ($data_fim !== '') {
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim) && strtotime($data_fim) !== false) {
        $where[] = 'ic.criado_em <= ?';
        $params[] = $data_fim . ' 23:59:59';
        $types .= 's';
    }
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total matching rows (for pagination meta)
$countSql = "SELECT COUNT(*) AS total FROM inventario_comparacoes ic LEFT JOIN armarios a ON a.id = ic.armario_id $whereSql";
$countRow = $db->fetchOne($countSql, $params, $types);
$total = intval($countRow['total'] ?? 0);

// Pagination parameters
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = intval($_GET['per_page'] ?? 50);
if ($per_page <= 0) $per_page = 50;
$per_page = min($per_page, 500); // cap

$baseSql = "SELECT ic.id, ic.ciclo_id, ic.armario_id, a.codigo AS armario_codigo, ic.remessa, ic.status_inventario, ic.status_banco, ic.observacao, ic.criado_por, ic.criado_em, u.username AS criado_por_nome
        FROM inventario_comparacoes ic
        LEFT JOIN armarios a ON a.id = ic.armario_id
        LEFT JOIN usuarios u ON u.id = ic.criado_por
        $whereSql
        ORDER BY a.codigo ASC, ic.status_inventario ASC, ic.criado_em DESC";

if ($export) {
    // CSV export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inventario_comparacoes.csv"');
    $out = fopen('php://output', 'w');
    // header
    fputcsv($out, ['id','ciclo_id','armario_id','armario_codigo','remessa','status_inventario','status_banco','observacao','criado_por','criado_por_nome','criado_em']);
    $rows_all = $db->fetchAll($baseSql, $params, $types);
    foreach ($rows_all as $r) {
        fputcsv($out, [
            $r['id'] ?? '',
            $r['ciclo_id'] ?? '',
            $r['armario_id'] ?? '',
            $r['armario_codigo'] ?? '',
            $r['remessa'] ?? '',
            $r['status_inventario'] ?? '',
            $r['status_banco'] ?? '',
            $r['observacao'] ?? '',
            $r['criado_por'] ?? '',
            $r['criado_por_nome'] ?? '',
            $r['criado_em'] ?? ''
        ]);
    }
    fclose($out);
    exit();
}

// Apply LIMIT/OFFSET for paged results
$offset = ($page - 1) * $per_page;
$sqlPaged = $baseSql . " LIMIT ? OFFSET ?";
$paramsPaged = array_merge($params, [$per_page, $offset]);
$typesPaged = $types . 'ii';
$rows = $db->fetchAll($sqlPaged, $paramsPaged, $typesPaged);

// Return JSON with meta pagination
 $pages = $per_page > 0 ? (int)ceil($total / $per_page) : 1;
jsonSuccess(['data' => $rows, 'meta' => ['total' => $total, 'page' => $page, 'per_page' => $per_page, 'pages' => $pages]]);

?>
