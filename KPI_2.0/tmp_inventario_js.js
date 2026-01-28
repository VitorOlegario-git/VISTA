
const STATUS_ORDER = [
    'aguardando_nf_retorno','aguardando_nf','aguardando_pg','descarte','em_analise','em_reparo','envio_analise','envio_cliente','envio_expedicao','estocado','inspecao_qualidade'
];

const STATUS_LABELS = {
    'aguardando_nf':'Aguardando NF',
    'aguardando_nf_retorno':'Aguardando NF (retorno)',
    'aguardando_pg':'Aguardando PG',
    'descarte':'Descarte',
    'em_analise':'Em An├ílise',
    'em_reparo':'Em Reparo',
    'envio_analise':'Enviado p/ An├ílise',
    'envio_cliente':'Enviado p/ Cliente',
    'envio_expedicao':'Expedi├º├úo',
    'estocado':'Estocado',
    'inspecao_qualidade':'Inspe├º├úo Qualidade'
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
            <td data-label="Raz├úo Social">${escapeHtml(row.razao_social||'ÔÇö')}</td>
            <td data-label="Nota Fiscal">${escapeHtml(row.nota_fiscal||'ÔÇö')}</td>
            <td data-label="Quantidade">${row.quantidade ?? row.qtd ?? 'ÔÇö'}</td>
            <td data-label="Arm├írio">${row.locker ? `<span class="locker-badge">${escapeHtml(row.locker)}</span>` : 'ÔÇö'}</td>
            <td data-label="Status">${STATUS_LABELS[row.status]||row.status}</td>
            <td data-label="Confirmar"><div style="display:flex;gap:8px;align-items:center">${activeFilter==='aguardando_pg'?`<select data-id="${row.id}" class="locker-select" style="min-width:84px">
                <option value="">ÔÇö</option>
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
        if(!res.ok) throw new Error('API n├úo dispon├¡vel');
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
        console.error('Inventario API n├úo respondeu:', e.message);
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
            // Refresh authoritative data from API to avoid UI/DB mismatch
            try{ await loadData(); }catch(e){ /* best-effort refresh */ }
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
            console.error('Falha ao atribuir arm├írio');
        }
    } catch(e){ console.error('Erro ao atribuir arm├írio', e); }
}

// Side-panel controls (reused pattern)
function openPanelNew(){
    document.getElementById('side-panel').classList.add('open');
    document.getElementById('panel-overlay').classList.add('active');
    document.getElementById('side-panel').setAttribute('aria-hidden','false');
    document.getElementById('panel-title').textContent = 'Cadastrar Remessa';
    document.getElementById('panel-icon').className = 'fas fa-plus-circle';
}
function closePanel(){
    document.getElementById('side-panel').classList.remove('open');
    document.getElementById('panel-overlay').classList.remove('active');
    document.getElementById('side-panel').setAttribute('aria-hidden','true');
}

const btnNewRecord = document.getElementById('btn-new-record');
if(btnNewRecord) btnNewRecord.addEventListener('click', (e)=>{ e.preventDefault(); openPanelNew(); });
const btnClosePanel = document.getElementById('btn-close-panel');
if(btnClosePanel) btnClosePanel.addEventListener('click', (e)=>{ e.preventDefault(); closePanel(); });
const panelOverlay = document.getElementById('panel-overlay');
if(panelOverlay) panelOverlay.addEventListener('click', ()=> closePanel());

document.getElementById('cancelAdd').addEventListener('click', (e)=>{ e.preventDefault(); closePanel(); });
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
    if(!razao || !nf){ alert('Raz├úo social e nota fiscal s├úo obrigat├│rios'); return; }
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
        if(j && j.success && j.item){ allItems.unshift(j.item); counts[j.item.status] = (counts[j.item.status]||0)+1; renderFilters(counts); renderTable(); closePanel(); }
        else alert('Erro ao criar remessa');
    }catch(e){ console.error(e); alert('Erro ao criar remessa'); }
});

// Close side-panel on ESC
document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape'){ closePanel(); } });

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
                    tr.innerHTML = `\n            <td data-label="Raz├úo Social">${escapeHtml(row.razao_social||'ÔÇö')}</td>\n            <td data-label="Nota Fiscal">${escapeHtml(row.nota_fiscal||'ÔÇö')}</td>\n            <td data-label="Quantidade">${row.quantidade ?? row.qtd ?? 'ÔÇö'}</td>\n            <td data-label="Status">${STATUS_LABELS[row.status]||row.status}</td>\n            <td data-label="Confirmar"><button class="confirm-btn" onclick="confirmItem(${row.id || 0}, this)">Confirmar</button></td>\n        `;
                    tbody.appendChild(tr);
                });
            }, 250);
        });
    }

    // Inicializa m├íscara e busca autom├ítica de raz├úo social por CNPJ
    const addCnpj = document.getElementById('add_cnpj');
    if(addCnpj){
        // Aplicar m├íscara enquanto digita
        addCnpj.addEventListener('input', function(){ applyCNPJMask(this); });

        // Ao perder foco, se CNPJ estiver completo, procurar cliente
        addCnpj.addEventListener('blur', async ()=>{
            const cnpjVal = addCnpj.value.trim();
            if(cnpjVal.length !== 18) return; // CNPJ formatado tem 18 caracteres
            try{
                const res = await fetch('/BackEnd/buscar_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'cnpj=' + encodeURIComponent(cnpjVal)
                });
                const json = await res.json();
                if(json && json.encontrado){
                    const razaoEl = document.getElementById('add_razao');
                    if(razaoEl) razaoEl.value = json.razao_social || '';
                }
            }catch(err){ console.error('Erro ao buscar cliente por CNPJ:', err); }
        });
    }

    loadData();
});

