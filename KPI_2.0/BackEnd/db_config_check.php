<?php
// Runtime DB configuration check (safe, logs masked values to server logs)
// Usage: call via web server (router_public.php?url=BackEnd/db_config_check.php) or CLI.
require_once __DIR__ . '/config.php';

function mask($s) {
    if ($s === null) return null;
    $s = (string)$s;
    if ($s === '') return '';
    $len = strlen($s);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($s,0,2) . str_repeat('*', max(1, $len-4)) . substr($s,-2);
}

$envPath = __DIR__ . '/../.env';
$envExists = file_exists($envPath);

// Capture getenv values (these are what config.php used to build constants)
$ge = [
    'DB_HOST' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : null,
    'DB_USERNAME' => getenv('DB_USERNAME') !== false ? getenv('DB_USERNAME') : null,
    'DB_PASSWORD' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : null,
    'DB_NAME' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : null,
    'DB_PORT' => getenv('DB_PORT') !== false ? getenv('DB_PORT') : null,
];

// Constants defined by config.php
$consts = [
    'DB_HOST' => defined('DB_HOST') ? DB_HOST : null,
    'DB_USERNAME' => defined('DB_USERNAME') ? DB_USERNAME : null,
    'DB_PASSWORD' => defined('DB_PASSWORD') ? DB_PASSWORD : null,
    'DB_NAME' => defined('DB_NAME') ? DB_NAME : null,
    'DB_DSN' => defined('DB_DSN') ? DB_DSN : null,
    'DB_USER' => defined('DB_USER') ? DB_USER : null,
    'DB_PASS' => defined('DB_PASS') ? DB_PASS : null,
];

// Prepare masked report
$report = [];
$report[] = '[DB CONFIG CHECK] RUN AT ' . date('c');
$report[] = '[DB CONFIG CHECK] .env file present: ' . ($envExists ? 'yes' : 'no');

$report[] = '[DB CONFIG CHECK] Effective constants (masked):';
$report[] = sprintf('  DB_HOST=%s', $consts['DB_HOST'] ?? '(not defined)');
$report[] = sprintf('  DB_NAME=%s', $consts['DB_NAME'] ?? '(not defined)');
$report[] = sprintf('  DB_USER=%s', $consts['DB_USERNAME'] ?? $consts['DB_USER'] ?? '(not defined)');
$report[] = sprintf('  DB_PASS=%s', mask($consts['DB_PASSWORD'] ?? $consts['DB_PASS'] ?? ''));

$report[] = '[DB CONFIG CHECK] Environment variables (masked if present):';
$report[] = sprintf('  DB_HOST(env)=%s', $ge['DB_HOST'] ?? '(not set)');
$report[] = sprintf('  DB_NAME(env)=%s', $ge['DB_NAME'] ?? '(not set)');
$report[] = sprintf('  DB_USER(env)=%s', $ge['DB_USERNAME'] ?? '(not set)');
$report[] = sprintf('  DB_PASS(env)=%s', mask($ge['DB_PASSWORD']));
$report[] = sprintf('  DB_PORT(env)=%s', $ge['DB_PORT'] ?? '(not set)');

// Check DSN parsing if present
if (!empty($consts['DB_DSN'])) {
    $report[] = '[DB CONFIG CHECK] DB_DSN defined: ' . $consts['DB_DSN'];
    // Try to parse host and dbname from DSN
    if (preg_match('/host=([^;]+);dbname=([^;]+)/i', $consts['DB_DSN'], $m)) {
        $dsn_host = $m[1]; $dsn_db = $m[2];
        $report[] = sprintf('  Parsed DSN -> host=%s dbname=%s', $dsn_host, $dsn_db);
        if (isset($consts['DB_HOST']) && $consts['DB_HOST'] !== $dsn_host) {
            $report[] = sprintf('  WARNING: DB_HOST constant (%s) differs from DB_DSN host (%s)', $consts['DB_HOST'], $dsn_host);
        }
        if (isset($consts['DB_NAME']) && $consts['DB_NAME'] !== $dsn_db) {
            $report[] = sprintf('  WARNING: DB_NAME constant (%s) differs from DB_DSN dbname (%s)', $consts['DB_NAME'], $dsn_db);
        }
    }
}

// Common problem checks
if (empty($consts['DB_HOST'])) $report[] = '[DB CONFIG CHECK] ERROR: DB_HOST is empty or not defined';
if (empty($consts['DB_USERNAME'])) $report[] = '[DB CONFIG CHECK] ERROR: DB_USERNAME is empty or not defined';
if (empty($consts['DB_NAME'])) $report[] = '[DB CONFIG CHECK] ERROR: DB_NAME is empty or not defined';

// localhost vs 127.0.0.1 hint
if (isset($consts['DB_HOST']) && strtolower($consts['DB_HOST']) === 'localhost') {
    $report[] = '[DB CONFIG CHECK] NOTE: DB_HOST is "localhost" — PHP may try a UNIX socket. If MySQL is listening on TCP only, use 127.0.0.1 instead.';
}

// Check if port likely missing (user provided DB_PORT in env or DSN)
$portKnown = false;
if (!empty($ge['DB_PORT'])) { $portKnown = true; }
if (strpos($consts['DB_DSN'] ?? '', 'port=') !== false) { $portKnown = true; }
if (!$portKnown) {
    $report[] = '[DB CONFIG CHECK] INFO: No explicit DB port detected; default MySQL port 3306 will be used.';
}

// Write report to error_log (server-side only)
foreach ($report as $line) {
    error_log($line);
}

// Additionally attempt DNS resolution for host (non-blocking)
if (!empty($consts['DB_HOST'])) {
    $host = $consts['DB_HOST'];
    $resolved = @gethostbyname($host);
    if ($resolved === $host) {
        error_log('[DB CONFIG CHECK] Host resolution: ' . $host . ' could not be resolved or resolves to itself');
    } else {
        error_log('[DB CONFIG CHECK] Host resolution: ' . $host . ' -> ' . $resolved);
    }
}

// Compare configs used by Database::getInstance (indirectly same constants) and by direct env
// We already loaded config.php, so Database will use these constants; we can log a short safe message for the admin
error_log('[DB CONFIG CHECK] Completed. For sensitive details check server logs above.');

// Output generic message to browser (no sensitive info)
if (php_sapi_name() === 'cli') {
    echo "DB CONFIG CHECK completed. See server logs for details.\n";
} else {
    echo "Verificação de configuração executada. Detalhes no log do servidor.";
}

?>