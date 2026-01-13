<?php
require_once __DIR__ . '/helpers.php';

// Destrói sessão de forma segura
destruirSessao();

// Redireciona para login
header("Location: " . url('FrontEnd/tela_login.php'));
exit;
?>
