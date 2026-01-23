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
    <link rel="stylesheet" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/cadastro_excel_entrada.css">
    <link rel="icon" href="https://kpi.stbextrema.com.br/FrontEnd/CSS/imagens/VISTA.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/excelInput.js"></script>
    <script src="https://kpi.stbextrema.com.br/FrontEnd/JS/CnpjMask.js"></script> <!-- Caso você tenha máscara de CNPJ -->
</head>
<body>
<div class="excel-container">
    <!-- Cabeçalho Contextual -->
    <header class="import-header">
        <h1 class="import-title">Importação de Remessas de Entrada</h1>
        <p class="import-subtitle">Cadastre IMEIs e laudos através de planilha Excel estruturada</p>
    </header>

    <!-- Etapa 1: Identificação da Remessa -->
    <section class="import-section">
        <h2 class="section-title">
            <span class="section-number">1</span>
            Identificação da Remessa
        </h2>
        <div class="form-group">
            <label for="nf_entrada" class="input-label">NF de Entrada</label>
            <input type="text" id="nf_entrada" name="nf_entrada" placeholder="Digite a NF de entrada" required 
                value="<?php echo isset($_GET['nf_entrada']) ? htmlspecialchars($_GET['nf_entrada']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="cnpj" class="input-label">CNPJ do Cliente</label>
            <input class="input" type="text" id="cnpj" name="cnpj" maxlength="18" 
                oninput="applyCNPJMask(this);" 
                onkeyup="if (event.key === 'Enter') getIdApontamentos(this.value)" 
                placeholder="00.000.000/0000-00" required 
                value="<?php echo isset($_GET['cnpj']) ? htmlspecialchars($_GET['cnpj']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="entrada_id" class="input-label">ID da Entrada</label>
            <input type="text" id="entrada_id" name="entrada_id" readonly placeholder="ID será preenchido automaticamente">
        </div>
    </section>

    <!-- Etapa 2: Seleção do Arquivo -->
    <section class="import-section">
        <h2 class="section-title">
            <span class="section-number">2</span>
            Seleção do Arquivo Excel
        </h2>
        <div class="upload-area">
            <div class="upload-icon">📁</div>
            <label for="excel-file" class="btn-excel">Escolher Arquivo</label>
            <input type="file" id="excel-file" accept=".xlsx,.xls" style="display: none;">
            <span class="file-name" id="file-name-display">Nenhum arquivo selecionado</span>
            <p class="upload-hint">Formato aceito: .xlsx, .xls</p>
        </div>
        <button id="import-excel" class="btn-import">
            <span class="btn-icon">⬆️</span>
            Importar Excel
        </button>
    </section>

    <!-- Etapa 3: Pré-visualização -->
    <section class="import-section preview-section" id="preview-section" style="display: none;">
        <h2 class="section-title">
            <span class="section-number">3</span>
            Pré-visualização dos Dados
        </h2>
        <div class="preview-info">
            <span class="preview-count" id="row-count">0 registros carregados</span>
        </div>
        <div id="imei-list"></div>
        <table id="excel-data-table" border="1"></table>
    </section>

    <!-- Etapa 4: Confirmação -->
    <section class="import-section action-section" id="action-section" style="display: none;">
        <button id="save-to-database" class="btn-save">
            <span class="btn-icon">💾</span>
            <span class="btn-text">Cadastrar no Sistema</span>
            <span class="btn-loading" style="display: none;">
                <span class="spinner"></span>
                Processando...
            </span>
        </button>
    </section>
</div>

<script>
    function getIdApontamentos(cnpj) {
        const nf_entrada = $('#nf_entrada').val();

        $('#entrada_id').val('Consultando...');

        $.ajax({
            // Use public router to avoid 404 when server rewrite/root differs
            url: '/router_public.php?url=consulta/id',
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
