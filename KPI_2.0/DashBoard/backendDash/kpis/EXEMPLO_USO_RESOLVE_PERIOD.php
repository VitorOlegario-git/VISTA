<?php
/**
 * 📘 EXEMPLO DE USO - resolvePeriod()
 * 
 * Demonstra como usar a função resolvePeriod() em um KPI real.
 * Este arquivo mostra o KPI de Backlog refatorado usando a nova função.
 * 
 * IMPORTANTE: Este é um exemplo. O arquivo real está em:
 * DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php
 */

require_once __DIR__ . '/../../../BackEnd/config.php';
require_once __DIR__ . '/../../../BackEnd/Database.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ============================================
// MARCA TEMPO DE INÍCIO
// ============================================
$startTime = microtime(true);

try {
    // ============================================
    // RESOLUÇÃO INTELIGENTE DE PERÍODO
    // ============================================
    
    // A função resolvePeriod() aceita múltiplos formatos:
    // 1. ?period=today
    // 2. ?period=last_7_days
    // 3. ?period=last_30_days
    // 4. ?inicio=14/01/2026&fim=15/01/2026
    // 5. Nenhum parâmetro = últimos 7 dias (default)
    
    try {
        $periodo = resolvePeriod($_GET);
    } catch (Exception $e) {
        kpiError('backlog-recebimento', $e->getMessage(), 400);
    }
    
    $dataInicio = $periodo['inicio'];
    $dataFim = $periodo['fim'];
    $tipoPeriodo = $periodo['tipo'];
    $descricaoPeriodo = $periodo['descricao'];
    $diasPeriodo = $periodo['dias'];
    
    // Outros filtros opcionais
    $setor = $_GET['setor'] ?? null;
    $operador = $_GET['operador'] ?? null;

    // ============================================
    // CÁLCULO DO PERÍODO DE REFERÊNCIA
    // ============================================
    // Período anterior do mesmo tamanho para comparação
    $dataInicioRef = date('Y-m-d', strtotime("$dataInicio -$diasPeriodo days"));
    $dataFimRef = date('Y-m-d', strtotime("$dataInicio -1 day"));

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

    $paramsAtual = [$dataInicio, $dataFim];

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
    // QUERY 2: BACKLOG PERÍODO ANTERIOR
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
    $variacao = 0;
    $tendencia = 'estavel';
    
    if ($backlogAnterior > 0) {
        $variacao = (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100;
        
        if ($variacao < -1) {
            $tendencia = 'baixa';
        } elseif ($variacao > 1) {
            $tendencia = 'alta';
        }
    }

    $estado = 'success';
    if ($variacao >= 30) {
        $estado = 'critical';
    } elseif ($variacao >= 10) {
        $estado = 'warning';
    } elseif ($variacao <= -10) {
        $estado = 'success';
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
        'periodo_analise' => [
            'tipo' => $tipoPeriodo,
            'descricao' => $descricaoPeriodo,
            'dias' => $diasPeriodo,
            'inicio' => $dataInicio,
            'fim' => $dataFim
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
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
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
    $period = "$dataInicio / $dataFim";

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
    
    kpiError(
        'backlog-recebimento',
        'Erro ao calcular backlog: ' . $e->getMessage(),
        500
    );
}

// ============================================
// 📊 EXEMPLOS DE USO DA URL
// ============================================
/*

1. PERÍODO PRÉ-DEFINIDO - HOJE
   URL: ?period=today
   Retorno: Backlog de hoje

2. PERÍODO PRÉ-DEFINIDO - ÚLTIMOS 7 DIAS
   URL: ?period=last_7_days
   Retorno: Backlog dos últimos 7 dias

3. PERÍODO PRÉ-DEFINIDO - ÚLTIMOS 30 DIAS
   URL: ?period=last_30_days
   Retorno: Backlog dos últimos 30 dias

4. PERÍODO PRÉ-DEFINIDO - SEMANA ATUAL
   URL: ?period=current_week
   Retorno: Backlog da semana atual (segunda a hoje)

5. PERÍODO PRÉ-DEFINIDO - MÊS ATUAL
   URL: ?period=current_month
   Retorno: Backlog do mês atual

6. PERÍODO CUSTOMIZADO
   URL: ?inicio=01/01/2026&fim=15/01/2026
   Retorno: Backlog entre 01/01 e 15/01

7. SEM PARÂMETROS (DEFAULT)
   URL: (sem parâmetros)
   Retorno: Backlog dos últimos 7 dias (padrão)

8. COMBINADO COM FILTROS
   URL: ?period=last_30_days&setor=Qualidade&operador=João
   Retorno: Backlog dos últimos 30 dias filtrado por setor e operador

*/

// ============================================
// 📊 EXEMPLO DE RESPOSTA COM PERÍODO INTELIGENTE
// ============================================
/*
{
  "status": "success",
  "kpi": "backlog-recebimento",
  "period": "2026-01-08 / 2026-01-15",
  "data": {
    "valor": 125,
    "valor_formatado": "125",
    "unidade": "equipamentos",
    "contexto": "Equipamentos aguardando envio para análise",
    "periodo_analise": {
      "tipo": "last_7_days",
      "descricao": "Últimos 7 dias",
      "dias": 8,
      "inicio": "2026-01-08",
      "fim": "2026-01-15"
    },
    "referencia": {
      "tipo": "periodo_anterior",
      "valor": 150,
      "periodo": "2025-12-31 a 2026-01-07"
    },
    "variacao": {
      "percentual": -16.67,
      "tendencia": "baixa",
      "estado": "success",
      "interpretacao": "Backlog diminuiu - melhoria operacional"
    }
  },
  "meta": {
    "generatedAt": "2026-01-15T12:30:45-03:00",
    "executionTimeMs": 78.92,
    "source": "vista-kpi"
  }
}
*/
?>
