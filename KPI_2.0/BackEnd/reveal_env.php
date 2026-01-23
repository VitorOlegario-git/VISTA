<?php
// Temporary diagnostic endpoint (safe, non-sensitive): reveals error_log path and project realpath
// Usage: router_public.php?url=reveal/env
header('Content-Type: application/json; charset=utf-8');
$info = [
    'sapi' => php_sapi_name(),
    'cwd' => getcwd(),
    'script_dir' => __DIR__,
    'project_root' => realpath(__DIR__ . '/..'),
    'error_log_ini' => ini_get('error_log'),
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors')
];
echo json_encode($info, JSON_UNESCAPED_UNICODE);
?>