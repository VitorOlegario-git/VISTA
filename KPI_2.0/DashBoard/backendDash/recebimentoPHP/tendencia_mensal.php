<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Função para definir cláusula WHERE com ou sem datas
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

// Consulta: total de recebimentos por mês
$sql = "
    SELECT DATE_FORMAT(data_recebimento, '%Y-%m') AS mes, 
           COUNT(id) AS total_recebimentos
    FROM recebimentos
    $where
    GROUP BY mes
    ORDER BY mes ASC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "mes" => $row['mes'],
        "total_recebimentos" => intval($row['total_recebimentos'])
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
