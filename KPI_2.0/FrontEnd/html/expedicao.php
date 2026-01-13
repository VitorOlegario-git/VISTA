<?php
session_start();
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

$tempo_limite = 1200;
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
    <title>Expedição - KPI 2.0</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/expedicao.css">
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
            <i class="fas fa-shipping-fast"></i>
            <h1>Expedição de Equipamentos</h1>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-voltar" onclick="voltarComReload()">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
            <button type="button" class="btn-novo" id="btn-novo-registro">
                <i class="fas fa-plus"></i> Nova Expedição
            </button>
        </div>
    </div>

    <!-- Seção de Tabelas -->
    <div class="table-section">
        <div class="table-controls">
            <div class="button-group-toggle">
                <button type="button" class="btn-toggle ativo" id="btn-expedicao">
                    <i class="fas fa-clock"></i> Aguardando Envio
                </button>
                <button type="button" class="btn-toggle" id="btn-enviado">
                    <i class="fas fa-check-circle"></i> Remessa Enviada
                </button>
            </div>
            <div class="filter-container">
                <i class="fas fa-search"></i>
                <input type="text" id="filtro-nf" placeholder="Pesquisar por NF entrada / retorno" class="filter-input">
            </div>
        </div>

        <!-- Tabela: Aguardando Envio -->
        <div class="table-wrapper" id="wrapper-aguardando-expedicao">
            <table id="tabela-info-aguardando-expedicao">
                <thead>
                    <tr>
                        <th><i class="fas fa-industry"></i> Setor</th>
                        <th><i class="fas fa-id-card"></i> CNPJ</th>
                        <th><i class="fas fa-building"></i> Razão Social</th>
                        <th><i class="fas fa-file-invoice"></i> NF</th>
                        <th><i class="fas fa-calendar-alt"></i> Data Envio Expedição</th>
                        <th><i class="fas fa-boxes"></i> Quantidade</th>
                        <th><i class="fas fa-tasks"></i> Status</th>
                        <th><i class="fas fa-file-invoice-dollar"></i> NF Retorno</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Tabela: Remessa Enviada -->
        <div class="table-wrapper" id="wrapper-expedicao-concluida" style="display: none;">
            <table id="tabela-info-expedicao-concluida">
                <thead>
                    <tr>
                        <th><i class="fas fa-industry"></i> Setor</th>
                        <th><i class="fas fa-id-card"></i> CNPJ</th>
                        <th><i class="fas fa-building"></i> Razão Social</th>
                        <th><i class="fas fa-file-invoice"></i> NF</th>
                        <th><i class="fas fa-calendar-check"></i> Data Envio Cliente</th>
                        <th><i class="fas fa-boxes"></i> Quantidade</th>
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
        <h2 id="panelTitle">Nova Expedição</h2>
        <button type="button" class="btn-close-panel" id="btnClosePanel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="panel-content">
        <form id="form-expedicao">
            <div id="mensagemErro" class="error-message"></div>

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
                        <label for="data_envio_expedicao">
                            <i class="fas fa-calendar-check"></i>
                            Data Envio p/ Expedição
                        </label>
                        <input type="date" id="data_envio_expedicao" name="data_envio_expedicao" readonly>
                    </div>
                    <div class="form-group">
                        <label for="data_envio_cliente">
                            <i class="fas fa-calendar-plus"></i>
                            Data Envio p/ Cliente
                        </label>
                        <input type="date" id="data_envio_cliente" name="data_envio_cliente">
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: Quantidade e Rastreio -->
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-boxes"></i>
                    <span>Quantidade e Rastreio</span>
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
                        <label for="codigo_rastreio_envio">
                            <i class="fas fa-barcode"></i>
                            Cód. Rastreio
                        </label>
                        <input type="text" id="codigo_rastreio_envio" name="codigo_rastreio_envio" 
                               placeholder="Código de rastreio do envio" required>
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
                            <option value="envio_expedicao">Enviado para expedição</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="operacao_destino">
                            <i class="fas fa-sign-out-alt"></i>
                            Operação Destino
                        </label>
                        <select id="operacao_destino" name="operacao_destino" required>
                            <option value="">Selecione</option>
                            <option value="envio_cliente">Enviado para o cliente</option>
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
                               placeholder="Nota fiscal de retorno" required>
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

