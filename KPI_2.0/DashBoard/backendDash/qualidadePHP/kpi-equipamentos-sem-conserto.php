<?php
/**
 * KPI: Equipamentos Sem Conserto
 * 
 * Catálogo Oficial v1.0 - Área: QUALIDADE
 * 
 * Descrição:
 * Total de equipamentos reprovados na qualidade (sem reparo viável).
 * Inclui: sem reparo fora garantia, substituição em garantia, rejeitados pelo cliente.
 * 
 * Fontes de dados:
 * - reparo_parcial (número_orcamento, data_solicitacao_nf)
 * - apontamentos_gerados (servico - filtrado por "sem reparo", "rejeitado", "substituição")
 * 
 * Cálculo:
 * - Buscar orçamentos do período (reparo_parcial.data_solicitacao_nf)
 * - Contar apontamentos com servico indicando sem conserto
 * - Variação: comparação com período anterior
 * 
 * Estados:
 * - critical: variação > 30% (aumento significativo de reprovações)
 * - warning: variação > 15% (aumento moderado)
 * - success: variação <= 15% (controlado)
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
 * Identifica se um serviço é "sem conserto"
 */
function is_sem_conserto($servico) {
    $s = normalize_text($servico);
    $c = preg_replace('/[^A-Z0-9]/', '', $s);
    
    // Padrões de sem conserto
    if (strpos($c, 'SEMREPARO') !== false) return true;
    if (strpos($c, 'SEMCONSERTO') !== false) return true;
    if (strpos($c, 'REJEITAD') !== false) return true;
    if (strpos($c, 'RECUSAD') !== false) return true;
    if (strpos($c, 'SUBST') !== false && strpos($c, 'GARAN') !== false) return true;
    if (strpos($c, 'FORADAGAR') !== false) return true;
    
    return false;
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

    // Converter datas para timestamp (reparo_parcial usa timestamp)
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

    // 2) Contar apontamentos "sem conserto"
    $totalAtual = 0;
    $distribuicaoTipos = [
        'SEM REPARO - FORA DA GARANTIA' => 0,
        'SEM REPARO - SUBST. EM GARANTIA' => 0,
        'REJEITADO PELO CLIENTE' => 0
    ];

    if (!empty($orcamentosAtuais)) {
        $placeholders = implode(",", array_fill(0, count($orcamentosAtuais), "?"));
        $sqlApontAtual = "
            SELECT servico, produto 
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
                if (is_sem_conserto($item)) {
                    $totalAtual++;
                    
                    // Classificar tipo
                    $s = normalize_text($item);
                    $c = preg_replace('/[^A-Z0-9]/', '', $s);
                    
                    if (strpos($c, 'SEMREPARO') !== false && strpos($c, 'FORA') !== false && strpos($c, 'GARAN') !== false) {
                        $distribuicaoTipos['SEM REPARO - FORA DA GARANTIA']++;
                    } elseif (strpos($c, 'SEMREPARO') !== false && preg_match('/SUBS(T|TI|TITU)?/', $c) && strpos($c, 'GARAN') !== false) {
                        $distribuicaoTipos['SEM REPARO - SUBST. EM GARANTIA']++;
                    } elseif (preg_match('/REJEITAD|RECUSAD/', $c) && strpos($c, 'CLIENTE') !== false) {
                        $distribuicaoTipos['REJEITADO PELO CLIENTE']++;
                    }
                }
            }
        }
        $stmtApontAtual->close();
    }

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

    // 2) Contar apontamentos "sem conserto" anteriores
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
                if (is_sem_conserto($item)) {
                    $totalAnterior++;
                }
            }
        }
        $stmtApontAnterior->close();
    }

    // ========================================
    // CÁLCULOS DERIVADOS
    // ========================================
    
    // Variação percentual
    $variacao = 0;
    if ($totalAnterior > 0) {
        $variacao = (($totalAtual - $totalAnterior) / $totalAnterior) * 100;
    } elseif ($totalAtual > 0) {
        $variacao = 100;
    }

    // Média diária
    $mediaDia = $dias > 0 ? $totalAtual / $dias : 0;

    // Estado baseado na variação (invertido: aumento é ruim)
    $estado = 'success';
    if ($variacao > 30) {
        $estado = 'critical';
    } elseif ($variacao > 15) {
        $estado = 'warning';
    }

    // ========================================
    // RESPOSTA JSON
    // ========================================
    $resposta = [
        'meta' => [
            'endpoint' => 'kpi-equipamentos-sem-conserto',
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
            'valor' => $totalAtual,
            'unidade' => 'equipamentos',
            'referencia' => [
                'periodo_anterior' => $totalAnterior,
                'variacao' => round($variacao, 1),
                'estado' => $estado
            ],
            'detalhes' => [
                'orcamentos_analisados' => count($orcamentosAtuais),
                'media_dia' => round($mediaDia, 1),
                'distribuicao' => $distribuicaoTipos
            ]
        ]
    ];

    enviarSucesso($resposta['data'], 'KPI calculado com sucesso', $resposta['meta'], $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-sem-conserto: " . $e->getMessage());
    enviarErro("Erro ao processar KPI: " . $e->getMessage(), 500);
}
