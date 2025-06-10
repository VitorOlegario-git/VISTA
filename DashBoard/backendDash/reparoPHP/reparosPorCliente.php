<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

$sql = "
    SELECT razao_social, 
           SUM(quantidade_total) AS total_reparos
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
      AND razao_social IS NOT NULL
    GROUP BY razao_social
    ORDER BY total_reparos DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

if (empty($dados)) {
    $dados[] = ["razao_social" => "Sem dados", "total_reparos" => 0];
}

echo json_encode($dados);
?>
