<?php
/**
 * KPI REPARO 4: TEMPO MÉDIO DE REPARO
 * 
 * Retorna tempo médio (em dias) de permanência no reparo.
 * Usa data_registro como proxy para tempo no setor.
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

    // WHERE com filtros globais
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_registro',
        null,
        $setor,
        'setor'
    );

    // Query - tempo médio baseado em diferença de datas
    // Nota: Simplificado pois não temos data_inicio_reparo e data_fim_reparo
    // Usa DATEDIFF entre hoje e data_registro como estimativa
    $sql = "
        SELECT 
            AVG(DATEDIFF(CURDATE(), data_registro)) as tempo_medio,
            COUNT(*) as total_reparos
        FROM reparo_resumo
        {$whereInfo['where']}
        AND quantidade_reparada > 0
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = round((float)($row['tempo_medio'] ?? 0), 1);
    $totalReparos = (int)($row['total_reparos'] ?? 0);

    // Valor de referência (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    $whereRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_registro',
        null,
        $setor,
        'setor'
    );
    
    $sqlReferencia = "
        SELECT AVG(DATEDIFF(CURDATE(), data_registro)) as tempo_medio
        FROM reparo_resumo
        {$whereRefInfo['where']}
        AND quantidade_reparada > 0
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
        'titulo' => 'Tempo Médio de Reparo',
        'periodo' => 'Período selecionado',
        'contexto' => 'Permanência no setor',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'total_reparos' => $totalReparos
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-tempo-medio-reparo.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular tempo médio de reparo');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
