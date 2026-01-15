<?php
/**
 * 📘 EXEMPLO DE USO DA FUNÇÃO kpiResponse()
 * 
 * Este arquivo demonstra como implementar um endpoint KPI
 * seguindo o novo contrato padrão VISTA.
 * 
 * NÃO USAR EM PRODUÇÃO - apenas referência.
 */

require_once __DIR__ . '/../../../BackEnd/conexao.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ============================================
// 1️⃣ MARCA TEMPO DE INÍCIO
// ============================================
$startTime = microtime(true);

// ============================================
// 2️⃣ VALIDA CONEXÃO COM BANCO
// ============================================
validarConexao($conn);

// ============================================
// 3️⃣ VALIDA E EXTRAI PARÂMETROS
// ============================================
$params = validarParametrosPadrao();
$dataInicio = $params['dataInicio'];
$dataFim = $params['dataFim'];
$operador = $params['operador'];

// ============================================
// 4️⃣ CONSTRÓI WHERE CLAUSE
// ============================================
$whereConfig = construirWherePadrao(
    $dataInicio,
    $dataFim,
    $operador,
    'data_recebimento', // campo de data na tabela
    'operador'          // campo de operador na tabela
);

// ============================================
// 5️⃣ EXECUTA QUERY PRINCIPAL
// ============================================
try {
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(quantidade) as quantidade_total,
            AVG(quantidade) as media_por_recebimento
        FROM recebimentos
        {$whereConfig['where']}
    ";

    $result = executarQuery(
        $conn,
        $sql,
        $whereConfig['params'],
        $whereConfig['types']
    );

    $row = $result->fetch_assoc();
    
    if (!$row) {
        throw new Exception('Nenhum dado encontrado');
    }

    // ============================================
    // 6️⃣ BUSCA DADOS DE REFERÊNCIA (HISTÓRICO)
    // ============================================
    $sqlReferencia = "
        SELECT 
            COUNT(*) as total_ref,
            SUM(quantidade) as quantidade_ref
        FROM recebimentos
        WHERE data_recebimento BETWEEN DATE_SUB(?, INTERVAL 30 DAY) AND DATE_SUB(?, INTERVAL 1 DAY)
    ";
    
    $resultRef = executarQuery(
        $conn,
        $sqlReferencia,
        [$dataInicio, $dataInicio],
        'ss'
    );
    
    $rowRef = $resultRef->fetch_assoc();

    // ============================================
    // 7️⃣ CALCULA VARIAÇÃO
    // ============================================
    $valorAtual = (int)$row['total'];
    $valorReferencia = (int)$rowRef['total_ref'] ?? 0;
    
    $variacao = 0;
    $tendencia = 'estavel';
    $estado = 'success';
    
    if ($valorReferencia > 0) {
        $variacao = (($valorAtual - $valorReferencia) / $valorReferencia) * 100;
        
        if ($variacao > 1) {
            $tendencia = 'alta';
        } elseif ($variacao < -1) {
            $tendencia = 'baixa';
        }
        
        // Define estado baseado em thresholds
        if (abs($variacao) > 50) {
            $estado = 'critical';
        } elseif (abs($variacao) > 25) {
            $estado = 'warning';
        } else {
            $estado = 'success';
        }
    }

    // ============================================
    // 8️⃣ MONTA ESTRUTURA DE DADOS
    // ============================================
    $data = [
        'valor' => $valorAtual,
        'valor_formatado' => number_format($valorAtual, 0, ',', '.'),
        'unidade' => 'equipamentos',
        'contexto' => 'Volume processado no período',
        'detalhes' => [
            'quantidade_total' => (int)$row['quantidade_total'],
            'media_por_recebimento' => round((float)$row['media_por_recebimento'], 2)
        ],
        'referencia' => [
            'tipo' => 'media_30d',
            'valor' => $valorReferencia,
            'descricao' => 'Média dos últimos 30 dias'
        ],
        'variacao' => [
            'percentual' => round($variacao, 2),
            'tendencia' => $tendencia,
            'estado' => $estado
        ],
        'filtros_aplicados' => [
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'operador' => $operador ?? 'Todos'
        ]
    ];

    // ============================================
    // 9️⃣ CALCULA TEMPO DE EXECUÇÃO
    // ============================================
    $executionTime = (microtime(true) - $startTime) * 1000; // em milissegundos

    // ============================================
    // 🔟 MONTA PERÍODO FORMATADO
    // ============================================
    $period = $dataInicio && $dataFim 
        ? "$dataInicio / $dataFim"
        : date('Y-m');

    // ============================================
    // ✅ RETORNA RESPOSTA PADRONIZADA
    // ============================================
    kpiResponse(
        'volume-processado',  // identificador do KPI
        $period,              // período
        $data,                // dados estruturados
        $executionTime        // tempo de execução
    );

} catch (Exception $e) {
    // ============================================
    // ❌ TRATAMENTO DE ERRO
    // ============================================
    error_log("Erro no KPI volume-processado: " . $e->getMessage());
    
    kpiError(
        'volume-processado',
        'Erro ao processar dados: ' . $e->getMessage(),
        500
    );
}

// ============================================
// 📊 EXEMPLO DE RESPOSTA ESPERADA
// ============================================
/*
{
  "status": "success",
  "kpi": "volume-processado",
  "period": "2026-01-07 / 2026-01-14",
  "data": {
    "valor": 1250,
    "valor_formatado": "1.250",
    "unidade": "equipamentos",
    "contexto": "Volume processado no período",
    "detalhes": {
      "quantidade_total": 3750,
      "media_por_recebimento": 3.0
    },
    "referencia": {
      "tipo": "media_30d",
      "valor": 1180,
      "descricao": "Média dos últimos 30 dias"
    },
    "variacao": {
      "percentual": 5.93,
      "tendencia": "alta",
      "estado": "success"
    },
    "filtros_aplicados": {
      "data_inicio": "2026-01-07",
      "data_fim": "2026-01-14",
      "operador": "Todos"
    }
  },
  "meta": {
    "generatedAt": "2026-01-15T10:30:45-03:00",
    "executionTimeMs": 45.23,
    "source": "vista-kpi"
  }
}
*/

// ============================================
// 📝 GUIA RÁPIDO DE IMPLEMENTAÇÃO
// ============================================
/*

CHECKLIST PARA MIGRAR KPI EXISTENTE:

1. [ ] Adicionar $startTime = microtime(true); no início
2. [ ] Manter lógica de query existente
3. [ ] Estruturar $data como array associativo
4. [ ] Calcular $executionTime ao final
5. [ ] Substituir enviarSucesso() por kpiResponse()
6. [ ] Substituir enviarErro() por kpiError() (em try/catch)
7. [ ] Testar endpoint: deve retornar novo contrato
8. [ ] Verificar que frontend ainda funciona

RETROCOMPATIBILIDADE:
- enviarSucesso() e enviarErro() ainda funcionam
- Migração pode ser gradual, KPI por KPI
- Frontend pode ser atualizado depois

*/
?>
