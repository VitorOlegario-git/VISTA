<?php
/**
 * KPI: Remessas Recebidas
 * Retorna a contagem de remessas recebidas no período com comparação ao período anterior
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../endpoint-helpers.php';

try {
    // Obter parâmetros
    $dataInicio = $_GET['inicio'] ?? null;
    $dataFim = $_GET['fim'] ?? null;
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    // Validar datas obrigatórias
    if (!$dataInicio || !$dataFim) {
        sendError('Parâmetros data_inicial e data_final são obrigatórios', 400);
    }

    // Converter formato de data (DD/MM/YYYY -> YYYY-MM-DD)
    $dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
    $dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));

    // Calcular período de referência (mesmo tamanho, período anterior)
    $diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / 86400;
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicioSQL -" . ($diasPeriodo + 1) . " days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicioSQL -1 day"));

    $db = Database::getInstance();
    $conn = $db->getConnection();

    // ========================================
    // PERÍODO ATUAL
    // ========================================
    $sqlAtual = "
        SELECT COUNT(DISTINCT id) AS total_remessas
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";

    $paramsAtual = [$dataInicioSQL, $dataFimSQL];

    if ($setor) {
        $sqlAtual .= " AND setor = ?";
        $paramsAtual[] = $setor;
    }

    if ($operador) {
        $sqlAtual .= " AND operador_recebimento = ?";
        $paramsAtual[] = $operador;
    }

    $stmtAtual = $conn->prepare($sqlAtual);
    $stmtAtual->execute($paramsAtual);
    $resultAtual = $stmtAtual->fetch(PDO::FETCH_ASSOC);
    $totalAtual = (int)$resultAtual['total_remessas'];

    // ========================================
    // PERÍODO ANTERIOR (REFERÊNCIA)
    // ========================================
    $sqlAnterior = "
        SELECT COUNT(DISTINCT id) AS total_remessas
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";

    $paramsAnterior = [$dataInicioRef, $dataFimRef];

    if ($setor) {
        $sqlAnterior .= " AND setor = ?";
        $paramsAnterior[] = $setor;
    }

    if ($operador) {
        $sqlAnterior .= " AND operador_recebimento = ?";
        $paramsAnterior[] = $operador;
    }

    $stmtAnterior = $conn->prepare($sqlAnterior);
    $stmtAnterior->execute($paramsAnterior);
    $resultAnterior = $stmtAnterior->fetch(PDO::FETCH_ASSOC);
    $totalAnterior = (int)$resultAnterior['total_remessas'];

    // ========================================
    // CÁLCULO DE VARIAÇÃO E ESTADO
    // ========================================
    $variacao = 0;
    if ($totalAnterior > 0) {
        $variacao = (($totalAtual - $totalAnterior) / $totalAnterior) * 100;
    } elseif ($totalAtual > 0) {
        $variacao = 100;
    }

    // Definir estado do KPI
    $estado = 'neutral';
    if ($variacao >= 10) {
        $estado = 'success'; // Crescimento significativo
    } elseif ($variacao <= -10) {
        $estado = 'warning'; // Queda significativa
    }

    // Retornar resposta
    sendSuccess([
        'valor' => $totalAtual,
        'unidade' => 'remessas',
        'periodo' => [
            'inicio' => $dataInicioSQL,
            'fim' => $dataFimSQL
        ],
        'referencia' => [
            'valor' => $totalAnterior,
            'variacao' => round($variacao, 1),
            'estado' => $estado,
            'periodo' => [
                'inicio' => $dataInicioRef,
                'fim' => $dataFimRef
            ]
        ],
        'filtros_aplicados' => [
            'setor' => $setor,
            'operador' => $operador
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em kpi-remessas-recebidas.php: " . $e->getMessage());
    sendError('Erro ao calcular KPI de remessas recebidas: ' . $e->getMessage(), 500);
}
?>
