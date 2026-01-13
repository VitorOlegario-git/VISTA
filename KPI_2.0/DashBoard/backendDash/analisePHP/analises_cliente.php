<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function abreviarRazaoSocial($razao) {
    $nomes = explode(" ", trim($razao));
    $ligacoes = ["do", "da", "de", "dos", "das", "&", "&amp;","&amp;amp;", "e"];

    if (isset($nomes[1]) && in_array(strtolower($nomes[1]), $ligacoes)) {
        return implode(" ", array_slice($nomes, 0, 3));
    }
    return implode(" ", array_slice($nomes, 0, 2));
}


$data_inicio = $_POST['data_inicial'] ?? "2000-01-01";
$data_fim = $_POST['data_final'] ?? date("Y-m-d");
$operador = $_POST['operador'] ?? '';

try {
    $sql = "
        SELECT razao_social, SUM(quantidade_parcial) AS total
        FROM analise_parcial
        WHERE data_envio_orcamento BETWEEN ? AND ?
          AND quantidade_parcial IS NOT NULL
    ";

    $params = [$data_inicio, $data_fim];
    $types = "ss";

    if (!empty($operador)) {
        $sql .= " AND operador = ?";
        $params[] = $operador;
        $types .= "s";
    }

    $sql .= " GROUP BY razao_social ORDER BY total DESC LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [];
    while ($row = $result->fetch_assoc()) {
       $row['razao_social'] = abreviarRazaoSocial($row['razao_social']);
       $dados[] = $row;
   }


    echo json_encode($dados);
} catch (Exception $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
?>
