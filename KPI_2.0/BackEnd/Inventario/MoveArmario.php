<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../Database.php';

/**
 * Handler para movimentação de armário de uma remessa (resumo_geral)
 * Rota registrada em router.php: /inventario/resumo/{resumo_id}/armario
 * Método: PATCH (aceita também POST por compatibilidade)
 * Payload JSON / form:
 *   { "armario_id": 3, "motivo": "texto" }
 * Regras:
 * - Exige sessão válida
 * - Exige CSRF
 * - Verifica permissão mínima (usuário em ADMIN_USERS ou $_SESSION['is_admin'])
 * - Registra movimentação em `inventario_movimentacoes` para auditoria
 */

function handleMoveArmario(int $resumoId)
{
    if (!verificarSessao(false)) {
        jsonUnauthorized();
    }

    definirHeadersSeguranca();

    // Allow PATCH or POST (for clients that cannot send PATCH easily)
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'PATCH' && $method !== 'POST') {
        jsonError('Método inválido. Use PATCH ou POST.', 405);
    }

    // Read payload: support JSON body (PATCH) or form-encoded (POST)
    $input = [];
    if ($method === 'PATCH') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
    } else {
        // POST
        $input = $_POST;
    }

    // CSRF: accept token via POST form or X-CSRF-Token header for PATCH/JSON
    verificarCSRF();

    // Normalize payload
    $armarioId = isset($input['armario_id']) ? intval($input['armario_id']) : 0;
    $motivo = isset($input['motivo']) ? sanitizeInput($input['motivo']) : '';

    if ($armarioId <= 0) {
        jsonError('armario_id inválido', 400);
    }

    if (empty($motivo)) {
        jsonError('motivo é obrigatório para movimentação', 400);
    }

    // Permission check: prefer env ADMIN_USERS (comma separated) or session is_admin flag
    $allowed = false;
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['is_admin'])) $allowed = true;
    $adminUsersRaw = getenv('ADMIN_USERS') ?: '';
    if ($adminUsersRaw !== '') {
        $admins = array_map('trim', explode(',', $adminUsersRaw));
        if (in_array($_SESSION['username'] ?? '', $admins, true)) $allowed = true;
    }

    // If no ADMIN_USERS configured, allow by default but log a warning (backward compatibility)
    if ($adminUsersRaw === '') {
        error_log('[MoveArmario] ADMIN_USERS not configured; defaulting to permissive move for user ' . ($_SESSION['username'] ?? 'unknown'));
        $allowed = true;
    }

    if (!$allowed) jsonError('Permissão negada', 403);

    try {
        $db = getDb();
        // validate resumo exists
        $rg = $db->fetchOne('SELECT id, armario_id FROM resumo_geral WHERE id = ? LIMIT 1', [$resumoId], 'i');
        if (!$rg) jsonError('Remessa não encontrada', 404);

        $fromArmario = $rg['armario_id'] ? intval($rg['armario_id']) : null;

        // validate target armario exists and is active
        $arm = $db->fetchOne('SELECT id, codigo, descricao, ativo FROM armarios WHERE id = ? LIMIT 1', [$armarioId], 'i');
        if (!$arm) jsonError('Armário destino não encontrado', 404);
        if (isset($arm['ativo']) && intval($arm['ativo']) === 0) jsonError('Armário destino inativo', 400);

        // perform transaction: update resumo_geral.armario_id and insert in inventario_movimentacoes
        $db->beginTransaction();

        // Update resumo_geral even if fromArmario is null
        $db->execute('UPDATE resumo_geral SET armario_id = ? WHERE id = ?', [$armarioId, $resumoId], 'ii');

        // Insert movement audit
        $db->insert('INSERT INTO inventario_movimentacoes (resumo_id, from_armario_id, to_armario_id, motivo, movimentado_por) VALUES (?, ?, ?, ?, ?)', [
            $resumoId,
            $fromArmario,
            $armarioId,
            $motivo,
            getUsuarioId() ?? 0
        ], 'iiisi');

        $db->commit();

        jsonSuccess([], 'Movimentação registrada com sucesso');

    } catch (Exception $e) {
        if (isset($db)) try { $db->rollback(); } catch (Throwable $_) {}
        error_log('[MoveArmario] Error: ' . $e->getMessage());
        jsonError('Erro interno ao mover armário', 500);
    }
}

?>
