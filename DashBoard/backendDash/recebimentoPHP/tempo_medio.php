<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Função para gerar a cláusula WHERE com ou sem datas
function getFiltroDatas(&$tipos, &$params) {
    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim = str_replace("/", "-", $_POST['data_final']);

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $tipos = "ss";
            $params = [$data_inicio, $data_fim];
            error_log("Filtro de datas aplicado: $data_inicio até $data_fim");
            return "WHERE DATE(data_recebimento) BETWEEN ? AND ? AND data_envio_analise IS NOT NULL";
        }
    }

    error_log("Sem filtro de datas — exibindo todos os dados");
    return "WHERE data_envio_analise IS NOT NULL";
}

$tipos = "";
$params = [];
$where = getFiltroDatas($tipos, $params);

// Consulta: tempo médio diário
$sql = "
    SELECT DATE(data_recebimento) AS dia, 
           AVG(DATEDIFF(data_envio_analise, data_recebimento)) AS tempo_medio
    FROM recebimentos
    $where
    GROUP BY dia
    ORDER BY dia ASC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "dia" => $row['dia'],
        "tempo_medio" => is_numeric($row['tempo_medio']) ? round($row['tempo_medio'], 2) : null

    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
