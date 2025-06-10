<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Recebe datas (se enviadas)
$dataInicial = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : null;
$dataFinal   = !empty($_POST['data_final'])   ? $_POST['data_final']   : null;

$where = [];
$params = [];
$types = "";

// Condicional de datas
if ($dataInicial && $dataFinal) {
    $where[] = "data_registro BETWEEN ? AND ?";
    $params[] = $dataInicial;
    $params[] = $dataFinal;
    $types .= "ss";
}

// Garante operador preenchido
$where[] = "operador IS NOT NULL";

$sql = "
    SELECT operador, COUNT(*) AS total
    FROM analise_parcial
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY operador ORDER BY total DESC";

// Prepara e executa
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["erro" => $conn->error]);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$dados = [];
$totalGeral = 0;

while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "operador" => $row["operador"],
        "total" => (int) $row["total"]
    ];
    $totalGeral += (int) $row["total"];
}

echo json_encode([
    "total_geral" => $totalGeral,
    "por_operador" => $dados
]);
?>
