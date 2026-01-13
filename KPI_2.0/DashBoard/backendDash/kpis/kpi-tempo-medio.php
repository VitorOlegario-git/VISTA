<?php
/**
 * 🔵 ENDPOINT TIPO A - KPI GLOBAL: TEMPO MÉDIO
 * 
 * Retorna tempo médio de processamento no período (em minutos).
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

    // Constrói WHERE para recebimento
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento',
        'operador'
    );

    // Calcula tempo médio considerando todo o fluxo
    // (da entrada no recebimento até a saída final)
    // JOIN usando cnpj e nota_fiscal como chave composta
    $sql = "
        SELECT 
            AVG(
                TIMESTAMPDIFF(MINUTE, 
                    r.data_recebimento, 
                    COALESCE(e.data_envio_cliente, NOW())
                )
            ) as tempo_medio_minutos
        FROM recebimentos r
        LEFT JOIN expedicao_registro e ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
        {$whereInfo['where']}
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();

    $valorAtual = round((float)($row['tempo_medio_minutos'] ?? 0));

    // 🎯 KPI 3.0: BUSCAR VALOR DE REFERÊNCIA (média últimos 30 dias)
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataInicio . ' -30 days'));
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    
    $sqlReferencia = "
        SELECT 
            AVG(
                TIMESTAMPDIFF(MINUTE, 
                    r.data_recebimento, 
                    COALESCE(e.data_envio_cliente, NOW())
                )
            ) as tempo_medio_minutos
        FROM recebimentos r
        LEFT JOIN expedicao_registro e ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
        WHERE r.data_recebimento BETWEEN ? AND ?
    ";
    
    $stmtRef = $conn->prepare($sqlReferencia);
    $stmtRef->bind_param('ss', $dataReferenciaInicio, $dataReferenciaFim);
    $stmtRef->execute();
    $resultRef = $stmtRef->get_result();
    $rowRef = $resultRef->fetch_assoc();
    $valorReferencia = round((float)($rowRef['tempo_medio_minutos'] ?? 0));

    // SLA: 7200 minutos (5 dias)
    $sla = 7200;
    
    // 🎯 KPI 3.0: ESTADO ESPECIAL (baseado em SLA e variação)
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    if ($valorAtual > $sla) {
        $estado = 'critical'; // Acima do SLA
    } elseif ($valorAtual > $sla * 0.8) {
        $estado = 'warning'; // Próximo do SLA
    } else {
        $estado = definirEstadoInvertido($variacao, [10, 25]); // Aumento é ruim
    }

    // Converte para formato legível
    $unidade = 'minutos';
    $valorExibicao = $valorAtual;
    
    if ($valorAtual >= 60) {
        $valorExibicao = round($valorAtual / 60, 1);
        $unidade = 'horas';
    }

    $kpi = montarKpiRefinado(
        $valorExibicao,
        round($valorReferencia / ($unidade === 'horas' ? 60 : 1), 1),
        $unidade,
        'Tempo médio',
        'media_30d',
        $estado
    );

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    error_log("Erro em kpi-tempo-medio.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular tempo médio');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
