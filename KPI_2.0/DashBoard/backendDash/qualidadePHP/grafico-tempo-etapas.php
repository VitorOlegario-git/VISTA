<?php
/**
 * GRÁFICO D - TEMPO MÉDIO POR ETAPA
 * Comparativo: Qualidade vs Reparo (tempo médio)
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../BackEnd/conexao.php';
require_once '../../BackEnd/endpoint-helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : null;
    $fim = isset($_GET['fim']) ? $_GET['fim'] : null;
    $setor = isset($_GET['setor']) ? $_GET['setor'] : null;
    $operador = isset($_GET['operador']) ? $_GET['operador'] : null;
    
    if (!$inicio || !$fim) {
        sendError("Parâmetros 'inicio' e 'fim' são obrigatórios", 400);
    }
    
    $dataInicio = DateTime::createFromFormat('d/m/Y', $inicio);
    $dataFim = DateTime::createFromFormat('d/m/Y', $fim);
    
    if (!$dataInicio || !$dataFim) {
        sendError("Formato de data inválido. Use DD/MM/YYYY", 400);
    }
    
    $inicioSQL = $dataInicio->format('Y-m-d');
    $fimSQL = $dataFim->format('Y-m-d');
    
    // Query: Tempo médio em qualidade
    $queryQualidade = "SELECT 
                         AVG(DATEDIFF(
                           COALESCE(data_envio_expedicao, CURDATE()), 
                           data_inicio_qualidade
                         )) AS tempo_qualidade
                       FROM qualidade_registro
                       WHERE data_inicio_qualidade >= :inicio 
                         AND data_inicio_qualidade <= :fim
                         AND COALESCE(quantidade_parcial, 0) > 0";
    
    $params = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $queryQualidade .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryQualidade .= " AND operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $stmt = $db->prepare($queryQualidade);
    $stmt->execute($params);
    $tempoQualidade = (float)($stmt->fetchColumn() ?? 0);
    
    // Query: Tempo médio em reparo (aguardando pagamento)
    $queryReparo = "SELECT 
                      AVG(DATEDIFF(
                        COALESCE(data_pg, CURDATE()), 
                        data_recebimento
                      )) AS tempo_reparo
                    FROM reparo_resumo
                    WHERE data_recebimento >= :inicio 
                      AND data_recebimento <= :fim";
    
    $paramsReparo = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $queryReparo .= " AND setor = :setor";
        $paramsReparo[':setor'] = $setor;
    }
    
    $stmt = $db->prepare($queryReparo);
    $stmt->execute($paramsReparo);
    $tempoReparo = (float)($stmt->fetchColumn() ?? 0);
    
    sendSuccess([
        'labels' => ['Qualidade', 'Reparo'],
        'valores' => [
            round($tempoQualidade, 1),
            round($tempoReparo, 1)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-tempo-etapas.php: " . $e->getMessage());
    sendError("Erro ao buscar tempo por etapas: " . $e->getMessage(), 500);
}
