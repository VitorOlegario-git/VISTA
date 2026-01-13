<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$data_inicio = $_POST['data_inicial'] ?? '2000-01-01';
$data_fim = $_POST['data_final'] ?? date('Y-m-d');
$modeloSelecionado = $_POST['modelo'] ?? null;

$data_inicio .= " 00:00:00";
$data_fim    .= " 23:59:59";

if (!empty($modeloSelecionado)) {
    $sql = "SELECT laudo, COUNT(*) AS total 
            FROM laudos_manutencao 
            WHERE modelo = ? AND data_cadastro BETWEEN ? AND ?
            GROUP BY laudo 
            ORDER BY total DESC 
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $modeloSelecionado, $data_inicio, $data_fim);
} else {
    $sql = "SELECT modelo, laudo, COUNT(*) AS total
            FROM laudos_manutencao
            WHERE data_cadastro BETWEEN ? AND ?
            GROUP BY modelo, laudo
            ORDER BY modelo, total DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
}

$stmt->execute();
$result = $stmt->get_result();
$dados = [];

if (empty($modeloSelecionado)) {
    $laudosPorModelo = [];
    while ($row = $result->fetch_assoc()) {
        $modelo = $row['modelo'];
        if (!isset($laudosPorModelo[$modelo])) {
            $laudosPorModelo[$modelo] = [
                "modelo" => $row["modelo"],
                "laudo" => $row["laudo"],
                "total" => (int)$row["total"]
            ];
        }
    }
    $dados = array_values($laudosPorModelo);
} else {
    while ($row = $result->fetch_assoc()) {
        $dados[] = [
            "modelo" => $modeloSelecionado,
            "laudo" => $row["laudo"],
            "total" => (int)$row["total"]
        ];
    }
}
$stmt->close();

$modelosRes = $conn->query("SELECT DISTINCT modelo FROM laudos_manutencao WHERE data_cadastro BETWEEN '$data_inicio' AND '$data_fim'");
$modelos = [];
while ($row = $modelosRes->fetch_assoc()) {
    $modelos[] = $row['modelo'];
}

echo json_encode([
    "modelos" => $modelos,
    "modeloSelecionado" => $modeloSelecionado,
    "laudos" => $dados
]);
