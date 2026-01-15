<?php
/**
 * KPI: Taxa de Qualidade (Aprovação)
 * 
 * Catálogo Oficial v1.0 - Área: QUALIDADE
 * 
 * Descrição:
 * Percentual de equipamentos aprovados na qualidade (enviados para expedição).
 * Taxa = (equipamentos_enviados_expedicao / equipamentos_avaliados) * 100
 * 
 * Fontes de dados:
 * - qualidade_registro (quantidade, operacao_destino)
 * 
 * Cálculo:
 * - Avaliados: operacao_destino = 'inspecao_qualidade'
 * - Aprovados: data_envio_expedicao IS NOT NULL (enviados para expedição)
 * - Taxa: (aprovados / avaliados) * 100
 * 
 * Estados:
 * - critical: taxa < 70% (muitas reprovações)
 * - warning: taxa < 85% (abaixo da meta)
 * - success: taxa >= 85% (meta atingida)
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
    // FUNÇÃO AUXILIAR PARA BUSCAR DADOS
    // ========================================
    function buscarDadosQualidade($conn, $dataInicio, $dataFim, $setor, $operador) {
        // Total avaliados
        $sqlAvaliados = "
            SELECT SUM(quantidade) as total
            FROM qualidade_registro
            WHERE data_inicio_qualidade BETWEEN ? AND ?
              AND operacao_destino = 'inspecao_qualidade'
              AND quantidade IS NOT NULL
        ";
        
        if ($setor) {
            $sqlAvaliados .= " AND setor = ?";
        }
        if ($operador) {
            $sqlAvaliados .= " AND operador = ?";
        }

        $stmtAval = $conn->prepare($sqlAvaliados);
        if (!$stmtAval) {
            enviarErro("Erro ao preparar consulta de avaliados", 500);
        }

        if ($setor && $operador) {
            $stmtAval->bind_param("ssss", $dataInicio, $dataFim, $setor, $operador);
        } elseif ($setor) {
            $stmtAval->bind_param("sss", $dataInicio, $dataFim, $setor);
        } elseif ($operador) {
            $stmtAval->bind_param("sss", $dataInicio, $dataFim, $operador);
        } else {
            $stmtAval->bind_param("ss", $dataInicio, $dataFim);
        }

        $stmtAval->execute();
        $resultAval = $stmtAval->get_result()->fetch_assoc();
        $stmtAval->close();

        $avaliados = (int)($resultAval['total'] ?? 0);

        // Total aprovados (enviados para expedição)
        $sqlAprovados = "
            SELECT SUM(quantidade) as total
            FROM qualidade_registro
            WHERE data_inicio_qualidade BETWEEN ? AND ?
              AND operacao_destino = 'inspecao_qualidade'
              AND data_envio_expedicao IS NOT NULL
              AND quantidade IS NOT NULL
        ";
        
        if ($setor) {
            $sqlAprovados .= " AND setor = ?";
        }
        if ($operador) {
            $sqlAprovados .= " AND operador = ?";
        }

        $stmtAprov = $conn->prepare($sqlAprovados);
        if (!$stmtAprov) {
            enviarErro("Erro ao preparar consulta de aprovados", 500);
        }

        if ($setor && $operador) {
            $stmtAprov->bind_param("ssss", $dataInicio, $dataFim, $setor, $operador);
        } elseif ($setor) {
            $stmtAprov->bind_param("sss", $dataInicio, $dataFim, $setor);
        } elseif ($operador) {
            $stmtAprov->bind_param("sss", $dataInicio, $dataFim, $operador);
        } else {
            $stmtAprov->bind_param("ss", $dataInicio, $dataFim);
        }

        $stmtAprov->execute();
        $resultAprov = $stmtAprov->get_result()->fetch_assoc();
        $stmtAprov->close();

        $aprovados = (int)($resultAprov['total'] ?? 0);

        return [
            'avaliados' => $avaliados,
            'aprovados' => $aprovados
        ];
    }

    // ========================================
    // PERÍODO ATUAL
    // ========================================
    $dadosAtual = buscarDadosQualidade($conn, $dataInicio, $dataFim, $setor, $operador);
    $avaliadosAtual = $dadosAtual['avaliados'];
    $aprovadosAtual = $dadosAtual['aprovados'];
    $reprovadosAtual = $avaliadosAtual - $aprovadosAtual;

    // Calcular taxa
    $taxaAtual = 0;
    if ($avaliadosAtual > 0) {
        $taxaAtual = ($aprovadosAtual / $avaliadosAtual) * 100;
    }

    // ========================================
    // PERÍODO ANTERIOR (REFERÊNCIA)
    // ========================================
    $dadosAnterior = buscarDadosQualidade($conn, $dataInicioAnterior, $dataFimAnterior, $setor, $operador);
    $avaliadosAnterior = $dadosAnterior['avaliados'];
    $aprovadosAnterior = $dadosAnterior['aprovados'];

    // Calcular taxa anterior
    $taxaAnterior = 0;
    if ($avaliadosAnterior > 0) {
        $taxaAnterior = ($aprovadosAnterior / $avaliadosAnterior) * 100;
    }

    // ========================================
    // CÁLCULOS DERIVADOS
    // ========================================
    
    // Variação em pontos percentuais
    $variacaoPP = $taxaAtual - $taxaAnterior;

    // Estado baseado no valor absoluto da taxa
    $estado = 'success';
    if ($taxaAtual < 70) {
        $estado = 'critical';
    } elseif ($taxaAtual < 85) {
        $estado = 'warning';
    }

    // ========================================
    // RESPOSTA JSON
    // ========================================
    $resposta = [
        'meta' => [
            'endpoint' => 'kpi-taxa-qualidade',
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
            'valor' => round($taxaAtual, 1),
            'unidade' => '%',
            'referencia' => [
                'periodo_anterior' => round($taxaAnterior, 1),
                'variacao' => round($variacaoPP, 1),
                'estado' => $estado
            ],
            'detalhes' => [
                'avaliados' => $avaliadosAtual,
                'aprovados' => $aprovadosAtual,
                'reprovados' => $reprovadosAtual,
                'taxa_reprovacao' => round(100 - $taxaAtual, 1)
            ]
        ]
    ];

    enviarSucesso($resposta['data'], 'KPI calculado com sucesso', $resposta['meta'], $setor);

} catch (Exception $e) {
    error_log("Erro em kpi-taxa-qualidade: " . $e->getMessage());
    enviarErro("Erro ao processar KPI: " . $e->getMessage(), 500);
}
