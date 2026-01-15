<?php
/**
 * KPI ANÁLISE 4: TEMPO MÉDIO DE ANÁLISE
 * 
 * Retorna tempo médio (em dias) entre recebimento e conclusão da análise.
 * Calcula AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) de analise_parcial.
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

    // WHERE com filtros globais
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_envio_orcamento',
        'operador',
        $setor,
        'setor'
    );

    // Query - tempo médio de análise
    $sql = "
        SELECT 
            AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) as tempo_medio,
            COUNT(*) as total_analises
        FROM analise_parcial
        {$whereInfo['where']}
        AND data_envio_orcamento IS NOT NULL
        AND data_inicio_analise IS NOT NULL
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = round((float)($row['tempo_medio'] ?? 0), 1);
    $totalAnalises = (int)($row['total_analises'] ?? 0);

    // Valor de referência (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    $whereRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_envio_orcamento',
        'operador',
        $setor,
        'setor'
    );
    
    $sqlReferencia = "
        SELECT AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) as tempo_medio
        FROM analise_parcial
        {$whereRefInfo['where']}
        AND data_envio_orcamento IS NOT NULL
        AND data_inicio_analise IS NOT NULL
    ";
    
    $resultRef = executarQuery($conn, $sqlReferencia, $whereRefInfo['params'], $whereRefInfo['types']);
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = round((float)($rowRef['tempo_medio'] ?? 0), 1);

    // Calcula variação
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    
    // Define estado (tempo alto é ruim - invertido)
    if ($variacao > 30) {
        $estado = 'critical'; // Tempo aumentou muito
    } elseif ($variacao > 15) {
        $estado = 'warning'; // Tempo aumentou
    } elseif ($variacao < -15) {
        $estado = 'success'; // Tempo reduziu significativamente
    } else {
        $estado = 'neutral';
    }

    // Monta resposta
    $kpi = [
        'valor' => $valorAtual,
        'unidade' => 'dias',
        'titulo' => 'Tempo Médio de Análise',
        'periodo' => 'Período selecionado',
        'contexto' => 'Recebimento até orçamento',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'total_analises' => $totalAnalises
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-tempo-medio-analise.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular tempo médio de análise');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
