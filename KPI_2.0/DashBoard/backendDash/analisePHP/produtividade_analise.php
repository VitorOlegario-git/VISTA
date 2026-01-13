<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

$data_inicio = $_POST['data_inicial'] ?? "2000-01-01";
$data_fim    = $_POST['data_final']   ?? date("Y-m-d");
$operador    = $_POST['operador']     ?? "";

// Função ajustada para aceitar operador opcional
function getData($conn, $queryBase, $inicio, $fim, $operador = "") {
    $params = [$inicio, $fim];
    $types = "ss";

    if (!empty($operador)) {
        $queryBase .= " AND operador = ?";
        $params[] = $operador;
        $types .= "s";
    }

    $queryBase .= " GROUP BY operador, periodo ORDER BY periodo, operador";

    $stmt = $conn->prepare($queryBase);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = [
            "operador" => $row["operador"],
            "periodo" => $row["periodo"],
            "quantidade" => (int) $row["quantidade"]
        ];
    }
    $stmt->close();
    return $dados;
}

// Query base SEM agrupamento ainda
$sqlSemanalBase = "
    SELECT operador,
           DATE_FORMAT(data_envio_orcamento, '%Y-%u') AS periodo,
           SUM(quantidade_parcial) AS quantidade
    FROM analise_parcial
    WHERE data_envio_orcamento BETWEEN ? AND ?
";

$sqlMensalBase = "
    SELECT operador,
           DATE_FORMAT(data_envio_orcamento, '%Y-%m') AS periodo,
           SUM(quantidade_parcial) AS quantidade
    FROM analise_parcial
    WHERE data_envio_orcamento BETWEEN ? AND ?
";

echo json_encode([
    "semanal" => getData($conn, $sqlSemanalBase, $data_inicio, $data_fim, $operador),
    "mensal"  => getData($conn, $sqlMensalBase,  $data_inicio, $data_fim, $operador)
]);

$conn->close();
?>
