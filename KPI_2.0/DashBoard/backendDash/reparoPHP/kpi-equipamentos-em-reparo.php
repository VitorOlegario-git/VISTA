<?php
/**
 * KPI REPARO 1: EQUIPAMENTOS EM REPARO (BACKLOG TÉCNICO)
 * 
 * Retorna SUM(quantidade_total - quantidade_reparada) de reparo_resumo.
 * Equipamentos que ainda estão aguardando reparo completo.
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

    // Query - equipamentos ainda em reparo (total - reparados)
    $sql = "
        SELECT 
            COALESCE(SUM(quantidade_total - COALESCE(quantidade_reparada, 0)), 0) as backlog,
            COUNT(*) as remessas_pendentes
        FROM reparo_resumo
        {$whereInfo['where']}
        AND COALESCE(quantidade_reparada, 0) < quantidade_total
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
        'data_registro',
        null,
        $setor,
        'setor'
    );
    
    $sqlReferencia = "
        SELECT COALESCE(SUM(quantidade_total - COALESCE(quantidade_reparada, 0)), 0) as backlog
        FROM reparo_resumo
        {$whereRefInfo['where']}
        AND COALESCE(quantidade_reparada, 0) < quantidade_total
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
        'titulo' => 'Equipamentos em Reparo',
        'periodo' => 'Período selecionado',
        'contexto' => 'Backlog técnico aguardando reparo',
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
    error_log("Erro em kpi-equipamentos-em-reparo.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos em reparo');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
