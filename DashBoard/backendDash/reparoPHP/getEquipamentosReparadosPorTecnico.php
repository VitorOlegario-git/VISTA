<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

$operador = $_POST['operador'] ?? '';

$sql = "
    SELECT operador, SUM(quantidade_total) AS total_reparado
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
";

$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND operador = ?";
    $types .= "s";
    $params[] = $operador;
}

$sql .= " GROUP BY operador ORDER BY total_reparado DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

// Se estiver vazio, retorna um valor "placeholder"
if (empty($dados)) {
    $dados[] = ["operador" => "Sem dados", "total_reparado" => 0];
}

echo json_encode($dados);
?>
