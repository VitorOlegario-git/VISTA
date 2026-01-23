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
    <title>Admin - Conciliação de Inventário</title>
    <?php echo metaCSRF(); ?>
    <style>body{font-family:Inter,system-ui,sans-serif;padding:12px;background:#0b1220;color:#e5e7eb}.card{background:rgba(255,255,255,0.04);padding:16px;border-radius:10px;margin-bottom:12px}.btn{padding:8px 12px;border-radius:8px;font-weight:600}.btn-primary{background:#3b82f6;color:#fff}.filters label{display:inline-block;margin-right:12px}</style>
</head>
<body>
<h1>Administração — Conciliações</h1>
<section class="card filters">
    <label>Ciclo: <select id="cicloSelect"><option>Carregando...</option></select></label>
    <label>Armário: <input id="armario" placeholder="Código do armário"></label>
    <label>Resultado: 
        <select id="resultado">
            <option value="">Todos</option>
            <option value="OK">OK</option>
            <option value="DIVERGENTE">DIVERGENTE</option>
            <option value="INEXISTENTE">INEXISTENTE</option>
        </select>
    </label>
    <label>Data Início: <input type="date" id="data_inicio"></label>
    <label>Data Fim: <input type="date" id="data_fim"></label>
    <div style="margin-top:8px">
        <button id="searchBtn" class="btn btn-primary">Pesquisar</button>
        <button id="resetBtn" class="btn">Limpar</button>
        <a id="exportLink" class="btn" href="#">Exportar CSV</a>
    </div>
</section>

<section class="card">
    <div style="display:flex;gap:12px">
        <div style="flex:1;background:#071028;padding:12px;border-radius:8px"><strong>OK</strong><div id="cnt_ok">-</div></div>
        <div style="flex:1;background:#2a0716;padding:12px;border-radius:8px"><strong>DIVERGENTE</strong><div id="cnt_div">-</div></div>
        <div style="flex:1;background:#1a1a1a;padding:12px;border-radius:8px"><strong>INEXISTENTE</strong><div id="cnt_inex">-</div></div>
        <div style="flex:1;background:#073016;padding:12px;border-radius:8px"><strong>TOTAL</strong><div id="cnt_total">-</div></div>
    </div>
</section>

<section class="card">
    <h2>Resultados</h2>
    <div id="tableWrap">Carregando...</div>
</section>

<script>
const apiBase = '/router_public.php?url=inventario/conciliacao-api';
const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function loadCycles(){
    try {
        const res = await fetch('/router_public.php?url=inventario/ciclos-api', {credentials:'same-origin'});
        if (!res.ok) throw res;
        const j = await res.json();
        const sel = document.getElementById('cicloSelect'); sel.innerHTML='';
        (j.data||[]).forEach(r=>{ const opt=document.createElement('option'); opt.value=r.id; opt.textContent=r.mes_ano; sel.appendChild(opt); });
    } catch (err) {
        console.error('Erro ao carregar ciclos', err);
        alert('Não foi possível carregar ciclos. Verifique a sessão.');
    }
}

function buildQuery(params){
    const p = new URLSearchParams();
    for (const k in params) if (params[k] !== null && params[k] !== undefined && params[k] !== '') p.append(k, params[k]);
    return p.toString();
}

async function fetchAdminList(){
    const ciclo = document.getElementById('cicloSelect').value;
    if (!ciclo) { alert('Selecione um ciclo'); return; }
    const arm = document.getElementById('armario').value.trim();
    const resultado = document.getElementById('resultado').value;
    const di = document.getElementById('data_inicio').value;
    const df = document.getElementById('data_fim').value;

    const qs = buildQuery({action:'listar_conciliacoes_admin', ciclo_id: ciclo, armario: arm, resultado: resultado, data_inicio: di, data_fim: df});
    const url = apiBase + '&' + qs;
    const res = await fetch(url, {credentials:'same-origin'});

    if (res.status === 401) { alert('Sessão expirada'); window.location = '/FrontEnd/tela_login.php'; return; }
    if (res.status === 403) { alert('Acesso negado'); return; }
    if (!res.ok) { const t = await res.text(); console.error(t); alert('Erro no servidor'); return; }

    const j = await res.json();
    if (!j.success) { alert(j.error || 'Erro'); return; }

    // Update counters
    document.getElementById('cnt_ok').textContent = j.counters.OK;
    document.getElementById('cnt_div').textContent = j.counters.DIVERGENTE;
    document.getElementById('cnt_inex').textContent = j.counters.INEXISTENTE;
    document.getElementById('cnt_total').textContent = j.counters.TOTAL;

    // Build table
    const wrap = document.getElementById('tableWrap'); wrap.innerHTML='';
    const table = document.createElement('table'); table.style.width='100%'; table.border=0;
    const thead = document.createElement('thead'); thead.innerHTML = '<tr><th>Ciclo</th><th>Armário</th><th>Remessa</th><th>Resultado</th><th>Status Banco</th><th>Observação</th><th>Criado Em</th></tr>';
    table.appendChild(thead);
    const tbody = document.createElement('tbody');
    (j.data||[]).forEach(r=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.ciclo_id}</td><td>${r.armario_id}</td><td>${r.remessa}</td><td>${r.resultado}</td><td>${r.status_banco}</td><td>${r.observacao||''}</td><td>${r.criado_em}</td>`;
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    wrap.appendChild(table);
}

function setExportLink(){
    const ciclo = document.getElementById('cicloSelect').value;
    const arm = document.getElementById('armario').value.trim();
    const resultado = document.getElementById('resultado').value;
    const di = document.getElementById('data_inicio').value;
    const df = document.getElementById('data_fim').value;
    if (!ciclo) { document.getElementById('exportLink').href = '#'; return; }
    const qs = buildQuery({action:'export_csv_admin', ciclo_id: ciclo, armario: arm, resultado: resultado, data_inicio: di, data_fim: df});
    document.getElementById('exportLink').href = apiBase + '&' + qs;
}

document.getElementById('searchBtn').onclick = async function(){ await fetchAdminList(); setExportLink(); };
document.getElementById('resetBtn').onclick = function(){ document.getElementById('armario').value=''; document.getElementById('resultado').value=''; document.getElementById('data_inicio').value=''; document.getElementById('data_fim').value=''; };
document.getElementById('exportLink').onclick = function(e){ if (this.getAttribute('href') === '#') { e.preventDefault(); alert('Selecione um ciclo antes de exportar'); } };

loadCycles().then(()=>{ const sel = document.getElementById('cicloSelect'); if (sel.options.length>0) sel.selectedIndex=0; setExportLink(); });
</script>
</body>
</html>
