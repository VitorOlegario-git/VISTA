<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../BackEnd/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['senha'])) {
    $username = trim($_POST['username']);
    $senha = $_POST['senha'];

    $sql = "SELECT id, nome, senha FROM usuarios WHERE nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        $erro = "Usuário ou senha inválidos.";
    } else {
        $usuario = $resultado->fetch_assoc();

        // Verifica a senha (ajuste se estiver usando hash)
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['username'] = $usuario['nome'];
            header("Location: html/PaginaPrincipal.php"); // ou dashboard
            exit();
        } else {
            $erro = "Usuário ou senha inválidos.";
        }
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">   
    <meta name="description" content="Sistema VISTA - Login">
    <meta name="keywords" content="VISTA, login, sistema, gestão, análise">
    <meta name="author" content="Suntech do Brasil">      
    <title>VISTA</title>
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="/sistema/KPI_2.0/FrontEnd/CSS/tela_login.css">
</head>
<body>

    <div class="header">
        
        <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
    </div>


    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($erro)): ?>
            <p class="error-message"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>

            <input type="submit" value="Login">
        </form>
        <a class="register-link" href="CadastroUsuario.php">Cadastrar Novo Usuário</a>
        <a class="register-link" href="RecuperarSenha.php">Esqueci minha senha</a>
    </div>
</body>
</html>
