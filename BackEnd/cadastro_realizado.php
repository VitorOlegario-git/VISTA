<?php
session_start();

// Use apenas:
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


$tempo_limite = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

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
    <title>Cadastro Realizado</title>
    <style>
        body {
            background-color: #0a0a0a;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
            font-family: Arial, sans-serif;
            color: #00ffff;
            margin: 0;
        }

        h1 {
            color: #00ffff;
            font-size: 2rem;
            margin-top: 200px;
            text-align: center;
        }

        #video-fundo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            z-index: -1;
        }

        #botao-pular {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10;
            background-color: rgba(0, 0, 0, 0.6);
            border: 1px solid #00ffff;
            color: #00ffff;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #botao-pular:hover {
            background-color: rgba(0, 255, 255, 0.2);
        }
    </style>
</head>
<body>

<video id="video-fundo" autoplay muted playsinline>
    <source src="/localhost/BackEnd/video_cadastrado_pronto.mp4?nocache=<?php echo time(); ?>" type="video/mp4">
    Seu navegador não suporta vídeo.
</video>


<h1>Cadastro realizado com sucesso</h1>

<button id="botao-pular">Pular vídeo</button>

<script>
    const video = document.getElementById('video-fundo');
    const botaoPular = document.getElementById('botao-pular');

    let redirecionado = false;

    function redirecionar() {
        if (!redirecionado) {
            redirecionado = true;
            window.top.location.href = "/localhost/FrontEnd/html/PaginaPrincipal.php";
        }
    }

    // Redireciona ao terminar o vídeo
    video.addEventListener('ended', redirecionar);

    // Redireciona ao clicar no botão
    botaoPular.addEventListener('click', redirecionar);

    // Redirecionamento de segurança caso o vídeo não carregue
    setTimeout(redirecionar, 1000);

    // Captura erro de carregamento do vídeo
    video.addEventListener('error', (e) => {
        console.error("Erro ao carregar o vídeo", e);
        redirecionar();
    });
</script>


</body>
</html>
