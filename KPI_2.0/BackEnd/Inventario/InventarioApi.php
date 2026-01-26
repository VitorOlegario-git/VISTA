<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

header('Content-Type: application/json; charset=utf-8');

// API should not redirect to HTML login
if (!verificarSessao(false)) {
    jsonUnauthorized();
}

definirHeadersSeguranca();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $db = getDb();

    if ($action === 'list') {
        // Return all remessas from resumo_geral with basic fields
        $sql = "SELECT id, cliente_nome, nota_fiscal, COALESCE(quantidade,1) AS quantidade, status FROM resumo_geral WHERE cnpj IS NOT NULL AND nota_fiscal IS NOT NULL AND TRIM(nota_fiscal) <> '' ORDER BY id DESC";
        $rows = $db->fetchAll($sql, []);

        // Normalize to expected fields: razao_social, nota_fiscal, quantidade, status
        $items = array_map(function($r){
            return [
                'id' => isset($r['id']) ? (int)$r['id'] : 0,
                'razao_social' => $r['cliente_nome'] ?? ($r['razao_social'] ?? ''),
                'nota_fiscal' => $r['nota_fiscal'] ?? '',
                'quantidade' => isset($r['quantidade']) ? (int)$r['quantidade'] : 1,
                'status' => $r['status'] ?? ''
            ];
        }, $rows);

        // Return top-level items (frontend expects json.items)
        jsonResponse(['items' => $items]);
    }

    jsonError('Ação inválida', 400);

} catch (Exception $e) {
    error_log($e->getMessage());
    jsonError('Erro interno');
}

?>
