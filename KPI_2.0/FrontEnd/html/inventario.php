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
                    <th>Status</th>
                    <th>Confirmar</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div id="emptyState" class="empty-state" style="display:none">Nenhuma remessa encontrada para o filtro atual.</div>
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
}

function updateActiveBtn(){
    document.querySelectorAll('.status-btn').forEach(b=>{
        if(b.dataset.status === activeFilter) b.classList.add('active'); else if(activeFilter===null && b.innerText.includes('Total')) b.classList.add('active'); else b.classList.remove('active');
    });
}

function renderTable(){
    const tbody = document.querySelector('#invTable tbody');
    tbody.innerHTML = '';
    const filtered = activeFilter? allItems.filter(i=>i.status===activeFilter) : allItems;
    const empty = filtered.length === 0;
    const emptyEl = document.getElementById('emptyState');
    if(emptyEl) emptyEl.style.display = empty ? 'block' : 'none';
    filtered.forEach(row=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td data-label="Razão Social">${escapeHtml(row.razao_social||'—')}</td>
            <td data-label="Nota Fiscal">${escapeHtml(row.nota_fiscal||'—')}</td>
            <td data-label="Quantidade">${row.quantidade ?? row.qtd ?? '—'}</td>
            <td data-label="Status">${STATUS_LABELS[row.status]||row.status}</td>
            <td data-label="Confirmar"><button class="confirm-btn" onclick="confirmItem(${row.id || 0}, this)">Confirmar</button></td>
        `;
        tbody.appendChild(tr);
    });
}

function escapeHtml(s){ return String(s).replace(/[&<>\\\"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

async function loadData(){
    isLoading = true; toggleLoading(true);
    try{
        const res = await fetch('/router_public.php?url=inventario-api&action=list');
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
        // fallback mock data to allow frontend work while backend is reimplentado
        console.warn('Inventario API não respondeu, usando mock data:', e.message);
        allItems = [
            {id:1,razao_social:'ACME LTDA',nota_fiscal:'NF-1001',quantidade:3,status:'aguardando_pg'},
            {id:2,razao_social:'COMERCIO XYZ',nota_fiscal:'NF-1002',quantidade:1,status:'envio_cliente'},
            {id:3,razao_social:'SOLUCOES SA',nota_fiscal:'NF-1003',quantidade:2,status:'estocado'},
            {id:4,razao_social:'IND-EX',nota_fiscal:'NF-1004',quantidade:5,status:'envio_expedicao'}
        ];
        counts = {};
        allItems.forEach(i=>counts[i.status] = (counts[i.status]||0)+1);
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
        const res = await fetch('/router_public.php?url=inventario-api', {method:'POST', body:fd});
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
