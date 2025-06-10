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
    <title>Cadastro Qualidade</title>
    <link rel="stylesheet" href="../CSS/qualidade.css">
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
<div class="qualidade-container">
    <form id="form-qualidade">
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
            <label for="data_inicio_qualidade">Data do início da inspeção</label>
            <input type="date" id="data_inicio_qualidade" name="data_inicio_qualidade" required>
        </div>
        <div class="input-group4">
            <label for="data_envio_expedicao">Data do envio para expedição</label>
            <input type="date" id="data_envio_expedicao" name="data_envio_expedicao">
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
            <label for="quantidade_parcial">Quantidade Parcial</label>
            <input type="number" id="quantidade_parcial" name="quantidade_parcial" placeholder="Quantidade parcial analisada">
        </div>
        <div class="input-group8">
            <label for="operacao_origem">Operação Origem</label>
            <select id="operacao_origem" name="operacao_origem" required>
                <option value="">Selecione</option>
                <option value="aguardando_NF_retorno">Aguardando NF de retorno</option>
                <option value="inspecao_qualidade">Envio qualidade</option>
            </select>
        </div>
        <div class="input-group9">
            <label for="operacao_destino">Operação Destino</label>
            <select id="operacao_destino" name="operacao_destino" required>
                <option value="">Selecione</option>
                <option value="inspecao_qualidade">Envio qualidade</option>
                <option value="envio_expedicao">Enviado para expedicao</option>
            </select>
        </div>

        <div class="input-group10">
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
        <div class="input-group11">
            <label for="operador">Operador</label>
            <input type="text" id="operador" name="operador" value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
        </div>
        <div class="input-group12">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" rows="4"></textarea>
        </div>

        <div class="button-group">
            <button type="submit">Cadastrar</button>
            <button onclick="voltarComReload()">Voltar</button>
        </div>
        <div class="input-group13">
            <label for="nota_fiscal_retorno">NF de retorno</label>
            <input type="text" id="nota_fiscal_retorno" name="nota_fiscal_retorno" required placeholder="Nota fiscal de retorno">
        </div>
    </form>

    <!-- Tabelas -->
    <div class="container-informacao">
        <div class="button-group2">
            <button type="button" id="btn-aguardando-nf-retorno">Aguardando NF de retorno</button>
            <button type="button" id="btn-setor-qualidade">Setor de Qualidade</button>
    </div>

        <input type="text" id="filtro-nf" placeholder="Pesquisar por NF..." class="filtro-nf-input">

        <table id="tabela-info-aguardando-nf-retorno" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data da solicitação da NF</th><th>Quantidade</th><th>Quantidade Parcial</th><th>Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <table id="tabela-info-em-inspecao" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data do envio para a qualidade</th><th>Quantidade</th><th>Quantidade Parcial</th><th>Status</th>
                    <th>Numero da NF de retorno</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <script>

function voltarComReload() {
    // Redireciona e força o recarregamento
    window.top.location.href = "/localhost/FrontEnd/html/PaginaPrincipal.php?reload=" + new Date().getTime();
}

let dadosAguardandoNFRetorno = [];
let dadosQualidade = [];

document.addEventListener("DOMContentLoaded", function () {

    const inputNumero = document.querySelector("input[name='numero_orcamento']");
    const inputValor = document.querySelector("input[name='valor_orcamento']");
    const quantidadeParcial = document.getElementById("quantidade_parcial");
    const form = document.getElementById("form-qualidade");
    const mensagemErro = document.getElementById("mensagemErro");

    const btnAguardandoNfRetorno = document.getElementById('btn-aguardando-nf-retorno');
    const btnQualidade = document.getElementById('btn-setor-qualidade');
    const tabelaAguardando = document.getElementById('tabela-info-aguardando-nf-retorno');
    const tabelaQualidade = document.getElementById('tabela-info-em-inspecao');

    form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("http://localhost/BackEnd/Qualidade/Qualidade.php", {
        method: "POST",
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        console.log("RESPOSTA RAW:", text);
        try {
            const json = JSON.parse(text);
            console.log("JSON decodificado:", json);

            if (json.success && json.redirect) {
                window.location.href = json.redirect;
            } else if (json.error) {
                mensagemErro.innerText = json.error;
            }
        } catch (err) {
            console.error("Erro ao converter para JSON:", err);
            mensagemErro.innerText = "Erro no formato da resposta do servidor.";
        }
    })
    .catch(error => {
        console.error("Erro geral na requisição:", error);
        mensagemErro.innerText = "Erro na comunicação com o servidor.";
    });
});




    function mostrarTabela(tabela) {
        tabelaAguardando.style.display = 'none';
        tabelaQualidade.style.display = 'none';
        tabela.style.display = 'table';
        tabela.querySelector('tbody').innerHTML = '';
    }

    function preencherInputs(item, tipo) {
        document.querySelector('#cnpj').value = item.cnpj || '';
        document.querySelector('#razao_social').value = item.razao_social || '';
        document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
        document.querySelector("#setor").value = item.setor || '';
        document.querySelector('#quantidade').value = item.quantidade || '';
        document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';

        if (tipo === "aguardando") {
            document.querySelector('#operacao_origem').value = item.operacao_destino || '';
            document.querySelector('#data_inicio_qualidade').value = item.data_inicio_qualidade || '';
        } else if (tipo === "qualidade") {      
            document.querySelector('#data_inicio_qualidade').value = item.data_inicio_qualidade || '';
            document.querySelector('#quantidade').value = item.quantidade|| '';
            document.querySelector('#quantidade_parcial').value = item.quantidade_parcial|| '';
            document.querySelector('#operacao_origem').value = item.operacao_destino || '';
            document.querySelector('#nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';
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
                <td>${item.data_inicio_qualidade || ''}</td>
                <td>${item.quantidade || ''}</td>
                <td>${item.quantidade_parcial || ''}</td>
                <td>${item.operacao_destino || ''}</td>
            `;
            row.addEventListener('click', () => preencherInputs(item, "aguardando"));
            tbody.appendChild(row);
        });
    }

    function preencherTabelaQualidade(dados) {
        mostrarTabela(tabelaQualidade);
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
            row.addEventListener('click', () => preencherInputs(item, "qualidade"));
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

    btnAguardandoNfRetorno.addEventListener('click', () => {
        destacarBotao(btnAguardandoNfRetorno);
        fetch("http://localhost/BackEnd/Qualidade/consulta_qualidade.php")
            .then(res => res.json())
            .then(qualidade => {
                dadosQualidade = qualidade;
                fetch("http://localhost/BackEnd/Qualidade/consulta_aguardando_nf.php")
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
        fetch("http://localhost/BackEnd/Qualidade/consulta_qualidade.php")
            .then(res => res.json())
            .then(dados => {
                dadosQualidade = dados;
                preencherTabelaQualidade(dados);
            });
    });

    // Inicializa com "Aguardando análise" visível
    btnAguardandoNfRetorno.click();

    // Filtro por NF
   document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();

    // Ignora termos inválidos com caracteres especiais perigosos
    if (!/^[\w\s\-./]*$/.test(termo)) return;

    const tabelas = [
        document.getElementById("tabela-info-aguardando-nf-retorno"),
        document.getElementById("tabela-info-em-inspecao")
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