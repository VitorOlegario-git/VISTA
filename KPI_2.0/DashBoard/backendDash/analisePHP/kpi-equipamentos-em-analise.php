<?php
/**
 * KPI ANÁLISE 1: EQUIPAMENTOS EM ANÁLISE (BACKLOG)
 * 
 * Retorna SUM(quantidade_total - quantidade_analisada) de analise_resumo.
 * Equipamentos que ainda estão aguardando análise completa.
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

    // Query - equipamentos ainda em análise (total - analisados)
    $sql = "
        SELECT 
            COALESCE(SUM(quantidade_total - quantidade_analisada), 0) as backlog,
            COUNT(*) as remessas_pendentes
        FROM analise_resumo
        {$whereInfo['where']}
        AND quantidade_analisada < quantidade_total
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (int)($row['backlog'] ?? 0);
    $remessasPendentes = (int)($row['remessas_pendentes'] ?? 0);

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
        SELECT COALESCE(SUM(quantidade_total - quantidade_analisada), 0) as backlog
        FROM analise_resumo
        {$whereRefInfo['where']}
        AND quantidade_analisada < quantidade_total
    ";
    
    $resultRef = executarQuery($conn, $sqlReferencia, $whereRefInfo['params'], $whereRefInfo['types']);
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = (int)($rowRef['backlog'] ?? 0);

    // Calcula variação
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    
    // Define estado (backlog crescente é ruim)
    if ($variacao > 50) {
        $estado = 'critical';
    } elseif ($variacao > 20) {
        $estado = 'warning';
    } else {
        $estado = 'success';
    }

    // Monta resposta
    $kpi = [
        'valor' => $valorAtual,
        'unidade' => 'equipamentos',
        'titulo' => 'Equipamentos em Análise',
        'periodo' => 'Período selecionado',
        'contexto' => 'Backlog aguardando análise',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'remessas_pendentes' => $remessasPendentes
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-em-analise.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos em análise');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
