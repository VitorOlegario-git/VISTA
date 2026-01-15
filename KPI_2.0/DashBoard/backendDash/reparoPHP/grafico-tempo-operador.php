<?php
/**
 * GRÁFICO C - TEMPO MÉDIO POR OPERADOR
 * Mede eficiência individual e detecta gargalos humanos
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
    
    // Query: tempo médio por operador (proxy usando DATEDIFF)
    $query = "SELECT 
                COALESCE(operador, 'Não Identificado') AS operador,
                AVG(DATEDIFF(CURDATE(), data_registro)) AS tempo_medio,
                COUNT(*) AS total_reparos
              FROM reparo_resumo
              WHERE data_registro >= :inicio 
                AND data_registro <= :fim
                AND quantidade_reparada > 0";
    
    $params = [
        ':inicio' => $inicioSQL,
        ':fim' => $fimSQL
    ];
    
    if ($setor) {
        $query .= " AND setor = :setor";
        $params[':setor'] = $setor;
    }
    
    $query .= " GROUP BY operador
                HAVING COUNT(*) >= 3
                ORDER BY tempo_medio ASC
                LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $labels = [];
    $valores = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['operador'];
        $valores[] = round((float)$row['tempo_medio'], 1);
    }
    
    sendSuccess([
        'labels' => $labels,
        'valores' => $valores
    ]);
    
} catch (Exception $e) {
    error_log("Erro em grafico-tempo-operador.php: " . $e->getMessage());
    sendError("Erro ao buscar tempo por operador: " . $e->getMessage(), 500);
}
