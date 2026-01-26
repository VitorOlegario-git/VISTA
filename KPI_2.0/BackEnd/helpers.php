<?php
/**
 * Helper de Sessão - Gerenciamento Centralizado
 * Evita duplicação de código em múltiplos arquivos
 */

// Inclui config.php apenas se existir
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Define SESSION_TIMEOUT padrão se não estiver definido
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutos
}

/**
 * Verifica se a sessão está ativa e se não expirou
 * Redireciona para login se necessário
 */
function verificarSessao($redirecionarSeInvalida = true) {
    // Inicia sessão se ainda não foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verifica inatividade
    // Detect if request expects JSON / is an AJAX call
    $isApiCall = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        // Session expired: destroy session and, when appropriate, redirect to login
        destruirSessao();
        if ($redirecionarSeInvalida) {
            if ($isApiCall && function_exists('jsonUnauthorized')) {
                jsonUnauthorized('Sessão expirada');
            }
            // Preserve original requested URI so user can be returned after login
            $returnTo = $_SERVER['REQUEST_URI'] ?? '/';
            header("Location: " . url('FrontEnd/tela_login.php') . '?return=' . urlencode($returnTo));
            exit();
        }
        return false;
    }

    // Verifica se está autenticado
    if (!isset($_SESSION['username'])) {
        if ($redirecionarSeInvalida) {
            if ($isApiCall && function_exists('jsonUnauthorized')) {
                jsonUnauthorized('Usuário não autenticado');
            }
            // Preserve original requested URI so user can be returned after login
            $returnTo = $_SERVER['REQUEST_URI'] ?? '/';
            header("Location: " . url('FrontEnd/tela_login.php') . '?return=' . urlencode($returnTo));
            exit();
        }
        return false;
    }
    
    // Atualiza última atividade
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Destrói a sessão completamente
 */
function destruirSessao() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
}

/**
 * Inicia sessão e autentica usuário
 */
function autenticarUsuario($userId, $username) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['usuario_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Regenera ID de sessão para prevenir session fixation
    session_regenerate_id(true);
}

/**
 * Verifica se usuário está autenticado
 */
function estaAutenticado() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['username']) && isset($_SESSION['usuario_id']);
}

/**
 * Obtém o nome de usuário da sessão
 */
function getUsuarioLogado() {
    return $_SESSION['username'] ?? null;
}

/**
 * Obtém o ID do usuário da sessão
 */
function getUsuarioId() {
    return $_SESSION['usuario_id'] ?? null;
}

/**
 * Define headers de segurança e cache
 */
function definirHeadersSeguranca() {
    // Previne cache
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Headers de segurança
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

/**
 * Sanitiza input para prevenir XSS
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida CNPJ
 */
function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    return true;
}

/**
 * Retorna resposta JSON e encerra execução
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Retorna erro JSON
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Retorna resposta JSON 401 (não autorizado) e encerra execução
 * Uso recomendado para APIs quando a sessão não existir ou expirar.
 */
function jsonUnauthorized($message = 'Usuário não autenticado') {
    // Garantir headers de segurança/caching antes de responder
    if (function_exists('definirHeadersSeguranca')) {
        definirHeadersSeguranca();
    }
    jsonResponse(['error' => $message], 401);
}

/**
 * Retorna sucesso JSON
 */
function jsonSuccess($data = [], $message = null) {
    $response = ['success' => true];
    if ($message) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Gera token CSRF
 */
function gerarTokenCSRF() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validarTokenCSRF($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verifica token CSRF do POST e retorna erro JSON se inválido
 */
function verificarCSRF() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!validarTokenCSRF($token)) {
        jsonError('Token CSRF inválido. Recarregue a página e tente novamente.', 403);
    }
}

/**
 * Gera campo hidden com token CSRF para formulários
 */
function campoCSRF() {
    $token = gerarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Retorna token CSRF como meta tag para requisições AJAX
 */
function metaCSRF() {
    $token = gerarTokenCSRF();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

// Add your helper functions here.
// Example:
function exampleHelper() {
    return "Helper function loaded.";
}
?>
