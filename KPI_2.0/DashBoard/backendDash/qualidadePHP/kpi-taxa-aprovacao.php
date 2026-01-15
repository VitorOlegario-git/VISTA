<?php
/**
 * KPI 3 - TAXA DE APROVAÇÃO
 * Mede confiabilidade do reparo
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
    
    // Taxa atual
    $query = "SELECT 
                SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
                SUM(quantidade) AS total
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
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $aprovados = (int)$result['aprovados'];
    $total = (int)$result['total'];
    $taxaAtual = $total > 0 ? ($aprovados / $total) * 100 : 0;
    
    // Período anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryAnt = "SELECT 
                    SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
                    SUM(quantidade) AS total
                 FROM qualidade_registro
                 WHERE data_inicio_qualidade >= :inicio_ant 
                   AND data_inicio_qualidade <= :fim_ant";
    
    $paramsAnt = [
        ':inicio_ant' => $periodoAnteriorInicio->format('Y-m-d'),
        ':fim_ant' => $periodoAnteriorFim->format('Y-m-d')
    ];
    
    if ($setor) {
        $queryAnt .= " AND setor = :setor";
        $paramsAnt[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryAnt .= " AND operador = :operador";
        $paramsAnt[':operador'] = $operador;
    }
    
    $stmtAnt = $db->prepare($queryAnt);
    $stmtAnt->execute($paramsAnt);
    $resultAnt = $stmtAnt->fetch(PDO::FETCH_ASSOC);
    
    $aprovadosAnt = (int)$resultAnt['aprovados'];
    $totalAnt = (int)$resultAnt['total'];
    $taxaAnterior = $totalAnt > 0 ? ($aprovadosAnt / $totalAnt) * 100 : 0;
    
    // Variação em pontos percentuais
    $variacao = $taxaAtual - $taxaAnterior;
    
    // Estados conforme blueprint
    $estado = 'neutral';
    if ($taxaAtual < 85) {
        $estado = 'critical';
    } elseif ($taxaAtual < 95) {
        $estado = 'warning';
    } else {
        $estado = 'success';
    }
    
    sendSuccess([
        'valor' => round($taxaAtual, 1),
        'referencia' => [
            'valor_anterior' => round($taxaAnterior, 1),
            'variacao' => round($variacao, 2),
            'estado' => $estado
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em kpi-taxa-aprovacao.php: " . $e->getMessage());
    sendError("Erro ao buscar taxa de aprovação: " . $e->getMessage(), 500);
}
