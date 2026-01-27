<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

if (!verificarSessao(false)) {
    jsonUnauthorized();
    exit;
}
definirHeadersSeguranca();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método inválido', 405);
}

verificarCSRF();

$armario_id = intval($_POST['armario_id'] ?? 0);
$remessas = $_POST['remessas'] ?? [];

if ($armario_id <= 0 || !is_array($remessas) || count($remessas) === 0) {
    jsonError('Dados inválidos');
}

// Ensure remessas are provided as resumo_id integers.
// This endpoint operates exclusively on `resumo_geral.id` values (resumo_id).
// Do NOT attempt to infer or modify any `status` here — armário é apenas
// um atributo do lote e deve ser atualizado sem alterar o estado.
// The canonical inventory status (incl. NO_ARMARIO) is derived by
// `vw_resumo_estado_real` and must not be computed or corrected here.
foreach ($remessas as $k => $v) {
    $remessas[$k] = intval($v);
    if ($remessas[$k] <= 0) jsonError('remessas deve conter resumo_id válidos');
}

try {
    $db = getDb();
    $db->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($remessas), '?'));
    $types = str_repeat('i', count($remessas));
    $params = array_map('intval', $remessas);

    // Determine eligible IDs: armario_id IS NULL AND nota_fiscal/cnpj present
    $eligibleSql = "SELECT id FROM resumo_geral WHERE id IN ($placeholders) AND (armario_id IS NULL OR armario_id = '') AND cnpj IS NOT NULL AND nota_fiscal IS NOT NULL AND TRIM(nota_fiscal) <> ''";
    $eligibleRows = $db->fetchAll($eligibleSql, $params, $types);
    $eligibleIds = array_map(function($r){ return (int)$r['id']; }, $eligibleRows ?: []);

    if (count($eligibleIds) === 0) {
        $db->commit();
        jsonSuccess([], 'Nenhuma remessa elegível para atribuição (filtro nota_fiscal aplicado)');
        return;
    }

    // Atualiza campo armario_id apenas para os elegíveis
    $placeholdersElig = implode(',', array_fill(0, count($eligibleIds), '?'));
    $sql = "UPDATE resumo_geral SET armario_id = ? WHERE id IN ($placeholdersElig)";
    $stmtParams = array_merge([$armario_id], $eligibleIds);
    $stmtTypes = 'i' . str_repeat('i', count($eligibleIds));

    // Usa execute helper
    $db->execute($sql, $stmtParams, $stmtTypes);

    $db->commit();
    jsonSuccess([], 'Armário atribuído com sucesso');

} catch (Exception $e) {
    if (isset($db)) $db->rollback();
    error_log($e->getMessage());
    $lower = strtolower($e->getMessage());
    if (strpos($lower, 'database connection failed') !== false || strpos($lower, 'erro de conexão') !== false || strpos($lower, 'connect') !== false) {
        jsonResponse(['success' => false, 'error' => 'Banco indisponível'], 503);
    }
    jsonError('Erro ao atribuir armário');
}

?>
