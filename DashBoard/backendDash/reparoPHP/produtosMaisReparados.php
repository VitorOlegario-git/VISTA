<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = $_POST['operador'] ?? '';

$sql = "
    SELECT ag.produto, 
           COUNT(ag.produto) AS total_reparos
    FROM apontamentos_gerados ag
    INNER JOIN reparo_parcial rp ON ag.orcamento = rp.numero_orcamento
    WHERE rp.data_solicitacao_nf BETWEEN ? AND ?
      AND ag.produto IS NOT NULL AND ag.produto != ''
";

$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND rp.operador = ?";
    $params[] = $operador;
    $types .= "s";
}

$sql .= "
    GROUP BY ag.produto
    ORDER BY total_reparos DESC LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro ao preparar o statement."]);
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
    $dados[] = ["produto" => "Sem dados", "total_reparos" => 0];
}

echo json_encode($dados);
$conn->close();

?>
