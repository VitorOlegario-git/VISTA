<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
require_once __DIR__ . '/../BackEnd/Validator.php';
require_once __DIR__ . '/../BackEnd/EmailService.php';
// Garantir conexão com o banco (getDb / $conn)
require_once __DIR__ . '/../BackEnd/conexao.php';

$mensagem = '';
$tipoMensagem = ''; // 'sucesso' ou 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $emailDigitado = sanitizeInput($_POST['username']);
    
    // Validação
    $validator = validator();
    if (!$validator->required($emailDigitado, 'email') || !$validator->email($emailDigitado)) {
        $mensagem = $validator->getFirstError();
        $tipoMensagem = 'erro';
    } else {
        $db = getDb();
        $usuario = $db->fetchOne(
            "SELECT id, nome, email FROM usuarios WHERE email = ?",
            [$emailDigitado],
            's'
        );
        
        if ($usuario) {
            // Gera token e expiração
            $token = bin2hex(random_bytes(32));
            $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));
            
            // Atualiza usuário com token
            $db->execute(
                "UPDATE usuarios SET token_recuperacao = ?, expira_token = ? WHERE id = ?",
                [$token, $expira, $usuario['id']],
                'ssi'
            );
            
            // Envia email
            $emailService = emailService();
            if ($emailService->enviarRecuperacaoSenha($usuario['email'], $usuario['nome'], $token)) {
                $mensagem = "Um link de recuperação foi enviado para seu e-mail.";
                $tipoMensagem = 'sucesso';
            } else {
                $mensagem = "Erro ao enviar o e-mail. Tente novamente mais tarde.";
                $tipoMensagem = 'erro';
                error_log("Erro ao enviar email de recuperação: " . $emailService->getErro());
            }
        } else {
            // Por segurança, não revelamos se o email existe ou não
            $mensagem = "Se este e-mail estiver cadastrado, você receberá um link de recuperação.";
            $tipoMensagem = 'sucesso';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/recuperar_senha.css'); ?>">
    <link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>">
</head>
<body>

    <div class="header">
        <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
    </div>

    <div class="recuperar-container">
        <h2>Recuperar Senha</h2>
        <form method="POST">
            <label for="username">E-mail:</label>
            <input type="email" id="username" name="username" required>


            <input type="submit" value="Enviar link de recuperação">
        </form>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-retorno"><?php echo $mensagem; ?></p>
        <?php endif; ?>
    </div>

</body>
</html>

