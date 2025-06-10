<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Função para montar a cláusula de data se necessário
function getFiltroDatas(&$tipos, &$params) {
    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim = str_replace("/", "-", $_POST['data_final']);

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $tipos = "ss";
            $params = [$data_inicio, $data_fim];
            error_log("Filtro de datas aplicado: $data_inicio até $data_fim");
            return "WHERE DATE(data_recebimento) BETWEEN ? AND ?";
        }
    }

    error_log("Sem filtro de datas — exibindo todos os dados");
    return "";
}

$tipos = "";
$params = [];
$where = getFiltroDatas($tipos, $params);

// Consulta SQL: tempo médio entre data_recebimento e data_envio_analise
$sql = "
    SELECT operacao_origem, operacao_destino, 
           AVG(DATEDIFF(data_envio_analise, data_recebimento)) AS tempo_medio
    FROM recebimentos
    $where
    GROUP BY operacao_origem, operacao_destino
    ORDER BY tempo_medio DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "operacao_origem" => $row['operacao_origem'],
        "operacao_destino" => $row['operacao_destino'],
        "tempo_medio" => round($row['tempo_medio'], 2)
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
