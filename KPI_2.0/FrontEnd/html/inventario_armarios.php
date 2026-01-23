<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Seleção de Armários - Inventário</title>
  <style>
    :root{--bg:#f5f7fa;--card:#ffffff;--muted:#6b7280;--accent:#0f62fe;--danger:#d93025;--success:#0b8457}
    body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#111;margin:0;padding:24px}
    .wrap{max-width:1100px;margin:0 auto}
    header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
    h1{font-size:20px;margin:0}
    .sub{color:var(--muted);font-size:13px}
    .notice{background:#fff3cd;border-left:4px solid #ffd54a;padding:12px;border-radius:6px;margin-bottom:12px}

    /* States container */
    .state{background:var(--card);padding:18px;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.04);}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-top:12px}
    .card{background:var(--card);padding:14px;border-radius:8px;border:1px solid #e6e9ef;display:flex;flex-direction:column;gap:10px;cursor:pointer;transition:transform .08s,border-color .12s}
    .card:focus{outline:3px solid rgba(15,98,254,.12);transform:translateY(-2px)}
    .card:hover{transform:translateY(-2px)}
    .card .title{font-weight:600}
    .card .desc{color:var(--muted);font-size:13px}
    .meta{display:flex;align-items:center;justify-content:space-between;gap:8px}
    .badge{display:inline-block;padding:6px 8px;border-radius:999px;font-weight:700;font-size:13px;color:#fff}
    .badge.empty{background:#9aa3b2}
    .badge.warn{background:var(--danger)}
    .start{background:var(--accent);color:#fff;padding:8px 10px;border-radius:6px;text-decoration:none;font-weight:600;font-size:13px}

    .helper{color:var(--muted);font-size:13px}
    .error{color:var(--danger);font-weight:600}
    .empty-state{color:var(--muted);padding:28px;text-align:center}
    .spinner{width:36px;height:36px;border-radius:50%;border:4px solid rgba(0,0,0,.08);border-top-color:var(--accent);animation:spin .9s linear infinite;margin:12px auto}
    @keyframes spin{to{transform:rotate(360deg)}}

    @media (max-width:520px){body{padding:12px}.grid{gap:10px}}
  </style>
</head>
<body>
  <main class="wrap">
    <header>
      <div>
        <h1>Seleção de Armários</h1>
        <div class="sub">Escolha o armário para iniciar o inventário do ciclo ativo</div>
      </div>
      <div class="sub" id="ciclo-label">—</div>
    </header>

    <!-- espaço para alertas inline (ciclo anterior não encerrado, erros, sessão expirada) -->
    <div id="alerts"></div>

    <section id="content" class="state">
      <!-- estados: loading / error / empty / list -->
      <div id="loading" class="state-view">
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="spinner" aria-hidden="true"></div>
          <div class="helper">Carregando armários e ciclo ativo…</div>
        </div>
      </div>

      <div id="error" class="state-view" style="display:none">
        <div class="error" id="error-msg">Erro ao carregar os dados.</div>
        <div class="helper" id="error-help" style="margin-top:8px">Tente novamente mais tarde ou contate o suporte.</div>
      </div>

      <div id="empty" class="state-view" style="display:none">
        <div class="empty-state">
          <div style="font-weight:700;margin-bottom:6px">Nenhum armário disponível</div>
          <div class="helper">Verifique se já existe um ciclo ativo. Você pode criar um ciclo antes de iniciar inventários.</div>
        </div>
      </div>

      <div id="list" class="state-view" style="display:none">
        <div class="grid" id="armarios-grid" role="list"></div>
      </div>
    </section>
  </main>

  <script>
  // inventario_armarios.php
  // JS responsável por consumir o endpoint /router_public.php?url=inventario/armarios-api
  // e renderizar estados de loading / erro / vazio / sucesso.

  (function(){
    const API = '/router_public.php?url=inventario/armarios-api';
    const alerts = document.getElementById('alerts');
    const cicloLabel = document.getElementById('ciclo-label');
    const elLoading = document.getElementById('loading');
    const elError = document.getElementById('error');
    const elEmpty = document.getElementById('empty');
    const elList = document.getElementById('list');
    const grid = document.getElementById('armarios-grid');

    // helpers para trocar estados
    function showState(name){
      elLoading.style.display = (name==='loading') ? '' : 'none';
      elError.style.display = (name==='error') ? '' : 'none';
      elEmpty.style.display = (name==='empty') ? '' : 'none';
      elList.style.display = (name==='list') ? '' : 'none';
    }

    function clearAlerts(){ alerts.innerHTML = ''; }
    function pushAlert(html){ const d=document.createElement('div'); d.innerHTML=html; alerts.appendChild(d); }

    // trata resposta de sucesso=false retornada pelo backend
    function handleApiErrorPayload(payload){
      const msg = payload && payload.message ? payload.message : 'Erro não identificado do servidor.';
      document.getElementById('error-msg').textContent = msg;
      showState('error');
    }

    // trata 401
    function renderSessionExpired(){
      clearAlerts();
      pushAlert('<div class="notice">Sua sessão expirou. <a href="/FrontEnd/tela_login.php">Entrar novamente</a></div>');
      showState('error');
      document.getElementById('error-msg').textContent = 'Sessão expirada. Faça login para continuar.';
      document.getElementById('error-help').textContent = '';
    }

    // monta cada card de armário
    function buildCard(a){
      const card = document.createElement('div');
      card.className = 'card';
      card.setAttribute('role','button');
      card.setAttribute('tabindex','0');
      card.dataset.armarioId = a.id;

      const title = document.createElement('div'); title.className='title'; title.textContent = a.codigo || ('Armário ' + a.id);
      const desc = document.createElement('div'); desc.className='desc'; desc.textContent = a.descricao || '';

      const meta = document.createElement('div'); meta.className='meta';

      const pend = document.createElement('div');
      const pendentes = Number(a.pendentes) || 0;
      const badge = document.createElement('span');
      badge.className = 'badge ' + (pendentes>0 ? 'warn' : 'empty');
      badge.textContent = pendentes + ' pend.';
      pend.appendChild(badge);

      const startLink = document.createElement('a');
      startLink.className = 'start';
      startLink.href = '/router_public.php?url=inventario/iniciar&armario_id=' + encodeURIComponent(a.id);
      startLink.textContent = 'Iniciar Inventário';
      startLink.setAttribute('aria-label','Iniciar inventário do armário ' + (a.codigo || a.id));

      meta.appendChild(pend);
      meta.appendChild(startLink);

      // destaque visual quando há pendências
      if(pendentes>0){ card.style.borderLeft = '4px solid '+getComputedStyle(document.documentElement).getPropertyValue('--danger') || '#d93025'; }

      card.appendChild(title);
      card.appendChild(desc);
      card.appendChild(meta);

      // clique no card também inicia (mesma ação do botão)
      card.addEventListener('click', function(e){
        // se o clique foi no link, deixamos navegar naturalmente
        if(e.target.tagName.toLowerCase()==='a') return;
        window.location.href = startLink.href;
      });

      // suporte teclado (Enter / Space)
      card.addEventListener('keydown', function(e){
        if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); window.location.href = startLink.href; }
      });

      return card;
    }

    // renderiza lista completa
    function renderList(payload){
      clearAlerts();
      const ciclo = payload.ciclo || null;
      const prev = payload.prev_open || null;

      // mostrar ciclo no header
      if(ciclo){
        cicloLabel.textContent = ciclo.mes_ano + ' • Aberto em ' + (ciclo.aberto_at || '—');
      } else { cicloLabel.textContent = 'Sem ciclo ativo'; }

      // se existir ciclo anterior aberto mostrar aviso inline
      if(prev){
        pushAlert('<div class="notice">Existe ciclo anterior não encerrado: <strong>'+escapeHtml(prev.mes_ano)+'</strong>. Por favor encerre-o antes de prosseguir.</div>');
      }

      // se não há ciclo ativo, bloquear tela e orientar criação
      if(!ciclo){
        pushAlert('<div class="notice">Não existe ciclo ativo. Crie um novo ciclo para iniciar inventários. <a href="/router_public.php?url=inventario/ciclos">Gerenciar ciclos</a></div>');
        showState('empty');
        return;
      }

      // montar os cards
      grid.innerHTML = '';
      const list = Array.isArray(payload.armarios) ? payload.armarios : [];
      if(list.length === 0){ showState('empty'); return; }

      list.forEach(a => {
        const c = buildCard(a);
        grid.appendChild(c);
      });

      showState('list');
    }

    // pequena sanitização para inclusão em HTML de alertas
    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];}); }

    // Fetch com tratamento robusto de status e payload
    function load(){
      showState('loading');
      clearAlerts();

      fetch(API, {credentials:'same-origin',cache:'no-store'})
      .then(function(resp){
        if(resp.status === 401){ renderSessionExpired(); throw new Error('unauth'); }
        // esperamos JSON de contrato; tratar códigos não-200 como erro
        if(!resp.ok){
          // tentativa de ler payload para mensagem amigável
          return resp.json().then(p => { handleApiErrorPayload(p); throw new Error('api-error'); }).catch(()=>{ document.getElementById('error-msg').textContent = 'Erro de rede ao consultar a API.'; showState('error'); throw new Error('network'); });
        }
        return resp.json();
      })
      .then(function(payload){
        if(!payload){ document.getElementById('error-msg').textContent = 'Resposta inválida do servidor.'; showState('error'); return; }
        if(payload.success !== true){ handleApiErrorPayload(payload); return; }
        renderList(payload);
      })
      .catch(function(){ /* erros já mapeados acima mostram UI adequada; evitar mensagens duplicadas */ });
    }

    // inicia ao carregar a página
    document.addEventListener('DOMContentLoaded', function(){ load(); });
  })();
  </script>
</body>
</html>
