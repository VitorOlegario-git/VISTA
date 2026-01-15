<?php
/**
 * KPI ANÁLISE 2: EQUIPAMENTOS ANALISADOS
 * 
 * Retorna SUM(quantidade_analisada) de analise_resumo.
 * Total de equipamentos que completaram a análise no período.
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
        'data_atualizacao',
        null, // analise_resumo não tem campo operador
        $setor,
        'setor'
    );

    // Query principal - SUM de equipamentos analisados
    $sql = "
        SELECT 
            COALESCE(SUM(quantidade_analisada), 0) as total,
            COUNT(*) as remessas_analisadas
        FROM analise_resumo
        {$whereInfo['where']}
        AND quantidade_analisada > 0
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (int)($row['total'] ?? 0);
    $remessasAnalisadas = (int)($row['remessas_analisadas'] ?? 0);

    // Valor de referência (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    $whereRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_atualizacao',
        null,
        $setor,
        'setor'
    );
    
    $sqlReferencia = "
        SELECT COALESCE(SUM(quantidade_analisada), 0) as total
        FROM analise_resumo
        {$whereRefInfo['where']}
        AND quantidade_analisada > 0
    ";
    
    $resultRef = executarQuery($conn, $sqlReferencia, $whereRefInfo['params'], $whereRefInfo['types']);
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = (int)($rowRef['total'] ?? 0);

    // Calcula variação
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    $estado = definirEstado($variacao, [10, 25]);

    // Média diária
    $mediaDia = $diasPeriodo > 0 ? round($valorAtual / $diasPeriodo, 1) : 0;

    // Monta resposta
    $kpi = [
        'valor' => $valorAtual,
        'unidade' => 'equipamentos',
        'titulo' => 'Equipamentos Analisados',
        'periodo' => 'Período selecionado',
        'contexto' => 'Total de equipamentos analisados',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'media_dia' => $mediaDia,
            'remessas_analisadas' => $remessasAnalisadas
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-analisados.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos analisados');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
