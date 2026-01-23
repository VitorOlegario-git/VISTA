<?php
session_start();

// Use apenas:
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
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
    <title>Recebimento - VISTA</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/recebimento.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/CnpjMask.js"></script>
</head>
<body>

<!-- Overlay do Painel (para Mobile) -->
<div class="panel-overlay" id="panel-overlay"></div>

<!-- Área Principal: Tabela -->
<div class="main-content">
    
    <!-- Header da Seção com Botão de Novo -->
    <div class="content-header">
        <div class="header-left">
            <i class="fas fa-inbox"></i>
            <h1>Recebimento de Equipamentos</h1>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-secondary" onclick="forcarRecarregamento()">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
            <button type="button" class="btn-new-record" id="btn-new-record">
                <i class="fas fa-plus"></i>
                <span>Novo Recebimento</span>
            </button>
        </div>
    </div>

    <!-- Container da Tabela -->
    <div class="table-section">
        <div class="table-controls">
            <input type="text" id="filtro-rastreio-cnpj" placeholder="🔍 Pesquisar por Código de Rastreio ou CNPJ..." class="search-input">
        </div>

        <div class="table-wrapper">
            <table id="tabela-info">
                <thead>
                    <tr>
                        <th>Código Rastreio</th>
                        <th>Setor</th>
                        <th>CNPJ</th>
                        <th>Razão Social</th>
                        <th>Data de Recebimento</th>
                        <th>Quantidade</th>
                        <th>Status</th>
                        <th>Observações</th>
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
            <h2 id="panel-title">Novo Recebimento</h2>
        </div>
        <button type="button" class="btn-close-panel" id="btn-close-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Corpo do Painel: Formulário -->
    <div class="panel-body">
        <form id="form-recebimento" action="/BackEnd/Recebimento/Recebimento.php" method="POST">

            <!-- Bloco: Identificação -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-tag"></i>
                    Identificação
                </h3>

                <div class="form-group">
                    <label for="cod_rastreio">
                        <i class="fas fa-barcode"></i>
                        Código de Rastreio
                    </label>
                    <input type="text" id="cod_rastreio" name="cod_rastreio" required placeholder="Digite o código de rastreio">
                </div>

                <div class="form-group">
                    <label for="setor">
                        <i class="fas fa-building"></i>
                        Setor
                    </label>
                    <select name="setor" id="setor">
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
                    <input type="text" id="cnpj" name="cnpj" placeholder="Digite o CNPJ">
                </div>

                <div class="form-group">
                    <label for="razao_social">
                        <i class="fas fa-user"></i>
                        Razão Social
                    </label>
                    <input type="text" id="razao_social" name="razao_social" placeholder="Razão social do cliente">
                </div>
            </div>

            <!-- Bloco: Datas e Documentação -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Datas e Documentação
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_recebimento">
                            <i class="fas fa-calendar-check"></i>
                            Data de Recebimento
                        </label>
                        <input type="date" id="data_recebimento" name="data_recebimento">
                    </div>

                    <div class="form-group">
                        <label for="data_envio_analise">
                            <i class="fas fa-calendar-alt"></i>
                            Data de Envio para Análise
                        </label>
                        <input type="date" id="data_envio_analise" name="data_envio_analise">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nota_fiscal">
                            <i class="fas fa-file-invoice"></i>
                            Nota Fiscal
                        </label>
                        <input type="text" id="nota_fiscal" name="nota_fiscal" placeholder="Número da nota fiscal">
                    </div>

                    <div class="form-group">
                        <label for="quantidade">
                            <i class="fas fa-sort-numeric-up"></i>
                            Quantidade
                        </label>
                        <input type="number" id="quantidade" name="quantidade" required placeholder="Quantidade de peças">
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
                        Operação de Origem
                    </label>
                    <select name="operacao_origem" id="operacao_origem">
                        <option value="">Selecione a operação</option>
                        <option value="recebimento">Recebimento</option>
                        <option value="aguardando_nf">Aguardando Emissão de nota fiscal</option>
                        <option value="envio_analise">Enviado para análise</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="operacao_destino">
                        <i class="fas fa-map-marker-alt"></i>
                        Operação de Destino
                    </label>
                    <select name="operacao_destino" id="operacao_destino">
                        <option value="">Selecione a operação</option>
                        <option value="recebimento">Recebimento</option>
                        <option value="aguardando_nf">Aguardando Emissão de nota fiscal</option>
                        <option value="envio_analise">Enviado para análise</option>
                        <option value="devolvido">Reenviado ao cliente</option>
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

<!-- Modal de Sucesso -->
<div id="success-modal" class="modal">
    <div class="modal-content">
        <i class="fas fa-check-circle"></i>
        <p id="success-message">Cadastro realizado com sucesso!</p>
        <button onclick="closeSuccessModal()">OK</button>
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

function forcarRecarregamento() {
    redirectTo("dashboard", "&nocache=" + Date.now());
}

// =====================================================
// ESTADO
// =====================================================
let isPanelOpen = false;
let isEditMode = false;

// =====================================================
// ELEMENTOS
// =====================================================
const sidePanel       = document.getElementById('side-panel');
const panelOverlay    = document.getElementById('panel-overlay');
const btnNewRecord    = document.getElementById('btn-new-record');
const btnClosePanel   = document.getElementById('btn-close-panel');
const panelTitle      = document.getElementById('panel-title');
const panelIcon       = document.getElementById('panel-icon');
const form            = document.getElementById('form-recebimento');

// =====================================================
// CONTROLE DO PAINEL
// =====================================================
function openPanelNew() {
    isEditMode = false;
    isPanelOpen = true;

    form.reset();

    sidePanel.classList.add('open');
    sidePanel.classList.remove('edit-mode');
    panelOverlay.classList.add('active');

    panelTitle.textContent = "Novo Recebimento";
    panelIcon.className = "fas fa-plus-circle";

    document.getElementById('operador').value = "<?php echo $_SESSION['username'] ?? ''; ?>";

    limparSelecaoTabela();
}

