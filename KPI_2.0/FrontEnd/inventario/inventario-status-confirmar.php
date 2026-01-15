<?php
// inventario-status-confirmar.php
session_start();
require_once __DIR__ . '/../../BackEnd/helpers.php';
require_once __DIR__ . '/../../BackEnd/config.php';

header('Content-Type: application/json');

function confirmarStatusInventario(PDO $pdo, string $numero_orcamento): bool {
    $sql = "UPDATE resumo_geral SET confirmado = 1 WHERE numero_orcamento = ? AND confirmado = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero_orcamento]);
    return $stmt->rowCount() > 0;
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_orcamento'])) {
    $ok = confirmarStatusInventario($pdo, $_POST['numero_orcamento']);
    echo json_encode(['success' => $ok]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Requisição inválida']);
