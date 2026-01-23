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
    <title>Conciliação de Inventário</title>
    <?php echo metaCSRF(); ?>
    <style>body{font-family:Inter,system-ui,sans-serif;padding:12px;background:#0b1220;color:#e5e7eb}.card{background:rgba(255,255,255,0.04);padding:16px;border-radius:10px;margin-bottom:12px}.btn{padding:8px 12px;border-radius:8px;font-weight:600}.btn-primary{background:#3b82f6;color:#fff}</style>
</head>
<body>
<h1>Conciliação / Divergências</h1>
<section class="card">
    <label>Ciclo: <select id="cicloSelect"><option>Carregando...</option></select></label>
    <label>Armário: <input id="armario" placeholder="Código do armário"></label>
    <p>Cole remessas (uma por linha) ou envie lote (máx 500)</p>
    <textarea id="remessas" rows="8" style="width:100%"></textarea>
    <div style="margin-top:8px"><button id="compareBtn" class="btn btn-primary">Comparar</button> <a id="exportLink" href="#" class="btn">Exportar CSV</a></div>
    <div id="result"></div>
</section>

<section class="card">
    <h2>Divergências Recentes</h2>
    <div id="list">Carregando...</div>
</section>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
async function loadCycles(){
    const res = await fetch('/router_public.php?url=inventario/ciclos-api', {credentials:'same-origin'});
    if (!res.ok) return;
    const j = await res.json();
    const sel = document.getElementById('cicloSelect'); sel.innerHTML='';
    (j.data||[]).forEach(r=>{ const opt=document.createElement('option'); opt.value=r.id; opt.textContent=r.mes_ano; sel.appendChild(opt); });
}

async function compare(){
    const ciclo_id = document.getElementById('cicloSelect').value;
    const armario = document.getElementById('armario').value.trim();
    let remessas = document.getElementById('remessas').value.split(/\r?\n|[,;]+/).map(s=>s.trim()).filter(Boolean);
    if (!ciclo_id || !armario || remessas.length===0) { alert('Selecione ciclo, informe armário e remessas'); return; }
    if (remessas.length>500) { alert('Máx 500 remessas'); return; }
    const f = new FormData(); f.append('action','compare_armario'); f.append('ciclo_id', ciclo_id); f.append('armario_id', armario); f.append('csrf_token', csrf);
    remessas.forEach(r=>f.append('remessas[]', r));
    const r = await fetch('/router_public.php?url=inventario/conciliacao-api', {method:'POST', body: f, credentials:'same-origin'});
    const j = await r.json();
    if (!j.success) { alert(j.error||'Erro'); return; }
    const container=document.getElementById('result'); container.innerHTML='';
    const table=document.createElement('table'); table.style.width='100%';
    j.data.forEach(row=>{ const tr=document.createElement('tr'); tr.innerHTML=`<td>${row.remessa}</td><td>${row.resultado}</td><td>${row.status_banco}</td><td>${row.observacao||''}</td>`; table.appendChild(tr); });
    container.appendChild(table);
}

async function loadList(){
    const res = await fetch('/router_public.php?url=inventario/conciliacao-api&action=listar_divergencias&per=20', {credentials:'same-origin'});
    const j = await res.json();
    const container = document.getElementById('list'); container.innerHTML='';
    (j.data||[]).forEach(r=>{ const d=document.createElement('div'); d.textContent = `${r.ciclo_id} | ${r.armario_id} | ${r.remessa} -> ${r.resultado}`; container.appendChild(d); });
}

document.getElementById('compareBtn').onclick = compare;
document.getElementById('exportLink').onclick = function(e){ e.preventDefault(); const ciclo=document.getElementById('cicloSelect').value; const arm=document.getElementById('armario').value; window.location = `/router_public.php?url=inventario/conciliacao-api&action=export_csv&ciclo_id=${encodeURIComponent(ciclo)}&armario_id=${encodeURIComponent(arm)}`; };

loadCycles().then(loadList);
</script>
</body>
</html>
