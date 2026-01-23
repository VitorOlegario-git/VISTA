<?php
// Temporary database diagnostic endpoint
// Safe, non-destructive, returns JSON only. Remove after use.

header('Content-Type: application/json; charset=utf-8');

// Include config if available to read DB_* constants, but do not expose secrets
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    // Use include_once to avoid re-declaring constants in other contexts
    @include_once $configPath;
}

$out = [
    'env' => [],
    'config' => [],
    'tcp_test' => [],
    'mysqli_test' => []
];

// 1) Environment
$out['env'] = [
    'php_version' => phpversion(),
    'sapi' => php_sapi_name(),
    'mysqli.default_socket' => ini_get('mysqli.default_socket'),
    'mysqli.default_host' => ini_get('mysqli.default_host')
];

// 2) Configuration (do not include password)
// Support both DB_USERNAME and DB_USER names
$dbHost = defined('DB_HOST') ? DB_HOST : null;
$dbName = defined('DB_NAME') ? DB_NAME : null;
$dbUser = defined('DB_USERNAME') ? DB_USERNAME : (defined('DB_USER') ? DB_USER : null);
$dbPort = null;
// Attempt to detect port from env or DB_HOST (host:port)
if (getenv('DB_PORT')) {
    $dbPort = getenv('DB_PORT');
} elseif (defined('DB_PORT')) {
    $dbPort = DB_PORT;
} elseif ($dbHost && strpos($dbHost, ':') !== false) {
    $parts = explode(':', $dbHost);
    $dbHost = $parts[0];
    $dbPort = $parts[1];
}

$out['config'] = [
    'DB_HOST' => $dbHost,
    'DB_NAME' => $dbName,
    'DB_USER' => $dbUser,
    'DB_PORT' => $dbPort
];

// Mark undefined constants explicitly
$required = ['DB_HOST' => 'DB_HOST', 'DB_NAME' => 'DB_NAME', 'DB_USER' => 'DB_USERNAME/DB_USER'];
foreach ($required as $k => $const) {
    if ($out['config'][$k] === null) {
        $out['config'][$k] = "<NOT_DEFINED: {$const}>";
    }
}

// 3) TCP test using fsockopen
$tcp = ['success' => false, 'error' => null];
$testHost = $out['config']['DB_HOST'];
$testPort = $out['config']['DB_PORT'] ?: 3306;
if (strpos((string)$testHost, '<NOT_DEFINED') !== false) {
    $tcp['success'] = false;
    $tcp['error'] = 'DB_HOST not defined';
} else {
    $errno = 0; $errstr = '';
    // Use a short timeout for quick feedback
    $fp = @fsockopen($testHost, (int)$testPort, $errno, $errstr, 2);
    if ($fp) {
        fclose($fp);
        $tcp['success'] = true;
    } else {
        $tcp['success'] = false;
        $tcp['error'] = trim($errstr ?: "fsockopen failed (errno={$errno})");
    }
}
$out['tcp_test'] = $tcp;

// 4) mysqli test (do not print password)
$mysqliTest = ['success' => false, 'errno' => 0, 'error' => null];
if (strpos((string)$testHost, '<NOT_DEFINED') !== false || strpos((string)$out['config']['DB_NAME'], '<NOT_DEFINED') !== false || strpos((string)$out['config']['DB_USER'], '<NOT_DEFINED') !== false) {
    $mysqliTest['success'] = false;
    $mysqliTest['error'] = 'Missing DB configuration (host/name/user)';
} else {
    // Use defined DB_PASSWORD if available, but never expose it
    $dbPass = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : null);

    // Turn off mysqli warnings to avoid noisy output
    mysqli_report(MYSQLI_REPORT_OFF);

    // Try connection — do not use @ on constructor to avoid hiding connect_errno
    try {
        $port = $testPort ? (int)$testPort : 3306;
        $mysqli = new mysqli($testHost, $out['config']['DB_USER'], $dbPass, $out['config']['DB_NAME'], $port);
        if ($mysqli->connect_errno) {
            $mysqliTest['success'] = false;
            $mysqliTest['errno'] = $mysqli->connect_errno;
            $mysqliTest['error'] = $mysqli->connect_error;
        } else {
            $mysqliTest['success'] = true;
            $mysqliTest['errno'] = 0;
            $mysqliTest['error'] = null;
            $mysqli->close();
        }
    } catch (Throwable $e) {
        $mysqliTest['success'] = false;
        $mysqliTest['errno'] = 0;
        $mysqliTest['error'] = $e->getMessage();
    }
}

$out['mysqli_test'] = $mysqliTest;

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
