<?php
/**
 * Insights Automáticos - Recebimento
 * Gera insights inteligentes baseados em dados do período
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
    // INSIGHT 1: GARGALO DE BACKLOG
    // ========================================
    $sqlBacklog = "
        SELECT 
            COUNT(*) AS total_pendente,
            AVG(DATEDIFF(CURDATE(), data_entrada)) AS dias_medio_espera
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.id IS NULL
    ";
    
    $paramsBacklog = [$dataInicioSQL, $dataFimSQL];
    if ($setor) {
        $sqlBacklog .= " AND r.setor = ?";
        $paramsBacklog[] = $setor;
    }
    if ($operador) {
        $sqlBacklog .= " AND r.operador_recebimento = ?";
        $paramsBacklog[] = $operador;
    }

    $stmtBacklog = $conn->prepare($sqlBacklog);
    $stmtBacklog->execute($paramsBacklog);
    $backlogData = $stmtBacklog->fetch(PDO::FETCH_ASSOC);

    if ($backlogData['total_pendente'] > 50) {
        $insights[] = [
            'categoria' => 'gargalo',
            'tipo' => 'warning',
            'titulo' => 'Backlog Acima do Ideal',
            'mensagem' => sprintf(
                '%d remessas ainda não foram enviadas para análise, com tempo médio de espera de %.1f dias.',
                $backlogData['total_pendente'],
                $backlogData['dias_medio_espera'] ?? 0
            ),
            'causa' => 'Possível sobrecarga operacional ou falta de priorização',
            'acao' => 'Revisar prioridades e alocar recursos adicionais para processamento'
        ];
    }

    // ========================================
    // INSIGHT 2: EFICIÊNCIA POR OPERADOR
    // ========================================
    $sqlOperadorLento = "
        SELECT 
            r.operador_recebimento,
            AVG(DATEDIFF(ar.data_analise, r.data_entrada)) AS tempo_medio,
            COUNT(*) AS total_processado
        FROM recebimentos r
        INNER JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.data_analise IS NOT NULL
        GROUP BY r.operador_recebimento
        HAVING COUNT(*) >= 5
        ORDER BY tempo_medio DESC
        LIMIT 1
    ";

    $paramsOp = [$dataInicioSQL, $dataFimSQL];
    if ($setor) {
        $sqlOperadorLento .= " AND r.setor = ?";
        $paramsOp[] = $setor;
    }

    $stmtOp = $conn->prepare($sqlOperadorLento);
    $stmtOp->execute($paramsOp);
    $opLento = $stmtOp->fetch(PDO::FETCH_ASSOC);

    if ($opLento && $opLento['tempo_medio'] > 3) {
        $insights[] = [
            'categoria' => 'eficiencia',
            'tipo' => 'info',
            'titulo' => 'Diferença de Desempenho Entre Operadores',
            'mensagem' => sprintf(
                'Operador "%s" apresenta tempo médio de %.1f dias para envio à análise.',
                $opLento['operador_recebimento'],
                $opLento['tempo_medio']
            ),
            'causa' => 'Possível necessidade de treinamento ou carga de trabalho desbalanceada',
            'acao' => 'Revisar distribuição de tarefas e considerar capacitação adicional'
        ];
    }

    // ========================================
    // INSIGHT 3: CRESCIMENTO DE VOLUME
    // ========================================
    $diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / 86400;
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicioSQL -" . ($diasPeriodo + 1) . " days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicioSQL -1 day"));

    $sqlAtual = "SELECT SUM(quantidade) AS total FROM recebimentos WHERE data_entrada >= ? AND data_entrada <= ?";
    $sqlAnterior = "SELECT SUM(quantidade) AS total FROM recebimentos WHERE data_entrada >= ? AND data_entrada <= ?";

    $stmtAtual = $conn->prepare($sqlAtual);
    $stmtAtual->execute([$dataInicioSQL, $dataFimSQL]);
    $totalAtual = (int)($stmtAtual->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmtAnterior = $conn->prepare($sqlAnterior);
    $stmtAnterior->execute([$dataInicioRef, $dataFimRef]);
    $totalAnterior = (int)($stmtAnterior->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    if ($totalAnterior > 0) {
        $crescimento = (($totalAtual - $totalAnterior) / $totalAnterior) * 100;
        
        if ($crescimento >= 20) {
            $insights[] = [
                'categoria' => 'crescimento',
                'tipo' => 'success',
                'titulo' => 'Aumento Significativo no Volume',
                'mensagem' => sprintf(
                    'Volume de recebimentos cresceu %.1f%% em relação ao período anterior (%d → %d equipamentos).',
                    $crescimento,
                    $totalAnterior,
                    $totalAtual
                ),
                'causa' => null,
                'acao' => 'Validar capacidade operacional para sustentar o crescimento'
            ];
        } elseif ($crescimento <= -20) {
            $insights[] = [
                'categoria' => 'crescimento',
                'tipo' => 'warning',
                'titulo' => 'Queda no Volume de Recebimentos',
                'mensagem' => sprintf(
                    'Volume reduziu %.1f%% em relação ao período anterior (%d → %d equipamentos).',
                    abs($crescimento),
                    $totalAnterior,
                    $totalAtual
                ),
                'causa' => 'Possível redução de demanda ou sazonalidade',
                'acao' => 'Investigar causas e ajustar planejamento operacional'
            ];
        }
    }

    // ========================================
    // INSIGHT 4: OPERAÇÕES BEM-SUCEDIDAS
    // ========================================
    if (empty($insights)) {
        $insights[] = [
            'categoria' => 'operacao',
            'tipo' => 'success',
            'titulo' => 'Operação Dentro da Normalidade',
            'mensagem' => 'Os indicadores de recebimento estão dentro dos parâmetros esperados.',
            'causa' => null,
            'acao' => null
        ];
    }

    sendSuccess($insights);

} catch (Exception $e) {
    error_log("Erro em insights-recebimento.php: " . $e->getMessage());
    sendError('Erro ao gerar insights: ' . $e->getMessage(), 500);
}
?>
