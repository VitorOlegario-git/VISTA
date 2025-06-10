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
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Reparo</title>
    <link rel="stylesheet" href="../CSS/reparo.css">
    <link rel="icon" href="/localhost/FrontEnd/CSS/imagens/VISTA.png">

    <style>
        .button-group2 button.ativo {
            background-color: #1d3557;
            color: white;
            font-weight: bold;
            transform: scale(1.05);
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/CnpjMask.js"></script>
</head>
<body>
<div class="reparo-container">
    <form id="form-reparo">
        <div id="loading" style="display: none;"></div>
        <div id="mensagemErro"></div>

        <!-- Inputs -->
        <div class="input-group1">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" required oninput="applyCNPJMask(this);" maxlength="18" placeholder="Digite o CNPJ">
        </div>
        <div class="input-group2">
            <label for="nota_fiscal">NF</label>
            <input type="text" id="nota_fiscal" name="nota_fiscal" required placeholder="Nota fiscal de entrada">
        </div>
        <div class="input-group3">
            <label for="data_inicio_reparo">Data de início do reparo</label>
            <input type="date" id="data_inicio_reparo" name="data_inicio_reparo" required>
        </div>
        <div class="input-group4">
            <label for="data_solicitacao_nf">Data da solicitação da NF de retorno</label>
            <input type="date" id="data_solicitacao_nf" name="data_solicitacao_nf">
        </div>
        <div class="input-group5">
            <label for="razao_social">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" required placeholder="Razão Social do cliente">
        </div>
        <div class="input-group6">
            <label for="quantidade">Quantidade Total</label>
            <input type="number" id="quantidade" name="quantidade" required placeholder="Quantidade total de peças">
        </div>
        <div class="input-group7">
            <label for="op_parcial">Reparo Parcial?</label>
            <select name="sim_nao" id="sim_nao">
                <option value=""></option>
                <option value="sim">Sim</option>
                <option value="nao">Não</option>
            </select>
        </div>
        <div class="input-group8">
            <label for="quantidade_parcial">Quantidade Parcial</label>
            <input type="number" id="quantidade_parcial" name="quantidade_parcial" placeholder="Quantidade parcial analisada">
        </div>
        <div class="input-group9">
            <label for="acao">Ação</label>
            <select id="acao" name="acao" required>
                <option value=""></option>
                <option value="inicio">Início do reparo</option>
                <option value="fim">Fim do reparo</option>
            </select>
        </div>
        <div class="input-group10">
            <label for="numero_orcamento">Nº do orçamento</label>
            <input type="text" name="numero_orcamento" id="numero_orcamento">
        </div>
        <div class="input-group11">
            <label for="valor_orcamento">Valor do orçamento</label>
            <input type="text" name="valor_orcamento" id="valor_orcamento">
        </div>
        <div class="input-group12">
            <label for="operacao_origem">Operação Origem</label>
            <select id="operacao_origem" name="operacao_origem" required>
                <option value="">Selecione</option>
                <option value="aguardando_pg">Aguardando Pagamento</option>
                <option value="em_reparo">Em reparo</option>
            </select>
        </div>
        <div class="input-group13">
            <label for="operacao_destino">Operação Destino</label>
            <select id="operacao_destino" name="operacao_destino" required>
                <option value="">Selecione</option>
                <option value="em_reparo">Em reparo</option>
                <option value="aguardando_NF_retorno">Aguardando NF de retorno</option>
                <option value="estocado">Estocado</option>
                <option value="reparo_pendente">Reparo pendente</option>
            </select>
        </div>

        <div class="input-group16">
                <label for="setor">Setor</label>
                <i class="fas fa-industry"></i>
                <select id="setor" name="setor" required>
                    <option value="">Selecione o setor</option>
                    <option value="manut-varejo">Manutenção Varejo</option>
                    <option value="dev-varejo">Devolução Varejo</option>
                    <option value="manut-datora">Manutenção Datora</option>
                    <option value="manut-lumini">Manutenção Lumini</option>
                    <option value="dev-datora">Devolução Datora</option>
                    
                    <!--<option value="dev-lumini">Devolução Lumini</option>
                    -->
                </select>
            </div>
        <div class="input-group14">
            <label for="operador">Operador</label>
            <input type="text" id="operador" name="operador" value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
        </div>
        <div class="input-group15">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" rows="4"></textarea>
        </div>

        <div class="button-group">
            <button type="submit">Cadastrar</button>
            <button onclick="voltarComReload()">Voltar</button>
        </div>

    </form>

    <!-- Tabelas -->
    <div class="container-informacao">
        <div class="button-group2">
            <button type="button" id="btn-aguardando-pg">Aguardando pagamento</button>
            <button type="button" id="btn-em-reparo">Em reparo</button>
    </div>

        <input type="text" id="filtro-nf" placeholder="Pesquisar por NF..." class="filtro-nf-input">

        <table id="tabela-info-aguardando-pagamento" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data de envio do orçamento</th><th>Quantidade</th><th>Status</th>
                    <th>Numero do orçamento</th>
                    <th>Valor do orçamento</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <table id="tabela-info-em-reparo" style="display: none;">
            <thead>
                <tr>
                <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data do inicio do reparo</th><th>Quantidade</th><th>Quantidade Parcial</th><th>Status</th>
                    <th>Numero do orçamento</th>
                    <th>Valor do orçamento</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script>

function voltarComReload() {
    // Redireciona e força o recarregamento
    window.top.location.href = "/localhost/FrontEnd/html/PaginaPrincipal.php?reload=" + new Date().getTime();
}

let dadosAguardandoPg = [];
let dadosEmReparo = [];

document.addEventListener("DOMContentLoaded", function () {
    const acaoSelect = document.getElementById("acao");
    const inputNumero = document.querySelector("input[name='numero_orcamento']");
    const inputValor = document.querySelector("input[name='valor_orcamento']");
    const quantidadeParcial = document.getElementById("quantidade_parcial");
    const form = document.getElementById("form-reparo");
    const mensagemErro = document.getElementById("mensagemErro");

    const btnAguardando = document.getElementById('btn-aguardando-pg');
    const btnEmReparo = document.getElementById('btn-em-reparo');
    const tabelaAguardando = document.getElementById('tabela-info-aguardando-pagamento');
    const tabelaEmReparo = document.getElementById('tabela-info-em-reparo');

    acaoSelect.addEventListener("change", function () {
        const isFim = this.value === "fim";
        inputNumero.required = isFim;
        inputValor.required = isFim;
        quantidadeParcial.disabled = isFim;
        if (isFim) quantidadeParcial.value = "";
    });

   form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("http://localhost/BackEnd/Reparo/Reparo.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        try {
            const data = JSON.parse(text);

            if (data.success) {
                alert(data.success);
                form.reset();

                const cnpj = formData.get("cnpj");
                const nf = formData.get("nota_fiscal");

                if (data.acao === "inicio") {
                    window.top.location.href = window.top.location.origin +
                        "/localhost/FrontEnd/html/cadastro_excel_pos_analise.php?cnpj=" +
                        encodeURIComponent(cnpj) + "&nf_entrada=" + encodeURIComponent(nf);
                } else if (data.acao === "fim") {
                    window.top.location.href = "/localhost/BackEnd/cadastro_realizado.php";
                }
            } else if (data.error) {
                mensagemErro.innerHTML = `<p style='color:red;'>${data.error}</p>`;
            } else {
                mensagemErro.innerHTML = `<p style='color:red;'>Resposta inesperada: ${text}</p>`;
            }
        } catch (err) {
            mensagemErro.innerHTML = `<p style='color:red;'>Erro ao processar resposta: ${text}</p>`;
        }
    });
});


    function mostrarTabela(tabela) {
        tabelaAguardando.style.display = 'none';
        tabelaEmReparo.style.display = 'none';
        tabela.style.display = 'table';
        tabela.querySelector('tbody').innerHTML = '';
    }

    function preencherInputs(item, tipo) {
        document.querySelector('#cnpj').value = item.cnpj || '';
        document.querySelector('#razao_social').value = item.razao_social || '';
        document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
        document.querySelector("#setor").value = item.setor || '';
        const qtdTotal = parseInt(item.quantidade_total) || 0;
        const qtdParcial = parseInt(item.quantidade_reparada) || 0;

    // Lógica condicional aplicada no preenchimento do campo #quantidade
        document.querySelector('#quantidade').value = qtdParcial > 0 ? qtdParcial : qtdTotal;

    // Preenche também o campo de referência, se quiser exibir a original
        document.querySelector('#quantidade_parcial').value = qtdParcial;

        if (tipo === "aguardando") {
            document.querySelector('#numero_orcamento').value = item.numero_orcamento || '';
            document.querySelector('#valor_orcamento').value = item.valor_orcamento || '';
            document.querySelector('#operacao_origem').value = item.status || '';
        } else if (tipo === "reparo") {
            document.querySelector('#data_inicio_reparo').value = item.data_atualizacao ? item.data_atualizacao.split(" ")[0] : '';

            const qtdTotal = parseInt(item.quantidade_total) || 0;
            const qtdParcial = parseInt(item.quantidade_reparada) || 0;

    // Lógica condicional aplicada no preenchimento do campo #quantidade
            document.querySelector('#quantidade').value = qtdParcial > 0 ? qtdParcial : qtdTotal;

    // Preenche também o campo de referência, se quiser exibir a original
            document.querySelector('#quantidade_parcial').value = qtdParcial;

            document.querySelector('#operacao_origem').value = item.status || '';
            document.querySelector('#numero_orcamento').value = item.numero_orcamento || '';
            document.querySelector('#valor_orcamento').value = item.valor_orcamento || '';
        }

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
                <td>${item.data_atualizacao ? item.data_atualizacao.split(" ")[0] : ''}</td>
                <td>${item.quantidade_total || ''}</td>
                <td>${item.status || ''}</td>
                <td>${item.numero_orcamento || ''}</td>
                <td>${item.valor_orcamento || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "aguardando"));
            tbody.appendChild(row);
        });
    }

    function preencherTabelaEmReparo(dados) {
        mostrarTabela(tabelaEmReparo);
        const tbody = tabelaEmReparo.querySelector('tbody');
        dados.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = ` 
                <td>${item.setor || ''}</td>
                <td>${item.cnpj || ''}</td>
                <td>${item.razao_social || ''}</td>
                <td>${item.nota_fiscal || ''}</td>
                <td>${item.data_atualizacao ? item.data_atualizacao.split(" ")[0] : ''}</td>
                <td>${item.quantidade_total || ''}</td>
                <td>${item.quantidade_reparada || ''}</td>
                <td>${item.status || ''}</td>
                <td>${item.numero_orcamento || ''}</td>
                <td>${item.valor_orcamento || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "reparo"));
            tbody.appendChild(row);
        });
    }

    function destacarBotao(btn) {
        btnAguardando.classList.remove("ativo");
        btnEmReparo.classList.remove("ativo");
        btn.classList.add("ativo");
    }

    function filtrarAguardando(listaAguardando, listaReparo) {
        const chavesReparo = new Set(listaReparo.map(item => `${item.cnpj}-${item.nota_fiscal}`));
        return listaAguardando.filter(item => {
            const chave = `${item.cnpj}-${item.nota_fiscal}`;
            return !chavesReparo.has(chave);
        });
    }

    btnAguardando.addEventListener('click', () => {
        destacarBotao(btnAguardando);
        fetch("http://localhost/BackEnd/Reparo/consulta_reparo.php")
            .then(res => res.json())
            .then(reparo => {
                dadosEmReparo = reparo;
                fetch("http://localhost/BackEnd/Reparo/consulta_aguardando_pg.php")
                    .then(res => res.json())
                    .then(aguardando => {
                        dadosAguardando = aguardando;
                        const filtrados = filtrarAguardando(dadosAguardando, dadosEmReparo);
                        preencherTabelaAguardando(filtrados);
                    });
            });
    });

    btnEmReparo.addEventListener('click', () => {
        destacarBotao(btnEmReparo);
        fetch("http://localhost/BackEnd/Reparo/consulta_reparo.php")
            .then(res => res.json())
            .then(dados => {
                dadosEmReparo = dados;
                preencherTabelaEmReparo(dados);
            });
    });

    // Inicializa com "Aguardando análise" visível
    btnAguardando.click();

    // Filtro por NF
   document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();

    // Ignora termos inválidos com caracteres especiais que possam causar erro
    if (!/^[\w\s\-./]*$/.test(termo)) return;

    const tabelas = [
        document.getElementById("tabela-info-aguardando-pagamento"),
        document.getElementById("tabela-info-em-reparo")
    ];

    tabelas.forEach(tabela => {
        const linhas = tabela.querySelectorAll("tbody tr");
        linhas.forEach(linha => {
            const colunaNF = linha.cells[3];
            if (colunaNF && colunaNF.textContent.trim().toLowerCase() === termo) {
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
