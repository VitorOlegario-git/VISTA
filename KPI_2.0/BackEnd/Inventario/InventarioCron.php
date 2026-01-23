<?php
/**
 * InventarioCron.php
 * Job CLI para gerenciar ciclos mensais de inventário
 * - Marca ciclos vencidos
 * - Garante no máximo 1 ciclo mensal aberto
 * - Cria novo ciclo mensal automaticamente se necessário
 * - Persiste alertas idempotentes em `sistema_alertas`
 *
 * Uso (cron):
 * # Executar todo dia às 02:00
 * 0 2 * * * php /caminho/para/BackEnd/Inventario/InventarioCron.php
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../../Database.php';

function log_info(string $msg) {
    $line = '[' . date('Y-m-d H:i:s') . '] InventarioCron: ' . $msg;
    // Log to error log and stdout for cron visibility
    error_log($line);
    echo $line . PHP_EOL;
}

try {
    $db = getDb();

    // 0) Ensure alert table exists (idempotent)
    $createAlerts = "CREATE TABLE IF NOT EXISTS sistema_alertas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo VARCHAR(50),
        mensagem TEXT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        resolvido TINYINT DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $db->execute($createAlerts);

    // STEP 1: Identify open monthly cycles (informational)
    $openCycles = $db->fetchAll(
        "SELECT * FROM inventario_ciclos WHERE tipo = ? AND status = ?",
        ['mensal','aberto'], 'ss'
    );

    // STEP 2: Mark expired cycles (periodo_fim < CURDATE())
    $markVencidoSql = "UPDATE inventario_ciclos
        SET status = 'vencido'
        WHERE tipo = 'mensal' AND status = 'aberto' AND periodo_fim < CURDATE()";
    $affected = $db->execute($markVencidoSql);
    if ($affected > 0) {
        log_info("Marked {$affected} cycle(s) as vencido");
        // Create or update an alert for vencido cycles
        $vencCount = $db->fetchOne(
            "SELECT COUNT(*) AS total FROM inventario_ciclos WHERE tipo = 'mensal' AND status = 'vencido'"
        );
        $totalVenc = intval($vencCount['total'] ?? 0);
        if ($totalVenc > 0) {
            // Insert idempotent alert if not exists unresolved
            $exists = $db->fetchOne(
                "SELECT id FROM sistema_alertas WHERE tipo = ? AND resolvido = 0 LIMIT 1",
                ['ciclo_vencido'], 's'
            );
            if (!$exists) {
                $msg = "Existem {$totalVenc} ciclo(s) mensal(is) vencido(s). Verifique e encerre manualmente.";
                $db->insert("INSERT INTO sistema_alertas (tipo, mensagem) VALUES (?, ?)", ['ciclo_vencido', $msg], 'ss');
                log_info('Inserted alerta: ciclo_vencido');
            } else {
                log_info('Alerta ciclo_vencido já existente (não duplicado)');
            }
        }
    } else {
        log_info('Nenhum ciclo marcado como vencido');
    }

    // STEP 3: Check count of open monthly cycles
    $countOpen = $db->fetchOne(
        "SELECT COUNT(*) AS total FROM inventario_ciclos WHERE tipo = ? AND status = ?",
        ['mensal','aberto'], 'ss'
    );
    $openTotal = intval($countOpen['total'] ?? 0);
    log_info("Ciclos mensais abertos: {$openTotal}");

    // STEP 4: Create new monthly cycle if none open (idempotent)
    if ($openTotal === 0) {
        // Use transaction to reduce race window
        $db->beginTransaction();
        try {
            // Re-check inside transaction
            $recheck = $db->fetchOne(
                "SELECT COUNT(*) AS total FROM inventario_ciclos WHERE tipo = ? AND status = ?",
                ['mensal','aberto'], 'ss'
            );
            $reTotal = intval($recheck['total'] ?? 0);
            if ($reTotal === 0) {
                $periodo_inicio = date('Y-m-01');
                $periodo_fim = date('Y-m-t');
                $insertSql = "INSERT INTO inventario_ciclos (tipo, periodo_inicio, periodo_fim, status, criado_por)
                    VALUES (?, ?, ?, 'aberto', NULL)";
                $db->insert($insertSql, ['mensal', $periodo_inicio, $periodo_fim], 'sss');
                $db->commit();
                log_info("Criado novo ciclo mensal: {$periodo_inicio} -> {$periodo_fim}");
                // Insert alert for new cycle created (optional governance)
                $existsNew = $db->fetchOne(
                    "SELECT id FROM sistema_alertas WHERE tipo = ? AND resolvido = 0 LIMIT 1",
                    ['ciclo_aberto_novo'], 's'
                );
                if (!$existsNew) {
                    $msg = "Novo ciclo mensal criado: {$periodo_inicio} até {$periodo_fim}.";
                    $db->insert("INSERT INTO sistema_alertas (tipo, mensagem) VALUES (?, ?)", ['ciclo_aberto_novo', $msg], 'ss');
                    log_info('Inserted alerta: ciclo_aberto_novo');
                }
            } else {
                $db->rollback();
                log_info('Outro processo abriu ciclo antes do commit; operação abortada');
            }
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    } else {
        log_info('Existe pelo menos um ciclo aberto; nenhum novo será criado');
    }

    // STEP 5: Governance alerts
    // Alerta 1: Ciclo vencido existente
    $vencidos = $db->fetchOne(
        "SELECT COUNT(*) AS total FROM inventario_ciclos WHERE tipo = 'mensal' AND status = 'vencido'"
    );
    $vencTotal = intval($vencidos['total'] ?? 0);
    if ($vencTotal > 0) {
        $exists = $db->fetchOne("SELECT id FROM sistema_alertas WHERE tipo = ? AND resolvido = 0 LIMIT 1", ['ciclo_vencido'], 's');
        if (!$exists) {
            $msg = "Existem {$vencTotal} ciclo(s) vencido(s). Ação manual necessária.";
            $db->insert("INSERT INTO sistema_alertas (tipo, mensagem) VALUES (?, ?)", ['ciclo_vencido', $msg], 'ss');
            log_info('Inserted alerta (governance): ciclo_vencido');
        }
    }

    // Alerta 2: Ciclo aberto há mais de X dias (10 dias)
    $days = 10;
    $oldOpen = $db->fetchOne(
        "SELECT COUNT(*) AS total FROM inventario_ciclos WHERE tipo = 'mensal' AND status = 'aberto' AND DATEDIFF(CURDATE(), periodo_inicio) > ?",
        [$days], 'i'
    );
    $oldTotal = intval($oldOpen['total'] ?? 0);
    if ($oldTotal > 0) {
        $existsOld = $db->fetchOne("SELECT id FROM sistema_alertas WHERE tipo = ? AND resolvido = 0 LIMIT 1", ['ciclo_aberto_antigo'], 's');
        if (!$existsOld) {
            $msg = "Existem {$oldTotal} ciclo(s) aberto(s) há mais de {$days} dias. Verifique.";
            $db->insert("INSERT INTO sistema_alertas (tipo, mensagem) VALUES (?, ?)", ['ciclo_aberto_antigo', $msg], 'ss');
            log_info('Inserted alerta: cicloe_aberto_antigo');
        }
    }

    log_info('InventarioCron finished successfully');

} catch (Exception $ex) {
    $err = 'Erro InventarioCron: ' . $ex->getMessage();
    error_log($err);
    fwrite(STDERR, $err . PHP_EOL);
    exit(1);
}

?>
