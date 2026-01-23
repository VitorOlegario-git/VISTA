<?php
// inventario_status.php
// Tela: Atribuir Armário — Carga Inicial
// Substituição conforme solicitação: mantém arquivo, includes padrão e estrutura do sistema.

// Incluir helpers e DB
require_once __DIR__ . '/BackEnd/helpers.php';
require_once __DIR__ . '/BackEnd/Database.php';

// Sessão e segurança
session_start();
verificarSessao();
definirHeadersSeguranca();

$db = getDb();
$message = null;
$error = null;

// Processamento POST: atribuir armário em lote com auditoria
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificarCSRF();

        $armario_id = intval($_POST['armario_id'] ?? 0);
        $remessas = $_POST['remessas'] ?? [];

        if ($armario_id <= 0) {
            throw new Exception('Armário inválido');
        }

        if (!is_array($remessas) || count($remessas) === 0) {
            throw new Exception('Nenhuma remessa selecionada');
        }

        // Sanitiza e converte para inteiros únicos
        $ids = array_values(array_unique(array_map('intval', $remessas)));
        if (count($ids) === 0) {
            throw new Exception('Nenhuma remessa válida');
        }

        // Primeiro, determina quais IDs são elegíveis (armario_id IS NULL)
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $typesIds = str_repeat('i', count($ids));
        $eligibleRows = $db->fetchAll("SELECT id FROM resumo_geral WHERE id IN ($placeholders) AND (armario_id IS NULL OR armario_id = '')", $ids, $typesIds);

        $eligibleIds = array_map(function($r){ return (int)$r['id']; }, $eligibleRows ?: []);

        if (count($eligibleIds) === 0) {
            $message = 'Nenhuma remessa foi modificada. Talvez já possuam armário definido.';
        } else {
            // Atualiza apenas os elegíveis e registra auditoria para cada um
            $db->beginTransaction();
            try {
                $placeholdersElig = implode(',', array_fill(0, count($eligibleIds), '?'));
                $sqlUpdate = "UPDATE resumo_geral SET armario_id = ? WHERE id IN ($placeholdersElig)";
                $paramsUpdate = array_merge([$armario_id], $eligibleIds);
                $typesUpdate = str_repeat('i', count($paramsUpdate));
                $affected = $db->execute($sqlUpdate, $paramsUpdate, $typesUpdate);

                // Inserir auditoria apenas para os IDs que realmente foram atualizados
                if ($affected > 0) {
                    $usuario = getUsuarioId() ?? 0;
                    foreach ($eligibleIds as $rid) {
                        // Insere linha de auditoria
                        $db->insert('INSERT INTO inventario_atribuicoes (resumo_id, armario_id, atribuido_por) VALUES (?, ?, ?)', [$rid, $armario_id, $usuario], 'iii');
                    }
                }

                $db->commit();
                $message = "Armário atribuído com sucesso para {$affected} remessas.";
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
        }

    } catch (Exception $e) {
        error_log('inventario_status error: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Busca remessas aguardando_pg sem armário
$remessas = $db->fetchAll("SELECT id, codigo_remessa, cliente_nome, nota_fiscal, status FROM resumo_geral WHERE status = 'aguardando_pg' AND (armario_id IS NULL OR armario_id = '') ORDER BY id DESC LIMIT 1000");

// Busca armários ativos
$armarios = $db->fetchAll('SELECT id, codigo, descricao FROM armarios WHERE ativo = 1 ORDER BY codigo ASC');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Atribuir Armário — Carga Inicial</title>
    <?php echo metaCSRF(); ?>
    <style>
        /* Reutiliza estilos do sistema; manter minimal inline just in case */
        body{font-family:Inter,system-ui,sans-serif;background:#0b1220;color:#e5e7eb;padding:18px}
        .card{background:rgba(255,255,255,0.03);padding:16px;border-radius:12px;margin-bottom:14px}
        .muted{color:#9ca3af;font-size:14px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.04);text-align:left}
        .btn{padding:10px 14px;border-radius:8px;font-weight:600;cursor:pointer}
        .btn-primary{background:#3b82f6;color:#fff;border:none}
        .btn-disabled{opacity:0.5;pointer-events:none}
        .alert-success{background:rgba(16,185,129,0.08);color:#10b981;padding:10px;border-radius:8px}
        .alert-error{background:rgba(239,68,68,0.06);color:#f87171;padding:10px;border-radius:8px}
        .top-actions{display:flex;gap:12px;align-items:center}
        .flex-right{margin-left:auto}
        input[type=checkbox]{transform:scale(1.1)}
        select{padding:8px;border-radius:6px}
    </style>
</head>
<body>
<header class="card">
    <h1>Atribuir Armário — Carga Inicial</h1>
    <p class="muted">Selecione remessas aguardando pagamento e atribua o armário físico. Esta ação não altera status financeiro nem cria registros de inventário.</p>
</header>

<main>
    <?php if ($message): ?>
        <div class="card alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="card alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (count($remessas) === 0): ?>
        <div class="card muted">Não existem remessas aguardando pagamento sem armário definido.</div>
    <?php else: ?>

    <form method="post" id="atribuirForm" class="card">
        <?php echo campoCSRF(); ?>
        <div class="top-actions">
            <div>
                <label for="armarioSelect">Escolha o armário</label>
                <select id="armarioSelect" name="armario_id" required>
                    <option value="">-- selecione --</option>
                    <?php foreach ($armarios as $a): ?>
                        <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['codigo'] . ' - ' . $a['descricao']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-right">
                <button id="btnAssign" class="btn btn-primary btn-disabled" type="submit" disabled>Atribuir Armário</button>
            </div>
        </div>

        <div style="margin-top:12px;overflow:auto;max-height:520px">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="chkAll"></th>
                        <th>ID / Código</th>
                        <th>Cliente</th>
                        <th>Nota fiscal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($remessas as $r): ?>
                    <tr>
                        <td><input type="checkbox" name="remessas[]" value="<?php echo (int)$r['id']; ?>" class="chkItem"></td>
                        <td><?php echo (int)$r['id']; ?> — <?php echo htmlspecialchars($r['codigo_remessa'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['cliente_nome'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['nota_fiscal'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php endif; ?>
</main>

<script>
// Habilita botão apenas quando houver seleção e armário escolhido
const form = document.getElementById('atribuirForm');
const armarioSelect = document.getElementById('armarioSelect');
const btnAssign = document.getElementById('btnAssign');
const chkAll = document.getElementById('chkAll');

function updateButtonState(){
    if(!form) return;
    const anyChecked = Array.from(document.querySelectorAll('.chkItem')).some(c => c.checked);
    const armSelected = armarioSelect && armarioSelect.value !== '';
    if(anyChecked && armSelected){
        btnAssign.disabled = false;
        btnAssign.classList.remove('btn-disabled');
    } else {
        btnAssign.disabled = true;
        btnAssign.classList.add('btn-disabled');
    }
}

document.addEventListener('change', (e)=>{
    if(e.target.classList && e.target.classList.contains('chkItem')){
        updateButtonState();
    }
});

if(armarioSelect){ armarioSelect.addEventListener('change', updateButtonState); }

if(chkAll){
    chkAll.addEventListener('change', ()=>{
        const checked = chkAll.checked;
        document.querySelectorAll('.chkItem').forEach(c => c.checked = checked);
        updateButtonState();
    });
}

// Prevent double submit
if(form){
    form.addEventListener('submit', ()=>{
        btnAssign.disabled = true; btnAssign.classList.add('btn-disabled');
    });
}
</script>

</body>
</html>
