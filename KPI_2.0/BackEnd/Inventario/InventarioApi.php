<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

// Ensure responses are JSON (helpers/jsonResponse will also set header)
header('Content-Type: application/json; charset=utf-8');

// Validate session for API consumers; do not redirect to HTML login
if (!verificarSessao(false)) {
    jsonUnauthorized();
}

definirHeadersSeguranca();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $db = getDb();

    if ($action === 'list') {
        // Make `armario` optional for compatibility with frontend that may not send it.
        $armarioCode = sanitizeInput($_GET['armario'] ?? '');
        $arm = null;
        $armario_id = null;
        if (!empty($armarioCode)) {
            // Resolve armário por código when provided; keep existing behavior (404) if not found
            $arm = $db->fetchOne('SELECT id, codigo, descricao FROM armarios WHERE codigo = ? LIMIT 1', [$armarioCode]);
            if (!$arm) jsonError('Armário não encontrado', 404);
            $armario_id = (int)$arm['id'];
        }

        // Busca ciclo aberto para o mês atual
        $mesAtual = date('Y-m');
        $ciclo = $db->fetchOne('SELECT id, mes_ano, aberto_at, encerrado_at FROM inventario_ciclos WHERE mes_ano = ? AND encerrado_at IS NULL LIMIT 1', [$mesAtual]);

        // Verifica se existe ciclo aberto anterior não encerrado (vencido)
        $prevOpen = $db->fetchOne('SELECT id, mes_ano FROM inventario_ciclos WHERE mes_ano <> ? AND encerrado_at IS NULL LIMIT 1', [$mesAtual]);

        // Build SQL and params conditionally: apply armario filter only when armario_id is provided
        $params = [$mesAtual];
        $sql = "SELECT rg.id, rg.codigo_remessa, rg.cliente_nome, rg.nota_fiscal, rg.status,
                       (SELECT COUNT(1) FROM inventario_registros ir JOIN inventario_ciclos ic ON ic.id = ir.ciclo_id WHERE ir.resumo_id = rg.id AND ic.encerrado_at IS NULL AND ic.id = (SELECT id FROM inventario_ciclos WHERE mes_ano = ? AND encerrado_at IS NULL LIMIT 1)) AS confirmado_no_ciclo_atual
                FROM resumo_geral rg
                WHERE rg.status = 'aguardando_pg'";

        if ($armario_id !== null) {
            $sql .= " AND rg.armario_id = ?";
            $params[] = $armario_id;
        }

        $sql .= "\n                  AND NOT EXISTS (
                    SELECT 1 FROM inventario_registros ir
                    JOIN inventario_ciclos ic ON ic.id = ir.ciclo_id
                    WHERE ir.resumo_id = rg.id AND ic.encerrado_at IS NOT NULL
                )
                ORDER BY rg.id DESC";

        $rows = $db->fetchAll($sql, $params);

        jsonSuccess(['armario' => $arm, 'ciclo' => $ciclo, 'prev_open' => $prevOpen, 'items' => $rows]);
    }

    if ($action === 'confirm') {
        verificarCSRF();
        // Normalize input: accept either `remessa_id` or `resumo_id` from frontend
        $resumo_id = intval($_POST['resumo_id'] ?? $_POST['remessa_id'] ?? 0);
        $ciclo_id = intval($_POST['ciclo_id'] ?? 0);

        // If ciclo_id not provided by the frontend, try to resolve the active cycle on backend
        // Fallback rule: select latest open cycle (status='aberto' or encerrado_at IS NULL) ordered by aberto_at DESC
        // This centralizes the active-cycle resolution in backend and keeps frontend compatibility.
        if ($ciclo_id <= 0) {
            $active = $db->fetchOne("SELECT id FROM inventario_ciclos WHERE status = 'aberto' OR encerrado_at IS NULL ORDER BY aberto_at DESC LIMIT 1");
            if (!$active || empty($active['id'])) {
                jsonResponse(['success' => false, 'error' => 'NO_ACTIVE_CYCLE'], 409);
            }
            $ciclo_id = (int)$active['id'];
        }

        if ($resumo_id <= 0) jsonError('Dados inválidos');

        $db->beginTransaction();
        try {
            // idempotente: insere ou atualiza inventario_registros
            $existing = $db->fetchOne('SELECT id FROM inventario_registros WHERE ciclo_id = ? AND resumo_id = ? LIMIT 1', [$ciclo_id, $resumo_id], 'ii');
            if ($existing) {
                // atualiza timestamp, usuario e mantém armario_id/observacao quando aplicável
                $db->execute('UPDATE inventario_registros SET inventariado_por = ?, inventariado_em = NOW() WHERE id = ?', [getUsuarioId(), $existing['id']], 'ii');
            } else {
                // busca armario_id atual da remessa/resumo
                $rg = $db->fetchOne('SELECT armario_id FROM resumo_geral WHERE id = ? LIMIT 1', [$resumo_id], 'i');
                $armarioId = $rg['armario_id'] ?? null;
                $db->insert('INSERT INTO inventario_registros (ciclo_id, armario_id, resumo_id, inventariado_por) VALUES (?, ?, ?, ?)', [$ciclo_id, $armarioId, $resumo_id, getUsuarioId()], 'iiii');
            }

            // Atualiza coluna de última confirmação no resumo_geral (usar resumo_id normalizado)
            $db->execute('UPDATE resumo_geral SET ultima_confirmacao_inventario = NOW() WHERE id = ?', [$resumo_id], 'i');

            $db->commit();
            jsonSuccess([], 'Item confirmado');
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    if ($action === 'notfound') {
        verificarCSRF();
        // Normalize input: accept `descricao` OR `observacao`, and `resumo_id` OR `remessa_id`
        $descricao = sanitizeInput($_POST['descricao'] ?? $_POST['observacao'] ?? '');
        $resumo_id = intval($_POST['resumo_id'] ?? $_POST['remessa_id'] ?? 0);
        $ciclo_id = intval($_POST['ciclo_id'] ?? 0);

        // Fallback to active cycle when missing (centralized rule)
        if ($ciclo_id <= 0) {
            $active = $db->fetchOne("SELECT id FROM inventario_ciclos WHERE status = 'aberto' OR encerrado_at IS NULL ORDER BY aberto_at DESC LIMIT 1");
            if (!$active || empty($active['id'])) {
                jsonResponse(['success' => false, 'error' => 'NO_ACTIVE_CYCLE'], 409);
            }
            $ciclo_id = (int)$active['id'];
        }

        if (empty($descricao) && $resumo_id <= 0) jsonError('Descrição obrigatória');

        try {
            // If resumo_id provided, keep idempotent behavior: update existing registro instead of inserting duplicate
            if ($resumo_id > 0) {
                $existing = $db->fetchOne('SELECT id FROM inventario_registros WHERE ciclo_id = ? AND resumo_id = ? LIMIT 1', [$ciclo_id, $resumo_id], 'ii');
                if ($existing) {
                    // update existing record with observation and timestamp
                    $db->execute('UPDATE inventario_registros SET inventariado_por = ?, inventariado_em = NOW(), observacao = ? WHERE id = ?', [getUsuarioId(), $descricao, $existing['id']], 'isi');
                    jsonSuccess([], 'Registro atualizado como não encontrado');
                } else {
                    // insert linked registro (resumo_id present)
                    $rg = $db->fetchOne('SELECT armario_id FROM resumo_geral WHERE id = ? LIMIT 1', [$resumo_id], 'i');
                    $armarioId = $rg['armario_id'] ?? null;
                    $db->insert('INSERT INTO inventario_registros (ciclo_id, armario_id, resumo_id, inventariado_por, observacao) VALUES (?, ?, ?, ?, ?)', [$ciclo_id, $armarioId, $resumo_id, getUsuarioId(), $descricao], 'iiiis');
                    jsonSuccess([], 'Registro inserido como não encontrado');
                }
            }

            // When resumo_id not provided, insert orphan record (resumo_id NULL)
            $db->insert('INSERT INTO inventario_registros (ciclo_id, armario_id, resumo_id, inventariado_por, observacao) VALUES (?, NULL, NULL, ?, ?)', [$ciclo_id, getUsuarioId(), $descricao], 'iis');
            jsonSuccess([], 'Registro de item não encontrado inserido');

        } catch (Exception $e) {
            jsonError('Erro ao registrar item não encontrado');
        }
    }

    if ($action === 'compare_armario') {
        // DESATIVADO: a comparação foi consolidada em ConsolidacaoApi.php
        // Favor utilizar o endpoint `inventario/conciliacao-api?action=compare_armario`
        jsonError('compare_armario desativado neste endpoint. Use inventario/conciliacao-api', 410);
    }

    if ($action === 'finalize') {
        verificarCSRF();
        $ciclo_id = intval($_POST['ciclo_id'] ?? 0);
        if ($ciclo_id <= 0) jsonError('Ciclo inválido');

        $db->beginTransaction();
        try {
            $affected = $db->execute('UPDATE inventario_ciclos SET encerrado_at = NOW(), encerrado_por = ? WHERE id = ? AND encerrado_at IS NULL', [getUsuarioId(), $ciclo_id], 'ii');
            if ($affected <= 0) {
                $db->rollback();
                jsonError('Não foi possível encerrar ciclo (talvez já encerrado)');
            }

            // Gera snapshot: lista de registros do ciclo
            $snapshotRows = $db->fetchAll('SELECT ir.*, a.codigo AS armario_codigo, rg.codigo_remessa, rg.cliente_nome FROM inventario_registros ir LEFT JOIN armarios a ON a.id = ir.armario_id LEFT JOIN resumo_geral rg ON rg.id = ir.resumo_id WHERE ir.ciclo_id = ?', [$ciclo_id], 'i');
            $snapshot = json_encode($snapshotRows, JSON_UNESCAPED_UNICODE);
            $db->insert('INSERT INTO inventario_relatorios (ciclo_id, criado_por, snapshot) VALUES (?, ?, ?)', [$ciclo_id, getUsuarioId(), $snapshot], 'iis');

            $db->commit();
            jsonSuccess([], 'Ciclo encerrado e relatório gerado');
        } catch (Exception $e) {
            $db->rollback();
            error_log($e->getMessage());
            jsonError('Erro interno');
        }
    }

    jsonError('Ação inválida', 400);

} catch (Exception $e) {
    // Log the original exception for operators
    error_log($e->getMessage());

    // Map database connection problems to a clear frontend message
    $msg = $e->getMessage();
    $lower = strtolower($msg);
    if (strpos($lower, 'database connection failed') !== false || strpos($lower, 'erro de conexão') !== false || strpos($lower, 'connect') !== false) {
        // 503 Service Unavailable indicates infrastructure problem
        jsonResponse(['success' => false, 'error' => 'Banco indisponível'], 503);
    }

    // Default: internal error (do not leak details)
    jsonError('Erro interno');
}

?>
