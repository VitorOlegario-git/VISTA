<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

$sql = "
    SELECT servico, 
           COUNT(servico) AS total_servicos
    FROM apontamentos_gerados
    WHERE data_cadastro BETWEEN ? AND ?
      AND servico IS NOT NULL AND servico != ''
    GROUP BY servico
    ORDER BY total_servicos DESC
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
    $dados[] = ["servico" => "Sem dados", "total_servicos" => 0];
}

echo json_encode($dados);
?>