<script>
function voltarComReload() {
    window.top.location.href = "https://kpi.stbextrema.com.br/router_public.php?url=dashboard&reload=" + new Date().getTime();
}

let dadosAguardandoExpedicao = [];
let dadosExpedido = [];

// ========== CONTROLE DO PAINEL ==========
function openPanelNew() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const form = document.getElementById('form-expedicao');
    const title = document.getElementById('panelTitle');
    
    form.reset();
    title.textContent = 'Nova Expedição';
    panel.classList.remove('estado-editando');
    panel.classList.add('estado-novo');
    panel.classList.add('aberto');
    overlay.classList.add('ativo');
    
    document.querySelectorAll('.row-selected').forEach(row => {
        row.classList.remove('row-selected');
    });
}

function openPanelEdit() {
    const panel = document.getElementById('sidePanel');
    const overlay = document.getElementById('panelOverlay');
    const title = document.getElementById('panelTitle');
    
    title.textContent = 'Editando Expedição';
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
    
    document.querySelectorAll('.row-selected').forEach(row => {
        row.classList.remove('row-selected');
    });
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-expedicao");
    const mensagemErro = document.getElementById("mensagemErro");
    
    const btnExpedicao = document.getElementById('btn-expedicao');
    const btnEnviado = document.getElementById('btn-enviado');
    const wrapperAguardando = document.getElementById('wrapper-aguardando-expedicao');
    const wrapperConcluida = document.getElementById('wrapper-expedicao-concluida');
    const tabelaAguardandoExpedicao = document.getElementById('tabela-info-aguardando-expedicao');
    const tabelaExpedido = document.getElementById('tabela-info-expedicao-concluida');

    const btnNovo = document.getElementById('btn-novo-registro');
    const btnClosePanel = document.getElementById('btnClosePanel');
    const btnCancelForm = document.getElementById('btnCancelForm');
    const overlay = document.getElementById('panelOverlay');

    btnNovo.addEventListener('click', openPanelNew);
    btnClosePanel.addEventListener('click', closePanel);
    btnCancelForm.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePanel();
    });

    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const res = await fetch("https://kpi.stbextrema.com.br/BackEnd/Expedicao/Expedicao.php", {
                method: "POST",
                body: formData
            });
            const text = await res.text();
            console.log("RESPOSTA RAW:", text);
            const json = JSON.parse(text);
            
            if (json.success && json.redirect) {
                window.location.href = json.redirect;
            } else if (json.error) {
                mensagemErro.innerText = json.error;
            } else if (json.success) {
                form.reset();
                mensagemErro.innerText = "";
                closePanel();
                // Recarregar tabela ativa
                if (btnExpedicao.classList.contains('ativo')) {
                    btnExpedicao.click();
                } else {
                    btnEnviado.click();
                }
            }
        } catch (err) {
            console.error("Erro ao converter para JSON:", err);
            mensagemErro.innerText = "Erro no formato da resposta do servidor.";
        }
    });

    function mostrarTabela(wrapper) {
        wrapperAguardando.style.display = 'none';
        wrapperConcluida.style.display = 'none';
        wrapper.style.display = 'block';
        tabelaAguardandoExpedicao.querySelector('tbody').innerHTML = '';
        tabelaExpedido.querySelector('tbody').innerHTML = '';
    }

    function preencherInputs(item, tipo, row) {
        document.querySelector('#cnpj').value = item.cnpj || '';
        document.querySelector('#razao_social').value = item.razao_social || '';
        document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
        document.querySelector("#setor").value = item.setor || '';
        document.querySelector('#quantidade').value = item.quantidade || '';
        document.querySelector('#nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';

        if (tipo === "aguardandoExpedicao") {
            document.querySelector('#operacao_origem').value = item.operacao_destino || '';
            document.querySelector('#data_envio_expedicao').value = item.data_envio_expedicao || '';
        } else if (tipo === "expedido") {
            document.querySelector('#data_envio_expedicao').value = item.data_inicio_qualidade || '';
            document.querySelector('#quantidade').value = item.quantidade || '';
            document.querySelector('#operacao_origem').value = item.operacao_destino || '';
            document.querySelector('#nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';
        }

        // Highlight da linha
        document.querySelectorAll('.row-selected').forEach(r => r.classList.remove('row-selected'));
        if (row) row.classList.add('row-selected');
        
        openPanelEdit();
    }

    function preencherTabelaAguardandoExpedicao(dados) {
        mostrarTabela(wrapperAguardando);
        const tbody = tabelaAguardandoExpedicao.querySelector('tbody');
        dados.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.setor || ''}</td>
                <td>${item.cnpj || ''}</td>
                <td>${item.razao_social || ''}</td>
                <td>${item.nota_fiscal || ''}</td>
                <td>${item.data_envio_expedicao || ''}</td>
                <td>${item.quantidade || ''}</td>
                <td>${item.operacao_destino || ''}</td>
                <td>${item.nota_fiscal_retorno || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "aguardandoExpedicao", row));
            tbody.appendChild(row);
        });
    }

    function preencherTabelaExpedido(dados) {
        mostrarTabela(wrapperConcluida);
        const tbody = tabelaExpedido.querySelector('tbody');
        dados.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = ` 
                <td>${item.setor || ''}</td>
                <td>${item.cnpj || ''}</td>
                <td>${item.razao_social || ''}</td>
                <td>${item.nota_fiscal || ''}</td>
                <td>${item.data_envio_cliente || ''}</td>
                <td>${item.quantidade || ''}</td>
                <td>${item.operacao_destino || ''}</td>
                <td>${item.nota_fiscal_retorno || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "expedido", row));
            tbody.appendChild(row);
        });
    }

    function destacarBotao(btn) {
        btnExpedicao.classList.remove("ativo");
        btnEnviado.classList.remove("ativo");
        btn.classList.add("ativo");
    }

    function filtrarAguardando(listaAguardandoExpedicao, listaexpedido) {
        const chavesExpedido = new Set(listaexpedido.map(item => `${item.cnpj}-${item.nota_fiscal}`));
        return listaAguardandoExpedicao.filter(item => {
            const chave = `${item.cnpj}-${item.nota_fiscal}`;
            return !chavesExpedido.has(chave);
        });
    }

    btnExpedicao.addEventListener('click', async () => {
        destacarBotao(btnExpedicao);
        const expedicao = await fetch("https://kpi.stbextrema.com.br/BackEnd/Expedicao/consulta_expedicao.php").then(res => res.json());
        const aguardando = await fetch("https://kpi.stbextrema.com.br/BackEnd/Expedicao/consulta_aguardando_envio.php").then(res => res.json());
        dadosExpedido = expedicao;
        dadosAguardandoExpedicao = aguardando;
        const filtrados = filtrarAguardando(aguardando, expedicao);
        preencherTabelaAguardandoExpedicao(filtrados);
    });

    btnEnviado.addEventListener('click', async () => {
        destacarBotao(btnEnviado);
        const dados = await fetch("https://kpi.stbextrema.com.br/BackEnd/Expedicao/consulta_expedicao.php").then(res => res.json());
        dadosExpedido = dados;
        preencherTabelaExpedido(dados);
    });

    btnExpedicao.click();

    document.getElementById("filtro-nf").addEventListener("input", function () {
        const termo = this.value.toLowerCase().trim();

        if (!/^[\w\s\-./]*$/.test(termo)) return;

        const tabelas = [tabelaAguardandoExpedicao, tabelaExpedido];

        tabelas.forEach(tabela => {
            const linhas = tabela.querySelectorAll("tbody tr");
            linhas.forEach(linha => {
                const notaFiscal = linha.cells[3]?.textContent.toLowerCase().trim() || '';
                const notaFiscalRetorno = linha.cells[7]?.textContent.toLowerCase().trim() || '';

                if (notaFiscal === termo || notaFiscalRetorno === termo) {
                    linha.style.display = "";
                } else {
                    linha.style.display = "none";
                }
            });
        });
    });
});
</script>
</body>
</html>