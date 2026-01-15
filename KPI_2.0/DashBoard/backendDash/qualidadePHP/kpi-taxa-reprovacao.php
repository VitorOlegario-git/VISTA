<?php
/**
 * KPI 5 - TAXA DE RETORNO / REPROVAÇÃO
 * Mede retrabalho gerado
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
    
    // Taxa atual (reprovados / total)
    $query = "SELECT 
                SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS reprovados,
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
    
    $reprovados = (int)$result['reprovados'];
    $total = (int)$result['total'];
    $taxaAtual = $total > 0 ? ($reprovados / $total) * 100 : 0;
    
    // Período anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryAnt = "SELECT 
                    SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS reprovados,
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
    
    $reprovadosAnt = (int)$resultAnt['reprovados'];
    $totalAnt = (int)$resultAnt['total'];
    $taxaAnterior = $totalAnt > 0 ? ($reprovadosAnt / $totalAnt) * 100 : 0;
    
    // Variação em pontos percentuais
    $variacao = $taxaAtual - $taxaAnterior;
    
    // Estados: invertido (mais reprovação = pior)
    $estado = 'neutral';
    if ($taxaAtual > 10) {
        $estado = 'critical';
    } elseif ($taxaAtual > 5) {
        $estado = 'warning';
    } elseif ($taxaAtual < 5) {
        $estado = 'success';
    }
    
    sendSuccess([
        'valor' => round($taxaAtual, 1),
        'referencia' => [
            'valor_anterior' => round($taxaAnterior, 1),
            'variacao' => round($variacao, 2),
            'estado' => $estado
        ],
        'extras' => [
            'reprovados' => $reprovados,
            'total' => $total
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em kpi-taxa-reprovacao.php: " . $e->getMessage());
    sendError("Erro ao buscar taxa de reprovação: " . $e->getMessage(), 500);
}
