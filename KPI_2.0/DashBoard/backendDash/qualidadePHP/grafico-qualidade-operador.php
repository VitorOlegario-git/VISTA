<?php
/**
 * GRÁFICO C - QUALIDADE POR OPERADOR
 * Taxa de aprovação individual por operador
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
    
    // Query: taxa de aprovação por operador
    $query = "SELECT 
                operador,
                SUM(quantidade) AS total,
                SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
                ROUND((SUM(COALESCE(quantidade_parcial, 0)) / SUM(quantidade)) * 100, 2) AS taxa_aprovacao
              FROM qualidade_registro
              WHERE data_inicio_qualidade >= :inicio 
                AND data_inicio_qualidade <= :fim";
    
    $params = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $query .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    $query .= " GROUP BY operador
                HAVING total > 0
                ORDER BY taxa_aprovacao DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $taxas = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['operador'];
        $taxas[] = (float)$row['taxa_aprovacao'];
    }
    
    sendSuccess([
        'labels' => $labels,
        'valores' => $taxas
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-qualidade-operador.php: " . $e->getMessage());
    sendError("Erro ao buscar qualidade por operador: " . $e->getMessage(), 500);
}
