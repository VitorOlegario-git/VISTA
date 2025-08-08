<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../BackEnd/conexao.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    die("Token inválido ou ausente.");
}

// Verifica se o token existe
$stmt = $conn->prepare("SELECT nome, email, senha FROM usuarios_temp WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $dados = $result->fetch_assoc();

    // Insere na tabela final
    $inserir = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
    $inserir->bind_param("sss", $dados['nome'], $dados['email'], $dados['senha']);
    $inserir->execute();

    // Remove da tabela temporária
    $excluir = $conn->prepare("DELETE FROM usuarios_temp WHERE token = ?");
    $excluir->bind_param("s", $token);
    $excluir->execute();

    echo "<h2>Cadastro confirmado com sucesso! ✅</h2>";
    echo "<p><a href='tela_login.php'>Clique aqui para fazer login</a></p>";

} else {
    echo "<h2>Token inválido ou expirado. ❌</h2>";
}
?>
