<?php
require_once __DIR__ . '/../../BackEnd/config.php';
session_start();
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventário de Status de Remessas</title>
    <link rel="stylesheet" href="/FrontEnd/CSS/PaginaPrincipal.css">
    <link rel="stylesheet" href="/FrontEnd/CSS/_template_module.css">
    <link rel="icon" href="/FrontEnd/CSS/imagens/VISTA.png">
</head>
<body>
    <div style="background:#ff0; color:#000; padding:16px; text-align:center; font-size:1.2rem; font-weight:bold;">TESTE: ESTE ARQUIVO FOI EDITADO EM 2026</div>
    <header class="app-header" id="logoHeader">
        <div class="app-header-content">
            <div class="app-header-left">
                <img src="/FrontEnd/CSS/imagens/VISTA.png" alt="VISTA Logo" class="app-logo">
                <div class="app-brand">
                    <span class="app-title">VISTA</span>
                    <span class="app-subtitle">Sistema de Gestão Integrada</span>
                </div>
            </div>
            <div class="app-header-right">
                <span class="app-user-greeting">Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <div class="app-user-avatar" id="userAvatar">
                    <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
                </div>
                <button class="app-btn-logout" id="logoutBtn" title="Sair do sistema">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sair</span>
                </button>
            </div>
        </div>
    </header>
    <main class="main-container">
        <div class="content-wrapper">
            <div class="module-card" style="max-width:900px;margin:auto;">
                <div class="card-content">
                        <div class="content-header">
                            <div class="header-left">
                                <i class="fas fa-archive"></i>
                                <h1>Inventário de Status de Remessas</h1>
                            </div>
                            <button class="btn-table-toggle voltar-btn" onclick="window.location.href='/FrontEnd/html/PaginaPrincipal.php'">⟵ Voltar</button>
                        </div>
                    <div class="table-section">
                        <div class="table-controls">
                            <input type="text" class="search-input" id="search" placeholder="Buscar por Razão Social ou NF">
                        </div>
                        <div class="table-wrapper">
                            <table class="styled-table">
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
                                    <tr data-id="<?= $r['id'] ?>" <?= !empty($r['confirmado']) ? 'class="row-confirmada"' : '' ?> >
                                        <td>
                                            <input type="checkbox" class="chk" <?= !empty($r['confirmado']) ? 'disabled' : '' ?> data-confirmado="<?= $r['confirmado'] ?>">
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
                        </div>
                        <button id="btnExcluir" class="delete-btn" disabled onclick="excluir()">Excluir selecionados</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
    </footer>
<script src="https://kit.fontawesome.com/7e5b2c7e2a.js" crossorigin="anonymous"></script>
<script>
document.getElementById("logoutBtn").onclick = () =>
    location.href = "/FrontEnd/tela_login.php?reload=" + Date.now();
function confirmar(btn) {
    const tr = btn.closest('tr');
    tr.classList.add('row-confirmada');
    btn.disabled = true;
    atualizarExcluir();
}
function atualizarExcluir() {
    const marcados = document.querySelectorAll('.chk:checked');
    document.getElementById('btnExcluir').disabled = marcados.length === 0;
}
document.querySelectorAll('.chk').forEach(c =>
    c.addEventListener('change', e => {
        e.target.closest('tr').classList.toggle('row-selected', e.target.checked);
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
    document.querySelectorAll('.chk:checked').forEach(c => c.closest('tr').remove());
    atualizarExcluir();
}
</script>
<style>
/* Estilo adicional para integração com paginaprincipal.css */
.main-container {
    background: #10172a;
    min-height: 100vh;
    padding-bottom: 40px;
}
.content-wrapper {
    padding: 32px 0;
}
.module-card {
    background: #1e293b;
    border-radius: 12px;
    box-shadow: 0 2px 8px #0003;
    padding: 32px 24px;
}
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.header-left h1 {
    font-size: 1.6rem;
    color: #fff;
    margin-left: 8px;
}
.btn-table-toggle.voltar-btn {
    background: #334155;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 18px;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-table-toggle.voltar-btn:hover {
    background: #475569;
}
.table-section {
    margin-top: 12px;
}
.table-controls {
    margin-bottom: 12px;
    text-align: right;
}
.search-input {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #334155;
    background: #0f172a;
    color: #fff;
    width: 260px;
}
.table-wrapper {
    overflow-x: auto;
}
.styled-table {
    width: 100%;
    border-collapse: collapse;
    background: #1e293b;
    color: #fff;
    font-size: 1rem;
    border-radius: 8px;
    box-shadow: 0 1px 4px #0002;
}
.styled-table th, .styled-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #334155;
    text-align: left;
}
.styled-table th {
    background: #334155;
    color: #fff;
    font-weight: 600;
}
.styled-table tr.row-confirmada td {
    opacity: 0.5;
    pointer-events: none;
    background: #1e293b !important;
}
.styled-table tr.row-selected td {
    background: #334155;
}
.ok-btn {
    background: #22c55e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 16px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}
.ok-btn:disabled {
    background: #334155;
    color: #aaa;
    cursor: not-allowed;
}
.delete-btn {
    background: #ef4444;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 18px;
    font-size: 1rem;
    cursor: pointer;
    margin-top: 18px;
    transition: background 0.2s;
}
.delete-btn:disabled {
    background: #334155;
    color: #aaa;
    cursor: not-allowed;
}
footer {
    background: #0f172a;
    color: #fff;
    text-align: center;
    padding: 18px 0 8px 0;
    font-size: 0.95rem;
    border-top: 1px solid #334155;
    margin-top: 32px;
}
</style>
</body>
</html>