function openPanelEdit() {
    isEditMode = true;
    isPanelOpen = true;

    sidePanel.classList.add('open', 'edit-mode');
    panelOverlay.classList.add('active');

    panelTitle.textContent = "Editando Recebimento";
    panelIcon.className = "fas fa-edit";
}

function closePanel() {
    isPanelOpen = false;
    isEditMode = false;

    sidePanel.classList.remove('open', 'edit-mode');
    panelOverlay.classList.remove('active');

    limparSelecaoTabela();
}

function limparSelecaoTabela() {
    document
        .querySelectorAll('#tabela-info tbody tr')
        .forEach(r => r.classList.remove('row-selected'));
}

// =====================================================
// EVENTOS GERAIS
// =====================================================
btnNewRecord.onclick = openPanelNew;
btnClosePanel.onclick = closePanel;
panelOverlay.onclick = closePanel;

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && isPanelOpen) closePanel();
});

// =====================================================
// MODAL DE SUCESSO
// =====================================================
function showSuccessModal(message) {
    document.getElementById("success-message").innerText = message;
    document.getElementById("success-modal").style.display = "block";
    setTimeout(closeSuccessModal, 2500);
}

function closeSuccessModal() {
    document.getElementById("success-modal").style.display = "none";
    closePanel();
}

// =====================================================
// INICIALIZAÇÃO
// =====================================================
document.addEventListener("DOMContentLoaded", () => {

    initializeCNPJMask();

    // -----------------------------------------------
    // BUSCA CLIENTE POR CNPJ
    // -----------------------------------------------
    const cnpjInput = document.getElementById("cnpj");
    if (cnpjInput) {
        cnpjInput.addEventListener("blur", async () => {
            const cnpj = cnpjInput.value.trim();
            if (cnpj.length !== 18) return;

            try {
                const res = await fetch(
                    "/BackEnd/buscar_cliente.php",
                    {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: "cnpj=" + encodeURIComponent(cnpj)
                    }
                );

                const json = await res.json();

                if (json.encontrado) {
                    document.getElementById("razao_social").value = json.razao_social;
                } else {
                    alert("Cliente não cadastrado. Você será redirecionado.");
                    redirectTo("cadastrar-cliente", "&cnpj=" + encodeURIComponent(json.cnpj_usado));
                }

            } catch (err) {
                console.error("Erro ao buscar cliente:", err);
            }
        });
    }

    // -----------------------------------------------
    // LISTAGEM DE RECEBIMENTOS
    // -----------------------------------------------
    carregarTabelaRecebimento();

    function carregarTabelaRecebimento() {
        fetch("/BackEnd/Recebimento/consulta_recebimento.php")
            .then(r => r.json())
            .then(dados => {
                const tbody = document.querySelector('#tabela-info tbody');
                tbody.innerHTML = "";

                dados.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.cod_rastreio || ''}</td>
                        <td>${item.setor || ''}</td>
                        <td>${item.cnpj || ''}</td>
                        <td>${item.razao_social || ''}</td>
                        <td>${item.data_recebimento || ''}</td>
                        <td>${item.quantidade || ''}</td>
                        <td>${item.operacao_destino || ''}</td>
                        <td>${item.observacoes || ''}</td>
                    `;
                    row.onclick = () => preencherFormulario(item, row);
                    tbody.appendChild(row);
                });
            });
    }

    function preencherFormulario(item, row) {
        limparSelecaoTabela();
        row.classList.add('row-selected');

        document.getElementById('cod_rastreio').value     = item.cod_rastreio || '';
        document.getElementById('setor').value            = item.setor || '';
        document.getElementById('cnpj').value             = item.cnpj || '';
        document.getElementById('razao_social').value     = item.razao_social || '';
        document.getElementById('data_recebimento').value = item.data_recebimento || '';
        document.getElementById('nota_fiscal').value      = item.nota_fiscal || '';
        document.getElementById('quantidade').value       = item.quantidade || '';
        document.getElementById('operacao_origem').value  = item.operacao_destino || '';
        document.getElementById('obs').value              = item.observacoes || '';

        openPanelEdit();
    }

    // -----------------------------------------------
    // SUBMIT DO FORMULÁRIO
    // -----------------------------------------------
    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch(
                "/BackEnd/Recebimento/Recebimento.php",
                { method: "POST", body: formData }
            );

            const text = await res.text();
            let json;

            try {
                json = JSON.parse(text);
            } catch {
                alert("Resposta inválida do servidor.");
                return;
            }

            if (json.error) {
                alert(json.error);
                return;
            }

            if (json.success) {
                showSuccessModal(json.success);
                carregarTabelaRecebimento();
            }

            if (json.redirect) {
                redirectTo(json.redirect);
            }

        } catch (err) {
            console.error("Erro no envio:", err);
            alert("Erro ao salvar recebimento.");
        }
    });

    // -----------------------------------------------
    // FILTRO (RASTREIO / CNPJ)
    // -----------------------------------------------
    document
        .getElementById("filtro-rastreio-cnpj")
        .addEventListener("input", function () {

            const termo = this.value.toLowerCase().trim();
            const linhas = document.querySelectorAll("#tabela-info tbody tr");

            linhas.forEach(linha => {
                const rastreio = linha.cells[0].textContent.toLowerCase();
                const cnpj     = linha.cells[2].textContent.toLowerCase();
                linha.style.display =
                    rastreio.includes(termo) || cnpj.includes(termo) ? "" : "none";
            });
        });

});
</script>


</body>
</html>
