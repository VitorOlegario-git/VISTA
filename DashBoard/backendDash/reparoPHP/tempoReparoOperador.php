<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

$sql = "
    SELECT operador, 
           AVG(DATEDIFF(data_solicitacao_nf, data_inicio_reparo)) AS tempo_medio
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
      AND data_solicitacao_nf IS NOT NULL
      AND operador IS NOT NULL
    GROUP BY operador
    ORDER BY tempo_medio DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $row['tempo_medio'] = (int) round($row['tempo_medio']); // remove decimais
    $dados[] = $row;
}

if (empty($dados)) {
    $dados[] = ["operador" => "Sem dados", "tempo_medio" => 0];
}

echo json_encode($dados);
?>
