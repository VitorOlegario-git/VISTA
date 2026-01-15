<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';

// Verifica sessão e define headers de segurança
verificarSessao();
definirHeadersSeguranca();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VISTA</title>
    <link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/PaginaPrincipal.css'); ?>">
    <link rel="icon" href="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
</head>

<body>

    <!-- APP HEADER CORPORATIVO -->
    <header class="app-header" id="logoHeader">
        <div class="app-header-content">
            <div class="app-header-left">
                <img src="<?php echo asset('FrontEnd/CSS/imagens/VISTA.png'); ?>" alt="VISTA Logo" class="app-logo">
                <div class="app-brand">
                    <span class="app-title">VISTA</span>
                    <span class="app-subtitle">Sistema de Gestão Integrada</span>
                </div>
            </div>
            <div class="app-header-right">
                <span class="app-user-greeting">Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <div class="app-user-avatar" id="userAvatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                </div>
                <button class="app-btn-logout" id="logoutBtn" title="Sair do sistema">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sair</span>
                </button>
            </div>
        </div>
    </header>

    <main class="main-container" id="menuPrincipal">
        <div class="content-wrapper">
            
            <div class="cards-grid">
                <!-- CARD EXCLUSIVO: INVENTÁRIO DE STATUS DE REMESSAS -->
                <?php if(isset($_SESSION['username']) && $_SESSION['username'] === 'Vitor Olegario'): ?>
                <div class="module-card" id="inventario-status-remessas">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <path d="M8 9h8M8 13h6M8 17h4"/>
                            </svg>
                            <h3 class="card-title">Inventário de Status de Remessas</h3>
                        </div>
                        <p class="card-description">Ferramenta restrita para validação e correção manual de status operacionais.</p>
                        <button class="card-action-btn" onclick="window.location.href='/inventario-status.php'">Acessar Inventário</button>
                    </div>
                </div>
                <?php endif; ?>
                <!-- 1. RECEBIMENTO -->
                <div class="module-card" id="recebimento">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                            <h3 class="card-title">Recebimento</h3>
                        </div>
                        <p class="card-description">Controle de entrada de equipamentos</p>
                    </div>
                </div>

                <!-- 2. ANÁLISE -->
                <div class="module-card" id="analise">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="20" x2="12" y2="10"/>
                                <line x1="18" y1="20" x2="18" y2="4"/>
                                <line x1="6" y1="20" x2="6" y2="16"/>
                            </svg>
                            <h3 class="card-title">Análise</h3>
                        </div>
                        <p class="card-description">Análise técnica de equipamentos</p>
                    </div>
                </div>

                <!-- 3. REPARO -->
                <div class="module-card" id="reparo">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                            <h3 class="card-title">Reparo</h3>
                        </div>
                        <p class="card-description">Gestão de reparos e manutenção</p>
                    </div>
                </div>

                <!-- 4. QUALIDADE -->
                <div class="module-card" id="qualidade">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <h3 class="card-title">Qualidade</h3>
                        </div>
                        <p class="card-description">Controle e validação de qualidade</p>
                    </div>
                </div>

                <!-- 5. EXPEDIÇÃO -->
                <div class="module-card" id="expedicao">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                            </svg>
                            <h3 class="card-title">Expedição</h3>
                        </div>
                        <p class="card-description">Gerenciamento e rastreamento de expedições</p>
                    </div>
                </div>

                <!-- 6. CONSULTA -->
                <div class="module-card" id="consulta">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                            <h3 class="card-title">Consulta</h3>
                        </div>
                        <p class="card-description">Busca e visualização de dados</p>
                    </div>
                </div>

                <!-- 7. RELATÓRIOS -->
                <?php if(in_array($_SESSION['username'], ['Vitor Olegario','petrius','will'])): ?>
                <div class="module-card" id="relatorio">
                    <div class="card-content">
                        <div class="card-header">
                            <svg class="card-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <line x1="9" y1="9" x2="15" y2="9"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                            <h3 class="card-title">Relatórios</h3>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <iframe id="conteudo-frame"></iframe>

    <footer>
        <p>© 2025 Suntech do Brasil. Todos os direitos reservados.</p>
    </footer>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const frame = document.getElementById("conteudo-frame");
    const menu = document.getElementById("menuPrincipal");
    const header = document.getElementById("logoHeader");

    // Gerar avatar com iniciais do usuário
    const userAvatar = document.getElementById("userAvatar");
    const userName = "<?php echo htmlspecialchars($_SESSION['username']); ?>";
    const initials = userName.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
    userAvatar.textContent = initials;

    // Animação de entrada dos cards
    const cards = document.querySelectorAll('.module-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });

    const openPage = url => {
        const isOpen = frame.style.display === "block";
        if (isOpen && frame.src.includes(url)) {
            frame.style.display = "none";
            frame.src = "";
            menu.style.display = "flex";
            header.style.display = "flex";
        } else {
            frame.src = url;
            frame.style.display = "block";
            menu.style.display = "none";
            header.style.display = "none";
        }
    };

    document.getElementById("analise").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/analise.php");
    document.getElementById("reparo").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/reparo.php");
    document.getElementById("qualidade").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/qualidade.php");
    document.getElementById("expedicao").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/expedicao.php");
    document.getElementById("consulta").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/consulta.php");
    document.getElementById("recebimento").onclick = () => openPage("https://kpi.stbextrema.com.br/FrontEnd/html/recebimento.php");

    const btnRel = document.getElementById('relatorio');
    if(btnRel){
        btnRel.onclick = () => location.href = "https://kpi.stbextrema.com.br/DashBoard/frontendDash/DashRecebimento.php";
    }

    // Botão de logout no header
    document.getElementById("logoutBtn").onclick = () =>
        location.href = "https://kpi.stbextrema.com.br/FrontEnd/tela_login.php?reload=" + Date.now();
});
</script>

</body>
</html>
