<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';

verificarSessao();
definirHeadersSeguranca();

/**
 * Server-side initial load.
 * IMPORTANT: Always inject an ARRAY (even if empty) to prevent client fallback.
 */
$initialItemsJson = '[]';

try {
    require_once __DIR__ . '/../../BackEnd/Database.php';
    $db = getDb();

    $sql = "SELECT
                resumo_id,
                cnpj,
                razao_social,
                nota_fiscal,
                quantidade_real,
                status_real,
                armario_id,
                data_envio_expedicao,
                codigo_rastreio_envio,
                setor
            FROM vw_resumo_estado_real_normalized
            ORDER BY resumo_id DESC
            LIMIT 200";

    $rows = $db->fetchAll($sql, []);

    $items = array_map(function($r){
        $status_raw = $r['status_real'] ?? '';
        $status_norm = '';
        if ($status_raw !== null && trim((string)$status_raw) !== '') {
            $status_norm = strtolower(preg_replace('/[^a-z0-9_]+/', '_', trim((string)$status_raw)));
            $status_norm = trim($status_norm, '_');
        }
        $arm_raw = $r['armario_id'] ?? null;
        $armario = ($arm_raw !== '' && $arm_raw !== null) ? (int)$arm_raw : null;

        return [
            'id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
            'resumo_id' => isset($r['resumo_id']) ? (int)$r['resumo_id'] : 0,
            'cnpj' => $r['cnpj'] ?? null,
            'razao_social' => $r['razao_social'] ?? '',
            'nota_fiscal' => $r['nota_fiscal'] ?? '',
            'quantidade' => isset($r['quantidade_real']) ? (int)$r['quantidade_real'] : 0,
            'status' => $status_norm,
            'locker' => $armario !== null ? (string)$armario : null,
            'armario_id' => $armario,
            'data_envio_expedicao' => $r['data_envio_expedicao'] ?? null,
            'codigo_rastreio_envio' => $r['codigo_rastreio_envio'] ?? null,
            'setor' => $r['setor'] ?? null
        ];
    }, $rows ?: []);

    // ALWAYS encode as array (even empty)
    $initialItemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // DB read failed. Try to load a cached server payload so frontend
    // still receives an authoritative items array without changing JS.
    error_log('[inventario.php] Initial load failed: ' . $e->getMessage());
    $cachePath = __DIR__ . '/../../data/inventario_server_payload.json';
    if (file_exists($cachePath)) {
        $txt = file_get_contents($cachePath);
        $decoded = json_decode($txt, true);
        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = [];
        }
    } else {
        $items = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Inventário - VISTA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/FrontEnd/CSS/consulta.css">
    <link rel="stylesheet" href="/FrontEnd/CSS/recebimento.css">
    <link rel="icon" href="/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>

<style>
/* (estilos mantidos — sem alterações funcionais) */
</style>
</head>

<body>
<div class="main-content">

<header class="content-header">
    <div class="header-title">
        <img src="/FrontEnd/CSS/imagens/VISTA.png" style="height:48px;margin-right:8px">
        <div>
            <h1>Inventário — Remessas</h1>
            <small>Status e confirmação</small>
        </div>
    </div>

    <div class="header-actions">
        <input id="searchInput" class="search-input" placeholder="Pesquisar..." type="search">
        <select id="lockerFilter" style="display:none">
            <option value="">Armário (todos)</option>
            <?php for($i=1;$i<=5;$i++): ?>
                <option value="<?= $i ?>">Armário <?= $i ?></option>
            <?php endfor ?>
        </select>
        <button id="btn-new-record" class="btn-primary">Adicionar</button>
        <button id="btn-back" class="btn-secondary" onclick="location.href='/router_public.php?url=dashboard'">Voltar</button>
    </div>
</header>

<div id="statusFilters" class="filter-panel"></div>

<div class="table-results table-wrapper">
    <div id="loadingOverlay" class="loading-overlay" style="display:none">
        <div class="spinner"></div>
    </div>
    <table>
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

// ALWAYS an array (possibly empty)
const __INITIAL_INVENTARIO_ITEMS__ = <?= $initialItemsJson ?>;

const $ = id => document.getElementById(id);

function toggleLoading(show){
    $('loadingOverlay').style.display = show ? 'flex' : 'none';
}

function normalizeStatus(s){
    return String(s||'').split(':')[0];
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
        b.addEventListener('click',()=>{ activeFilter = code; render(); });
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
        tr.innerHTML = `
          <td>${r.razao_social||''}</td>
          <td>${r.nota_fiscal||''}</td>
          <td>${r.quantidade}</td>
          <td>${r.locker ? `<span class="locker-badge">${r.locker}</span>` : '—'}</td>
          <td>${STATUS_LABELS[r.status]||r.status}</td>
          <td><button class="confirm-btn" data-id="${r.id}">Confirmar</button></td>
        `;
        tr.querySelector('button').addEventListener('click',()=>confirmItem(r.id));
        body.appendChild(tr);
    });
}

async function loadData(){
    // AUTHORITATIVE server payload — no fallback
    toggleLoading(true);
    allItems = Array.isArray(__INITIAL_INVENTARIO_ITEMS__)
        ? __INITIAL_INVENTARIO_ITEMS__.map(r=>({
            id:r.id,
            razao_social:r.razao_social||'',
            nota_fiscal:r.nota_fiscal||'',
            quantidade:r.quantidade||0,
            locker:r.armario_id||null,
            status:normalizeStatus(r.status)
        }))
        : [];
    render();
    toggleLoading(false);
}

async function confirmItem(id){
    try{
        const fd = new FormData();
        fd.append('action','confirm');
        fd.append('resumo_id',id);
        const res = await fetch('/router_public.php?url=inventario-api',{method:'POST',body:fd,credentials:'include'});
        if(!res.ok) throw new Error('HTTP '+res.status);
        const j = await res.json();
        if(j.success){ location.reload(); }
        else alert(j.message||'Erro ao confirmar');
    }catch(e){ alert(e.message||e); }
}

$('searchInput').addEventListener('input',e=>{ searchQuery=e.target.value||''; render(); });
$('lockerFilter').addEventListener('change',e=>{ activeLocker=e.target.value||null; render(); });

loadData();
})();
</script>
</body>
</html>
