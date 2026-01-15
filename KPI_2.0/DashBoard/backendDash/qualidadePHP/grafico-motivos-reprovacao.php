<?php
/**
 * GRÁFICO B - PRINCIPAIS MOTIVOS DE REPROVAÇÃO
 * TOP motivos de reprovação (causa raiz)
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
    
    // Query: TOP motivos de reprovação
    $query = "SELECT 
                COALESCE(motivo_reprovacao, 'Não informado') AS motivo,
                SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS total_reprovados
              FROM qualidade_registro
              WHERE data_inicio_qualidade >= :inicio 
                AND data_inicio_qualidade <= :fim
                AND (quantidade - COALESCE(quantidade_parcial, 0)) > 0";
    
    $params = [':inicio' => $inicioSQL, ':fim' => $fimSQL];
    
    if ($setor) {
        $query .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    if ($operador) {
        $query .= " AND operador = :operador";
        $params[':operador'] = $operador;
    }
    
    $query .= " GROUP BY motivo_reprovacao
                ORDER BY total_reprovados DESC
                LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['motivo'];
        $valores[] = (int)$row['total_reprovados'];
    }
    
    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-motivos-reprovacao.php: " . $e->getMessage());
    sendError("Erro ao buscar motivos de reprovação: " . $e->getMessage(), 500);
}
