<?php
session_start();
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$tempo_limite = 1200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location:https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}
if (!isset($_SESSION['username'])) {
    header("Location:https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qualidade - KPI 2.0</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/qualidade.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/CnpjMask.js"></script>
</head>
<body>

<!-- OVERLAY DO PAINEL -->
<div class="panel-overlay" id="panelOverlay"></div>

<!-- CONTEÚDO PRINCIPAL -->
<div class="main-content">
    <div class="content-header">
        <div class="header-title">
            <i class="fas fa-clipboard-check"></i>
            <h1>Inspeção de Qualidade</h1>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-voltar" onclick="voltarComReload()">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
            <button type="button" class="btn-novo" id="btn-novo-registro">
                <i class="fas fa-plus"></i> Nova Inspeção
            </button>
        </div>
    </div>

    <!-- Seção de Tabelas -->
    <div class="table-section">
        <div class="table-controls">
            <div class="button-group-toggle">
                <button type="button" class="btn-toggle ativo" id="btn-aguardando-nf-retorno">
                    <i class="fas fa-clock"></i> Aguardando NF de Retorno
                </button>
                <button type="button" class="btn-toggle" id="btn-setor-qualidade">
                    <i class="fas fa-check-circle"></i> Em Inspeção
                </button>
            </div>
            <div class="filter-container">
                <i class="fas fa-search"></i>
                <input type="text" id="filtro-nf" placeholder="Pesquisar por NF entrada / retorno" class="filter-input">
            </div>
        </div>

        <!-- Tabela: Aguardando NF de Retorno -->
        <div class="table-wrapper" id="wrapper-aguardando-nf-retorno">
            <table id="tabela-info-aguardando-nf-retorno">
                <thead>
                    <tr>
                        <th><i class="fas fa-industry"></i> Setor</th>
                        <th><i class="fas fa-id-card"></i> CNPJ</th>
                        <th><i class="fas fa-building"></i> Razão Social</th>
                        <th><i class="fas fa-file-invoice"></i> NF</th>
                        <th><i class="fas fa-boxes"></i> Quantidade</th>
                        <th><i class="fas fa-box"></i> Qtd Parcial</th>
                        <th><i class="fas fa-tasks"></i> Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Tabela: Em Inspeção -->
        <div class="table-wrapper" id="wrapper-em-inspecao" style="display: none;">
            <table id="tabela-info-em-inspecao">
                <thead>
                    <tr>
                        <th><i class="fas fa-industry"></i> Setor</th>
                        <th><i class="fas fa-id-card"></i> CNPJ</th>
                        <th><i class="fas fa-building"></i> Razão Social</th>
                        <th><i class="fas fa-file-invoice"></i> NF</th>
                        <th><i class="fas fa-calendar-alt"></i> Data Início</th>
                        <th><i class="fas fa-boxes"></i> Quantidade</th>
                        <th><i class="fas fa-box"></i> Qtd Parcial</th>
                        <th><i class="fas fa-tasks"></i> Status</th>
                        <th><i class="fas fa-file-invoice-dollar"></i> NF Retorno</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- PAINEL LATERAL -->
<div class="side-panel" id="sidePanel">
    <div class="panel-header">
        <h2 id="panelTitle">Nova Inspeção</h2>
        <button type="button" class="btn-close-panel" id="btnClosePanel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="panel-content">
        <form id="form-qualidade">
            <div id="mensagemErro" class="error-message"></div>
            <div id="mensagemAlertaInline" class="alert-inline" style="display: none;"></div>

            <!-- SEÇÃO: Dados do Cliente -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-user-tie"></i>
                    <span>Dados do Cliente</span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cnpj">
                            <i class="fas fa-id-card"></i>
                            CNPJ
                        </label>
                        <input type="text" id="cnpj" name="cnpj" required 
                               oninput="applyCNPJMask(this);" maxlength="18" 
                               placeholder="Digite o CNPJ" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nota_fiscal">
                            <i class="fas fa-file-invoice"></i>
                            NF Entrada
                        </label>
                        <input type="text" id="nota_fiscal" name="nota_fiscal" required 
                               placeholder="Nota fiscal de entrada" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="razao_social">
                            <i class="fas fa-building"></i>
                            Razão Social
                        </label>
                        <input type="text" id="razao_social" name="razao_social" required 
                               placeholder="Razão Social do cliente" readonly>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="setor">
                            <i class="fas fa-industry"></i>
                            Setor
                        </label>
                        <select id="setor" name="setor" required disabled>
                            <option value="">Selecione o setor</option>
                            <option value="manut-varejo">Manutenção Varejo</option>
                            <option value="dev-varejo">Devolução Varejo</option>
                            <option value="manut-datora">Manutenção Datora</option>
                            <option value="manut-lumini">Manutenção Lumini</option>
                            <option value="dev-datora">Devolução Datora</option>
                            <option value="dev-lumini">Devolução Lumini</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Datas -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Datas</span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio_qualidade">
                            <i class="fas fa-calendar-check"></i>
                            Data Início Inspeção
                        </label>
                        <input type="date" id="data_inicio_qualidade" name="data_inicio_qualidade" required>
                    </div>
                    <div class="form-group">
                        <label for="data_envio_expedicao">
                            <i class="fas fa-calendar-plus"></i>
                            Data Envio p/ Expedição
                        </label>
                        <input type="date" id="data_envio_expedicao" name="data_envio_expedicao">
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Quantidades -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-boxes"></i>
                    <span>Quantidades</span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantidade">
                            <i class="fas fa-box"></i>
                            Quantidade Total
                        </label>
                        <input type="number" id="quantidade" name="quantidade" required 
                               placeholder="Quantidade total" readonly>
                    </div>
                    <div class="form-group">
                        <label for="quantidade_parcial">
                            <i class="fas fa-box-open"></i>
                            Quantidade Parcial
                        </label>
                        <input type="number" id="quantidade_parcial" name="quantidade_parcial" 
                               placeholder="Qtd parcial inspecionada" readonly>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Operações -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Operações</span>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="operacao_origem">
                            <i class="fas fa-sign-in-alt"></i>
                            Operação Origem
                        </label>
                        <select id="operacao_origem" name="operacao_origem" required>
                            <option value="">Selecione</option>
                            <option value="aguardando_NF_retorno">Aguardando NF de retorno</option>
                            <option value="inspecao_qualidade">Envio qualidade</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="operacao_destino">
                            <i class="fas fa-sign-out-alt"></i>
                            Operação Destino
                        </label>
                        <select id="operacao_destino" name="operacao_destino" required>
                            <option value="">Selecione</option>
                            <option value="inspecao_qualidade">Envio qualidade</option>
                            <option value="envio_expedicao">Enviado para expedição</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nota_fiscal_retorno">
                            <i class="fas fa-file-invoice-dollar"></i>
                            NF de Retorno
                        </label>
                        <input type="text" id="nota_fiscal_retorno" name="nota_fiscal_retorno" 
                               placeholder="Nota fiscal de retorno">
                    </div>
                    <div class="form-group">
                        <label for="operador">
                            <i class="fas fa-user"></i>
                            Operador
                        </label>
                        <input type="text" id="operador" name="operador" 
                               value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Observações -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-comment-alt"></i>
                    <span>Observações</span>
                </div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="obs">
                            <i class="fas fa-sticky-note"></i>
                            Observações
                        </label>
                        <textarea id="obs" name="obs" rows="4" placeholder="Observações gerais"></textarea>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-check"></i> Salvar
                </button>
                <button type="button" class="btn-cancel" id="btnCancelForm">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Alerta grande global -->
<div id="mensagemAlerta" class="big-alert hidden" role="alert" aria-live="assertive">
  <div class="big-alert-box" tabindex="-1">
    <button class="big-alert-close" aria-label="Fechar">&times;</button>
    <div class="big-alert-title">⚠️ Atenção</div>
    <div class="big-alert-message"></div>
    <div class="big-alert-actions">
      <button id="bigAlertConfirm">Confirmar</button>
      <button id="bigAlertCancel">Cancelar</button>
    </div>
  </div>
</div>


<script>
function voltarComReload() {
  window.top.location.href = "https://kpi.stbextrema.com.br/router_public.php?url=dashboard&reload=" + new Date().getTime();
}

let dadosAguardandoNFRetorno = [];
let dadosQualidade = [];

// ========== CONTROLE DO PAINEL ==========
function openPanelNew() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const form = document.getElementById('form-qualidade');
    const title = document.getElementById('panelTitle');
    
    form.reset();
    title.textContent = 'Nova Inspeção';
    panel.classList.remove('estado-editando');
    panel.classList.add('estado-novo');
    panel.classList.add('aberto');
    overlay.classList.add('ativo');
    
    // Limpar seleção de linhas
    document.querySelectorAll('.row-selected').forEach(row => {
        row.classList.remove('row-selected');
    });
}

function openPanelEdit() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const title = document.getElementById('panelTitle');
    
    title.textContent = 'Editando Inspeção';
    panel.classList.remove('estado-novo');
    panel.classList.add('estado-editando');
    panel.classList.add('aberto');
    overlay.classList.add('ativo');
}

