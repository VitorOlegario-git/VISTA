<?php
/**
 * 🔵 ENDPOINT TIPO A - KPI GLOBAL: TOTAL PROCESSADO
 * 
 * Retorna quantidade total de equipamentos processados no período.
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
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao carregar dependências: ' . $e->getMessage(),
        'file' => __FILE__
    ]);
    exit;
}

// Valida conexão
validarConexao($conn);

// Valida e extrai parâmetros
$params = validarParametrosPadrao();
extract($params); // $dataInicio, $dataFim, $operador

try {
    // Se não há datas, retorna erro (endpoint tipo A requer datas)
    if (!$dataInicio || !$dataFim) {
        enviarErro(400, 'Período (inicio e fim) é obrigatório para este KPI');
    }

    // Constrói WHERE clause
    $whereInfo = construirWherePadrao(
        $dataInicio,
        $dataFim,
        $operador,
        'data_recebimento', // campo de data na tabela recebimentos
        'operador' // campo operador
    );

    // Query principal - VALOR ATUAL
    $sql = "
        SELECT COUNT(*) as total
        FROM recebimentos
        {$whereInfo['where']}
    ";

    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();
    $valorAtual = (int)($row['total'] ?? 0);

    // 🎯 KPI 3.0: BUSCAR VALOR DE REFERÊNCIA (média últimos 30 dias)
    $dataReferenciaInicio = date('Y-m-d', strtotime($dataInicio . ' -30 days'));
    $dataReferenciaFim = date('Y-m-d', strtotime($dataInicio . ' -1 day'));
    
    $sqlReferencia = "
        SELECT COUNT(*) / 30 as media
        FROM recebimentos
        WHERE data_recebimento BETWEEN ? AND ?
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

    // 🎯 KPI 3.0: CALCULAR VARIAÇÃO E ESTADO
    $kpi = montarKpiRefinado(
        $valorAtual,
        $valorReferencia,
        'equipamentos',
        'Total processado',
        'media_30d',
        definirEstado(calcularVariacao($valorAtual, $valorReferencia), [10, 25])
    );

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    error_log("Erro em kpi-total-processado.php: " . $e->getMessage());
    enviarErro(500, 'Erro ao calcular total processado');
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
