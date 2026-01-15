<?php
/**
 * KPI 1 - EQUIPAMENTOS EM QUALIDADE (BACKLOG)
 * Mede volume aguardando liberação
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
    
    // Calcular backlog atual
    $query = "SELECT SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS backlog
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
    $backlogAtual = (int)$stmt->fetchColumn();
    
    // Calcular período anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryAnterior = "SELECT SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS backlog
                      FROM qualidade_registro
                      WHERE data_inicio_qualidade >= :inicio_ant 
                        AND data_inicio_qualidade <= :fim_ant";
    
    $paramsAnt = [
        ':inicio_ant' => $periodoAnteriorInicio->format('Y-m-d'),
        ':fim_ant' => $periodoAnteriorFim->format('Y-m-d')
    ];
    
    if ($setor) {
        $queryAnterior .= " AND setor = :setor";
        $paramsAnt[':setor'] = $setor;
    }
    
    if ($operador) {
        $queryAnterior .= " AND operador = :operador";
        $paramsAnt[':operador'] = $operador;
    }
    
    $stmtAnt = $db->prepare($queryAnterior);
    $stmtAnt->execute($paramsAnt);
    $backlogAnterior = (int)$stmtAnt->fetchColumn();
    
    // Calcular variação
    $variacao = $backlogAnterior > 0 ? (($backlogAtual - $backlogAnterior) / $backlogAnterior) * 100 : 0;
    
    // Determinar estado (invertido: mais backlog = pior)
    $estado = 'neutral';
    if ($variacao > 40) {
        $estado = 'critical';
    } elseif ($variacao > 20) {
        $estado = 'warning';
    } elseif ($variacao <= 0) {
        $estado = 'success';
    }
    
    sendSuccess([
        'valor' => $backlogAtual,
        'referencia' => [
            'valor_anterior' => $backlogAnterior,
            'variacao' => round($variacao, 2),
            'estado' => $estado
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em kpi-backlog-qualidade.php: " . $e->getMessage());
    sendError("Erro ao buscar backlog de qualidade: " . $e->getMessage(), 500);
}
