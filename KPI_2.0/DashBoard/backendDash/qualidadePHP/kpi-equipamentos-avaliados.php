<?php
/**
 * KPI: Equipamentos Avaliados na Qualidade
 * 
 * Catálogo Oficial v1.0 - Área: QUALIDADE
 * 
 * Descrição:
 * Total de equipamentos que passaram pela etapa de qualidade no período.
 * Este KPI representa o volume de inspeção/validação técnica realizada.
 * 
 * Fontes de dados:
 * - qualidade_registro (quantidade)
 * 
 * Cálculo:
 * - Período atual: SUM(quantidade) WHERE operacao_destino = 'inspecao_qualidade'
 * - Período anterior: mesmo cálculo, período anterior igual
 * - Variação: ((atual - anterior) / anterior) * 100
 * 
 * Estados:
 * - critical: variação < -30% (queda acentuada)
 * - warning: variação < -15% (queda moderada)
 * - success: variação >= -15% (estável ou crescimento)
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
header("Content-Type: application/json; charset=UTF-8");

require_once dirname(__DIR__, 2) . '/BackEnd/endpoint-helpers.php';
require_once dirname(__DIR__, 2) . '/BackEnd/conexao.php';

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

    // ========================================
    // PERÍODO ATUAL
    // ========================================
    $sqlAtual = "
        SELECT 
            SUM(quantidade) as total_avaliados,
            COUNT(DISTINCT nota_fiscal) as remessas_avaliadas,
            COUNT(DISTINCT operador) as operadores_ativos
        FROM qualidade_registro
        WHERE data_inicio_qualidade BETWEEN ? AND ?
          AND operacao_destino = 'inspecao_qualidade'
          AND quantidade IS NOT NULL
    ";
    
    if ($setor) {
        $sqlAtual .= " AND setor = ?";
    }
    if ($operador) {
        $sqlAtual .= " AND operador = ?";
    }

    $stmtAtual = $conn->prepare($sqlAtual);
    if (!$stmtAtual) {
        enviarErro("Erro ao preparar consulta do período atual", 500);
    }

    if ($setor && $operador) {
        $stmtAtual->bind_param("ssss", $dataInicio, $dataFim, $setor, $operador);
    } elseif ($setor) {
        $stmtAtual->bind_param("sss", $dataInicio, $dataFim, $setor);
    } elseif ($operador) {
        $stmtAtual->bind_param("sss", $dataInicio, $dataFim, $operador);
    } else {
        $stmtAtual->bind_param("ss", $dataInicio, $dataFim);
    }

    $stmtAtual->execute();
    $resultadoAtual = $stmtAtual->get_result()->fetch_assoc();
    $stmtAtual->close();

    $totalAtual = (int)($resultadoAtual['total_avaliados'] ?? 0);
    $remessasAtuais = (int)($resultadoAtual['remessas_avaliadas'] ?? 0);
    $operadoresAtivos = (int)($resultadoAtual['operadores_ativos'] ?? 0);

    // ========================================
    // PERÍODO ANTERIOR (REFERÊNCIA)
    // ========================================
    $sqlAnterior = "
        SELECT 
            SUM(quantidade) as total_avaliados
        FROM qualidade_registro
        WHERE data_inicio_qualidade BETWEEN ? AND ?
          AND operacao_destino = 'inspecao_qualidade'
          AND quantidade IS NOT NULL
    ";
    
    if ($setor) {
        $sqlAnterior .= " AND setor = ?";
    }
    if ($operador) {
        $sqlAnterior .= " AND operador = ?";
    }

    $stmtAnterior = $conn->prepare($sqlAnterior);
    if (!$stmtAnterior) {
        enviarErro("Erro ao preparar consulta do período anterior", 500);
    }

    if ($setor && $operador) {
        $stmtAnterior->bind_param("ssss", $dataInicioAnterior, $dataFimAnterior, $setor, $operador);
    } elseif ($setor) {
        $stmtAnterior->bind_param("sss", $dataInicioAnterior, $dataFimAnterior, $setor);
    } elseif ($operador) {
        $stmtAnterior->bind_param("sss", $dataInicioAnterior, $dataFimAnterior, $operador);
    } else {
        $stmtAnterior->bind_param("ss", $dataInicioAnterior, $dataFimAnterior);
    }

    $stmtAnterior->execute();
    $resultadoAnterior = $stmtAnterior->get_result()->fetch_assoc();
    $stmtAnterior->close();

    $totalAnterior = (int)($resultadoAnterior['total_avaliados'] ?? 0);

    // ========================================
    // CÁLCULOS DERIVADOS
    // ========================================
    
    // Variação percentual
    $variacao = 0;
    if ($totalAnterior > 0) {
        $variacao = (($totalAtual - $totalAnterior) / $totalAnterior) * 100;
    } elseif ($totalAtual > 0) {
        $variacao = 100; // Passou de 0 para valor positivo
    }

    // Média diária
    $mediaDia = $dias > 0 ? $totalAtual / $dias : 0;

    // Estado baseado na variação
    $estado = definirEstado($variacao, [-30, -15]);

    // ========================================
    // RESPOSTA JSON
    // ========================================
    $resposta = [
        'meta' => [
            'endpoint' => 'kpi-equipamentos-avaliados',
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
                'remessas_avaliadas' => $remessasAtuais,
                'operadores_ativos' => $operadoresAtivos,
                'media_dia' => round($mediaDia, 1)
            ]
        ]
    ];

    enviarSucesso($resposta['data'], 'KPI calculado com sucesso', $resposta['meta'], $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-avaliados: " . $e->getMessage());
    enviarErro("Erro ao procesar KPI: " . $e->getMessage(), 500);
}
