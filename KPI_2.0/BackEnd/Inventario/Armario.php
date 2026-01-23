<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

if (!verificarSessao(false)) {
     jsonUnauthorized();
     exit; // Ensure we stop execution after unauthorized response
}
definirHeadersSeguranca();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método inválido', 405);
}

verificarCSRF();

$codigo = sanitizeInput($_POST['codigo'] ?? '');
$descricao = sanitizeInput($_POST['descricao'] ?? '');
$ativo = isset($_POST['ativo']) && $_POST['ativo'] ? 1 : 0;

if (empty($codigo)) {
    jsonError('Código do armário é obrigatório');
}

try {
    $db = getDb();
    $id = $db->insert(
        'INSERT INTO armarios (codigo, descricao, ativo) VALUES (?, ?, ?)',
        [$codigo, $descricao, $ativo],
        'ssi'
    );

    jsonSuccess(['id' => $id], 'Armário cadastrado com sucesso');
} catch (Exception $e) {
    // Log error for operators
    error_log($e->getMessage());
    $lower = strtolower($e->getMessage());
    if (strpos($lower, 'database connection failed') !== false || strpos($lower, 'erro de conexão') !== false || strpos($lower, 'connect') !== false) {
        jsonResponse(['success' => false, 'error' => 'Banco indisponível'], 503);
    }
    jsonError('Erro ao cadastrar armário');
}

?>
