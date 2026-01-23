<?php
/**
 * Arquivo de Configuração Central
 * Carrega variáveis de ambiente e define constantes globais
 */

// Carrega variáveis do arquivo .env
function loadEnv($path) {
    if (!file_exists($path)) {
        // Do not die here; many endpoints should be resilient when .env is absent.
        // Log a warning and continue with defaults.
        $logPath = __DIR__ . '/../logs/php_errors.log';
        if (!file_exists(dirname($logPath))) {
            @mkdir(dirname($logPath), 0755, true);
        }
        // Registrar aviso via error_log (evita warnings no output HTTP)
        error_log('[config] WARNING: .env not found at ' . $path);
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignora comentários
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse linha KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove aspas se existirem
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Define variável de ambiente se não existir
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Carrega o arquivo .env
loadEnv(__DIR__ . '/../.env');
// Define constantes de configuração
// Primeiro define ambiente e debug para usarmos no fallback de DB_HOST
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));

// Em produção, usar 127.0.0.1 como fallback para forçar TCP (evita uso de socket 'localhost')
$defaultDbHost = (APP_ENV === 'production') ? '127.0.0.1' : 'localhost';
define('DB_HOST', getenv('DB_HOST') ?: $defaultDbHost);
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'vista');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Timeout de sessão em segundos
define('SESSION_TIMEOUT', 1800); // 30 minutos

// Configurações de erro baseadas no ambiente
if (APP_DEBUG && APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    // Em produção, erros devem ser logados, não exibidos
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Define URLs constantes
define('BASE_URL', APP_URL);
define('FRONTEND_URL', APP_URL . '/FrontEnd');
define('BACKEND_URL', APP_URL . '/BackEnd');
define('CSS_URL', FRONTEND_URL . '/CSS');
define('JS_URL', FRONTEND_URL . '/JS');
define('IMAGES_URL', CSS_URL . '/imagens');

// Configurações de Email
define('MAIL_HOST', getenv('MAIL_HOST'));
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS'));
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Sistema VISTA');

// Dados de conexão com o banco de dados (ajuste conforme seu ambiente)
define('DB_DSN', 'mysql:host=localhost;dbname=nome_do_banco;charset=utf8');
define('DB_USER', 'usuario_do_banco');
define('DB_PASS', 'senha_do_banco');

/**
 * Função helper para gerar URLs
 */
function url($path = '') {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Função helper para assets
 */
function asset($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}
?>
