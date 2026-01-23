<?php
declare(strict_types=1);

// Test runner for ConsolidacaoApi - simula sessão + POST data
chdir(__DIR__ . '/..');

if (session_status() === PHP_SESSION_NONE) session_start();
// Simular usuário autenticado
$_SESSION['username'] = 'tester';
$_SESSION['usuario_id'] = 1;
// Gerar token CSRF de teste
$_SESSION['csrf_token'] = 'TEST_CSRF_TOKEN';

// Simular request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ciclo_id'] = 1;
$_POST['armario_id'] = 'ARM-01';
$_POST['remessas'] = "REM123\nREM_NOT_FOUND";
$_REQUEST['action'] = 'compare_armario';
// Incluir token CSRF no POST para passar pela verificação
$_POST['csrf_token'] = 'TEST_CSRF_TOKEN';

// Ensure errors are visible in CLI output
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../BackEnd/Inventario/ConsolidacaoApi.php';

$api = new ConsolidacaoApi();
$api->handle();

