<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Função para montar filtro de data (opcional)
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

// 1. Consulta total de recebimentos
$sql_total = "SELECT COUNT(id) AS total_recebimentos FROM recebimentos $where";
$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) $stmt_total->bind_param($tipos, ...$params);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();
$total_recebimentos = $row_total['total_recebimentos'] ?? 0;
$stmt_total->close();

// 2. Consulta total de reenvios
// 2. Consulta total de reenvios
$clausulaReenvio = empty($where) 
    ? "WHERE operacao_destino = 'reenvio'" 
    : "$where AND operacao_destino = 'reenvio'";

$sql_reenvios = "SELECT COUNT(id) AS total_reenvios FROM recebimentos $clausulaReenvio";
$stmt_reenvios = $conn->prepare($sql_reenvios);
if (!empty($params)) $stmt_reenvios->bind_param($tipos, ...$params);
$stmt_reenvios->execute();
$result_reenvios = $stmt_reenvios->get_result();
$row_reenvios = $result_reenvios->fetch_assoc();
$total_reenvios = $row_reenvios['total_reenvios'] ?? 0;
$stmt_reenvios->close();


// 3. Calcula a taxa de rejeição
$taxa_rejeicao = ($total_recebimentos > 0) ? ($total_reenvios / $total_recebimentos) * 100 : 0;

// 4. Retorna resposta JSON
echo json_encode([
    "total_recebimentos" => $total_recebimentos,
    "total_reenvios" => $total_reenvios,
    "taxa_rejeicao" => round($taxa_rejeicao, 2)
]);

$conn->close();
?>
