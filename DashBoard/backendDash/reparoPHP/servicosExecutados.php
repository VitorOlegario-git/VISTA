<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

// Filtros recebidos via POST
$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = $_POST['operador'] ?? '';

// SQL com JOIN usando a tabela reparo_parcial
$sql = "
    SELECT ag.servico, 
           COUNT(ag.servico) AS total_servicos
    FROM apontamentos_gerados ag
    INNER JOIN reparo_parcial rp ON ag.orcamento = rp.numero_orcamento
    WHERE rp.data_solicitacao_nf BETWEEN ? AND ?
      AND ag.servico IS NOT NULL AND ag.servico != ''
";

// Adiciona o filtro por operador se houver
$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND rp.operador = ?";
    $params[] = $operador;
    $types .= "s";
}

$sql .= "
    GROUP BY ag.servico
    ORDER BY total_servicos DESC
";

// Executa a consulta
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

// Retorno padrão se nenhum dado for encontrado
if (empty($dados)) {
    $dados[] = ["servico" => "Sem dados", "total_servicos" => 0];
}

echo json_encode($dados);
$conn->close();

?>
