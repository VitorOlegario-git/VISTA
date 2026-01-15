<?php
/****************************
 * SEGURANÇA E SETUP
 ****************************/
session_start();

require_once __DIR__ . '/BackEnd/config.php'; // ajuste se necessário

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Vitor Olegario') {
    http_response_code(403);
    echo 'Acesso restrito';
    exit;
}

// Exclusão via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_ids'])) {
    $ids = $_POST['excluir_ids'];
    if (!is_array($ids)) {
        $ids = json_decode($ids, true);
    }
    if (is_array($ids) && count($ids)) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM resumo_geral WHERE id IN ($in)");
            $stmt->execute($ids);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao excluir.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'IDs inválidos.']);
    }
    exit;
}

/****************************
 * FILTROS
 ****************************/
$statusesPermitidos = [
    'envio_analise' => 'Envio à Análise',
    'em_analise' => 'Em Análise',
    'aguardando_pg' => 'Aguardando PG',
    'em_reparo' => 'Em Reparo',
    'aguardando_NF_retorno' => 'Aguardando NF Retorno',
    'qualidade' => 'Qualidade'
];

$status = $_GET['status'] ?? '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!array_key_exists($status, $statusesPermitidos)) {
    $status = '';
}

// CONEXÃO PDO
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Erro ao conectar ao banco de dados.';
    exit;
}

/****************************
 * CONSULTA AO BANCO
 ****************************/
$sql = "SELECT id, razao_social, nota_fiscal, quantidade, status, confirmado
    FROM resumo_geral";

$params = [];

if ($status) {
    $sql .= " WHERE status = ?";
    $params[] = $status;
}

if ($search) {
    $sql .= $status ? " AND" : " WHERE";
    $sql .= " (razao_social LIKE ? OR nota_fiscal LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$remessas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Inventário de Status</title>

<style>
body {
    font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1a2332 100%);
    margin: 0;
    padding: 0;
    color: #e2e8f0;
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    background: #182235;
    padding: 32px 28px;
    border-radius: 18px;
    box-shadow: 0 4px 32px #000a;
    border: 1.5px solid #22304a;
}

.container h1 {
    margin: 0;
    font-size: 2rem;
    color: #e2e8f0;
    letter-spacing: 1px;
    font-weight: 700;
}

.subtitle {
    color: #7b8bbd;
    margin-bottom: 24px;
    font-size: 1.1rem;
}

.status-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.status-btn {
    padding: 8px 20px;
    border-radius: 24px;
    background: #22304a;
    text-decoration: none;
    color: #b6c3e0;
    font-size: 1rem;
    font-weight: 500;
    border: 1.5px solid #22304a;
    transition: background 0.2s, border 0.2s;
    box-shadow: 0 1px 4px #0002;
}
.status-btn:hover {
    background: #2563eb22;
    border-color: #2563eb;
    color: #fff;
}
.status-btn.active {
    background: #2563eb33;
    border-color: #2563eb;
    color: #fff;
}

.filters input {
    padding: 8px 14px;
    font-size: 1rem;
    border-radius: 8px;
    border: 1.5px solid #22304a;
    background: #10172a;
    color: #b6c3e0;
}
.filters button {
    padding: 8px 18px;
    border-radius: 8px;
    background: #2563eb;
    color: #fff;
    border: none;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    box-shadow: 0 1px 4px #0002;
}
.filters button:hover {
    background: #1d4ed8;
}
.filters button:disabled {
    background: #22304a;
    color: #aaa;
    cursor: not-allowed;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 1rem;
    background: #182235;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px #0004;
}
thead {
    background: #22304a;
}
th, td {
    padding: 14px 12px;
    border-bottom: 1.5px solid #22304a;
    text-align: left;
}
th {
    color: #e2e8f0;
    font-weight: 600;
    font-size: 1.08rem;
}
tr:nth-child(even) {
    background: #1a2332;
}
tr.confirmada td {
    opacity: 0.7;
    pointer-events: none;
    background: #182235 !important;
}
tr.confirmada {
    border-left: 5px solid #22c55e !important;
    box-shadow: 0 0 0 2px #22c55e33;
}
tr.selecionada td {
    background: #2563eb !important;
    color: #fff;
}
.actions {
    text-align: center;
}

