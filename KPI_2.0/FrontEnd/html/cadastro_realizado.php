<?php
require_once __DIR__ . '/../../BackEnd/helpers.php';
// Segurança
verificarSessao();
definirHeadersSeguranca();
// Contexto
$usuario       = $_SESSION['username'] ?? 'Operador';
$dataHora      = date('d/m/Y H:i:s');
$operacao      = $_SESSION['ultima_operacao'] ?? 'Registro';
$identificador = $_SESSION['ultimo_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Registro - KPI 2.0</title>
    <style>
        :root {
            --bg-primary: #0b1220;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --glass-light: rgba(255,255,255,0.06);
            --glass-medium: rgba(255,255,255,0.12);
            --border-subtle: rgba(255,255,255,0.12);
            --border-medium: rgba(255,255,255,0.2);
            --border-strong: rgba(255,255,255,0.35);
            --transition: 0.25s ease;
            --shadow-lg: 0 20px 40px rgba(0,0,0,.4);
            --success-glow: rgba(16,185,129,.5);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .confirmation-container {
            width: 100%;
            max-width: 960px;
        }
        .success-card,
        .summary-card,
        .flow-card {
            background: var(--glass-light);
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
            backdrop-filter: blur(12px);
        }
        .success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-green), #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 8px 24px var(--success-glow);
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
        @media (max-width: 768px) {
            .success-card { padding: 36px 24px; }
            .success-icon { width: 64px; height: 64px; font-size: 36px; }
            .success-title { font-size: 24px; }
            .summary-grid { grid-template-columns: 1fr; gap: 16px; }
            .flow-steps { flex-direction: column; gap: 16px; }
            .actions-grid { grid-template-columns: 1fr; }
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
        let autoRedirectTimer = setTimeout(() => { voltarDashboard(); }, 10000);
        function continuarEtapa() { clearTimeout(autoRedirectTimer); window.history.back(); }
        function proximaEtapa() { clearTimeout(autoRedirectTimer); voltarDashboard(); }
        function voltarDashboard() { clearTimeout(autoRedirectTimer); window.top.location.href = '/router_public.php?url=dashboard'; }
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
