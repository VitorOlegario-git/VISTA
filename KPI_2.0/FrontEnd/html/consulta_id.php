<?php
session_start();

// Use apenas:
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


if (!isset($_SESSION['username'])) {
    header("Location: tela_login.php");
    exit();
}

// Use caminho relativo ao arquivo para suportar diferentes server roots
require_once __DIR__ . '/../../BackEnd/conexao.php';

header("Content-Type: application/json");

$nota_fiscal = $_POST['nf_entrada'] ?? '';
$cnpj = $_POST['cnpj'] ?? '';

if (empty($nota_fiscal) || empty($cnpj)) {
    echo json_encode(["error" => "CNPJ ou NF não informados"]);
    exit();
}
// Normaliza CNPJ (remove pontos, barras e traços) para comparação robusta
$cnpj_digits = preg_replace('/\D/', '', $cnpj);

// Consulta comparando CNPJ sem formatação (usa REPLACE encadeado)
$sql = "SELECT id FROM analise_resumo WHERE nota_fiscal = ? AND REPLACE(REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', ''), ' ', '') = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro na preparação da consulta", "detail" => $conn->error]);
    exit();
}

$stmt->bind_param("ss", $nota_fiscal, $cnpj_digits);
if (!$stmt->execute()) {
    echo json_encode(["error" => "Erro na execução da consulta", "detail" => $stmt->error]);
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
