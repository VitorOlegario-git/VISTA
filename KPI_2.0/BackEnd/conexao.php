<?php
/**
 * Arquivo de Conexão com o Banco de Dados
 * Usa variáveis de ambiente para segurança
 */

// Carrega configurações
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

// Mantém compatibilidade com código legado
// Obtém conexão mysqli tradicional
$conn = getConnection();

// Para novo código, use:
// $db = getDb();
// $db->query($sql, $params, $types);
?>
