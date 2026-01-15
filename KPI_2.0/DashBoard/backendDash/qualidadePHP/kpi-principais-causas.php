<?php
/**
 * KPI: Principais Causas de Reprovação
 * 
 * Catálogo Oficial v1.0 - Área: QUALIDADE
 * 
 * Descrição:
 * Top 5 causas de reprovação (tipos de serviço "sem conserto").
 * Permite identificar padrões de falha e concentração de problemas.
 * 
 * Fontes de dados:
 * - reparo_parcial (numero_orcamento, data_solicitacao_nf)
 * - apontamentos_gerados (servico - classificado por tipo)
 * 
 * Cálculo:
 * - Buscar orçamentos do período
 * - Agregar serviços "sem conserto" por categoria
 * - Retornar top 5 ordenado por quantidade
 * - Calcular concentração (% da causa #1 sobre total)
 * 
 * Estados:
 * - critical: causa #1 > 60% (concentração muito alta)
 * - warning: causa #1 > 40% (concentração alta)
 * - success: causa #1 <= 40% (distribuído)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__DIR__, 2) . '/BackEnd/endpoint-helpers.php';
require_once dirname(__DIR__, 2) . '/BackEnd/conexao.php';

/**
 * Normaliza texto para comparação
 */
function normalize_text($s) {
    $s = trim((string)$s);
    if ($s === "") return "";
    $no = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($no !== false) $s = $no;
    $s = mb_strtoupper($s, 'UTF-8');
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

/**
 * Classifica serviço em categoria canônica
 */
function classificar_causa($servico) {
    $s = normalize_text($servico);
    $c = preg_replace('/[^A-Z0-9]/', '', $s);
    
    if (strpos($c, 'SEMREPARO') !== false && strpos($c, 'FORA') !== false && strpos($c, 'GARAN') !== false) {
        return "SEM REPARO - FORA DA GARANTIA";
    }
    if (strpos($c, 'SEMREPARO') !== false && preg_match('/SUBS(T|TI|TITU)?/', $c) && (strpos($c, 'EMGAR') !== false || strpos($c, 'GARAN') !== false)) {
        return "SEM REPARO - SUBST. EM GARANTIA";
    }
    if (preg_match('/REJEITAD|RECUSAD/', $c) && strpos($c, 'CLIENTE') !== false) {
        return "REJEITADO PELO CLIENTE";
    }
    
    // Se não se encaixa nas categorias conhecidas, retorna original normalizado
    return $s !== "" ? $s : null;
}

try {
    // Validar parâmetros de entrada
    $parametros = validarParametrosPadrao();
    $dataInicio = $parametros['dataInicio'];
    $dataFim = $parametros['dataFim'];
    $setor = $parametros['setor'];
    $operador = $parametros['operador'];

    // Calcular período anterior
    $dias = calcularDiferencaDias($dataInicio, $dataFim);
    $dataInicioAnterior = calcularDataAnterior($dataInicio, $dias);
    $dataFimAnterior = calcularDataAnterior($dataFim, $dias);

    // Converter datas para timestamp
    $dataInicioTs = $dataInicio . " 00:00:00";
    $dataFimTs = $dataFim . " 23:59:59";
    $dataInicioAnteriorTs = $dataInicioAnterior . " 00:00:00";
    $dataFimAnteriorTs = $dataFimAnterior . " 23:59:59";

    // ========================================
    // PERÍODO ATUAL
    // ========================================
    
    // 1) Buscar orçamentos do período atual
    $sqlOrcAtual = "
        SELECT DISTINCT numero_orcamento
        FROM reparo_parcial
        WHERE data_solicitacao_nf BETWEEN ? AND ?
          AND numero_orcamento IS NOT NULL 
          AND numero_orcamento <> ''
    ";
    
    $stmtOrcAtual = $conn->prepare($sqlOrcAtual);
    if (!$stmtOrcAtual) {
        enviarErro("Erro ao preparar consulta de orçamentos", 500);
    }
    
    $stmtOrcAtual->bind_param("ss", $dataInicioTs, $dataFimTs);
    $stmtOrcAtual->execute();
    $resultOrc = $stmtOrcAtual->get_result();
    
    $orcamentosAtuais = [];
    while ($row = $resultOrc->fetch_assoc()) {
        $orc = trim($row['numero_orcamento']);
        if ($orc !== "") $orcamentosAtuais[] = $orc;
    }
    $stmtOrcAtual->close();

    // 2) Agregar causas
    $causasAtual = [];
    $totalAtual = 0;

    if (!empty($orcamentosAtuais)) {
        $placeholders = implode(",", array_fill(0, count($orcamentosAtuais), "?"));
        $sqlApontAtual = "
            SELECT servico 
            FROM apontamentos_gerados 
            WHERE orcamento IN ($placeholders)
        ";
        
        $stmtApontAtual = $conn->prepare($sqlApontAtual);
        if (!$stmtApontAtual) {
            enviarErro("Erro ao preparar consulta de apontamentos", 500);
        }
        
        $types = str_repeat("s", count($orcamentosAtuais));
        $stmtApontAtual->bind_param($types, ...$orcamentosAtuais);
        $stmtApontAtual->execute();
        $resultApont = $stmtApontAtual->get_result();
        
        while ($row = $resultApont->fetch_assoc()) {
            $servico = (string)($row['servico'] ?? "");
            if ($servico === "") continue;
            
            // Dividir múltiplos serviços
            $itens = preg_split('/[,;\/]+/', $servico);
            foreach ($itens as $item) {
                $causa = classificar_causa($item);
                if ($causa) {
                    if (!isset($causasAtual[$causa])) {
                        $causasAtual[$causa] = 0;
                    }
                    $causasAtual[$causa]++;
                    $totalAtual++;
                }
            }
        }
        $stmtApontAtual->close();
    }

    // Ordenar por quantidade (descendente)
    arsort($causasAtual);

    // Top 5 causas
    $top5Atual = array_slice($causasAtual, 0, 5, true);
    
    // Calcular percentual e concentração
    $causasFormatadas = [];
    $primeiraQuantidade = 0;
    
    foreach ($top5Atual as $causa => $quantidade) {
        if ($primeiraQuantidade === 0) {
            $primeiraQuantidade = $quantidade;
        }
        
        $percentual = $totalAtual > 0 ? ($quantidade / $totalAtual) * 100 : 0;
        
        $causasFormatadas[] = [
            'causa' => $causa,
            'quantidade' => $quantidade,
            'percentual' => round($percentual, 1)
        ];
    }

    // Concentração (% da causa #1)
    $concentracao = $totalAtual > 0 ? ($primeiraQuantidade / $totalAtual) * 100 : 0;

    // ========================================
    // PERÍODO ANTERIOR (REFERÊNCIA)
    // ========================================
    
    // 1) Buscar orçamentos do período anterior
    $sqlOrcAnterior = "
        SELECT DISTINCT numero_orcamento
        FROM reparo_parcial
        WHERE data_solicitacao_nf BETWEEN ? AND ?
          AND numero_orcamento IS NOT NULL 
          AND numero_orcamento <> ''
    ";
    
    $stmtOrcAnterior = $conn->prepare($sqlOrcAnterior);
    if (!$stmtOrcAnterior) {
        enviarErro("Erro ao preparar consulta de orçamentos anteriores", 500);
    }
    
    $stmtOrcAnterior->bind_param("ss", $dataInicioAnteriorTs, $dataFimAnteriorTs);
    $stmtOrcAnterior->execute();
    $resultOrcAnt = $stmtOrcAnterior->get_result();
    
    $orcamentosAnteriores = [];
    while ($row = $resultOrcAnt->fetch_assoc()) {
        $orc = trim($row['numero_orcamento']);
        if ($orc !== "") $orcamentosAnteriores[] = $orc;
    }
    $stmtOrcAnterior->close();

    // 2) Contar total anterior
    $totalAnterior = 0;

    if (!empty($orcamentosAnteriores)) {
        $placeholders = implode(",", array_fill(0, count($orcamentosAnteriores), "?"));
        $sqlApontAnterior = "
            SELECT servico 
            FROM apontamentos_gerados 
            WHERE orcamento IN ($placeholders)
        ";
        
        $stmtApontAnterior = $conn->prepare($sqlApontAnterior);
        if (!$stmtApontAnterior) {
            enviarErro("Erro ao preparar consulta de apontamentos anteriores", 500);
        }
        
        $types = str_repeat("s", count($orcamentosAnteriores));
        $stmtApontAnterior->bind_param($types, ...$orcamentosAnteriores);
        $stmtApontAnterior->execute();
        $resultApontAnt = $stmtApontAnterior->get_result();
        
        while ($row = $resultApontAnt->fetch_assoc()) {
            $servico = (string)($row['servico'] ?? "");
            if ($servico === "") continue;
            
            $itens = preg_split('/[,;\/]+/', $servico);
            foreach ($itens as $item) {
                $causa = classificar_causa($item);
                if ($causa) {
                    $totalAnterior++;
                }
            }
        }
        $stmtApontAnterior->close();
    }

    // ========================================
    // CÁLCULOS DERIVADOS
    // ========================================
    
    // Variação do total
    $variacao = 0;
    if ($totalAnterior > 0) {
        $variacao = (($totalAtual - $totalAnterior) / $totalAnterior) * 100;
    } elseif ($totalAtual > 0) {
        $variacao = 100;
    }

    // Estado baseado na concentração
    $estado = 'success';
    if ($concentracao > 60) {
        $estado = 'critical';
    } elseif ($concentracao > 40) {
        $estado = 'warning';
    }

    // ========================================
    // RESPOSTA JSON
    // ========================================
    $resposta = [
        'meta' => [
            'endpoint' => 'kpi-principais-causas',
            'area' => 'qualidade',
            'timestamp' => date('Y-m-d H:i:s'),
            'periodo' => [
                'inicio' => $dataInicio,
                'fim' => $dataFim,
                'dias' => $dias
            ],
            'filtros' => array_filter([
                'setor' => $setor,
                'operador' => $operador
            ]),
            'setor' => $setor
        ],
        'data' => [
            'valor' => count($causasFormatadas) > 0 ? $causasFormatadas[0]['causa'] : 'N/A',
            'unidade' => 'causas',
            'referencia' => [
                'periodo_anterior' => $totalAnterior,
                'variacao' => round($variacao, 1),
                'estado' => $estado
            ],
            'detalhes' => [
                'top_5_causas' => $causasFormatadas,
                'total_reprovacoes' => $totalAtual,
                'concentracao_causa_1' => round($concentracao, 1),
                'orcamentos_analisados' => count($orcamentosAtuais)
            ]
        ]
    ];

    enviarSucesso($resposta['data'], 'KPI calculado com sucesso', $resposta['meta'], $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-principais-causas: " . $e->getMessage());
    enviarErro("Erro ao processar KPI: " . $e->getMessage(), 500);
}
