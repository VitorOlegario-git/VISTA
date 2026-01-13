<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Função auxiliar para montar a cláusula WHERE se datas forem fornecidas
function getFiltroDatas(&$tipos, &$params) {
    if (!empty($_POST['data_inicial']) && !empty($_POST['data_final'])) {
        $data_inicio = str_replace("/", "-", $_POST['data_inicial']);
        $data_fim    = str_replace("/", "-", $_POST['data_final']);

        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_inicio) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $data_fim)) {
            $tipos  = "ss";
            $params = [$data_inicio, $data_fim];
            return "WHERE DATE(data_recebimento) BETWEEN ? AND ?";
        }
    }
    return "";
}

$tipos = "";
$params = [];
$where = getFiltroDatas($tipos, $params);

// 1) Agregado por setor (para o gráfico)
$sqlAgg = "
    SELECT setor, SUM(quantidade) AS total_pecas
    FROM recebimentos
    $where
    GROUP BY setor
    ORDER BY total_pecas DESC
";

$stmtAgg = $conn->prepare($sqlAgg);
if (!empty($params)) $stmtAgg->bind_param($tipos, ...$params);
$stmtAgg->execute();
$resAgg = $stmtAgg->get_result();

$dados = [];
while ($row = $resAgg->fetch_assoc()) {
    $dados[] = [
        "setor"       => $row['setor'],
        "total_pecas" => (int)$row['total_pecas']
    ];
}
$stmtAgg->close();

// 2) Remessas detalhadas (para a tabela)
// Ajuste os campos conforme sua estrutura/necessidade
$sqlDet = "
    SELECT 
        id, 
        cnpj, 
        razao_social, 
        nota_fiscal, 
        setor,
        quantidade, 
        DATE_FORMAT(data_recebimento, '%Y-%m-%d') AS data_recebimento
    FROM recebimentos
    $where
    ORDER BY data_recebimento DESC, id DESC
    LIMIT 300
";

$stmtDet = $conn->prepare($sqlDet);
if (!empty($params)) $stmtDet->bind_param($tipos, ...$params);
$stmtDet->execute();
$resDet = $stmtDet->get_result();

$remessas = [];
while ($r = $resDet->fetch_assoc()) {
    $remessas[] = [
        "id"               => (int)$r['id'],
        "data_recebimento" => $r['data_recebimento'],
        "cnpj"             => $r['cnpj'],
        "razao_social"     => $r['razao_social'],
        "nota_fiscal"      => $r['nota_fiscal'],
        "setor"            => $r['setor'],
        "quantidade"       => (int)$r['quantidade']
    ];
}
$stmtDet->close();

echo json_encode([
    "dados"    => $dados,     // mantém compatibilidade
    "remessas" => $remessas   // nova seção para a tabela
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>