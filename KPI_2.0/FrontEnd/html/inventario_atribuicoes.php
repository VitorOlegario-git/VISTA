<?php
// inventario_atribuicoes.php
// Visualizador simples de auditoria de atribuições de armário
require_once __DIR__ . '/../../BackEnd/helpers.php';
require_once __DIR__ . '/../../BackEnd/Database.php';

verificarSessao();
definirHeadersSeguranca();

$db = getDb();

// filtros opcionais
$f_resumo = intval($_GET['resumo_id'] ?? 0);
$f_armario = intval($_GET['armario_id'] ?? 0);
$f_usuario = intval($_GET['usuario_id'] ?? 0);

$where = [];
$params = [];
$types = '';

if ($f_resumo > 0) { $where[] = 'ia.resumo_id = ?'; $params[] = $f_resumo; $types .= 'i'; }
if ($f_armario > 0) { $where[] = 'ia.armario_id = ?'; $params[] = $f_armario; $types .= 'i'; }
if ($f_usuario > 0) { $where[] = 'ia.atribuido_por = ?'; $params[] = $f_usuario; $types .= 'i'; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT ia.id, ia.resumo_id, ia.armario_id, ia.atribuido_por, ia.atribuido_em, a.codigo AS armario_codigo, u.username AS usuario_nome
        FROM inventario_atribuicoes ia
        LEFT JOIN armarios a ON a.id = ia.armario_id
        LEFT JOIN (SELECT id, username FROM usuarios) u ON u.id = ia.atribuido_por
        $whereSql
        ORDER BY ia.atribuido_em DESC
        LIMIT 500";

$rows = $db->fetchAll($sql, $params, $types);

// lista de armários e usuários para filtros rápidos
$armarios = $db->fetchAll('SELECT id, codigo FROM armarios WHERE ativo = 1 ORDER BY codigo ASC');
$usuarios = $db->fetchAll('SELECT id, username FROM usuarios ORDER BY username ASC');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Auditoria — Atribuições de Armário</title>
    <style>
        body{font-family:Inter,system-ui,sans-serif;background:#0b1220;color:#e5e7eb;padding:18px}
        .card{background:rgba(255,255,255,0.03);padding:12px;border-radius:10px;margin-bottom:10px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,0.04)}
        .muted{color:#9ca3af}
    </style>
</head>
<body>
<header class="card">
    <h1>Auditoria — Atribuições de Armário</h1>
    <p class="muted">Histórico das atribuições iniciais (apenas carga inicial).</p>
</header>

<section class="card">
    <form method="get" style="display:flex;gap:8px;align-items:center">
        <label>Resumo ID <input name="resumo_id" value="<?php echo $f_resumo ?: ''; ?>" style="width:90px"></label>
        <label>Armário
            <select name="armario_id">
                <option value="">--todos--</option>
                <?php foreach($armarios as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>" <?php if($f_armario==(int)$a['id']) echo 'selected'; ?>><?php echo htmlspecialchars($a['codigo']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Usuário
            <select name="usuario_id">
                <option value="">--todos--</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?php echo (int)$u['id']; ?>" <?php if($f_usuario==(int)$u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['username']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filtrar</button>
    </form>
</section>

<section class="card">
    <table>
        <thead>
            <tr><th>ID</th><th>Resumo ID</th><th>Armário</th><th>Usuário</th><th>Quando</th></tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
            <tr>
                <td><?php echo (int)$r['id']; ?></td>
                <td><?php echo (int)$r['resumo_id']; ?></td>
                <td><?php echo htmlspecialchars($r['armario_codigo'] ?? $r['armario_id']); ?></td>
                <td><?php echo htmlspecialchars($r['usuario_nome'] ?? $r['atribuido_por']); ?></td>
                <td><?php echo htmlspecialchars($r['atribuido_em']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

</body>
</html>
