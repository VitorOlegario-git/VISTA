<?php
/**
 * KPI 4 - TEMPO MÉDIO EM QUALIDADE
 * Mede eficiência da verificação final
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
    
    // Tempo médio atual
    $query = "SELECT AVG(DATEDIFF(COALESCE(data_envio_expedicao, CURDATE()), data_inicio_qualidade)) AS tempo_medio
              FROM qualidade_registro
              WHERE data_inicio_qualidade >= :inicio 
                AND data_inicio_qualidade <= :fim
                AND quantidade_parcial > 0";
    
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
    $tempoAtual = round((float)$stmt->fetchColumn(), 1);
    
    // Período anterior
    $diffDias = $dataInicio->diff($dataFim)->days;
    $periodoAnteriorInicio = (clone $dataInicio)->modify("-{$diffDias} days");
    $periodoAnteriorFim = (clone $dataInicio)->modify("-1 day");
    
    $queryAnt = "SELECT AVG(DATEDIFF(COALESCE(data_envio_expedicao, CURDATE()), data_inicio_qualidade)) AS tempo_medio
                 FROM qualidade_registro
                 WHERE data_inicio_qualidade >= :inicio_ant 
                   AND data_inicio_qualidade <= :fim_ant
                   AND quantidade_parcial > 0";
    
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
    $tempoAnterior = round((float)$stmtAnt->fetchColumn(), 1);
    
    $variacao = $tempoAnterior > 0 ? (($tempoAtual - $tempoAnterior) / $tempoAnterior) * 100 : 0;
    
    // Estado invertido: tempo maior = pior
    $estado = 'neutral';
    if ($variacao >= 20) {
        $estado = 'critical';
    } elseif ($variacao >= 10) {
        $estado = 'warning';
    } elseif ($variacao <= -10) {
        $estado = 'success';
    }
    
    sendSuccess([
        'valor' => $tempoAtual,
        'referencia' => [
            'valor_anterior' => $tempoAnterior,
            'variacao' => round($variacao, 2),
            'estado' => $estado
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro em kpi-tempo-medio-qualidade.php: " . $e->getMessage());
    sendError("Erro ao buscar tempo médio em qualidade: " . $e->getMessage(), 500);
}
