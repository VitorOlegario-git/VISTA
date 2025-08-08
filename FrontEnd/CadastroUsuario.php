<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../BackEnd/conexao.php';

require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username'], $_POST['email'], $_POST['senha'])) {
        $nome = trim($_POST['username']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        // Validação
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Formato de e-mail inválido.";
        } elseif (!str_ends_with($email, '@suntechdobrasil.com.br')) {
            $erro = "Somente e-mails @suntechdobrasil.com.br são permitidos.";
        } elseif (!checkdnsrr(substr(strrchr($email, "@"), 1), "MX")) {
            $erro = "Domínio de e-mail inválido ou inativo.";
        } elseif (strlen($senha) < 6) {
            $erro = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            // Verifica se já existe
            $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                // Tudo ok, prosseguir com cadastro temporário
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(16));

                $stmt = $conn->prepare("INSERT INTO usuarios_temp (nome, email, senha, token) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $email, $senhaHash, $token);
                $stmt->execute();

                // Envia e-mail de confirmação
                $mail = new PHPMailer();
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // ajuste aqui
                $mail->SMTPAuth = true;
                $mail->Username = 'vitor.olegario@suntechdobrasil.com.br'; // ajuste aqui
                $mail->Password = 'kfwgfntsuqolxlqr'; // ajuste aqui
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nao-responda@seudominio.com', 'Sistema VISTA');
                $mail->addAddress($email, $nome);
                $mail->Subject = 'Confirme seu cadastro';
                $mail->Body = "Olá $nome,\n\nClique no link para ativar sua conta:\n" .
                    "http://172.16.0.50/sistema/KPI_2.0/FrontEnd/confirmar_cadastro.php?token=$token";

                if ($mail->send()) {
                    echo "Cadastro pendente de confirmação. Verifique seu e-mail.";
                    exit;
                } else {
                    $erro = "Erro ao enviar o e-mail. Tente novamente.";
                }
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
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
</head>
<body>
    <div class="header"></div>
    <div class="registro-container">
        <h2>Registro</h2>

        <?php 
        if (isset($_GET['erro']) && $_GET['erro'] == 'email_existente') {
            echo "<p class='error-message'>Erro: E-mail ou usuário já cadastrado!</p>";
        }

        if (isset($erro)) {
            echo "<p class='error-message'>Erro: $erro</p>";
        }
        ?>

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