function closePanel() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    
    panel.classList.remove('aberto');
    overlay.classList.remove('ativo');
    
    // Limpar seleção de linhas
    document.querySelectorAll('.row-selected').forEach(row => {
        row.classList.remove('row-selected');
    });
}

document.addEventListener("DOMContentLoaded", function () {
  // Inicializar máscara de CNPJ
  initializeCNPJMask();
  
  const form = document.getElementById("form-qualidade");
  if (!form) { console.error("form-qualidade não encontrado"); return; }

  const mensagemErro = document.getElementById("mensagemErro");
  const mensagemAlertaInline = document.getElementById("mensagemAlertaInline");

  const opOrigem = document.getElementById("operacao_origem");
  const opDestino = document.getElementById("operacao_destino");

  const btnAguardandoNfRetorno = document.getElementById('btn-aguardando-nf-retorno');
  const btnQualidade = document.getElementById('btn-setor-qualidade');
  const wrapperAguardando = document.getElementById('wrapper-aguardando-nf-retorno');
  const wrapperQualidade = document.getElementById('wrapper-em-inspecao');
  const tabelaAguardando = document.getElementById('tabela-info-aguardando-nf-retorno');
  const tabelaQualidade = document.getElementById('tabela-info-em-inspecao');

  // Botões do painel
  const btnNovo = document.getElementById('btn-novo-registro');
  const btnClosePanel = document.getElementById('btnClosePanel');
  const btnCancelForm = document.getElementById('btnCancelForm');
  const overlay = document.getElementById('panelOverlay');

  btnNovo.addEventListener('click', openPanelNew);
  btnClosePanel.addEventListener('click', closePanel);
  btnCancelForm.addEventListener('click', closePanel);
  overlay.addEventListener('click', closePanel);

  // ESC para fechar painel
  document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closePanel();
  });

  // ========= Utilitários de alerta (inline) =========
  function mostrarAlertaSubstituicaoInline(imeis = []) {
    const detalhe = (imeis && imeis.length)
      ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>`
      : "";
    if (mensagemAlertaInline) {
      mensagemAlertaInline.innerHTML = `⚠️ Existe equipamento substituído na remessa. Confira se está na caixa.${detalhe}`;
      mensagemAlertaInline.style.display = "block";
    } else {
      console.warn("Alerta inline não encontrado:", detalhe);
    }
  }
  
  function esconderAlertaSubstituicaoInline() {
    if (mensagemAlertaInline) {
      mensagemAlertaInline.style.display = "none";
      mensagemAlertaInline.innerHTML = "";
    }
  }

  // ========= Regra de origem/destino =========
  opOrigem.addEventListener("change", function () {
    if (opOrigem.value === "aguardando_NF_retorno") {
      opDestino.innerHTML = '';
      const opt = document.createElement("option");
      opt.value = "inspecao_qualidade";
      opt.textContent = "Envio qualidade";
      opDestino.appendChild(opt);
    } else {
      opDestino.innerHTML = `
        <option value="">Selecione</option>
        <option value="inspecao_qualidade">Envio qualidade</option>
        <option value="envio_expedicao">Enviado para expedição</option>
      `;
    }
  });

  // ========= showBigAlert robusto =========
  function showBigAlert({ title = "Atenção", message = "", detalheHtml = "", requireConfirm = true } = {}) {
    function ensureModalExists() {
      let wrapper = document.getElementById("mensagemAlerta");
      if (wrapper) return wrapper;

      wrapper = document.createElement("div");
      wrapper.id = "mensagemAlerta";
      wrapper.className = "big-alert hidden";
      wrapper.setAttribute("role", "alert");
      wrapper.setAttribute("aria-live", "assertive");
      wrapper.style.position = "fixed";
      wrapper.style.inset = "0";
      wrapper.style.display = "none";
      wrapper.style.alignItems = "center";
      wrapper.style.justifyContent = "center";
      wrapper.style.background = "rgba(0,0,0,0.55)";
      wrapper.style.zIndex = "99999";
      wrapper.style.padding = "20px";

      wrapper.innerHTML = `
        <div class="big-alert-box" tabindex="-1" role="dialog" aria-modal="true"
             style="background:#fff;border:6px solid #ff4d4d;color:#8b0000;width:min(980px,95%);border-radius:12px;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,0.4);text-align:center;outline:none;position:relative;">
          <button class="big-alert-close" aria-label="Fechar" style="position:absolute;right:12px;top:8px;background:transparent;border:none;font-size:28px;color:#8b0000;cursor:pointer;">&times;</button>
          <div class="big-alert-title" style="font-size:34px;font-weight:800;margin-bottom:8px;">Atenção</div>
          <div class="big-alert-message" style="font-size:20px;font-weight:600;line-height:1.3;margin-top:6px;max-height:36vh;overflow:auto;"></div>
          <div class="big-alert-actions" style="margin-top:18px;display:flex;gap:12px;justify-content:center;">
            <button id="bigAlertConfirm" style="padding:12px 20px;font-size:18px;border-radius:8px;border:2px solid #8b0000;background:#fff;color:#8b0000;cursor:pointer;min-width:140px;">Confirmar</button>
            <button id="bigAlertCancel" style="padding:12px 20px;font-size:18px;border-radius:8px;border:2px solid #8b0000;background:#fff;color:#8b0000;cursor:pointer;min-width:140px;">Cancelar</button>
          </div>
        </div>
      `;
      document.body.appendChild(wrapper);
      return wrapper;
    }

    return new Promise((resolve) => {
      try {
        const wrapper = ensureModalExists();
        const box = wrapper.querySelector(".big-alert-box") || wrapper;
        const titleEl = wrapper.querySelector(".big-alert-title");
        const messageEl = wrapper.querySelector(".big-alert-message");
        const confirmBtn = wrapper.querySelector("#bigAlertConfirm");
        const cancelBtn = wrapper.querySelector("#bigAlertCancel");
        const closeBtn = wrapper.querySelector(".big-alert-close");

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.innerHTML = `<div style="font-weight:700">${String(message)}</div>${detalheHtml || ''}`;

        wrapper.style.display = "flex";
        wrapper.classList.remove("hidden");
        if (box && typeof box.focus === "function") box.focus();

        function cleanup(result) {
          try { wrapper.style.display = "none"; } catch (_) {}
          if (confirmBtn) confirmBtn.removeEventListener("click", onConfirm);
          if (cancelBtn) cancelBtn.removeEventListener("click", onCancel);
          if (closeBtn) closeBtn.removeEventListener("click", onCancel);
          document.removeEventListener("keydown", onKey);
          resolve(Boolean(result));
        }
        function onConfirm() { cleanup(true); }
        function onCancel() { cleanup(false); }
        function onKey(e) {
          if (e.key === "Escape") onCancel();
          if (e.key === "Enter" && requireConfirm) onConfirm();
        }

        if (confirmBtn) confirmBtn.addEventListener("click", onConfirm);
        if (cancelBtn) cancelBtn.addEventListener("click", onCancel);
        if (closeBtn) closeBtn.addEventListener("click", onCancel);
        document.addEventListener("keydown", onKey);

      } catch (err) {
        console.error("showBigAlert fallback (confirm):", err);
        const fallback = window.confirm(message + (detalheHtml ? "\n\n" + detalheHtml.replace(/<[^>]*>?/gm, '') : ''));
        resolve(Boolean(fallback));
      }
    });
  }

  function showError(msg) {
    console.error(msg);
    if (mensagemErro) mensagemErro.innerText = msg;
  }

  // ========= SUBMIT (pré-check -> confirmar -> salvar) =========
  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    if (mensagemErro) mensagemErro.innerText = "";

    const formData = new FormData(this);
    const checkData = new FormData(this);
    checkData.set("only_check", "1");

    try {
      // PRE-CHECK
      let res = await fetch("https://kpi.stbextrema.com.br/BackEnd/Qualidade/Qualidade.php", {
        method: "POST",
        body: checkData
      });
      const text = await res.text();
      console.log("PRECHECK RAW:", text);

      if (!res.ok) {
        showError("Falha no servidor (pré-check): " + res.status);
        return;
      }

      let jsonCheck;
      try { jsonCheck = JSON.parse(text); } catch (err) {
        console.error("JSON inválido (pré-check):", err, text);
        showError("Resposta inválida do servidor (pré-check).");
        return;
      }

      // Se substituição detectada: mostrar modal grande para confirmar
      if (jsonCheck.substitution && jsonCheck.substitution.checked) {
        if (jsonCheck.substitution.has_substitution) {
          const imeis = Array.isArray(jsonCheck.substitution.imeis) ? jsonCheck.substitution.imeis : [];
          const detalhe = imeis.length ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>` : "";

          mostrarAlertaSubstituicaoInline(imeis);

          const confirmado = await showBigAlert({
            title: "Existe equipamento substituído na remessa",
            message: "Existe equipamento substituído na remessa. Confira se está na caixa.",
            detalheHtml: detalhe,
            requireConfirm: true
          });
          if (!confirmado) {
            return;
          }
        } else {
          esconderAlertaSubstituicaoInline();
        }
      }

      // SAVE
      let resSave = await fetch("https://kpi.stbextrema.com.br/BackEnd/Qualidade/Qualidade.php", {
        method: "POST",
        body: formData
      });
      let textSave = await resSave.text();
      console.log("SAVE RAW:", textSave);

      if (!resSave.ok) {
        showError("Falha no servidor: " + resSave.status);
        return;
      }

      let jsonSave;
      try { jsonSave = JSON.parse(textSave); } catch (err) {
        console.error("JSON inválido (save):", err, textSave);
        showError("Resposta inválida do servidor.");
        return;
      }

      if (jsonSave.substitution && jsonSave.substitution.checked) {
        if (jsonSave.substitution.has_substitution) {
          const imeis = Array.isArray(jsonSave.substitution.imeis) ? jsonSave.substitution.imeis : [];
          const detalhe = imeis.length ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>` : "";
          mostrarAlertaSubstituicaoInline(imeis);

          await showBigAlert({
            title: "Atenção — Substituição detectada",
            message: "Existe equipamento substituído na remessa. Confira se está na caixa.",
            detalheHtml: detalhe,
            requireConfirm: false
          });
        } else {
          esconderAlertaSubstituicaoInline();
        }
      }

      if (jsonSave.success && jsonSave.redirect) {
        window.location.href = jsonSave.redirect;
        return;
      } else if (jsonSave.error) {
        showError(jsonSave.error);
        return;
      }

      if (jsonSave.success) {
        try { form.reset(); } catch (_) {}
        if (mensagemErro) mensagemErro.innerText = "";
        closePanel();
        // Recarregar tabela ativa
        if (btnAguardandoNfRetorno.classList.contains('ativo')) {
          btnAguardandoNfRetorno.click();
        } else {
          btnQualidade.click();
        }
      }

    } catch (error) {
      console.error("Erro geral na requisição:", error);
      showError("Erro na comunicação com o servidor.");
    }
  });

  // ========= Tabelas / preenchimento =========
  function mostrarTabela(wrapper) {
    wrapperAguardando.style.display = 'none';
    wrapperQualidade.style.display = 'none';
    wrapper.style.display = 'block';
    tabelaAguardando.querySelector('tbody').innerHTML = '';
    tabelaQualidade.querySelector('tbody').innerHTML = '';
  }

  function preencherInputs(item, tipo, row) {
    document.querySelector('#cnpj').value = item.cnpj || '';
    document.querySelector('#razao_social').value = item.razao_social || '';
    document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
    document.querySelector("#setor").value = item.setor || '';
    document.querySelector('#quantidade').value = item.quantidade || '';
    document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';

    if (tipo === "aguardando") {
      document.querySelector('#operacao_origem').value = item.operacao_destino || '';
    } else if (tipo === "qualidade") {
      document.querySelector('#data_inicio_qualidade').value = item.data_inicio_qualidade || '';
      document.querySelector('#quantidade').value = item.quantidade || '';
      document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';
      document.querySelector('#operacao_origem').value = item.operacao_destino || '';
      document.querySelector('#nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';
    }

    // Highlight da linha
    document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
    if (row) row.classList.add('row-selected');
    
    openPanelEdit();
  }

  function preencherTabelaAguardando(dados) {
    mostrarTabela(wrapperAguardando);
    const tbody = tabelaAguardando.querySelector('tbody');
    dados.forEach(item => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${item.setor || ''}</td>
        <td>${item.cnpj || ''}</td>
        <td>${item.razao_social || ''}</td>
        <td>${item.nota_fiscal || ''}</td>
        <td>${item.quantidade || ''}</td>
        <td>${item.quantidade_parcial || ''}</td>
        <td>${item.operacao_destino || ''}</td>
      `;
      row.addEventListener('click', () => preencherInputs(item, "aguardando", row));
      tbody.appendChild(row);
    });
  }

  function preencherTabelaQualidade(dados) {
    mostrarTabela(wrapperQualidade);
    const tbody = tabelaQualidade.querySelector('tbody');
    dados.forEach(item => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${item.setor || ''}</td>
        <td>${item.cnpj || ''}</td>
        <td>${item.razao_social || ''}</td>
        <td>${item.nota_fiscal || ''}</td>
        <td>${item.data_inicio_qualidade || ''}</td>
        <td>${item.quantidade || ''}</td>
        <td>${item.quantidade_parcial || ''}</td>
        <td>${item.operacao_destino || ''}</td>
        <td>${item.nota_fiscal_retorno || ''}</td>
      `;
      row.addEventListener('click', () => preencherInputs(item, "qualidade", row));
      tbody.appendChild(row);
    });
  }

  function destacarBotao(btn) {
    btnAguardandoNfRetorno.classList.remove("ativo");
    btnQualidade.classList.remove("ativo");
    btn.classList.add("ativo");
  }

  function filtrarAguardando(listaAguardando, listaQualidade) {
    const chavesQualidade = new Set(listaQualidade.map(item => `${item.cnpj}-${item.nota_fiscal}`));
    return listaAguardando.filter(item => {
      const chave = `${item.cnpj}-${item.nota_fiscal}`;
      return !chavesQualidade.has(chave);
    });
  }

  // Eventos para os botões (busca dados)
  btnAguardandoNfRetorno.addEventListener('click', () => {
    destacarBotao(btnAguardandoNfRetorno);
    fetch("https://kpi.stbextrema.com.br/BackEnd/Qualidade/consulta_qualidade.php")
      .then(res => res.json())
      .then(qualidade => {
        dadosQualidade = qualidade;
        fetch("https://kpi.stbextrema.com.br/BackEnd/Qualidade/consulta_aguardando_nf.php")
          .then(res => res.json())
          .then(aguardandoNF => {
            dadosAguardandoNFRetorno = aguardandoNF;
            const filtrados = filtrarAguardando(dadosAguardandoNFRetorno, dadosQualidade);
            preencherTabelaAguardando(filtrados);
          });
      });
  });

  btnQualidade.addEventListener('click', () => {
    destacarBotao(btnQualidade);
    fetch("https://kpi.stbextrema.com.br/BackEnd/Qualidade/consulta_qualidade.php")
      .then(res => res.json())
      .then(dados => {
        dadosQualidade = dados;
        preencherTabelaQualidade(dados);
      });
  });

  // Inicializa com "Aguardando NF de retorno" visível
  btnAguardandoNfRetorno.click();

  // Filtro por NF entrada / NF retorno (exato)
  document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();
    if (!/^[\w\s\-./]*$/.test(termo)) return;

    const tabelas = [tabelaAguardando, tabelaQualidade];

    tabelas.forEach(tabela => {
      const linhas = tabela.querySelectorAll("tbody tr");
      linhas.forEach(linha => {
        const notaFiscal = linha.cells[3]?.textContent.trim().toLowerCase() || '';
        const notaFiscalRetorno = linha.cells[8]?.textContent.trim().toLowerCase() || '';
        linha.style.display = (notaFiscal === termo || notaFiscalRetorno === termo) ? "" : "none";
      });
    });
  });

}); // DOMContentLoaded
</script>

</body>
</html>