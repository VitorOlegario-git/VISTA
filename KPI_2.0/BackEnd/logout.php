<?php
require_once __DIR__ . '/helpers.php';

// Destroi sessão de forma segura
destruirSessao();

// Redireciona para login
header("Location: " . url('FrontEnd/tela_login.php'));
exit;
?>
