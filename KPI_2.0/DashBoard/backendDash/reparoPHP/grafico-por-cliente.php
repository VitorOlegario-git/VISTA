<?php
/**
 * GRÁFICO B - REPAROS POR CLIENTE
 * Volume técnico por cliente (identifica clientes com maior impacto)
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
    
    // Query: TOP 10 clientes por volume reparado
    $query = "SELECT 
                COALESCE(c.razao_social, 'Cliente Não Identificado') AS cliente,
                SUM(COALESCE(r.quantidade_reparada, 0)) AS total
              FROM reparo_resumo r
              LEFT JOIN clientes c ON r.cnpj = c.cnpj
              WHERE r.data_registro >= :inicio 
                AND r.data_registro <= :fim";
    
    $params = [
        ':inicio' => $inicioSQL,
        ':fim' => $fimSQL
    ];
    
    if ($setor) {
        $query .= " AND r.setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND r.operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $query .= " GROUP BY r.cnpj, c.razao_social
                ORDER BY total DESC
                LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['cliente'];
        $valores[] = (int)$row['total'];
    }
    
    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-por-cliente.php: " . $e->getMessage());
    sendError("Erro ao buscar reparos por cliente: " . $e->getMessage(), 500);
}
