<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$dataInicial = $_POST['data_inicial'] ?? '';
$dataFinal = $_POST['data_final'] ?? '';

try {
    if ($dataInicial && $dataFinal) {
        $query = "
            SELECT razao_social, SUM(quantidade_parcial) AS total
            FROM analise_parcial
            WHERE data_inicio_analise BETWEEN ? AND ?
              AND quantidade_parcial IS NOT NULL
            GROUP BY razao_social
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $dataInicial, $dataFinal);
    } else {
        $query = "
            SELECT razao_social, SUM(quantidade_parcial) AS total
            FROM analise_parcial
            WHERE quantidade_parcial IS NOT NULL
            GROUP BY razao_social
            ORDER BY total DESC
        ";
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while ($row = $result->fetch_assoc()) {
        $dados[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($dados);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(["erro" => $e->getMessage()]);
}
?>