<?php
/**
 * KPI REPARO 3: TAXA DE CONVERSÃO DO REPARO
 * 
 * Retorna (Equipamentos Reparados / Equipamentos Analisados) * 100.
 * Mede a eficiência da conversão de análises em reparos completados.
 * Catálogo Oficial de KPIs v1.0 - Área: Reparo
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

    // WHERE para reparo_resumo (reparados)
    $whereReparoInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_registro',
        null,
        $setor,
        'setor'
    );

    // Query - equipamentos reparados
    $sqlReparados = "
        SELECT COALESCE(SUM(quantidade_reparada), 0) as total
        FROM reparo_resumo
        {$whereReparoInfo['where']}
    ";

    $resultReparados = executarQuery($conn, $sqlReparados, $whereReparoInfo['params'], $whereReparoInfo['types']);
    $rowReparados = $resultReparados->fetch_assoc();
    $equipamentosReparados = (int)($rowReparados['total'] ?? 0);

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

    // Calcula taxa de conversão
    $taxaAtual = $equipamentosAnalisados > 0 
        ? round(($equipamentosReparados / $equipamentosAnalisados) * 100, 1) 
        : 0;

    // Período de referência
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    // Reparo - período anterior
    $whereReparoRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_registro',
        null,
        $setor,
        'setor'
    );
    
    $sqlReparadosRef = "
        SELECT COALESCE(SUM(quantidade_reparada), 0) as total
        FROM reparo_resumo
        {$whereReparoRefInfo['where']}
    ";
    
    $resultReparadosRef = executarQuery($conn, $sqlReparadosRef, $whereReparoRefInfo['params'], $whereReparoRefInfo['types']);
    $rowReparadosRef = $resultReparadosRef->fetch_assoc();
    $equipamentosReparadosRef = (int)($rowReparadosRef['total'] ?? 0);

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

    $taxaReferencia = $equipamentosAnalisadosRef > 0 
        ? round(($equipamentosReparadosRef / $equipamentosAnalisadosRef) * 100, 1) 
        : 0;

    // Variação em pontos percentuais
    $variacao = $taxaAtual - $taxaReferencia;
    
    // Define estado
    if ($taxaAtual < 60) {
        $estado = 'critical';
    } elseif ($taxaAtual < 75) {
        $estado = 'warning';
    } else {
        $estado = 'success';
    }

    // Monta resposta
    $kpi = [
        'valor' => $taxaAtual,
        'unidade' => '%',
        'titulo' => 'Taxa de Conversão do Reparo',
        'periodo' => 'Período selecionado',
        'contexto' => 'Reparados / Analisados',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $taxaReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'reparados' => $equipamentosReparados,
            'analisados' => $equipamentosAnalisados
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-conversao-reparo.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular taxa de conversão do reparo');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
