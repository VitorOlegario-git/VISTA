<?php
/**
 * KPI REPARO 5: VALOR ORÇADO NO REPARO
 * 
 * Retorna SUM(valor_orcamento) de reparo_resumo.
 * Total de valor orçado gerado pela área de reparo.
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

    // Query principal - SUM de valor orçado
    $sql = "
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM reparo_resumo
        {$whereInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (float)($row['total'] ?? 0);

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
        SELECT COALESCE(SUM(valor_orcamento), 0) as total
        FROM reparo_resumo
        {$whereRefInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";
    
    $resultRef = executarQuery($conn, $sqlReferencia, $whereRefInfo['params'], $whereRefInfo['types']);
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = (float)($rowRef['total'] ?? 0);

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

    // Valor médio por equipamento reparado
    $sqlEquip = "
        SELECT COALESCE(SUM(quantidade_reparada), 0) as total
        FROM reparo_resumo
        {$whereInfo['where']}
    ";
    $resultEquip = executarQuery($conn, $sqlEquip, $whereInfo['params'], $whereInfo['types']);
    $rowEquip = $resultEquip->fetch_assoc();
    $equipamentosReparados = (int)($rowEquip['total'] ?? 0);
    
    $valorMedioPorEquip = $equipamentosReparados > 0 
        ? number_format($valorAtual / $equipamentosReparados, 2, ',', '.') 
        : '0,00';

    // Monta resposta
    $kpi = [
        'valor' => $valorFormatado,
        'unidade' => 'R$',
        'titulo' => 'Valor Orçado no Reparo',
        'periodo' => 'Período selecionado',
        'contexto' => 'Total orçado pelo reparo',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferenciaFormatado,
            'variacao' => $variacao,
            'estado' => $estado
        ],
        'detalhes' => [
            'valor_medio_equip' => $valorMedioPorEquip,
            'equipamentos_reparados' => $equipamentosReparados
        ]
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador, $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-valor-orcado-reparo.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular valor orçado no reparo');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
