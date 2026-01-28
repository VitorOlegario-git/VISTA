<?php
require_once __DIR__ . '/../BackEnd/config.php';
$mysqli = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    echo "DB_FAIL: " . $mysqli->connect_error . PHP_EOL;
    exit(1);
}
$sql = "CREATE OR REPLACE VIEW vw_resumo_estado_real_normalized AS SELECT * FROM vw_resumo_estado_real";
if (!@$mysqli->query($sql)) {
    echo "VIEW_FAIL: " . $mysqli->error . PHP_EOL;
    exit(2);
}
echo "VIEW_OK" . PHP_EOL;
?>