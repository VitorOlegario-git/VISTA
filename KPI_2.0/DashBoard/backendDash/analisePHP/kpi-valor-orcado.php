<?php
/**
 * KPI: Valor Orçado na Análise
 * SUM(valor_orcamento)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../endpoint-helpers.php';

try {
    $dataInicio = $_GET['inicio'] ?? null;
    $dataFim = $_GET['fim'] ?? null;
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    if (!$dataInicio || !$dataFim) {
        sendError('Parâmetros inicio e fim são obrigatórios', 400);
    }

    $dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
    $dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));

    $diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / 86400;
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicioSQL -" . ($diasPeriodo + 1) . " days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicioSQL -1 day"));

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // ========================================
    // PERÍODO ATUAL
    // ========================================
    $sqlAtual = "
        SELECT 
            SUM(COALESCE(valor_orcamento, 0)) AS total_valor,
            COUNT(*) AS num_orcamentos
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";

    $paramsAtual = [$dataInicioSQL, $dataFimSQL];

    if ($setor) {
        $sqlAtual .= " AND setor = ?";
        $paramsAtual[] = $setor;
    }

    if ($operador) {
        $sqlAtual .= " AND operador_analise = ?";
        $paramsAtual[] = $operador;
    }

    $stmtAtual = $conn->prepare($sqlAtual);
    $stmtAtual->execute($paramsAtual);
    $resultAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);
    $valorAtual = (float)($resultAtual['total_valor'] ?? 0);
    $numOrcamentos = (int)($resultAtual['num_orcamentos'] ?? 0);

    // ========================================
    // PERÍODO ANTERIOR
    // ========================================
    $sqlAnterior = "
        SELECT SUM(COALESCE(valor_orcamento, 0)) AS total_valor
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";

    $paramsAnterior = [$dataInicioRef, $dataFimRef];

    if ($setor) {
        $sqlAnterior .= " AND setor = ?";
        $paramsAnterior[] = $setor;
    }

    if ($operador) {
        $sqlAnterior .= " AND operador_analise = ?";
        $paramsAnterior[] = $operador;
    }

    $stmtAnterior = $conn->prepare($sqlAnterior);
    $stmtAnterior->execute($paramsAnterior);
    $valorAnterior = (float)($stmtAnterior->fetch(PDO::FETCH_ASSOC)['total_valor'] ?? 0);

    // Variação
    $variacao = 0;
    if ($valorAnterior > 0) {
        $variacao = (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
    } elseif ($valorAtual > 0) {
        $variacao = 100;
    }

    // Estado
    $estado = 'neutral';
    if ($variacao >= 10) {
        $estado = 'success'; // Valor orçado aumentou
    } elseif ($variacao <= -10) {
        $estado = 'warning'; // Valor orçado diminuiu
    }

    sendSuccess([
        'valor' => $valorAtual,
        'unidade' => 'R$',
        'periodo' => [
            'inicio' => $dataInicioSQL,
            'fim' => $dataFimSQL
        ],
        'referencia' => [
            'valor' => $valorAnterior,
            'variacao' => round($variacao, 1),
            'estado' => $estado
        ],
        'extras' => [
            'num_orcamentos' => $numOrcamentos,
            'valor_medio' => $numOrcamentos > 0 ? round($valorAtual / $numOrcamentos, 2) : 0
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em kpi-valor-orcado.php: " . $e->getMessage());
    sendError('Erro ao calcular valor orçado: ' . $e->getMessage(), 500);
}
?>
