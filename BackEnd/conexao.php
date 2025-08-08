<?php
// Estabelece a conexão com o banco de dados
$servername = "localhost";
$username = "kpi";
$password = "kpi456";
$dbname = "vista";

$conn = new mysqli($servername, $username, $password, $dbname);


// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

?>
