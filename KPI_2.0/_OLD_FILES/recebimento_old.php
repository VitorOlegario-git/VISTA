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
    <title>Cadastro Recebimento</title>
    <link rel="stylesheet" href="../CSS/recebimento.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/CnpjMask.js"></script>

</head>
<body>
<div id="loading" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">
    <i class="fas fa-spinner fa-spin"></i> Carregando...
</div>
    <div class="recebimento-container">
        <form action="https://kpi.stbextrema.com.br/BackEnd/Recebimento/Recebimento.php" method="POST">

            <div class="input-group1">
                <label for="cod_rastreio">Código do rastreio</label>
                <i class="fas fa-barcode"></i>
                <input type="text" id="cod_rastreio" name="cod_rastreio" required placeholder="Digite o código de rastreio">
            </div>

            <div class="input-group2">
                <label for="setor">Setor</label>
                <i class="fas fa-industry"></i>
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

            <div class="input-group3">
                <label for="cnpj">CNPJ</label>
                <i class="fas fa-building"></i>
                <input type="text" id="cnpj" name="cnpj" required oninput="applyCNPJMask(this);" maxlength="18" placeholder="Digite o CNPJ">
            </div>

            <div class="input-group4">
                <label for="razao_social">Razão Social</label>
                <i class="fas fa-user"></i>
                <input type="text" id="razao_social" name="razao_social" required placeholder="Razão Social">
            </div>

            <div class="input-group5">
                <label for="data_recebimento">Data de recebimento</label>
                <i class="fas fa-calendar-alt"></i>
                <input type="date" id="data_recebimento" name="data_recebimento">
            </div>

            <div class="input-group6">
                <label for="data_envio_analise">Data de envio para análise</label>
                <i class="fas fa-calendar-alt"></i>
                <input type="date" id="data_envio_analise" name="data_envio_analise">
            </div>

            

            <div class="input-group7">
                <label for="nota_fiscal">Nota fiscal</label>
                <i class="fas fa-file-invoice"></i>
                <input type="text" id="nota_fiscal" name="nota_fiscal" placeholder="Digite o número da nota fiscal">
            </div>

            <div class="input-group8">
                <label for="quantidade">Quantidade</label>
                <i class="fas fa-sort-numeric-up"></i>
                <input type="number" id="quantidade" name="quantidade" required placeholder="Digite a quantidade de peças">
            </div>

            <div class="input-group9">
                <label for="operacao_origem">Operação de Origem</label>
                <i class="fas fa-map-marker-alt"></i>
                <select name="operacao_origem" id="operacao_origem"> 
                    <option value="">Selecione a operação</option>
                    <option value="recebimento">Recebimento</option>
                    <option value="aguardando_nf">Aguardando Emissão de nota fiscal</option>
                    <option value="envio_analise">Enviado para analise</option>
                </select>
            </div>

            <div class="input-group10">
                <label for="operacao_destino">Operação de Destino</label>
                <i class="fas fa-map-marker-alt"></i>
                <select name="operacao_destino" id="operacao_destino">
                    <option value="">Selecione a operação</option>
                    <option value="recebimento">Recebimento</option>
                    <option value="aguardando_nf">Aguardando Emissão de nota fiscal</option>
                    <option value="envio_analise">Enviado para analise</option>
                    <option value="devolvido">Reenviado ao cliente</option>
                </select>
            </div>

            <div class="input-group11">
                <label for="operador">Operador</label>
                <i class="fas fa-user-cog"></i>
                <input type="text" id="operador" name="operador" value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
            </div>

            <div class="input-group12">
                <label for="obs">Observações</label>
                <i class="fas fa-comment"></i>
                <textarea id="obs" name="obs" rows="4" placeholder="Digite a observação"></textarea>
            </div>

            <div class="button-group">
               <button type="submit">Cadastrar</button>
               <button type="button" onclick="forcarRecarregamento()">Voltar</button>
            </div>

            <!-- Badge de Estado do Formulário -->
            <div class="form-mode-badge" id="form-mode-badge">
                <i class="fas fa-plus-circle"></i>
                <span>Modo Cadastro</span>
            </div>
        </form>

            <!--Modal de Sucesso--> 

            <div id="success-modal" class="modal">
               <div class="modal-content">
                   <i class="fas fa-check-circle"></i>
                   <p id="success-message">Cadastro realizado com sucesso!</p>
                   <button onclick="closeSuccessModal()">OK</button>
               </div>
            </div>