.ok-btn {
    background: #22c55e;
    color: #fff;
    border: none;
    padding: 8px 18px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.2s;
    box-shadow: 0 1px 8px #22c55e22;
}
.ok-btn:disabled {
    background: #22304a;
    color: #aaa;
    cursor: not-allowed;
}
.delete-btn {
    margin-top: 18px;
    background: #ef4444;
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: background 0.2s;
    box-shadow: 0 1px 8px #ef444422;
}
.delete-btn:disabled {
    background: #22304a;
    color: #aaa;
    cursor: not-allowed;
}
.voltar-btn {
    background: #334155;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 22px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    box-shadow: 0 1px 4px #0002;
    margin-left: 18px;
}
.voltar-btn:hover {
    background: #475569;
}
</style>
</head>

<body>
<div class="container">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
    <div>
        <h1 style="margin-bottom:0;">Inventário de Status de Remessas</h1>
        <div class="subtitle">Validação manual e exclusão controlada</div>
    </div>
    <button onclick="window.location.href='/FrontEnd/html/PaginaPrincipal.php'" class="voltar-btn">⟵ Voltar</button>
</div>


<!-- STATUS -->
<div class="status-bar">
<?php foreach ($statusesPermitidos as $key => $label): ?>
    <a class="status-btn <?= $status === $key ? 'active' : '' ?>"
       href="?status=<?= $key ?>">
        <?= $label ?>
    </a>
<?php endforeach; ?>
</div>

<!-- BUSCA -->
<form method="get" class="filters">
    <?php if ($status): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <?php endif; ?>
    <input type="text" name="search"
           placeholder="Buscar por Razão Social ou NF"
           value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Buscar</button>
</form>

<!-- TABELA -->
<table>
<thead>
<tr>
    <th><input type="checkbox" id="chkMestre" title="Selecionar todos não confirmados"></th>
    <th>Razão Social</th>
    <th>Nota Fiscal</th>
    <th>Qtd.</th>
    <th>Status</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>
<?php if (!$remessas): ?>
<tr><td colspan="6">Nenhuma remessa encontrada.</td></tr>
<?php else: foreach ($remessas as $r): ?>
<tr data-id="<?= $r['id'] ?>"<?= !empty($r['confirmado']) ? ' class="confirmada"' : '' ?>>
    <td>
        <?php if (empty($r['confirmado'])): ?>
            <input type="checkbox" class="chk">
        <?php else: ?>
            <span style="color:#22c55e;"><i class="fas fa-check-circle"></i></span>
        <?php endif; ?>
    </td>
    <td><?= htmlspecialchars($r['razao_social']) ?></td>
    <td><?= htmlspecialchars($r['nota_fiscal']) ?></td>
    <td><?= (int)$r['quantidade'] ?></td>
    <td><?= htmlspecialchars($r['status']) ?></td>
    <td class="actions">
        <button class="ok-btn" onclick="confirmar(this)" <?= !empty($r['confirmado']) ? 'disabled' : '' ?>>OK</button>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>

<button id="btnExcluir" class="delete-btn" disabled onclick="excluir()">Excluir selecionados</button>

</div>

<script>
function confirmar(btn) {
    const tr = btn.closest('tr');
    tr.classList.add('confirmada');
    btn.disabled = true;
    atualizarExcluir();
}

function atualizarExcluir() {
    const marcados = document.querySelectorAll('.chk:checked');
    document.getElementById('btnExcluir').disabled = marcados.length === 0;
}

document.querySelectorAll('.chk').forEach(c =>
    c.addEventListener('change', e => {
        e.target.closest('tr').classList.toggle('selecionada', e.target.checked);
        atualizarExcluir();
    })
);

document.getElementById('chkMestre').addEventListener('change', function() {
    const marcar = this.checked;
    document.querySelectorAll('.chk').forEach(c => {
        if (!c.disabled) c.checked = marcar;
        c.dispatchEvent(new Event('change'));
    });
});

function excluir() {
    if (!confirm('Confirma exclusão dos registros selecionados?')) return;
    const ids = Array.from(document.querySelectorAll('.chk:checked'))
        .map(c => c.closest('tr').getAttribute('data-id'));
    if (!ids.length) return;
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'excluir_ids=' + encodeURIComponent(JSON.stringify(ids))
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            ids.forEach(id => {
                const tr = document.querySelector('tr[data-id="'+id+'"]');
                if (tr) tr.remove();
            });
            atualizarExcluir();
        } else {
            alert('Erro ao excluir: ' + (resp.error || 'Desconhecido'));
        }
    })
    .catch(() => alert('Erro de comunicação com o servidor.'));
}
</script>
</body>
</html>
