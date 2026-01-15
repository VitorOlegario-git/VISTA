<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$tempo_limite = 1200; // 20 minutos

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

if (!isset($_SESSION['username'])) {
    header("Location: https://kpi.stbextrema.com.br/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();

// Capturar área da URL
$area = isset($_GET['area']) ? $_GET['area'] : 'recebimento';

// Configurações por área
$config = [
    'recebimento' => [
        'titulo' => 'Recebimento',
        'icone' => 'fa-box-open',
        'cor' => '#3b82f6'
    ],
    'analise' => [
        'titulo' => 'Análise',
        'icone' => 'fa-microscope',
        'cor' => '#8b5cf6'
    ],
    'reparo' => [
        'titulo' => 'Reparo',
        'icone' => 'fa-tools',
        'cor' => '#f59e0b'
    ],
    'qualidade' => [
        'titulo' => 'Qualidade',
        'icone' => 'fa-check-circle',
        'cor' => '#10b981'
    ]
];

$areaConfig = $config[$area] ?? $config['recebimento'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $areaConfig['titulo'] ?> - Análise Detalhada | KPI 2.0</title>
    <link rel="stylesheet" href="cssDash/area-detalhada.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>

    <!-- 🧭 1. HEADER CONTEXTUAL FIXO -->
    <header class="context-header">
        <div class="header-navigation">
            <button class="btn-back" onclick="voltarDashboard()">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar ao Resumo Executivo</span>
            </button>
        </div>
        
        <div class="header-title-group">
            <div class="area-icon" style="background: linear-gradient(135deg, <?= $areaConfig['cor'] ?>22 0%, <?= $areaConfig['cor'] ?>44 100%); border: 2px solid <?= $areaConfig['cor'] ?>;">
                <i class="fas <?= $areaConfig['icone'] ?>" style="color: <?= $areaConfig['cor'] ?>;"></i>
            </div>
            <div class="title-content">
                <h1><?= $areaConfig['titulo'] ?> – Análise Detalhada</h1>
                <p class="subtitle" id="period-info">Período: Carregando...</p>
            </div>
        </div>
    </header>

    <!-- Container scrollable para todo o conteúdo -->
    <main>

    <!-- 📊 2. KPIs OPERACIONAIS DA ÁREA -->
    <section class="kpis-section">
        <div class="kpis-grid" id="kpis-container">
            <!-- Skeleton loading -->
            <div class="kpi-card skeleton">
                <div class="kpi-header skeleton-text"></div>
                <div class="kpi-value skeleton-text"></div>
                <div class="kpi-comparison skeleton-text"></div>
            </div>
            <div class="kpi-card skeleton">
                <div class="kpi-header skeleton-text"></div>
                <div class="kpi-value skeleton-text"></div>
                <div class="kpi-comparison skeleton-text"></div>
            </div>
            <div class="kpi-card skeleton">
                <div class="kpi-header skeleton-text"></div>
                <div class="kpi-value skeleton-text"></div>
                <div class="kpi-comparison skeleton-text"></div>
            </div>
            <div class="kpi-card skeleton">
                <div class="kpi-header skeleton-text"></div>
                <div class="kpi-value skeleton-text"></div>
                <div class="kpi-comparison skeleton-text"></div>
            </div>
        </div>
    </section>

    <!-- 🧠 3. INSIGHTS AUTOMÁTICOS DA ÁREA -->
    <section class="insights-section" id="insights-section" style="display: none;">
        <div class="section-header">
            <h2><i class="fas fa-lightbulb"></i> Insights Operacionais</h2>
        </div>
        <div class="insights-grid" id="insights-container"></div>
    </section>

    <!-- 📈 4. GRÁFICOS OPERACIONAIS -->
    <section class="charts-section">
        
        <!-- Bloco A - Evolução Temporal -->
        <div class="chart-block">
            <div class="chart-header">
                <h3><i class="fas fa-chart-line"></i> Evolução Temporal</h3>
                <p class="chart-description">Volume diário no período selecionado</p>
            </div>
            <div class="chart-container">
                <canvas id="chartEvolucao"></canvas>
            </div>
        </div>

        <!-- Bloco B - Distribuição -->
        <div class="charts-grid">
            <div class="chart-block">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Distribuição por Setor</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartSetor"></canvas>
                </div>
            </div>

            <div class="chart-block">
                <div class="chart-header">
                    <h3><i class="fas fa-exchange-alt"></i> Operações</h3>
                </div>
                <div class="chart-container">
                    <canvas id="chartOperacoes"></canvas>
                </div>
            </div>
        </div>

        <!-- Bloco C - Eficiência / Tempo -->
        <div class="chart-block">
            <div class="chart-header">
                <h3><i class="fas fa-hourglass-half"></i> Tempo Médio por Etapa</h3>
                <p class="chart-description">Análise de performance temporal</p>
            </div>
            <div class="chart-container">
                <canvas id="chartTempo"></canvas>
            </div>
        </div>

    </section>

    <!-- 📋 5. TABELA OPERACIONAL DETALHADA -->
    <section class="table-section">
        <div class="table-header">
            <div class="table-title">
                <h3><i class="fas fa-table"></i> Registros Operacionais</h3>
                <span class="record-count" id="record-count">0 registros</span>
            </div>
            <div class="table-controls">
                <input type="text" id="table-search" placeholder="🔍 Buscar..." class="search-input">
                <select id="table-sort" class="sort-select">
                    <option value="data_desc">Data (mais recente)</option>
                    <option value="data_asc">Data (mais antiga)</option>
                    <option value="quantidade_desc">Quantidade (maior)</option>
                    <option value="quantidade_asc">Quantidade (menor)</option>
                </select>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table" id="operational-table">
                <thead id="table-header">
                    <!-- Cabeçalhos dinâmicos por área -->
                </thead>
                <tbody id="table-body">
                    <!-- Dados carregados via JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="pagination" id="pagination"></div>
        </div>
    </section>

    <!-- Estado Vazio -->
    <div class="empty-state" id="empty-state" style="display: none;">
        <i class="fas fa-inbox"></i>
        <h3>Sem dados para o período selecionado</h3>
        <p>Ajuste o filtro de período no Dashboard Executivo</p>
        <button class="btn-primary" onclick="voltarDashboard()">
            <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
        </button>
    </div>

    </main>
    <!-- Fim do container scrollable -->

    <!-- JavaScript Principal -->
    <script>
        // Configuração global da área
        const AREA_ATUAL = '<?= $area ?>';
        const AREA_CONFIG = <?= json_encode($areaConfig) ?>;

        // Obter filtros da URL ou localStorage
        function obterFiltros() {
            const params = new URLSearchParams(window.location.search);
            const filtrosURL = {
                inicio: params.get('inicio'),
                fim: params.get('fim'),
                setor: params.get('setor'),
                operador: params.get('operador')
            };

            // Se não houver na URL, buscar do localStorage
            if (!filtrosURL.inicio || !filtrosURL.fim) {
                const filtrosLS = JSON.parse(localStorage.getItem('filtrosDashboard') || '{}');
                return {
                    inicio: filtrosURL.inicio || filtrosLS.inicio || obterDataPadrao(-30),
                    fim: filtrosURL.fim || filtrosLS.fim || obterDataPadrao(0),
                    setor: filtrosURL.setor || filtrosLS.setor || '',
                    operador: filtrosURL.operador || filtrosLS.operador || ''
                };
            }

            return filtrosURL;
        }

        function obterDataPadrao(diasOffset) {
            const data = new Date();
            data.setDate(data.getDate() + diasOffset);
            return data.toISOString().split('T')[0];
        }

        // Atualizar subtítulo com período
        function atualizarPeriodoInfo() {
            const filtros = obterFiltros();
            const inicioFormatado = new Date(filtros.inicio + 'T00:00:00').toLocaleDateString('pt-BR');
            const fimFormatado = new Date(filtros.fim + 'T00:00:00').toLocaleDateString('pt-BR');
            
            let info = `Período: ${inicioFormatado} a ${fimFormatado}`;
            
            if (filtros.operador) {
                info += ` | Operador: ${filtros.operador}`;
            } else {
                info += ' | Operador: Todos';
            }

            if (filtros.setor) {
                info += ` | Setor: ${filtros.setor}`;
            }

            document.getElementById('period-info').textContent = info;
        }

        // Navegar de volta ao dashboard
        function voltarDashboard() {
            const filtros = obterFiltros();
            const params = new URLSearchParams({
                inicio: filtros.inicio,
                fim: filtros.fim
            });
            if (filtros.setor) params.append('setor', filtros.setor);
            if (filtros.operador) params.append('operador', filtros.operador);
            
            window.location.href = `DashboardExecutivo.php?${params.toString()}`;
        }

        // Carregar todos os dados da área
        async function carregarDadosArea() {
            try {
                atualizarPeriodoInfo();
                
                // Carregar dados em paralelo
                await Promise.all([
                    carregarKPIs(),
                    carregarInsights(),
                    carregarGraficos(),
                    carregarTabelaOperacional()
                ]);

                console.log('✅ Dados da área carregados com sucesso');
            } catch (error) {
                console.error('❌ Erro ao carregar dados:', error);
                mostrarEstadoVazio();
            }
        }

        function mostrarEstadoVazio() {
            document.getElementById('empty-state').style.display = 'flex';
            document.querySelector('.kpis-section').style.display = 'none';
            document.querySelector('.charts-section').style.display = 'none';
            document.querySelector('.table-section').style.display = 'none';
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', () => {
            carregarDadosArea();
        });
    </script>

    <!-- JavaScript específico da área -->
    <script src="jsDash/area-detalhada-<?= $area ?>.js?v=1.0"></script>

</body>
</html>
