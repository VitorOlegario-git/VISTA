<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
require_once __DIR__ . '/../BackEnd/Validator.php';
require_once __DIR__ . '/../BackEnd/EmailService.php';
// Garantir acesso ao DB e funções de conexão
require_once __DIR__ . '/../BackEnd/conexao.php';

$erro = '';
$sucesso = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username'], $_POST['email'], $_POST['senha'])) {
        $nome = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $senha = $_POST['senha'];
        
        // Validação com Validator
        $validator = validator();
        $validator->required($nome, 'nome');
        $validator->minLength($nome, 3, 'nome');
        $validator->corporateEmail($email);
        $validator->minLength($senha, 6, 'senha');
        
        if ($validator->hasErrors()) {
            $erro = $validator->getFirstError();
        } else {
            try {
                $db = getDb();
                
                // Verifica se já existe
                $existe = $db->fetchOne(
                    "SELECT 1 FROM usuarios WHERE email = ?",
                    [$email],
                    's'
                );
                
                if ($existe) {
                    $erro = "Este e-mail já está cadastrado.";
                } else {
                    // Cadastro temporário
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $token = bin2hex(random_bytes(32));
                    
                    $db->insert(
                        "INSERT INTO usuarios_temp (nome, email, senha, token) VALUES (?, ?, ?, ?)",
                        [$nome, $email, $senhaHash, $token],
                        'ssss'
                    );
                    
                    // Envia email de confirmação
                    $emailService = emailService();
                    try {
                        $enviado = $emailService->enviarConfirmacaoCadastro($email, $nome, $token);
                    } catch (Exception $e) {
                        $enviado = false;
                        error_log("Exception ao enviar email de cadastro: " . $e->getMessage());
                    }

                    if ($enviado) {
                        // Redirecionar para tela padrão de sucesso (usar front controller em query-mode)
                        header("Location: /router_public.php?url=cadastro-realizado");
                        exit();
                    } else {
                        $erro = "Erro ao enviar o e-mail. Tente novamente.";
                        error_log("Erro ao enviar email de cadastro: " . $emailService->getErro());
                    }
                }
            } catch (Exception $e) {
                error_log("Erro no cadastro: " . $e->getMessage());
                $erro = "Erro ao processar cadastro. Tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="CSS/CadastroUsuario.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
</head>
<body>

<header class="header">
    <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
</header>

<main class="page-wrapper">
    <div class="login-shell" id="loginShell">
        <div class="frame-corner corner-tl"></div>
        <div class="frame-corner corner-tr"></div>
        <div class="frame-corner corner-bl"></div>
        <div class="frame-corner corner-br"></div>

        <div class="login-container" id="loginContainer">
            <h2>Registro</h2>

            <?php if (!empty($sucesso)): ?>
                <p class="success-message"><?php echo htmlspecialchars($sucesso); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($erro)): ?>
                <p class="error-message"><?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" autocomplete="email" required>
                </div>

                <div class="input-group">
                    <label for="username">Usuário:</label>
                    <input type="text" id="username" name="username" autocomplete="username" required>
                </div>

                <div class="input-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" autocomplete="new-password" required minlength="6">
                </div>

                <button type="submit" class="btn-login" id="btnRegister">
                    <span class="btn-text">Registrar</span>
                    <span class="btn-spinner"></span>
                </button>
            </form>

            <a class="register-link" href="tela_login.php">Voltar</a>
        </div>
    </div>
</main>

<script>
// Expansão do frame e loading no botão, same as tela_login
const shell = document.getElementById('loginShell');
const form = document.getElementById('registerForm');
const btn = document.getElementById('btnRegister');

window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        shell.classList.add('expanded');
    }, 300);
});

form.addEventListener('submit', function() {
    btn.classList.add('loading');
});
</script>

<footer>
    <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
</footer>

</body>
</html>