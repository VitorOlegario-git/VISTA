<?php
/**
 * 📦 KPI 4: TAXA DE CONCLUSÃO TÉCNICA
 * 
 * Retorna (Equipamentos Expedidos / Equipamentos Recebidos) * 100
 * Indica % do que foi recebido e já foi finalizado/expedido.
 * Catálogo Oficial de KPIs v1.0
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../BackEnd/conexao.php';
    require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Erro ao carregar dependências']);
    exit;
}

validarConexao($conn);

$params = validarParametrosPadrao();
extract($params); // $dataInicio, $dataFim, $operador, $setor

try {
    if (!$dataInicio || !$dataFim) {
        enviarErro(400, 'Período (inicio e fim) é obrigatório para este KPI');
    }

    // WHERE para recebimentos
    $whereRecInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento',
        'operador',
        $setor,
        'setor'
    );

    // WHERE para expedições
    $whereExpInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_expedicao',
        'operador',
        $setor,
        'setor'
    );

    // Query - equipamentos recebidos
    $sqlRecebidos = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM recebimentos
        {$whereRecInfo['where']}
    ";

    $resultRec = executarQuery($conn, $sqlRecebidos, $whereRecInfo['params'], $whereRecInfo['types']);
    $rowRec = $resultRec->fetch_assoc();
    $equipamentosRecebidos = (int)($rowRec['total'] ?? 0);

    // Query - equipamentos expedidos
    $sqlExpedidos = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM expedicao_registro
        {$whereExpInfo['where']}
    ";

    $resultExp = executarQuery($conn, $sqlExpedidos, $whereExpInfo['params'], $whereExpInfo['types']);
    $rowExp = $resultExp->fetch_assoc();
    $equipamentosExpedidos = (int)($rowExp['total'] ?? 0);

    // Calcula taxa (previne divisão por zero)
    $taxaAtual = $equipamentosRecebidos > 0 
        ? round(($equipamentosExpedidos / $equipamentosRecebidos) * 100, 1) 
        : 0;

    // Valor de referência (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    // Recebidos período anterior
    $whereRecRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_recebimento',
        'operador',
        $setor,
        'setor'
    );
    
    $sqlRecebidosRef = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM recebimentos
        {$whereRecRefInfo['where']}
    ";
    
    $resultRecRef = executarQuery($conn, $sqlRecebidosRef, $whereRecRefInfo['params'], $whereRecRefInfo['types']);
    $rowRecRef = $resultRecRef->fetch_assoc();
    $equipamentosRecebidosRef = (int)($rowRecRef['total'] ?? 0);

    // Expedidos período anterior
    $whereExpRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_expedicao',
        'operador',
        $setor,
        'setor'
    );
    
    $sqlExpedidosRef = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM expedicao_registro
        {$whereExpRefInfo['where']}
    ";
    
    $resultExpRef = executarQuery($conn, $sqlExpedidosRef, $whereExpRefInfo['params'], $whereExpRefInfo['types']);
    $rowExpRef = $resultExpRef->fetch_assoc();
    $equipamentosExpedidosRef = (int)($rowExpRef['total'] ?? 0);

    $taxaReferencia = $equipamentosRecebidosRef > 0 
        ? round(($equipamentosExpedidosRef / $equipamentosRecebidosRef) * 100, 1) 
        : 0;

    // Calcula variação em pontos percentuais
    $variacaoPP = $taxaAtual - $taxaReferencia;
    
    // Define estado (crescimento é bom)
    if ($variacaoPP >= 5) {
        $estado = 'success';
    } elseif ($variacaoPP >= -5) {
        $estado = 'warning';
    } else {
        $estado = 'critical';
    }

    // Monta resposta
    $kpi = [
        'valor' => $taxaAtual,
        'unidade' => '%',
        'titulo' => 'Taxa de Conclusão Técnica',
        'periodo' => 'Período selecionado',
        'contexto' => 'Equipamentos finalizados vs recebidos',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $taxaReferencia,
            'variacao' => $variacaoPP,
            'estado' => $estado
        ],
        'detalhes' => [
            'recebidos' => $equipamentosRecebidos,
            'expedidos' => $equipamentosExpedidos
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-conclusao.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular taxa de conclusão técnica');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
