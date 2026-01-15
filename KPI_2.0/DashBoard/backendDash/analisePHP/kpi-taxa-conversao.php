<?php
/**
 * KPI: Taxa de Conversão da Análise
 * (Equipamentos Analisados / Equipamentos Recebidos) × 100
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
    
    // Total recebido (da tabela recebimentos)
    $sqlRecebidoAtual = "
        SELECT SUM(quantidade) AS total
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";
    $paramsRecebidoAtual = [$dataInicioSQL, $dataFimSQL];
    
    if ($setor) {
        $sqlRecebidoAtual .= " AND setor = ?";
        $paramsRecebidoAtual[] = $setor;
    }

    $stmtRecebido = $conn->prepare($sqlRecebidoAtual);
    $stmtRecebido->execute($paramsRecebidoAtual);
    $totalRecebido = (int)($stmtRecebido->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Total analisado
    $sqlAnalisadoAtual = "
        SELECT SUM(COALESCE(quantidade_analisada, 0)) AS total
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";
    $paramsAnalisadoAtual = [$dataInicioSQL, $dataFimSQL];
    
    if ($setor) {
        $sqlAnalisadoAtual .= " AND setor = ?";
        $paramsAnalisadoAtual[] = $setor;
    }
    if ($operador) {
        $sqlAnalisadoAtual .= " AND operador_analise = ?";
        $paramsAnalisadoAtual[] = $operador;
    }

    $stmtAnalisado = $conn->prepare($sqlAnalisadoAtual);
    $stmtAnalisado->execute($paramsAnalisadoAtual);
    $totalAnalisado = (int)($stmtAnalisado->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $taxaAtual = $totalRecebido > 0 ? ($totalAnalisado / $totalRecebido) * 100 : 0;

    // ========================================
    // PERÍODO ANTERIOR
    // ========================================
    
    $sqlRecebidoRef = "
        SELECT SUM(quantidade) AS total
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";
    $paramsRecebidoRef = [$dataInicioRef, $dataFimRef];
    if ($setor) {
        $sqlRecebidoRef .= " AND setor = ?";
        $paramsRecebidoRef[] = $setor;
    }

    $stmtRecebidoRef = $conn->prepare($sqlRecebidoRef);
    $stmtRecebidoRef->execute($paramsRecebidoRef);
    $totalRecebidoRef = (int)($stmtRecebidoRef->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $sqlAnalisadoRef = "
        SELECT SUM(COALESCE(quantidade_analisada, 0)) AS total
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";
    $paramsAnalisadoRef = [$dataInicioRef, $dataFimRef];
    if ($setor) {
        $sqlAnalisadoRef .= " AND setor = ?";
        $paramsAnalisadoRef[] = $setor;
    }
    if ($operador) {
        $sqlAnalisadoRef .= " AND operador_analise = ?";
        $paramsAnalisadoRef[] = $operador;
    }

    $stmtAnalisadoRef = $conn->prepare($sqlAnalisadoRef);
    $stmtAnalisadoRef->execute($paramsAnalisadoRef);
    $totalAnalisadoRef = (int)($stmtAnalisadoRef->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $taxaAnterior = $totalRecebidoRef > 0 ? ($totalAnalisadoRef / $totalRecebidoRef) * 100 : 0;

    // Variação
    $variacao = 0;
    if ($taxaAnterior > 0) {
        $variacao = (($taxaAtual - $taxaAnterior) / $taxaAnterior) * 100;
    }

    // Estado
    $estado = 'neutral';
    if ($taxaAtual >= 85) {
        $estado = 'success';
    } elseif ($taxaAtual >= 70) {
        $estado = 'warning';
    } else {
        $estado = 'critical';
    }

    sendSuccess([
        'valor' => round($taxaAtual, 1),
        'unidade' => '%',
        'periodo' => [
            'inicio' => $dataInicioSQL,
            'fim' => $dataFimSQL
        ],
        'referencia' => [
            'valor' => round($taxaAnterior, 1),
            'variacao' => round($variacao, 1),
            'estado' => $estado
        ]
    ]);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-conversao.php: " . $e->getMessage());
    sendError('Erro ao calcular taxa de conversão: ' . $e->getMessage(), 500);
}
?>
