<?php
/**
 * GRÁFICO A - EVOLUÇÃO DE REPAROS NO TEMPO
 * Reparados por dia (identifica picos e quedas)
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../../BackEnd/conexao.php';
require_once '../../BackEnd/endpoint-helpers.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parâmetros de filtro
    $inicio = isset($_GET['inicio']) ? $_GET['inicio'] : null;
    $fim = isset($_GET['fim']) ? $_GET['fim'] : null;
    $setor = isset($_GET['setor']) ? $_GET['setor'] : null;
    $operador = isset($_GET['operador']) ? $_GET['operador'] : null;
    
    if (!$inicio || !$fim) {
        sendError("Parâmetros 'inicio' e 'fim' são obrigatórios", 400);
    }
    
    // Converter datas DD/MM/YYYY para YYYY-MM-DD
    $dataInicio = DateTime::createFromFormat('d/m/Y', $inicio);
    $dataFim = DateTime::createFromFormat('d/m/Y', $fim);
    
    if (!$dataInicio || !$dataFim) {
        sendError("Formato de data inválido. Use DD/MM/YYYY", 400);
    }
    
    $inicioSQL = $dataInicio->format('Y-m-d');
    $fimSQL = $dataFim->format('Y-m-d');
    
    // Query: reparos por dia
    $query = "SELECT 
                DATE_FORMAT(data_registro, '%d/%m') AS data,
                SUM(COALESCE(quantidade_reparada, 0)) AS total_reparado
              FROM reparo_resumo
              WHERE data_registro >= :inicio 
                AND data_registro <= :fim";
    
    $params = [
        ':inicio' => $inicioSQL,
        ':fim' => $fimSQL
    ];
    
    if ($setor) {
        $query .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $query .= " GROUP BY DATE_FORMAT(data_registro, '%Y-%m-%d')
                ORDER BY data_registro ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $reparados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['data'];
        $reparados[] = (int)$row['total_reparado'];
    }
    
    sendSuccess([
        'labels' => $labels,
        'reparados' => $reparados
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-evolucao-reparos.php: " . $e->getMessage());
    sendError("Erro ao buscar evolução de reparos: " . $e->getMessage(), 500);
}
