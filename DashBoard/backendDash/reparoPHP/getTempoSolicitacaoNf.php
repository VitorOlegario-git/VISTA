<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

// Coleta os parâmetros do POST ou define padrão
$data_inicio = $_POST['data_inicial'] ?? "2000-01-01";
$data_fim = $_POST['data_final'] ?? date("Y-m-d");
$operador = $_POST['operador'] ?? '';

// Monta SQL com filtro de datas e operador (se existir)
$sql = "
    SELECT 
        operador, 
        ROUND(AVG(DATEDIFF(data_solicitacao_nf, data_inicio_reparo))) AS tempo_medio
    FROM 
        reparo_parcial
    WHERE 
        data_inicio_reparo BETWEEN ? AND ?
        AND data_solicitacao_nf IS NOT NULL
";

// Tipagem e parâmetros
$params = [$data_inicio, $data_fim];
$types = "ss";

if (!empty($operador)) {
    $sql .= " AND operador = ?";
    $types .= "s";
    $params[] = $operador;
}

$sql .= " GROUP BY operador ORDER BY operador";

// Prepara e executa
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro ao preparar statement"]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Coleta os dados
$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = $row;
}

// Retorno padrão se vazio
if (empty($dados)) {
    $dados[] = ["operador" => "Sem dados", "tempo_medio" => 0];
}

// Retorna JSON
echo json_encode($dados);
?>
