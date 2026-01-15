<?php
/**
 * 📦 KPI 2: EQUIPAMENTOS RECEBIDOS
 * 
 * Retorna SUM(quantidade) de equipamentos (não remessas) recebidos no período.
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

    // WHERE com filtros globais
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento',
        'operador',
        $setor,
        'setor'
    );

    // Query principal - SUM de equipamentos
    $sql = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM recebimentos
        {$whereInfo['where']}
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (int)($row['total'] ?? 0);

    // Valor de referência (período anterior igual)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    $whereRefInfo = construirWherePadrao(
        $dataReferenciaInicio,
        $dataReferenciaFim,
        $operador,
        'data_recebimento',
        'operador',
        $setor,
        'setor'
    );
    
    $sqlReferencia = "
        SELECT COALESCE(SUM(quantidade), 0) as total
        FROM recebimentos
        {$whereRefInfo['where']}
    ";
    
    $resultRef = executarQuery($conn, $sqlReferencia, $whereRefInfo['params'], $whereRefInfo['types']);
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = (int)($rowRef['total'] ?? 0);

    // Calcula variação
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    $estado = definirEstado($variacao, [10, 25]);

    // Monta resposta
    $kpi = [
        'valor' => $valorAtual,
        'unidade' => 'equipamentos',
        'titulo' => 'Equipamentos Recebidos',
        'periodo' => 'Período selecionado',
        'contexto' => 'Volume físico de equipamentos',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferencia,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'media_dia' => $diasPeriodo > 0 ? round($valorAtual / $diasPeriodo, 1) : 0
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-recebidos.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos recebidos');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
