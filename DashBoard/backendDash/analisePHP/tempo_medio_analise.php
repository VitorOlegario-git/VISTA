<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$dataInicial = $_POST['data_inicial'] ?? '';
$dataFinal = $_POST['data_final'] ?? '';

try {
    if ($dataInicial && $dataFinal) {
        $query = "
            SELECT operador,
                   ROUND(AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)), 2) AS tempo_medio
            FROM analise_parcial
            WHERE data_inicio_analise BETWEEN ? AND ?
              AND data_envio_orcamento IS NOT NULL
              AND operador IS NOT NULL
            GROUP BY operador
        ";
        $stmt = $conn->prepare($query);
        if (!$stmt) throw new Exception("Erro ao preparar consulta com datas.");
        $stmt->bind_param("ss", $dataInicial, $dataFinal);
    } else {
        $query = "
            SELECT operador,
                   ROUND(AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)), 2) AS tempo_medio
            FROM analise_parcial
            WHERE data_envio_orcamento IS NOT NULL
              AND operador IS NOT NULL
            GROUP BY operador
        ";
        $stmt = $conn->prepare($query);
        if (!$stmt) throw new Exception("Erro ao preparar consulta sem datas.");
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
