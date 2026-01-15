<?php
/**
 * KPI: Equipamentos em Análise (Backlog)
 * Volume pendente de análise
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
    // PERÍODO ATUAL - Backlog
    // ========================================
    $sqlAtual = "
        SELECT SUM(quantidade_total - COALESCE(quantidade_analisada, 0)) AS backlog
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
    $backlogAtual = (int)($stmtAtual->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // ========================================
    // PERÍODO ANTERIOR
    // ========================================
    $sqlAnterior = "
        SELECT SUM(quantidade_total - COALESCE(quantidade_analisada, 0)) AS backlog
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
    $backlogAnterior = (int)($stmtAnterior->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // Variação (invertida: menos backlog é melhor)
    $variacao = 0;
    if ($backlogAnterior > 0) {
        $variacao = (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100;
    } elseif ($backlogAtual > 0) {
        $variacao = 100;
    }

    // Estado (invertido)
    $estado = 'neutral';
    if ($variacao >= 50) {
        $estado = 'critical'; // Backlog aumentou muito
    } elseif ($variacao >= 20) {
        $estado = 'warning'; // Backlog aumentou moderadamente
    } elseif ($variacao <= -10) {
        $estado = 'success'; // Backlog reduziu
    }

    sendSuccess([
        'valor' => $backlogAtual,
        'unidade' => 'equipamentos',
        'periodo' => [
            'inicio' => $dataInicioSQL,
            'fim' => $dataFimSQL
        ],
        'referencia' => [
            'valor' => $backlogAnterior,
            'variacao' => round($variacao, 1),
            'estado' => $estado
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em kpi-backlog-analise.php: " . $e->getMessage());
    sendError('Erro ao calcular backlog: ' . $e->getMessage(), 500);
}
?>