<div class="container-informacao">
     <!-- Título da Seção de Registros -->
     <div class="section-header">
         <i class="fas fa-list"></i>
         <h3>Registros Cadastrados</h3>
     </div>
    
    <!-- Campo de busca -->
    <input type="text" id="filtro-rastreio-cnpj" placeholder="Pesquisar por Código de Rastreio ou CNPJ..." class="filtro-input">

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
<script>
function forcarRecarregamento() {
    window.location.assign("https://kpi.stbextrema.com.br/router_public.php?url=dashboard&nocache=" + new Date().getTime());
}

function showSuccessModal(message) {
    document.getElementById("success-message").innerText = message;
    document.getElementById("success-modal").style.display = "block";
    setTimeout(closeSuccessModal, 3000);
}

function closeSuccessModal() {
    document.getElementById("success-modal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function () {
    const cnpjInput = document.getElementById("cnpj");

    if (cnpjInput) {
        cnpjInput.addEventListener("blur", function () {
            let cnpj = this.value.trim();
            if (cnpj.length === 18) {
                fetch("https://kpi.stbextrema.com.br/BackEnd/buscar_cliente.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "cnpj=" + encodeURIComponent(cnpj)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.encontrado) {
                        document.getElementById("razao_social").value = data.razao_social;
                    } else {
                        alert("Cliente não cadastrado. Você será redirecionado para o cadastro.");
                        window.location.href = "https://kpi.stbextrema.com.br/router_public.php?url=cadastrar-cliente&cnpj=" + encodeURIComponent(data.cnpj_usado);
                    }
                })
                .catch(error => console.error("Erro ao buscar cliente:", error));
            }
        });
    }

    // Consulta de recebimentos
    fetch("https://kpi.stbextrema.com.br/BackEnd/Recebimento/consulta_recebimento.php")
        .then(response => response.json())
        .then(dados => {
            const tbody = document.querySelector('#tabela-info tbody');
            tbody.innerHTML = ""; // limpa antes de inserir
            dados.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.cod_rastreio}</td>
                    <td>${item.setor}</td>
                    <td>${item.cnpj}</td>
                    <td>${item.razao_social}</td>
                    <td>${item.data_recebimento}</td>
                    <td>${item.quantidade}</td>
                    <td>${item.operacao_destino}</td>
                    <td>${item.observacoes}</td>
                `;
                row.addEventListener('click', () => preencherInputs(item));
                tbody.appendChild(row);
            });
        });

    function preencherInputs(item) {
        // Remove seleção anterior
        document.querySelectorAll('#tabela-info tbody tr').forEach(r => r.classList.remove('row-selected'));
        
        // Adiciona classe na linha clicada
        event.currentTarget.classList.add('row-selected');
        
        // Ativa modo de edição
        const badge = document.getElementById('form-mode-badge');
        badge.classList.add('editing-mode');
        badge.innerHTML = '<i class="fas fa-edit"></i><span>Modo Edição</span>';
        
        // Preenche os campos
        document.querySelector('#cod_rastreio').value = item.cod_rastreio;
        document.querySelector('#setor').value = item.setor;
        document.querySelector('#cnpj').value = item.cnpj;
        document.querySelector('#razao_social').value = item.razao_social;
        document.querySelector('#data_recebimento').value = item.data_recebimento;
        document.querySelector('#quantidade').value = item.quantidade;
        document.querySelector('#operacao_origem').value = item.operacao_destino;
        document.querySelector('#obs').value = item.observacoes;
    }

    document.getElementById("filtro-rastreio-cnpj").addEventListener("input", function () {
        const termo = this.value.toLowerCase();
        const linhas = document.querySelectorAll("#tabela-info tbody tr");

        linhas.forEach(linha => {
            const codRastreio = linha.cells[0].textContent.toLowerCase();
            const cnpj = linha.cells[2].textContent.toLowerCase();
            linha.style.display = codRastreio.includes(termo) || cnpj.includes(termo) ? "" : "none";
        });
    });
});
</script>

</body>
</html>
