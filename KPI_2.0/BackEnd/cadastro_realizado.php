<?php
require_once __DIR__ . '/helpers.php';

// Verifica sessão e define headers de segurança
verificarSessao();
definirHeadersSeguranca();

// Captura informações da sessão e contexto
$usuario = $_SESSION['username'] ?? 'Operador';
$dataHora = date('d/m/Y H:i:s');
$operacao = $_SESSION['ultima_operacao'] ?? 'Registro';
$identificador = $_SESSION['ultimo_id'] ?? null;
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
require_once __DIR__ . '/helpers.php';

// Verifica sessão e define headers de segurança
verificarSessao();
           CONFIRMAÇÃO DE REGISTRO OPERACIONAL - KPI 2.0

// Captura informações da sessão e contexto
$usuario = $_SESSION['username'] ?? 'Operador';
$dataHora = date('d/m/Y H:i:s');
$operacao = $_SESSION['ultima_operacao'] ?? 'Registro';
$identificador = $_SESSION['ultimo_id'] ?? null;
?>
           =============================================================== */

        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #111827;
            --bg-card: #1a1f35;
            
            --glass-light: rgba(255, 255, 255, 0.03);
            --glass-medium: rgba(255, 255, 255, 0.06);
            
            --border-subtle: rgba(56, 139, 253, 0.15);
            --border-medium: rgba(56, 139, 253, 0.25);
            
            --text-primary: #e8f4ff;
            --text-secondary: #a8c5e0;
            --text-muted: #6b8199;
            
            --accent-blue: #388bfd;
            --accent-green: #10b981;
            --success-glow: rgba(16, 185, 129, 0.2);
            
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.5);
            
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }
        @media (max-width: 600px) {
            .confirmation-container {
                padding: 0 2vw;
                max-width: 98vw;
            }
            .success-card, .summary-card, .flow-card, .actions-grid {
                padding: 18px 6px !important;
                font-size: 1rem;
            }
            .success-title { font-size: 1.2rem; }
            .success-icon { width: 56px; height: 56px; font-size: 32px; }
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 10%, rgba(56, 139, 253, 0.08), transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.06), transparent 55%);
            pointer-events: none;
            z-index: 0;
        }

        .confirmation-container {
            position: relative;
            z-index: 1;
            max-width: 700px;
            width: 100%;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ======== CARD DE SUCESSO ======== */
        .success-card {
            background: var(--glass-medium);
            border: 1px solid var(--border-medium);
            border-radius: 16px;
            padding: 48px 40px;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            margin-bottom: 24px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--accent-green), #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 8px 24px var(--success-glow);
            animation: scaleIn 0.5s ease-out 0.2s backwards;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }

        .success-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* ======== RESUMO DO REGISTRO ======== */
        .summary-card {
            background: var(--glass-light);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .summary-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .summary-label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .summary-value {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 600;
        }

        .summary-value.highlight {
            color: var(--accent-blue);
        }

        /* ======== FLUXO DO PROCESSO ======== */
        .flow-card {
            background: var(--glass-light);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 24px;
        }

        .flow-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .flow-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .flow-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .flow-step::after {
            content: "";
            position: absolute;
            top: 14px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border-subtle);
            z-index: 0;
        }

        .flow-step:last-child::after {
            display: none;
        }

        .flow-dot {
            width: 28px;
            height: 28px;
            margin: 0 auto 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .flow-step.completed .flow-dot {
            background: var(--accent-green);
            color: white;
        }

        .flow-step.current .flow-dot {
            background: var(--accent-blue);
            color: white;
            box-shadow: 0 0 20px rgba(56, 139, 253, 0.5);
        }

        .flow-step.pending .flow-dot {
            background: var(--glass-medium);
            border: 2px solid var(--border-subtle);
            color: var(--text-muted);
        }

        .flow-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .flow-step.current .flow-label {
            color: var(--accent-blue);
            font-weight: 600;
        }

        /* ======== BOTÕES DE AÇÃO ======== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .btn {
            padding: 16px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-blue), #2563eb);
            color: white;
            box-shadow: 0 4px 12px rgba(56, 139, 253, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(56, 139, 253, 0.4);
        }

        .btn-secondary {
            background: var(--glass-medium);
            border: 1px solid var(--border-medium);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--glass-light);
            border-color: var(--border-strong);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
        }

        .btn-outline:hover {
            border-color: var(--border-medium);
            color: var(--text-primary);
        }

        /* ======== RESPONSIVIDADE ======== */
        @media (max-width: 768px) {
            .success-card {
                padding: 36px 24px;
            }

            .success-icon {
                width: 64px;
                height: 64px;
                font-size: 36px;
            }

            .success-title {
                font-size: 24px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .flow-steps {
                flex-direction: column;
                gap: 16px;
            }

            .flow-step::after {
                display: none;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <!-- Card de Sucesso -->
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1 class="success-title">Registro Salvo com Sucesso</h1>
            <p class="success-subtitle">A operação foi concluída e os dados foram armazenados no sistema</p>
        </div>

        <!-- Resumo do Registro -->
        <div class="summary-card">
            <h2 class="summary-title">Resumo do Registro</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-label">Operação</span>
                    <span class="summary-value highlight"><?php echo htmlspecialchars($operacao); ?></span>
                </div>
                <?php if ($identificador): ?>
                <div class="summary-item">
                    <span class="summary-label">Identificador</span>
                    <span class="summary-value">#<?php echo htmlspecialchars($identificador); ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-item">
                    <span class="summary-label">Data e Hora</span>
                    <span class="summary-value"><?php echo $dataHora; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Usuário</span>
                    <span class="summary-value"><?php echo htmlspecialchars($usuario); ?></span>
                </div>
            </div>
        </div>

        <!-- Fluxo do Processo -->
        <div class="flow-card">
            <h2 class="flow-title">Fluxo do Processo</h2>
            <div class="flow-steps">
                <div class="flow-step completed">
                    <div class="flow-dot">✓</div>
                    <div class="flow-label">Recebimento</div>
                </div>
                <div class="flow-step current">
                    <div class="flow-dot">2</div>
                    <div class="flow-label">Análise</div>
                </div>
                <div class="flow-step pending">
                    <div class="flow-dot">3</div>
                    <div class="flow-label">Reparo</div>
                </div>
                <div class="flow-step pending">
                    <div class="flow-dot">4</div>
                    <div class="flow-label">Qualidade</div>
                </div>
                <div class="flow-step pending">
                    <div class="flow-dot">5</div>
                    <div class="flow-label">Expedição</div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="actions-grid">
            <button class="btn btn-primary" onclick="continuarEtapa()">
                <span>↻</span>
                <span>Continuar nesta Etapa</span>
            </button>
            <button class="btn btn-secondary" onclick="proximaEtapa()">
                <span>→</span>
                <span>Próxima Etapa</span>
            </button>
            <button class="btn btn-outline" onclick="voltarDashboard()">
                <span>⌂</span>
                <span>Voltar ao Dashboard</span>
            </button>
        </div>
    </div>

    <script>
        // Redireciona para o dashboard após 10 segundos (garante compatibilidade mobile)
        let autoRedirectTimer = setTimeout(() => {
            voltarDashboard();
        }, 10000);
        // Fallback para garantir redirecionamento mesmo se o setTimeout falhar (mobile edge cases)
        window.addEventListener('pageshow', function() {
            setTimeout(() => { voltarDashboard(); }, 12000);
        });

        function continuarEtapa() {
            clearTimeout(autoRedirectTimer);
            // Volta para a mesma etapa
            window.history.back();
        }

        function proximaEtapa() {
            clearTimeout(autoRedirectTimer);
            // Implementar lógica de próxima etapa baseada na operação atual
            voltarDashboard(); // Por enquanto volta ao dashboard
        }

        function voltarDashboard() {
            clearTimeout(autoRedirectTimer);
            window.top.location.href = "https://kpi.stbextrema.com.br/router_public.php?url=dashboard";
        }

        // Animação de entrada progressiva
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.success-card, .summary-card, .flow-card, .actions-grid');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease-out';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
