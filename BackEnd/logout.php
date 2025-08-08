<?php
session_start();
session_destroy();
header("Location: /sistema/KPI_2.0/FrontEnd/tela_login.php");
exit;
?>
