<?php
/**
 * KPI: Taxa de Envio para Análise
 * Percentual de equipamentos recebidos que foram enviados para análise
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
    
    // Total recebido
    $sqlTotalAtual = "
        SELECT SUM(quantidade) AS total
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";
    $paramsTotalAtual = [$dataInicioSQL, $dataFimSQL];
    
    if ($setor) {
        $sqlTotalAtual .= " AND setor = ?";
        $paramsTotalAtual[] = $setor;
    }
    if ($operador) {
        $sqlTotalAtual .= " AND operador_recebimento = ?";
        $paramsTotalAtual[] = $operador;
    }

    $stmtTotal = $conn->prepare($sqlTotalAtual);
    $stmtTotal->execute($paramsTotalAtual);
    $totalRecebido = (int)($stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Total enviado para análise
    $sqlEnviadoAtual = "
        SELECT SUM(r.quantidade) AS total
        FROM recebimentos r
        INNER JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
    ";
    $paramsEnviadoAtual = [$dataInicioSQL, $dataFimSQL];
    
    if ($setor) {
        $sqlEnviadoAtual .= " AND r.setor = ?";
        $paramsEnviadoAtual[] = $setor;
    }
    if ($operador) {
        $sqlEnviadoAtual .= " AND r.operador_recebimento = ?";
        $paramsEnviadoAtual[] = $operador;
    }

    $stmtEnviado = $conn->prepare($sqlEnviadoAtual);
    $stmtEnviado->execute($paramsEnviadoAtual);
    $totalEnviado = (int)($stmtEnviado->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $taxaAtual = $totalRecebido > 0 ? ($totalEnviado / $totalRecebido) * 100 : 0;

    // ========================================
    // PERÍODO ANTERIOR
    // ========================================
    
    $sqlTotalRef = "
        SELECT SUM(quantidade) AS total
        FROM recebimentos
        WHERE data_entrada >= ? AND data_entrada <= ?
    ";
    $paramsTotalRef = [$dataInicioRef, $dataFimRef];
    if ($setor) {
        $sqlTotalRef .= " AND setor = ?";
        $paramsTotalRef[] = $setor;
    }
    if ($operador) {
        $sqlTotalRef .= " AND operador_recebimento = ?";
        $paramsTotalRef[] = $operador;
    }

    $stmtTotalRef = $conn->prepare($sqlTotalRef);
    $stmtTotalRef->execute($paramsTotalRef);
    $totalRecebidoRef = (int)($stmtTotalRef->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $sqlEnviadoRef = "
        SELECT SUM(r.quantidade) AS total
        FROM recebimentos r
        INNER JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
    ";
    $paramsEnviadoRef = [$dataInicioRef, $dataFimRef];
    if ($setor) {
        $sqlEnviadoRef .= " AND r.setor = ?";
        $paramsEnviadoRef[] = $setor;
    }
    if ($operador) {
        $sqlEnviadoRef .= " AND r.operador_recebimento = ?";
        $paramsEnviadoRef[] = $operador;
    }

    $stmtEnviadoRef = $conn->prepare($sqlEnviadoRef);
    $stmtEnviadoRef->execute($paramsEnviadoRef);
    $totalEnviadoRef = (int)($stmtEnviadoRef->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $taxaAnterior = $totalRecebidoRef > 0 ? ($totalEnviadoRef / $totalRecebidoRef) * 100 : 0;

    // Variação
    $variacao = 0;
    if ($taxaAnterior > 0) {
        $variacao = (($taxaAtual - $taxaAnterior) / $taxaAnterior) * 100;
    }

    // Estado
    $estado = 'neutral';
    if ($taxaAtual >= 95) {
        $estado = 'success';
    } elseif ($taxaAtual < 80) {
        $estado = 'warning';
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
    error_log("Erro em kpi-taxa-envio-analise.php: " . $e->getMessage());
    sendError('Erro ao calcular taxa de envio: ' . $e->getMessage(), 500);
}
?>
