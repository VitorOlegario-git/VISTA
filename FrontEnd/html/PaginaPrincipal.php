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
    header("Location: /sistema/KPI_2.0/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /sistema/KPI_2.0/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();


?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VISTA</title>
    <link rel="stylesheet" href="/sistema/KPI_2.0/FrontEnd/CSS/PaginaPrincipal.css">
    <link rel="icon" href="/sistema/KPI_2.0/FrontEnd/CSS/imagens/VISTA.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/sistema/KPI_2.0/FrontEnd/JS/CnpjMask.js"></script>
</head>
<body>
    <div class="header" id="header">
        <a href="https://www.suntechdobrasil.com.br" target="_blank" class="link-clicavel"></a>
    </div>
<div class="container-principal" id="container_principal" style="display: block">
    
    <div class="botao-wrapper botao-1">
        <button class="botao-circular" id="analise" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/analise.png');"></button>
        <div class="nome-botao">Análise</div>
    </div>
    <div class="botao-wrapper botao-2">
        <button class="botao-circular" id="reparo" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/reparo.png');"></button>
        <div class="nome-botao">Reparo</div>
    </div>
    <div class="botao-wrapper botao-3">
        <button class="botao-circular" id="qualidade" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/qualidade.png');"></button>
        <div class="nome-botao">Qualidade</div>
    </div>
    <div class="botao-wrapper botao-4">
        <button class="botao-circular" id="expedicao" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/expedicao.png');"></button>
        <div class="nome-botao">Expedição</div>
    </div>
    <div class="botao-wrapper botao-5">
        <button class="botao-circular" id="recebimento" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/botao_recebimento.png');"></button>
        <div class="nome-botao">Recebimento</div>
    </div>
    <div class="botao-wrapper botao-6">
        <button class="botao-circular" id="consulta" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/consultar.png');"></button>
        <div class="nome-botao">Consulta</div>
    </div>
    <?php if (
       isset($_SESSION['username']) &&
       (
           $_SESSION['username'] === 'Vitor Olegario' ||
           $_SESSION['username'] === 'petrius' ||
           $_SESSION['username'] === 'will'
       )
    ): ?>
    <div class="botao-wrapper botao-7">
        <button class="botao-circular" id="relatorio" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/kpi.png');"></button>
        <div class="nome-botao">Relatórios</div>
    </div>
    <?php endif; ?>
    <div class="botao-wrapper botao-8">
        <button class="botao-circular" onclick="voltarComReload()" style="background-image: url('/sistema/KPI_2.0/FrontEnd/CSS/imagens/logout.png');" ></button>
        <div class="nome-botao" >Sair</div>
    </div>
</div>   

<!-- Iframe de conteúdo -->
<iframe id="conteudo-frame" src="" width="100%" height="600px" style="border: none; display: none;"></iframe>

<script>

function voltarComReload() {
    window.top.location.href = "/sistema/KPI_2.0/FrontEnd/tela_login.php?reload=" + new Date().getTime();
}

document.addEventListener("DOMContentLoaded", function () {
    const conteudoFrame = document.getElementById("conteudo-frame");
    const containerPrincipal = document.getElementById("container_principal");
    const pngHeader = document.getElementById('header');

    let isFrameAberto = false;
    let paginaAtual = "";

    function alternarFrame(pagina) {
        if (!isFrameAberto || paginaAtual !== pagina) {
            conteudoFrame.src = pagina;
            conteudoFrame.style.display = "block";
            containerPrincipal.style.display = "none"; // Esconde o menu principal
            pngHeader.style.display = "none";
            isFrameAberto = true;
            paginaAtual = pagina;
        } else {
            conteudoFrame.style.display = "none";
            conteudoFrame.src = "";
            containerPrincipal.style.display = "block"; // Mostra o menu principal de volta
            pngHeader.style.display = "none";
            isFrameAberto = false;
            paginaAtual = "";
        }
    }

    // Eventos dos botões
    document.getElementById("recebimento").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/recebimento.php");

    });

    document.getElementById("analise").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/analise.php");
    });

    document.getElementById("reparo").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/reparo.php");
    });

    document.getElementById("qualidade").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/qualidade.php");
    });

    document.getElementById("expedicao").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/expedicao.php");
    });
    document.getElementById("consulta").addEventListener("click", function () {
        alternarFrame("/sistema/KPI_2.0/FrontEnd/html/consulta.php");
    });
});

document.getElementById('relatorio').addEventListener('click', function () {
    window.location.href = '/sistema/KPI_2.0/DashBoard/frontendDash/DashRecebimento.php';
});




/// Bloqueia o clique direito
  //document.addEventListener('contextmenu', event => event.preventDefault());

  // Bloqueia F12, Ctrl+Shift+I, Ctrl+U, Ctrl+S etc.
  //document.onkeydown = function(e) {
   // if (e.key === "F12" || 
       // (e.ctrlKey && e.shiftKey && (e.key === "I" || e.key === "J")) || 
       // (e.ctrlKey && e.key === "U") || 
        //(e.ctrlKey && e.key === "S")) {
     // return false;
    //}
 // };
</script>
<footer>
    <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
</footer>
</body>
</html>
