<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';

// sessão e headers de segurança
verificarSessao();
definirHeadersSeguranca();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Inventário - VISTA</title>
    <link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/consulta.css'); 
?>">
    <link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/recebimento.css'
); ?>">                                                                             <link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?
>">                                                                                 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awe
some/6.5.0/css/all.min.css">                                                        <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>   
    <style>
        /* Inventário — ajustes responsivos e visual moderno */
        :root{--bg:#071029;--card:#071827;--muted:#9aa4b2;--accent:#6366f1;--suc
cess:#10b981}                                                                           body{background:linear-gradient(180deg,#071029 0%,#071827 60%);color:#e6
eef8;font-family:Inter,Segoe UI,Arial,sans-serif}                                       .inv-container{padding:20px;max-width:1200px;margin:0 auto}
        .inv-header{display:flex;align-items:center;justify-content:space-betwee
n;gap:12px;margin-bottom:14px}                                                          .inv-title{display:flex;align-items:center;gap:12px}
        .inv-logo{height:40px;border-radius:6px}
        .inv-title h1{font-size:20px;margin:0;color:#f8fafc}
        .inv-actions{display:flex;align-items:center;gap:8px}
        #searchInput{padding:8px 12px;border-radius:8px;border:0;min-width:220px
}                                                                                       .btn-back{background:transparent;border:1px solid rgba(255,255,255,0.08)
;color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer}                  
        .status-filters{display:flex;flex-wrap:nowrap;gap:10px;overflow-x:auto;p
adding-bottom:6px}                                                                      .status-btn{background:rgba(255,255,255,0.04);color:#fff;padding:10px 14
px;border-radius:12px;border:1px solid rgba(255,255,255,0.03);cursor:pointer;display:flex;flex-direction:column;align-items:flex-start;min-width:140px}                 .status-btn .label{font-size:12px;opacity:0.9}
        .status-btn .count{font-weight:700;font-size:18px}
        .status-btn.active{box-shadow:0 6px 18px rgba(99,102,241,0.14);border-co
lor:var(--accent);transform:translateY(-3px)}                                   
        .table-wrapper{position:relative;margin-top:16px;background:linear-gradi
ent(180deg,#ffffff 0%, #ffffff 0%);border-radius:12px;overflow:hidden;color:#0b1724}                                                                                    table.inv-table{width:100%;border-collapse:collapse}
        table.inv-table thead{background:transparent}
        table.inv-table th, table.inv-table td{padding:12px 16px;text-align:left
,border-bottom:1px solid #eef2f6}                                                       table.inv-table tbody tr:hover{background:#f8fafc}
        .confirm-btn{background:var(--success);color:#fff;border:none;padding:6p
x 12px;border-radius:8px;cursor:pointer}                                                .confirm-btn[disabled]{opacity:0.7;cursor:not-allowed}
        .small-muted{font-size:13px;color:rgba(255,255,255,0.65)}
        .locker-badge{display:inline-block;background:rgba(99,102,241,0.12);colo
r:var(--accent);padding:6px 8px;border-radius:999px;font-weight:700;font-size:13px}                                                                                     .locker-select{padding:6px;border-radius:6px;border:1px solid rgba(11,23
,36,0.06);background:#fff;color:#0b1724}                                                /* Drawer (side panel) for add form */
        .drawer-overlay{position:fixed;inset:0;background:rgba(2,6,23,0.6);displ
ay:none;z-index:1200}                                                                   .drawer-overlay.open{display:block}
        .drawer{position:fixed;right:0;top:0;height:100%;width:420px;background:
linear-gradient(180deg,#071827,#081627);box-shadow:-8px 0 30px rgba(2,6,23,0.6);z-index:1300;transform:translateX(100%);transition:transform .28s ease}                 .drawer.open{transform:translateX(0)}
        .drawer .content{padding:20px;color:#e6eef8;overflow:auto;height:100%}  
        .drawer .close-btn{background:transparent;border:0;color:#9aa4b2;font-si
ze:18px;cursor:pointer}                                                                 @media(max-width:720px){ .drawer{width:100%} }

        .loading-overlay{position:absolute;inset:0;display:flex;align-items:cent
er;justify-content:center;background:rgba(255,255,255,0.6);backdrop-filter:blur(2px)}                                                                                   .spinner{width:36px;height:36px;border-radius:50%;border:4px solid rgba(
0,0,0,0.06);border-top-color:var(--accent);animation:spin 1s linear infinite}           @keyframes spin{to{transform:rotate(360deg)}}

        .empty-state{padding:20px;text-align:center;color:#475569}

        @media(max-width:720px){
            #searchInput{min-width:120px}
            .status-filters{gap:8px}
            .status-btn{min-width:46%}
            table.inv-table thead{display:none}
            table.inv-table, table.inv-table tbody, table.inv-table tr, table.in
v-table td{display:block;width:100%}                                                        table.inv-table tr{margin-bottom:12px}
            table.inv-table td{padding:10px 12px;background:transparent;border-b
ottom:0}                                                                                    table.inv-table td:before{content:attr(data-label);font-weight:600;d
isplay:block;margin-bottom:6px}                                                         }
    </style>
    <style>
        /* Small-screen override: ensure side-panel covers viewport and slides f
rom edge */                                                                             @media (max-width: 720px){
            .side-panel{min-width:0!important;width:100%!important;right:0;left:
0;border-radius:0;}                                                                         .side-panel.open{transform:translateX(0)!important}
            .panel-overlay{z-index:1200}
            .side-panel{z-index:1201}
            .main-content{margin-right:0!important}
        }
    </style>
</head>
<body>
<div class="inv-container">
    <div class="inv-header">
        <div class="inv-title">
            <img src="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>" al
t="logo" class="inv-logo">                                                                  <div>
                <h1>Inventário — Status de Remessas</h1>
                <div class="small-muted">Visualize e confirme remessas por statu
s</div>                                                                                     </div>
        </div>
        <div class="inv-actions">
            <input id="searchInput" type="search" placeholder="Pesquisar por raz
ão social ou nota fiscal..." aria-label="Pesquisar" />                                      <select id="lockerFilter" style="padding:8px 10px;border-radius:8px;
border:0;background:rgba(255,255,255,0.03);color:#fff;margin-left:8px">                         <option value="">Armário (Todos)</option>
                <option value="1">Armário 1</option>
                <option value="2">Armário 2</option>
                <option value="3">Armário 3</option>
                <option value="4">Armário 4</option>
                <option value="5">Armário 5</option>
            </select>
            <button id="btn-new-record" class="btn-new-record" style="margin-lef
t:8px">Adicionar</button>                                                                   <button onclick="location.href='/router_public.php?url=dashboard'" c
lass="btn-back">Voltar</button>                                                         </div>
    </div>

    <div class="status-filters" id="statusFilters" role="tablist" aria-label="Fi
ltros por status">                                                                      <!-- Botões serão renderizados dinamicamente -->
    </div>

    <div class="table-wrapper">
        <div id="loadingOverlay" class="loading-overlay" aria-hidden="true"><div
 class="spinner"></div></div>                                                           <table class="inv-table" id="invTable">
            <thead>
                <tr>
                    <th>Razão Social</th>
                    <th>Nota Fiscal</th>
                    <th>Quantidade</th>
                            <th>Armário</th>
                    <th>Status</th>
                    <th>Confirmar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div id="emptyState" class="empty-state" style="display:none">Nenhuma re
messa encontrada para o filtro atual.</div>                                         </div>

    <!-- Panel overlay + side-panel using shared pattern from Recebimento -->   
    <div id="panel-overlay" class="panel-overlay" onclick="closePanel()"></div> 
    <div id="side-panel" class="side-panel" aria-hidden="true">
        <div class="panel-header">
            <div class="panel-title-group">
                <i class="fas fa-plus-circle" id="panel-icon"></i>
                <h2 id="panel-title">Cadastrar Remessa</h2>
            </div>
            <button type="button" class="btn-close-panel" id="btn-close-panel"><
i class="fas fa-times"></i></button>                                                    </div>
        <div class="panel-body">
            <form id="form-inventario" onsubmit="return false;">
                <div style="display:flex;flex-direction:column;gap:10px">       
                    <input id="add_cnpj" placeholder="CNPJ" maxlength="18" style
="padding:10px;border-radius:8px;border:0" />                                                       <input id="add_razao" placeholder="Razão Social" style="padd
ing:10px;border-radius:8px;border:0" />                                                             <input id="add_nf" placeholder="Nota Fiscal" style="padding:
10px;border-radius:8px;border:0" />                                                                 <div style="display:flex;gap:8px">
                        <input id="add_qtd" type="number" min="1" value="1" styl
e="padding:10px;border-radius:8px;border:0;width:120px" />                                              <select id="add_status" style="padding:10px;border-radiu
s:8px;border:0;flex:1">                                                                                     <option value="aguardando_pg">Aguardando PG</option>
                            <option value="envio_cliente">Enviado p/ Cliente</option>                                                                                                       <option value="estocado">Estocado</option>
                            <option value="envio_expedicao">Expedição</option>  
                        </select>
                    </div>
                    <input id="add_data_ultimo_registro" placeholder="Data últim
o registro (YYYY-MM-DD HH:MM)" style="padding:10px;border-radius:8px;border:0" />                                                                                                   <input id="add_codigo_rastreio_entrada" placeholder="Código 
rastreio entrada" style="padding:10px;border-radius:8px;border:0" />
                    <input id="add_codigo_rastreio_envio" placeholder="Código ra
streio envio" style="padding:10px;border-radius:8px;border:0" />                                    <input id="add_nota_fiscal_retorno" placeholder="Nota fiscal
 retorno" style="padding:10px;border-radius:8px;border:0" />                                        <input id="add_numero_orcamento" placeholder="Número orçamen
to" style="padding:10px;border-radius:8px;border:0" />                                              <input id="add_valor_orcamento" placeholder="Valor orçamento
" style="padding:10px;border-radius:8px;border:0" />                                                <input id="add_setor" placeholder="Setor" style="padding:10p
x;border-radius:8px;border:0" />                                                                    <select id="add_locker" style="padding:10px;border-radius:8p
x;border:0">                                                                                            <option value="">Armário (nenhum)</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                    <label style="display:flex;align-items:center;gap:8px"><inpu
t id="add_confirmado" type="checkbox"> Confirmado</label>                                           <div style="display:flex;gap:8px;margin-top:6px">
                        <button id="submitAdd" class="confirm-btn">Adicionar rem
essa</button>                                                                                           <button id="cancelAdd" class="btn-back">Cancelar</button
>                                                                                                   </div>
                </div>
            </form>
        </div>
    </div>

    <div style="margin-top:12px" class="small-muted">Filtro atual: <span id="cur
rentFilter">Todos</span></div>                                                  
</div>

<script src="/FrontEnd/JS/CnpjMask.js"></script>
<script>
const STATUS_ORDER = [
    'aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise',
    'em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'
];

const STATUS_LABELS = {
    'aguardando_nf':'Aguardando NF',
    'aguardando_nf_retorno':'Aguardando NF (retorno)',
    'aguardando_pg':'Aguardando PG',
    'descarte':'Descarte',
    'em_analise':'Em Análise',
    'em_reparo':'Em Reparo',
    'envio_analise':'Enviado p/ Análise',
    'envio_cliente':'Enviado p/ Cliente',
    'envio_expedicao':'Expedição',
    'estocado':'Estocado',
    'inspecao_qualidade':'Inspeção Qualidade'
};

let allItems = [];
let activeFilter = null;
let activeLocker = null;
let counts = {};
let isLoading = false;

function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function toggleLoading(show){
    const overlay = document.getElementById('loadingOverlay');
    if(!overlay) return;
    overlay.style.display = show ? 'flex' : 'none';
    overlay.setAttribute('aria-hidden', show ? 'false' : 'true');
}

function renderFilters(counts){
    const container = document.getElementById('statusFilters');
    container.innerHTML = '';

    const total = Object.values(counts).reduce((s,v)=>s+v,0);
    const btnAll = document.createElement('button');
    btnAll.className = 'status-btn' + (activeFilter===null? ' active':'');
    const lblAll = document.createElement('span'); lblAll.className = 'label'; lblAll.textContent = 'Total';
    const cntAll = document.createElement('span'); cntAll.className = 'count'; cntAll.textContent = total;
    btnAll.appendChild(lblAll); btnAll.appendChild(cntAll);
    btnAll.addEventListener('click', ()=>{ activeFilter=null; document.getElementById('currentFilter').textContent='Todos'; renderTable(); updateActiveBtn(); });
    container.appendChild(btnAll);

    STATUS_ORDER.forEach(code=>{
        const c = counts[code] || 0;
        const b = document.createElement('button');
        b.className = 'status-btn' + (activeFilter===code? ' active':'');
        b.dataset.status = code;
        const l = document.createElement('span'); l.className='label'; l.textContent = STATUS_LABELS[code] || code;
        const n = document.createElement('span'); n.className='count'; n.textContent = c;
        b.appendChild(l); b.appendChild(n);
        b.addEventListener('click', ()=>{ activeFilter = code; document.getElementById('currentFilter').textContent = STATUS_LABELS[code]||code; renderTable(); updateActiveBtn(); });
        container.appendChild(b);
    });
    updateLockerVisibility();
}

function updateActiveBtn(){
    document.querySelectorAll('.status-btn').forEach(b=>{
        const ds = b.dataset.status || '';
        if(ds === String(activeFilter)) b.classList.add('active');
        else if(activeFilter===null && b.querySelector('.label') && b.querySelector('.label').textContent === 'Total') b.classList.add('active');
        else b.classList.remove('active');
    });
}

function updateLockerVisibility(){
    const lf = document.getElementById('lockerFilter');
    const addLocker = document.getElementById('add_locker');
    const show = activeFilter === 'aguardando_pg';
    if(lf) lf.style.display = show ? 'inline-block' : 'none';
    if(addLocker) addLocker.style.display = show ? 'inline-block' : 'none';
}

function renderTable(items){
    const tbody = document.querySelector('#invTable tbody');
    if(!tbody) return;
    tbody.innerHTML = '';
    const source = Array.isArray(items) ? items : allItems;
    const filtered = source.filter(i=>{
        if(activeFilter && i.status !== activeFilter) return false;
        if(activeLocker && String(i.locker || '') !== String(activeLocker)) return false;
        return true;
    });
    const empty = filtered.length === 0;
    const emptyEl = document.getElementById('emptyState');
    if(emptyEl) emptyEl.style.display = empty ? 'block' : 'none';

    filtered.forEach(row => {
        const tr = document.createElement('tr');

        const tdRazao = document.createElement('td'); tdRazao.setAttribute('data-label','Razão Social'); tdRazao.textContent = row.razao_social || '—';
        const tdNf = document.createElement('td'); tdNf.setAttribute('data-label','Nota Fiscal'); tdNf.textContent = row.nota_fiscal || '—';
        const tdQtd = document.createElement('td'); tdQtd.setAttribute('data-label','Quantidade'); tdQtd.textContent = row.quantidade ?? row.qtd ?? '—';
        const tdArm = document.createElement('td'); tdArm.setAttribute('data-label','Armário');
        if(row.locker){ const sp = document.createElement('span'); sp.className='locker-badge'; sp.textContent = String(row.locker); tdArm.appendChild(sp); } else tdArm.textContent = '—';
        const tdStatus = document.createElement('td'); tdStatus.setAttribute('data-label','Status'); tdStatus.textContent = STATUS_LABELS[row.status] || row.status || '';

        const tdAction = document.createElement('td'); tdAction.setAttribute('data-label','Confirmar');
        const wrapper = document.createElement('div'); wrapper.style.display='flex'; wrapper.style.gap='8px'; wrapper.style.alignItems='center';

        if(activeFilter === 'aguardando_pg'){
            const sel = document.createElement('select'); sel.className='locker-select'; sel.dataset.id = row.id; sel.style.minWidth = '84px';
            const opts = ['','1','2','3','4','5'];
            opts.forEach(v=>{ const o = document.createElement('option'); o.value = v; o.textContent = v || '—'; sel.appendChild(o); });
            sel.value = row.locker || '';
            sel.addEventListener('change', (e)=> assignLocker(row.id, e.target.value, sel));
            wrapper.appendChild(sel);
        }

        const btn = document.createElement('button'); btn.className = 'confirm-btn'; btn.textContent = 'Confirmar';
        btn.addEventListener('click', ()=> confirmItem(row.id, btn));
        wrapper.appendChild(btn);
        tdAction.appendChild(wrapper);

        tr.appendChild(tdRazao);
        tr.appendChild(tdNf);
        tr.appendChild(tdQtd);
        tr.appendChild(tdArm);
        tr.appendChild(tdStatus);
        tr.appendChild(tdAction);

        tbody.appendChild(tr);
    });
}

async function loadData(){
    toggleLoading(true);
    try{
        const res = await fetch('/router_public.php?url=inventario-api&action=list', { credentials: 'include' });
        if(!res.ok) throw new Error('API não disponível');
        const json = await res.json();
        allItems = (json.items || []).map(r=>({
            id: r.id || r.resumo_id || 0,
            razao_social: r.cliente_nome || r.razao_social || '',
            nota_fiscal: r.nota_fiscal || '',
            quantidade: r.quantidade || r.qtd || 1,
            status: r.status || '',
            locker: r.armario_id || r.locker || null
        }));
        counts = {};
        allItems.forEach(i=> counts[i.status] = (counts[i.status]||0) + 1);
        renderFilters(counts);
        renderTable();
    }catch(e){
        console.error('Inventario API não respondeu:', e && e.message ? e.message : e);
        allItems = []; counts = {};
        renderFilters(counts);
        renderTable();
    } finally {
        toggleLoading(false);
    }
}

async function confirmItem(resumoId, btn){
    const original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('action','confirm');
        const res = await fetch('/router_public.php?url=inventario-api', { method: 'POST', body: fd, credentials: 'include' });
        const j = await res.json();
        if(j && j.success){
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#4ade80';
            const idx = allItems.findIndex(x=> x.id === resumoId);
            if(idx !== -1){ const st = allItems[idx].status; allItems.splice(idx,1); counts[st] = Math.max(0, (counts[st]||1)-1); renderFilters(counts); renderTable(); }
        } else {
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            btn.style.background = '#f43f5e';
        }
    }catch(e){
        btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        btn.style.background = '#f43f5e';
    }
    setTimeout(()=>{ btn.disabled = false; btn.innerHTML = original; btn.style.background = ''; }, 1500);
}

async function assignLocker(resumoId, locker, selectEl){
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('locker', locker);
        const res = await fetch('/router_public.php?url=inventario-api&action=assign_locker', { method: 'POST', body: fd, credentials: 'include' });
        const j = await res.json();
        if(j && j.success){ const it = allItems.find(x=> String(x.id) === String(resumoId)); if(it) it.locker = locker || null; renderFilters(counts); renderTable(); }
        else console.error('Falha ao atribuir armário');
    }catch(e){ console.error('Erro ao atribuir armário', e); }
}

function openPanelNew(){
    const side = document.getElementById('side-panel'); const overlay = document.getElementById('panel-overlay');
    if(side) side.classList.add('open'); if(overlay) overlay.classList.add('active');
    if(side) side.setAttribute('aria-hidden','false'); document.getElementById('panel-title').textContent = 'Cadastrar Remessa';
}
function closePanel(){ const side = document.getElementById('side-panel'); const overlay = document.getElementById('panel-overlay'); if(side) side.classList.remove('open'); if(overlay) overlay.classList.remove('active'); if(side) side.setAttribute('aria-hidden','true'); }

document.addEventListener('DOMContentLoaded', ()=>{
    const btnNewRecord = document.getElementById('btn-new-record'); if(btnNewRecord) btnNewRecord.addEventListener('click', (e)=>{ e.preventDefault(); openPanelNew(); });
    const btnClosePanel = document.getElementById('btn-close-panel'); if(btnClosePanel) btnClosePanel.addEventListener('click', (e)=>{ e.preventDefault(); closePanel(); });
    const panelOverlay = document.getElementById('panel-overlay'); if(panelOverlay) panelOverlay.addEventListener('click', ()=> closePanel());
    const cancelAdd = document.getElementById('cancelAdd'); if(cancelAdd) cancelAdd.addEventListener('click', (e)=>{ e.preventDefault(); closePanel(); });

    const submitAdd = document.getElementById('submitAdd');
    if(submitAdd) submitAdd.addEventListener('click', async ()=>{
        const razao = document.getElementById('add_razao').value.trim();
        const nf = document.getElementById('add_nf').value.trim();
        const cnpj = document.getElementById('add_cnpj').value.trim();
        const qtd = document.getElementById('add_qtd').value || 1;
        const status = document.getElementById('add_status').value;
        const data_ultimo_registro = document.getElementById('add_data_ultimo_registro').value.trim();
        const codigo_rastreio_entrada = document.getElementById('add_codigo_rastreio_entrada').value.trim();
        const codigo_rastreio_envio = document.getElementById('add_codigo_rastreio_envio').value.trim();
        const nota_fiscal_retorno = document.getElementById('add_nota_fiscal_retorno').value.trim();
        const numero_orcamento = document.getElementById('add_numero_orcamento').value.trim();
        const valor_orcamento = document.getElementById('add_valor_orcamento').value.trim();
        const setor = document.getElementById('add_setor').value.trim();
        const locker = document.getElementById('add_locker').value;
        const confirmado = document.getElementById('add_confirmado').checked ? 1 : 0;
        if(!razao || !nf){ alert('Razão social e nota fiscal são obrigatórios'); return; }
        try{
            const fd = new FormData();
            fd.append('razao_social', razao);
            fd.append('nota_fiscal', nf);
            fd.append('cnpj', cnpj);
            fd.append('quantidade', qtd);
            fd.append('status', status);
            fd.append('data_ultimo_registro', data_ultimo_registro);
            fd.append('codigo_rastreio_entrada', codigo_rastreio_entrada);
            fd.append('codigo_rastreio_envio', codigo_rastreio_envio);
            fd.append('nota_fiscal_retorno', nota_fiscal_retorno);
            fd.append('numero_orcamento', numero_orcamento);
            fd.append('valor_orcamento', valor_orcamento);
            fd.append('setor', setor);
            fd.append('armario_id', locker);
            fd.append('confirmado', confirmado);
            const res = await fetch('/router_public.php?url=inventario-api&action=create_manual', { method: 'POST', body: fd, credentials: 'include' });
            const j = await res.json();
            if(j && j.success && j.item){ allItems.unshift(j.item); counts[j.item.status] = (counts[j.item.status]||0)+1; renderFilters(counts); renderTable(); closePanel(); }
            else alert('Erro ao criar remessa');
        }catch(e){ console.error(e); alert('Erro ao criar remessa'); }
    });

    document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape'){ closePanel(); } });

    const lockerFilter = document.getElementById('lockerFilter');
    if(lockerFilter) lockerFilter.addEventListener('change', (e)=>{ activeLocker = e.target.value || null; renderTable(); });

    const search = document.getElementById('searchInput');
    if(search){
        let timer = null;
        search.addEventListener('input', (e)=>{
            clearTimeout(timer);
            timer = setTimeout(()=>{
                const q = e.target.value.trim().toLowerCase();
                if(q === ''){ renderFilters(counts); renderTable(); return; }
                const filtered = allItems.filter(i=> (i.razao_social||'').toLowerCase().includes(q) || (i.nota_fiscal||'').toLowerCase().includes(q));
                renderTable(filtered);
            }, 250);
        });
    }

    // CNPJ mask init
    const addCnpj = document.getElementById('add_cnpj');
    if(addCnpj){ addCnpj.addEventListener('input', function(){ applyCNPJMask(this); });
        addCnpj.addEventListener('blur', async ()=>{
            const cnpjVal = addCnpj.value.trim();
            if(cnpjVal.length !== 18) return;
            try{
                const res = await fetch('/BackEnd/buscar_cliente.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'cnpj=' + encodeURIComponent(cnpjVal) });
                const json = await res.json();
                if(json && json.encontrado){ const razaoEl = document.getElementById('add_razao'); if(razaoEl) razaoEl.value = json.razao_social || ''; }
            }catch(err){ console.error('Erro ao buscar cliente por CNPJ:', err); }
        });
    }

    updateLockerVisibility();
    loadData();
});
</script>

</body>
</html>
