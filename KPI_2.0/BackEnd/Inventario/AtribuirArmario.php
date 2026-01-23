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

try {
    $db = getDb();
    $db->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($remessas), '?'));
    $types = str_repeat('i', count($remessas));
    $params = array_map('intval', $remessas);

    // Atualiza campo armario_id na tabela resumo_geral
    $sql = "UPDATE resumo_geral SET armario_id = ? WHERE id IN ($placeholders)";
    $stmtParams = array_merge([$armario_id], $params);
    $stmtTypes = 'i' . $types;

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
