<?php
/**
 * INSIGHTS - QUALIDADE
 * Análise inteligente da área de qualidade
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
    
    $insights = [];
    
    // =============================================
    // INSIGHT 1: Reprovação Crítica
    // =============================================
    $query = "SELECT 
                SUM(quantidade) AS total,
                SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS reprovados
              FROM qualidade_registro
              WHERE data_inicio_qualidade >= :inicio 
                AND data_inicio_qualidade <= :fim";
    
    $params = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $query .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = (int)($row['total'] ?? 0);
    $reprovados = (int)($row['reprovados'] ?? 0);
    $taxaReprovacao = $total > 0 ? ($reprovados / $total) * 100 : 0;
    
    if ($taxaReprovacao > 10) {
        $insights[] = [
            'tipo' => 'critical',
            'titulo' => '🚨 Taxa de Reprovação Crítica',
            'descricao' => sprintf(
                'Taxa de reprovação de %.1f%% (acima de 10%%). %d equipamentos reprovados de %d analisados.',
                $taxaReprovacao,
                $reprovados,
                $total
            ),
            'acao' => 'Revisar processos de reparo e identificar causas principais de reprovação.'
        ];
    }
    
    // =============================================
    // INSIGHT 2: Gargalo em Qualidade
    // =============================================
    $queryBacklog = "SELECT 
                       SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS backlog
                     FROM qualidade_registro
                     WHERE data_inicio_qualidade >= :inicio 
                       AND data_inicio_qualidade <= :fim";
    
    $stmt = $db->prepare($queryBacklog . ($setor ? " AND setor = :setor" : "") . ($operador ? " AND operador = :operador" : ""));
    $stmt->execute($params);
    $backlog = (int)($stmt->fetchColumn() ?? 0);
    
    $queryTempo = "SELECT 
                     AVG(DATEDIFF(
                       COALESCE(data_envio_expedicao, CURDATE()), 
                       data_inicio_qualidade
                     )) AS tempo_medio
                   FROM qualidade_registro
                   WHERE data_inicio_qualidade >= :inicio 
                     AND data_inicio_qualidade <= :fim
                     AND COALESCE(quantidade_parcial, 0) > 0";
    
    $stmt = $db->prepare($queryTempo . ($setor ? " AND setor = :setor" : "") . ($operador ? " AND operador = :operador" : ""));
    $stmt->execute($params);
    $tempoMedio = (float)($stmt->fetchColumn() ?? 0);
    
    // Critério: backlog > 100 E tempo > 5 dias
    if ($backlog > 100 && $tempoMedio > 5) {
        $tipo = $backlog > 200 ? 'critical' : 'warning';
        $insights[] = [
            'tipo' => $tipo,
            'titulo' => '⚠️ Gargalo Identificado em Qualidade',
            'descricao' => sprintf(
                'Backlog de %d equipamentos aguardando análise com tempo médio de %.1f dias.',
                $backlog,
                $tempoMedio
            ),
            'acao' => 'Considerar alocar mais recursos ou priorizar lotes com maior impacto.'
        ];
    }
    
    // =============================================
    // INSIGHT 3: Qualidade Saudável
    // =============================================
    $taxaAprovacao = $total > 0 ? (($total - $reprovados) / $total) * 100 : 0;
    
    if ($taxaAprovacao >= 95 && $tempoMedio <= 3) {
        $insights[] = [
            'tipo' => 'success',
            'titulo' => '✅ Processo de Qualidade Saudável',
            'descricao' => sprintf(
                'Taxa de aprovação de %.1f%% com tempo médio de %.1f dias. Processo estável e eficiente.',
                $taxaAprovacao,
                $tempoMedio
            ),
            'acao' => 'Manter padrões atuais e documentar boas práticas.'
        ];
    }
    
    // =============================================
    // FALLBACK: Operação Normal
    // =============================================
    if (empty($insights)) {
        $insights[] = [
            'tipo' => 'info',
            'titulo' => 'ℹ️ Operação Normal',
            'descricao' => sprintf(
                'Taxa de aprovação de %.1f%% com tempo médio de %.1f dias. Nenhuma anomalia detectada.',
                $taxaAprovacao,
                $tempoMedio
            ),
            'acao' => 'Continue monitorando os indicadores para identificar tendências.'
        ];
    }
    
    sendSuccess(['insights' => $insights]);
    
} catch (Exception $e) {
    error_log("Erro em insights-qualidade.php: " . $e->getMessage());
    sendError("Erro ao gerar insights de qualidade: " . $e->getMessage(), 500);
}
