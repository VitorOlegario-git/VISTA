<?php
/**
 * Insights Automáticos - Análise
 * Gera insights inteligentes baseados em dados da análise
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../Database.php';
require_once __DIR__ . '/../../endpoint-helpers.php';

try {
    $dataInicio = $_GET['inicio'] ?? null;
    $dataFim = $_GET['fim'] ?? null;
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    if (!$dataInicio || !$dataFim) {
        sendError('Parâmetros inicio e fim são obrigatórios', 400);
    }

    $dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
    $dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));

    $db = Database::getInstance();
    $conn = $db->getConnection();

    $insights = [];

    // ========================================
    // INSIGHT 1: GARGALO DE ANÁLISE
    // ========================================
    $sqlBacklog = "
        SELECT 
            SUM(quantidade_total - COALESCE(quantidade_analisada, 0)) AS backlog_total,
            AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) AS tempo_medio
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";
    
    $paramsBacklog = [$dataInicioSQL, $dataFimSQL];
    if ($setor) {
        $sqlBacklog .= " AND setor = ?";
        $paramsBacklog[] = $setor;
    }
    if ($operador) {
        $sqlBacklog .= " AND operador_analise = ?";
        $paramsBacklog[] = $operador;
    }

    $stmtBacklog = $conn->prepare($sqlBacklog);
    $stmtBacklog->execute($paramsBacklog);
    $backlogData = $stmtBacklog->fetch(PDO::FETCH_ASSOC);

    // Calcular variação do backlog
    $diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / 86400;
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicioSQL -" . ($diasPeriodo + 1) . " days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicioSQL -1 day"));

    $sqlBacklogRef = "
        SELECT SUM(quantidade_total - COALESCE(quantidade_analisada, 0)) AS backlog_total
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
    ";
    $paramsBacklogRef = [$dataInicioRef, $dataFimRef];
    if ($setor) {
        $sqlBacklogRef .= " AND setor = ?";
        $paramsBacklogRef[] = $setor;
    }
    if ($operador) {
        $sqlBacklogRef .= " AND operador_analise = ?";
        $paramsBacklogRef[] = $operador;
    }

    $stmtBacklogRef = $conn->prepare($sqlBacklogRef);
    $stmtBacklogRef->execute($paramsBacklogRef);
    $backlogAnterior = (int)($stmtBacklogRef->fetch(PDO::FETCH_ASSOC)['backlog_total'] ?? 0);
    $backlogAtual = (int)($backlogData['backlog_total'] ?? 0);

    if ($backlogAtual > 0 && $backlogAnterior > 0) {
        $variacaoBacklog = (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100;
        
        if ($variacaoBacklog >= 20 && $backlogData['tempo_medio'] > 3) {
            $insights[] = [
                'categoria' => 'gargalo',
                'tipo' => 'critical',
                'titulo' => 'Gargalo Crítico na Análise',
                'mensagem' => sprintf(
                    'Backlog aumentou %.1f%% (%d equipamentos pendentes) e tempo médio está em %.1f dias.',
                    $variacaoBacklog,
                    $backlogAtual,
                    $backlogData['tempo_medio']
                ),
                'causa' => 'Capacidade insuficiente ou excesso de entrada de equipamentos',
                'acao' => 'Redistribuir operadores ou revisar SLA de análise'
            ];
        }
    }

    // ========================================
    // INSIGHT 2: BAIXA CONVERSÃO
    // ========================================
    $sqlConversao = "
        SELECT 
            SUM(COALESCE(ar.quantidade_analisada, 0)) AS analisado,
            (SELECT SUM(quantidade) FROM recebimentos WHERE data_entrada >= ? AND data_entrada <= ?) AS recebido
        FROM analise_resumo ar
        WHERE ar.data_inicio_analise >= ? AND ar.data_inicio_analise <= ?
    ";
    
    $paramsConversao = [$dataInicioSQL, $dataFimSQL, $dataInicioSQL, $dataFimSQL];
    if ($setor) {
        $sqlConversao .= " AND ar.setor = ?";
        $paramsConversao[] = $setor;
    }

    $stmtConversao = $conn->prepare($sqlConversao);
    $stmtConversao->execute($paramsConversao);
    $conversaoData = $stmtConversao->fetch(PDO::FETCH_ASSOC);

    $totalRecebido = (int)($conversaoData['recebido'] ?? 0);
    $totalAnalisado = (int)($conversaoData['analisado'] ?? 0);

    if ($totalRecebido > 0) {
        $taxaConversao = ($totalAnalisado / $totalRecebido) * 100;
        
        if ($taxaConversao < 70) {
            $tipo = $taxaConversao < 50 ? 'critical' : 'warning';
            $insights[] = [
                'categoria' => 'conversao',
                'tipo' => $tipo,
                'titulo' => 'Taxa de Conversão Abaixo do Esperado',
                'mensagem' => sprintf(
                    'Apenas %.1f%% dos equipamentos recebidos foram analisados (%d de %d).',
                    $taxaConversao,
                    $totalAnalisado,
                    $totalRecebido
                ),
                'causa' => 'Perfil de entrada inadequado ou critérios de triagem inconsistentes',
                'acao' => 'Revisar processo de triagem e regras técnicas de análise'
            ];
        }
    }

    // ========================================
    // INSIGHT 3: OPERADOR LENTO
    // ========================================
    $sqlOperadorLento = "
        SELECT 
            operador_analise,
            AVG(DATEDIFF(data_envio_orcamento, data_inicio_analise)) AS tempo_medio,
            COUNT(*) AS total_processado
        FROM analise_resumo
        WHERE data_inicio_analise >= ? AND data_inicio_analise <= ?
        AND data_envio_orcamento IS NOT NULL
        GROUP BY operador_analise
        HAVING COUNT(*) >= 5
        ORDER BY tempo_medio DESC
        LIMIT 1
    ";

    $stmtOp = $conn->prepare($sqlOperadorLento);
    $stmtOp->execute([$dataInicioSQL, $dataFimSQL]);
    $opLento = $stmtOp->fetch(PDO::FETCH_ASSOC);

    if ($opLento && $opLento['tempo_medio'] > 5) {
        $insights[] = [
            'categoria' => 'tempo',
            'tipo' => 'warning',
            'titulo' => 'Tempo de Análise Elevado',
            'mensagem' => sprintf(
                'Operador "%s" apresenta tempo médio de %.1f dias para análise.',
                $opLento['operador_analise'],
                $opLento['tempo_medio']
            ),
            'causa' => 'Possível necessidade de treinamento ou sobrecarga de trabalho',
            'acao' => 'Revisar distribuição de tarefas e considerar capacitação'
        ];
    }

    // ========================================
    // INSIGHT 4: ANÁLISE SAUDÁVEL
    // ========================================
    if (empty($insights) && $totalRecebido > 0) {
        $taxaConversao = ($totalAnalisado / $totalRecebido) * 100;
        
        if ($taxaConversao >= 85) {
            $insights[] = [
                'categoria' => 'operacao',
                'tipo' => 'success',
                'titulo' => 'Análise Operando com Eficiência',
                'mensagem' => sprintf(
                    'Taxa de conversão em %.1f%% e processos dentro do padrão estabelecido.',
                    $taxaConversao
                ),
                'causa' => null,
                'acao' => 'Manter padrão operacional atual'
            ];
        }
    }

    // Fallback se nenhum insight foi gerado
    if (empty($insights)) {
        $insights[] = [
            'categoria' => 'operacao',
            'tipo' => 'info',
            'titulo' => 'Operação Dentro da Normalidade',
            'mensagem' => 'Os indicadores de análise estão dentro dos parâmetros esperados.',
            'causa' => null,
            'acao' => null
        ];
    }

    sendSuccess($insights);

} catch (Exception $e) {
    error_log("Erro em insights-analise.php: " . $e->getMessage());
    sendError('Erro ao gerar insights: ' . $e->getMessage(), 500);
}
?>
