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
    <div class="big-alert-title"><i class="fas fa-exclamation-triangle big-alert-icon"></i> Atenção</div>
    <div class="big-alert-message"></div>
    <div class="big-alert-actions">
      <button id="bigAlertConfirm">Confirmar</button>
      <button id="bigAlertCancel">Cancelar</button>
    </div>
  </div>
</div>


<script>
// =====================================================
// CONFIGURAÇÕES GLOBAIS
// =====================================================
const BASE_ROUTER_URL = '/router_public.php?url=';

function redirectTo(route, params = "") {
    window.top.location.href = BASE_ROUTER_URL + route + params;
}

function voltarComReload() {
    redirectTo("dashboard", "&reload=" + new Date().getTime());
}

// =====================================================
// ESTADO
// =====================================================
let dadosAguardandoNFRetorno = [];
let dadosQualidade = [];

// =====================================================
// CONTROLE DO PAINEL
// =====================================================
function openPanelNew() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const form = document.getElementById('form-qualidade');
    const title = document.getElementById('panelTitle');

    form.reset();
    title.textContent = 'Nova Inspeção';

    panel.classList.remove('edit-mode');
    panel.classList.add('open');
    overlay.classList.add('active');

    // Habilitar campos que podem estar desabilitados para garantir submissão
    try { document.getElementById('setor').disabled = false; } catch(e){}

    document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
}

function openPanelEdit() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const title = document.getElementById('panelTitle');

    title.textContent = 'Editando Inspeção';

    panel.classList.remove('open');
    panel.classList.add('edit-mode', 'open');
    overlay.classList.add('active');

    // Habilitar campos que podem estar desabilitados para garantir submissão
    try { document.getElementById('setor').disabled = false; } catch(e){}
}

function closePanel() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');

    panel.classList.remove('open', 'edit-mode');
    overlay.classList.remove('active');

    document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================
