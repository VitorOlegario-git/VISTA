<?php
/**
 * 🧪 ENDPOINT DE TESTE - DEBUG
 * Verifica se os includes funcionam e retorna informações de debug
 */

header('Content-Type: application/json; charset=utf-8');

$debug = [
    'status' => 'OK',
    'file' => __FILE__,
    'dir' => __DIR__,
    'params' => $_GET,
    'includes' => []
];

// Testa include de conexao
$conexaoPath = __DIR__ . '/../../../BackEnd/conexao.php';
$debug['includes']['conexao'] = [
    'path' => $conexaoPath,
    'exists' => file_exists($conexaoPath),
    'readable' => is_readable($conexaoPath)
];

// Testa include de helpers
$helpersPath = __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
$debug['includes']['helpers'] = [
    'path' => $helpersPath,
    'exists' => file_exists($helpersPath),
    'readable' => is_readable($helpersPath)
];

// Tenta carregar
try {
    if (file_exists($conexaoPath)) {
        require_once $conexaoPath;
        $debug['includes']['conexao']['loaded'] = true;
        $debug['includes']['conexao']['conn_exists'] = isset($conn);
    }
    
    if (file_exists($helpersPath)) {
        require_once $helpersPath;
        $debug['includes']['helpers']['loaded'] = true;
        $debug['includes']['helpers']['functions'] = [
            'validarParametrosPadrao' => function_exists('validarParametrosPadrao'),
            'construirWherePadrao' => function_exists('construirWherePadrao'),
            'enviarSucesso' => function_exists('enviarSucesso'),
            'enviarErro' => function_exists('enviarErro')
        ];
    }
} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
    $debug['trace'] = $e->getTraceAsString();
}

echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
