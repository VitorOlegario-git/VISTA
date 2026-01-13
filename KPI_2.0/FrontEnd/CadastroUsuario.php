<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
require_once __DIR__ . '/../BackEnd/Validator.php';
require_once __DIR__ . '/../BackEnd/EmailService.php';

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
                    if ($emailService->enviarConfirmacaoCadastro($email, $nome, $token)) {
                        $sucesso = "Cadastro pendente de confirmação. Verifique seu e-mail.";
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
    <div class="header"></div>
    <div class="registro-container">
        <h2>Registro</h2>

        <?php if (!empty($sucesso)): ?>
            <p class="success-message"><?php echo htmlspecialchars($sucesso); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($erro)): ?>
            <p class="error-message"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required minlength="6">

            <input type="submit" value="Registrar">
        </form>
        <a class="back-link" href="tela_login.php">Voltar</a>
    </div>

</body>
</html>