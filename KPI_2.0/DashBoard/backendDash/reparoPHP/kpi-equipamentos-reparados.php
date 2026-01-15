<?php
/**
 * KPI REPARO 2: EQUIPAMENTOS REPARADOS
 * 
 * Retorna SUM(quantidade_reparada) de reparo_resumo.
 * Total de equipamentos que completaram o reparo no período.
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
        null, // reparo_resumo não tem campo operador
        $setor,
        'setor'
    );

    // Query principal - SUM de equipamentos reparados
    $sql = "
        SELECT 
            COALESCE(SUM(quantidade_reparada), 0) as total,
            COUNT(*) as remessas_reparadas
        FROM reparo_resumo
        {$whereInfo['where']}
        AND quantidade_reparada > 0
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (int)($row['total'] ?? 0);
    $remessasReparadas = (int)($row['remessas_reparadas'] ?? 0);

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
        SELECT COALESCE(SUM(quantidade_reparada), 0) as total
        FROM reparo_resumo
        {$whereRefInfo['where']}
        AND quantidade_reparada > 0
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
        'titulo' => 'Equipamentos Reparados',
        'periodo' => 'Período selecionado',
        'contexto' => 'Total de equipamentos reparados',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'media_dia' => $mediaDia,
            'remessas_reparadas' => $remessasReparadas
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-reparados.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos reparados');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
