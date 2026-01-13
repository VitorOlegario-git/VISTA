<?php
/**
 * 🔵 ENDPOINT TIPO A - KPI GLOBAL: VALOR ORÇADO
 * 
 * Retorna valor total orçado no período (R$).
 * Depende de data (obrigatório aplicar filtro).
 */

// Configuração de erros (TEMPORÁRIO PARA DEBUG)
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
extract($params);

try {
    if (!$dataInicio || !$dataFim) {
        enviarErro(400, 'Período (inicio e fim) é obrigatório para este KPI');
    }

    // WHERE para análise (onde são gerados orçamentos)
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_envio_orcamento', // data de envio do orçamento
        'operador'
    );

    // Soma valores de orçamentos gerados
    // Tabela analise_parcial contém os orçamentos
    $sql = "
        SELECT 
            COALESCE(SUM(valor_orcamento), 0) as valor_total
        FROM analise_parcial
        {$whereInfo['where']}
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();

    $valorAtual = (float)($row['valor_total'] ?? 0);

    // 🎯 KPI 3.0: BUSCAR VALOR DE REFERÊNCIA (período anterior)
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataReferenciaFim . ' -' . ($diasPeriodo - 1) . ' days'));
    
    $sqlReferencia = "
        SELECT 
            COALESCE(SUM(valor_orcamento), 0) as valor_total
        FROM analise_parcial
        WHERE data_envio_orcamento BETWEEN ? AND ?
        AND valor_orcamento IS NOT NULL
        AND valor_orcamento > 0
    ";
    
    $stmtRef = $conn->prepare($sqlReferencia);
    $stmtRef->bind_param('ss', $dataReferenciaInicio, $dataReferenciaFim);
    $stmtRef->execute();
    $resultRef = $stmtRef->get_result();
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = (float)($rowRef['valor_total'] ?? 0);

    // 🎯 KPI 3.0: ESTADO ESPECIAL (queda é ruim)
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    if ($variacao < -25) {
        $estado = 'critical'; // Queda acentuada
    } elseif ($variacao < -10) {
        $estado = 'warning'; // Queda moderada
    } else {
        $estado = 'success'; // Estável ou crescimento
    }

    // Formata valores para exibição
    $valorFormatado = number_format($valorAtual, 2, ',', '.');
    $valorReferenciaFormatado = number_format($valorReferencia, 2, ',', '.');

    $kpi = [
        'valor' => $valorFormatado,
        'unidade' => 'R$',
        'periodo' => 'Período selecionado',
        'contexto' => 'Valor orçado',
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $valorReferenciaFormatado
        ],
        'variacao' => [
            'percentual' => $variacao,
            'direcao' => definirDirecao($variacao)
        ],
        'estado' => $estado
    ];

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    error_log("Erro em kpi-valor-orcado.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular valor orçado');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
