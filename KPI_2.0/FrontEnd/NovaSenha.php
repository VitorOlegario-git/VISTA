<?php
session_start();
require_once __DIR__ . '/../BackEnd/conexao.php';

$token = $_GET['token'] ?? '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
    $novaSenha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
    $tokenPost = $_POST['token'];

    $sql = "SELECT id FROM usuarios WHERE token_recuperacao = ? AND expira_token > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tokenPost);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
        $sqlUpdate = "UPDATE usuarios SET senha = ?, token_recuperacao = NULL, expira_token = NULL WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("si", $novaSenha, $usuario['id']);
        $stmtUpdate->execute();

        $mensagem = "Senha redefinida com sucesso! <a href='tela_login.php'>Faça login</a>";
    } else {
        $mensagem = "Token inválido ou expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nova Senha</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/recuperar_senha.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
</head>
<body>
    <div class="header">
        <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
    </div>

    <div class="recuperar-container">
        <h2>Definir Nova Senha</h2>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <label for="nova_senha">Nova senha:</label>
            <input type="password" name="nova_senha" id="nova_senha" required>

            <input type="submit" value="Redefinir Senha">
        </form>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-retorno"><?php echo $mensagem; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

