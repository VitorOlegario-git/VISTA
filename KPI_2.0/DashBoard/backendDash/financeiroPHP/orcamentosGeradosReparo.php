<?php
// orcamentosGerados.php

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function norm_date($d, $fallback) {
  if (!$d) return $fallback;
  $t = strtotime($d);
  return $t ? date("Y-m-d", $t) : $fallback;
}

$dataIni = $_POST['data_inicial'] ?? '2000-01-01';
$dataFim = $_POST['data_final']   ?? date('Y-m-d');
$dataInicial = norm_date($dataIni, '2000-01-01') . " 00:00:00";
$dataFinal   = norm_date($dataFim, date('Y-m-d')) . " 23:59:59";

try {
  $sql = "
    SELECT razao_social, nota_fiscal, numero_orcamento, valor_orcamento
    FROM reparo_parcial
    WHERE data_solicitacao_nf BETWEEN ? AND ?
      AND numero_orcamento IS NOT NULL
      AND numero_orcamento <> ''
  ";
  $stmt = $conn->prepare($sql);
  if (!$stmt) throw new Exception("Erro na preparação da consulta: " . $conn->error);
  $stmt->bind_param("ss", $dataInicial, $dataFinal);
  $stmt->execute();
  $res = $stmt->get_result();

  $dados = [];
  while ($row = $res->fetch_assoc()) {
    $dados[] = [
      'razao_social'     => $row['razao_social'] ?? '-',
      'nota_fiscal'      => $row['nota_fiscal'] ?? '-',
      'numero_orcamento' => $row['numero_orcamento'] ?? '-',
      'valor_orcamento'  => number_format((float)$row['valor_orcamento'], 2, ',', '.')
    ];
  }

  echo json_encode(["ok" => true, "data" => $dados], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  echo json_encode(["ok" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>