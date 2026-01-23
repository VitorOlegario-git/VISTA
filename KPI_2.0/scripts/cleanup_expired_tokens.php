<?php
// Script CLI para remover tokens expirados de usuarios_temp
// Uso: php cleanup_expired_tokens.php [--hours=24] [--notify]

require_once __DIR__ . '/../BackEnd/conexao.php';
require_once __DIR__ . '/../BackEnd/EmailService.php';
require_once __DIR__ . '/../BackEnd/helpers.php';

$options = [];
foreach ($argv as $arg) {
    if (preg_match('/^--hours=(\d+)$/', $arg, $m)) {
        $options['hours'] = (int)$m[1];
    }
    if ($arg === '--notify') {
        $options['notify'] = true;
    }
}
$hours = $options['hours'] ?? 24;
$notify = !empty($options['notify']);

try {
    $db = getDb();

    // Detectar coluna datetime/timestamp
    $timeColRow = $db->fetchOne(
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios_temp' AND DATA_TYPE IN ('timestamp','datetime') LIMIT 1",
        [],
        ''
    );
    $timeCol = $timeColRow['COLUMN_NAME'] ?? null;

    if (!$timeCol) {
        echo "Tabela usuarios_temp não possui coluna de data para expiração.\n";
        exit(1);
    }

    $sql = "SELECT token, nome, email, `" . $timeCol . "` AS created_at FROM usuarios_temp WHERE `" . $timeCol . "` < DATE_SUB(NOW(), INTERVAL ? HOUR)";
    $rows = $db->fetchAll($sql, [$hours], 'i');

    $count = 0;
    foreach ($rows as $r) {
        try {
            $db->execute("DELETE FROM usuarios_temp WHERE token = ?", [$r['token']], 's');
            $count++;
            // Registrar remoção no log (cria tabela se necessário)
            try {
                $db->insert(
                    "INSERT INTO usuarios_temp_expired_log (token, nome, email, token_created_at, reason, removed_by) VALUES (?, ?, ?, ?, ?, ?)",
                    [$r['token'], $r['nome'] ?? null, $r['email'] ?? null, $r['created_at'] ?? null, 'expired', 'cli'],
                    'ssssss'
                );
            } catch (Exception $e) {
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
                        [$r['token'], $r['nome'] ?? null, $r['email'] ?? null, $r['created_at'] ?? null, 'expired', 'cli'],
                        'ssssss'
                    );
                } catch (Exception $e2) {
                    error_log("Erro ao gravar log de token expirado: " . $e2->getMessage());
                }
            }
            if ($notify && !empty($r['email'])) {
                try {
                    $es = emailService();
                    $subject = 'Token de confirmação expirado - Sistema VISTA';
                    $title = 'Token expirado';
                    $link = url('FrontEnd/CadastroUsuario.php');
                    $name = $r['nome'] ?? '';

                    // Monta corpo HTML e aplica template centralizado
                    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $bodyHtml = "<p>Olá " . ($safeName ? $safeName . "!</p>" : "!</p>");
                    $bodyHtml .= "<p>Seu token de confirmação expirou. Para se cadastrar novamente, clique no botão abaixo:</p>";
                    $bodyHtml .= "<p style='margin:16px 0;'><a href='" . $link . "' style='background:#0066cc;color:#fff;padding:10px 16px;text-decoration:none;border-radius:4px;'>Cadastrar novamente</a></p>";
                    $bodyHtml .= "<p><small>Ou copie e cole este link no navegador: " . $link . "</small></p>";

                    $html = $es->formatTemplate($title, $bodyHtml, 'pt');
                    $es->enviarHTML($r['email'], $subject, $html, $name);
                } catch (Exception $e) {
                    error_log("Erro ao notificar email para token expirado: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao remover token expirado ({$r['token']}): " . $e->getMessage());
        }
    }

    echo "Removidos $count tokens expirados (horas={$hours}).\n";
    exit(0);
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(2);
}
