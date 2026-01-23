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
    <title>Relatório Final — Inventário</title>
    <?php echo metaCSRF(); ?>
    <style>body{font-family:Inter,system-ui,sans-serif;padding:12px;background:#0b1220;color:#e5e7eb}.card{background:rgba(255,255,255,0.04);padding:16px;border-radius:10px;margin-bottom:12px}.btn{padding:8px 12px;border-radius:8px;font-weight:600}.btn-primary{background:#3b82f6;color:#fff}</style>
</head>
<body>
<h1>Relatório Final por Ciclo</h1>
<section class="card">
    <label>Ciclo: <select id="cicloSelect"><option>Carregando...</option></select></label>
    <button id="genBtn" class="btn btn-primary">Gerar Relatório</button>
    <a id="exportLink" class="btn" href="#">Exportar CSV</a>
</section>

<section class="card" id="summary" style="display:none">
    <h2>Resumo Executivo</h2>
    <div style="display:flex;gap:12px">
        <div style="flex:1;background:#071028;padding:12px;border-radius:8px"><strong>Total</strong><div id="sum_total">-</div></div>
        <div style="flex:1;background:#073016;padding:12px;border-radius:8px"><strong>OK</strong><div id="sum_ok">-</div></div>
        <div style="flex:1;background:#2a0716;padding:12px;border-radius:8px"><strong>Divergente</strong><div id="sum_div">-</div></div>
        <div style="flex:1;background:#1a1a1a;padding:12px;border-radius:8px"><strong>Inexistente</strong><div id="sum_inex">-</div></div>
        <div style="flex:1;background:#0b1f3a;padding:12px;border-radius:8px"><strong>% OK</strong><div id="sum_pct">-</div></div>
    </div>
</section>

<section class="card" id="itemsSection" style="display:none">
    <h2>Itens</h2>
    <div id="tableWrap">—</div>
</section>

<section class="card" id="conclusionSection" style="display:none">
    <h2>Conclusão</h2>
    <div id="conclusionText"></div>
</section>

<script>
const apiBase = '/router_public.php?url=inventario/relatorio-final-api';
async function loadCycles(){
    const res = await fetch('/router_public.php?url=inventario/ciclos-api', {credentials:'same-origin'});
    if (!res.ok) return;
    const j = await res.json();
    const sel = document.getElementById('cicloSelect'); sel.innerHTML='';
    (j.data||[]).forEach(r=>{ const opt=document.createElement('option'); opt.value=r.id; opt.textContent=r.mes_ano; sel.appendChild(opt); });
}

function setExportLink(ciclo){
    if (!ciclo) { document.getElementById('exportLink').href = '#'; return; }
    document.getElementById('exportLink').href = apiBase + '&action=export_csv&ciclo_id=' + encodeURIComponent(ciclo);
}

let currentPage = 1;
const perPage = 200; // server default; can be adjusted

async function gerar(page = 1){
    currentPage = Math.max(1, page);
    const ciclo = document.getElementById('cicloSelect').value;
    if (!ciclo) { alert('Selecione um ciclo'); return; }
    const url = apiBase + '&action=gerar_relatorio_ciclo&ciclo_id=' + encodeURIComponent(ciclo) + '&page=' + currentPage + '&per=' + perPage;
    const res = await fetch(url, {credentials:'same-origin'});
    if (res.status === 401) { alert('Sessão expirada'); window.location = '/FrontEnd/tela_login.php'; return; }
    if (!res.ok) { alert('Erro ao gerar relatório'); return; }
    const j = await res.json();
    if (!j.success) { alert(j.error || 'Erro'); return; }

    document.getElementById('summary').style.display = 'block';
    document.getElementById('sum_total').textContent = j.resumo.total;
    document.getElementById('sum_ok').textContent = j.resumo.ok;
    document.getElementById('sum_div').textContent = j.resumo.divergente;
    document.getElementById('sum_inex').textContent = j.resumo.inexistente;
    document.getElementById('sum_pct').textContent = j.resumo.percentual_ok + '%';

    // Table
    const wrap = document.getElementById('tableWrap'); wrap.innerHTML = '';
    const table = document.createElement('table'); table.style.width='100%';
    const thead = document.createElement('thead'); thead.innerHTML = '<tr><th>Armário</th><th>Remessa</th><th>Resultado</th><th>Status Banco</th><th>Observação</th><th>Criado Em</th></tr>';
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    (j.itens || []).forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.armario_id}</td><td>${r.remessa}</td><td>${r.resultado}</td><td>${r.status_banco}</td><td>${r.observacao||''}</td><td>${r.criado_em}</td>`;
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    wrap.appendChild(table);
    document.getElementById('itemsSection').style.display = 'block';

    // Conclusion
    document.getElementById('conclusionText').textContent = j.conclusao || '';
    document.getElementById('conclusionSection').style.display = 'block';

    setExportLink(ciclo);
    renderPagination(j.meta || {});
}

function renderPagination(meta){
    const wrap = document.getElementById('tableWrap');
    // Remove existing pager if any
    const existing = document.getElementById('pager');
    if (existing) existing.remove();
    const pager = document.createElement('div');
    pager.id = 'pager';
    pager.style.marginTop = '8px';
    const page = meta.page || currentPage;
    const totalPages = meta.total_pages || 1;
    const totalItems = meta.total_items || 0;
    const prev = document.createElement('button'); prev.textContent = '« Prev'; prev.className='btn';
    prev.disabled = page <= 1;
    prev.onclick = () => gerar(page - 1);
    const next = document.createElement('button'); next.textContent = 'Next »'; next.className='btn';
    next.disabled = page >= totalPages;
    next.onclick = () => gerar(page + 1);
    const info = document.createElement('span'); info.style.margin='0 12px'; info.textContent = `Página ${page} / ${totalPages} — ${totalItems} itens`;
    pager.appendChild(prev); pager.appendChild(info); pager.appendChild(next);
    wrap.appendChild(pager);
}

document.getElementById('genBtn').onclick = gerar;

loadCycles().then(()=>{ const sel=document.getElementById('cicloSelect'); if (sel.options.length>0) sel.selectedIndex=0; setExportLink(sel.value); });
</script>
</body>
</html>