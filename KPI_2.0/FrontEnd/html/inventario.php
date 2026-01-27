<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';

verificarSessao();
definirHeadersSeguranca();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Inventário - VISTA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= asset('FrontEnd/CSS/consulta.css') ?>">
    <link rel="stylesheet" href="<?= asset('FrontEnd/CSS/recebimento.css') ?>">
    <link rel="icon" href="<?= asset('FrontEnd/CSS/imagens/VISTA.png') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>

<style>
:root{
    --bg:#071029;
    --card:#071827;
    --accent:#6366f1;
    --success:#10b981;
}

body{
    background:linear-gradient(180deg,#071029,#071827);
    color:#e6eef8;
    font-family:Inter,Segoe UI,Arial,sans-serif;
}

.inv-container{max-width:1200px;margin:auto;padding:20px}

.inv-header{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
.inv-title{display:flex;gap:12px;align-items:center}
.inv-logo{height:40px;border-radius:6px}

.inv-actions{display:flex;gap:8px;flex-wrap:wrap}

.inv-actions input#searchInput{
    width:130px;
    padding:8px 10px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.08);
    background:rgba(255,255,255,0.03);
    color:var(--bg, #071029);
    outline:0;
    transition:box-shadow .15s, border-color .15s, transform .06s;
}
.inv-actions input#searchInput:focus{
    border-color:var(--accent);
    box-shadow:0 8px 24px rgba(99,102,241,0.12);
    background:rgba(255,255,255,0.02);
}

.inv-actions button{
    padding:6px 8px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,0.06);
    background:transparent;
    color:inherit;
    cursor:pointer;
    font-weight:600;
    transition:transform .06s, box-shadow .08s, filter .08s;
    min-width:80px;
}
.inv-actions button:active{transform:translateY(1px)}

#btn-new-record{
    background:linear-gradient(180deg,#4ade80,#10b981);
    color:#07202a;
    border:0;
    box-shadow:0 6px 18px rgba(16,185,129,0.16);
    min-width:90px;
}
#btn-new-record:hover{filter:brightness(.96)}

#btn-back{
    background:transparent;
    border:1px solid rgba(255,255,255,0.12);
    color:inherit;
    min-width:70px;
}
#btn-back:hover{background:rgba(255,255,255,0.02)}

.status-filters{display:flex;gap:10px;overflow-x:auto;margin-top:12px}
.status-btn{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    color:#fff;
    padding:10px 14px;
    border-radius:12px;
    cursor:pointer;
    min-width:140px;
}
.status-btn.active{border-color:var(--accent);box-shadow:0 6px 18px rgba(99,102,241,.3)}

