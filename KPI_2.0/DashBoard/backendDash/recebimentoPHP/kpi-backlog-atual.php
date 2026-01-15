<?php
/**
 * KPI: Backlog Atual
 * Equipamentos recebidos que ainda não foram enviados para análise
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
    // BACKLOG ATUAL
    // ========================================
    $sqlAtual = "
        SELECT SUM(r.quantidade) AS backlog
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.id IS NULL
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
    $backlogAtual = (int)($stmtAtual->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // ========================================
    // BACKLOG ANTERIOR
    // ========================================
    $sqlAnterior = "
        SELECT SUM(r.quantidade) AS backlog
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.id IS NULL
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
    $backlogAnterior = (int)($stmtAnterior->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // Variação (invertida: redução de backlog é positiva)
    $variacao = 0;
    if ($backlogAnterior > 0) {
        $variacao = (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100;
    }

    // Estado (invertido: menos backlog é melhor)
    $estado = 'neutral';
    if ($variacao <= -10) {
        $estado = 'success'; // Backlog reduziu
    } elseif ($variacao >= 10) {
        $estado = 'critical'; // Backlog aumentou
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
    error_log("Erro em kpi-backlog-atual.php: " . $e->getMessage());
    sendError('Erro ao calcular backlog: ' . $e->getMessage(), 500);
}
?>
