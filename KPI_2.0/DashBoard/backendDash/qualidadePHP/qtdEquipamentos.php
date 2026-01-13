<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Filtros
$data_inicio = $_POST['data_inicial'] ?? '2000-01-01';
$data_fim    = $_POST['data_final'] ?? date('Y-m-d');

// Busca as notas fiscais dentro do período
function buscarNotasFiscais($conn, $tabela, $coluna_data, $data_inicio, $data_fim) {
    $sql = "SELECT DISTINCT nota_fiscal FROM $tabela WHERE $coluna_data BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();

    $notas = [];
    while ($row = $result->fetch_assoc()) {
        $nota = $row['nota_fiscal'];
        if (!empty($nota)) {
            $notas[] = $nota;
        }
    }

    $stmt->close();
    return $notas;
}

// Agrupa modelos por NF e soma a quantidade por modelo
function buscarQuantidadePorModelo($conn, $notas) {
    if (empty($notas)) return [];

    $placeholders = implode(',', array_fill(0, count($notas), '?'));
    $tipos = str_repeat('s', count($notas));

    $sql = "SELECT modelo, COUNT(*) as quantidade FROM laudos_manutencao WHERE nf IN ($placeholders) GROUP BY modelo";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$notas);
    $stmt->execute();
    $result = $stmt->get_result();

    $modelos = [];
    while ($row = $result->fetch_assoc()) {
        $modelo = $row['modelo'] ?: 'Desconhecido';
        $modelos[] = [
            "modelo" => $modelo,
            "quantidade" => (int)$row['quantidade']
        ];
    }

    $stmt->close();
    return $modelos;
}

// Processamento
$notas_recebidas   = buscarNotasFiscais($conn, 'recebimentos',     'data_envio_analise',      $data_inicio, $data_fim);
$notas_analisadas  = buscarNotasFiscais($conn, 'analise_parcial',  'data_envio_orcamento',  $data_inicio, $data_fim);
$notas_reparadas   = buscarNotasFiscais($conn, 'reparo_parcial',   'data_solicitacao_nf',   $data_inicio, $data_fim);

$modelos_recebidos   = buscarQuantidadePorModelo($conn, $notas_recebidas);
$modelos_analisados  = buscarQuantidadePorModelo($conn, $notas_analisadas);
$modelos_reparados   = buscarQuantidadePorModelo($conn, $notas_reparadas);

// Retorno final
echo json_encode([
    "modelos_recebidos"  => $modelos_recebidos,
    "modelos_analisados" => $modelos_analisados,
    "modelos_reparados"  => $modelos_reparados
]);
?>