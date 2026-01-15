<?php
/**
 * KPI 5: VALOR TOTAL ORÇADO
 * 
 * Retorna SUM(valor_orcamento) de analise_resumo + reparo_resumo.
 * Considera orçamentos gerados no período (análise + reparo).
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

    // WHERE para análise_resumo
    $whereAnaliseInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_atualizacao',
        null, // analise_resumo não tem campo operador direto
        $setor,
        'setor'
    );

    // WHERE para reparo_resumo
    $whereReparoInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_registro', // reparo_resumo usa data_registro
        null, // reparo_resumo não tem campo operador direto
        $setor,
        'setor'
    );

    // Query - valor orçado em análise
    $sqlAnalise = "
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM analise_resumo
        {$whereAnaliseInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";

    $resultAnalise = executarQuery($conn, $sqlAnalise, $whereAnaliseInfo['params'], $whereAnaliseInfo['types']);
    $rowAnalise = $resultAnalise->fetch_assoc();
    $valorAnalise = (float)($rowAnalise['total'] ?? 0);

    // Query - valor orçado em reparo
    $sqlReparo = "
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM reparo_resumo
        {$whereReparoInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";

    $resultReparo = executarQuery($conn, $sqlReparo, $whereReparoInfo['params'], $whereReparoInfo['types']);
    $rowReparo = $resultReparo->fetch_assoc();
    $valorReparo = (float)($rowReparo['total'] ?? 0);

    // Total = Análise + Reparo
    $valorAtual = $valorAnalise + $valorReparo;

    // Valor de referência (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    // Análise período anterior
    $whereAnaliseRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_atualizacao',
        null,
        $setor,
        'setor'
    );
    
    $sqlAnaliseRef = "
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM analise_resumo
        {$whereAnaliseRefInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";
    
    $resultAnaliseRef = executarQuery($conn, $sqlAnaliseRef, $whereAnaliseRefInfo['params'], $whereAnaliseRefInfo['types']);
    $rowAnaliseRef = $resultAnaliseRef->fetch_assoc();
    $valorAnaliseRef = (float)($rowAnaliseRef['total'] ?? 0);

    // Reparo período anterior
    $whereReparoRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_registro', // reparo_resumo usa data_registro
        null,
        $setor,
        'setor'
    );
    
    $sqlReparoRef = "
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM reparo_resumo
        {$whereReparoRefInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";
    
    $resultReparoRef = executarQuery($conn, $sqlReparoRef, $whereReparoRefInfo['params'], $whereReparoRefInfo['types']);
    $rowReparoRef = $resultReparoRef->fetch_assoc();
    $valorReparoRef = (float)($rowReparoRef['total'] ?? 0);

    $valorReferencia = $valorAnaliseRef + $valorReparoRef;

    // Calcula variação
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    
    // Define estado (queda é ruim para valor orçado)
    if ($variacao < -25) {
        $estado = 'critical';
    } elseif ($variacao < -10) {
        $estado = 'warning';
    } else {
        $estado = 'success';
    }

    // Formata valores
    $valorFormatado = number_format($valorAtual, 2, ',', '.');
    $valorReferenciaFormatado = number_format($valorReferencia, 2, ',', '.');
    $valorAnaliseFormatado = number_format($valorAnalise, 2, ',', '.');
    $valorReparoFormatado = number_format($valorReparo, 2, ',', '.');

    // Monta resposta
    $kpi = [
        'valor' => $valorFormatado,
        'unidade' => 'R$',
        'titulo' => 'Valor Total Orçado',
        'periodo' => 'Período selecionado',
        'contexto' => 'Soma de orçamentos gerados',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferenciaFormatado,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'analise' => $valorAnaliseFormatado,
            'reparo' => $valorReparoFormatado
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-valor-orcado.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular valor total orçado');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
