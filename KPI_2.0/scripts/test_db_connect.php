<?php
require_once __DIR__ . '/../BackEnd/config.php';
echo "DEBUG: DB_HOST=" . DB_HOST . " DB_USER=" . DB_USERNAME . " DB_NAME=" . DB_NAME . PHP_EOL;
$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    echo "DB_FAIL: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
echo "DB_OK" . PHP_EOL;
?>