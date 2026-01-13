<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function getFiltroDatas(&$tipos, &$params) {
    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim = str_replace("/", "-", $_POST['data_final']);

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $tipos = "ss";
            $params = [$data_inicio, $data_fim];
            return "WHERE DATE(data_recebimento) BETWEEN ? AND ? AND data_envio_analise IS NOT NULL";
        }
    }
    return "WHERE data_envio_analise IS NOT NULL";
}

$tipos = "";
$params = [];
$where = getFiltroDatas($tipos, $params);

// Consulta com JOINs da razão social
$sql = "
    SELECT 
        DATE(r.data_envio_analise) AS dia,
        AVG(DATEDIFF(r.data_envio_analise, r.data_recebimento)) AS tempo_medio,
        JSON_ARRAYAGG(
            JSON_OBJECT(
                'nf', r.nota_fiscal,
                'razao_social', r.razao_social,
                'dias_entre', DATEDIFF(r.data_envio_analise, r.data_recebimento)
            )
        ) AS detalhes
    FROM recebimentos r
    $where
    GROUP BY dia
    ORDER BY dia ASC
";


$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "dia" => $row['dia'],
        "tempo_medio" => round($row['tempo_medio'], 2),
        "detalhes" => json_decode($row['detalhes'], true)
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
