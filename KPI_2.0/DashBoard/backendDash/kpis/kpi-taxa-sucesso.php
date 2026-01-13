<?php
/**
 * 🔵 ENDPOINT TIPO A - KPI GLOBAL: TAXA DE SUCESSO
 * 
 * Retorna percentual de equipamentos reparados com sucesso (%).
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

    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento',
        'operador'
    );

    // Conta total processado
    $sqlTotal = "
        SELECT COUNT(*) as total
        FROM recebimentos r
        {$whereInfo['where']}
    ";

    $resultTotal = executarQuery($conn, $sqlTotal, $whereInfo['params'], $whereInfo['types']);
    $rowTotal = $resultTotal->fetch_assoc();
    $total = (int)($rowTotal['total'] ?? 0);

    if ($total == 0) {
        // Se não há dados, retorna 0%
        $kpi = formatarKPI(
            0,
            '%',
            formatarPeriodoMeta($dataInicio, $dataFim),
            'Taxa de sucesso',
            [
                'icone' => 'fa-check-circle',
                'cor' => '#10b981',
                'total' => 0,
                'reparados' => 0
            ]
        );
        enviarSucesso($kpi, $dataInicio, $dataFim, $operador);
    }

    // Conta reparados com sucesso (verificando se chegou até expedicao)
    // JOIN usando cnpj e nota_fiscal
    $sqlSucesso = "
        SELECT COUNT(DISTINCT r.id) as reparados
        FROM recebimentos r
        LEFT JOIN qualidade_registro q ON r.cnpj = q.cnpj AND r.nota_fiscal = q.nota_fiscal
        LEFT JOIN expedicao_registro e ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
        {$whereInfo['where']}
        AND e.data_envio_cliente IS NOT NULL
    ";

    $resultSucesso = executarQuery($conn, $sqlSucesso, $whereInfo['params'], $whereInfo['types']);
    $rowSucesso = $resultSucesso->fetch_assoc();
    $reparados = (int)($rowSucesso['reparados'] ?? 0);

    $valorAtual = round(($reparados / $total) * 100, 1);

    // 🎯 KPI 3.0: BUSCAR VALOR DE REFERÊNCIA (média últimos 30 dias)
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataInicio . ' -30 days'));
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    
    $sqlTotalRef = "
        SELECT COUNT(*) as total
        FROM recebimentos r
        WHERE r.data_recebimento BETWEEN ? AND ?
    ";
    
    $stmtTotalRef = $conn->prepare($sqlTotalRef);
    $stmtTotalRef->bind_param('ss', $dataReferenciaInicio, $dataReferenciaFim);
    $stmtTotalRef->execute();
    $resultTotalRef = $stmtTotalRef->get_result();
    $rowTotalRef = $resultTotalRef->fetch_assoc();
    $totalRef = (int)($rowTotalRef['total'] ?? 0);
    
    $sqlSucessoRef = "
        SELECT COUNT(DISTINCT r.id) as reparados
        FROM recebimentos r
        LEFT JOIN expedicao_registro e ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
        WHERE r.data_recebimento BETWEEN ? AND ?
        AND e.data_envio_cliente IS NOT NULL
    ";
    
    $stmtSucessoRef = $conn->prepare($sqlSucessoRef);
    $stmtSucessoRef->bind_param('ss', $dataReferenciaInicio, $dataReferenciaFim);
    $stmtSucessoRef->execute();
    $resultSucessoRef = $stmtSucessoRef->get_result();
    $rowSucessoRef = $resultSucessoRef->fetch_assoc();
    $reparadosRef = (int)($rowSucessoRef['reparados'] ?? 0);
    
    $valorReferencia = $totalRef > 0 ? round(($reparadosRef / $totalRef) * 100, 1) : 0;

    // 🎯 KPI 3.0: ESTADO ESPECIAL (baseado em meta)
    // Meta: ≥85% → success, 70-84% → warning, <70% → critical
    if ($valorAtual >= 85) {
        $estado = 'success';
    } elseif ($valorAtual >= 70) {
        $estado = 'warning';
    } else {
        $estado = 'critical';
    }

    $kpi = montarKpiRefinado(
        $valorAtual,
        $valorReferencia,
        '%',
        'Taxa de sucesso',
        'media_30d',
        $estado
    );

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-sucesso.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular taxa de sucesso');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
