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
        .inv-container{padding:16px;max-width:1100px;margin:0 auto}
        .inv-header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
        .inv-title{display:flex;align-items:center;gap:10px}
        .inv-title h1{font-size:20px;margin:0}
        .status-filters{display:flex;flex-wrap:wrap;gap:8px}
        .status-btn{background:linear-gradient(135deg,#0f172a,#0b2540);color:#fff;padding:8px 12px;border-radius:10px;border:none;cursor:pointer;display:flex;flex-direction:column;align-items:flex-start;min-width:120px}
        .status-btn .label{font-size:12px;opacity:0.9}
        .status-btn .count{font-weight:700;font-size:18px}
        .status-btn.active{outline:3px solid rgba(99,102,241,0.18)}
        .table-wrapper{margin-top:16px;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(2,6,23,0.08);overflow:hidden}
        table.inv-table{width:100%;border-collapse:collapse}
        table.inv-table thead{background:linear-gradient(90deg,#eef2ff,#ffffff)}
        table.inv-table th, table.inv-table td{padding:10px 12px;text-align:left;border-bottom:1px solid #f1f5f9}
        .confirm-btn{background:#10b981;color:#fff;border:none;padding:6px 10px;border-radius:8px;cursor:pointer}
        .small-muted{font-size:12px;color:#6b7280}

        @media(max-width:720px){
            .status-btn{min-width:48%;font-size:13px}
            table.inv-table thead{display:none}
            table.inv-table, table.inv-table tbody, table.inv-table tr, table.inv-table td{display:block;width:100%}
            table.inv-table tr{margin-bottom:12px}
            table.inv-table td{padding:8px;background:transparent;border-bottom:0}
            table.inv-table td:before{content:attr(data-label);font-weight:600;display:block;margin-bottom:6px}
        }
    </style>
</head>
<body>
<div class="inv-container">
    <div class="inv-header">
        <div class="inv-title">
            <img src="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>" alt="logo" style="height:36px">
            <h1>Inventário — Status de Remessas</h1>
        </div>
        <div>
            <button onclick="location.href='/router_public.php?url=dashboard'" class="status-btn" style="min-width:90px">Voltar</button>
        </div>
    </div>

    <div class="status-filters" id="statusFilters" role="tablist" aria-label="Filtros por status">
        <!-- Botões serão renderizados dinamicamente -->
    </div>

    <div class="table-wrapper">
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
        const counts = {};
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
        const counts = {};
        allItems.forEach(i=>counts[i.status] = (counts[i.status]||0)+1);
        renderFilters(counts);
        renderTable();
    }
}

async function confirmItem(resumoId, btn){
    btn.disabled = true; btn.textContent='...';
    try{
        const fd = new FormData(); fd.append('resumo_id', resumoId); fd.append('action','confirm');
        const res = await fetch('/router_public.php?url=inventario-api', {method:'POST', body:fd});
        const j = await res.json();
        if(j && j.success){
            btn.textContent='OK';
            btn.style.background='#4ade80';
        } else {
            btn.textContent='Erro'; btn.style.background='#f43f5e';
        }
    }catch(e){
        btn.textContent='Erro'; btn.style.background='#f43f5e';
    }
    setTimeout(()=>{ btn.disabled=false; btn.textContent='Confirmar'; btn.style.background=''; },1500);
}

document.addEventListener('DOMContentLoaded', ()=>{ loadData(); });
</script>

</body>
</html>
