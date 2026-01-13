<?php
/**
 * 🔵 ENDPOINT TIPO A - KPI GLOBAL: SEM CONSERTO
 * 
 * Retorna quantidade de equipamentos sem conserto no período.
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

    // Conta equipamentos com "sem conserto" nas observações
    // JOIN usando cnpj e nota_fiscal
    $sql = "
        SELECT COUNT(DISTINCT r.id) as sem_conserto
        FROM recebimentos r
        LEFT JOIN qualidade_registro q ON r.cnpj = q.cnpj AND r.nota_fiscal = q.nota_fiscal
        {$whereInfo['where']}
        AND q.observacoes LIKE '%sem conserto%'
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();

    $valorAtual = (int)($row['sem_conserto'] ?? 0);

    // 🎯 KPI 3.0: BUSCAR VALOR DE REFERÊNCIA (média últimos 30 dias)
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataInicio . ' -30 days'));
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    
    $sqlReferencia = "
        SELECT COUNT(DISTINCT r.id) / 30 as media
        FROM recebimentos r
        LEFT JOIN qualidade_registro q ON r.cnpj = q.cnpj AND r.nota_fiscal = q.nota_fiscal
        WHERE r.data_recebimento BETWEEN ? AND ?
        AND q.observacoes LIKE '%sem conserto%'
    ";
    
    $stmtRef = $conn->prepare($sqlReferencia);
    $stmtRef->bind_param('ss', $dataReferenciaInicio, $dataReferenciaFim);
    $stmtRef->execute();
    $resultRef = $stmtRef->get_result();
    $rowRef = $resultRef->fetch_assoc();
    $mediaReferencia = (float)($rowRef['media'] ?? 0);
    
    // Ajusta referência para período selecionado
    $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400 + 1;
    $valorReferencia = round($mediaReferencia * $diasPeriodo);

    // 🎯 KPI 3.0: ESTADO INVERTIDO (aumento é ruim)
    $variacao = calcularVariacao($valorAtual, $valorReferencia);
    if ($variacao > 25) {
        $estado = 'critical'; // Aumento muito acima da média
    } elseif ($variacao > 10) {
        $estado = 'warning'; // Aumento moderado
    } else {
        $estado = 'success'; // Dentro do esperado
    }

    $kpi = montarKpiRefinado(
        $valorAtual,
        $valorReferencia,
        'equipamentos',
        'Sem conserto',
        'media_30d',
        $estado
    );

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    error_log("Erro em kpi-sem-conserto.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular equipamentos sem conserto');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
