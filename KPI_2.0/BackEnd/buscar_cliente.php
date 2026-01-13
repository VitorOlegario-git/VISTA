<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once dirname(__DIR__) . '/BackEnd/conexao.php';

header('Content-Type: application/json');

$cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');

if (empty($cnpj)) {
    echo json_encode(["error" => "CNPJ não informado"]);
    exit();
}

$sql = "SELECT razaosocial FROM clientes 
        WHERE TRIM(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '')) = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $cnpj);
$stmt->execute();
$stmt->bind_result($razao_social);
$stmt->fetch();
$stmt->close();
$conn->close();

if (!empty($razao_social)) {
    echo json_encode([
        "razao_social" => $razao_social,
        "cnpj_usado" => $cnpj,
        "encontrado" => true
    ]);
} else {
    echo json_encode([
        "razao_social" => "Não encontrado",
        "cnpj_usado" => $cnpj,
        "encontrado" => false
    ]);
}
?>
