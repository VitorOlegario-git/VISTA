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
<link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/recebimento.css'); ?>">
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
    .filter-btn{min-width:100px;background:transparent;border-radius:10px;padding:10px;border:1px solid rgba(255,255,255,0.04);cursor:pointer;display:flex;flex-direction:column;align-items:flex-start}
    .filter-btn .label{font-size:12px;color:var(--muted)}
    .filter-btn .count{font-weight:700;margin-top:6px}
    .filter-btn.active{box-shadow:0 6px 16px rgba(99,102,241,0.12);border-color:rgba(99,102,241,0.24);transform:none}

    .list{margin-top:12px;display:flex;flex-direction:column;gap:12px}
    .card{background:linear-gradient(180deg,var(--card),#0b2030);color:var(--text);border-radius:12px;padding:12px;box-shadow:0 6px 18px rgba(2,6,23,0.12);display:flex;flex-direction:column;gap:8px}
    .card .row{display:flex;justify-content:space-between;align-items:center;gap:10px}
    .card .meta{font-size:13px;color:var(--muted);margin-top:6px}
    .locker-pill{display:inline-block;background:rgba(99,102,241,0.12);color:var(--accent);padding:6px 8px;border-radius:999px;font-weight:700;font-size:12px;margin-top:6px}
    .locker-s{padding:8px;border-radius:8px;border:0;background:#fff;color:#0b1724}
    .confirm{background:var(--success);color:#fff;border:0;padding:10px 14px;border-radius:10px;cursor:pointer;font-weight:600}

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
        <div style="display:flex;gap:8px">
            <button id="mobileAdd" style="background:transparent;border:0;color:var(--muted)">Adicionar</button>
            <button onclick="location.href='/router_public.php?url=dashboard'" style="background:transparent;border:0;color:var(--muted)">Voltar</button>
        </div>
    </div>

    <div class="search"><input id="q" placeholder="Pesquisar por razão social ou nota fiscal"></div>

    <div class="filters" id="filters"></div>

    <div class="list" id="list"></div>
    <div id="empty" class="empty" style="display:none">Nenhuma remessa encontrada.</div>
    <!-- Panel overlay + side-panel (mobile) -->
    <div id="panel-overlay" class="panel-overlay"></div>
    <div id="side-panel" class="side-panel" aria-hidden="true">
        <div class="panel-header">
            <div class="panel-title-group">
                <i class="fas fa-plus-circle" id="panel-icon"></i>
                <h2 id="panel-title">Cadastrar Remessa</h2>
            </div>
            <button type="button" class="btn-close-panel" id="btn-close-panel"><i class="fas fa-times"></i></button>
        </div>
        <div class="panel-body">
            <form id="form-mobile" onsubmit="return false;">
                <div class="form-section">
                    <input id="m_cnpj" placeholder="CNPJ" maxlength="18" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_razao" placeholder="Razão Social" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_nf" placeholder="Nota Fiscal" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <div style="display:flex;gap:8px;margin-bottom:8px">
                        <input id="m_qtd" type="number" value="1" min="1" style="flex:1;padding:10px;border-radius:8px;border:0">
                        <select id="m_status" style="flex:1;padding:10px;border-radius:8px;border:0">
                            <option value="aguardando_pg">Aguardando PG</option>
                            <option value="envio_cliente">Enviado p/ Cliente</option>
                            <option value="estocado">Estocado</option>
                        </select>
                    </div>
                    <input id="m_codigo_rastreio_entrada" placeholder="Código rastreio entrada" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_codigo_rastreio_envio" placeholder="Código rastreio envio" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_nota_fiscal_retorno" placeholder="Nota fiscal retorno" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_numero_orcamento" placeholder="Número orçamento" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_valor_orcamento" placeholder="Valor orçamento" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <input id="m_setor" placeholder="Setor" style="width:100%;padding:10px;border-radius:8px;border:0;margin-bottom:8px">
                    <div style="display:flex;gap:8px"><select id="m_locker" style="padding:8px;border-radius:8px;border:0">
                        <option value="">Armário (nenhum)</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <button id="m_submit" class="confirm" style="margin-left:auto">Adicionar</button>
                    <button id="m_cancel" class="btn-back" style="margin-left:6px;background:transparent;border:0;color:var(--muted)">Cancelar</button></div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/FrontEnd/JS/CnpjMask.js"></script>
<script>
const STATUS_ORDER = ['aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise','em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'];
const STATUS_LABELS = { 'aguardando_nf':'Aguardando NF','aguardando_nf_retorno':'Aguardando NF (retorno)','aguardando_pg':'Aguardando PG','descarte':'Descarte','em_analise':'Em Análise','em_reparo':'Em Reparo','envio_analise':'Enviado p/ Análise','envio_cliente':'Enviado p/ Cliente','envio_expedicao':'Expedição','estocado':'Estocado','inspecao_qualidade':'Inspeção Qualidade' };
let items = [];
let counts = {};
let active = null;
let activeLocker = null;

function renderFilters(){
    const c = document.getElementById('filters'); c.innerHTML='';
    const allBtn = document.createElement('div'); allBtn.className='filter-btn'+(active===null?' active':''); allBtn.innerHTML = `<div class="label">Total</div><div class="count">${Object.values(counts).reduce((s,v)=>s+v,0)||0}</div>`; allBtn.onclick = ()=>{active=null; renderList(); updateFilters();}; c.appendChild(allBtn);
    STATUS_ORDER.forEach(code=>{
        const el = document.createElement('div'); el.className='filter-btn'+(active===code?' active':''); el.dataset.status = code; el.innerHTML = `<div class="label">${STATUS_LABELS[code]||code}</div><div class="count">${counts[code]||0}</div>`; el.onclick = ()=>{ active = code; renderList(); updateFilters(); }; c.appendChild(el);
    });
    updateMobileLockerVisibility();
}

function updateFilters(){ document.querySelectorAll('.filter-btn').forEach(b=>{ b.classList.toggle('active', b.dataset.status === active || (active===null && b.innerText.includes('Total'))); }); }

function renderList(){
    const container = document.getElementById('list'); container.innerHTML='';
    const q = (document.getElementById('q').value||'').trim().toLowerCase();
    let visible = items.filter(i => (active? i.status===active : true) && ((i.razao_social||'').toLowerCase().includes(q) || (i.nota_fiscal||'').toLowerCase().includes(q)));
    if(visible.length===0){ document.getElementById('empty').style.display='block'; return; } else document.getElementById('empty').style.display='none';
    visible.forEach(r=>{
        const d = document.createElement('div'); d.className='card';
        d.innerHTML = `<div class="row"><div style="flex:1"><div style="font-weight:700">${escape(r.razao_social)}</div><div class="meta">NF: ${escape(r.nota_fiscal)} • Qt: ${r.quantidade||1}</div>${r.locker?'<div class="locker-pill">Armário ' + escape(r.locker) + '</div>':''}</div><div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px"><select class="locker-s" data-id="${r.id}" style="padding:8px;border-radius:8px;border:0;margin-bottom:6px"><option value="">—</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select><button class="confirm small" onclick="confirmIt(${r.id}, this)">Confirmar</button></div></div>`;
        container.appendChild(d);
    });
    // set locker selects and handlers
    document.querySelectorAll('.locker-s').forEach(s=>{ const id = s.dataset.id; const it = items.find(x=>String(x.id)===String(id)); if(it) s.value = it.locker || ''; s.style.display = (active==='aguardando_pg' ? 'block' : 'none'); s.addEventListener('change', (e)=>{ assignLocker(id, e.target.value, s); }); });
}

function updateMobileLockerVisibility(){
    const addForm = document.getElementById('mobileAddForm');
    const show = active === 'aguardando_pg';
    if(addForm) addForm.style.display = (show ? (addForm.style.display==='block'?'block':'none') : 'none');
}

function escape(s){ return String(s || '').replace(/[&<>\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

async function load(){
    try{
        const res = await fetch('/router_public.php?url=inventario-api&action=list', { method: 'GET', credentials: 'include', cache: 'no-cache', headers: { 'Accept': 'application/json' } });
        if(!res.ok) throw new Error('API');
        const j = await res.json();
        items = (j.items||[]).map(r=>({ id: r.id||0, razao_social: r.razao_social||r.cliente_nome||'', nota_fiscal: r.nota_fiscal||'', quantidade: r.quantidade||1, status: r.status||'' }));
    }catch(e){
        console.error('Inventario API não respondeu (mobile):', e.message);
        items = [];
    }
    counts = {}; items.forEach(x=>counts[x.status] = (counts[x.status]||0)+1);
    renderFilters(); renderList();
}

async function confirmIt(id, btn){ btn.disabled=true; const orig = btn.innerHTML; btn.innerHTML='...'; try{ const fd = new FormData(); fd.append('resumo_id', id); fd.append('action','confirm'); const r = await fetch('/router_public.php?url=inventario-api',{method:'POST',body:fd, credentials: 'include'}); const j = await r.json(); if(j && j.success){ btn.innerHTML='OK'; // remove
        const idx = items.findIndex(x=>x.id===id); if(idx!==-1){ counts[items[idx].status] = Math.max(0,(counts[items[idx].status]||1)-1); items.splice(idx,1); renderFilters(); renderList(); }
    } else { btn.innerHTML='Erro'; }
 }catch(e){ btn.innerHTML='Erro'; } setTimeout(()=>{ btn.disabled=false; btn.innerHTML=orig; },1200); }

async function assignLocker(resumoId, locker, selectEl){
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('locker', locker);
        const res = await fetch('/router_public.php?url=inventario-api&action=assign_locker', {method:'POST', body:fd, credentials:'include'});
        const j = await res.json();
        if(j && j.success){ const it = items.find(x=>String(x.id)===String(resumoId)); if(it) it.locker = locker || null; renderFilters(); renderList(); }
    }catch(e){ console.error('Erro ao atribuir armário', e); }
}

// mobile add handlers
// side-panel handlers (mobile)
function openPanelNew(){
    document.getElementById('side-panel').classList.add('open');
    document.getElementById('panel-overlay').classList.add('active');
    document.getElementById('side-panel').setAttribute('aria-hidden','false');
}
function closePanel(){
    document.getElementById('side-panel').classList.remove('open');
    document.getElementById('panel-overlay').classList.remove('active');
    document.getElementById('side-panel').setAttribute('aria-hidden','true');
}

document.getElementById('mobileAdd').addEventListener('click', ()=>{ openPanelNew(); });
document.getElementById('btn-close-panel').addEventListener('click', ()=>{ closePanel(); });
document.getElementById('panel-overlay').addEventListener('click', ()=>{ closePanel(); });
document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closePanel(); });

document.getElementById('m_cancel').addEventListener('click', ()=>{ closePanel(); });
document.getElementById('m_submit').addEventListener('click', async ()=>{
    const razao = document.getElementById('m_razao').value.trim(); const nf = document.getElementById('m_nf').value.trim(); const cnpj = document.getElementById('m_cnpj').value.trim(); const qtd = document.getElementById('m_qtd').value || 1; const status = document.getElementById('m_status').value; const codigo_rastreio_entrada = document.getElementById('m_codigo_rastreio_entrada').value.trim(); const codigo_rastreio_envio = document.getElementById('m_codigo_rastreio_envio').value.trim(); const nota_fiscal_retorno = document.getElementById('m_nota_fiscal_retorno').value.trim(); const numero_orcamento = document.getElementById('m_numero_orcamento').value.trim(); const valor_orcamento = document.getElementById('m_valor_orcamento').value.trim(); const setor = document.getElementById('m_setor').value.trim(); const locker = document.getElementById('m_locker').value;
    if(!razao || !nf){ alert('Razão social e nota fiscal são obrigatórios'); return; }
    try{
        const fd = new FormData();
        fd.append('razao_social', razao);
        fd.append('nota_fiscal', nf);
        fd.append('cnpj', cnpj);
        fd.append('quantidade', qtd);
        fd.append('status', status);
        fd.append('codigo_rastreio_entrada', codigo_rastreio_entrada);
        fd.append('codigo_rastreio_envio', codigo_rastreio_envio);
        fd.append('nota_fiscal_retorno', nota_fiscal_retorno);
        fd.append('numero_orcamento', numero_orcamento);
        fd.append('valor_orcamento', valor_orcamento);
        fd.append('setor', setor);
        fd.append('armario_id', locker);
        const res = await fetch('/router_public.php?url=inventario-api&action=create_manual', {method:'POST', body:fd, credentials:'include'});
        const j = await res.json(); if(j && j.success && j.item){ items.unshift(j.item); counts[j.item.status] = (counts[j.item.status]||0)+1; renderFilters(); renderList(); document.getElementById('mobileAddForm').style.display='none'; }
    }catch(e){ console.error(e); alert('Erro ao criar remessa'); }
});
// Inicializa máscara e busca automática de razão social por CNPJ (mobile)
const mCnpj = document.getElementById('m_cnpj');
if(mCnpj){
    mCnpj.addEventListener('input', function(){ applyCNPJMask(this); });
    mCnpj.addEventListener('blur', async ()=>{
        const cnpjVal = mCnpj.value.trim();
        if(cnpjVal.length !== 18) return;
        try{
            const res = await fetch('/BackEnd/buscar_cliente.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'cnpj=' + encodeURIComponent(cnpjVal)
            });
            const json = await res.json();
            if(json && json.encontrado) document.getElementById('m_razao').value = json.razao_social || '';
        }catch(e){ console.error('Erro ao buscar cliente (mobile):', e); }
    });
}

// ensure locker visibility initial
updateMobileLockerVisibility();

document.getElementById('q').addEventListener('input', ()=>renderList());
window.addEventListener('load', ()=>load());
</script>
</body>
</html>
