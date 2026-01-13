<?php
header("Content-Type: application/json");
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

$data_inicio = !empty($_POST['data_inicial']) ? $_POST['data_inicial'] : "2000-01-01";
$data_fim    = !empty($_POST['data_final'])   ? $_POST['data_final']   : date("Y-m-d");
$operador    = $_POST['operador'] ?? '';

$params = [$data_inicio, $data_fim];
$types  = "ss";

/* -------- 1) Agregado para o gráfico -------- */
$sqlAgg = "
  SELECT operador,
         ROUND(AVG(DATEDIFF(data_solicitacao_nf, data_inicio_reparo)), 2) AS tempo_medio
  FROM reparo_parcial
  WHERE DATE(data_inicio_reparo) BETWEEN ? AND ?
    AND data_solicitacao_nf IS NOT NULL
    AND operador IS NOT NULL
";
if (!empty($operador)) {
  $sqlAgg .= " AND operador = ?";
  $params[] = $operador;
  $types   .= "s";
}
$sqlAgg .= " GROUP BY operador ORDER BY tempo_medio DESC";

$stmtAgg = $conn->prepare($sqlAgg);
if (!$stmtAgg) { echo json_encode(["error" => "Erro ao preparar agregado"]); exit; }
$stmtAgg->bind_param($types, ...$params);
$stmtAgg->execute();
$resAgg = $stmtAgg->get_result();

$dados = [];
while ($r = $resAgg->fetch_assoc()) {
  $dados[] = [
    "operador"    => $r["operador"],
    "tempo_medio" => (float)$r["tempo_medio"]
  ];
}
$stmtAgg->close();

/* -------- 2) Detalhes para a tabela -------- */
/* Se sua tabela tiver `razao_social` e `nota_fiscal`, ótimo.
   Caso use outro nome/relacione por outra tabela, ajuste os campos abaixo. */
$sqlDet = "
  SELECT
    id,
    operador,
    razao_social,
    nota_fiscal,
    quantidade_parcial,
    DATE_FORMAT(data_inicio_reparo, '%Y-%m-%d')     AS data_inicio_reparo,
    DATE_FORMAT(data_solicitacao_nf, '%Y-%m-%d')    AS data_solicitacao_nf,
    DATEDIFF(data_solicitacao_nf, data_inicio_reparo) AS dias
  FROM reparo_parcial
  WHERE DATE(data_inicio_reparo) BETWEEN ? AND ?
    AND data_solicitacao_nf IS NOT NULL
    AND operador IS NOT NULL
";
if (!empty($operador)) {
  $sqlDet .= " AND operador = ?";
}
$sqlDet .= " ORDER BY data_solicitacao_nf DESC, id DESC LIMIT 300";

$stmtDet = $conn->prepare($sqlDet);
if (!$stmtDet) { echo json_encode(["error" => "Erro ao preparar detalhes"]); exit; }
$stmtDet->bind_param($types, ...$params);
$stmtDet->execute();
$resDet = $stmtDet->get_result();

$registros = [];
while ($r = $resDet->fetch_assoc()) {
  $registros[] = [
    "id"                   => (int)$r["id"],
    "operador"             => $r["operador"],
    "razao_social"         => $r["razao_social"],
    "nota_fiscal"          => $r["nota_fiscal"],
    "quantidade_parcial"   => (int)$r["quantidade_parcial"],
    "data_inicio_reparo"   => $r["data_inicio_reparo"],
    "data_solicitacao_nf"  => $r["data_solicitacao_nf"],
    "dias"                 => is_null($r["dias"]) ? null : (int)$r["dias"]
  ];
}
$stmtDet->close();

echo json_encode([
  "dados"     => $dados,     // para o gráfico (compatível com o que você já usa)
  "registros" => $registros  // para a tabela
], JSON_UNESCAPED_UNICODE);
$conn->close();
?>