<?php
/**
 * KPI: Tempo Médio até Análise
 * Calcula o tempo médio entre recebimento e envio para análise
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

    // Período de referência
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
            AVG(DATEDIFF(ar.data_analise, r.data_entrada)) AS tempo_medio_dias
        FROM recebimentos r
        INNER JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.data_analise IS NOT NULL
    ";

    $paramsAtual = [$dataInicioSQL, $dataFimSQL];

    if ($setor) {
        $sqlAtual .= " AND r.setor = ?";
        $paramsAtual[] = $setor;
    }

    if ($operador) {
        $sqlAtual .= " AND r.operador_recebimento = ?";
        $paramsAtual[] = $operador;
    }

    $stmtAtual = $conn->prepare($sqlAtual);
    $stmtAtual->execute($paramsAtual);
    $resultAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);
    $tempoAtual = round((float)($resultAtual['tempo_medio_dias'] ?? 0), 1);

    // ========================================
    // PERÍODO ANTERIOR
    // ========================================
    $sqlAnterior = "
        SELECT 
            AVG(DATEDIFF(ar.data_analise, r.data_entrada)) AS tempo_medio_dias
        FROM recebimentos r
        INNER JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.data_analise IS NOT NULL
    ";

    $paramsAnterior = [$dataInicioRef, $dataFimRef];

    if ($setor) {
        $sqlAnterior .= " AND r.setor = ?";
        $paramsAnterior[] = $setor;
    }

    if ($operador) {
        $sqlAnterior .= " AND r.operador_recebimento = ?";
        $paramsAnterior[] = $operador;
    }

    $stmtAnterior = $conn->prepare($sqlAnterior);
    $stmtAnterior->execute($paramsAnterior);
    $resultAnterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);
    $tempoAnterior = round((float)($resultAnterior['tempo_medio_dias'] ?? 0), 1);

    // Variação (invertida: redução de tempo é positiva)
    $variacao = 0;
    if ($tempoAnterior > 0) {
        $variacao = (($tempoAtual - $tempoAnterior) / $tempoAnterior) * 100;
    }

    // Estado (invertido: menos tempo é melhor)
    $estado = 'neutral';
    if ($variacao <= -10) {
        $estado = 'success'; // Tempo reduziu
    } elseif ($variacao >= 10) {
        $estado = 'critical'; // Tempo aumentou
    }

    sendSuccess([
        'valor' => $tempoAtual,
        'unidade' => 'dias',
        'periodo' => [
            'inicio' => $dataInicioSQL,
            'fim' => $dataFimSQL
        ],
        'referencia' => [
            'valor' => $tempoAnterior,
            'variacao' => round($variacao, 1),
            'estado' => $estado,
            'periodo' => [
                'inicio' => $dataInicioRef,
                'fim' => $dataFimRef
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em kpi-tempo-ate-analise.php: " . $e->getMessage());
    sendError('Erro ao calcular tempo médio: ' . $e->getMessage(), 500);
}
?>
