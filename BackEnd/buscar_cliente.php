<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
file_put_contents("debug_cnpj.txt", $cnpj);

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
        "cnpj_usado" => $cnpj
    ]);
} else {
    echo json_encode([
        "razao_social" => "Não encontrado",
        "cnpj_usado" => $cnpj
    ]);
}
?>
