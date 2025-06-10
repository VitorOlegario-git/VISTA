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
    <title>Cadastro Expedicao</title>
    <link rel="stylesheet" href="../CSS/expedicao.css">
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
<div class="expedicao-container">
    <form id="form-expedicao">
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
            <label for="data_envio_expedicao">Data do envio para expedição</label>
            <input type="date" id="data_envio_expedicao" name="data_envio_expedicao">
        </div>
        <div class="input-group4">
            <label for="data_envio_cliente">Data do envio para o cliente</label>
            <input type="date" id="data_envio_cliente" name="data_envio_cliente">
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
            <label for="codigo_rastreio_envio">Cód. rastreio do envio</label>
            <input type="text" id="codigo_rastreio_envio" name="codigo_rastreio_envio" required placeholder="Código de rastreio do envio">
        </div>
        <div class="input-group8">
            <label for="operacao_origem">Operação Origem</label>
            <select id="operacao_origem" name="operacao_origem" required>
                <option value="">Selecione</option>
                <option value="envio_expedicao">Enviado para expedicao</option>
            </select>
        </div>
        <div class="input-group9">
            <label for="operacao_destino">Operação Destino</label>
            <select id="operacao_destino" name="operacao_destino" required>
                <option value="">Selecione</option>
                <option value="envio_cliente">Enviado para o cliente</option>
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
            <button type="button" id="btn-expedicao">Aguardando envio para o cliente</button>
            <button type="button" id="btn-enviado">Remessa enviada ao cliente</button>
        </div>

        <input type="text" id="filtro-nf" placeholder="Pesquisar por NF..." class="filtro-nf-input">

        <table id="tabela-info-aguardando-expedicao" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data do envio para expedição</th><th>Quantidade</th><th>Status</th>
                    <th>Numero da NF de retorno</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <table id="tabela-info-expedicao-concluida" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data do envio para o cliente</th><th>Quantidade</th><th>Status</th>
                    <th>Numero da NF de retorno</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

<script>
function voltarComReload() {
    // Redireciona e força o recarregamento
    window.location.href = "/localhost/FrontEnd/html/PaginaPrincipal.php?reload=" + new Date().getTime();
}

let dadosAguardandoExpedicao = [];
let dadosExpedido = [];

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("form-expedicao");
    const mensagemErro = document.getElementById("mensagemErro");
    const btnExpedicao = document.getElementById('btn-expedicao');
    const btnEnviado = document.getElementById('btn-enviado');
    const tabelaAguardandoExpedicao = document.getElementById('tabela-info-aguardando-expedicao');
    const tabelaExpedido = document.getElementById('tabela-info-expedicao-concluida');

    form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        try {
            const res = await fetch("http://localhost/BackEnd/Expedicao/Expedicao.php", {
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
            }
        } catch (err) {
            console.error("Erro ao converter para JSON:", err);
            mensagemErro.innerText = "Erro no formato da resposta do servidor.";
        }
    });

    function mostrarTabela(tabela) {
        tabelaAguardandoExpedicao.style.display = 'none';
        tabelaExpedido.style.display = 'none';
        tabela.style.display = 'table';
        tabela.querySelector('tbody').innerHTML = '';
    }

    function preencherInputs(item, tipo) {
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
    }

    function preencherTabelaAguardandoExpedicao(dados) {
        mostrarTabela(tabelaAguardandoExpedicao);
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
            row.addEventListener('click', () => preencherInputs(item, "aguardandoExpedicao"));
            tbody.appendChild(row);
        });
    }

    function preencherTabelaExpedido(dados) {
        mostrarTabela(tabelaExpedido);
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
            row.addEventListener('click', () => preencherInputs(item, "expedido"));
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
        const expedicao = await fetch("http://localhost/BackEnd/Expedicao/consulta_expedicao.php").then(res => res.json());
        const aguardando = await fetch("http://localhost/BackEnd/Expedicao/consulta_aguardando_envio.php").then(res => res.json());
        dadosExpedido = expedicao;
        dadosAguardandoExpedicao = aguardando;
        const filtrados = filtrarAguardando(aguardando, expedicao);
        preencherTabelaAguardandoExpedicao(filtrados);
    });

    btnEnviado.addEventListener('click', async () => {
        destacarBotao(btnEnviado);
        const dados = await fetch("http://localhost/BackEnd/Expedicao/consulta_expedicao.php").then(res => res.json());
        dadosExpedido = dados;
        preencherTabelaExpedido(dados);
    });

    btnExpedicao.click();

    document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();

    // Impede o uso de caracteres especiais perigosos
    if (!/^[\w\s\-./]*$/.test(termo)) return;

    const tabelas = [tabelaAguardandoExpedicao, tabelaExpedido];

    tabelas.forEach(tabela => {
        const linhas = tabela.querySelectorAll("tbody tr");
        linhas.forEach(linha => {
            const colunaNF = linha.cells[3];
            if (colunaNF && colunaNF.textContent.toLowerCase().includes(termo)) {
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
