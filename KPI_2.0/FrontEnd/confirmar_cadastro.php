<?php
require_once __DIR__ . '/../BackEnd/helpers.php';

$token = sanitizeInput($_GET['token'] ?? '');
$sucesso = false;
$mensagem = '';

if (empty($token)) {
    $mensagem = "Token inválido ou ausente.";
} else {
    try {
        $db = getDb();
        
        // Verifica se o token existe
        $dados = $db->fetchOne(
            "SELECT nome, email, senha FROM usuarios_temp WHERE token = ?",
            [$token],
            's'
        );
        
        if ($dados) {
            $db->beginTransaction();
            
            try {
                // Insere na tabela final
                $db->insert(
                    "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)",
                    [$dados['nome'], $dados['email'], $dados['senha']],
                    'sss'
                );
                
                // Remove da tabela temporária
                $db->execute(
                    "DELETE FROM usuarios_temp WHERE token = ?",
                    [$token],
                    's'
                );
                
                $db->commit();
                $sucesso = true;
                $mensagem = "Cadastro confirmado com sucesso!";
            } catch (Exception $e) {
                $db->rollback();
                error_log("Erro ao confirmar cadastro: " . $e->getMessage());
                $mensagem = "Erro ao processar confirmação. Tente novamente.";
            }
        } else {
            $mensagem = "Token inválido ou expirado.";
        }
    } catch (Exception $e) {
        error_log("Erro na confirmação: " . $e->getMessage());
        $mensagem = "Erro ao processar solicitação.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Cadastro - VISTA</title>
    <link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #001e3c, #0066cc);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .sucesso { color: #28a745; }
        .erro { color: #dc3545; }
        h2 { margin: 20px 0; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover { background: #0052a3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon <?php echo $sucesso ? 'sucesso' : 'erro'; ?>">
            <?php echo $sucesso ? '✅' : '❌'; ?>
        </div>
        <h2><?php echo htmlspecialchars($mensagem); ?></h2>
        <?php if ($sucesso): ?>
            <p>Sua conta foi ativada com sucesso! Agora você pode fazer login no sistema.</p>
            <a href="tela_login.php" class="btn">Fazer Login</a>
        <?php else: ?>
            <p>Tente solicitar um novo cadastro ou entre em contato com o administrador.</p>
            <a href="CadastroUsuario.php" class="btn">Voltar ao Cadastro</a>
        <?php endif; ?>
    </div>
</body>
</html>
