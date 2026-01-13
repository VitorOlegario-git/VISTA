<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Recebe filtros
$data_inicio = $_POST['data_inicial'] ?? "2000-01-01";
$data_fim    = $_POST['data_final']   ?? date("Y-m-d");
$operador    = $_POST['operador']     ?? null;

$sql = "
    SELECT operador, cnpj,
           ROUND(SUM(valor_orcamento), 2) AS ticket_medio
    FROM analise_parcial
    WHERE valor_orcamento IS NOT NULL
";

$params = [];
$types = "";

// Filtro por data
if (!empty($data_inicio) && !empty($data_fim)) {
    $sql .= " AND data_envio_orcamento BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
}

// Filtro por operador
if (!empty($operador)) {
    $sql .= " AND operador = ?";
    $params[] = $operador;
    $types .= "s";
}

$sql .= " GROUP BY operador, cnpj ORDER BY ticket_medio DESC";

// Prepara e executa
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Erro ao preparar a query: " . $conn->error]);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "operador" => $row["operador"],
        "cnpj" => $row["cnpj"],
        "ticket_medio" => floatval($row["ticket_medio"])
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["ticket_medio" => $dados]);
?>
