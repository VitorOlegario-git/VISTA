<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';

// sessão e headers de segurança
verificarSessao();
definirHeadersSeguranca();

?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inventário (Mobile) - VISTA</title>
<link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    :root{--accent:#6366f1;--bg:#071029;--card:#0f172a;--text:#e6eef8;--muted:rgba(230,238,248,0.7);--success:#10b981}
    body{margin:0;font-family:Inter,Segoe UI,Arial,sans-serif;background:linear-gradient(180deg,var(--bg),#071827);color:var(--text);-webkit-font-smoothing:antialiased}
    .wrap{padding:12px 12px 80px}
    .header{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px}
    .title{display:flex;flex-direction:column}
    .title h1{margin:0;font-size:16px}
    .search{width:100%;margin:10px 0}
    .search input{width:100%;padding:10px;border-radius:10px;border:0}

    .filters{display:flex;gap:10px;overflow-x:auto;padding-bottom:8px}
    .filter-btn{min-width:110px;background:linear-gradient(135deg,#0b172a,#102338);border-radius:10px;padding:10px;border:1px solid rgba(255,255,255,0.03);cursor:pointer}
    .filter-btn .label{font-size:12px;color:var(--muted)}
    .filter-btn .count{font-weight:700;margin-top:6px}
    .filter-btn.active{outline:3px solid rgba(99,102,241,0.12);transform:translateY(-2px)}

    .list{margin-top:12px;display:flex;flex-direction:column;gap:10px}
    .card{background:linear-gradient(180deg,#fff,#fff);color:#0b1724;border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(2,6,23,0.06)}
    .card .row{display:flex;justify-content:space-between;align-items:center}
    .card .meta{font-size:13px;color:#475569}
    .confirm{background:var(--success);color:#fff;border:0;padding:8px 12px;border-radius:8px;cursor:pointer}

    .empty{padding:20px;text-align:center;color:#94a3b8}

    @media (min-width:720px){
        .wrap{max-width:720px;margin:0 auto}
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="title">
            <h1>Inventário (Mobile)</h1>
            <div class="meta">Toque em um filtro ou pesquise para encontrar remessas</div>
        </div>
        <button onclick="location.href='/router_public.php?url=dashboard'" style="background:transparent;border:0;color:var(--muted)">Voltar</button>
    </div>

    <div class="search"><input id="q" placeholder="Pesquisar por razão social ou nota fiscal"></div>

    <div class="filters" id="filters"></div>

    <div class="list" id="list"></div>
    <div id="empty" class="empty" style="display:none">Nenhuma remessa encontrada.</div>
</div>

<script>
const STATUS_ORDER = ['aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise','em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'];
const STATUS_LABELS = { 'aguardando_nf':'Aguardando NF','aguardando_nf_retorno':'Aguardando NF (retorno)','aguardando_pg':'Aguardando PG','descarte':'Descarte','em_analise':'Em Análise','em_reparo':'Em Reparo','envio_analise':'Enviado p/ Análise','envio_cliente':'Enviado p/ Cliente','envio_expedicao':'Expedição','estocado':'Estocado','inspecao_qualidade':'Inspeção Qualidade' };
let items = [];
let counts = {};
let active = null;

function renderFilters(){
    const c = document.getElementById('filters'); c.innerHTML='';
    const allBtn = document.createElement('div'); allBtn.className='filter-btn'+(active===null?' active':''); allBtn.innerHTML = `<div class="label">Total</div><div class="count">${Object.values(counts).reduce((s,v)=>s+v,0)||0}</div>`; allBtn.onclick = ()=>{active=null; renderList(); updateFilters();}; c.appendChild(allBtn);
    STATUS_ORDER.forEach(code=>{
        const el = document.createElement('div'); el.className='filter-btn'+(active===code?' active':''); el.dataset.status = code; el.innerHTML = `<div class="label">${STATUS_LABELS[code]||code}</div><div class="count">${counts[code]||0}</div>`; el.onclick = ()=>{ active = code; renderList(); updateFilters(); }; c.appendChild(el);
    });
}

function updateFilters(){ document.querySelectorAll('.filter-btn').forEach(b=>{ b.classList.toggle('active', b.dataset.status === active || (active===null && b.innerText.includes('Total'))); }); }

function renderList(){
    const container = document.getElementById('list'); container.innerHTML='';
    const q = (document.getElementById('q').value||'').trim().toLowerCase();
    let visible = items.filter(i => (active? i.status===active : true) && ((i.razao_social||'').toLowerCase().includes(q) || (i.nota_fiscal||'').toLowerCase().includes(q)));
    if(visible.length===0){ document.getElementById('empty').style.display='block'; return; } else document.getElementById('empty').style.display='none';
    visible.forEach(r=>{
        const d = document.createElement('div'); d.className='card';
        d.innerHTML = `<div class="row"><div><div style="font-weight:700">${escape(r.razao_social)}</div><div class="meta">NF: ${escape(r.nota_fiscal)} • Qt: ${r.quantidade||1}</div></div><div><button class="confirm" onclick="confirmIt(${r.id}, this)">Confirmar</button></div></div>`;
        container.appendChild(d);
    });
}

function escape(s){ return String(s || '').replace(/[&<>\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

async function load(){
    try{
        const res = await fetch('/router_public.php?url=inventario-api&action=list');
        if(!res.ok) throw new Error('API');
        const j = await res.json();
        items = (j.items||[]).map(r=>({ id: r.id||0, razao_social: r.razao_social||r.cliente_nome||'', nota_fiscal: r.nota_fiscal||'', quantidade: r.quantidade||1, status: r.status||'' }));
    }catch(e){
        console.warn('API indisponível, usando mock');
        items = [ {id:1,razao_social:'ACME',nota_fiscal:'NF-1001',quantidade:1,status:'aguardando_pg'} ];
    }
    counts = {}; items.forEach(x=>counts[x.status] = (counts[x.status]||0)+1);
    renderFilters(); renderList();
}

async function confirmIt(id, btn){ btn.disabled=true; const orig = btn.innerHTML; btn.innerHTML='...'; try{ const fd = new FormData(); fd.append('resumo_id', id); fd.append('action','confirm'); const r = await fetch('/router_public.php?url=inventario-api',{method:'POST',body:fd}); const j = await r.json(); if(j && j.success){ btn.innerHTML='OK'; // remove
        const idx = items.findIndex(x=>x.id===id); if(idx!==-1){ counts[items[idx].status] = Math.max(0,(counts[items[idx].status]||1)-1); items.splice(idx,1); renderFilters(); renderList(); }
    } else { btn.innerHTML='Erro'; }
 }catch(e){ btn.innerHTML='Erro'; } setTimeout(()=>{ btn.disabled=false; btn.innerHTML=orig; },1200); }

document.getElementById('q').addEventListener('input', ()=>renderList());
window.addEventListener('load', ()=>load());
</script>
</body>
</html>
