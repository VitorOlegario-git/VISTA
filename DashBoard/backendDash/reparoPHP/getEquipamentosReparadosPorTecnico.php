<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

// Parâmetros com valores padrão
$data_inicio = $_POST['data_inicial'] ?? "2000-01-01";
$data_fim = $_POST['data_final'] ?? date("Y-m-d");
$operador = $_POST['operador'] ?? "";

// Query base
$sql = "
    SELECT operador, SUM(quantidade_parcial) AS total_reparado
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
";

// Vinculação dinâmica de parâmetros
$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND operador = ?";
    $types .= "s";
    $params[] = $operador;
}

$sql .= " GROUP BY operador ORDER BY total_reparado DESC";

// Prepara e executa
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro na preparação da consulta"]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Coleta dados
$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

// Fallback amigável
if (empty($dados)) {
    $dados[] = ["operador" => "Sem dados", "total_reparado" => 0];
}

// Resposta JSON
echo json_encode($dados);
?>
