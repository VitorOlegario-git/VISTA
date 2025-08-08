<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = !empty($_POST['operador']) ? $_POST['operador'] : null;

// Monta a base da query
$sql = "
    SELECT razao_social, 
           SUM(quantidade_total) AS total_reparos
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
      AND razao_social IS NOT NULL 
";

// Adiciona filtro por operador se fornecido
if ($operador) {
    $sql .= " AND operador = ?";
}

// Finaliza a query
$sql .= " GROUP BY razao_social ORDER BY total_reparos DESC LIMIT 10";

// Prepara e executa
$stmt = $conn->prepare($sql);

// Bind com ou sem operador
if ($operador) {
    $stmt->bind_param("sss", $data_inicio, $data_fim, $operador);
} else {
    $stmt->bind_param("ss", $data_inicio, $data_fim);
}

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
