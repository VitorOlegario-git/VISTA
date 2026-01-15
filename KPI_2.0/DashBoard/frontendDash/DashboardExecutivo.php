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
                <!-- KPI 1: Remessas Recebidas -->
                <div class="kpi-global-card" data-navigate="remessas">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #388bfd;">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Remessas Recebidas</span>
                            <span class="kpi-period" id="remessasPeriod">Últimos 7 dias</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="remessasTotal">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="remessasComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="remessasDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 2: Equipamentos Recebidos -->
                <div class="kpi-global-card" data-navigate="equipamentos-rec">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #11cfff;">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Equipamentos Recebidos</span>
                            <span class="kpi-period" id="equipRecPeriod">Últimos 7 dias</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="equipRecTotal">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="equipRecComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="equipRecDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 3: Equipamentos Expedidos -->
                <div class="kpi-global-card" data-navigate="equipamentos-exp">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #10b981;">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Equipamentos Expedidos</span>
                            <span class="kpi-period" id="equipExpPeriod">Últimos 7 dias</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="equipExpTotal">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="equipExpComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="equipExpDetail">Carregando...</span>
                    </div>
                </div>

                <!-- KPI 4: Taxa de Conclusão Técnica -->
                <div class="kpi-global-card" data-navigate="conclusao">
                    <div class="kpi-header">
                        <div class="kpi-icon" style="--icon-color: #f59e0b;">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="kpi-title">
                            <span class="kpi-label">Taxa de Conclusão</span>
                            <span class="kpi-period" id="conclusaoPeriod">Expedidos/Recebidos</span>
                        </div>
                    </div>
                    <div class="kpi-value" id="taxaConclusao">--</div>
                    <div class="kpi-comparison">
                        <span class="comparison-badge" id="conclusaoComparison">
                            <i class="fas fa-minus"></i> --
                        </span>
                        <span class="comparison-text">vs. período anterior</span>
                    </div>
                    <div class="kpi-footer">
                        <span class="kpi-detail" id="conclusaoDetail">Carregando...</span>
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
             CAMADA 2.5 - KPIs POR ÁREA
             ============================================ -->
        <section class="area-kpis-section">
            <div class="section-header">
                <h2><i class="fas fa-th-large"></i> KPIs por Área</h2>
                <span class="section-subtitle">Indicadores específicos por setor operacional</span>
            </div>
            
            <!-- Área: RECEBIMENTO -->
            <div class="area-kpis-container">
                <div class="area-header">
                    <h3><i class="fas fa-box-open"></i> Recebimento</h3>
                    <button class="btn-area-expand" onclick="navigateTo('recebimento')">
                        <i class="fas fa-external-link-alt"></i> Ver Detalhes
                    </button>
                </div>
                <div class="area-kpis-grid">
                    <!-- KPI 1: Backlog Atual -->
                    <div class="kpi-area-card" id="cardRecebimentoBacklog" onclick="navigateTo('recebimento')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Backlog Atual</span>
                            <div class="kpi-area-value" id="recebimentoBacklog">---</div>
                            <div class="kpi-area-comparison" id="recebimentoBacklogVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 2: Equipamentos Recebidos -->
                    <div class="kpi-area-card" id="cardRecebimentoRecebidos" onclick="navigateTo('recebimento')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Equipamentos Recebidos</span>
                            <div class="kpi-area-value" id="recebimentoRecebidos">---</div>
                            <div class="kpi-area-comparison" id="recebimentoRecebidosVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 3: Remessas Recebidas -->
                    <div class="kpi-area-card" id="cardRecebimentoRemessas" onclick="navigateTo('recebimento')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-dolly"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Remessas Recebidas</span>
                            <div class="kpi-area-value" id="recebimentoRemessas">---</div>
                            <div class="kpi-area-comparison" id="recebimentoRemessasVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 4: Taxa de Envio para Análise -->
                    <div class="kpi-area-card" id="cardRecebimentoTaxa" onclick="navigateTo('recebimento')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Taxa de Envio</span>
                            <div class="kpi-area-value" id="recebimentoTaxa">---</div>
                            <div class="kpi-area-comparison" id="recebimentoTaxaVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 5: Tempo Até Análise -->
                    <div class="kpi-area-card" id="cardRecebimentoTempo" onclick="navigateTo('recebimento')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-stopwatch"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Tempo Até Análise</span>
                            <div class="kpi-area-value" id="recebimentoTempo">---</div>
                            <div class="kpi-area-comparison" id="recebimentoTempoVariacao"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área: ANÁLISE -->
            <div class="area-kpis-container">
                <div class="area-header">
                    <h3><i class="fas fa-search"></i> Análise</h3>
                    <button class="btn-area-expand" onclick="navigateTo('analise')">
                        <i class="fas fa-external-link-alt"></i> Ver Detalhes
                    </button>
                </div>
                <div class="area-kpis-grid">
                    <!-- KPI 1: Equipamentos em Análise -->
                    <div class="kpi-area-card" id="cardAnaliseBacklog" onclick="navigateTo('analise')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Em Análise (Backlog)</span>
                            <div class="kpi-area-value" id="analiseBacklog">---</div>
                            <div class="kpi-area-comparison" id="analiseBacklogVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 2: Equipamentos Analisados -->
                    <div class="kpi-area-card" id="cardAnaliseAnalisados" onclick="navigateTo('analise')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Equipamentos Analisados</span>
                            <div class="kpi-area-value" id="analiseAnalisados">---</div>
                            <div class="kpi-area-comparison" id="analiseAnalisadosVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 3: Taxa de Conversão -->
                    <div class="kpi-area-card" id="cardAnaliseTaxaConversao" onclick="navigateTo('analise')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Taxa de Conversão</span>
                            <div class="kpi-area-value" id="analiseTaxaConversao">---</div>
                            <div class="kpi-area-comparison" id="analiseTaxaConversaoVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 4: Tempo Médio -->
                    <div class="kpi-area-card" id="cardAnaliseTempoMedio" onclick="navigateTo('analise')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Tempo Médio</span>
                            <div class="kpi-area-value" id="analiseTempoMedio">---</div>
                            <div class="kpi-area-comparison" id="analiseTempoMedioVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 5: Valor Orçado -->
                    <div class="kpi-area-card" id="cardAnaliseValorOrcado" onclick="navigateTo('analise')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Valor Orçado</span>
                            <div class="kpi-area-value" id="analiseValorOrcado">---</div>
                            <div class="kpi-area-comparison" id="analiseValorOrcadoVariacao"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área: REPARO -->
            <div class="area-kpis-container">
                <div class="area-header">
                    <h3><i class="fas fa-tools"></i> Reparo</h3>
                    <button class="btn-area-expand" onclick="navigateTo('reparo')">
                        <i class="fas fa-external-link-alt"></i> Ver Detalhes
                    </button>
                </div>
                <div class="area-kpis-grid">
                    <!-- KPI 1: Equipamentos em Reparo -->
                    <div class="kpi-area-card" id="cardReparoBacklog" onclick="navigateTo('reparo')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Em Reparo (Backlog)</span>
                            <div class="kpi-area-value" id="reparoBacklog">---</div>
                            <div class="kpi-area-comparison" id="reparoBacklogVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 2: Equipamentos Reparados -->
                    <div class="kpi-area-card" id="cardReparoReparados" onclick="navigateTo('reparo')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Equipamentos Reparados</span>
                            <div class="kpi-area-value" id="reparoReparados">---</div>
                            <div class="kpi-area-comparison" id="reparoReparadosVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 3: Taxa de Conversão -->
                    <div class="kpi-area-card" id="cardReparoTaxaConversao" onclick="navigateTo('reparo')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Taxa de Conversão</span>
                            <div class="kpi-area-value" id="reparoTaxaConversao">---</div>
                            <div class="kpi-area-comparison" id="reparoTaxaConversaoVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 4: Tempo Médio -->
                    <div class="kpi-area-card" id="cardReparoTempoMedio" onclick="navigateTo('reparo')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Tempo Médio</span>
                            <div class="kpi-area-value" id="reparoTempoMedio">---</div>
                            <div class="kpi-area-comparison" id="reparoTempoMedioVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 5: Valor Orçado -->
                    <div class="kpi-area-card" id="cardReparoValorOrcado" onclick="navigateTo('reparo')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Valor Orçado</span>
                            <div class="kpi-area-value" id="reparoValorOrcado">---</div>
                            <div class="kpi-area-comparison" id="reparoValorOrcadoVariacao"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Área: QUALIDADE -->
            <div class="area-kpis-container">
                <div class="area-header">
                    <h3><i class="fas fa-check-circle"></i> Qualidade</h3>
                    <button class="btn-area-expand" onclick="navigateTo('qualidade')">
                        <i class="fas fa-external-link-alt"></i> Ver Detalhes
                    </button>
                </div>
                <div class="area-kpis-grid">
                    <!-- KPI 1: Equipamentos Avaliados -->
                    <div class="kpi-area-card" id="cardQualidadeAvaliados" onclick="navigateTo('qualidade')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Equipamentos Avaliados</span>
                            <div class="kpi-area-value" id="qualidadeAvaliados">---</div>
                            <div class="kpi-area-comparison" id="qualidadeAvaliadosVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 2: Equipamentos Sem Conserto -->
                    <div class="kpi-area-card" id="cardQualidadeSemConserto" onclick="navigateTo('qualidade')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Sem Conserto</span>
                            <div class="kpi-area-value" id="qualidadeSemConserto">---</div>
                            <div class="kpi-area-comparison" id="qualidadeSemConsertoVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 3: Taxa de Qualidade -->
                    <div class="kpi-area-card" id="cardQualidadeTaxa" onclick="navigateTo('qualidade')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Taxa de Aprovação</span>
                            <div class="kpi-area-value" id="qualidadeTaxa">---</div>
                            <div class="kpi-area-comparison" id="qualidadeTaxaVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 4: Principais Causas -->
                    <div class="kpi-area-card" id="cardQualidadeCausas" onclick="navigateTo('qualidade')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Principal Causa</span>
                            <div class="kpi-area-value" id="qualidadeCausas" style="font-size: 0.85rem;">---</div>
                            <div class="kpi-area-comparison" id="qualidadeCausasVariacao"></div>
                        </div>
                    </div>

                    <!-- KPI 5: Modelos Reprovados -->
                    <div class="kpi-area-card" id="cardQualidadeModelos" onclick="navigateTo('qualidade')" style="cursor: pointer;">
                        <div class="kpi-area-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <div class="kpi-area-content">
                            <span class="kpi-area-label">Modelo Mais Reprovado</span>
                            <div class="kpi-area-value" id="qualidadeModelos" style="font-size: 0.85rem;">---</div>
                            <div class="kpi-area-comparison" id="qualidadeModelosVariacao"></div>
                        </div>
                    </div>
                </div>
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
                if (el.id === 'remessasPeriod' || el.id === 'equipRecPeriod' || el.id === 'equipExpPeriod') {
                    el.textContent = `Últimos ${dias} dias`;
                }
            });

            // Carregar dados em paralelo
            await Promise.all([
                carregarKPIsGlobais(dataInicioStr, dataFimStr, dias),
                carregarKPIsRecebimento(dataInicioStr, dataFimStr),
                carregarKPIsAnalise(dataInicioStr, dataFimStr),
                carregarKPIsReparo(dataInicioStr, dataFimStr),
                carregarKPIsQualidade(dataInicioStr, dataFimStr),
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
        try {
            // Chamar os 5 KPIs do Catálogo Oficial v1.0
            const [remessas, equipRec, equipExp, taxaConclusao, valorOrcado] = await Promise.all([
                obterRemessasRecebidas(dataInicio, dataFim),
                obterEquipamentosRecebidos(dataInicio, dataFim),
                obterEquipamentosExpedidos(dataInicio, dataFim),
                obterTaxaConclusao(dataInicio, dataFim),
                obterValorTotalOrcado(dataInicio, dataFim)
            ]);

            // Armazenar KPIs globalmente para motor de insights
            window.kpisGlobais = {
                remessas: remessas,
                equipRec: equipRec,
                equipExp: equipExp,
                conclusao: taxaConclusao,
                valor: valorOrcado
            };

            // Atualizar KPIs na tela
            atualizarKPI('remessas', remessas);
            atualizarKPI('equipRec', equipRec);
            atualizarKPI('equipExp', equipExp);
            atualizarKPI('conclusao', taxaConclusao);
            atualizarKPI('valor', valorOrcado);
        } catch (error) {
            console.error('Erro ao carregar KPIs:', error);
        }
    }

    function atualizarKPI(tipo, dados) {
        if (!dados) return;
        
        switch(tipo) {
            case 'remessas':
                document.getElementById('remessasTotal').textContent = dados.valor.toLocaleString('pt-BR');
                atualizarComparacao('remessasComparison', dados.referencia?.variacao || 0);
                const mediaRemessas = dados.detalhes?.media_dia || 0;
                document.getElementById('remessasDetail').textContent = `Média: ${mediaRemessas.toFixed(1)} remessas/dia`;
                break;
            
            case 'equipRec':
                document.getElementById('equipRecTotal').textContent = dados.valor.toLocaleString('pt-BR');
                atualizarComparacao('equipRecComparison', dados.referencia?.variacao || 0);
                const mediaEquipRec = dados.detalhes?.media_dia || 0;
                document.getElementById('equipRecDetail').textContent = `Média: ${mediaEquipRec.toFixed(1)} equipamentos/dia`;
                break;
            
            case 'equipExp':
                document.getElementById('equipExpTotal').textContent = dados.valor.toLocaleString('pt-BR');
                atualizarComparacao('equipExpComparison', dados.referencia?.variacao || 0);
                const mediaEquipExp = dados.detalhes?.media_dia || 0;
                document.getElementById('equipExpDetail').textContent = `Média: ${mediaEquipExp.toFixed(1)} equipamentos/dia`;
                break;
            
            case 'conclusao':
                document.getElementById('taxaConclusao').textContent = `${dados.valor}%`;
                const variacaoPP = dados.referencia?.variacao || 0;
                atualizarComparacaoPP('conclusaoComparison', variacaoPP);
                const recebidos = dados.detalhes?.recebidos || 0;
                const expedidos = dados.detalhes?.expedidos || 0;
                document.getElementById('conclusaoDetail').textContent = `${expedidos} expedidos de ${recebidos} recebidos`;
                break;
            
            case 'valor':
                document.getElementById('valorOrcado').textContent = `R$ ${dados.valor}`;
                atualizarComparacao('valorComparison', dados.referencia?.variacao || 0);
                const analise = dados.detalhes?.analise || '0,00';
                const reparo = dados.detalhes?.reparo || '0,00';
                document.getElementById('valorDetail').textContent = `Análise: R$ ${analise} | Reparo: R$ ${reparo}`;
                break;
        }
    }

    function atualizarComparacao(elementId, variacao) {
        const badge = document.getElementById(elementId);
        if (!badge) return;
        
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

    function atualizarComparacaoPP(elementId, variacaoPP) {
        const badge = document.getElementById(elementId);
        if (!badge) return;
        
        const icon = badge.querySelector('i');
        badge.className = 'comparison-badge';
        
        if (variacaoPP > 0) {
            badge.classList.add('positive');
            icon.className = 'fas fa-arrow-up';
            badge.innerHTML = `<i class="fas fa-arrow-up"></i> +${variacaoPP.toFixed(1)}pp`;
        } else if (variacaoPP < 0) {
            badge.classList.add('negative');
            icon.className = 'fas fa-arrow-down';
            badge.innerHTML = `<i class="fas fa-arrow-down"></i> ${variacaoPP.toFixed(1)}pp`;
        } else {
            badge.classList.add('neutral');
            icon.className = 'fas fa-minus';
            badge.innerHTML = `<i class="fas fa-minus"></i> 0pp`;
        }
    }

    // 🎯 CAMADA 2 - INSIGHTS AUTOMATIZADOS v2.0
    async function gerarInsightsAutomatizados() {
        try {
            // Aguardar carregamento dos KPIs globais
            if (!window.kpisGlobais) {
                console.warn('KPIs globais ainda não carregados');
                document.getElementById('insightsSection').style.display = 'none';
                return;
            }

            // Passar dados dos KPIs oficiais para o motor
            const insights = insightsEngine.analisar(window.kpisGlobais);

            // Armazenar TODOS os insights globalmente
            todosInsights = insights || [];

            // Renderizar insights (máximo 3)
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
        
        // Mapeamento de ícones por tipo de insight v2.0
        const iconMap = {
            'gargalo': 'hourglass-half',
            'eficiencia': 'tachometer-alt',
            'crescimento': 'chart-line',
            'operacao': 'check-circle',
            'critical': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };

        const icon = iconMap[insight.tipo] || iconMap[insight.type] || 'lightbulb';

        // Estrutura de card v2.0 com causa e ação
        card.innerHTML = `
            <div class="insight-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="insight-content">
                <div class="insight-header">
                    <span class="insight-category">
                        <i class="fas fa-tag"></i> ${insight.tipo?.toUpperCase() || insight.type?.toUpperCase() || 'INSIGHT'}
                    </span>
                </div>
                <h4>${insight.title}</h4>
                <p class="insight-message">${insight.message}</p>
                ${insight.causa ? `
                    <p class="insight-causa">
                        <strong><i class="fas fa-search"></i> Causa Provável:</strong> ${insight.causa}
                    </p>
                ` : ''}
                ${insight.acao ? `
                    <p class="insight-acao">
                        <strong><i class="fas fa-wrench"></i> Ação Sugerida:</strong> ${insight.acao}
                    </p>
                ` : ''}
                ${insight.action?.link ? `
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

    // 🎯 CAMADA 2.5 - KPIs POR ÁREA: RECEBIMENTO
    async function carregarKPIsRecebimento(dataInicio, dataFim) {
        try {
            const baseUrl = '/DashBoard/backendDash/recebimentoPHP';
            const params = `inicio=${dataInicio.split('-').reverse().join('/')}&fim=${dataFim.split('-').reverse().join('/')}`;
            
            // Buscar todos os KPIs de recebimento em paralelo
            const [backlog, recebidos, remessas, taxa, tempo] = await Promise.all([
                fetch(`${baseUrl}/kpi-backlog-atual.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-equipamentos-recebidos.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-remessas-recebidas.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-taxa-envio-analise.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-tempo-ate-analise.php?${params}`).then(r => r.json())
            ]);

            // Renderizar KPIs
            atualizarKPIArea('recebimentoBacklog', backlog.data, 'equipamentos', 'recebimentoBacklogVariacao');
            atualizarKPIArea('recebimentoRecebidos', recebidos.data, 'equipamentos', 'recebimentoRecebidosVariacao');
            atualizarKPIArea('recebimentoRemessas', remessas.data, 'remessas', 'recebimentoRemessasVariacao');
            atualizarKPIArea('recebimentoTaxa', taxa.data, '%', 'recebimentoTaxaVariacao');
            atualizarKPIArea('recebimentoTempo', tempo.data, 'dias', 'recebimentoTempoVariacao');

        } catch (error) {
            console.error('Erro ao carregar KPIs de Recebimento:', error);
        }
    }

    // 🎯 CAMADA 2.5 - KPIs POR ÁREA: ANÁLISE
    async function carregarKPIsAnalise(dataInicio, dataFim) {
        try {
            const baseUrl = '/DashBoard/backendDash/analisePHP';
            const params = `inicio=${dataInicio.split('-').reverse().join('/')}&fim=${dataFim.split('-').reverse().join('/')}`;
            
            // Buscar todos os KPIs de análise em paralelo
            const [backlog, analisados, taxaConversao, tempoMedio, valorOrcado] = await Promise.all([
                fetch(`${baseUrl}/kpi-equipamentos-em-analise.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-equipamentos-analisados.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-taxa-conversao-analise.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-tempo-medio-analise.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-valor-orcado-analise.php?${params}`).then(r => r.json())
            ]);

            // Renderizar KPIs
            atualizarKPIArea('analiseBacklog', backlog.data, 'equipamentos', 'analiseBacklogVariacao');
            atualizarKPIArea('analiseAnalisados', analisados.data, 'equipamentos', 'analiseAnalisadosVariacao');
            atualizarKPIArea('analiseTaxaConversao', taxaConversao.data, '%', 'analiseTaxaConversaoVariacao');
            atualizarKPIArea('analiseTempoMedio', tempoMedio.data, 'dias', 'analiseTempoMedioVariacao');
            atualizarKPIArea('analiseValorOrcado', valorOrcado.data, 'R$', 'analiseValorOrcadoVariacao');

            // Gerar insights específicos da análise
            gerarInsightsAnalise({backlog, analisados, taxaConversao, tempoMedio, valorOrcado});

        } catch (error) {
            console.error('Erro ao carregar KPIs de Análise:', error);
        }
    }

    function atualizarKPIArea(elementoId, dados, unidade, variacaoId) {
        const elemento = document.getElementById(elementoId);
        const variacaoEl = document.getElementById(variacaoId);
        
        if (!elemento || !dados) return;

        // Atualizar valor
        let valorFormatado = dados.valor;
        if (unidade === 'R$') {
            elemento.innerHTML = `<span class="kpi-currency">R$</span> ${valorFormatado}`;
        } else {
            elemento.textContent = `${valorFormatado} ${unidade}`;
        }

        // Atualizar comparação
        if (variacaoEl && dados.referencia) {
            const variacao = dados.referencia.variacao || 0;
            const estado = dados.referencia.estado || 'neutral';
            
            const icone = variacao > 0 ? '↑' : variacao < 0 ? '↓' : '→';
            const cor = estado === 'success' ? '#10b981' : estado === 'warning' ? '#f59e0b' : estado === 'critical' ? '#ef4444' : '#6b7280';
            const sinal = variacao > 0 ? '+' : '';
            
            variacaoEl.innerHTML = `<span style="color: ${cor};">${icone} ${sinal}${variacao.toFixed(1)}%</span>`;
        }
    }

    function gerarInsightsAnalise(kpis) {
        const insightsAnalise = [];

        // Insight 1: Backlog crescente
        const backlog = kpis.backlog.data;
        if (backlog.referencia?.estado === 'critical') {
            insightsAnalise.push({
                type: 'critical',
                tipo: 'gargalo',
                title: 'Backlog de análise crescendo',
                message: `${backlog.valor} equipamentos aguardando análise. Aumento de ${backlog.referencia.variacao.toFixed(1)}%.`,
                causa: 'Volume de recebimentos superando capacidade de análise',
                acao: 'Escalar equipe ou priorizar remessas críticas'
            });
        }

        // Insight 2: Baixa conversão
        const taxa = kpis.taxaConversao.data;
        if (taxa.valor < 70) {
            insightsAnalise.push({
                type: 'critical',
                tipo: 'eficiencia',
                title: 'Taxa de conversão baixa na análise',
                message: `Apenas ${taxa.valor}% dos equipamentos recebidos foram analisados.`,
                causa: 'Gargalo na análise ou backlog histórico acumulado',
                acao: 'Revisar processos e aumentar produtividade da análise'
            });
        } else if (taxa.valor < 85) {
            insightsAnalise.push({
                type: 'warning',
                tipo: 'eficiencia',
                title: 'Taxa de conversão abaixo da meta',
                message: `${taxa.valor}% de conversão (meta: 85%).`,
                causa: 'Capacidade de análise próxima do limite',
                acao: 'Monitorar tendência e avaliar necessidade de recursos'
            });
        }

        // Insight 3: Análise saudável
        if (taxa.valor >= 85 && backlog.referencia?.estado !== 'critical') {
            insightsAnalise.push({
                type: 'info',
                tipo: 'operacao',
                title: 'Análise operando dentro da meta',
                message: `${taxa.valor}% de conversão e backlog controlado.`,
                causa: 'Capacidade adequada ao volume de recebimentos',
                acao: 'Manter padrões operacionais e monitorar qualidade'
            });
        }

        // Adicionar insights de análise aos insights globais
        if (window.todosInsights) {
            window.todosInsights.push(...insightsAnalise);
        }

        console.log('✅ Insights de Análise gerados:', insightsAnalise);
    }

    // 🎯 CAMADA 2.5 - KPIs POR ÁREA: REPARO
    async function carregarKPIsReparo(dataInicio, dataFim) {
        try {
            const baseUrl = '/DashBoard/backendDash/reparoPHP';
            const params = `inicio=${dataInicio.split('-').reverse().join('/')}&fim=${dataFim.split('-').reverse().join('/')}`;
            
            // Buscar todos os KPIs de reparo em paralelo
            const [backlog, reparados, taxaConversao, tempoMedio, valorOrcado] = await Promise.all([
                fetch(`${baseUrl}/kpi-equipamentos-em-reparo.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-equipamentos-reparados.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-taxa-conversao-reparo.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-tempo-medio-reparo.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-valor-orcado-reparo.php?${params}`).then(r => r.json())
            ]);

            // Renderizar KPIs
            atualizarKPIArea('reparoBacklog', backlog.data, 'equipamentos', 'reparoBacklogVariacao');
            atualizarKPIArea('reparoReparados', reparados.data, 'equipamentos', 'reparoReparadosVariacao');
            atualizarKPIArea('reparoTaxaConversao', taxaConversao.data, '%', 'reparoTaxaConversaoVariacao');
            atualizarKPIArea('reparoTempoMedio', tempoMedio.data, 'dias', 'reparoTempoMedioVariacao');
            atualizarKPIArea('reparoValorOrcado', valorOrcado.data, 'R$', 'reparoValorOrcadoVariacao');

            // Gerar insights específicos do reparo
            gerarInsightsReparo({backlog, reparados, taxaConversao, tempoMedio, valorOrcado});

        } catch (error) {
            console.error('Erro ao carregar KPIs de Reparo:', error);
        }
    }

    function gerarInsightsReparo(kpis) {
        const insightsReparo = [];

        // Insight 1: Gargalo técnico (backlog crescente)
        const backlog = kpis.backlog.data;
        if (backlog.referencia?.estado === 'critical') {
            insightsReparo.push({
                type: 'critical',
                tipo: 'gargalo',
                title: 'Gargalo técnico no reparo',
                message: `${backlog.valor} equipamentos aguardando reparo. Aumento de ${backlog.referencia.variacao.toFixed(1)}%.`,
                causa: 'Capacidade técnica insuficiente ou complexidade acima do esperado',
                acao: 'Reforçar equipe técnica ou escalar para fornecedor externo'
            });
        }

        // Insight 2: Taxa de conversão baixa
        const taxa = kpis.taxaConversao.data;
        if (taxa.valor < 60) {
            insightsReparo.push({
                type: 'critical',
                tipo: 'eficiencia',
                title: 'Taxa de conversão crítica no reparo',
                message: `Apenas ${taxa.valor}% dos equipamentos analisados foram reparados.`,
                causa: 'Peças indisponíveis, reparos inviáveis ou priorização incorreta',
                acao: 'Revisar viabilidade de reparos e gestão de estoque de peças'
            });
        } else if (taxa.valor < 75) {
            insightsReparo.push({
                type: 'warning',
                tipo: 'eficiencia',
                title: 'Taxa de conversão abaixo da meta',
                message: `${taxa.valor}% de conversão no reparo (meta: 75%).`,
                causa: 'Limitações técnicas ou logísticas impactando a conclusão',
                acao: 'Identificar principais motivos de não-reparo e mitigar'
            });
        }

        // Insight 3: Reparo operando bem
        if (taxa.valor >= 80 && backlog.referencia?.variacao < 0) {
            insightsReparo.push({
                type: 'info',
                tipo: 'operacao',
                title: 'Reparo operando com excelência',
                message: `${taxa.valor}% de conversão e backlog em redução.`,
                causa: 'Capacidade técnica adequada e boa gestão de recursos',
                acao: 'Manter padrões e documentar boas práticas'
            });
        }

        // Adicionar insights de reparo aos insights globais
        if (window.todosInsights) {
            window.todosInsights.push(...insightsReparo);
        }

        console.log('✅ Insights de Reparo gerados:', insightsReparo);
    }

    // 🎯 CAMADA 2.5 - KPIs POR ÁREA: QUALIDADE
    async function carregarKPIsQualidade(dataInicio, dataFim) {
        try {
            const baseUrl = '/DashBoard/backendDash/qualidadePHP';
            const params = `inicio=${dataInicio.split('-').reverse().join('/')}&fim=${dataFim.split('-').reverse().join('/')}`;
            
            // Buscar todos os KPIs de qualidade em paralelo
            const [avaliados, semConserto, taxaQualidade, causas, modelos] = await Promise.all([
                fetch(`${baseUrl}/kpi-equipamentos-avaliados.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-equipamentos-sem-conserto.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-taxa-qualidade.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-principais-causas.php?${params}`).then(r => r.json()),
                fetch(`${baseUrl}/kpi-modelos-reprovados.php?${params}`).then(r => r.json())
            ]);

            // Renderizar KPIs
            atualizarKPIArea('qualidadeAvaliados', avaliados.data, 'equipamentos', 'qualidadeAvaliadosVariacao');
            atualizarKPIArea('qualidadeSemConserto', semConserto.data, 'equipamentos', 'qualidadeSemConsertoVariacao');
            atualizarKPIArea('qualidadeTaxa', taxaQualidade.data, '%', 'qualidadeTaxaVariacao');
            
            // KPIs especiais (texto sem unidade)
            const elementoCausas = document.getElementById('qualidadeCausas');
            const elementoModelos = document.getElementById('qualidadeModelos');
            
            if (elementoCausas && causas.data) {
                elementoCausas.textContent = causas.data.valor || 'N/A';
                const variacaoCausas = document.getElementById('qualidadeCausasVariacao');
                if (variacaoCausas && causas.data.detalhes) {
                    const concentracao = causas.data.detalhes.concentracao_causa_1 || 0;
                    const estado = causas.data.referencia?.estado || 'neutral';
                    const cor = estado === 'success' ? '#10b981' : estado === 'warning' ? '#f59e0b' : estado === 'critical' ? '#ef4444' : '#6b7280';
                    variacaoCausas.innerHTML = `<span style="color: ${cor};">Concentração: ${concentracao.toFixed(1)}%</span>`;
                }
            }
            
            if (elementoModelos && modelos.data) {
                elementoModelos.textContent = modelos.data.valor || 'N/A';
                const variacaoModelos = document.getElementById('qualidadeModelosVariacao');
                if (variacaoModelos && modelos.data.detalhes) {
                    const concentracao = modelos.data.detalhes.concentracao_modelo_1 || 0;
                    const estado = modelos.data.referencia?.estado || 'neutral';
                    const cor = estado === 'success' ? '#10b981' : estado === 'warning' ? '#f59e0b' : estado === 'critical' ? '#ef4444' : '#6b7280';
                    variacaoModelos.innerHTML = `<span style="color: ${cor};">Concentração: ${concentracao.toFixed(1)}%</span>`;
                }
            }

            // Gerar insights específicos da qualidade
            gerarInsightsQualidade({avaliados, semConserto, taxaQualidade, causas, modelos});

        } catch (error) {
            console.error('Erro ao carregar KPIs de Qualidade:', error);
        }
    }

    function gerarInsightsQualidade(kpis) {
        const insightsQualidade = [];

        // Insight 1: Alta taxa de reprovação
        const taxa = kpis.taxaQualidade.data;
        if (taxa.valor < 70) {
            insightsQualidade.push({
                type: 'critical',
                tipo: 'eficiencia',
                title: 'Taxa de aprovação crítica',
                message: `Apenas ${taxa.valor}% dos equipamentos aprovados na qualidade.`,
                causa: 'Alto índice de equipamentos sem conserto ou com problemas de qualidade',
                acao: 'Revisar padrões de reparo e critérios de aprovação na qualidade'
            });
        } else if (taxa.valor < 85) {
            insightsQualidade.push({
                type: 'warning',
                tipo: 'eficiencia',
                title: 'Taxa de aprovação abaixo da meta',
                message: `${taxa.valor}% de aprovação (meta: 85%).`,
                causa: 'Reprovações acima do esperado na inspeção final',
                acao: 'Investigar causas de reprovação e melhorar processo de reparo'
            });
        }

        // Insight 2: Concentração de causa (problema sistêmico)
        const causas = kpis.causas.data;
        const concentracaoCausa = causas.detalhes?.concentracao_causa_1 || 0;
        if (concentracaoCausa > 60) {
            insightsQualidade.push({
                type: 'critical',
                tipo: 'gargalo',
                title: 'Concentração crítica em uma causa',
                message: `${concentracaoCausa.toFixed(1)}% das reprovações: "${causas.valor}".`,
                causa: 'Problema sistêmico afetando grande parte dos equipamentos',
                acao: 'Criar plano de ação específico para mitigar esta causa raiz'
            });
        } else if (concentracaoCausa > 40) {
            insightsQualidade.push({
                type: 'warning',
                tipo: 'gargalo',
                title: 'Causa de reprovação dominante',
                message: `${concentracaoCausa.toFixed(1)}% concentrados em "${causas.valor}".`,
                causa: 'Uma causa específica responsável por parte significativa das falhas',
                acao: 'Priorizar solução desta causa para melhorar aprovação geral'
            });
        }

        // Insight 3: Qualidade saudável
        if (taxa.valor >= 85 && concentracaoCausa <= 40) {
            insightsQualidade.push({
                type: 'info',
                tipo: 'operacao',
                title: 'Qualidade operando dentro da meta',
                message: `${taxa.valor}% de aprovação com causas distribuídas.`,
                causa: 'Processos de reparo e inspeção bem calibrados',
                acao: 'Manter padrões e monitorar tendências de novos problemas'
            });
        }

        // Adicionar insights de qualidade aos insights globais
        if (window.todosInsights) {
            window.todosInsights.push(...insightsQualidade);
        }

        console.log('✅ Insights de Qualidade gerados:', insightsQualidade);
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
        
        // Obter filtros atuais
        const filtroInicio = document.getElementById('filtroInicio')?.value || '';
        const filtroFim = document.getElementById('filtroFim')?.value || '';
        const filtroSetor = document.getElementById('filtroSetor')?.value || '';
        const filtroOperador = document.getElementById('filtroOperador')?.value || '';
        
        // Construir parâmetros de URL
        let params = new URLSearchParams();
        if (filtroInicio) params.append('inicio', filtroInicio);
        if (filtroFim) params.append('fim', filtroFim);
        if (filtroSetor) params.append('setor', filtroSetor);
        if (filtroOperador) params.append('operador', filtroOperador);
        
        const queryString = params.toString() ? '&' + params.toString() : '';
        
        // Navegação por área (novo sistema de detalhamento)
        switch(destino) {
            case 'recebimento':
                window.location.href = `AreaDetalhada.php?area=recebimento${queryString}`;
                break;
            case 'analise':
                window.location.href = `AreaDetalhada.php?area=analise${queryString}`;
                break;
            case 'reparo':
                window.location.href = `AreaDetalhada.php?area=reparo${queryString}`;
                break;
            case 'qualidade':
                window.location.href = `AreaDetalhada.php?area=qualidade${queryString}`;
                break;
            
            // Navegação legada (compatibilidade com sistema antigo)
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

    // 🎯 FUNÇÕES DE INTEGRAÇÃO COM BACKEND - CATÁLOGO OFICIAL v1.0
    
    async function obterRemessasRecebidas(dataInicio, dataFim) {
        try {
            const params = new URLSearchParams({
                inicio: dataInicio,
                fim: dataFim
            });
            
            const response = await fetch(`../backendDash/kpis/kpi-remessas-recebidas.php?${params}`);
            const data = await response.json();
            
            if (data.meta?.success) {
                return data.data;
            }
            throw new Error(data.meta?.message || 'Erro ao buscar remessas recebidas');
        } catch (error) {
            console.error('Erro em obterRemessasRecebidas:', error);
            return { valor: 0, unidade: 'remessas', referencia: { variacao: 0 }, detalhes: { media_dia: 0 } };
        }
    }

    async function obterEquipamentosRecebidos(dataInicio, dataFim) {
        try {
            const params = new URLSearchParams({
                inicio: dataInicio,
                fim: dataFim
            });
            
            const response = await fetch(`../backendDash/kpis/kpi-equipamentos-recebidos.php?${params}`);
            const data = await response.json();
            
            if (data.meta?.success) {
                return data.data;
            }
            throw new Error(data.meta?.message || 'Erro ao buscar equipamentos recebidos');
        } catch (error) {
            console.error('Erro em obterEquipamentosRecebidos:', error);
            return { valor: 0, unidade: 'equipamentos', referencia: { variacao: 0 }, detalhes: { media_dia: 0 } };
        }
    }

    async function obterEquipamentosExpedidos(dataInicio, dataFim) {
        try {
            const params = new URLSearchParams({
                inicio: dataInicio,
                fim: dataFim
            });
            
            const response = await fetch(`../backendDash/kpis/kpi-equipamentos-expedidos.php?${params}`);
            const data = await response.json();
            
            if (data.meta?.success) {
                return data.data;
            }
            throw new Error(data.meta?.message || 'Erro ao buscar equipamentos expedidos');
        } catch (error) {
            console.error('Erro em obterEquipamentosExpedidos:', error);
            return { valor: 0, unidade: 'equipamentos', referencia: { variacao: 0 }, detalhes: { media_dia: 0 } };
        }
    }

    async function obterTaxaConclusao(dataInicio, dataFim) {
        try {
            const params = new URLSearchParams({
                inicio: dataInicio,
                fim: dataFim
            });
            
            const response = await fetch(`../backendDash/kpis/kpi-taxa-conclusao.php?${params}`);
            const data = await response.json();
            
            if (data.meta?.success) {
                return data.data;
            }
            throw new Error(data.meta?.message || 'Erro ao buscar taxa de conclusão');
        } catch (error) {
            console.error('Erro em obterTaxaConclusao:', error);
            return { valor: 0, unidade: '%', referencia: { variacao: 0 }, detalhes: { recebidos: 0, expedidos: 0 } };
        }
    }

    async function obterValorTotalOrcado(dataInicio, dataFim) {
        try {
            const params = new URLSearchParams({
                inicio: dataInicio,
                fim: dataFim
            });
            
            const response = await fetch(`../backendDash/kpis/kpi-valor-orcado.php?${params}`);
            const data = await response.json();
            
            if (data.meta?.success) {
                return data.data;
            }
            throw new Error(data.meta?.message || 'Erro ao buscar valor orçado');
        } catch (error) {
            console.error('Erro em obterValorTotalOrcado:', error);
            return { valor: '0,00', unidade: 'R$', referencia: { variacao: 0 }, detalhes: { analise: '0,00', reparo: '0,00' } };
        }
    }
    </script>

</body>
</html>
