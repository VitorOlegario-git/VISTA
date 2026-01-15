<?php
/**
 * GRÁFICO D - PRINCIPAIS SERVIÇOS / LAUDOS
 * Distribuição por tipo de serviço (apoia decisões técnicas e de estoque)
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
    
    // Query: TOP 10 serviços/laudos mais frequentes
    $query = "SELECT 
                COALESCE(servico, 'Não Especificado') AS servico,
                COUNT(*) AS quantidade,
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
    
    $query .= " GROUP BY servico
                ORDER BY quantidade DESC
                LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['servico'];
        $valores[] = (int)$row['quantidade'];
    }
    
    // Se não houver dados, retornar placeholder
    if (empty($labels)) {
        $labels = ['Sem Dados'];
        $valores = [0];
    }
    
    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-servicos.php: " . $e->getMessage());
    sendError("Erro ao buscar principais serviços: " . $e->getMessage(), 500);
}
