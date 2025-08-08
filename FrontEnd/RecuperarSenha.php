<?php
session_start();

require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../BackEnd/conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $emailDigitado = trim($_POST['username']);

    $sql = "SELECT id, nome, email FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $emailDigitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        $token = bin2hex(random_bytes(16));
        $expira = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $sqlUpdate = "UPDATE usuarios SET token_recuperacao = ?, expira_token = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssi", $token, $expira, $usuario['id']);
        $stmtUpdate->execute();

        $link = "http://172.16.0.50/sistema/KPI_2.0/FrontEnd/NovaSenha.php?token=$token";
        $emailDestino = $usuario['email'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'vitor.olegario@suntechdobrasil.com.br'; // ✅ SEU e-mail real
            $mail->Password = 'kfwgfntsuqolxlqr';        // ✅ Senha de aplicativo sem espaço
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('vitor.olegario@suntechdobrasil.com.br', 'Sistema VISTA');
            $mail->addAddress($emailDestino, $usuario['nome']);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacao de Senha - Sistema VISTA';
            $mail->Body = "
                <p>Olá, <strong>{$usuario['nome']}</strong>!</p>
                <p>Você solicitou a redefinição de senha. Clique no link abaixo para continuar:</p>
                <p><a href='$link'>$link</a></p>
                <p><small>Este link expira em 1 hora.</small></p>
            ";

            $mail->send();
            $mensagem = "Um link de recuperação foi enviado para seu e-mail.";
        } catch (Exception $e) {
            $mensagem = "Erro ao enviar o e-mail: " . $mail->ErrorInfo;
        }
    } else {
        $mensagem = "E-mail não encontrado.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="/sistema/KPI_2.0/FrontEnd/CSS/recuperar_senha.css">
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
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

