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
    header("Location:https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
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
    <title>Assistência Técnica - Cadastro de Laudos</title>
    <link rel="stylesheet" href="../CSS/cadastro_excel_entrada.css">
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/excelInput.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/CnpjMask.js"></script> <!-- Caso você tenha máscara de CNPJ -->
</head>
<body>
<div class="excel-container">
    <h3>Importar IMEIs e Laudos</h3>

    <input type="text" id="nf_entrada" name="nf_entrada" placeholder="Digite a NF de entrada" required 
        value="<?php echo isset($_GET['nf_entrada']) ? htmlspecialchars($_GET['nf_entrada']) : ''; ?>">

    <input class="input" type="text" id="cnpj" name="cnpj" maxlength="18" 
        oninput="applyCNPJMask(this);" 
        onkeyup="if (event.key === 'Enter') getIdApontamentos(this.value)" 
        placeholder="Digite o CNPJ" required 
        value="<?php echo isset($_GET['cnpj']) ? htmlspecialchars($_GET['cnpj']) : ''; ?>">

    <input type="text" id="entrada_id" name="entrada_id" readonly placeholder="ID será preenchido automaticamente">

    <label for="excel-file" class="btn-excel">📁 Selecionar arquivo Excel</label>
    <input type="file" id="excel-file" accept=".xlsx,.xls" style="display: none;">
    <button id="import-excel">Importar Excel</button>
    <button id="save-to-database">Cadastrar</button>

    <div id="imei-list"></div>
    <table id="excel-data-table" border="1"></table>
</div>

<script>
    function getIdApontamentos(cnpj) {
        const nf_entrada = $('#nf_entrada').val();

        $('#entrada_id').val('Consultando...');

        $.ajax({
            url: 'consulta_id.php',
            method: 'POST',
            dataType: 'json',
            data: {
                nf_entrada: nf_entrada,
                cnpj: cnpj
            },
            success: function (response) {
                if (response.id) {
                    $('#entrada_id').val(response.id);
                } else {
                    $('#entrada_id').val('');
                    alert("ID não encontrado para os dados informados.");
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro ao obter o ID de apontamentos:', error);
                $('#entrada_id').val('');
                alert("Erro ao consultar o ID no servidor.");
            }
        });
    }

    // Preenche automaticamente ao carregar a página
    $(document).ready(function () {
        const cnpj = $('#cnpj').val();
        if (cnpj) {
            getIdApontamentos(cnpj);
        }
    });
</script>
</body>
</html>
