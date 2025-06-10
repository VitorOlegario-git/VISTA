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

    if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    if (password_verify($senha, $usuario['senha'])) {
        session_regenerate_id(true); // Segurança contra fixação de sessão
        $_SESSION['username'] = $usuario['nome'];
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['last_activity'] = time();

        header("Location: /localhost/FrontEnd/html/PaginaPrincipal.php");
        exit;
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
    <title>VISTA</title>
    <link rel="icon" href="/localhost/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="/localhost/FrontEnd/CSS/tela_login.css">
</head>
<body>
    <div class="header"></div>

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
    </div>
</body>
</html>
