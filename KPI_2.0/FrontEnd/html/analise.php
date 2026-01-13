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
    <title>Análise - VISTA</title>
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/analise.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/CnpjMask.js"></script>
</head>
<body>

<!-- Overlay do Painel -->
<div class="panel-overlay" id="panel-overlay"></div>

<!-- Área Principal: Tabela -->
<div class="main-content">
    
    <!-- Header da Seção -->
    <div class="content-header">
        <div class="header-left">
            <i class="fas fa-microscope"></i>
            <h1>Análise de Equipamentos</h1>
        </div>
        <div class="header-actions">
            <button type="button" class="btn-secondary" onclick="voltarComReload()">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
            <button type="button" class="btn-new-record" id="btn-new-record">
                <i class="fas fa-plus"></i>
                <span>Nova Análise</span>
            </button>
        </div>
    </div>

    <!-- Container da Tabela -->
    <div class="table-section">
        <div class="table-controls">
            <input type="text" id="filtro-nf" placeholder="🔍 Pesquisar por NF..." class="search-input">
            
            <div class="table-toggle-buttons">
                <button type="button" class="btn-table-toggle" id="btn-aguardando-analise">
                    Aguardando Análise
                </button>
                <button type="button" class="btn-table-toggle" id="btn-em-analise">
                    Em Análise
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <!-- Tabela Aguardando Análise -->
            <table id="tabela-info-aguardando-analise" style="display: none;">
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th>CNPJ</th>
                        <th>Razão Social</th>
                        <th>NF</th>
                        <th>Quantidade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <!-- Tabela Em Análise -->
            <table id="tabela-info-em-analise" style="display: none;">
                <thead>
                    <tr>
                        <th>CNPJ</th>
                        <th>Razão Social</th>
                        <th>NF</th>
                        <th>Data Início Análise</th>
                        <th>Quantidade</th>
                        <th>Qtd. Parcial</th>
                        <th>Status</th>
                        <th>Setor</th>
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
            <h2 id="panel-title">Nova Análise</h2>
        </div>
        <button type="button" class="btn-close-panel" id="btn-close-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Corpo do Painel: Formulário -->
    <div class="panel-body">
        <div id="loading" style="display: none;">Carregando...</div>
        <div id="mensagemErro"></div>

        <form id="form-analise" action="https://kpi.stbextrema.com.br/BackEnd/Analise/Analise.php" method="POST">

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
                        <option value="dev-datora">Devolução Datora</option>
                        <option value="manut-datora">Manutenção Datora</option>
                        <option value="dev-lumini">Devolução Lumini</option>
                        <option value="manut-lumini">Manutenção Lumini</option>
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
                        <label for="data_inicio_analise">
                            <i class="fas fa-calendar-check"></i>
                            Início da Análise
                        </label>
                        <input type="date" id="data_inicio_analise" name="data_inicio_analise" required>
                    </div>

                    <div class="form-group">
                        <label for="data_envio_orcamento">
                            <i class="fas fa-calendar-alt"></i>
                            Encerramento da Análise
                        </label>
                        <input type="date" id="data_envio_orcamento" name="data_envio_orcamento">
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

                    <div class="form-group">
                        <label for="sim_nao">
                            <i class="fas fa-question-circle"></i>
                            Análise Parcial?
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
                    <input type="number" id="quantidade_parcial" name="quantidade_parcial" placeholder="Quantidade parcial analisada">
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
                        <option value="inicio">Início de Análise</option>
                        <option value="fim">Fim de Análise</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_orcamento">
                            <i class="fas fa-hashtag"></i>
                            Nº do Orçamento
                        </label>
                        <input type="text" name="numero_orcamento" placeholder="Número do orçamento">
                    </div>

                    <div class="form-group">
                        <label for="valor_orcamento">
                            <i class="fas fa-dollar-sign"></i>
                            Valor do Orçamento
                        </label>
                        <input type="text" name="valor_orcamento" placeholder="Valor do orçamento">
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
                        <option value="envio_analise">Enviado para análise</option>
                        <option value="em_analise">Em análise</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="operacao_destino">
                        <i class="fas fa-map-marker-alt"></i>
                        Operação Destino
                    </label>
                    <select id="operacao_destino" name="operacao_destino" required>
                        <option value="">Selecione</option>
                        <option value="em_analise">Em análise</option>
                        <option value="aguardando_pg">Análise finalizada</option>
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
// ==========================================
// CONTROLE DO PAINEL DESLIZANTE
// ==========================================

