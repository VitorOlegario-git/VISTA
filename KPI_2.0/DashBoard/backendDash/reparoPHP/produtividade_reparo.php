<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");
$operador = $_POST['operador'] ?? '';

function getData($conn, $queryBase, $inicio, $fim, $operador) {
    $query = $queryBase;
    $params = [$inicio, $fim];
    $types = "ss";

    if (!empty($operador)) {
        $query .= " AND operador = ?";
        $params[] = $operador;
        $types .= "s";
    }

    $query .= " GROUP BY operador, periodo ORDER BY periodo, operador";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

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

$sqlSemanal = "
    SELECT operador,
           DATE_FORMAT(data_solicitacao_nf, '%Y-%u') AS periodo,
           SUM(quantidade_parcial) AS quantidade
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
";

$sqlMensal = "
    SELECT operador,
           DATE_FORMAT(data_solicitacao_nf, '%Y-%m') AS periodo,
           SUM(quantidade_parcial) AS quantidade
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
";

echo json_encode([
    "semanal" => getData($conn, $sqlSemanal, $data_inicio, $data_fim, $operador),
    "mensal" => getData($conn, $sqlMensal, $data_inicio, $data_fim, $operador)
]);

$conn->close();
?>
