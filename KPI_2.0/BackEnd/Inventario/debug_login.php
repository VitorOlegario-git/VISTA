<?php
// DEBUG helper: cria sessão autenticada temporária (DEV ONLY)
// Uso: curl -c cookies.txt "http://127.0.0.1:8000/router_public.php?url=BackEnd/Inventario/debug_login.php"
// Depois chamar endpoint com: curl -b cookies.txt "http://127.0.0.1:8000/router_public.php?url=BackEnd/Inventario/Ciclos.php"

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['username'] = 'dev_user';
$_SESSION['usuario_id'] = 1;
$_SESSION['last_activity'] = time();
// ensure a CSRF token exists for API testing
if (!isset($_SESSION['csrf_token'])) {
	try {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	} catch (Exception $e) {
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
	}
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
	'success' => true,
	'message' => 'Sessão de desenvolvimento iniciada',
	'username' => $_SESSION['username'],
	'csrf_token' => $_SESSION['csrf_token']
]);
