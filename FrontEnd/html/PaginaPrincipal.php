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
    <title>VISTA</title>
    <link rel="stylesheet" href="/localhost/FrontEnd/CSS/PaginaPrincipal.css">
    <link rel="icon" href="/localhost/FrontEnd/CSS/imagens/VISTA.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/localhost/FrontEnd/JS/CnpjMask.js"></script>
</head>
<body>
<div class="container-principal" id="container_principal" style="display: block">
    
    <div class="botao-wrapper botao-1">
        <button class="botao-circular" id="analise" style="background-image: url('/localhost/FrontEnd/CSS/imagens/analise.png');"></button>
        <div class="nome-botao">Análise</div>
    </div>
    <div class="botao-wrapper botao-2">
        <button class="botao-circular" id="reparo" style="background-image: url('/localhost/FrontEnd/CSS/imagens/reparo.png');"></button>
        <div class="nome-botao">Reparo</div>
    </div>
    <div class="botao-wrapper botao-3">
        <button class="botao-circular" id="qualidade" style="background-image: url('/localhost/FrontEnd/CSS/imagens/qualidade.png');"></button>
        <div class="nome-botao">Qualidade</div>
    </div>
    <div class="botao-wrapper botao-4">
        <button class="botao-circular" id="expedicao" style="background-image: url('/localhost/FrontEnd/CSS/imagens/expedicao.png');"></button>
        <div class="nome-botao">Expedição</div>
    </div>
    <div class="botao-wrapper botao-5">
        <button class="botao-circular" id="recebimento" style="background-image: url('/localhost/FrontEnd/CSS/imagens/botao_recebimento.png');"></button>
        <div class="nome-botao">Recebimento</div>
    </div>
    <div class="botao-wrapper botao-6">
        <button class="botao-circular" id="consulta" style="background-image: url('/localhost/FrontEnd/CSS/imagens/consultar.png');"></button>
        <div class="nome-botao">Consulta</div>
    </div>
    <div class="botao-wrapper botao-7">
        <button class="botao-circular" id="relatorio" style="background-image: url('/localhost/FrontEnd/CSS/imagens/kpi.png');"></button>
        <div class="nome-botao">Relatórios</div>
    </div>
    <div class="botao-wrapper botao-8">
        <button class="botao-circular" onclick="voltarComReload()" style="background-image: url('/localhost/FrontEnd/CSS/imagens/logout.png');" ></button>
        <div class="nome-botao" >Sair</div>
    </div>
</div>   

<!-- Iframe de conteúdo -->
<iframe id="conteudo-frame" src="" width="100%" height="600px" style="border: none; display: none;"></iframe>

<script>

function voltarComReload() {
    window.top.location.href = "/localhost/FrontEnd/tela_login.php?reload=" + new Date().getTime();
}

document.addEventListener("DOMContentLoaded", function () {
    const conteudoFrame = document.getElementById("conteudo-frame");
    const containerPrincipal = document.getElementById("container_principal");
    let isFrameAberto = false;
    let paginaAtual = "";

    function alternarFrame(pagina) {
        if (!isFrameAberto || paginaAtual !== pagina) {
            conteudoFrame.src = pagina;
            conteudoFrame.style.display = "block";
            containerPrincipal.style.display = "none"; // Esconde o menu principal
            isFrameAberto = true;
            paginaAtual = pagina;
        } else {
            conteudoFrame.style.display = "none";
            conteudoFrame.src = "";
            containerPrincipal.style.display = "block"; // Mostra o menu principal de volta
            isFrameAberto = false;
            paginaAtual = "";
        }
    }

    // Eventos dos botões
    document.getElementById("recebimento").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/recebimento.php");

    });

    document.getElementById("analise").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/analise.php");
    });

    document.getElementById("reparo").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/reparo.php");
    });

    document.getElementById("qualidade").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/qualidade.php");
    });

    document.getElementById("expedicao").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/expedicao.php");
    });
    document.getElementById("consulta").addEventListener("click", function () {
        alternarFrame("/localhost/FrontEnd/html/consulta.php");
    });
});

// Relatório com animação
document.getElementById('relatorio').addEventListener('click', function () {
    const relatorio = this;
    const body = document.body;

    const buttons = [
        document.getElementById('recebimento'),
        document.getElementById('analise'),
        document.getElementById('reparo'),
        document.getElementById('qualidade'),
        document.getElementById('expedicao')
    ];

    const intervalBetweenLines = 200;
    let currentIndex = 0;

    function createLine(target) {
        const relatorioRect = relatorio.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();

        const dx = targetRect.left + targetRect.width / 2 - (relatorioRect.left + relatorioRect.width / 2);
        const dy = targetRect.top + targetRect.height / 2 - (relatorioRect.top + relatorioRect.height / 2);
        const distance = Math.sqrt(dx * dx + dy * dy);
        const angle = Math.atan2(dy, dx) * 180 / Math.PI;

        const line = document.createElement('div');
        line.classList.add('line');
        line.style.width = '0px';
        line.style.left = `${relatorioRect.left + relatorioRect.width / 2}px`;
        line.style.top = `${relatorioRect.top + relatorioRect.height / 2}px`;
        line.style.transform = `rotate(${angle}deg)`;
        line.style.position = 'absolute';
        body.appendChild(line);

        setTimeout(() => {
            line.style.width = `${distance}px`;
            line.classList.add('visible');
        }, 100);
    }

    function animateLines() {
        if (currentIndex < buttons.length) {
            createLine(buttons[currentIndex]);
            currentIndex++;
            setTimeout(animateLines, intervalBetweenLines);
        } else {
            setTimeout(() => {
                relatorio.classList.add('loading');
            }, 100);

            setTimeout(() => {
                window.location.href = '/localhost/DashBoard/frontendDash/DashRecebimento.php';
            }, 5000);
        }
    }

    animateLines();


    const conteudoFrame = document.getElementById("conteudo-frame");
    const containerPrincipal = document.getElementById("container_principal");

});
</script>
</body>
</html>
