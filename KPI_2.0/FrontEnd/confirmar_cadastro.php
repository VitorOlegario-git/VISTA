<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
// Garantir conexão com o banco (getDb)
require_once __DIR__ . '/../BackEnd/conexao.php';
// Serviço de email (opcional) para notificar token expirado
require_once __DIR__ . '/../BackEnd/EmailService.php';

$token = sanitizeInput($_GET['token'] ?? '');
$sucesso = false;
$mensagem = '';
// Tempo de expiração do token em horas (configurável)
$expiryHours = 24; // padrão: 24 horas

if (empty($token)) {
    $mensagem = "Token inválido ou ausente.";
} else {
    try {
        $db = getDb();

        // Detecta se existe uma coluna de timestamp/ datetime na tabela usuarios_temp
        $timeColRow = $db->fetchOne(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_temp' AND DATA_TYPE IN ('timestamp','datetime') LIMIT 1",
            [],
            ''
        );

        $timeCol = $timeColRow['COLUMN_NAME'] ?? null;

        // Monta SELECT incluindo coluna de tempo quando disponível
        if ($timeCol) {
            $sql = "SELECT nome, email, senha, `" . $timeCol . "` AS created_at FROM usuarios_temp WHERE token = ?";
            $dados = $db->fetchOne($sql, [$token], 's');
        } else {
            $dados = $db->fetchOne(
                "SELECT nome, email, senha FROM usuarios_temp WHERE token = ?",
                [$token],
                's'
            );
        }

        if ($dados) {
            // Se houver coluna de tempo, validar expiração (1 hora)
            $expired = false;
            if (!empty($dados['created_at'])) {
                $createdTs = strtotime($dados['created_at']);
                if ($createdTs === false) {
                    // Não foi possível interpretar a data; considerar inválido por segurança
                    $expired = true;
                } else {
                    $expirySeconds = $expiryHours * 3600; // usa configuração em horas
                    if (($createdTs + $expirySeconds) < time()) {
                        $expired = true;
                    }
                }
            }

            if ($expired) {
                // Remove token expirado e comunica ao usuário
                try {
                    $db->execute("DELETE FROM usuarios_temp WHERE token = ?", [$token], 's');
                } catch (Exception $e) {
                    error_log("Erro ao remover token expirado: " . $e->getMessage());
                }
                // Registrar remoção no log de tokens expirados (se a tabela existir ou puder ser criada)
                try {
                    $db->insert(
                        "INSERT INTO usuarios_temp_expired_log (token, nome, email, token_created_at, reason, removed_by) VALUES (?, ?, ?, ?, ?, ?)",
                        [$token, $dados['nome'] ?? null, $dados['email'] ?? null, $dados['created_at'] ?? null, 'expired', 'confirm_page'],
                        'ssssss'
                    );
                } catch (Exception $e) {
                    // Se tabela não existir, tentar criá-la e inserir novamente
                    try {
                        $createSql = "CREATE TABLE IF NOT EXISTS usuarios_temp_expired_log (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            token VARCHAR(128) NOT NULL,
                            nome VARCHAR(255) DEFAULT NULL,
                            email VARCHAR(255) DEFAULT NULL,
                            token_created_at DATETIME DEFAULT NULL,
                            removed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            reason VARCHAR(50) DEFAULT 'expired',
                            removed_by VARCHAR(50) DEFAULT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                        $db->execute($createSql);
                        $db->insert(
                            "INSERT INTO usuarios_temp_expired_log (token, nome, email, token_created_at, reason, removed_by) VALUES (?, ?, ?, ?, ?, ?)",
                            [$token, $dados['nome'] ?? null, $dados['email'] ?? null, $dados['created_at'] ?? null, 'expired', 'confirm_page'],
                            'ssssss'
                        );
                    } catch (Exception $e2) {
                        error_log("Não foi possível gravar log de token expirado: " . $e2->getMessage());
                    }
                }
                // Tenta notificar por email se disponível
                if (!empty($dados['email'])) {
                    try {
                           $emailService = emailService();
                           $subject = 'Token de confirmação expirado - Sistema VISTA';
                           $title = 'Token expirado';
                           $link = url('FrontEnd/CadastroUsuario.php');
                           $userName = $dados['nome'] ?? '';
                           $bodyHtml = "<p>" . ($userName ? htmlspecialchars($userName) . ",<br><br>" : '') .
                                 "Seu token de confirmação expirou há mais de {$expiryHours} horas.</p>" .
                                 "<p>Para completar seu registro, acesse o link abaixo e solicite um novo cadastro:</p>" .
                                 "<p><a href='" . $link . "'>Abrir formulário de cadastro</a></p>";

                           $html = $emailService->formatTemplate($title, $bodyHtml, 'pt');
                           $emailService->enviarHTML($dados['email'], $subject, $html, $dados['nome'] ?? '');
                    } catch (Exception $e) {
                        error_log("Erro ao enviar notificação de token expirado: " . $e->getMessage());
                    }
                }
                $mensagem = "Token expirado. Solicite um novo cadastro.";
            } else {
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
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png" type="image/png">
    <link rel="stylesheet" href="CSS/tela_login.css">
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
            <h2>Confirmação de Cadastro</h2>

            <div class="icon-message">
                <div class="icon <?php echo $sucesso ? 'sucesso' : 'erro'; ?>" style="font-size:48px;">
                    <?php echo $sucesso ? '✅' : '❌'; ?>
                </div>
            </div>

            <p class="<?php echo $sucesso ? 'success-message' : 'error-message'; ?>" style="margin-top:18px;">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>

            <?php if ($sucesso): ?>
                <p style="color:#7dd3fc;">Sua conta foi ativada com sucesso! Agora você pode fazer login no sistema.</p>
                <a href="tela_login.php" class="register-link">Fazer Login</a>
            <?php else: ?>
                <p style="color:#7dd3fc;">Tente solicitar um novo cadastro ou entre em contato com o administrador.</p>
                <a href="CadastroUsuario.php" class="register-link">Voltar ao Cadastro</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Animação de expansão similar às outras telas
const shell = document.getElementById('loginShell');
window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() { shell.classList.add('expanded'); }, 300);
});
</script>

<footer>
    <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
</footer>

</body>
</html>
