<?php
require_once __DIR__ . '/../BackEnd/conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username'], $_POST['email'], $_POST['senha'])) {
        $nome = trim($_POST['username']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        // Validação básica
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Formato de e-mail inválido.");
        }

        if (strlen($senha) < 6) {
            die("A senha deve ter pelo menos 6 caracteres.");
        }

        // Hash seguro da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Verifica se o e-mail já existe usando prepared statement
       $check_sql = "SELECT id FROM usuarios WHERE email = ? OR nome = ?";
       $stmt = $conn->prepare($check_sql);
       $stmt->bind_param("ss", $email, $nome);
       $stmt->execute();
       $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            header("Location: CadastroUsuario.php?erro=email_existente");
            exit();
        }

        $stmt->close();

        // Inserindo novo usuário com prepared statement
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nome, $email, $senhaHash);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: tela_login.php?sucesso=cadastro");
            exit();
        } else {
            die("Erro ao cadastrar. Tente novamente.");
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="CSS/CadastroUsuario.css">
    <link rel="icon" href="/localhost/FrontEnd/CSS/imagens/VISTA.png">

</head>
<body>
    <div class="header"></div>
    <div class="registro-container">
        <h2>Registro</h2>
        <?php 
        if (isset($_GET['erro']) && $_GET['erro'] == 'email_existente') {
            echo "<p class='error-message'>Erro: E-mail já cadastrado!</p>";
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
