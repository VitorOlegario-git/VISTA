<?php
session_start();

header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$tempo_limite = 1200; // 20 minutos

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

if (!isset($_SESSION['username'])) {
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparo - VISTA</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/reparo.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/CnpjMask.js"></script>
    <style>
        /* Ocultação condicional que preserva o espaço (não quebra layout) */
        .conditional-hidden { transition: opacity .15s ease; }
        .conditional-hidden.hidden { visibility: hidden; opacity: 0; pointer-events: none; }
    </style>
</head>
<body>

<!-- Overlay do Painel -->
<div class="panel-overlay" id="panel-overlay"></div>

<!-- Área Principal: Tabela -->
<div class="main-content">
    
    <!-- Header da Seção -->
    <div class="content-header">
        <div class="header-left">
            <i class="fas fa-tools"></i>
            <h1>Reparo de Equipamentos</h1>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-secondary" onclick="voltarComReload()">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
            <button type="button" class="btn-new-record" id="btn-new-record">
                <i class="fas fa-plus"></i>
                <span>Novo Reparo</span>
            </button>
        </div>
    </div>

    <!-- Container da Tabela -->
    <div class="table-section">
        <div class="table-controls">
            <input type="text" id="filtro-nf" placeholder="🔍 Pesquisar por NF..." class="search-input">
            
            <div class="table-toggle-buttons">
                <button type="button" class="btn-table-toggle" id="btn-aguardando-pg">
                    Aguardando Pagamento
                </button>
                <button type="button" class="btn-table-toggle" id="btn-em-reparo">
                    Em Reparo
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <!-- Tabela Aguardando Pagamento -->
            <table id="tabela-info-aguardando-pagamento" style="display: none;">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th>CNPJ</th>
                        <th>Razão Social</th>
                        <th>NF</th>
                        <th>Quantidade</th>
                        <th>Status</th>
                        <th>Nº Orçamento</th>
                        <th>Valor Orçamento</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <!-- Tabela Em Reparo -->
            <table id="tabela-info-em-reparo" style="display: none;">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th>CNPJ</th>
                        <th>Razão Social</th>
                        <th>NF</th>
                        <th>Data Início Reparo</th>
                        <th>Quantidade</th>
                        <th>Qtd. Parcial</th>
                        <th>Status</th>
                        <th>Nº Orçamento</th>
                        <th>Valor Orçamento</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Painel Lateral Deslizante -->
<div class="side-panel" id="side-panel">
    
    <!-- Header do Painel -->
    <div class="panel-header">
        <div class="panel-title-group">
            <i class="fas fa-plus-circle" id="panel-icon"></i>
            <h2 id="panel-title">Novo Reparo</h2>
        </div>
        <button type="button" class="btn-close-panel" id="btn-close-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Corpo do Painel: Formulário -->
    <div class="panel-body">
        <div id="loading" style="display: none;">Carregando...</div>
        <div id="mensagemErro"></div>

        <form id="form-reparo" action="/BackEnd/Reparo/Reparo.php" method="POST">

            <!-- Bloco: Cliente -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-user-tie"></i>
                    Cliente
                </h3>

                <div class="form-group">
                    <label for="cnpj">
                        <i class="fas fa-building"></i>
                        CNPJ
                    </label>
                    <input type="text" id="cnpj" name="cnpj" required maxlength="18" placeholder="Digite o CNPJ" readonly>
                </div>

                <div class="form-group">
                    <label for="razao_social">
                        <i class="fas fa-user"></i>
                        Razão Social
                    </label>
                    <input type="text" id="razao_social" name="razao_social" required placeholder="Razão Social do cliente" readonly>
                </div>

                <div class="form-group">
                    <label for="nota_fiscal">
                        <i class="fas fa-file-invoice"></i>
                        Nota Fiscal
                    </label>
                    <input type="text" id="nota_fiscal" name="nota_fiscal" required placeholder="Nota fiscal de entrada" readonly>
                </div>

                <div class="form-group">
                    <label for="setor">
                        <i class="fas fa-industry"></i>
                        Setor
                    </label>
                    <select id="setor" name="setor" required>
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

            <!-- Bloco: Datas -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Datas
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inicio_reparo">
                            <i class="fas fa-calendar-check"></i>
                            Início do Reparo
                        </label>
                        <input type="date" id="data_inicio_reparo" name="data_inicio_reparo" required>
                    </div>

                    <div class="form-group">
                        <label for="data_solicitacao_nf">
                            <i class="fas fa-calendar-alt"></i>
                            Encerramento do Reparo
                        </label>
                        <input type="date" id="data_solicitacao_nf" name="data_solicitacao_nf">
                    </div>
                </div>
            </div>

            <!-- Bloco: Quantidades -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-boxes"></i>
                    Quantidades
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantidade">
                            <i class="fas fa-sort-numeric-up"></i>
                            Quantidade Total
                        </label>
                        <input type="number" id="quantidade" name="quantidade" required placeholder="Quantidade total" readonly>
                    </div>

                    <div id="wrap-sim-nao" class="form-group conditional-hidden">
                        <label for="sim_nao">
                            <i class="fas fa-question-circle"></i>
                            Reparo Parcial?
                        </label>
                        <select name="sim_nao" id="sim_nao">
                            <option value="">Selecione</option>
                            <option value="sim">Sim</option>
                            <option value="nao">Não</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="quantidade_parcial">
                        <i class="fas fa-box"></i>
                        Quantidade Parcial
                    </label>
                    <input type="number" id="quantidade_parcial" name="quantidade_parcial" placeholder="Quantidade parcial reparada">
                </div>
            </div>

            <!-- Bloco: Ação e Orçamento -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-cogs"></i>
                    Ação e Orçamento
                </h3>

                <div class="form-group">
                    <label for="acao">
                        <i class="fas fa-play-circle"></i>
                        Ação
                    </label>
                    <select id="acao" name="acao" required>
                        <option value="">Selecione</option>
                        <option value="inicio">Início do Reparo</option>
                        <option value="fim">Fim do Reparo</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_orcamento">
                            <i class="fas fa-hashtag"></i>
                            Nº do Orçamento
                        </label>
                        <input type="text" name="numero_orcamento" id="numero_orcamento" placeholder="Número do orçamento">
                    </div>

                    <div class="form-group">
                        <label for="valor_orcamento">
                            <i class="fas fa-dollar-sign"></i>
                            Valor do Orçamento
                        </label>
                        <input type="text" name="valor_orcamento" id="valor_orcamento" placeholder="Valor do orçamento">
                    </div>
                </div>
            </div>

            <!-- Bloco: Operações -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-exchange-alt"></i>
                    Operações
                </h3>

                <div class="form-group">
                    <label for="operacao_origem">
                        <i class="fas fa-map-marker-alt"></i>
                        Operação Origem
                    </label>
                    <select id="operacao_origem" name="operacao_origem" required>
                        <option value="">Selecione</option>
                        <option value="aguardando_pg">Aguardando Pagamento</option>
                        <option value="em_reparo">Em reparo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="operacao_destino">
                        <i class="fas fa-map-marker-alt"></i>
                        Operação Destino
                    </label>
                    <select id="operacao_destino" name="operacao_destino" required>
                        <option value="">Selecione</option>
                        <option value="em_reparo">Em reparo</option>
                        <option value="aguardando_NF_retorno">Aguardando NF de retorno</option>
                        <option value="segregado">Segregado</option>
                        <option value="descarte">Descarte</option>
                        <option value="estocado">Estocado</option>
                        <option value="reparo_pendente">Reparo pendente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="operador">
                        <i class="fas fa-user-cog"></i>
                        Operador
                    </label>
                    <input type="text" id="operador" name="operador" value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
                </div>
            </div>

            <!-- Bloco: Observações -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-comment"></i>
                    Observações
                </h3>

                <div class="form-group">
                    <label for="obs">Observações Gerais</label>
                    <textarea id="obs" name="obs" rows="4" placeholder="Digite observações relevantes"></textarea>
                </div>
            </div>

            <!-- Botões do Formulário -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Cadastrar
                </button>
            </div>

        </form>
    </div>

</div>

<script>
// =====================================================
// CONFIGURAÇÕES GERAIS
// =====================================================
const ROUTER = '/router_public.php?url=';

function redirect(route, params = "") {
    window.top.location.href = ROUTER + route + params;
}

function voltarComReload() {
    redirect("dashboard", "&reload=" + Date.now());
}

// =====================================================
// ESTADO
// =====================================================
let isPanelOpen = false;
let isEditMode = false;

// =====================================================
// ELEMENTOS
// =====================================================
const sidePanel    = document.getElementById('side-panel');
const panelOverlay = document.getElementById('panel-overlay');
const btnNew       = document.getElementById('btn-new-record');
const btnClose     = document.getElementById('btn-close-panel');
const panelTitle   = document.getElementById('panel-title');
const panelIcon    = document.getElementById('panel-icon');
const form         = document.getElementById('form-reparo');
const msgErro      = document.getElementById('mensagemErro');

// =====================================================
// PAINEL
// =====================================================
function openPanelNew() {
    isEditMode = false;
    isPanelOpen = true;
    form.reset();

    sidePanel.classList.add('open');
    sidePanel.classList.remove('edit-mode');
    panelOverlay.classList.add('active');

    panelTitle.textContent = "Novo Reparo";
    panelIcon.className = "fas fa-plus-circle";
    document.getElementById('operador').value = "<?php echo $_SESSION['username'] ?? ''; ?>";
}

function openPanelEdit() {
    isEditMode = true;
    isPanelOpen = true;

    sidePanel.classList.add('open', 'edit-mode');
    panelOverlay.classList.add('active');

    panelTitle.textContent = "Editando Reparo";
    panelIcon.className = "fas fa-edit";
}

function closePanel() {
    isPanelOpen = false;
    isEditMode = false;

    sidePanel.classList.remove('open', 'edit-mode');
    panelOverlay.classList.remove('active');

    document.querySelectorAll('table tbody tr')
        .forEach(r => r.classList.remove('row-selected'));
}

// Eventos painel
btnNew.onclick = openPanelNew;
btnClose.onclick = closePanel;
panelOverlay.onclick = closePanel;

document.addEventListener("keydown", e => {
    if (e.key === "Escape" && isPanelOpen) closePanel();
});

// =====================================================
// INICIALIZAÇÃO
// =====================================================
let dadosAguardando = [];
let dadosReparo = [];

document.addEventListener("DOMContentLoaded", () => {

    initializeCNPJMask();
        // Formata data ISO (YYYY-MM-DD[ HH:MM:SS]) para DD/MM/YYYY
        function formatDateToBR(iso){
            if(!iso) return '';
            const datePart = String(iso).split(' ')[0];
            const p = datePart.split('-');
            if(p.length !== 3) return datePart;
            return `${p[2]}/${p[1]}/${p[0]}`;
        }

    const acao      = document.getElementById("acao");
    const parcial   = document.getElementById("sim_nao");
    const qtdParc   = document.getElementById("quantidade_parcial");
    const opOrigem  = document.getElementById("operacao_origem");
    const opDestino = document.getElementById("operacao_destino");
    const setor     = document.getElementById("setor");
    const numOrc    = document.getElementById("numero_orcamento");
    const valOrc    = document.getElementById("valor_orcamento");
    const dataFim   = document.getElementById("data_solicitacao_nf");

    const btnAg = document.getElementById("btn-aguardando-pg");
    const btnRp = document.getElementById("btn-em-reparo");
    const tblAg = document.getElementById("tabela-info-aguardando-pagamento");
    const tblRp = document.getElementById("tabela-info-em-reparo");

    // ---------------------------
    // REGRAS DE FORMULÁRIO
    // ---------------------------
    parcial.onchange = () => {
        qtdParc.required = parcial.value === "sim";
    };

    acao.onchange = () => {
        const isFim = acao.value === "fim";
        numOrc.required  = isFim;
        valOrc.required  = isFim;
        dataFim.required = isFim;
        qtdParc.disabled = isFim;
        if (isFim) qtdParc.value = "";
        atualizarDestino();
    };

    function atualizarDestino() {
        opDestino.innerHTML = `<option value="">Selecione</option>`;

        if (acao.value === "inicio") {
            ["em_reparo","segregado","descarte"].forEach(v =>
                opDestino.innerHTML += `<option value="${v}">${v.replace("_"," ")}</option>`
            );
            return;
        }

        if (acao.value === "fim") {
            if (setor.value.startsWith("dev")) {
                opDestino.innerHTML += `<option value="estocado">Estocado</option>`;
            } else {
                ["aguardando_NF_retorno","reparo_pendente","segregado","descarte"]
                    .forEach(v => opDestino.innerHTML += `<option value="${v}">${v.replace("_"," ")}</option>`);
            }
        }
    }

    acao.onchange = atualizarDestino;
    setor.onchange = atualizarDestino;
    opOrigem.onchange = atualizarDestino;

    // Controla visibilidade do campo 'Reparo Parcial?' sem quebrar o layout
    const wrapSimNao = document.getElementById('wrap-sim-nao');
    function controlarVisibilidadeReparoParcial(){
        if(!wrapSimNao || !opOrigem) return;
        if(opOrigem.value === 'em_reparo'){
            wrapSimNao.classList.add('hidden');
        } else {
            wrapSimNao.classList.remove('hidden');
        }
    }
    controlarVisibilidadeReparoParcial();
    opOrigem.addEventListener('change', controlarVisibilidadeReparoParcial);
    // Ajusta opções de 'acao' dependendo da operacao_origem (reparo)
    function atualizarAcaoPorOrigem(){
        if(!acao || !opOrigem) return;
        const origem = opOrigem.value;
        acao.innerHTML = '<option value="">Selecione</option>';
        if(origem === 'aguardando_pg'){
            acao.innerHTML += '<option value="inicio">Início do Reparo</option>';
        } else if(origem === 'em_reparo'){
            acao.innerHTML += '<option value="fim">Fim do Reparo</option>';
        } else {
            acao.innerHTML += '<option value="inicio">Início do Reparo</option>';
            acao.innerHTML += '<option value="fim">Fim do Reparo</option>';
        }
    }
    atualizarAcaoPorOrigem();
    opOrigem.addEventListener('change', atualizarAcaoPorOrigem);

    // ---------------------------
    // SUBMIT
    // ---------------------------
    form.addEventListener("submit", async e => {
        e.preventDefault();
        msgErro.innerHTML = "";

        try {
            const res = await fetch(
                "/BackEnd/Reparo/Reparo.php",
                { method: "POST", body: new FormData(form) }
            );

            const text = await res.text();
            let json;

            try { json = JSON.parse(text); }
            catch { throw "Resposta inválida do servidor"; }

            if (json.error) {
                msgErro.innerHTML = `<p style="color:red">${json.error}</p>`;
                return;
            }

            alert(json.success || "Operação realizada");

            if (json.acao === "inicio") {
                redirect("cadastro-pos-analise",
                    "&cnpj=" + encodeURIComponent(form.cnpj.value) +
                    "&nf_entrada=" + encodeURIComponent(form.nota_fiscal.value)
                );
            } else {
                redirect("dashboard");
            }

        } catch (err) {
            msgErro.innerHTML = `<p style="color:red">${err}</p>`;
        }
    });

    // ---------------------------
    // TABELAS
    // ---------------------------
    function mostrarTabela(tbl) {
        tblAg.style.display = "none";
        tblRp.style.display = "none";
        tbl.querySelector("tbody").innerHTML = "";
        tbl.style.display = "table";
    }

    function preencher(item, tipo, row) {
        document.querySelectorAll("tbody tr").forEach(r => r.classList.remove("row-selected"));
        row.classList.add("row-selected");

        form.cnpj.value          = item.cnpj;
        form.razao_social.value  = item.razao_social;
        form.nota_fiscal.value   = item.nota_fiscal;
        form.setor.value         = item.setor;
        form.quantidade.value    = item.quantidade_total;
        form.quantidade_parcial.value = item.quantidade_parcial || "";
        form.operacao_origem.value = item.status;
        if (typeof controlarVisibilidadeReparoParcial === 'function') controlarVisibilidadeReparoParcial();
        if (typeof atualizarAcaoPorOrigem === 'function') atualizarAcaoPorOrigem();
        form.numero_orcamento.value = item.numero_orcamento || "";
        form.valor_orcamento.value  = item.valor_orcamento || "";

        if (tipo === "reparo") {
            form.data_inicio_reparo.value = item.data_inicio_reparo?.split(" ")[0] || "";
            form.data_inicio_reparo.readOnly = true;
        }

        openPanelEdit();
    }

    function preencherTabela(tbl, dados, tipo) {
        mostrarTabela(tbl);
        const tbody = tbl.querySelector("tbody");
        dados.forEach(item => {
            const tr = document.createElement("tr");
            if (tipo === 'reparo') {
                tr.innerHTML = `
                    <td>${item.setor || ''}</td>
                    <td>${item.cnpj || ''}</td>
                    <td>${item.razao_social || ''}</td>
                    <td>${item.nota_fiscal || ''}</td>
                    <td>${item.data_inicio_reparo ? item.data_inicio_reparo.split(' ')[0] : ''}</td>
                    <td>${item.quantidade_total || ''}</td>
                    <td>${item.quantidade_parcial || ''}</td>
                    <td>${item.status || ''}</td>
                    <td>${item.numero_orcamento || ''}</td>
                    <td>${item.valor_orcamento || ''}</td>`;
            } else {
                // Aguardando pagamento - manter estrutura original
                tr.innerHTML = `
                    <td>${item.setor || ''}</td>
                    <td>${item.cnpj || ''}</td>
                    <td>${item.razao_social || ''}</td>
                    <td>${item.nota_fiscal || ''}</td>
                    <td>${item.quantidade_total || ''}</td>
                    <td>${item.status || ''}</td>
                    <td>${item.numero_orcamento || ''}</td>
                    <td>${item.valor_orcamento || ''}</td>`;
            }
            tr.onclick = () => preencher(item, tipo, tr);
            tbody.appendChild(tr);
        });
    }

    btnAg.onclick = async () => {
        const r = await fetch("/BackEnd/Reparo/consulta_reparo.php").then(r=>r.json());
        const a = await fetch("/BackEnd/Reparo/consulta_aguardando_pg.php").then(r=>r.json());
        dadosReparo = r;
        dadosAguardando = a.filter(x => !r.some(y => y.cnpj===x.cnpj && y.nota_fiscal===x.nota_fiscal));
        preencherTabela(tblAg, dadosAguardando, "aguardando");
    };

    btnRp.onclick = async () => {
        dadosReparo = await fetch("/BackEnd/Reparo/consulta_reparo.php").then(r=>r.json());
        preencherTabela(tblRp, dadosReparo, "reparo");
    };

    btnAg.click();

    // ---------------------------
    // FILTRO NF
    // ---------------------------
    document.getElementById("filtro-nf").addEventListener("input", function () {
        const termo = this.value.toLowerCase().trim();
        if (!/^[\w\-./]*$/.test(termo)) return;

        [tblAg, tblRp].forEach(tbl => {
            tbl.querySelectorAll("tbody tr").forEach(tr => {
                tr.style.display =
                    tr.cells[3].textContent.toLowerCase() === termo ? "" : "none";
            });
        });
    });
});
</script>

    
</body>
</html>
