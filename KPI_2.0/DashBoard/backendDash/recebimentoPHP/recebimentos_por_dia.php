<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Função para gerar cláusula de filtro por data, se necessário
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

// Consulta recebimentos por dia da semana
$sql = "
    SELECT DAYNAME(data_recebimento) AS dia_semana, COUNT(id) AS total_recebimentos
    FROM recebimentos
    $where
    GROUP BY dia_semana
    ORDER BY FIELD(dia_semana, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "dia_semana" => $row['dia_semana'],
        "total_recebimentos" => intval($row['total_recebimentos'])
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
