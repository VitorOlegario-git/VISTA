<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
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

        if (password_verify($senha, $usuario['senha'])) {
            // Usa função helper para autenticação segura
            autenticarUsuario($usuario['id'], $usuario['nome']);
            header("Location: /router_public.php?url=dashboard");
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema VISTA - Login">
    <meta name="author" content="Suntech do Brasil">
    <title>VISTA</title>
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png" type="image/png">
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/tela_login.css">
</head>
<body>

<header class="header">
    <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
</header>

<main class="page-wrapper">
    <!-- Frame Futurista -->
    <div class="login-shell" id="loginShell">
        <!-- 4 Cantos do Frame -->
        <div class="frame-corner corner-tl"></div>
        <div class="frame-corner corner-tr"></div>
        <div class="frame-corner corner-bl"></div>
        <div class="frame-corner corner-br"></div>
        
        <!-- Container de Login -->
        <div class="login-container" id="loginContainer">
            <h2>Login</h2>

            <?php if (isset($erro)): ?>
                <p class="error-message"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="input-group">
                    <label for="username">Usuário:</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>

                <div class="input-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Entrar</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <a class="register-link" href="CadastroUsuario.php">Cadastrar Novo Usuário</a>
            <a class="register-link" href="RecuperarSenha.php">Esqueci minha senha</a>
        </div>
    </div>
</main>

<script>
// ========== ANIMAÇÃO DE EXPANSÃO DO FRAME ==========
const shell = document.getElementById('loginShell');
const form = document.getElementById('loginForm');
const btnLogin = document.getElementById('btnLogin');

// Expande o frame automaticamente ao carregar a página
window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        shell.classList.add('expanded');
    }, 300); // Delay de 300ms para dar tempo da página renderizar
});

// ========== LOADING NO BOTÃO ==========
form.addEventListener('submit', function() {
    btnLogin.classList.add('loading');
});
</script>

<footer>
    <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
</footer>

</body>
</html>
