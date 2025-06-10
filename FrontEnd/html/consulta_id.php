<?php
session_start();

// Use apenas:
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


if (!isset($_SESSION['username'])) {
    header("Location: tela_login.php");
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

header("Content-Type: application/json");

$nota_fiscal = $_POST['nf_entrada'] ?? '';
$cnpj = $_POST['cnpj'] ?? '';

if (empty($nota_fiscal) || empty($cnpj)) {
    echo json_encode(["error" => "CNPJ ou NF não informados"]);
    exit();
}

$sql = "SELECT id FROM analise_resumo WHERE nota_fiscal = ? AND cnpj = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro na preparação da consulta"]);
    exit();
}

$stmt->bind_param("ss", $nota_fiscal, $cnpj);
if (!$stmt->execute()) {
    echo json_encode(["error" => "Erro na execução da consulta"]);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->bind_result($id_apontamentos);
$stmt->fetch();

if (!empty($id_apontamentos)) {
    echo json_encode(["id" => $id_apontamentos]);
} else {
    echo json_encode(["id" => null]);
}

$stmt->close();
$conn->close();
?>
