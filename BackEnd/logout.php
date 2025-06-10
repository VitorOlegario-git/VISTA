<?php
session_start();
session_destroy();
header("Location: /localhost/FrontEnd/tela_login.php");
exit;
?>