.table-wrapper{margin-top:16px;background:#fff;border-radius:12px;color:#000;overflow:hidden}
.inv-table{width:100%;border-collapse:collapse}
.inv-table th,.inv-table td{padding:12px 16px;border-bottom:1px solid #eee}

.confirm-btn{
    background:var(--success);
    border:0;
    padding:6px 12px;
    border-radius:8px;
    color:#fff;
    cursor:pointer;
}

.locker-badge{
    background:rgba(99,102,241,.15);
    color:var(--accent);
    padding:4px 10px;
    border-radius:999px;
    font-weight:700;
}

.loading-overlay{
    position:absolute;
    inset:0;
    background:rgba(255,255,255,.6);
    display:flex;
    align-items:center;
    justify-content:center;
}
.spinner{
    width:36px;height:36px;
    border-radius:50%;
    border:4px solid #ccc;
    border-top-color:var(--accent);
    animation:spin 1s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}

.empty-state{text-align:center;padding:20px;color:#555}

/* Modal add-record */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:1200}
.modal-card{background:#041226;color:#e6eef8;border-radius:12px;padding:18px;min-width:320px;max-width:560px;box-shadow:0 20px 60px rgba(2,6,23,0.6)}
.modal-card h3{margin:0 0 8px 0}
.modal-row{display:flex;gap:8px;margin-bottom:8px}
.modal-row input,.modal-row select, .modal-card input, .modal-card select{flex:1;padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:rgba(255,255,255,0.02);color:inherit}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
.modal-actions button{padding:8px 12px;border-radius:8px}
.modal-error{color:#fca5a5;font-size:13px;margin-top:6px}
</style>
</head>

<body>
<div class="inv-container">

<header class="inv-header">
    <div class="inv-title">
        <img src="<?= asset('FrontEnd/CSS/imagens/VISTA.png') ?>" class="inv-logo">
        <div>
            <h1>Inventário — Remessas</h1>
            <small>Status e confirmação</small>
        </div>
    </div>

    <div class="inv-actions">
        <input id="searchInput" placeholder="Pesquisar..." type="search">
        <select id="lockerFilter" style="display:none">
            <option value="">Armário (todos)</option>
            <?php for($i=1;$i<=5;$i++): ?>
                <option value="<?= $i ?>">Armário <?= $i ?></option>
            <?php endfor ?>
        </select>
        <button id="btn-new-record">Adicionar</button>
        <button id="btn-back" onclick="location.href='/router_public.php?url=dashboard'">Voltar</button>
    </div>
</header>

<div id="statusFilters" class="status-filters"></div>

<div class="table-wrapper">
    <div id="loadingOverlay" class="loading-overlay" style="display:none">
        <div class="spinner"></div>
    </div>
    <table class="inv-table">
        <thead>
            <tr>
                <th>Razão Social</th>
                <th>NF</th>
                <th>Qtd</th>
                <th>Armário</th>
                <th>Status</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody id="tableBody"></tbody>
    </table>
    <div id="emptyState" class="empty-state" style="display:none">
        Nenhuma remessa encontrada
    </div>
</div>

</div>

<!-- Add record modal -->
<div id="addModal" class="modal-backdrop" role="dialog" aria-hidden="true">
    <div class="modal-card" role="document">
        <h3>Adicionar remessa</h3>
        <div class="modal-body">
            <div class="modal-row">
                <input id="add_razao" placeholder="Razão Social" />
            </div>
            <div class="modal-row">
                <input id="add_cnpj" placeholder="CNPJ" />
                <input id="add_nota" placeholder="Nota Fiscal" />
            </div>
            <div class="modal-row">
                <input id="add_qtd" type="number" min="1" placeholder="Quantidade" />
                <select id="add_locker">
                    <option value="">Armário (nenhum)</option>
                    <option value="1">Armário 1</option>
                    <option value="2">Armário 2</option>
                    <option value="3">Armário 3</option>
                    <option value="4">Armário 4</option>
                    <option value="5">Armário 5</option>
                </select>
            </div>
            <div id="addError" class="modal-error" style="display:none"></div>
            <div class="modal-actions">
                <button id="addCancel">Cancelar</button>
                <button id="addSubmit" class="confirm-btn">Cadastrar</button>
            </div>
        </div>
    </div>
</div>

<script src="/FrontEnd/JS/CnpjMask.js"></script>
<script>
(() => {

const STATUS_ORDER = [
 'aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise',
 'em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'
];

const STATUS_LABELS = {
 aguardando_nf:'Aguardando NF',
 aguardando_nf_retorno:'Aguardando NF (retorno)',
 aguardando_pg:'Aguardando PG',
 descarte:'Descarte',
 em_analise:'Em análise',
 em_reparo:'Em reparo',
 envio_analise:'Envio p/ análise',
 envio_cliente:'Envio p/ cliente',
 envio_expedicao:'Expedição',
 estocado:'Estocado',
 inspecao_qualidade:'Inspeção'
};

let allItems = [];
let searchQuery = '';
let activeFilter = null;
let activeLocker = null;

const $ = id => document.getElementById(id);

function toggleLoading(show){
    $('loadingOverlay').style.display = show ? 'flex' : 'none';
}

function normalizeStatus(s){
    return String(s||'').split(':')[0];
}

function escapeHtml(str){
    if(str == null) return '';
    return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#39;");
}

function renderFilters(){
    const el = $('statusFilters');
    el.innerHTML = '';

    const counts = {};
    allItems.forEach(i => counts[i.status] = (counts[i.status]||0)+1);

    const total = Object.values(counts).reduce((a,b)=>a+b,0);

    createBtn('Total', total, null);

    STATUS_ORDER.forEach(st=>{
        createBtn(STATUS_LABELS[st]||st, counts[st]||0, st);
    });

    function createBtn(label,count,code){
        const b = document.createElement('button');
        b.className = 'status-btn'+(activeFilter===code?' active':'');
        const lbl = document.createElement('div'); lbl.textContent = label;
        const cnt = document.createElement('strong'); cnt.textContent = count;
        b.appendChild(lbl); b.appendChild(cnt);
        b.addEventListener('click',()=>{
            activeFilter = code;
            render();
        });
        el.appendChild(b);
    }
}

function render(){
    renderFilters();
    renderTable();
    $('lockerFilter').style.display = activeFilter==='aguardando_pg'?'inline-block':'none';
}

function renderTable(){
    const body = $('tableBody');
    body.innerHTML = '';

    const q = (searchQuery||'').toLowerCase();

    const filtered = allItems.filter(i=>{
        if(activeFilter && i.status!==activeFilter) return false;
        if(activeLocker && String(i.locker)!==String(activeLocker)) return false;
        if(q){
            const rs = String(i.razao_social||'').toLowerCase();
            const nf = String(i.nota_fiscal||'').toLowerCase();
            if(!(rs.includes(q) || nf.includes(q))) return false;
        }
        return true;
    });

    $('emptyState').style.display = filtered.length ? 'none' : 'block';

    filtered.forEach(r=>{
        const tr = document.createElement('tr');

        const tdRazao = document.createElement('td'); tdRazao.textContent = r.razao_social || '';
        const tdNf = document.createElement('td'); tdNf.textContent = r.nota_fiscal || '';
        const tdQtd = document.createElement('td'); tdQtd.textContent = r.quantidade;
        const tdLocker = document.createElement('td');
        if(r.locker){ const sp = document.createElement('span'); sp.className='locker-badge'; sp.textContent = r.locker; tdLocker.appendChild(sp); } else { tdLocker.textContent = '—'; }
        const tdStatus = document.createElement('td'); tdStatus.textContent = STATUS_LABELS[r.status]||r.status;
        const tdAction = document.createElement('td');
        const btn = document.createElement('button'); btn.className='confirm-btn'; btn.textContent='Confirmar';
        btn.addEventListener('click',()=>confirmItem(r.id));
        tdAction.appendChild(btn);

        tr.appendChild(tdRazao);
        tr.appendChild(tdNf);
        tr.appendChild(tdQtd);
        tr.appendChild(tdLocker);
        tr.appendChild(tdStatus);
        tr.appendChild(tdAction);

        body.appendChild(tr);
    });
}

async function loadData(){
    try{
        toggleLoading(true);
        const res = await fetch('/router_public.php?url=inventario-api&action=list',{credentials:'include'});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const j = await res.json();

        allItems = (j.items||[]).map(r=>({
            id:r.id,
            razao_social:r.razao_social||r.cliente_nome||'',
            nota_fiscal:r.nota_fiscal||'',
            quantidade:r.quantidade||1,
            locker:r.armario_id||null,
            status:normalizeStatus(r.status)
        }));

        render();
    }catch(err){
        console.error('loadData error',err);
        alert('Erro ao carregar dados: '+(err.message||err));
    }finally{
        toggleLoading(false);
    }
}

async function confirmItem(id){
    try{
        const fd = new FormData();
        fd.append('resumo_id',id);
        fd.append('action','confirm');

        const res = await fetch('/router_public.php?url=inventario-api',{method:'POST',body:fd,credentials:'include'});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const j = await res.json();
        if(j.success){
            allItems = allItems.filter(i=>i.id!==id);
            render();
        }else{
            alert('Erro ao confirmar: '+(j.message||'Resposta do servidor inválida'));
        }
    }catch(err){
        console.error('confirmItem error',err);
        alert('Erro ao confirmar: '+(err.message||err));
    }
}

$('searchInput').addEventListener('input',e=>{
    searchQuery = e.target.value || '';
    render();
});

$('lockerFilter').addEventListener('change',e=>{
    activeLocker = e.target.value || null;
    render();
});

loadData();

// Modal handling: open/close and submit
function openAddModal(){
    const m = $('addModal');
    m.style.display = 'flex';
    m.setAttribute('aria-hidden','false');
    $('addError').style.display='none';
    $('add_razao').value='';
    $('add_cnpj').value='';
    $('add_nota').value='';
    $('add_qtd').value='1';
    $('add_locker').value='';
}

function closeAddModal(){
    const m = $('addModal');
    m.style.display = 'none';
    m.setAttribute('aria-hidden','true');
}

document.getElementById('btn-new-record').addEventListener('click',openAddModal);
document.getElementById('addCancel').addEventListener('click',closeAddModal);

document.getElementById('addSubmit').addEventListener('click',async function(){
    const btn = this; btn.disabled = true;
    const errEl = $('addError'); errEl.style.display='none'; errEl.textContent='';
    const payload = new FormData();
    payload.append('action','create_manual');
    payload.append('razao_social',$('add_razao').value||'');
    payload.append('cnpj',$('add_cnpj').value||'');
    payload.append('nota_fiscal',$('add_nota').value||'');
    payload.append('quantidade',$('add_qtd').value||'1');
    payload.append('armario_id',$('add_locker').value||'');

    try{
        const res = await fetch('/router_public.php?url=inventario-api',{method:'POST',body:payload,credentials:'include'});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const j = await res.json();
        if(j.success){
            closeAddModal();
            await loadData();
        }else{
            errEl.textContent = j.message || 'Erro ao criar remessa';
            errEl.style.display='block';
        }
    }catch(err){
        errEl.textContent = err.message || err;
        errEl.style.display='block';
        console.error('create_manual error',err);
    }finally{ btn.disabled = false }
});

})();
</script>
</body>
</html>
