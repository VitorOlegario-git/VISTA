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
    <link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/consulta.css'); ?>">
    <link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <style>
        /* Inventário — ajustes responsivos e visual moderno */
        :root{--bg:#071029;--card:#071827;--muted:#9aa4b2;--accent:#6366f1;--success:#10b981}
        body{background:linear-gradient(180deg,#071029 0%,#071827 60%);color:#e6eef8;font-family:Inter,Segoe UI,Arial,sans-serif}
        .inv-container{padding:20px;max-width:1200px;margin:0 auto}
        .inv-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
        .inv-title{display:flex;align-items:center;gap:12px}
        .inv-logo{height:40px;border-radius:6px}
        .inv-title h1{font-size:20px;margin:0;color:#f8fafc}
        .inv-actions{display:flex;align-items:center;gap:8px}
        #searchInput{padding:8px 12px;border-radius:8px;border:0;min-width:220px}
        .btn-back{background:transparent;border:1px solid rgba(255,255,255,0.08);color:#fff;padding:8px 12px;border-radius:8px;cursor:pointer}

        .status-filters{display:flex;flex-wrap:nowrap;gap:10px;overflow-x:auto;padding-bottom:6px}
        .status-btn{background:rgba(255,255,255,0.04);color:#fff;padding:10px 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.03);cursor:pointer;display:flex;flex-direction:column;align-items:flex-start;min-width:140px}
        .status-btn .label{font-size:12px;opacity:0.9}
        .status-btn .count{font-weight:700;font-size:18px}
        .status-btn.active{box-shadow:0 6px 18px rgba(99,102,241,0.14);border-color:var(--accent);transform:translateY(-3px)}

        .table-wrapper{position:relative;margin-top:16px;background:linear-gradient(180deg,#ffffff 0%, #ffffff 0%);border-radius:12px;overflow:hidden;color:#0b1724}
        table.inv-table{width:100%;border-collapse:collapse}
        table.inv-table thead{background:transparent}
        table.inv-table th, table.inv-table td{padding:12px 16px;text-align:left;border-bottom:1px solid #eef2f6}
        table.inv-table tbody tr:hover{background:#f8fafc}
        .confirm-btn{background:var(--success);color:#fff;border:none;padding:6px 12px;border-radius:8px;cursor:pointer}
        .confirm-btn[disabled]{opacity:0.7;cursor:not-allowed}
        .small-muted{font-size:13px;color:rgba(255,255,255,0.65)}
        .locker-badge{display:inline-block;background:rgba(99,102,241,0.12);color:var(--accent);padding:6px 8px;border-radius:999px;font-weight:700;font-size:13px}
        .locker-select{padding:6px;border-radius:6px;border:1px solid rgba(11,23,36,0.06);background:#fff;color:#0b1724}

        .loading-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.6);backdrop-filter:blur(2px)}
        .spinner{width:36px;height:36px;border-radius:50%;border:4px solid rgba(0,0,0,0.06);border-top-color:var(--accent);animation:spin 1s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}

        .empty-state{padding:20px;text-align:center;color:#475569}

        @media(max-width:720px){
            #searchInput{min-width:120px}
            .status-filters{gap:8px}
            .status-btn{min-width:46%}
            table.inv-table thead{display:none}
            table.inv-table, table.inv-table tbody, table.inv-table tr, table.inv-table td{display:block;width:100%}
            table.inv-table tr{margin-bottom:12px}
            table.inv-table td{padding:10px 12px;background:transparent;border-bottom:0}
            table.inv-table td:before{content:attr(data-label);font-weight:600;display:block;margin-bottom:6px}
        }
    </style>
</head>
<body>
<div class="inv-container">
    <div class="inv-header">
        <div class="inv-title">
            <img src="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>" alt="logo" class="inv-logo">
            <div>
                <h1>Inventário — Status de Remessas</h1>
                <div class="small-muted">Visualize e confirme remessas por status</div>
            </div>
        </div>
        <div class="inv-actions">
            <input id="searchInput" type="search" placeholder="Pesquisar por razão social ou nota fiscal..." aria-label="Pesquisar" />
            <select id="lockerFilter" style="padding:8px 10px;border-radius:8px;border:0;background:rgba(255,255,255,0.03);color:#fff;margin-left:8px">
                <option value="">Armário (Todos)</option>
                <option value="1">Armário 1</option>
                <option value="2">Armário 2</option>
                <option value="3">Armário 3</option>
                <option value="4">Armário 4</option>
                <option value="5">Armário 5</option>
            </select>
            <button id="addBtn" class="btn-back" style="margin-left:8px">Adicionar</button>
            <button onclick="location.href='/router_public.php?url=dashboard'" class="btn-back">Voltar</button>
        </div>
    </div>

    <div class="status-filters" id="statusFilters" role="tablist" aria-label="Filtros por status">
        <!-- Botões serão renderizados dinamicamente -->
    </div>

    <div class="table-wrapper">
        <div id="loadingOverlay" class="loading-overlay" aria-hidden="true"><div class="spinner"></div></div>
        <table class="inv-table" id="invTable">
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
        <div id="emptyState" class="empty-state" style="display:none">Nenhuma remessa encontrada para o filtro atual.</div>
    </div>

    <div id="addForm" style="display:none;margin-top:12px;background:rgba(255,255,255,0.02);padding:12px;border-radius:8px">
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <input id="add_razao" placeholder="Razão Social" style="padding:8px;border-radius:8px;border:0;min-width:220px" />
            <input id="add_nf" placeholder="Nota Fiscal" style="padding:8px;border-radius:8px;border:0;min-width:140px" />
            <input id="add_cnpj" placeholder="CNPJ" style="padding:8px;border-radius:8px;border:0;min-width:160px" />
            <input id="add_qtd" type="number" min="1" value="1" style="padding:8px;border-radius:8px;border:0;width:80px" />
            <select id="add_status" style="padding:8px;border-radius:8px;border:0">
                <option value="aguardando_pg">Aguardando PG</option>
                <option value="envio_cliente">Enviado p/ Cliente</option>
                <option value="estocado">Estocado</option>
                <option value="envio_expedicao">Expedição</option>
            </select>
            <input id="add_data_ultimo_registro" placeholder="Data último registro (YYYY-MM-DD HH:MM)" style="padding:8px;border-radius:8px;border:0;min-width:200px" />
            <input id="add_codigo_rastreio_entrada" placeholder="Código rastreio entrada" style="padding:8px;border-radius:8px;border:0;min-width:200px" />
            <input id="add_codigo_rastreio_envio" placeholder="Código rastreio envio" style="padding:8px;border-radius:8px;border:0;min-width:200px" />
            <input id="add_nota_fiscal_retorno" placeholder="Nota fiscal retorno" style="padding:8px;border-radius:8px;border:0;min-width:140px" />
            <input id="add_numero_orcamento" placeholder="Número orçamento" style="padding:8px;border-radius:8px;border:0;min-width:140px" />
            <input id="add_valor_orcamento" placeholder="Valor orçamento" style="padding:8px;border-radius:8px;border:0;min-width:120px" />
            <input id="add_setor" placeholder="Setor" style="padding:8px;border-radius:8px;border:0;min-width:120px" />
            <select id="add_locker" style="padding:8px;border-radius:8px;border:0">
                <option value="">Armário (nenhum)</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
        </div>
        <div style="margin-top:8px">
            <label style="display:inline-flex;align-items:center;gap:8px"><input id="add_confirmado" type="checkbox"> Confirmado</label>
            <button id="submitAdd" class="confirm-btn" style="margin-left:12px">Adicionar remessa</button>
            <button id="cancelAdd" class="btn-back" style="margin-left:8px">Cancelar</button>
        </div>
    </div>

    <div style="margin-top:12px" class="small-muted">Filtro atual: <span id="currentFilter">Todos</span></div>

</div>

<script>
const STATUS_ORDER = [
    'aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise','em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'
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

function renderFilters(counts){
    const container = document.getElementById('statusFilters');
    container.innerHTML = '';
    // Add "Todos" button
    const total = Object.values(counts).reduce((s,v)=>s+v,0);
    const btnAll = document.createElement('button');
    btnAll.className = 'status-btn' + (activeFilter===null? ' active':'');
    btnAll.innerHTML = `<span class="label">Total</span><span class="count">${total}</span>`;
    btnAll.onclick = ()=>{ activeFilter=null; document.getElementById('currentFilter').textContent='Todos'; renderTable(); updateActiveBtn(); };
    container.appendChild(btnAll);

    STATUS_ORDER.forEach(code=>{
        const c = counts[code] || 0;
        const b = document.createElement('button');
        b.className = 'status-btn' + (activeFilter===code? ' active':'');
        b.dataset.status = code;
        b.innerHTML = `<span class="label">${STATUS_LABELS[code]||code}</span><span class="count">${c}</span>`;
        b.onclick = ()=>{ activeFilter = code; document.getElementById('currentFilter').textContent = STATUS_LABELS[code]||code; renderTable(); updateActiveBtn(); };
        container.appendChild(b);
    });
    updateLockerVisibility();
}

function updateActiveBtn(){
    document.querySelectorAll('.status-btn').forEach(b=>{
        if(b.dataset.status === activeFilter) b.classList.add('active'); else if(activeFilter===null && b.innerText.includes('Total')) b.classList.add('active'); else b.classList.remove('active');
    });
}

function updateLockerVisibility(){
    const lf = document.getElementById('lockerFilter');
    const addLocker = document.getElementById('add_locker');
    const show = activeFilter === 'aguardando_pg';
    if(lf) lf.style.display = show ? 'inline-block' : 'none';
    if(addLocker) addLocker.style.display = show ? 'inline-block' : 'none';
}

function renderTable(){
    const tbody = document.querySelector('#invTable tbody');
    tbody.innerHTML = '';
    const filtered = allItems.filter(i=>{
        if(activeFilter && i.status !== activeFilter) return false;
        if(activeLocker && String(i.locker || '') !== String(activeLocker)) return false;
        return true;
    });
    const empty = filtered.length === 0;
    const emptyEl = document.getElementById('emptyState');
    if(emptyEl) emptyEl.style.display = empty ? 'block' : 'none';
    filtered.forEach(row=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="Razão Social">${escapeHtml(row.razao_social||'—')}</td>
            <td data-label="Nota Fiscal">${escapeHtml(row.nota_fiscal||'—')}</td>
            <td data-label="Quantidade">${row.quantidade ?? row.qtd ?? '—'}</td>
            <td data-label="Armário">${row.locker ? `<span class="locker-badge">${escapeHtml(row.locker)}</span>` : '—'}</td>
            <td data-label="Status">${STATUS_LABELS[row.status]||row.status}</td>
            <td data-label="Confirmar"><div style="display:flex;gap:8px;align-items:center">${activeFilter==='aguardando_pg'?`<select data-id="${row.id}" class="locker-select" style="min-width:84px">
                <option value="">—</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>`: ''}<button class="confirm-btn" onclick="confirmItem(${row.id || 0}, this)">Confirmar</button></div></td>
        `;
        tbody.appendChild(tr);
    });
    // set locker selects values and change handlers
    document.querySelectorAll('.locker-select').forEach(s=>{
        const id = s.dataset.id;
        const item = allItems.find(x=>String(x.id) === String(id));
        if(item) s.value = item.locker || '';
        s.addEventListener('change', (e)=>{ assignLocker(id, e.target.value, s); });
    });
}

function escapeHtml(s){ return String(s).replace(/[&<>\\\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

async function loadData(){
    isLoading = true; toggleLoading(true);
    try{
        const res = await fetch('/router_public.php?url=inventario-api&action=list', {
            method: 'GET',
            credentials: 'include',
            cache: 'no-cache',
            headers: { 'Accept': 'application/json' }
        });
        if(!res.ok) throw new Error('API não disponível');
        const json = await res.json();
        // Expecting json.items with fields: cliente_nome (or razao_social), nota_fiscal, quantidade, status, id
        allItems = (json.items || []).map(r=>({
            id: r.id || r.resumo_id || 0,
            razao_social: r.cliente_nome || r.razao_social || '',
            nota_fiscal: r.nota_fiscal || '',
            quantidade: r.quantidade || r.qtd || 1,
            status: r.status || ''
        }));
        counts = {};
        allItems.forEach(i=>counts[i.status] = (counts[i.status]||0)+1);
        renderFilters(counts);
        renderTable();
    }catch(e){
        // If API fails, show empty state and log error (do not inject mock data)
        console.error('Inventario API não respondeu:', e.message);
        allItems = [];
        counts = {};
        renderFilters(counts);
        renderTable();
    } finally {
        isLoading = false; toggleLoading(false);
    }
}

async function confirmItem(resumoId, btn){
    const original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('action','confirm');
        const res = await fetch('/router_public.php?url=inventario-api', {method:'POST', body:fd, credentials: 'include'});
        const j = await res.json();
        if(j && j.success){
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background='#4ade80';
            // optimistic update: remove item from list and update counts
            const idx = allItems.findIndex(x => x.id === resumoId);
            if(idx !== -1){
                const st = allItems[idx].status;
                allItems.splice(idx,1);
                counts[st] = Math.max(0, (counts[st]||1) - 1);
                renderFilters(counts);
                renderTable();
            }
        } else {
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            btn.style.background='#f43f5e';
        }
    }catch(e){
        btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        btn.style.background='#f43f5e';
    }
    setTimeout(()=>{ btn.disabled=false; btn.innerHTML = original; btn.style.background=''; },1500);
}

async function assignLocker(resumoId, locker, selectEl){
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('locker', locker);
        const res = await fetch('/router_public.php?url=inventario-api&action=assign_locker', {method:'POST', body:fd, credentials:'include'});
        const j = await res.json();
        if(j && j.success){
            const it = allItems.find(x=>String(x.id) === String(resumoId)); if(it) it.locker = locker || null; renderFilters(counts); renderTable();
        } else {
            console.error('Falha ao atribuir armário');
        }
    } catch(e){ console.error('Erro ao atribuir armário', e); }
}

// Add remessa UI
document.getElementById('addBtn').addEventListener('click', ()=>{ document.getElementById('addForm').style.display = 'block'; });
document.getElementById('cancelAdd').addEventListener('click', ()=>{ document.getElementById('addForm').style.display = 'none'; });
document.getElementById('submitAdd').addEventListener('click', async ()=>{
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
        const res = await fetch('/router_public.php?url=inventario-api&action=create_manual', {method:'POST', body:fd, credentials:'include'});
        const j = await res.json();
        if(j && j.success && j.item){ allItems.unshift(j.item); counts[j.item.status] = (counts[j.item.status]||0)+1; renderFilters(counts); renderTable(); document.getElementById('addForm').style.display='none'; }
        else alert('Erro ao criar remessa');
    }catch(e){ console.error(e); alert('Erro ao criar remessa'); }
});

// locker filter
document.getElementById('lockerFilter').addEventListener('change', (e)=>{ activeLocker = e.target.value || null; renderTable(); });
// Ensure locker visibility is correct on load
updateLockerVisibility();

function toggleLoading(show){
    const overlay = document.getElementById('loadingOverlay');
    if(!overlay) return;
    overlay.style.display = show ? 'flex' : 'none';
    overlay.setAttribute('aria-hidden', show ? 'false' : 'true');
}

document.addEventListener('DOMContentLoaded', ()=>{
    const search = document.getElementById('searchInput');
    if(search){
        let timer = null;
        search.addEventListener('input', (e)=>{
            clearTimeout(timer);
            timer = setTimeout(()=>{
                const q = e.target.value.trim().toLowerCase();
                if(q === ''){ renderFilters(counts); renderTable(); return; }
                const filtered = allItems.filter(i=> (i.razao_social||'').toLowerCase().includes(q) || (i.nota_fiscal||'').toLowerCase().includes(q));
                const tbody = document.querySelector('#invTable tbody'); tbody.innerHTML='';
                filtered.forEach(row=>{
                    const tr = document.createElement('tr');
                    tr.innerHTML = `\n            <td data-label="Razão Social">${escapeHtml(row.razao_social||'—')}</td>\n            <td data-label="Nota Fiscal">${escapeHtml(row.nota_fiscal||'—')}</td>\n            <td data-label="Quantidade">${row.quantidade ?? row.qtd ?? '—'}</td>\n            <td data-label="Status">${STATUS_LABELS[row.status]||row.status}</td>\n            <td data-label="Confirmar"><button class="confirm-btn" onclick="confirmItem(${row.id || 0}, this)">Confirmar</button></td>\n        `;
                    tbody.appendChild(tr);
                });
            }, 250);
        });
    }
    loadData();
});
</script>

</body>
</html>
