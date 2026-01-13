<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);
header("Content-Type: application/json");
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

// Filtros de datas
$data_inicio = $_POST['data_inicial'] ?? '2000-01-01';
$data_fim    = $_POST['data_final'] ?? date('Y-m-d');

// 1. Buscar orçamentos do período em reparo_parcial
$sqlOrc = "SELECT DISTINCT numero_orcamento 
           FROM reparo_parcial 
           WHERE data_solicitacao_nf BETWEEN ? AND ? 
             AND numero_orcamento IS NOT NULL AND numero_orcamento != ''";
$stmt = $conn->prepare($sqlOrc);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$res = $stmt->get_result();

$orcamentos = [];
while ($row = $res->fetch_assoc()) {
    $orcamentos[] = $row['numero_orcamento'];
}
$stmt->close();

if (empty($orcamentos)) {
    echo json_encode([]);
    exit;
}

// 2. Buscar serviços + produto em apontamentos_gerados
$placeholders = implode(',', array_fill(0, count($orcamentos), '?'));
$tipos = str_repeat('s', count($orcamentos));
$sqlServicos = "SELECT servico, produto FROM apontamentos_gerados WHERE orcamento IN ($placeholders)";
$stmt = $conn->prepare($sqlServicos);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["erro" => "Erro ao preparar consulta: " . $conn->error]);
    exit;
}

$stmt->bind_param($tipos, ...$orcamentos);
$stmt->execute();
$result = $stmt->get_result();

// 3. Estrutura hierárquica: serviço > produto > quantidade
$estrutura = [];

while ($row = $result->fetch_assoc()) {
    $servicos = explode(',', $row['servico']);  // Pode haver múltiplos serviços
    $produto = trim($row['produto']);

    foreach ($servicos as $servico) {
        $servico = trim($servico);
        if ($servico === '' || $produto === '') continue;

        if (!isset($estrutura[$servico])) {
            $estrutura[$servico] = [];
        }

        if (!isset($estrutura[$servico][$produto])) {
            $estrutura[$servico][$produto] = 0;
        }

        $estrutura[$servico][$produto]++;
    }
}

ksort($estrutura); // ordena os serviços em ordem alfabética
echo json_encode($estrutura);
?>