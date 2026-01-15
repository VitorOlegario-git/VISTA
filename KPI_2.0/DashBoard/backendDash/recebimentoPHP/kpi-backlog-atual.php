<?php
/**
 * KPI: Backlog Atual
 * 
 * Equipamentos recebidos que ainda não foram enviados para análise.
 * Este endpoint utiliza o contrato padronizado VISTA (kpiResponse).
 * 
 * @version 2.1 - Protegido com middleware em 15/01/2026
 * @uses kpiResponse() - Contrato padronizado
 * @uses validarAutenticacao() - Middleware de segurança
 */

require_once __DIR__ . '/../../../BackEnd/config.php';
require_once __DIR__ . '/../../../BackEnd/Database.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
require_once __DIR__ . '/../../../BackEnd/auth-middleware.php';

// ============================================
// VALIDAÇÃO DE AUTENTICAÇÃO
// ============================================
validarAutenticacao();

// ============================================
// MARCA TEMPO DE INÍCIO
// ============================================
$startTime = microtime(true);

try {
    // ============================================
    // VALIDAÇÃO DE PARÂMETROS
    // ============================================
    $dataInicio = $_GET['inicio'] ?? null;
    $dataFim = $_GET['fim'] ?? null;
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    if (!$dataInicio || !$dataFim) {
        kpiError('backlog-recebimento', 'Parâmetros inicio e fim são obrigatórios', 400);
    }

    // Conversão de formato dd/mm/yyyy para yyyy-mm-dd
    $dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
    $dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));

    // Cálculo do período de referência (mesmo tamanho do período atual)
    $diasPeriodo = (strtotime($dataFimSQL) - strtotime($dataInicioSQL)) / 86400;
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicioSQL -" . ($diasPeriodo + 1) . " days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicioSQL -1 day"));

    // ============================================
    // CONEXÃO COM BANCO
    // ============================================
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // ============================================
    // QUERY 1: BACKLOG ATUAL
    // ============================================
    $sqlAtual = "
        SELECT SUM(r.quantidade) AS backlog
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.id IS NULL
    ";

    $paramsAtual = [$dataInicioSQL, $dataFimSQL];

    if ($setor) {
        $sqlAtual .= " AND r.setor = ?";
        $paramsAtual[] = $setor;
    }

    if ($operador) {
        $sqlAtual .= " AND r.operador_recebimento = ?";
        $paramsAtual[] = $operador;
    }

    $stmtAtual = $conn->prepare($sqlAtual);
    $stmtAtual->execute($paramsAtual);
    $backlogAtual = (int)($stmtAtual->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // ============================================
    // QUERY 2: BACKLOG PERÍODO ANTERIOR (REFERÊNCIA)
    // ============================================
    $sqlAnterior = "
        SELECT SUM(r.quantidade) AS backlog
        FROM recebimentos r
        LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
        WHERE r.data_entrada >= ? AND r.data_entrada <= ?
        AND ar.id IS NULL
    ";

    $paramsAnterior = [$dataInicioRef, $dataFimRef];

    if ($setor) {
        $sqlAnterior .= " AND r.setor = ?";
        $paramsAnterior[] = $setor;
    }

    if ($operador) {
        $sqlAnterior .= " AND r.operador_recebimento = ?";
        $paramsAnterior[] = $operador;
    }

    $stmtAnterior = $conn->prepare($sqlAnterior);
    $stmtAnterior->execute($paramsAnterior);
    $backlogAnterior = (int)($stmtAnterior->fetch(PDO::FETCH_ASSOC)['backlog'] ?? 0);

    // ============================================
    // CÁLCULOS DE VARIAÇÃO E ESTADO
    // ============================================
    
    // Variação percentual (invertida: redução de backlog é positiva)
    $variacao = 0;
    $tendencia = 'estavel';
    
    if ($backlogAnterior > 0) {
        $variacao = (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100;
        
        if ($variacao < -1) {
            $tendencia = 'baixa'; // Backlog diminuiu (bom)
        } elseif ($variacao > 1) {
            $tendencia = 'alta'; // Backlog aumentou (ruim)
        }
    }

    // Estado (invertido: menos backlog é melhor)
    $estado = 'success';
    if ($variacao >= 30) {
        $estado = 'critical'; // Backlog aumentou muito
    } elseif ($variacao >= 10) {
        $estado = 'warning'; // Backlog aumentou moderadamente
    } elseif ($variacao <= -10) {
        $estado = 'success'; // Backlog reduziu significativamente
    }

    // ============================================
    // ESTRUTURA DE DADOS PADRONIZADA
    // ============================================
    $data = [
        'valor' => $backlogAtual,
        'valor_formatado' => number_format($backlogAtual, 0, ',', '.'),
        'unidade' => 'equipamentos',
        'contexto' => 'Equipamentos aguardando envio para análise',
        'detalhes' => [
            'percentual_criticidade' => $backlogAtual > 100 ? 'alto' : ($backlogAtual > 50 ? 'medio' : 'baixo')
        ],
        'referencia' => [
            'tipo' => 'periodo_anterior',
            'valor' => $backlogAnterior,
            'periodo' => "$dataInicioRef a $dataFimRef",
            'descricao' => 'Backlog do período anterior (mesmo tamanho)'
        ],
        'variacao' => [
            'percentual' => round($variacao, 2),
            'tendencia' => $tendencia,
            'estado' => $estado,
            'interpretacao' => $variacao >= 10 
                ? 'Backlog aumentou - atenção necessária' 
                : ($variacao <= -10 
                    ? 'Backlog diminuiu - melhoria operacional' 
                    : 'Backlog estável')
        ],
        'filtros_aplicados' => [
            'data_inicio' => $dataInicioSQL,
            'data_fim' => $dataFimSQL,
            'setor' => $setor ?? 'Todos',
            'operador' => $operador ?? 'Todos'
        ]
    ];

    // ============================================
    // CALCULA TEMPO DE EXECUÇÃO
    // ============================================
    $executionTime = (microtime(true) - $startTime) * 1000;

    // ============================================
    // FORMATA PERÍODO PARA RESPOSTA
    // ============================================
    $period = "$dataInicioSQL / $dataFimSQL";

    // ============================================
    // REGISTRA LOG DE EXECUÇÃO
    // ============================================
    logKpiExecution(
        'kpi-backlog-atual',
        ['inicio' => $dataInicioSQL, 'fim' => $dataFimSQL],
        (int)round($executionTime),
        'success',
        $operador ?? 'Todos'
    );

    // ============================================
    // RETORNA RESPOSTA PADRONIZADA
    // ============================================
    kpiResponse(
        'backlog-recebimento',
        $period,
        $data,
        $executionTime
    );

} catch (Exception $e) {
    error_log("Erro em kpi-backlog-atual.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // ============================================
    // REGISTRA LOG DE ERRO
    // ============================================
    $executionTime = (microtime(true) - $startTime) * 1000;
    logKpiExecution(
        'kpi-backlog-atual',
        [
            'inicio' => $dataInicioSQL ?? 'N/A',
            'fim' => $dataFimSQL ?? 'N/A'
        ],
        (int)round($executionTime),
        'error',
        $operador ?? 'Todos',
        $e->getMessage()
    );
    
    kpiError(
        'backlog-recebimento',
        'Erro ao calcular backlog: ' . $e->getMessage(),
        500
    );
}
?>
