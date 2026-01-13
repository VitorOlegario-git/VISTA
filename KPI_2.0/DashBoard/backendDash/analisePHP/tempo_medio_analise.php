<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

$dataInicial = $_POST['data_inicial'] ?? '';
$dataFinal   = $_POST['data_final'] ?? '';
$operador    = $_POST['operador'] ?? '';

try {
    $where  = [];
    $params = [];
    $types  = "";

    if ($dataInicial && $dataFinal) {
        // usa BETWEEN inclusivo com datas (presume 'YYYY-MM-DD')
        $where[] = "DATE(data_inicio_analise) BETWEEN ? AND ?";
        $params[] = $dataInicial;
        $params[] = $dataFinal;
        $types   .= "ss";
    }

    $where[] = "data_envio_orcamento IS NOT NULL";
    $where[] = "operador IS NOT NULL";

    if (!empty($operador)) {
        $where[] = "operador = ?";
        $params[] = $operador;
        $types   .= "s";
    }

    $whereClause = "WHERE " . implode(" AND ", $where);

    // 1) Agregado (para o gráfico)
    $sqlAgg = "
        SELECT operador,
               ROUND(AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)), 2) AS tempo_medio
        FROM analise_parcial
        $whereClause
        GROUP BY operador
        ORDER BY tempo_medio DESC
    ";
    $stmtAgg = $conn->prepare($sqlAgg);
    if (!$stmtAgg) throw new Exception("Erro ao preparar consulta agregada.");
    if (!empty($params)) $stmtAgg->bind_param($types, ...$params);
    $stmtAgg->execute();
    $resAgg = $stmtAgg->get_result();

    $dados = [];
    while ($row = $resAgg->fetch_assoc()) {
        $dados[] = [
            "operador"    => $row["operador"],
            "tempo_medio" => (float)$row["tempo_medio"]
        ];
    }
    $stmtAgg->close();

    // 2) Detalhes (para a tabela)
// 2) Detalhes (para a tabela)
$sqlDet = "
    SELECT 
        id,
        operador,
        razao_social,
        nota_fiscal,
        quantidade_parcial,
        DATE_FORMAT(data_inicio_analise, '%Y-%m-%d') AS data_inicio_analise,
        DATE_FORMAT(data_envio_orcamento, '%Y-%m-%d') AS data_envio_orcamento,
        DATEDIFF(data_envio_orcamento, data_inicio_analise) AS dias
    FROM analise_parcial
    $whereClause
    ORDER BY data_envio_orcamento DESC, id DESC
    LIMIT 300
";
$stmtDet = $conn->prepare($sqlDet);
if (!empty($params)) $stmtDet->bind_param($types, ...$params);
$stmtDet->execute();
$resDet = $stmtDet->get_result();

$registros = [];
while ($r = $resDet->fetch_assoc()) {
    $registros[] = [
        "id"                  => (int)$r["id"],
        "operador"            => $r["operador"],
        "razao_social"        => $r["razao_social"],
        "nota_fiscal"         => $r["nota_fiscal"],
        "quantidade_parcial"  => (int)$r["quantidade_parcial"],
        "data_inicio_analise" => $r["data_inicio_analise"],
        "data_envio_orcamento"=> $r["data_envio_orcamento"],
        "dias"                => is_null($r["dias"]) ? null : (int)$r["dias"]
    ];
}
$stmtDet->close();


    echo json_encode([
        "dados"     => $dados,      // agregado (compatível com o gráfico)
        "registros" => $registros   // detalhamento para a tabela
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(["erro" => $e->getMessage()]);
}
$conn->close();
?>