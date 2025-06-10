<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Recebe as datas, se existirem
$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : null;
$data_fim    = !empty($_POST['data_final'])   ? $_POST['data_final']   : null;

$sql = "
    SELECT operador, cnpj,
           ROUND(SUM(valor_orcamento), 2) AS ticket_medio
    FROM analise_parcial
    WHERE valor_orcamento IS NOT NULL
";

// Filtros opcionais por data
$params = [];
$types = "";

if ($data_inicio && $data_fim) {
    $sql .= " AND data_registro BETWEEN ? AND ?";
    $params[] = $data_inicio;
    $params[] = $data_fim;
    $types .= "ss";
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

// Organiza os dados
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
