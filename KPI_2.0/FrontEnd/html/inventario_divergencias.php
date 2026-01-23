<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';
require_once __DIR__ . '/../../BackEnd/Database.php';

verificarSessao();
definirHeadersSeguranca();

$db = getDb();
// fetch cycles and armarios for filters
$cycles = $db->fetchAll('SELECT id, mes_ano FROM inventario_ciclos ORDER BY id DESC');
$armarios = $db->fetchAll('SELECT id, codigo FROM armarios WHERE ativo = 1 ORDER BY codigo ASC');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Divergências de Inventário</title>
    <?php echo metaCSRF(); ?>
    <style>
        body{font-family:Inter,system-ui,sans-serif;background:#0b1220;color:#e5e7eb;padding:12px}
        .card{background:rgba(255,255,255,0.03);padding:12px;border-radius:10px;margin-bottom:12px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid rgba(255,255,255,0.04);text-align:left}
        .group-header{background:rgba(255,255,255,0.02);padding:8px;font-weight:700}
        .btn{padding:8px 10px;border-radius:8px}
        .btn-primary{background:#3b82f6;color:#fff;border:none}
    </style>
</head>
<body>
<h1>Divergências — Inventário</h1>

<section class="card">
    <form id="filterForm" style="display:flex;gap:8px;align-items:center">
        <label>Ciclo
            <select id="fCiclo" name="ciclo_id">
                <option value="">-- todos --</option>
                <?php foreach($cycles as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['mes_ano']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Data início
            <input type="date" id="fDataInicio" name="data_inicio">
        </label>
        <label>Data fim
            <input type="date" id="fDataFim" name="data_fim">
        </label>

        <label>Armário
            <select id="fArmario" name="armario">
                <option value="">-- todos --</option>
                <?php foreach($armarios as $a): ?>
                    <option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['codigo']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Status
            <select id="fStatus" name="status">
                <option value="">-- todos --</option>
                <option value="OK">OK</option>
                <option value="DIVERGENTE">DIVERGENTE</option>
                <option value="INEXISTENTE">INEXISTENTE</option>
            </select>
        </label>

        <button id="btnApply" class="btn btn-primary">Aplicar</button>
        <button id="btnExport" class="btn">Exportar CSV</button>
    </form>
</section>

<section class="card">
    <div id="results">Carregando...</div>
    <div id="pager" style="margin-top:8px;display:flex;gap:8px;align-items:center"></div>
</section>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const btnApply = document.getElementById('btnApply');
const btnExport = document.getElementById('btnExport');

async function loadResults(page=1, per_page=50) {
    const ciclo = document.getElementById('fCiclo').value;
    const arm = document.getElementById('fArmario').value;
    const status = document.getElementById('fStatus').value;
    const qs = new URLSearchParams();
    if(ciclo) qs.set('ciclo_id', ciclo);
    if(arm) qs.set('armario', arm);
    if(status) qs.set('status', status);
    const dataInicio = document.getElementById('fDataInicio').value;
    const dataFim = document.getElementById('fDataFim').value;
    if (dataInicio) qs.set('data_inicio', dataInicio);
    if (dataFim) qs.set('data_fim', dataFim);
    qs.set('page', page);
    qs.set('per_page', per_page);

    const endpoint = '/router_public.php?url=BackEnd/Inventario/listar_comparacoes.php&' + qs.toString();
    const res = await fetch(endpoint, {credentials:'same-origin'});
    if (res.status === 401) { document.getElementById('results').innerText = 'Sessão expirada'; return; }
    const txt = await res.text();
    let j;
    try { j = JSON.parse(txt); } catch (e) { document.getElementById('results').innerText = 'Erro no servidor'; return; }
    if(!j || !j.data) { document.getElementById('results').innerText = 'Nenhum dado'; return; }
    renderTable(j.data);
    renderPager(j.meta || {page:1,pages:1});
}

function renderPager(meta){
    const pager = document.getElementById('pager');
    pager.innerHTML = '';
    const page = meta.page || 1;
    const pages = meta.pages || 1;
    const prev = document.createElement('button'); prev.className='btn'; prev.textContent='Anterior';
    prev.disabled = page <= 1;
    prev.onclick = ()=>{ loadResults(page-1); };
    const next = document.createElement('button'); next.className='btn'; next.textContent='Próxima';
    next.disabled = page >= pages;
    next.onclick = ()=>{ loadResults(page+1); };
    const info = document.createElement('div'); info.style.marginLeft='auto'; info.style.color='#9ca3af'; info.textContent = `Página ${page} / ${pages} — Total: ${meta.total||0}`;
    pager.appendChild(prev); pager.appendChild(next); pager.appendChild(info);
}

function renderTable(rows) {
    if(!rows || rows.length===0) { document.getElementById('results').innerHTML = '<div>Nenhum registro</div>'; return; }
    // group by armario_codigo
    const groups = {};
    rows.forEach(r=>{
        const key = r.armario_codigo || 'Sem armário';
        if(!groups[key]) groups[key]=[];
        groups[key].push(r);
    });

    let html = '';
    for(const g of Object.keys(groups)){
        html += `<div class="group-header">Armário: ${g}</div>`;
        html += '<table><thead><tr><th>Ciclo</th><th>Remessa</th><th>Status Inventário</th><th>Status Banco</th><th>Observação</th><th>Quem</th><th>Quando</th></tr></thead><tbody>';
        groups[g].forEach(r=>{
            html += `<tr><td>${r.ciclo_id}</td><td>${escapeHtml(r.remessa)}</td><td>${r.status_inventario}</td><td>${r.status_banco||''}</td><td>${escapeHtml(r.observacao||'')}</td><td>${escapeHtml(r.criado_por_nome||r.criado_por||'')}</td><td>${r.criado_em}</td></tr>`;
        });
        html += '</tbody></table>';
    }
    document.getElementById('results').innerHTML = html;
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }

document.getElementById('filterForm').addEventListener('submit', (e)=>{ e.preventDefault(); loadResults(); });
btnExport.addEventListener('click', (e)=>{
    e.preventDefault();
    const ciclo = document.getElementById('fCiclo').value;
    const arm = document.getElementById('fArmario').value;
    const status = document.getElementById('fStatus').value;
    const qs = new URLSearchParams();
    if(ciclo) qs.set('ciclo_id', ciclo);
    if(arm) qs.set('armario', arm);
    if(status) qs.set('status', status);
    const dataInicio = document.getElementById('fDataInicio').value;
    const dataFim = document.getElementById('fDataFim').value;
    if (dataInicio) qs.set('data_inicio', dataInicio);
    if (dataFim) qs.set('data_fim', dataFim);
    qs.set('export','csv');
    // open CSV in new tab to download
    window.open('/router_public.php?url=BackEnd/Inventario/listar_comparacoes.php&'+qs.toString(), '_blank');
});

// initial load
loadResults();
</script>

</body>
</html>
