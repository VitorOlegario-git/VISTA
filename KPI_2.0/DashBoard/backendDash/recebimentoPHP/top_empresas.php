<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function abreviarRazaoSocial($razao) {
    $nomes = explode(" ", trim($razao));
    $ligacoes = ["do", "da", "de", "dos", "das", "&", "&amp;","&amp;amp;", "e"];

    if (isset($nomes[1]) && in_array(strtolower($nomes[1]), $ligacoes)) {
        return implode(" ", array_slice($nomes, 0, 3));
    }
    return implode(" ", array_slice($nomes, 0, 2));
}

function getFiltroDatas(&$tipos, &$params) {
    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim = str_replace("/", "-", $_POST['data_final']);
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $tipos = "ss";
            $params = [$data_inicio, $data_fim];
            return "WHERE DATE(data_recebimento) BETWEEN ? AND ?";
        }
    }
    return "";
}

$tipos = "";
$params = [];
$where = getFiltroDatas($tipos, $params);

$sql = "
    SELECT razao_social, SUM(quantidade) AS total_pecas
    FROM recebimentos
    $where
    GROUP BY razao_social
    ORDER BY total_pecas DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$dados = [];
while ($row = $result->fetch_assoc()) {
    $dados[] = [
        "empresa" => abreviarRazaoSocial($row['razao_social']),
        "total_pecas" => intval($row['total_pecas'])
    ];
}

echo json_encode(["dados" => $dados]);

$stmt->close();
$conn->close();
?>