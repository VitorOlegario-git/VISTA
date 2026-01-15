<?php
/**
 * GRÁFICO A - EVOLUÇÃO DE APROVAÇÕES
 * Aprovados vs Reprovados ao longo do tempo
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
    
    // Query: aprovados e reprovados por dia
    $query = "SELECT 
                DATE_FORMAT(data_inicio_qualidade, '%d/%m') AS data,
                SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
                SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS reprovados
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
    
    $query .= " GROUP BY DATE_FORMAT(data_inicio_qualidade, '%Y-%m-%d')
                ORDER BY data_inicio_qualidade ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $aprovados = [];
    $reprovados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['data'];
        $aprovados[] = (int)$row['aprovados'];
        $reprovados[] = (int)$row['reprovados'];
    }
    
    sendSuccess([
        'labels' => $labels,
        'aprovados' => $aprovados,
        'reprovados' => $reprovados
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-evolucao-aprovacoes.php: " . $e->getMessage());
    sendError("Erro ao buscar evolução de aprovações: " . $e->getMessage(), 500);
}
