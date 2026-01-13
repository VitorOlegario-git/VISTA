<?php
/**
 * 🧪 MOCK - KPI TOTAL (SEM BANCO)
 * Teste para isolar o problema
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // Pega parâmetros
    $inicio = $_GET['inicio'] ?? null;
    $fim = $_GET['fim'] ?? null;
    $operador = $_GET['operador'] ?? 'Todos';
    
    // Retorna mock sem usar banco
    $response = [
        'meta' => [
            'inicio' => $inicio,
            'fim' => $fim,
            'operador' => $operador,
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'data' => [
            'valor' => 1247,
            'unidade' => 'equipamentos',
            'periodo' => 'Mock - Últimos 7 dias',
            'contexto' => 'Processados (TESTE)',
            'icone' => 'fa-box-open',
            'cor' => '#3b82f6'
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