document.addEventListener("DOMContentLoaded", () => {

    initializeCNPJMask();

    const form = document.getElementById("form-qualidade");
    const mensagemErro = document.getElementById("mensagemErro");
    const mensagemAlertaInline = document.getElementById("mensagemAlertaInline");

    function showFormError(msg) {
        if (mensagemErro) {
            mensagemErro.textContent = msg || '';
            mensagemErro.style.display = msg ? 'block' : 'none';
        }
    }

    function hideFormError() {
        if (mensagemErro) {
            mensagemErro.textContent = '';
            mensagemErro.style.display = 'none';
        }
    }

    const btnAguardando = document.getElementById('btn-aguardando-nf-retorno');
    const btnQualidade = document.getElementById('btn-setor-qualidade');

    const wrapperAguardando = document.getElementById('wrapper-aguardando-nf-retorno');
    const wrapperQualidade = document.getElementById('wrapper-em-inspecao');

    const tabelaAguardando = document.getElementById('tabela-info-aguardando-nf-retorno');
    const tabelaQualidade = document.getElementById('tabela-info-em-inspecao');

    document.getElementById('btn-novo-registro').onclick = openPanelNew;
    document.getElementById('btnClosePanel').onclick = closePanel;
    document.getElementById('btnCancelForm').onclick = closePanel;
    document.getElementById('panelOverlay').onclick = closePanel;

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closePanel();
    });

    // =====================================================
    // SUBMIT (PRECHECK + SAVE)
    // =====================================================
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        hideFormError();

        // Validação cliente: checar campos obrigatórios antes do precheck
        const requiredFields = [
            { id: 'cnpj', name: 'CNPJ' },
            { id: 'nota_fiscal', name: 'NF Entrada' },
            { id: 'razao_social', name: 'Razão Social' },
            { id: 'operacao_origem', name: 'Operação Origem' },
            { id: 'operacao_destino', name: 'Operação Destino' },
            { id: 'setor', name: 'Setor' },
            { id: 'data_inicio_qualidade', name: 'Data Início Inspeção' }
        ];

        for (const f of requiredFields) {
            const el = document.getElementById(f.id);
            const val = el ? (el.value || '').toString().trim() : '';
            if (!val) {
                showFormError(`Campo obrigatório: ${f.name}`);
                try { if (el) el.focus(); } catch(e){}
                return;
            }
        }

        // Remover atributo disabled de campos dentro do form antes de construir the FormData
        try {
            form.querySelectorAll('[disabled]').forEach(el => el.removeAttribute('disabled'));
        } catch (err) { console.warn('qualidade: error enabling disabled fields', err); }

        const formData = new FormData(form);
        const checkData = new FormData(form);
        checkData.set("only_check", "1");

        try {
            // Debug: log formData contents before sending
            try {
                const preview = {};
                for (const pair of formData.entries()) { preview[pair[0]] = pair[1]; }
                console.log('qualidade: formData before precheck ->', preview);
                const previewCheck = {};
                for (const pair of checkData.entries()) { previewCheck[pair[0]] = pair[1]; }
                console.log('qualidade: checkData before precheck ->', previewCheck);
            } catch (dbg) { console.warn('qualidade: cannot preview formData', dbg); }

            // ---------- PRECHECK ----------
            let resCheck = await fetch(
                "/BackEnd/Qualidade/Qualidade.php",
                { method: "POST", body: checkData }
            );

            let textCheck = await resCheck.text();
            console.log('qualidade: precheck response status=', resCheck.status, 'text=', textCheck);
            let jsonCheck;
            try { jsonCheck = JSON.parse(textCheck); } catch (e) { jsonCheck = null; }
            if (resCheck.status === 400) {
                try { console.error('qualidade: precheck error json=', jsonCheck); } catch(e){}
            }

            if (jsonCheck.substitution?.has_substitution) {
                mensagemAlertaInline.style.display = "block";
                mensagemAlertaInline.innerHTML =
                    "⚠️ Existe equipamento substituído na remessa. Verifique a caixa.";

                // Usar modal customizado que retorna Promise quando disponível
                let confirmar = true;
                try {
                    if (window.__qualidadeConfirm) {
                        confirmar = await window.__qualidadeConfirm(
                            "Existe equipamento substituído na remessa. Deseja continuar?"
                        );
                    } else {
                        confirmar = confirm(
                            "Existe equipamento substituído na remessa. Deseja continuar?"
                        );
                    }
                } catch (e) {
                    console.error('Erro no confirm custom:', e);
                    confirmar = false;
                }

                if (!confirmar) return;
            } else {
                mensagemAlertaInline.style.display = "none";
            }

            // ---------- SAVE ----------
            let resSave = await fetch(
                "/BackEnd/Qualidade/Qualidade.php",
                { method: "POST", body: formData }
            );

            let textSave = await resSave.text();
            console.log('qualidade: save response status=', resSave.status, 'text=', textSave);
            let jsonSave;
            try { jsonSave = JSON.parse(textSave); } catch (e) { jsonSave = null; }
            if (resSave.status === 400) {
                try { console.error('qualidade: save error json=', jsonSave); } catch(e){}
            }

            if (jsonSave.error) {
                mensagemErro.textContent = jsonSave.error;
                return;
            }

            if (jsonSave.success) {

                // Redirect seguro (se vier do backend)
                if (jsonSave.redirect) {
                    redirectTo(jsonSave.redirect);
                    return;
                }

                alert("Inspeção registrada com sucesso.");
                form.reset();
                closePanel();

                if (btnAguardando.classList.contains('ativo')) {
                    btnAguardando.click();
                } else {
                    btnQualidade.click();
                }
            }

        } catch (err) {
            console.error(err);
            mensagemErro.textContent = "Erro na comunicação com o servidor.";
        }
    });

    // =====================================================
    // TABELAS
    // =====================================================
    function mostrarTabela(wrapper) {
        wrapperAguardando.style.display = 'none';
        wrapperQualidade.style.display = 'none';
        wrapper.style.display = 'block';

        tabelaAguardando.querySelector('tbody').innerHTML = '';
        tabelaQualidade.querySelector('tbody').innerHTML = '';
    }

    function preencherInputs(item, tipo, row) {
        document.getElementById('cnpj').value = item.cnpj || '';
        document.getElementById('razao_social').value = item.razao_social || '';
        document.getElementById('nota_fiscal').value = item.nota_fiscal || '';
        document.getElementById('setor').value = item.setor || '';
        document.getElementById('quantidade').value = item.quantidade || '';
        document.getElementById('quantidade_parcial').value = item.quantidade_parcial || '';

        if (tipo === "qualidade") {
            document.getElementById('data_inicio_qualidade').value = item.data_inicio_qualidade || '';
            document.getElementById('nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';
        }

        document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
        if (row) row.classList.add('row-selected');

        // Log para debug: identifica remessa clicada
        try { console.log('qualidade: preencherInputs item=', item, 'tipo=', tipo); } catch(e) {}

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
            row.onclick = () => preencherInputs(item, "aguardando", row);
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
            row.onclick = () => preencherInputs(item, "qualidade", row);
            tbody.appendChild(row);
        });
    }

    function destacarBotao(btn) {
        btnAguardando.classList.remove("ativo");
        btnQualidade.classList.remove("ativo");
        btn.classList.add("ativo");
    }

    function filtrarAguardando(listaAguardando, listaQualidade) {
        const chaves = new Set(listaQualidade.map(i => `${i.cnpj}-${i.nota_fiscal}`));
        return listaAguardando.filter(i => !chaves.has(`${i.cnpj}-${i.nota_fiscal}`));
    }

    // =====================================================
    // BOTÕES DE VISÃO
    // =====================================================
    btnAguardando.onclick = () => {
        destacarBotao(btnAguardando);
        fetch("/BackEnd/Qualidade/consulta_qualidade.php")
            .then(r => r.json())
            .then(qualidade => {
                dadosQualidade = qualidade;
                fetch("/BackEnd/Qualidade/consulta_aguardando_nf.php")
                    .then(r => r.json())
                    .then(aguardando => {
                        dadosAguardandoNFRetorno = aguardando;
                        preencherTabelaAguardando(
                            filtrarAguardando(dadosAguardandoNFRetorno, dadosQualidade)
                        );
                    });
            });
    };

    btnQualidade.onclick = () => {
        destacarBotao(btnQualidade);
        fetch("/BackEnd/Qualidade/consulta_qualidade.php")
            .then(r => r.json())
            .then(dados => {
                dadosQualidade = dados;
                preencherTabelaQualidade(dados);
            });
    };

    // Inicialização padrão
    btnAguardando.click();

    // =====================================================
    // FILTRO POR NF
    // =====================================================
    document.getElementById("filtro-nf").addEventListener("input", function () {
        const termo = this.value.toLowerCase().trim();
        if (!/^[\w\s\-./]*$/.test(termo)) return;

        [tabelaAguardando, tabelaQualidade].forEach(tabela => {
            tabela.querySelectorAll("tbody tr").forEach(row => {
                const nf = row.cells[3]?.textContent.toLowerCase().trim() || '';
                const nfRet = row.cells[8]?.textContent.toLowerCase().trim() || '';
                row.style.display = (nf === termo || nfRet === termo) ? "" : "none";
            });
        });
    });

    // Handlers para o alerta grande (fechar/confirmar/cancelar) com suporte a Promise
    (function setupBigAlertHandlers(){
        const bigAlert = document.getElementById('mensagemAlerta');
        if (!bigAlert) return;

        const msgBox = bigAlert.querySelector('.big-alert-message');
        const btnClose = bigAlert.querySelector('.big-alert-close');
        const btnConfirm = document.getElementById('bigAlertConfirm');
        const btnCancel = document.getElementById('bigAlertCancel');

        let pendingResolve = null;

        function hideBigAlert() {
            bigAlert.classList.add('hidden');
            if (msgBox) msgBox.textContent = '';
        }

        function showBigAlert(message){
            if (msgBox) msgBox.textContent = message || '';
            bigAlert.classList.remove('hidden');
        }

        function showConfirm(message){
            return new Promise((resolve) => {
                pendingResolve = resolve;
                showBigAlert(message);
            });
        }

        function finishConfirm(result){
            try { hideBigAlert(); } catch(e){}
            if (pendingResolve) {
                pendingResolve(result);
                pendingResolve = null;
            }
        }

        if (btnClose) btnClose.addEventListener('click', () => finishConfirm(false));
        if (btnCancel) btnCancel.addEventListener('click', () => finishConfirm(false));
        if (btnConfirm) btnConfirm.addEventListener('click', () => finishConfirm(true));

        // Expor para debugging e compatibilidade
        window.__qualidadeShowBigAlert = showBigAlert;
        window.__qualidadeHideBigAlert = hideBigAlert;
        window.__qualidadeConfirm = showConfirm;

        // Garantir que o alerta esteja escondido ao iniciar
        try { hideBigAlert(); } catch(e){}
    })();

});
</script>


</body>
</html>