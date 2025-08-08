<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/sistema/KPI_2.0/BackEnd/conexao.php";

$cnpj = $_GET['cnpj'] ?? '';
$cnpj = preg_replace('/\D/', '', $cnpj); // Limpa máscara
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj'] ?? '');
    $razao_social = trim($_POST['razao_social'] ?? '');

    if (!empty($cnpj) && !empty($razao_social)) {
        // Verifica se o cliente já existe
        $verifica = $conn->prepare("SELECT id FROM clientes WHERE cnpj = ?");
        $verifica->bind_param("s", $cnpj);
        $verifica->execute();
        $verifica->store_result();

        if ($verifica->num_rows > 0) {
            $mensagem = "⚠️ Este CNPJ já está cadastrado.";
        } else {
            $stmt = $conn->prepare("INSERT INTO clientes (cnpj, razaosocial) VALUES (?, ?)");
            $stmt->bind_param("ss", $cnpj, $razao_social);

            if ($stmt->execute()) {
                $mensagem = "✅ Cliente cadastrado com sucesso! Redirecionando...";
                header("refresh:3;url=/sistema/KPI_2.0/FrontEnd/html/recebimento.php");
                exit; // <- Impede execução do restante do código após redirecionamento
            } else {
                $mensagem = "❌ Erro ao cadastrar cliente.";
            }
            $stmt->close();
        }

        $verifica->close();
    } else {
        $mensagem = "❌ Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Cliente</title>
    <link rel="stylesheet" href="/sistema/KPI_2.0/FrontEnd/CSS/recuperar_senha.css">
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
</head>
<body>

    <div class="recuperar-container">
        <h2>Cadastro de Cliente</h2>

        <form method="POST">
            <label for="cnpj">CNPJ:</label>
            <input type="text" id="cnpj" name="cnpj" value="<?php echo htmlspecialchars($cnpj); ?>" readonly>

            <label for="razao_social">Razão Social:</label>
            <input type="text" id="razao_social" name="razao_social" required>

            <input type="submit" value="Cadastrar Cliente">
        </form>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-retorno"><?php echo $mensagem; ?></p>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const razaoInput = document.getElementById("razao_social");
            if (razaoInput) {
                razaoInput.focus();
            }
        });
    </script>

    <footer>
        <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
    </footer>

</body>
</html>
