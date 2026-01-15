<?php
session_start();

// 1) Bloquear acesso se não for "Vitor Olegario"
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Vitor Olegario') {
    http_response_code(403);
    echo "Acesso restrito";
    exit();
}

// 2) Receber via GET
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 3) Validar status
$validStatus = [
    'envio_analise',
    'em_analise',
    'aguardando_pg',
    'em_reparo',
    'aguardando_NF_retorno',
    'qualidade'
];
if ($status === '' || !in_array($status, $validStatus, true)) {
    http_response_code(400);
    echo "Status inválido ou não informado.";
    exit();
}

// 4) Conexão com o banco
require_once __DIR__ . '/../config.php';
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao conectar ao banco de dados.";
    exit();
}

// 5) Montar SQL seguro
$sql = "SELECT razao_social, nota_fiscal, quantidade, status FROM resumo_geral WHERE status = :status";
$params = [':status' => $status];

if ($search !== '') {
    $sql .= " AND (razao_social LIKE :search OR nota_fiscal LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao consultar dados.";
    exit();
}
?>
