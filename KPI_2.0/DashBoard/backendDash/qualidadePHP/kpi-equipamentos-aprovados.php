<?php
/**
 * KPI 2 - EQUIPAMENTOS APROVADOS
 * Mede throughput de liberação
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
    
    // Aprovados atual
    $query = "SELECT 
                SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
                COUNT(DISTINCT DATE(data_inicio_qualidade)) AS dias_periodo
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
    
    $aprovadosAtual = (int)$result['aprovados'];
    $diasPeriodo = (int)$result['dias_periodo'] ?: 1;
    $mediaDiaria = round($aprovadosAtual / $diasPeriodo, 1);
    
    // Período anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryAnt = "SELECT SUM(COALESCE(quantidade_parcial, 0)) AS aprovados
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
    $aprovadosAnterior = (int)$stmtAnt->fetchColumn();
    
    $variacao = $aprovadosAnterior > 0 ? (($aprovadosAtual - $aprovadosAnterior) / $aprovadosAnterior) * 100 : 0;
    
    $estado = 'neutral';
    if ($variacao >= 15) {
        $estado = 'success';
    } elseif ($variacao <= -15) {
        $estado = 'critical';
    } elseif ($variacao <= -5) {
        $estado = 'warning';
    }
    
    sendSuccess([
        'valor' => $aprovadosAtual,
        'referencia' => [
            'valor_anterior' => $aprovadosAnterior,
            'variacao' => round($variacao, 2),
            'estado' => $estado
        ],
        'extras' => [
            'media_diaria' => $mediaDiaria
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em kpi-equipamentos-aprovados.php: " . $e->getMessage());
    sendError("Erro ao buscar equipamentos aprovados: " . $e->getMessage(), 500);
}
