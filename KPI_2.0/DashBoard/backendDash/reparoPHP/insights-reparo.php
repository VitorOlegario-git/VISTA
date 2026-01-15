<?php
/**
 * INSIGHTS AUTOMÁTICOS - ÁREA DE REPARO
 * Gera até 3 insights baseados nos KPIs de reparo
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../BackEnd/conexao.php';
require_once '../../BackEnd/endpoint-helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parâmetros de filtro
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : null;
    $fim = isset($_GET['fim']) ? $_GET['fim'] : null;
    $setor = isset($_GET['setor']) ? $_GET['setor'] : null;
    $operador = isset($_GET['operador']) ? $_GET['operador'] : null;
    
    if (!$inicio || !$fim) {
        sendError("Parâmetros 'inicio' e 'fim' são obrigatórios", 400);
    }
    
    // Converter datas
    $dataInicio = DateTime::createFromFormat('d/m/Y', $inicio);
    $dataFim = DateTime::createFromFormat('d/m/Y', $fim);
    
    if (!$dataInicio || !$dataFim) {
        sendError("Formato de data inválido. Use DD/MM/YYYY", 400);
    }
    
    $inicioSQL = $dataInicio->format('Y-m-d');
    $fimSQL = $dataFim->format('Y-m-d');
    
    $insights = [];
    
    // ============================================
    // INSIGHT 1: GARGALO TÉCNICO CRÍTICO
    // Condição: backlog ↑ + tempo médio ↑
    // ============================================
    
    // Calcular backlog atual e anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryBacklogAtual = "SELECT SUM(quantidade_total - COALESCE(quantidade_reparada, 0)) AS backlog
                          FROM reparo_resumo
                          WHERE data_registro >= :inicio AND data_registro <= :fim";
    
    $queryBacklogAnterior = "SELECT SUM(quantidade_total - COALESCE(quantidade_reparada, 0)) AS backlog
                             FROM reparo_resumo
                             WHERE data_registro >= :inicio_ant AND data_registro <= :fim_ant";
    
    $paramsAtual = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    $paramsAnterior = [
        ':inicio_ant' => $periodoAnteriorInicio->format('Y-m-d'),
        ':fim_ant' => $periodoAnteriorFim->format('Y-m-d')
    ];
    
    if ($setor) {
        $queryBacklogAtual .= " AND setor = :setor";
        $queryBacklogAnterior .= " AND setor = :setor";
        $paramsAtual[':setor'] = $setor;
        $paramsAnterior[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryBacklogAtual .= " AND operador = :operador";
        $queryBacklogAnterior .= " AND operador = :operador";
        $paramsAtual[':operador'] = $operador;
        $paramsAnterior[':operador'] = $operador;
    }
    
    $stmtAtual = $db->prepare($queryBacklogAtual);
    $stmtAtual->execute($paramsAtual);
    $backlogAtual = (int)$stmtAtual->fetchColumn();
    
    $stmtAnterior = $db->prepare($queryBacklogAnterior);
    $stmtAnterior->execute($paramsAnterior);
    $backlogAnterior = (int)$stmtAnterior->fetchColumn();
    
    $variacaoBacklog = $backlogAnterior > 0 ? (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100 : 0;
    
    // Tempo médio
    $queryTempo = "SELECT AVG(DATEDIFF(CURDATE(), data_registro)) AS tempo_medio
                   FROM reparo_resumo
                   WHERE data_registro >= :inicio AND data_registro <= :fim
                     AND quantidade_reparada > 0";
    
    $paramsTempo = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $queryTempo .= " AND setor = :setor";
        $paramsTempo[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryTempo .= " AND operador = :operador";
        $paramsTempo[':operador'] = $operador;
    }
    
    $stmtTempo = $db->prepare($queryTempo);
    $stmtTempo->execute($paramsTempo);
    $tempoMedio = (float)$stmtTempo->fetchColumn();
    
    if ($variacaoBacklog >= 20 && $tempoMedio > 5) {
        $insights[] = [
            'categoria' => 'Gargalo Técnico',
            'tipo' => 'critical',
            'titulo' => 'Gargalo Técnico Crítico Detectado',
            'mensagem' => "Backlog aumentou {$variacaoBacklog}% e tempo médio está em " . number_format($tempoMedio, 1) . " dias",
            'causa' => 'Capacidade técnica insuficiente para o volume de entrada',
            'acao' => 'Reforçar equipe técnica ou redistribuir cargas entre operadores'
        ];
    }
    
    // ============================================
    // INSIGHT 2: BAIXA CONVERSÃO TÉCNICA
    // Condição: taxa < 60%
    // ============================================
    
    $queryConversao = "SELECT 
                        SUM(COALESCE(quantidade_reparada, 0)) AS reparado,
                        (SELECT SUM(COALESCE(quantidade_analisada, 0)) 
                         FROM analise_resumo 
                         WHERE data_inicio_analise >= :inicio AND data_inicio_analise <= :fim) AS analisado
                       FROM reparo_resumo
                       WHERE data_registro >= :inicio AND data_registro <= :fim";
    
    $paramsConv = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $queryConversao .= " AND setor = :setor";
        $paramsConv[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryConversao .= " AND operador = :operador";
        $paramsConv[':operador'] = $operador;
    }
    
    $stmtConv = $db->prepare($queryConversao);
    $stmtConv->execute($paramsConv);
    $resultConv = $stmtConv->fetch(PDO::FETCH_ASSOC);
    
    $reparado = (int)$resultConv['reparado'];
    $analisado = (int)$resultConv['analisado'];
    $taxaConversao = $analisado > 0 ? ($reparado / $analisado) * 100 : 0;
    
    if ($taxaConversao < 60 && count($insights) < 3) {
        $tipo = $taxaConversao < 50 ? 'critical' : 'warning';
        $insights[] = [
            'categoria' => 'Conversão Técnica',
            'tipo' => $tipo,
            'titulo' => 'Baixa Conversão de Análise para Reparo',
            'mensagem' => "Apenas " . number_format($taxaConversao, 1) . "% dos equipamentos analisados estão sendo reparados",
            'causa' => 'Alto índice de inviabilidade técnica ou rejeição de orçamentos',
            'acao' => 'Revisar critérios de aceite na análise ou política de orçamento'
        ];
    }
    
    // ============================================
    // INSIGHT 3: OPERADOR LENTO
    // Tempo médio > 7 dias
    // ============================================
    
    $queryOperadorLento = "SELECT 
                            operador,
                            AVG(DATEDIFF(CURDATE(), data_registro)) AS tempo_medio,
                            COUNT(*) AS total_reparos
                          FROM reparo_resumo
                          WHERE data_registro >= :inicio AND data_registro <= :fim
                            AND quantidade_reparada > 0
                            AND operador IS NOT NULL";
    
    $paramsOp = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $queryOperadorLento .= " AND setor = :setor";
        $paramsOp[':setor'] = $setor;
    }
    
    $queryOperadorLento .= " GROUP BY operador
                             HAVING COUNT(*) >= 5 AND AVG(DATEDIFF(CURDATE(), data_registro)) > 7
                             ORDER BY tempo_medio DESC
                             LIMIT 1";
    
    $stmtOp = $db->prepare($queryOperadorLento);
    $stmtOp->execute($paramsOp);
    $operadorLento = $stmtOp->fetch(PDO::FETCH_ASSOC);
    
    if ($operadorLento && count($insights) < 3) {
        $insights[] = [
            'categoria' => 'Eficiência Operacional',
            'tipo' => 'warning',
            'titulo' => 'Operador com Tempo Acima do Esperado',
            'mensagem' => "Operador {$operadorLento['operador']} está com tempo médio de " . 
                         number_format($operadorLento['tempo_medio'], 1) . " dias",
            'causa' => 'Possível sobrecarga ou necessidade de capacitação técnica',
            'acao' => 'Revisar distribuição de tarefas e considerar treinamento adicional'
        ];
    }
    
    // ============================================
    // INSIGHT 4 (FALLBACK): REPARO SAUDÁVEL
    // Condição: taxa ≥ 80% + backlog ↓
    // ============================================
    
    if (empty($insights) && $taxaConversao >= 80 && $variacaoBacklog <= 0) {
        $insights[] = [
            'categoria' => 'Operação Saudável',
            'tipo' => 'success',
            'titulo' => 'Reparo Operando Dentro do Padrão',
            'mensagem' => "Taxa de conversão em " . number_format($taxaConversao, 1) . "% e backlog estável ou em redução",
            'causa' => null,
            'acao' => 'Manter padrão operacional atual'
        ];
    }
    
    // Fallback final
    if (empty($insights)) {
        $insights[] = [
            'categoria' => 'Operação Normal',
            'tipo' => 'info',
            'titulo' => 'Reparo Dentro da Normalidade',
            'mensagem' => 'Nenhuma exceção crítica detectada no período selecionado',
            'causa' => null,
            'acao' => 'Continuar monitoramento regular'
        ];
    }
    
    sendSuccess($insights);
    
} catch (Exception $e) {
    error_log("Erro em insights-reparo.php: " . $e->getMessage());
    sendError("Erro ao gerar insights: " . $e->getMessage(), 500);
}
