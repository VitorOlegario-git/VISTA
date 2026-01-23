<?php
// Teste temporário de conexão com DB. Não deve expor credenciais em produção.
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

// Executa tentativa de conexão e reporta resultado de forma segura
try {
    $db = Database::getInstance();
    echo "OK";
    exit;
} catch (Exception $e) {
    // Log com detalhe no servidor para diagnóstico (não exibimos senha)
    error_log('[DB_TEST] Exception: ' . $e->getMessage());

    // Se estiver em modo debug, também exibimos mensagem (útil em dev)
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo 'ERROR: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    } else {
        echo 'Banco indisponível';
    }
    exit;
}
?>