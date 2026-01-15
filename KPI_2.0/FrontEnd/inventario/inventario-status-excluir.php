<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../BackEnd/config.php';

function buscarRemessaPorOrcamento(PDO $pdo, string $numero_orcamento): ?array {
    $sql = "SELECT id, status, confirmado FROM resumo_geral WHERE numero_orcamento = ? LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero_orcamento]);
    $remessa = $stmt->fetch(PDO::FETCH_ASSOC);
    return $remessa ?: null;
}

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_orcamento'])) {
    $remessa = buscarRemessaPorOrcamento($pdo, $_POST['numero_orcamento']);
    if ($remessa && $remessa['confirmado'] == 0) {
        $sql = "DELETE FROM resumo_geral WHERE numero_orcamento = ? AND confirmado = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['numero_orcamento']]);
        echo json_encode(['success' => $stmt->rowCount() > 0]);
    } else {
        echo json_encode(['error' => 'Remessa confirmada não pode ser excluída']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
