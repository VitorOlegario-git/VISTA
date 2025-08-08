<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

$dataInicial = $_POST['data_inicial'] ?? '';
$dataFinal   = $_POST['data_final'] ?? '';
$operador    = $_POST['operador'] ?? '';

try {
    $where = [];
    $params = [];
    $types = "";

    if ($dataInicial && $dataFinal) {
        $where[] = "data_inicio_analise BETWEEN ? AND ?";
        $params[] = $dataInicial;
        $params[] = $dataFinal;
        $types .= "ss";
    }

    $where[] = "data_envio_orcamento IS NOT NULL";
    $where[] = "operador IS NOT NULL";

    if (!empty($operador)) {
        $where[] = "operador = ?";
        $params[] = $operador;
        $types .= "s";
    }

    $whereClause = "WHERE " . implode(" AND ", $where);

    $query = "
        SELECT operador,
               ROUND(AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)), 2) AS tempo_medio
        FROM analise_parcial
        $whereClause
        GROUP BY operador
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Erro ao preparar consulta.");

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }

    echo json_encode($dados);
} catch (Exception $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