const sidePanel = document.getElementById('side-panel');
const btnNewRecord = document.getElementById('btn-new-record');
const btnClosePanel = document.getElementById('btn-close-panel');
const panelOverlay = document.getElementById('panel-overlay');
const panelTitle = document.getElementById('panel-title');
const panelIcon = document.getElementById('panel-icon');
const formAnalise = document.getElementById('form-analise');

let isPanelOpen = false;
let isEditMode = false;

function openPanelNew() {
    isEditMode = false;
    isPanelOpen = true;
    
    formAnalise.reset();
    
    sidePanel.classList.add('open');
    sidePanel.classList.remove('edit-mode');
    panelOverlay.classList.add('active');
    
    panelTitle.textContent = 'Nova Análise';
    panelIcon.className = 'fas fa-plus-circle';
    
    document.getElementById('operador').value = '<?php echo $_SESSION['username'] ?? ''; ?>';
    
    const campoDataInicio = document.querySelector('#data_inicio_analise');
    if (campoDataInicio) campoDataInicio.readOnly = false;
}

function openPanelEdit() {
    isEditMode = true;
    isPanelOpen = true;
    
    sidePanel.classList.add('open', 'edit-mode');
    panelOverlay.classList.add('active');
    
    panelTitle.textContent = 'Editando Análise';
    panelIcon.className = 'fas fa-edit';
}

function closePanel() {
    isPanelOpen = false;
    isEditMode = false;
    
    sidePanel.classList.remove('open', 'edit-mode');
    panelOverlay.classList.remove('active');
    
    document.querySelectorAll('table tbody tr').forEach(r => r.classList.remove('row-selected'));
}

btnNewRecord.addEventListener('click', openPanelNew);
btnClosePanel.addEventListener('click', closePanel);
panelOverlay.addEventListener('click', closePanel);

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && isPanelOpen) {
        closePanel();
    }
});

// ==========================================
// FUNÇÕES ORIGINAIS (PRESERVADAS)
// ==========================================

function voltarComReload() {
    // Redireciona e força o recarregamento
    window.top.location.href = "https://kpi.stbextrema.com.br/router_public.php?url=dashboard&reload=" + new Date().getTime();
}

let dadosAguardando = [];
let dadosEmAnalise = [];

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar máscara de CNPJ
    initializeCNPJMask();
    const acaoSelect = document.getElementById("acao");
    const parcialSelect = document.getElementById("sim_nao");
    const inputNumero = document.querySelector("input[name='numero_orcamento']");
    const inputValor = document.querySelector("input[name='valor_orcamento']");
    const quantidadeParcial = document.getElementById("quantidade_parcial");
    const form = document.getElementById("form-analise");
    const mensagemErro = document.getElementById("mensagemErro");
    const operacaoOrigem = document.getElementById("operacao_origem");
    const operacaoDestino = document.getElementById("operacao_destino");
    const dataEnvioOrcamento = document.getElementById("data_envio_orcamento");

    const btnAguardando = document.getElementById('btn-aguardando-analise');
    const btnEmAnalise = document.getElementById('btn-em-analise');
    const tabelaAguardando = document.getElementById('tabela-info-aguardando-analise');
    const tabelaEmAnalise = document.getElementById('tabela-info-em-analise');

    parcialSelect.addEventListener("change", function () {
        const isParcial = this.value === "sim";
        quantidadeParcial.required = isParcial;
    });

    acaoSelect.addEventListener("change", function(){
        const isInicio = this.value === "inicio";
        parcialSelect.required = isInicio;
    });

    acaoSelect.addEventListener("change", function () {
        const isFim = this.value === "fim";
        inputNumero.required = isFim;
        inputValor.required = isFim;
        dataEnvioOrcamento.required = isFim;
        quantidadeParcial.disabled = isFim;
        if (isFim) quantidadeParcial.value = "";
    });

    function atualizarOperacaoDestino() {
    const acao = acaoSelect.value;
    const origem = operacaoOrigem.value;

    // Limpa o destino e adiciona o "Selecione"
    operacaoDestino.innerHTML = '<option value="">Selecione</option>';

    if (acao === "inicio") {
        if (origem === "envio_analise") {
            operacaoDestino.innerHTML += '<option value="em_analise">Em análise</option>';
            return; // já adicionou, pode sair da função
        }
    }

    if (acao === "fim") {
        if (origem === "em_analise") {
            operacaoDestino.innerHTML += '<option value="aguardando_pg">Análise finalizada</option>';
            return;
        }
    }

    // Se não atender a nenhuma das regras específicas, mostra todas as opções
    const opcoes = [
        { value: "em_analise", text: "Em análise" },
        { value: "aguardando_pg", text: "Análise finalizada" },
        { value: "analise_pendente", text: "Análise pendente" }
    ];

    opcoes.forEach(opcao => {
        const opt = document.createElement("option");
        opt.value = opcao.value;
        opt.textContent = opcao.text;
        operacaoDestino.appendChild(opt);
    });
}

