<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . "/localhost/BackEnd/conexao.php";

// Função para aplicar filtro apenas se datas forem válidas
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

// SQL para contar operações por origem/destino
$sql = "
    SELECT operacao_origem, operacao_destino, COUNT(*) AS total_operacoes
    FROM recebimentos
    $where
    GROUP BY operacao_origem, operacao_destino
    ORDER BY total_operacoes DESC
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
        "total_operacoes" => intval($row['total_operacoes'])
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>
