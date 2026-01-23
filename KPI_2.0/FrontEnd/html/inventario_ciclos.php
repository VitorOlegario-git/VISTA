<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';
verificarSessao();
definirHeadersSeguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ciclos de Inventário</title>
    <?php echo metaCSRF(); ?>
    <style>
        body{font-family:Inter,system-ui,sans-serif;background:#0b1220;color:#e5e7eb;padding:12px}
        .card{background:rgba(255,255,255,0.04);padding:16px;border-radius:10px;margin-bottom:12px}
        .btn{padding:8px 12px;border-radius:8px;font-weight:600;border:0;cursor:pointer}
        .btn-primary{background:#3b82f6;color:#fff}
        .btn[disabled]{opacity:0.6;cursor:not-allowed}
        .muted{color:#9ca3af}
        .error-msg{color:#fecaca;background:rgba(254,202,202,0.05);padding:8px;border-radius:8px}
        .success-msg{color:#bbf7d0;background:rgba(187,247,208,0.04);padding:8px;border-radius:8px}
        .cycle-list{display:flex;flex-direction:column;gap:8px}
        .cycle-card{display:flex;justify-content:space-between;align-items:center;padding:12px;border-radius:8px;background:rgba(255,255,255,0.02)}
        .cycle-meta{display:flex;gap:12px;align-items:center}
        .badge{padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700}
        .badge-open{background:#064e3b;color:#bbf7d0}
        .badge-closed{background:#4b5563;color:#e5e7eb}
        .small{font-size:13px;color:#cbd5e1}
        .confirm-area{display:inline-flex;gap:8px}
    </style>
</head>
<body>
<h1>Ciclos de Inventário</h1>

<section class="card">
    <h2>Criar novo ciclo</h2>
    <p>O sistema gera automaticamente o mês atual se vazio (formato YYYY-MM).</p>
    <form id="createForm">
        <input name="mes_ano" placeholder="YYYY-MM">
        <button id="createBtn" class="btn btn-primary" type="submit">Criar</button>
    </form>
    <div id="createResult" role="status" aria-live="polite"></div>
</section>

<section class="card">
    <h2>Ciclos existentes</h2>
    <div id="cyclesList" aria-live="polite"></div>
</section>

<script>
/*
    UI State Summary (comments explain each state below):
    - Loading: renderLoading() shows loading placeholder while fetching cycles.
    - Empty: renderEmpty() displays when API returns an empty list.
    - Error: renderError(msg) displays inline error box with retry button (used for network/server errors and backend messages).
    - Success: renderList(rows) renders cycles as cards; create/close operations show inline success messages.
    - Auth: 401 responses show a polite session-expired message and link to login.

    Accessibility: Inline containers use `aria-live="polite"` so screen readers receive updates.
    No use of alert()/console.* in production flows — user-visible messages only.
*/

const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Helper: safe JSON parse with fallback
function safeParse(text){ try{ return JSON.parse(text); }catch(e){ return null; } }

const cyclesContainer = document.getElementById('cyclesList');

function renderLoading(){
    cyclesContainer.innerHTML = '<div class="muted">Carregando ciclos de inventário…</div>';
}

function renderEmpty(){
    cyclesContainer.innerHTML = '<div class="muted">Nenhum ciclo criado ainda.</div>';
}

function renderError(msg){
    cyclesContainer.innerHTML = '';
    const box = document.createElement('div'); box.className='error-msg';
    const p = document.createElement('div'); p.textContent = 'Erro ao carregar ciclos: ' + (msg || 'Erro desconhecido');
    p.setAttribute('role','status');
    const btn = document.createElement('button'); btn.className='btn'; btn.textContent='Tentar novamente';
    btn.onclick = () => { loadCycles(); };
    box.appendChild(p); box.appendChild(btn);
    cyclesContainer.appendChild(box);
}

function formatStatusBadge(row){
    const closed = row.encerrado_at && row.encerrado_at !== null && row.encerrado_at !== '';
    const span = document.createElement('span'); span.className = 'badge ' + (closed? 'badge-closed' : 'badge-open');
    span.textContent = closed ? 'encerrado' : 'aberto';
    return span;
}

function renderList(rows){
    if (!rows || rows.length === 0) return renderEmpty();
    const list = document.createElement('div'); list.className='cycle-list';
    rows.forEach(row => {
        const card = document.createElement('div'); card.className='cycle-card';
        const meta = document.createElement('div'); meta.className='cycle-meta';
        const title = document.createElement('div'); title.innerHTML = '<strong>' + (row.mes_ano || '') + '</strong>';
        const small = document.createElement('div'); small.className='small'; small.textContent = (row.aberto_at ? 'aberto: ' + row.aberto_at : '') + (row.encerrado_at ? ' encerrado: ' + row.encerrado_at : '');
        meta.appendChild(title); meta.appendChild(formatStatusBadge(row)); meta.appendChild(small);

        const actions = document.createElement('div');
        // preserve existing "Encerrar" action but with inline confirmation UI and without alerts
        const closeBtn = document.createElement('button'); closeBtn.className='btn'; closeBtn.textContent='Encerrar';
        closeBtn.onclick = () => {
            // show inline confirm area
            const confirmArea = document.createElement('span'); confirmArea.className='confirm-area';
            const yes = document.createElement('button'); yes.className='btn btn-primary'; yes.textContent='Sim';
            const no = document.createElement('button'); no.className='btn'; no.textContent='Cancelar';
            confirmArea.appendChild(yes); confirmArea.appendChild(no);
            actions.innerHTML=''; actions.appendChild(confirmArea);
            no.onclick = () => { actions.innerHTML=''; actions.appendChild(closeBtn); };
            yes.onclick = async () => {
                yes.disabled = true; yes.textContent = 'Enviando...';
                const f = new FormData(); f.append('action','close'); f.append('ciclo_id', row.id); f.append('csrf_token', csrf);
                let r;
                try { r = await fetch('/router_public.php?url=inventario/ciclos-api', {method:'POST', body: f, credentials:'same-origin'}); }
                catch (err) {
                    actions.innerHTML=''; const ebox = document.createElement('div'); ebox.className='error-msg'; ebox.textContent = 'Falha de rede ao encerrar ciclo'; actions.appendChild(ebox); setTimeout(()=>{ actions.innerHTML=''; actions.appendChild(closeBtn); }, 3000); return;
                }

                // Handle auth/permission/server status codes explicitly
                if (r.status === 401) {
                    actions.innerHTML=''; const msg = document.createElement('div'); msg.className='muted'; msg.innerHTML = 'Sessão expirada. <a href="/router_public.php?url=FrontEnd/tela_login.php">Entrar</a>';
                    actions.appendChild(msg); return;
                }
                if (r.status === 403) {
                    actions.innerHTML=''; const ebox = document.createElement('div'); ebox.className='error-msg'; ebox.textContent = 'Acesso negado ao encerrar ciclo.'; actions.appendChild(ebox); return;
                }
                const raw = await r.text(); const j = safeParse(raw);
                actions.innerHTML='';
                if (r.status >= 500) {
                    const errMsg = (j && j.error) ? j.error : ('Erro interno do servidor (código ' + r.status + ')');
                    const ebox = document.createElement('div'); ebox.className='error-msg'; ebox.textContent = errMsg;
                    actions.appendChild(ebox); setTimeout(()=>{ actions.innerHTML=''; actions.appendChild(closeBtn); }, 3000); return;
                }

                if(!j || !j.success){
                    const err = (j && j.error) ? j.error : 'Erro ao encerrar ciclo';
                    const ebox = document.createElement('div'); ebox.className='error-msg'; ebox.textContent = err;
                    actions.appendChild(ebox);
                    // re-show button after short delay
                    setTimeout(()=>{ actions.innerHTML=''; actions.appendChild(closeBtn); }, 3000);
                } else {
                    const sbox = document.createElement('div'); sbox.className='success-msg'; sbox.textContent = j.message || 'Ciclo encerrado';
                    actions.appendChild(sbox);
                    // refresh list after short delay to show change
                    setTimeout(()=>{ loadCycles(); }, 800);
                }
            };
        };
        actions.appendChild(closeBtn);

        card.appendChild(meta); card.appendChild(actions);
        list.appendChild(card);
    });
    cyclesContainer.innerHTML = ''; cyclesContainer.appendChild(list);
}

async function loadCycles(){
    renderLoading();
    const endpoint = '/router_public.php?url=inventario/ciclos-api';
    let res;
    try {
        res = await fetch(endpoint, {credentials:'same-origin'});
    } catch (e) {
        return renderError(e.message || 'Falha de rede');
    }

    if (res.status === 401) {
        cyclesContainer.innerHTML = '<div class="muted">Sessão expirada. <a href="/router_public.php?url=FrontEnd/tela_login.php">Entrar</a></div>';
        return;
    }

    // Handle common HTTP status codes explicitly
    if (res.status === 403) {
        return renderError('Acesso negado (403). Você não tem permissão para ver os ciclos.');
    }
    if (res.status >= 500) {
        // Try to parse JSON error if present, otherwise show generic server error
        const raw = await res.text();
        const parsed = safeParse(raw);
        const errMsg = (parsed && parsed.error) ? parsed.error : 'Erro interno do servidor';
        // If backend returned a friendly message like "Banco indisponível", show it inline
        return renderError(errMsg + ' (código ' + res.status + ')');
    }

    // For other responses (200/4xx), attempt to parse JSON and show backend message if available
    const raw = await res.text();
    const data = safeParse(raw);
    if (!data) return renderError('Resposta inválida do servidor');
    if (!data.success) {
        return renderError(data.error || ('Erro ao listar ciclos (código ' + res.status + ')'));
    }
    renderList(data.data || []);
}

// Create form UX: disable button while creating, show inline messages, refresh list on success
document.getElementById('createForm').onsubmit = async (e)=>{
    e.preventDefault();
    const btn = document.getElementById('createBtn');
    const resultEl = document.getElementById('createResult');
    let mes = e.target.mes_ano.value.trim();
    if (!mes) {
        const now = new Date();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        mes = now.getFullYear() + '-' + mm;
    }
    const f = new FormData(); f.append('action','create'); f.append('mes_ano', mes); f.append('csrf_token', csrf);
    btn.disabled = true; const origText = btn.textContent; btn.textContent = 'Criando...';
    resultEl.innerHTML = '';
    let r;
    try { r = await fetch('/router_public.php?url=inventario/ciclos-api', {method:'POST', body: f, credentials:'same-origin'}); }
    catch(err){ resultEl.innerHTML = '<div class="error-msg">Erro de rede: '+(err.message||'')+'</div>'; btn.disabled=false; btn.textContent=origText; return; }

    if (r.status === 401) { resultEl.innerHTML = '<div class="muted">Sessão expirada. <a href="/router_public.php?url=FrontEnd/tela_login.php">Entrar</a></div>'; btn.disabled=false; btn.textContent=origText; return; }
    if (r.status === 403) { resultEl.innerHTML = '<div class="error-msg">Acesso negado. Você não tem permissão.</div>'; btn.disabled=false; btn.textContent=origText; return; }
    const raw = await r.text(); const j = safeParse(raw);
    if (r.status >= 500) { const errMsg = (j && j.error) ? j.error : ('Erro interno do servidor (código ' + r.status + ')'); resultEl.innerHTML = '<div class="error-msg">'+errMsg+'</div>'; btn.disabled=false; btn.textContent=origText; return; }
    if (!j || !j.success){ resultEl.innerHTML = '<div class="error-msg">'+((j && j.error) ? j.error : 'Erro ao criar ciclo')+'</div>'; btn.disabled=false; btn.textContent=origText; return; }
    resultEl.innerHTML = '<div class="success-msg">'+(j.message || 'Ciclo criado com sucesso')+'</div>';
    btn.disabled=false; btn.textContent=origText;
    // Refresh list to show new item
    loadCycles();
}

// initial load
loadCycles();
</script>

</body>
</html>