// Eventos que disparam a verificação
acaoSelect.addEventListener("change", atualizarOperacaoDestino);
operacaoOrigem.addEventListener("change", atualizarOperacaoDestino);


    form.addEventListener("submit", async function (e) {
    e.preventDefault();

    if (form.classList.contains("bloqueado")) {
        console.warn("Formulário já está sendo processado.");
        return;
    }

    form.classList.add("bloqueado"); // Evita múltiplos envios
    const formData = new FormData(form);
    mensagemErro.innerHTML = ""; // Limpa mensagens antigas

    try {
        const res = await fetch("https://kpi.stbextrema.com.br/BackEnd/Analise/Analise.php", {
            method: "POST",
            body: formData
        });

        const text = await res.text();
        console.log("Resposta bruta do servidor:", text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            throw new Error("Resposta inválida do servidor: " + text);
        }

        if (data.success) {
            alert(data.success);

            // Pequeno atraso para evitar conflitos com redirecionamentos
           setTimeout(() => {
              if (data.acao === "inicio") {
                const cnpj = encodeURIComponent(formData.get("cnpj"));
                const nf = encodeURIComponent(formData.get("nota_fiscal"));
                window.top.location.href = `https://kpi.stbextrema.com.br/router_public.php?url=cadastro-entrada&cnpj=${cnpj}&nf_entrada=${nf}`;
              } else if (data.acao === "fim") {
                window.top.location.href = "https://kpi.stbextrema.com.br/BackEnd/cadastro_realizado.php";
              }
           }, 200);

        } else if (data.error) {
            mensagemErro.innerHTML = `<p style='color:red;'>${data.error}</p>`;
        } else {
            throw new Error("Resposta inesperada do servidor.");
        }

    } catch (error) {
        console.error("Erro na submissão:", error);
        mensagemErro.innerHTML = `<p style='color:red;'>Erro: ${error.message}</p>`;
    } finally {
        form.classList.remove("bloqueado");
    }
});



    function mostrarTabela(tabela) {
        tabelaAguardando.style.display = 'none';
        tabelaEmAnalise.style.display = 'none';
        tabelaAguardando.querySelector('tbody').innerHTML = "";
        tabelaEmAnalise.querySelector('tbody').innerHTML = "";
        tabela.style.display = 'table';
    }

    function preencherInputs(item, tipo, row) {
        document.querySelectorAll('table tbody tr').forEach(r => r.classList.remove('row-selected'));
        
        if (row) row.classList.add('row-selected');
        
        document.querySelector('#cnpj').value = item.cnpj || '';
        document.querySelector('#razao_social').value = item.razao_social || '';
        document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
        document.querySelector("#setor").value = item.setor || '';
        document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';

        if (tipo === "aguardando") {
            document.querySelector('#quantidade').value = item.quantidade_total || '';
            document.querySelector('#operacao_origem').value = item.status || '';
        } else if (tipo === "analise") {
            const campoDataInicio = document.querySelector('#data_inicio_analise');
            campoDataInicio.value = item.data_inicio_analise ? item.data_inicio_analise.split(" ")[0] : '';
            campoDataInicio.readOnly = true;

            const qtdTotal = parseInt(item.quantidade_total || 0);
            const qtdParcial = parseInt(item.quantidade_parcial || 0);
            const campoQuantidade = document.querySelector('#quantidade');

            if (qtdParcial > 0 && qtdParcial !== qtdTotal) {
                campoQuantidade.value = qtdParcial;
            } else {
                campoQuantidade.value = qtdTotal;
            }

            document.querySelector('#operacao_origem').value = item.status || '';
        }
        
        openPanelEdit();
    }


    function preencherTabelaAguardando(dados) {
        mostrarTabela(tabelaAguardando);
        const tbody = tabelaAguardando.querySelector('tbody');
        dados.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.setor || ''}</td>
                <td>${item.cnpj || ''}</td>
                <td>${item.razao_social || ''}</td>
                <td>${item.nota_fiscal || ''}</td>
                <td>${item.quantidade_total || ''}</td>
                <td>${item.status || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "aguardando", row));
            tbody.appendChild(row);
        });
    }

    function preencherTabelaEmAnalise(dados) {
        mostrarTabela(tabelaEmAnalise);
        const tbody = tabelaEmAnalise.querySelector('tbody');
        dados.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = ` 
                <td>${item.cnpj || ''}</td>
                <td>${item.razao_social || ''}</td>
                <td>${item.nota_fiscal || ''}</td>
                <td>${item.data_inicio_analise ? item.data_inicio_analise.split(" ")[0] : ''}</td>
                <td>${item.quantidade_total || ''}</td>
                <td>${item.quantidade_parcial || ''}</td>
                <td>${item.status || ''}</td>
                <td>${item.setor || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "analise", row));
            tbody.appendChild(row);
        });
    }

    function destacarBotao(btn) {
        btnAguardando.classList.remove("ativo");
        btnEmAnalise.classList.remove("ativo");
        btn.classList.add("ativo");
    }

    function filtrarAguardando(listaAguardando, listaAnalise) {
        const chavesAnalise = new Set(listaAnalise.map(item => `${item.cnpj}-${item.nota_fiscal}`));
        return listaAguardando.filter(item => {
            const chave = `${item.cnpj}-${item.nota_fiscal}`;
            return !chavesAnalise.has(chave);
        });
    }

    btnAguardando.addEventListener('click', () => {
        destacarBotao(btnAguardando);
        fetch("https://kpi.stbextrema.com.br/BackEnd/Analise/consulta_analise.php")
            .then(res => res.json())
            .then(analise => {
                dadosEmAnalise = analise;
                fetch("https://kpi.stbextrema.com.br/BackEnd/Analise/consulta_aguardando_analise.php")
                    .then(res => res.json())
                    .then(aguardando => {
                        dadosAguardando = aguardando;
                        const filtrados = filtrarAguardando(dadosAguardando, dadosEmAnalise);
                        preencherTabelaAguardando(filtrados);
                    });
            });
    });

    btnEmAnalise.addEventListener('click', () => {
        destacarBotao(btnEmAnalise);
        fetch("https://kpi.stbextrema.com.br/BackEnd/Analise/consulta_analise.php")
            .then(res => res.json())
            .then(dados => {
                dadosEmAnalise = dados;
                preencherTabelaEmAnalise(dados);
            });
    });

    // Inicializa com "Aguardando análise" visível
    btnAguardando.click();

    // Filtro por NF
    document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();

    // Ignora buscas com seletores inválidos
    const seletorInvalido = /[^\w\s\-./]/.test(termo); // evita ^, *, $, etc
    if (seletorInvalido) {
        return;
    }

    const tabelas = [
        {
            tabela: document.getElementById("tabela-info-aguardando-analise"),
            colunaNF: 3
        },
        {
            tabela: document.getElementById("tabela-info-em-analise"),
            colunaNF: 2
        }
    ];

    tabelas.forEach(({ tabela, colunaNF }) => {
        const linhas = tabela.querySelectorAll("tbody tr");
        linhas.forEach(linha => {
            const coluna = linha.cells[colunaNF];
            if (coluna && coluna.textContent.trim().toLowerCase() === termo) {
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
