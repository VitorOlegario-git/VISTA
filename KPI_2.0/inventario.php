<?php
require_once __DIR__ . '/BackEnd/helpers.php';

verificarSessao();
definirHeadersSeguranca();

$armario = sanitizeInput($_GET['armario'] ?? '');
if (empty($armario)) {
    // mensagem simples, instruir uso via QR (usar query-mode se rewrite não estiver disponível)
    echo "<p>Armário não informado. Acesse via QR Code: /router_public.php?url=inventario&armario=ARM-01</p>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inventário - <?php echo htmlspecialchars($armario); ?></title>
    <?php echo metaCSRF(); ?>
    <style>
        /* Minimal responsive layout reusing existing classes */
        body{font-family:Inter,system-ui,sans-serif;background:#0b1220;color:#e5e7eb;padding:12px}
        .card{background:rgba(255,255,255,0.04);padding:16px;border-radius:10px;margin-bottom:12px}
        .btn{padding:10px 14px;border-radius:8px;font-weight:600}
        .btn-primary{background:#3b82f6;color:#fff}
    </style>
</head>
<body>
<header>
    <h1>Inventário físico — Armário <?php echo htmlspecialchars($armario); ?></h1>
    <p>Modo mobile — confirme presença ou registre não encontrado.</p>
</header>

<main>
    <section class="card" id="infoCards">
        <div id="counts">Carregando...</div>
    </section>

    <section class="card">
        <div id="itemsList">Carregando itens...</div>
    </section>

    <section class="card">
        <h2>Entrada manual — Remessas encontradas</h2>
        <div>
            <label>Remessa (código ou ID)<input id="inpRemessa" placeholder="ABC123 / 123" style="margin-left:8px"></label>
            <label style="margin-left:8px">Quantidade (opcional)<input id="inpQtd" type="number" style="width:80px;margin-left:8px"></label>
            <label style="margin-left:8px">Observação (opcional)<input id="inpObs" placeholder="Observação" style="margin-left:8px"></label>
            <button id="btnAdd" class="btn btn-primary" style="margin-left:8px">Adicionar</button>
        </div>
        <div style="margin-top:12px">
            <ul id="manualList" style="list-style:none;padding:0;margin:0"></ul>
        </div>
        <div style="margin-top:12px">
            <button id="btnCompare" class="btn btn-primary">Enviar comparação</button>
        </div>
    </section>

    <section style="position:fixed;bottom:12px;left:12px;right:12px;display:flex;gap:8px;">
        <a class="btn btn-primary" id="btnRefresh" href="#">Atualizar</a>
        <a class="btn" id="btnFinalize" href="#" style="background:#ef4444;color:#fff">Encerrar ciclo</a>
    </section>
</main>

<script>
const armario = '<?php echo addslashes($armario); ?>';
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function fetchItems(){
    const res = await fetch('/inventario-api?action=list&armario=' + encodeURIComponent(armario), {credentials:'same-origin'});
    if (res.status === 401) {
        await handleUnauthorized(res);
        return;
    }
    let data;
    try {
        data = await res.json();
    } catch (err) {
        document.getElementById('itemsList').innerText = 'Resposta inválida do servidor';
        return;
    }
    if(!data.success){ document.getElementById('itemsList').innerText = 'Erro'; return; }
    const rows = data.data || [];
    document.getElementById('counts').innerText = `${rows.length} itens aguardando`;
    const container = document.getElementById('itemsList');
    container.innerHTML = '';
    rows.forEach(r => {
        const el = document.createElement('div');
        el.className = 'item card';
        el.innerHTML = `
            <div><strong>${r.codigo_remessa ?? r.remessa_id}</strong></div>
            <div style="margin-top:8px">
                <button class="btn btn-primary" data-id="${r.remessa_id}" data-action="confirm">Confirmar</button>
                <button class="btn" data-id="${r.remessa_id}" data-action="notfound">Não encontrado</button>
            </div>
        `;
        container.appendChild(el);
    });

    // bind
    container.querySelectorAll('button[data-action]').forEach(btn => {
        btn.onclick = async (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const action = btn.getAttribute('data-action');
            if(action === 'confirm'){
                await postAction('confirm', {remessa_id: id});
            } else {
                const obs = prompt('Observação (opcional)');
                await postAction('notfound', {remessa_id: id, observacao: obs || ''});
            }
            await fetchItems();
        }
    });
}

async function postAction(action, payload){
    payload.action = action;
    const form = new FormData();
    for(const k in payload) form.append(k, payload[k]);
    form.append('csrf_token', csrf);
    const res = await fetch('/inventario-api', {method:'POST', body: form, credentials:'same-origin'});
    if (res.status === 401) { await handleUnauthorized(res); return; }
    let data;
    try { data = await res.json(); } catch (err) { alert('Resposta inválida do servidor'); return; }
    if(!data.success) alert(data.error || 'Erro');
}

document.getElementById('btnRefresh').onclick = (e)=>{e.preventDefault(); fetchItems();};
document.getElementById('btnFinalize').onclick = async (e)=>{
    e.preventDefault();
    if(!confirm('Encerrar ciclo atual? Apenas encerrado manualmente.')) return;
    // Para simplificar, pede ciclo_id (poderia detectar ou criar automáticamente)
    const cid = prompt('ID do ciclo a encerrar (ex: 1)');
    if(!cid) return;
    const f = new FormData(); f.append('action','finalize'); f.append('ciclo_id', cid); f.append('csrf_token', csrf);
    const res = await fetch('/inventario-api', {method:'POST', body: f, credentials:'same-origin'});
    if (res.status === 401) { await handleUnauthorized(res); return; }
    let data;
    try { data = await res.json(); } catch (err) { alert('Resposta inválida do servidor'); return; }
    if(!data.success) alert(data.error||'Erro'); else alert(data.message||'OK');
}

async function handleUnauthorized(res){
    // Try to read JSON message if available
    let msg = 'Sessão expirada ou não autenticado.';
    try {
        const j = await res.json();
        if (j && j.error) msg = j.error;
    } catch (e) {
        // ignore
    }

    // Replace main content with a friendly message and login link
    const main = document.querySelector('main');
    if (main) {
        main.innerHTML = `\n            <section class="card">\n                <h2>Autenticação necessária</h2>\n                <p>${msg}</p>\n                <p><a href="/router_public.php?url=login">Ir para a tela de login</a></p>\n            </section>`;
    } else {
        document.body.innerHTML = `<div style="padding:16px">` +
            `<h2>Autenticação necessária</h2><p>${msg}</p><p><a href="/router_public.php?url=login">Ir para a tela de login</a></p></div>`;
    }
}

fetchItems();
</script>

</body>
</html>

<script>
// Manual entry handlers
const manual = [];
const inpRemessa = document.getElementById('inpRemessa');
const inpQtd = document.getElementById('inpQtd');
const inpObs = document.getElementById('inpObs');
const btnAdd = document.getElementById('btnAdd');
const manualList = document.getElementById('manualList');
const btnCompare = document.getElementById('btnCompare');

function renderManual(){
    manualList.innerHTML = '';
    manual.forEach((m, idx)=>{
        const li = document.createElement('li');
        li.style.padding='8px'; li.style.borderBottom='1px solid rgba(255,255,255,0.04)';
        li.innerHTML = `<strong>${m.remessa}</strong> ${m.quantidade?(' — Qtd: '+m.quantidade):''} ${m.observacao?(' — '+m.observacao):''} <button data-idx='${idx}' class='btn' style='margin-left:8px'>Remover</button>`;
        manualList.appendChild(li);
    });
    manualList.querySelectorAll('button[data-idx]').forEach(b=>b.onclick=(e)=>{ const i=parseInt(b.getAttribute('data-idx')); manual.splice(i,1); renderManual(); });
}

btnAdd.onclick = (e)=>{ e.preventDefault(); const r = (inpRemessa.value||'').trim(); if(!r) return alert('Informe remessa'); manual.push({remessa:r, quantidade: inpQtd.value?parseInt(inpQtd.value):null, observacao: (inpObs.value||'').trim()}); inpRemessa.value=''; inpQtd.value=''; inpObs.value=''; renderManual(); };

btnCompare.onclick = async (e)=>{
    e.preventDefault(); if(manual.length===0) return alert('Adicione pelo menos uma remessa');
    const payload = { action: 'compare_armario', armario: armario, remessas: manual };
    const f = new FormData();
    f.append('action','compare_armario');
    f.append('armario', armario);
    f.append('remessas_json', JSON.stringify(manual));
    f.append('csrf_token', csrf);

    const res = await fetch('/inventario-api', { method: 'POST', body: f, credentials: 'same-origin' });
    if (res.status === 401) { await handleUnauthorized(res); return; }
    let data;
    try { data = await res.json(); } catch (err) { alert('Resposta inválida do servidor'); return; }
    if(!data.success){ alert(data.error || 'Erro'); return; }
    // show result basic
    const out = data.data || data.resultado || {};
    let txt = 'Resultado da comparação:\n';
    (out.resultado || out).forEach(r=>{ txt += `${r.remessa} — ${r.status}` + (r.status_banco?(' (banco: '+r.status_banco+')'):'') + '\n'; });
    alert(txt);
    // Optionally refresh items
    fetchItems();
}
</script>
