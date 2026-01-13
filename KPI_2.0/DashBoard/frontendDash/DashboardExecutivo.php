<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

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
    <title>Dashboard Executivo | Sunlab KPI 2.0</title>
    <link rel="stylesheet" href="cssDash/dashboard-executivo.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    
    <!-- Motor de Insights -->
    <script src="jsDash/insights-engine.js"></script>
</head>
<body>

    <!-- 🎯 HEADER EXECUTIVO -->
    <header class="executive-header">
        <div class="header-brand">
            <i class="fas fa-chart-network"></i>
            <div class="brand-text">
                <h1>Dashboard Executivo</h1>
                <span class="brand-subtitle">Sunlab KPI 2.0 - Visão Estratégica</span>
            </div>
        </div>
        
        <div class="header-controls">
            <!-- Seletor de Período -->
            <div class="period-selector">
                <button class="period-btn active" data-days="7">
                    <i class="fas fa-calendar-week"></i>
                    <span>7 dias</span>
                </button>
                <button class="period-btn" data-days="30">
                    <i class="fas fa-calendar-alt"></i>
                    <span>30 dias</span>
                </button>
                <button class="period-btn" data-days="90">
                    <i class="fas fa-calendar"></i>
                    <span>90 dias</span>
                </button>
            </div>
            
            <!-- Atualização -->
            <div class="update-info">
                <i class="fas fa-sync-alt"></i>
                <span id="lastUpdate">Carregando...</span>
            </div>
            
            <!-- Navegação -->
            <button class="btn-secondary" onclick="window.location.href='DashRecebimento.php'">
                <i class="fas fa-chart-line"></i> Relatórios
            </button>
            
            <button class="btn-back" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
        </div>
    </header>

    <!-- 🎯 ÁREA PRINCIPAL -->
    <main class="executive-canvas">
        
        <!-- ============================================
             CAMADA 1 - KPIs GLOBAIS
             ============================================ -->
        <section class="kpi-global-section">
            <div class="section-header">
                <h2><i class="fas fa-tachometer-alt"></i> Indicadores Globais</h2>
                <span class="section-subtitle">Visão geral da operação no período selecionado</span>
            </div>
            
            <div class="kpi-global-grid">
                <!-- KPI 1: Volume Processado -->
                <div class="kpi-global-card" data-navigate="volume">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #388bfd;">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Volume Processado</span>
                            <span class="kpi-period" id="volumePeriod">Últimos 7 dias</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="volumeTotal">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="volumeComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. média histórica</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="volumeDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 2: Tempo Médio Total -->
                <div class="kpi-global-card" data-navigate="tempo">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #11cfff;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Tempo Médio Total</span>
                            <span class="kpi-period">Ciclo completo</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="tempoMedioTotal">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="tempoComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="tempoDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 3: Taxa de Sucesso -->
                <div class="kpi-global-card" data-navigate="sucesso">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Taxa de Sucesso</span>
                            <span class="kpi-period">Reparos finalizados</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="taxaSucesso">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="sucessoComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. meta 85%</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="sucessoDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 4: Taxa Sem Conserto -->
                <div class="kpi-global-card" data-navigate="semconserto">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #f59e0b;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Taxa Sem Conserto</span>
                            <span class="kpi-period">% global</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="taxaSemConserto">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="semConsertoComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. média histórica</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="semConsertoDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 5: Valor Orçado -->
                <div class="kpi-global-card" data-navigate="financeiro">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #8b5cf6;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Valor Orçado</span>
                            <span class="kpi-period">Análise + Reparo</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="valorOrcado">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="valorComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="valorDetail">Carregando...</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             CAMADA 2 - INSIGHTS AUTOMATIZADOS
             ============================================ -->
        <section class="insights-section" id="insightsSection" style="display: none;">
            <div class="section-header">
                <div>
                    <h2><i class="fas fa-brain"></i> Insights do Sistema</h2>
                    <span class="section-subtitle">Exceções detectadas e recomendações acionáveis</span>
                </div>
                <button class="btn-ver-todos" onclick="abrirModalInsights()" title="Ver todos os insights">
                    <i class="fas fa-expand-alt"></i>
                    <span>Ver Todos</span>
                </button>
            </div>
            
            <div class="insights-grid" id="insightsGrid">
                <!-- Insights gerados automaticamente pelo motor de análise -->
            </div>
        </section>

        <!-- ============================================
             CAMADA 3 - FLUXO OPERACIONAL
             ============================================ -->
        <section class="flow-section">
            <div class="section-header">
                <h2><i class="fas fa-stream"></i> Fluxo Operacional</h2>
                <span class="section-subtitle">Tempo e volume por etapa do processo</span>
            </div>
            
            <div class="flow-grid">
                <!-- Tempo por Etapa -->
                <div class="flow-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-hourglass-half"></i> Tempo Médio por Etapa</h3>
                        <button class="widget-expand" onclick="navigateTo('tempo-detalhado')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartTempoEtapas"></canvas>
                    </div>
                </div>

                <!-- Volume por Etapa -->
                <div class="flow-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-chart-bar"></i> Volume por Etapa</h3>
                        <button class="widget-expand" onclick="navigateTo('volume-detalhado')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartVolumeEtapas"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             CAMADA 4 - QUALIDADE & CAUSA RAIZ
             ============================================ -->
        <section class="quality-section">
            <div class="section-header">
                <h2><i class="fas fa-clipboard-check"></i> Qualidade & Causa Raiz</h2>
                <span class="section-subtitle">Análise de falhas e recorrências</span>
            </div>
            
            <div class="quality-grid">
                <!-- Principais Laudos -->
                <div class="quality-widget widget-large">
                    <div class="widget-header">
                        <h3><i class="fas fa-file-medical"></i> Principais Laudos Recorrentes</h3>
                        <button class="widget-expand" onclick="navigateTo('laudos-detalhados')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartLaudos"></canvas>
                    </div>
                </div>

                <!-- Sem Conserto por Produto -->
                <div class="quality-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-ban"></i> Sem Conserto por Modelo</h3>
                        <button class="widget-expand" onclick="navigateTo('semconserto-detalhado')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartSemConserto"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================
             CAMADA 5 - FINANCEIRO INTELIGENTE
             ============================================ -->
        <section class="financial-section">
            <div class="section-header">
                <h2><i class="fas fa-chart-pie"></i> Financeiro Inteligente</h2>
                <span class="section-subtitle">Análise de custos e retorno</span>
            </div>
            
            <div class="financial-grid">
                <!-- Custo Médio por Produto -->
                <div class="financial-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-coins"></i> Custo Médio por Produto</h3>
                        <button class="widget-expand" onclick="navigateTo('custos-detalhados')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartCustos"></canvas>
                    </div>
                </div>

                <!-- Retorno por Cliente -->
                <div class="financial-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-building"></i> Top 5 Clientes (Valor)</h3>
                        <button class="widget-expand" onclick="navigateTo('clientes-detalhados')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartClientes"></canvas>
                    </div>
                </div>

                <!-- Orçamentos por Etapa -->
                <div class="financial-widget">
                    <div class="widget-header">
                        <h3><i class="fas fa-receipt"></i> Orçamentos por Etapa</h3>
                        <button class="widget-expand" onclick="navigateTo('orcamentos-detalhados')">
                            <i class="fas fa-expand-alt"></i>
                        </button>
                    </div>
                    <div class="widget-content">
                        <canvas id="chartOrcamentos"></canvas>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- ============================================
         MODAL OVERLAY - INSIGHTS EXPANDIDOS
         ============================================ -->
    <div class="insights-modal-overlay" id="insightsModal" style="display: none;">
        <div class="insights-modal">
            <div class="insights-modal-header">
                <h2><i class="fas fa-brain"></i> Todos os Insights do Sistema</h2>
                <button class="insights-modal-close" onclick="fecharModalInsights()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="insights-modal-content" id="insightsModalContent">
                <!-- Conteúdo carregado dinamicamente -->
            </div>
        </div>
    </div>

    <!-- ============================================
         JAVASCRIPT - ORQUESTRAÇÃO DE DADOS
         ============================================ -->
    <script>
    // 🎯 VARIÁVEIS GLOBAIS
    let periodoAtual = 7; // dias
    let dadosCache = {};
    let todosInsights = []; // Armazena TODOS os insights gerados

    // 🎯 INICIALIZAÇÃO
    document.addEventListener('DOMContentLoaded', function() {
        inicializarDashboard();
        configurarEventos();
    });

    function inicializarDashboard() {
        carregarDados(periodoAtual);
        atualizarTimestamp();
        setInterval(atualizarTimestamp, 60000); // Atualiza a cada minuto
    }

    function configurarEventos() {
        // Seletor de período
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                periodoAtual = parseInt(this.dataset.days);
                carregarDados(periodoAtual);
            });
        });

        // Fechar modal ao clicar fora
        document.getElementById('insightsModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalInsights();
            }
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModalInsights();
            }
        });

        // Navegação em KPIs
        document.querySelectorAll('.kpi-global-card').forEach(card => {
            card.addEventListener('click', function() {
                const destino = this.dataset.navigate;
                navigateTo(destino);
            });
        });
    }

    // 🎯 CARREGAMENTO DE DADOS
    async function carregarDados(dias) {
        try {
            // Calcular datas
            const dataFim = new Date();
            const dataInicio = new Date();
            dataInicio.setDate(dataFim.getDate() - dias);
            
            const dataInicioStr = dataInicio.toISOString().split('T')[0];
            const dataFimStr = dataFim.toISOString().split('T')[0];

            // Atualizar textos de período
            document.querySelectorAll('.kpi-period').forEach(el => {
                if (el.id === 'volumePeriod') {
                    el.textContent = `Últimos ${dias} dias`;
                }
            });

            // Carregar dados em paralelo
            await Promise.all([
                carregarKPIsGlobais(dataInicioStr, dataFimStr, dias),
                carregarFluxoOperacional(dataInicioStr, dataFimStr),
                carregarDadosQualidade(dataInicioStr, dataFimStr),
                carregarDadosFinanceiros(dataInicioStr, dataFimStr)
            ]);

            // Gerar insights automatizados após carregar todos os dados
            await gerarInsightsAutomatizados();

        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            mostrarErro('Erro ao carregar dados do dashboard');
        }
    }

    // 🎯 CAMADA 1 - KPIs GLOBAIS
    async function carregarKPIsGlobais(dataInicio, dataFim, dias) {
        // Este exemplo usa os endpoints existentes
        // Você deve ajustar conforme seus endpoints reais
        
        // Volume Processado (soma de todas as etapas)
        // Aqui você chamaria seus endpoints PHP existentes
        
        // EXEMPLO - DADOS SIMULADOS (substitua por chamadas reais)
        const volumeTotal = await obterVolumeTotalProcessado(dataInicio, dataFim);
        const tempoMedio = await obterTempoMedioCiclo(dataInicio, dataFim);
        const taxaSucesso = await obterTaxaSucesso(dataInicio, dataFim);
        const taxaSemConserto = await obterTaxaSemConserto(dataInicio, dataFim);
        const valorOrcado = await obterValorTotalOrcado(dataInicio, dataFim);

        // Atualizar KPIs na tela
        atualizarKPI('volume', volumeTotal);
        atualizarKPI('tempo', tempoMedio);
        atualizarKPI('sucesso', taxaSucesso);
        atualizarKPI('semconserto', taxaSemConserto);
        atualizarKPI('valor', valorOrcado);
    }

    function atualizarKPI(tipo, dados) {
        switch(tipo) {
            case 'volume':
                document.getElementById('volumeTotal').textContent = dados.total.toLocaleString('pt-BR');
                atualizarComparacao('volumeComparison', dados.variacao);
                document.getElementById('volumeDetail').textContent = `Média: ${dados.media.toLocaleString('pt-BR')} equip/dia`;
                break;
            
            case 'tempo':
                document.getElementById('tempoMedioTotal').textContent = formatarTempo(dados.total);
                atualizarComparacao('tempoComparison', dados.variacao);
                document.getElementById('tempoDetail').textContent = `Recebimento → Expedição`;
                break;
            
            case 'sucesso':
                document.getElementById('taxaSucesso').textContent = `${dados.percentual.toFixed(1)}%`;
                atualizarComparacao('sucessoComparison', dados.variacao);
                document.getElementById('sucessoDetail').textContent = `${dados.reparados} de ${dados.total} finalizados`;
                break;
            
            case 'semconserto':
                document.getElementById('taxaSemConserto').textContent = `${dados.percentual.toFixed(1)}%`;
                atualizarComparacao('semConsertoComparison', dados.variacao);
                document.getElementById('semConsertoDetail').textContent = `${dados.quantidade} equipamentos`;
                break;
            
            case 'valor':
                document.getElementById('valorOrcado').textContent = formatarMoeda(dados.total);
                atualizarComparacao('valorComparison', dados.variacao);
                document.getElementById('valorDetail').textContent = `Análise: ${formatarMoeda(dados.analise)} | Reparo: ${formatarMoeda(dados.reparo)}`;
                break;
        }
    }

    function atualizarComparacao(elementId, variacao) {
        const badge = document.getElementById(elementId);
        const icon = badge.querySelector('i');
        
        badge.className = 'comparison-badge';
        
        if (variacao > 0) {
            badge.classList.add('positive');
            icon.className = 'fas fa-arrow-up';
            badge.innerHTML = `<i class="fas fa-arrow-up"></i> +${variacao.toFixed(1)}%`;
        } else if (variacao < 0) {
            badge.classList.add('negative');
            icon.className = 'fas fa-arrow-down';
            badge.innerHTML = `<i class="fas fa-arrow-down"></i> ${variacao.toFixed(1)}%`;
        } else {
            badge.classList.add('neutral');
            icon.className = 'fas fa-minus';
            badge.innerHTML = `<i class="fas fa-minus"></i> 0%`;
        }
    }

    // 🎯 CAMADA 2 - INSIGHTS AUTOMATIZADOS
    async function gerarInsightsAutomatizados() {
        try {
            // Coletar dados de todas as camadas
            const dadosInsights = {
                volume: {
                    total: await obterVolumeTotalProcessado(),
                    media: 850, // Deve vir do histórico localStorage
                    variacao: 0 // calculado pela engine
                },
                tempo: {
                    etapas: {
                        recebimento: 2.5,
                        analise: 5.8,
                        reparo: 12.3,
                        qualidade: 3.2,
                        expedicao: 1.8
                    },
                    total: 25.6
                },
                qualidade: {
                    taxaSemConserto: 13.5,
                    totalSemConserto: 115,
                    laudosPorTipo: {
                        "Sem Defeito": 420,
                        "Sem Conserto": 115,
                        "Com Conserto": 315,
                        "Em Análise": 48
                    }
                },
                financeiro: {
                    custoMedio: 182,
                    valorOrcadoTotal: 195000,
                    variacaoCusto: 10.3,
                    variacaoReceita: -5.2
                },
                clienteProduto: {
                    topClientes: [
                        { nome: "TechCorp", volume: 340, taxaProblema: 18.2 },
                        { nome: "GlobalTech", volume: 280, taxaProblema: 12.1 },
                        { nome: "MegaStore", volume: 210, taxaProblema: 15.8 }
                    ],
                    topProdutos: [
                        { nome: "iPhone 12", volume: 185, taxaSemConserto: 21.6 },
                        { nome: "Samsung Galaxy S21", volume: 142, taxaSemConserto: 14.8 },
                        { nome: "Notebook Dell", volume: 128, taxaSemConserto: 16.4 }
                    ]
                }
            };

            // Gerar insights usando o motor
            const insights = gerarInsights(dadosInsights);

            // Armazenar TODOS os insights globalmente
            todosInsights = insights || [];

            // Renderizar insights (top 3)
            renderizarInsights(insights);

        } catch (error) {
            console.error('Erro ao gerar insights:', error);
            document.getElementById('insightsSection').style.display = 'none';
        }
    }

    function renderizarInsights(insights) {
        const insightsSection = document.getElementById('insightsSection');
        const insightsGrid = document.getElementById('insightsGrid');

        if (!insights || insights.length === 0) {
            insightsSection.style.display = 'none';
            todosInsights = [];
            return;
        }

        insightsSection.style.display = 'block';
        insightsGrid.innerHTML = '';

        // Máximo 3 insights (já vem priorizado da engine)
        insights.slice(0, 3).forEach(insight => {
            const card = criarCardInsight(insight);
            insightsGrid.appendChild(card);
        });
    }

    function criarCardInsight(insight) {
        const card = document.createElement('div');
        card.className = `insight-card insight-${insight.type}`;
        
        const iconMap = {
            'volume': 'chart-bar',
            'tempo': 'clock',
            'gargalo': 'hourglass-half',
            'qualidade': 'shield-alt',
            'financeiro': 'dollar-sign',
            'cliente': 'user-tie',
            'produto': 'box'
        };

        const icon = iconMap[insight.category] || 'lightbulb';

        card.innerHTML = `
            <div class="insight-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="insight-content">
                <div class="insight-header">
                    <span class="insight-category">
                        <i class="fas fa-tag"></i> ${insight.category.toUpperCase()}
                    </span>
                </div>
                <h4>${insight.title}</h4>
                <p>${insight.message}</p>
                ${insight.action ? `
                    <div class="insight-action" onclick="navigateTo('${insight.action.link}')">
                        ${insight.action.label} <i class="fas fa-arrow-right"></i>
                    </div>
                ` : ''}
            </div>
        `;

        return card;
    }

    // 🎯 MODAL DE INSIGHTS
    function abrirModalInsights() {
        const modal = document.getElementById('insightsModal');
        const modalContent = document.getElementById('insightsModalContent');
        
        if (!modal || !modalContent) return;

        // Limpar conteúdo anterior
        modalContent.innerHTML = '';

        // Verificar se há insights
        if (!todosInsights || todosInsights.length === 0) {
            modalContent.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                    <i class="fas fa-check-circle" style="font-size: 64px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3 style="margin-bottom: 10px;">Sistema Saudável</h3>
                    <p>Nenhuma exceção detectada no momento.</p>
                </div>
            `;
        } else {
            // Renderizar TODOS os insights
            todosInsights.forEach(insight => {
                const card = criarCardInsight(insight);
                card.style.marginBottom = '16px';
                modalContent.appendChild(card);
            });
        }

        // Mostrar modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevenir scroll da página
    }

    function fecharModalInsights() {
        const modal = document.getElementById('insightsModal');
        if (!modal) return;

        modal.style.display = 'none';
        document.body.style.overflow = ''; // Restaurar scroll da página
    }

    // 🎯 CAMADA 3 - FLUXO OPERACIONAL
    async function carregarFluxoOperacional(dataInicio, dataFim) {
        // Tempo por etapa
        const tempoEtapas = {
            recebimento: 2.5,
            analise: 5.8,
            reparo: 12.3,
            qualidade: 3.2,
            expedicao: 1.8
        };

        criarGraficoTempoEtapas(tempoEtapas);

        // Volume por etapa
        const volumeEtapas = {
            recebimento: 1250,
            analise: 1180,
            reparo: 980,
            qualidade: 950,
            expedicao: 920
        };

        criarGraficoVolumeEtapas(volumeEtapas);
    }

    function criarGraficoTempoEtapas(dados) {
        const ctx = document.getElementById('chartTempoEtapas');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Recebimento', 'Análise', 'Reparo', 'Qualidade', 'Expedição'],
                datasets: [{
                    label: 'Tempo Médio (dias)',
                    data: Object.values(dados),
                    backgroundColor: [
                        'rgba(56, 139, 253, 0.8)',
                        'rgba(17, 207, 255, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: [
                        'rgb(56, 139, 253)',
                        'rgb(17, 207, 255)',
                        'rgb(139, 92, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#e8f4ff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value) => value.toFixed(1) + 'd'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#a8c5e0' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: '#a8c5e0' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function criarGraficoVolumeEtapas(dados) {
        const ctx = document.getElementById('chartVolumeEtapas');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Recebimento', 'Análise', 'Reparo', 'Qualidade', 'Expedição'],
                datasets: [{
                    label: 'Volume',
                    data: Object.values(dados),
                    borderColor: 'rgb(56, 139, 253)',
                    backgroundColor: 'rgba(56, 139, 253, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: 'rgb(56, 139, 253)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'top',
                        align: 'top',
                        color: '#e8f4ff',
                        font: { weight: 'bold', size: 12 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#a8c5e0' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: '#a8c5e0' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 🎯 CAMADA 4 - QUALIDADE
    async function carregarDadosQualidade(dataInicio, dataFim) {
        // Implementar chamadas aos endpoints reais
        const laudos = {
            'Display danificado': 85,
            'Bateria viciada': 72,
            'Placa mãe oxidada': 45,
            'Sem sinal': 38,
            'Outros': 60
        };

        criarGraficoLaudos(laudos);

        const semConserto = {
            'iPhone 12': 18,
            'Samsung S21': 12,
            'iPhone 11': 10,
            'Motorola G9': 8,
            'Outros': 15
        };

        criarGraficoSemConserto(semConserto);
    }

    function criarGraficoLaudos(dados) {
        const ctx = document.getElementById('chartLaudos');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(dados),
                datasets: [{
                    data: Object.values(dados),
                    backgroundColor: [
                        'rgba(56, 139, 253, 0.8)',
                        'rgba(17, 207, 255, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#1a1f35'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: '#e8f4ff', padding: 15 }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 14 },
                        formatter: (value, ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = (value / total * 100).toFixed(1);
                            return percentage + '%';
                        }
                    }
                }
            }
        });
    }

    function criarGraficoSemConserto(dados) {
        const ctx = document.getElementById('chartSemConserto');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(dados),
                datasets: [{
                    label: '% Sem Conserto',
                    data: Object.values(dados),
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgb(245, 158, 11)',
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'right',
                        color: '#e8f4ff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value) => value + '%'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 20,
                        ticks: { color: '#a8c5e0' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        ticks: { color: '#a8c5e0' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 🎯 CAMADA 5 - FINANCEIRO
    async function carregarDadosFinanceiros(dataInicio, dataFim) {
        const custos = {
            'Display': 180,
            'Bateria': 95,
            'Placa': 320,
            'Câmera': 150,
            'Outros': 85
        };

        criarGraficoCustos(custos);

        const clientes = {
            'Cliente A': 45800,
            'Cliente B': 38500,
            'Cliente C': 32200,
            'Cliente D': 28900,
            'Cliente E': 24100
        };

        criarGraficoClientes(clientes);

        const orcamentos = {
            'Análise': 125400,
            'Reparo': 87600
        };

        criarGraficoOrcamentos(orcamentos);
    }

    function criarGraficoCustos(dados) {
        const ctx = document.getElementById('chartCustos');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(dados),
                datasets: [{
                    label: 'Custo Médio (R$)',
                    data: Object.values(dados),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#e8f4ff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value) => 'R$ ' + value
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: '#a8c5e0',
                            callback: (value) => 'R$ ' + value
                        },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    x: {
                        ticks: { color: '#a8c5e0' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function criarGraficoClientes(dados) {
        const ctx = document.getElementById('chartClientes');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: Object.keys(dados),
                datasets: [{
                    label: 'Valor (R$)',
                    data: Object.values(dados),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'right',
                        color: '#e8f4ff',
                        font: { weight: 'bold', size: 11 },
                        formatter: (value) => 'R$ ' + (value/1000).toFixed(1) + 'k'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { 
                            color: '#a8c5e0',
                            callback: (value) => 'R$ ' + (value/1000) + 'k'
                        },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        ticks: { color: '#a8c5e0' },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function criarGraficoOrcamentos(dados) {
        const ctx = document.getElementById('chartOrcamentos');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(dados),
                datasets: [{
                    data: Object.values(dados),
                    backgroundColor: [
                        'rgba(56, 139, 253, 0.8)',
                        'rgba(139, 92, 246, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#1a1f35'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e8f4ff', padding: 15 }
                    },
                    datalabels: {
                        color: '#fff',
                        font: { weight: 'bold', size: 14 },
                        formatter: (value) => 'R$ ' + (value/1000).toFixed(0) + 'k'
                    }
                }
            }
        });
    }

    // 🎯 FUNÇÕES AUXILIARES
    function formatarTempo(horas) {
        if (horas < 24) return `${horas.toFixed(1)}h`;
        const dias = Math.floor(horas / 24);
        return `${dias}d`;
    }

    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function atualizarTimestamp() {
        const agora = new Date();
        const texto = agora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        document.getElementById('lastUpdate').textContent = `Atualizado: ${texto}`;
    }

    function navigateTo(destino) {
        // Navegar para relatórios detalhados
        console.log('Navegar para:', destino);
        
        // Exemplo de navegação
        switch(destino) {
            case 'volume':
            case 'tempo':
            case 'tempo-detalhado':
            case 'volume-detalhado':
                window.location.href = 'DashRecebimento.php#recebimento';
                break;
            case 'semconserto':
            case 'semconserto-detalhado':
            case 'laudos-detalhados':
                window.location.href = 'DashRecebimento.php#qualidade';
                break;
            case 'financeiro':
            case 'custos-detalhados':
            case 'clientes-detalhados':
            case 'orcamentos-detalhados':
                window.location.href = 'DashRecebimento.php#financeiro';
                break;
        }
    }

    function mostrarErro(mensagem) {
        console.error(mensagem);
        // Implementar UI de erro se necessário
    }

    // 🎯 FUNÇÕES DE INTEGRAÇÃO COM BACKEND (PLACEHOLDER)
    // Substitua estas funções por chamadas reais aos seus endpoints PHP

    async function obterVolumeTotalProcessado(dataInicio, dataFim) {
        // Simular dados - substituir por fetch real
        return {
            total: 1250,
            media: 178,
            variacao: 12.5
        };
    }

    async function obterTempoMedioCiclo(dataInicio, dataFim) {
        return {
            total: 600, // horas
            variacao: -5.2
        };
    }

    async function obterTaxaSucesso(dataInicio, dataFim) {
        return {
            percentual: 87.5,
            reparados: 1094,
            total: 1250,
            variacao: 2.8
        };
    }

    async function obterTaxaSemConserto(dataInicio, dataFim) {
        return {
            percentual: 12.5,
            quantidade: 156,
            variacao: -2.8
        };
    }

    async function obterValorTotalOrcado(dataInicio, dataFim) {
        return {
            total: 213000,
            analise: 125400,
            reparo: 87600,
            variacao: 8.3
        };
    }
    </script>

</body>
</html>
