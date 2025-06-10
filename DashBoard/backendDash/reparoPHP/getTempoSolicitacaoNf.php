<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = $_POST['operador'] ?? '';

$sql = "
    SELECT operador, 
           ROUND(AVG(DATEDIFF(data_solicitacao_nf, data_inicio_reparo))) AS tempo_medio
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
      AND data_solicitacao_nf IS NOT NULL
";

$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND operador = ?";
    $params[] = $operador;
    $types .= "s";
}

$sql .= " GROUP BY operador ORDER BY operador";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro ao preparar statement"]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

if (empty($dados)) {
    $dados[] = ["operador" => "Sem dados", "tempo_medio" => 0];
}

echo json_encode($dados);
?>