<?php
/**
 * 🧪 TESTE MÍNIMO - Apenas echo JSON
 */

header('Content-Type: application/json; charset=utf-8');
echo '{"status":"ok","message":"Endpoint funcionando","params":' . json_encode($_GET) . '}';
?>
