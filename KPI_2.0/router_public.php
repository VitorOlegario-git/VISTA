<?php
/**
 * Sistema VISTA - Front Controller Público
 * Roteador que funciona SEM necessidade de configuração do servidor
 */

// Carrega o sistema de roteamento
require_once __DIR__ . '/router.php';

// Cria e executa o roteador
$router = createRouter();
$router->dispatch();
