<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim = !empty($_POST['data_final']) ? $_POST['data_final'] : date("Y-m-d");

function getData($conn, $query, $inicio, $fim) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $inicio, $fim);
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
           DATE_FORMAT(data_inicio_reparo, '%Y-%u') AS periodo,
           SUM(quantidade_total) AS quantidade
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
    GROUP BY operador, periodo
    ORDER BY periodo, operador
";

$sqlMensal = "
    SELECT operador,
           DATE_FORMAT(data_inicio_reparo, '%Y-%m') AS periodo,
           SUM(quantidade_total) AS quantidade
    FROM reparo_parcial
    WHERE data_inicio_reparo BETWEEN ? AND ?
    GROUP BY operador, periodo
    ORDER BY periodo, operador
";

echo json_encode([
    "semanal" => getData($conn, $sqlSemanal, $data_inicio, $data_fim),
    "mensal" => getData($conn, $sqlMensal, $data_inicio, $data_fim)
]);

$conn->close();
?>
