<?php
/**
 * KPI ANÁLISE 3: TAXA DE CONVERSÃO DA ANÁLISE
 * 
 * Retorna (Equipamentos Analisados / Equipamentos Recebidos) * 100.
 * Mede a eficiência da conversão de recebimentos em análises completadas.
 * Catálogo Oficial de KPIs v1.0 - Área: Análise
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

    // WHERE para analise_resumo (analisados)
    $whereAnaliseInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_atualizacao',
        null,
        $setor,
        'setor'
    );

    // Query - equipamentos analisados
    $sqlAnalisados = "
        SELECT COALESCE(SUM(quantidade_analisada), 0) as total
        FROM analise_resumo
        {$whereAnaliseInfo['where']}
    ";

    $resultAnalisados = executarQuery($conn, $sqlAnalisados, $whereAnaliseInfo['params'], $whereAnaliseInfo['types']);
    $rowAnalisados = $resultAnalisados->fetch_assoc();
    $equipamentosAnalisados = (int)($rowAnalisados['total'] ?? 0);

    // WHERE para recebimentos (recebidos)
    $whereRecInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento',
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

    $resultRecebidos = executarQuery($conn, $sqlRecebidos, $whereRecInfo['params'], $whereRecInfo['types']);
    $rowRecebidos = $resultRecebidos->fetch_assoc();
    $equipamentosRecebidos = (int)($rowRecebidos['total'] ?? 0);

    // Calcula taxa de conversão
    $taxaAtual = $equipamentosRecebidos > 0 
        ? round(($equipamentosAnalisados / $equipamentosRecebidos) * 100, 1) 
        : 0;

    // Período de referência
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    // Análise - período anterior
    $whereAnaliseRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_atualizacao',
        null,
        $setor,
        'setor'
    );
    
    $sqlAnalisadosRef = "
        SELECT COALESCE(SUM(quantidade_analisada), 0) as total
        FROM analise_resumo
        {$whereAnaliseRefInfo['where']}
    ";
    
    $resultAnalisadosRef = executarQuery($conn, $sqlAnalisadosRef, $whereAnaliseRefInfo['params'], $whereAnaliseRefInfo['types']);
    $rowAnalisadosRef = $resultAnalisadosRef->fetch_assoc();
    $equipamentosAnalisadosRef = (int)($rowAnalisadosRef['total'] ?? 0);

    // Recebimentos - período anterior
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
    
    $resultRecebidosRef = executarQuery($conn, $sqlRecebidosRef, $whereRecRefInfo['params'], $whereRecRefInfo['types']);
    $rowRecebidosRef = $resultRecebidosRef->fetch_assoc();
    $equipamentosRecebidosRef = (int)($rowRecebidosRef['total'] ?? 0);

    $taxaReferencia = $equipamentosRecebidosRef > 0 
        ? round(($equipamentosAnalisadosRef / $equipamentosRecebidosRef) * 100, 1) 
        : 0;

    // Variação em pontos percentuais
    $variacao = $taxaAtual - $taxaReferencia;
    
    // Define estado (baixa conversão é ruim)
    if ($taxaAtual < 70) {
        $estado = 'critical';
    } elseif ($taxaAtual < 85) {
        $estado = 'warning';
    } else {
        $estado = 'success';
    }

    // Monta resposta
    $kpi = [
        'valor' => $taxaAtual,
        'unidade' => '%',
        'titulo' => 'Taxa de Conversão da Análise',
        'periodo' => 'Período selecionado',
        'contexto' => 'Analisados / Recebidos',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $taxaReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'analisados' => $equipamentosAnalisados,
            'recebidos' => $equipamentosRecebidos
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-conversao-analise.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular taxa de conversão da análise');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
