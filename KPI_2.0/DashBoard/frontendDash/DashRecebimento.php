<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilidade adicional
header("Pragma: no-cache"); // Compatível com HTTP/1.0
header("Expires: 0"); // Expira imediatamente

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
    <title>Centro de Inteligência Operacional | KPI 2.0</title>
    <link rel="stylesheet" href="cssDash/dashrecebimento.css?v=3.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart.js principal -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Plugin de data labels (exibe números nas barras) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<!-- Plugin de annotation (linhas verticais com rótulos) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0/dist/chartjs-plugin-annotation.min.js"></script>

<!-- 🧱 FETCH HELPERS PADRONIZADOS -->
    <script src="jsDash/fetch-helpers.js?v=1.0"></script>

<!-- RECEBIMENTO -->
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoQuantidade.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoMedio.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosSetor.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosOperador.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoOperacoes.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTaxaRejeicao.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoOperacoes.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTopEmpresas.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosDia.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTendenciaMensal.js"></script>

<!-- ANÁLISE -->
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/analiseJS/produtividadeAnalise.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/analiseJS/graficoTicketMedio.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/analiseJS/tempoMedioAnalise.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/analiseJS/parcialCompleta.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/analiseJS/analisesCliente.js"></script>

<!-- Reparo -->  
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/reparoJS/produtividadeReparo.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/reparoJS/tempoReparoOperador.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/reparoJS/reparosPorCliente.js"></script>



<!-- Qualidade -->
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/qualidadeJS/quantidadesEquip.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/qualidadeJS/principaisServicos.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/qualidadeJS/principaisLaudos.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/qualidadeJS/semConsertoProdutos.js"></script>
<!-- Financeiro -->
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/financeiroJS/orcamentosGeradosAnalise.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/financeiroJS/orcamentosGeradosReparo.js"></script>
    <script src="https://kpi.stbextrema.com.br/DashBoard/frontendDash/jsDash/financeiroJS/kpiCustosProdutos.js"></script>
</head>
<body>

    <!-- 🎯 HEADER SUPERIOR - CONTROLE DE CONTEXTO -->
    <header class="intelligence-header">
        <div class="header-brand">
            <i class="fas fa-chart-network"></i>
            <h1>Centro de Inteligência Operacional</h1>
        </div>
        
        <div class="header-controls">
            <!-- Filtros de período -->
            <div class="control-group">
                <label for="data_inicial"><i class="far fa-calendar"></i> De:</label>
                <input type="date" id="data_inicial" name="data_inicial">
            </div>
            
            <div class="control-group">
                <label for="data_final"><i class="far fa-calendar"></i> Até:</label>
                <input type="date" id="data_final" name="data_final">
            </div>
            
            <div class="control-group">
                <label for="operador"><i class="far fa-user"></i> Operador:</label>
                <select id="operador" name="operador">
                    <option value="">Todos</option>
                    <option value="Vitor Olegario">Vitor Olegário</option>
                    <option value="Luan Oliveira">Luan Oliveira</option>
                    <option value="ronyrodrigues">Rony Rodrigues</option>
                    <option value="Ederson Santos">Ederson Santos</option>
                    <option value="Matheus Ferreira">Matheus Ferreira</option>
                </select>
            </div>
            
            <button id="btnFiltrar" class="btn-primary" type="button">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            
            <div class="filtro-status" id="filtroStatus" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span>Filtro aplicado</span>
            </div>
            
            <button type="button" id="admin" class="btn-secondary">
                <i class="fas fa-user-shield"></i> Admin
            </button>
            
            <button type="button" onclick="window.history.back()" class="btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </button>
        </div>
    </header>

    <!-- 🎯 PAINEL LATERAL - SELETOR DE MÓDULOS -->
    <aside class="module-sidebar">
        <div class="sidebar-title">
            <i class="fas fa-layer-group"></i>
            <span>Módulos</span>
        </div>
        
        <nav class="module-nav">
            <button type="button" id="recebimento" class="module-btn" data-module="recebimento">
                <i class="fas fa-box-open"></i>
                <span>Recebimento</span>
            </button>
            
            <button type="button" id="analise" class="module-btn" data-module="analise">
                <i class="fas fa-microscope"></i>
                <span>Análise</span>
            </button>
            
            <button type="button" id="reparo" class="module-btn" data-module="reparo">
                <i class="fas fa-tools"></i>
                <span>Reparo</span>
            </button>
            
            <button type="button" id="qualidade" class="module-btn" data-module="qualidade">
                <i class="fas fa-clipboard-check"></i>
                <span>Qualidade</span>
            </button>
            
            <button type="button" id="financeiro" class="module-btn" data-module="financeiro">
                <i class="fas fa-chart-line"></i>
                <span>Financeiro</span>
            </button>
        </nav>
        
        <!-- Menu de KPIs por módulo (aparece quando módulo ativo) -->
        <div class="kpi-menu" id="kpiMenuRecebimento" style="display: none;">
            <div class="kpi-menu-title">Indicadores</div>
            <a id="quantidade-recebida" class="kpi-link" data-target="quantidadeRecebimento">
                <i class="fas fa-box"></i> QTD Recebidos
            </a>
            <a id="tempo-medio" class="kpi-link" data-target="tempoMedioAnalise">
                <i class="fas fa-clock"></i> Tempo Médio
            </a>
            <a id="recebimento-setor" class="kpi-link" data-target="recebimentosSetor">
                <i class="fas fa-chart-pie"></i> Por Setor
            </a>
            <a id="operacoes-origem-destino" class="kpi-link" data-target="operacoesOrigemDestino">
                <i class="fas fa-route"></i> Operações
            </a>
            <a id="tempo-operacoes" class="kpi-link" data-target="tempoOperacoes">
                <i class="fas fa-hourglass-half"></i> Tempo Operações
            </a>
            <a id="top-empresas" class="kpi-link" data-target="topEmpresas">
                <i class="fas fa-building"></i> Top Empresas
            </a>
            <a id="tendencia-mensal" class="kpi-link" data-target="tendenciaMensal">
                <i class="fas fa-chart-area"></i> Tendência
            </a>
        </div>
        
        <div class="kpi-menu" id="kpiMenuAnalise" style="display: none;">
            <div class="kpi-menu-title">Indicadores</div>
            <a id="equipamentos_finalizados" data-target="quantidadeAnalise" class="kpi-link">
                <i class="fas fa-check-circle"></i> QTD Analisadas
            </a>
            <a id="tempo_medio_analise" data-target="graficoTempoMedioAnaliseContainer" class="kpi-link">
                <i class="fas fa-stopwatch"></i> Tempo Médio
            </a>
            <a id="parcial_vs_completa" data-target="graficoParcialCompletaContainer" class="kpi-link">
                <i class="fas fa-balance-scale"></i> Parcial vs Completa
            </a>
            <a id="analises_por_cliente" data-target="graficoAnalisesClienteContainer" class="kpi-link">
                <i class="fas fa-users"></i> Por Cliente
            </a>
        </div>
        
        <div class="kpi-menu" id="kpiMenuReparo" style="display: none;">
            <div class="kpi-menu-title">Indicadores</div>
            <a id="quantidade_reparados" class="kpi-link">
                <i class="fas fa-wrench"></i> QTD Reparada
            </a>
            <a id="tempoMedioReparoOperador" class="kpi-link">
                <i class="fas fa-user-clock"></i> Tempo por Operador
            </a>
            <a id="reparoPorCliente" class="kpi-link">
                <i class="fas fa-industry"></i> Por Cliente
            </a>
        </div>
        
        <div class="kpi-menu" id="kpiMenuQualidade" style="display: none;">
            <div class="kpi-menu-title">Indicadores</div>
            <a id="quantidade_equipamentos" class="kpi-link">
                <i class="fas fa-mobile-alt"></i> Por Modelo
            </a>
            <a id="principais_servicos" class="kpi-link">
                <i class="fas fa-cog"></i> Serviços
            </a>
            <a id="principais_laudos" class="kpi-link">
                <i class="fas fa-file-medical"></i> Laudos
            </a>
            <a id="quantidade_sem_conserto" class="kpi-link">
                <i class="fas fa-ban"></i> Sem Conserto
            </a>
        </div>
        
        <div class="kpi-menu" id="kpiMenuFinanceiro" style="display: none;">
            <div class="kpi-menu-title">Indicadores</div>
            <a class="kpi-link">
                <i class="fas fa-dollar-sign"></i> Orçamentos
            </a>
        </div>
    </aside>

    <!-- 🎯 ÁREA CENTRAL - CANVAS DE DADOS -->
    <main class="intelligence-canvas">
        
        <!-- ============================================
             RESUMO EXECUTIVO - VISÍVEL AO CARREGAR
             ============================================ -->
        <section class="resumo-executivo" id="resumoExecutivo">
            
            <!-- 1️⃣ KPIs GLOBAIS -->
            <div class="kpis-globais">
                <h2 class="section-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Indicadores Globais
                </h2>
                
                <div class="kpis-grid">
                    <div class="kpi-global-card" id="cardTotalProcessado">
                        <div class="kpi-icon-wrapper" style="--icon-color: #3b82f6;">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="kpi-data">
                            <span class="kpi-label">Total Processado</span>
                            <span class="kpi-value" id="globalTotal">--</span>
                            <span class="kpi-variacao" id="globalTotalVariacao"></span>
                            <span class="kpi-period" id="globalPeriodo">Últimos 7 dias</span>
                        </div>
                    </div>
                    
                    <div class="kpi-global-card" id="cardTempoMedio">
                        <div class="kpi-icon-wrapper" style="--icon-color: #8b5cf6;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="kpi-data">
                            <span class="kpi-label">Tempo Médio Global</span>
                            <span class="kpi-value" id="globalTempo">--</span>
                            <span class="kpi-variacao" id="globalTempoVariacao"></span>
                            <span class="kpi-period">Ciclo completo</span>
                        </div>
                    </div>
                    
                    <div class="kpi-global-card" id="cardTaxaSucesso">
                        <div class="kpi-icon-wrapper" style="--icon-color: #10b981;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="kpi-data">
                            <span class="kpi-label">Taxa de Sucesso</span>
                            <span class="kpi-value" id="globalSucesso">--</span>
                            <span class="kpi-variacao" id="globalSucessoVariacao"></span>
                            <span class="kpi-period">Com conserto</span>
                        </div>
                    </div>
                    
                    <div class="kpi-global-card" id="cardSemConserto">
                        <div class="kpi-icon-wrapper" style="--icon-color: #ef4444;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="kpi-data">
                            <span class="kpi-label">Sem Conserto</span>
                            <span class="kpi-value" id="globalSemConserto">--</span>
                            <span class="kpi-variacao" id="globalSemConsertoVariacao"></span>
                            <span class="kpi-period">Não reparáveis</span>
                        </div>
                    </div>
                    
                    <div class="kpi-global-card" id="cardValorOrcado">
                        <div class="kpi-icon-wrapper" style="--icon-color: #f59e0b;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="kpi-data">
                            <span class="kpi-label">Valor Orçado</span>
                            <span class="kpi-value" id="globalValor">--</span>
                            <span class="kpi-variacao" id="globalValorVariacao"></span>
                            <span class="kpi-period">Análise + Reparo</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 2️⃣ INSIGHTS AUTOMATIZADOS -->
            <div class="insights-container" id="insightsContainer">
                <h2 class="section-title">
                    <i class="fas fa-brain"></i>
                    Insights do Sistema
                </h2>
                
                <div class="insights-grid" id="insightsGridResumo">
                    <div class="insight-placeholder">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Analisando dados do período...</p>
                    </div>
                </div>
            </div>
            
            <!-- 3️⃣ VISÃO POR ÁREA (CARDS RESUMO) -->
            <div class="areas-overview">
                <h2 class="section-title">
                    <i class="fas fa-th-large"></i>
                    Visão por Área
                </h2>
                
                <div class="areas-grid">
                    <!-- Recebimento -->
                    <div class="area-card" data-area="recebimento" onclick="abrirArea('recebimento')">
                        <div class="area-header">
                            <div class="area-icon" style="--area-color: #3b82f6;">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3>Recebimento</h3>
                        </div>
                        <div class="area-metrics">
                            <div class="metric">
                                <span class="metric-label">Volume</span>
                                <span class="metric-value" id="areaRecebVolume">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Tempo Médio</span>
                                <span class="metric-value" id="areaRecebTempo">--</span>
                            </div>
                        </div>
                        <div class="area-status" id="areaRecebStatus">
                            <i class="fas fa-circle"></i> Normal
                        </div>
                    </div>
                    
                    <!-- Análise -->
                    <div class="area-card" data-area="analise" onclick="abrirArea('analise')">
                        <div class="area-header">
                            <div class="area-icon" style="--area-color: #8b5cf6;">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Análise</h3>
                        </div>
                        <div class="area-metrics">
                            <div class="metric">
                                <span class="metric-label">Volume</span>
                                <span class="metric-value" id="areaAnaliseVolume">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Tempo Médio</span>
                                <span class="metric-value" id="areaAnaliseTempo">--</span>
                            </div>
                        </div>
                        <div class="area-status" id="areaAnaliseStatus">
                            <i class="fas fa-circle"></i> Normal
                        </div>
                    </div>
                    
                    <!-- Reparo -->
                    <div class="area-card" data-area="reparo" onclick="abrirArea('reparo')">
                        <div class="area-header">
                            <div class="area-icon" style="--area-color: #f59e0b;">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <h3>Reparo</h3>
                        </div>
                        <div class="area-metrics">
                            <div class="metric">
                                <span class="metric-label">Volume</span>
                                <span class="metric-value" id="areaReparoVolume">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Tempo Médio</span>
                                <span class="metric-value" id="areaReparoTempo">--</span>
                            </div>
                        </div>
                        <div class="area-status" id="areaReparoStatus">
                            <i class="fas fa-circle"></i> Normal
                        </div>
                    </div>
                    
                    <!-- Qualidade -->
                    <div class="area-card" data-area="qualidade" onclick="abrirArea('qualidade')">
                        <div class="area-header">
                            <div class="area-icon" style="--area-color: #10b981;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Qualidade</h3>
                        </div>
                        <div class="area-metrics">
                            <div class="metric">
                                <span class="metric-label">Laudos</span>
                                <span class="metric-value" id="areaQualidadeLaudos">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Sem Conserto</span>
                                <span class="metric-value" id="areaQualidadeSC">--</span>
                            </div>
                        </div>
                        <div class="area-status" id="areaQualidadeStatus">
                            <i class="fas fa-circle"></i> Normal
                        </div>
                    </div>
                    
                    <!-- Financeiro -->
                    <div class="area-card" data-area="financeiro" onclick="abrirArea('financeiro')">
                        <div class="area-header">
                            <div class="area-icon" style="--area-color: #ec4899;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Financeiro</h3>
                        </div>
                        <div class="area-metrics">
                            <div class="metric">
                                <span class="metric-label">Orçamentos</span>
                                <span class="metric-value" id="areaFinanceiroOrc">--</span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Valor Total</span>
                                <span class="metric-value" id="areaFinanceiroValor">--</span>
                            </div>
                        </div>
                        <div class="area-status" id="areaFinanceiroStatus">
                            <i class="fas fa-circle"></i> Normal
                        </div>
                    </div>
                </div>
            </div>
            
        </section><!-- fim resumo-executivo -->
        
        <!-- ============================================
             GRÁFICOS DETALHADOS - OCULTOS POR PADRÃO
             ============================================ -->
        <section class="graficos-detalhados" id="graficosDetalhados" style="display: none;">
            
            <!-- Botão Voltar ao Resumo -->
            <div class="header-secao-graficos">
                <button class="btn-voltar-resumo" onclick="voltarAoResumo()">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Resumo Executivo
                </button>
                <h2 id="tituloAreaAtiva">Detalhes</h2>
            </div>
        
        <!-- KPIs Rápidos (aparece dinamicamente com dados do módulo ativo) -->
        <section class="kpi-cards" id="kpiCards" style="display: none;">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-box"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Total Processado</span>
                    <span class="kpi-value" id="kpiTotal">--</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-clock"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Tempo Médio</span>
                    <span class="kpi-value" id="kpiTempo">--</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-chart-line"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Produtividade</span>
                    <span class="kpi-value" id="kpiProdutividade">--</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="kpi-content">
                    <span class="kpi-label">Status Crítico</span>
                    <span class="kpi-value" id="kpiCritico">--</span>
                </div>
            </div>
        </section>
        
        <!-- Grid de Widgets de Gráficos -->
        <section class="widgets-grid">
            
    <!--Área administrativa exclusiva para Vitor Olegario-->
<?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'Vitor Olegario'): ?>
    <div id="area-administrativa" style="display: none;">
        <h3>Monitoramento em Tempo Real - Operadores</h3>
        <div class="operadores-container">
            <!-- Operador 1 -->
            <div class="operador-box" id="operador1">
                <h4>Vitor Olegario</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Vitor_Olegario" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 2 -->
            <div class="operador-box" id="operador2">
                <h4>Luan Oliveira</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Luan_Oliveira" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 3 -->
            <div class="operador-box" id="operador3">
                <h4>Rony Rodrigues</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Rony_Rodrigues" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 4 -->
            <div class="operador-box" id="operador4">
                <h4>Ederson Santos</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Ederson_Santos" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>
            </div>
            <!-- Operador 5 -->
            <div class="operador-box" id="operador5">
                <h4>Matheus Ferreira</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Matheus_Ferreira" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!--Area do recebimento-->
    <div class="dados-container module-content" id="dadosContainerRecebimento" style="display: none;">
        <!--Quantidade Total de Equipamentos Recebidos-->
        
        <div id="quantidadeRecebimento" >
            <div class="quantidade-recebida" id="dadosQuantidade"></div>
            <div class="grafico-container grafico-medio" id="graficoQuantidadeSemanal" style="display: none;" >
                <canvas id="graficoRecebimentosSemanal"></canvas> 
            </div>
            <div class="grafico-container grafico-medio" id="graficoQuantidadeMensal" style="display: none;" >
                <canvas id="graficoRecebimentosMensal"></canvas>
            </div>
        </div>

        <!--Tempo Médio para Envio à Análise-->
        <div class="grafico-container grafico-medio" id="tempoMedioAnalise" style="display: none;">
            <canvas id="graficoTempoMedio"></canvas>
        </div>  
        
        <!--Recebimentos por Setor-->
        <div class="grafico-container grafico-medio" id="recebimentosSetor" style="display: none;">
           <!-- Conteúdo preenchido via JS -->
        </div>


        <!--Quantidade de Equipamentos Recebidos por Operador-->
        <div class="grafico-container grafico-medio" id="recebimentosOperador" style="display: none;">
            <canvas id="graficoOperador"></canvas>
        </div>

        <!--Principais Operações de Origem e Destino-->
        <div class="grafico-container grafico-medio" id="operacoesOrigemDestino" style="display: none;">
            <canvas id="graficoOperacoes"></canvas>
        </div>

        <!--Taxa de Rejeição ou Reenvio-->
        <div class="grafico-container grafico-medio" id="taxaRejeicao" style="display: none;">
            <canvas id="graficoRejeicao"></canvas>
        </div>

        <!--Tempo Médio Entre Operações-->
        <div class="grafico-container grafico-pequeno" id="tempoOperacoes" style="display: none;">
            <canvas id="graficoTempoOperacoes"></canvas>
        </div>

        <!--Top 5 Empresas com Maior Volume de Recebimentos-->
        <div class="grafico-container grafico-medio" id="topEmpresas" style="display: none;">
            <canvas id="graficoEmpresas"></canvas>
        </div>
 
        <!--Distribuição de Recebimentos por Dia da Semana-->
        <div class="grafico-container grafico-medio" id="recebimentosDia" style="display: none;">
            <canvas id="graficoDiaSemana"></canvas>
        </div>
  
        <!--Tendência Mensal de Recebimentos-->
        <div class="grafico-container grafico-pequeno" id="tendenciaMensal" style="display: none;">
            <canvas id="graficoTendenciaMensal"></canvas>
        </div>
             
    </div>

    <!--Area da analise-->
    <div class="dados-container module-content" id="dadosAnalise" style="display: none;">
        
          <div class="grafico-container grafico-pequeno" id="graficoQuantidadeFinalisadaSemanal" style="display: none;">
            <div style="overflow-x: auto; width: 100%;">
                <canvas id="graficoProdutividadeSemanal"></canvas>
            </div>
          </div>
          <div class="grafico-container grafico-pequeno" id="graficoQuantidadeFinalisadaMensal" style="display: none;">
            <canvas id="graficoProdutividadeMensal"></canvas>
          </div>
 
          <div class="grafico-container grafico-pequeno" id="graficoTempoMedioAnaliseContainer" style="display: none;">
            <canvas id="graficoTempoMedioAnalise"></canvas>
          </div>

          <div class="grafico-container grafico-pequeno" id="graficoParcialCompletaContainer" style="display: none;">
            <canvas id="graficoParcialCompleta"></canvas>
          </div>

          <div class="grafico-container grafico-pequeno" id="graficoAnalisesClienteContainer" style="display: none;">
            <canvas id="graficoAnalisesCliente"></canvas>
          </div>     
    </div>

    <!-- Área do Reparo -->
    <div class="dados-container module-content" id="dadosReparo" style="display: none;">

  <!-- Gráfico Semanal -->
  <div class="grafico-container grafico-medio" id="graficoQuantidadeReparadaSemanal" style="display: none;">
    <canvas id="graficoReparoSemanal"></canvas>
  </div>

  <!-- Gráfico Mensal -->
  <div class="grafico-container grafico-medio" id="graficoQuantidadeReparadaMensal" style="display: none;">
    <canvas id="graficoReparoMensal"></canvas>
  </div>


  <!-- Tempo Médio de Reparo por Operador -->
  <div class="grafico-container grafico-pequeno" id="graficoTempoReparoOperador" style="display: none;">
    <canvas id="graficoReparoOperador"></canvas>
  </div>

  <!-- Distribuição de Reparos por Cliente -->
  <div class="grafico-container grafico-medio" id="graficoTotalReparoCliente" style="display: none;">
    <canvas id="graficoReparoCliente"></canvas>
  </div>


</div>

<!-- Área da Qualidade -->
<div class="dados-container module-content" id="dadosQualidade" style="display: none;">

<!-- Quantidade por equipamentos recebidos, analisados e reparados -->
  <div class="grafico-container grafico-pequeno" id="graficoquantidadeequipamentos" style="display: none;">
    <canvas id="graficoQuantidadeEquipamentos"></canvas>
  </div>
<!-- Principais serviços no reparo -->
  <div class="grafico-container grafico-grande" id="graficoprincipaisservicos" style="display: none;">
    <canvas id="graficoPrincipaisServicos"></canvas>
  </div>
  <!-- Principais laudos enviados por modelo -->
  <div class="grafico-container grafico-pequeno" id="graficoprincipaislaudos" style="display: none;">
    <h3>📋 Principais Laudos Técnicos</h3>
    <label for="filtroModelo">Modelo:</label>
    <select id="filtroModelo">
        <option value="">Todos os modelos</option>
        <!-- Opções serão preenchidas via JS -->
    </select>
    <div class="tabela-laudos">
        <table id="tabelaLaudos">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Laudo</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <!-- Dados serão preenchidos via JS -->
            </tbody>
        </table>
    </div>
</div>
    <div class="grafico-container grafico-medio" id="graficosemconserto" style="display: none;">
        <canvas id="graficoSemConserto"></canvas>
        <h3>📋 Sem Conserto por Modelo</h3>
  <div class="tabela-laudos">
    <table id="tabelaSemConserto">
      <thead>
        <tr>
          <th>Modelo</th>
          <th>Apontamento (sem conserto)</th>
          <th>Quantidade</th>
        </tr>
      </thead>
      <tbody><!-- via JS --></tbody>
    </table>
  </div>
    </div>
</div>   

<!--Financeiro-->
<div class="dados-container module-content" id="dadosFinanceiro" style="display: none;">
<div class="grafico-container grafico-medio" id="orcamentosGeradosContainerAnalise" style="display: none;">
    <h3>📋 Orçamentos Gerados - Análise</h3>
    <p id="valorTotalOrcamentos" style="font-weight: bold; margin-top: 4px; color: #333;"></p>
    <div class="tabela-laudos">
        <table id="tabelaOrcamentos">
      <thead>
          <tr>
          <th>Cliente</th>
          <th>Nota Fiscal</th>
          <th>Nº Orçamento</th>
          <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <!-- Conteúdo será preenchido via JS -->
    </tbody>
</table>
</div>
</div>
<div class="grafico-container grafico-medio" id="orcamentosGeradosContainerReparo" style="display: none;">
    <h3>📋 Orçamentos Finalizados - Reparo</h3>
    <p id="valorTotalOrcamentosReparo" style="font-weight: bold; margin-top: 4px; color: #333;"></p>
    <div class="tabela-laudos">
        <table id="tabelaOrcamentos">
      <thead>
          <tr>
          <th>Cliente</th>
          <th>Nota Fiscal</th>
          <th>Nº Orçamento</th>
          <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <!-- Conteúdo será preenchido via JS -->
    </tbody>
</table>
</div>
</div>

<div class="grafico-container grafico-medio" id="custosProdutosContainer" style="display: none;">
    <h3>📦 Custos de Produtos - Reparo</h3>
    <p id="valorTotalCustos" style="font-weight: bold; margin-top: 4px; color: #333;"></p>

    <div class="tabela-laudos" style="margin-top: 20px;">
        <table id="tabelaCustos">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Valor Unitário</th>
                    <th>Qtd Somado</th>
                    <th>Qtd Não Somado</th>
                    <th>Serviços</th>
                </tr>
            </thead>
            <tbody>
                <!-- Conteúdo via JS -->
            </tbody>
        </table>
    </div>
</div>

</div>
<script>

    
document.addEventListener("DOMContentLoaded", function () {
    const btnFiltrar = document.getElementById("btnFiltrar");

if (btnFiltrar) {
    btnFiltrar.addEventListener("click", (event) => {
        event.preventDefault();

        const filtros = {
            inicio: document.getElementById("data_inicial").value || "",
            fim: document.getElementById("data_final").value || "",
            operador: document.getElementById("operador").value || ""
        };

        // Validação obrigatória de período
        if (!filtros.inicio || !filtros.fim) {
            alert("Por favor, selecione a data inicial e final para aplicar o filtro.");
            return;
        }

        console.log("Filtros aplicados:", filtros);
        executarFiltros(filtros);
    });
}

function executarFiltros({ inicio, fim, operador }) {
    const graficos = [
        // ANALISE
        { id: "graficoQuantidadeFinalisadaSemanal", func: carregarProdutividadeAnalise },
        { id: "graficoTempoMedioAnaliseContainer", func: carregarTempoMedioAnalise },
        { id: "graficoParcialCompletaContainer", func: carregarParcialCompleta },
        { id: "graficoAnalisesClienteContainer", func: carregarAnalisesPorCliente },

        // RECEBIMENTO
        { id: "graficoQuantidadeSemanal", func: carregarQuantidadeRecebidaEGraficos },
        { id: "graficoQuantidadeMensal", func: carregarQuantidadeRecebidaEGraficos },
        { id: "tempoMedioAnalise", func: carregarGraficoTempoMedio },
        { id: "recebimentosSetor", func: carregarGraficoSetor },
        { id: "operacoesOrigemDestino", func: carregarGraficoOperacoes },
        { id: "tempoOperacoes", func: carregarGraficoTempoOperacoes },
        { id: "topEmpresas", func: carregarGraficoEmpresas },
        { id: "tendenciaMensal", func: carregarGraficoTendenciaMensal },

        // REPARO
        { id: "graficoQuantidadeReparadaSemanal", func: carregarProdutividadeReparo },
        { id: "graficoQuantidadeReparadaMensal", func: carregarProdutividadeReparo },
        { id: "graficoTempoReparoOperador", func: carregarTempoReparoOperador },
        { id: "graficoTotalReparoCliente", func: carregarReparosPorCliente },

        // QUALIDADE
        { id: "graficoquantidadeequipamentos", func: carregarquantidadeEquip},
        { id: "graficoprincipaisservicos", func: carregarPrincipaisServicos },
        { id: "graficoprincipaislaudos", func: carregarPrincipaisLaudos },
        { id: "graficosemconserto", func: carregarEquipSemConserto },

        // FINANCEIRO
        { id: "orcamentosGeradosContainerAnalise", func: carregarOrcamentosGeradosAnalise },
        { id: "orcamentosGeradosContainerReparo", func: carregarOrcamentosGeradosReparo },
        { id: "custosProdutosContainer", func: carregarCustosProdutos }
    ];

    const chamados = new Set(); // para evitar chamadas duplicadas da mesma função

    graficos.forEach(({ id, func }) => {
        const el = document.getElementById(id);
        if (el && window.getComputedStyle(el).display === "block" && !chamados.has(func)) {
            func(inicio, fim, operador);
            chamados.add(func);
        }
    });
}

        // 🎯 CONTROLE DE MÓDULOS - NOVO SISTEMA
const modulos = [
    {
        id: "recebimento",
        botao: document.getElementById("recebimento"),
        container: document.getElementById("dadosContainerRecebimento"),
        kpiMenu: document.getElementById("kpiMenuRecebimento"),
        onAtivar: () => {} // Carregamento sob demanda via filtros
    },
    {
        id: "analise",
        botao: document.getElementById("analise"),
        container: document.getElementById("dadosAnalise"),
        kpiMenu: document.getElementById("kpiMenuAnalise"),
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
        }
    },
    {
        id: "reparo",
        botao: document.getElementById("reparo"),
        container: document.getElementById("dadosReparo"),
        kpiMenu: document.getElementById("kpiMenuReparo"),
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            carregarProdutividadeReparo(dataInicio, dataFim, operador);
        }
    },
    {
        id: "qualidade",
        botao: document.getElementById("qualidade"),
        container: document.getElementById("dadosQualidade"),
        kpiMenu: document.getElementById("kpiMenuQualidade"),
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            carregarquantidadeEquip(dataInicio, dataFim, operador);
        }
    },
    {
        id: "financeiro",
        botao: document.getElementById("financeiro"),
        container: document.getElementById("dadosFinanceiro"),
        kpiMenu: document.getElementById("kpiMenuFinanceiro"),
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            carregarOrcamentosGeradosAnalise(dataInicio, dataFim, operador);
        }
    }
];

// Oculta todos os módulos
function ocultarTodosModulos() {
    modulos.forEach(({ container, kpiMenu, botao }) => {
        if (container) container.style.display = "none";
        if (kpiMenu) kpiMenu.style.display = "none";
        if (botao) botao.classList.remove("active");
    });
}

// Ativa um módulo específico
modulos.forEach(({ botao, container, kpiMenu, onAtivar }) => {
    if (!botao) return;

    botao.addEventListener("click", function () {
        const estaVisivel = container && container.style.display === "block";

        ocultarTodosModulos();

        if (!estaVisivel) {
            // Ativa visualmente o botão
            botao.classList.add("active");
            
            // Exibe container e menu KPI
            if (container) container.style.display = "block";
            if (kpiMenu) kpiMenu.style.display = "flex";
            
            // Executa callback de ativação
            onAtivar();
        }
    });
});

// 🎯 CONTROLE ÁREA ADMINISTRATIVA
document.getElementById("admin")?.addEventListener("click", function () {
    const adminDiv = document.getElementById("area-administrativa");
    if (adminDiv) {
        const visivel = adminDiv.style.display === "block";
        adminDiv.style.display = visivel ? "none" : "block";
    }
});


      

        /*Estruturação para abrir e esconder os graficos*/
      
  // 🔁 Mapeamento de links, containers e funções de carregamento
  //Recebimento
const graficosRecebimento = [
    {
        linkId: "quantidade-recebida",
        containerIds: ["graficoQuantidadeSemanal", "graficoQuantidadeMensal"],
        funcao: carregarQuantidadeRecebidaEGraficos
    },
    {
        linkId: "tempo-medio",
        containerIds: ["tempoMedioAnalise"],
        funcao: carregarGraficoTempoMedio
    },
    {
        linkId: "recebimento-setor",
        containerIds: ["recebimentosSetor"],
        funcao: carregarGraficoSetor
    },
    {
        linkId: "operacoes-origem-destino",
        containerIds: ["operacoesOrigemDestino"],
        funcao: carregarGraficoOperacoes
    },
    {
        linkId: "tempo-operacoes",
        containerIds: ["tempoOperacoes"],
        funcao: carregarGraficoTempoOperacoes
    },
    {
        linkId: "top-empresas",
        containerIds: ["topEmpresas"],
        funcao: carregarGraficoEmpresas
    },
    {
        linkId: "tendencia-mensal",
        containerIds: ["tendenciaMensal"],
        funcao: carregarGraficoTendenciaMensal
    }
];

// 🔁 Coleta containers únicos
const todosContainers = [...new Set(graficosRecebimento.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Destaque visual do botão ativo
function destacarBotaoGraficoAtivoRecebimento(botaoClicado) {
    graficosRecebimento.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove('grafico-ativo');
    });
    botaoClicado.classList.add('grafico-ativo');
}

// 🔁 Função para ocultar todos os containers
function ocultarTodosOsContainers() {
    todosContainers.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Obter filtros comuns
function obterFiltros() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos
graficosRecebimento.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);

    link?.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const visivel = primeiroContainer?.style.display === "block";

        // Oculta todos
        ocultarTodosOsContainers();

        if (!visivel) {
            destacarBotaoGraficoAtivoRecebimento(this);

            // Exibe os containers definidos
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            // Executa a função de carregamento
            const { dataInicio, dataFim, operador } = obterFiltros();
            funcao(dataInicio, dataFim, operador);
        }
    });
});


// 🔁 Mapeamento de links, containers e funções de carregamento (ANÁLISE)
const graficosAnalise = [
    {
        linkId: "equipamentos_finalizados",
        containerIds: ["graficoQuantidadeFinalisadaSemanal", "graficoQuantidadeFinalisadaMensal"],
        funcao: carregarProdutividadeAnalise
    },
    // {
    //     linkId: "financeiro",
    //     containerIds: ["graficoTicketContainer"],
    //     funcao: carregarTicketMedio
    // },
    {
        linkId: "tempo_medio_analise",
        containerIds: ["graficoTempoMedioAnaliseContainer"],
        funcao: carregarTempoMedioAnalise
    },
    {
        linkId: "parcial_vs_completa",
        containerIds: ["graficoParcialCompletaContainer"],
        funcao: carregarParcialCompleta
    },
    {
        linkId: "analises_por_cliente",
        containerIds: ["graficoAnalisesClienteContainer"],
        funcao: carregarAnalisesPorCliente
    }
];

// 🔁 Coleta todos os containers únicos usados nos gráficos de análise
const todosContainersAnalise = [...new Set(graficosAnalise.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Destaque visual do botão ativo
function destacarBotaoGraficoAtivoAnalise(botaoClicado) {
    graficosAnalise.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Oculta todos os containers da área de Análise
function ocultarTodosOsContainersAnalise() {
    todosContainersAnalise.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Obtem filtros
function obterFiltrosAnalise() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos para a área de Análise
graficosAnalise.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        // Oculta todos
        ocultarTodosOsContainersAnalise();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoAnalise(this);

            // Exibe os containers definidos
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            // Executa função com os filtros
            const { dataInicio, dataFim, operador } = obterFiltrosAnalise();
            funcao(dataInicio, dataFim, operador);
        }
    });
});


// 🔁 Mapeamento de links, containers e funções de carregamento (REPARO)
const graficosReparo = [
    {
        linkId: "quantidade_reparados",
        containerIds: ["graficoQuantidadeReparadaSemanal", "graficoQuantidadeReparadaMensal"],
        funcao: carregarProdutividadeReparo
    },
    {
        linkId: "tempoMedioReparoOperador",
        containerIds: ["graficoTempoReparoOperador"],
        funcao: carregarTempoReparoOperador
    },
    {
        linkId: "reparoPorCliente",
        containerIds: ["graficoTotalReparoCliente"],
        funcao: carregarReparosPorCliente
    }

];

// 🔁 Coleta todos os containers únicos
const todosContainersReparo = [...new Set(graficosReparo.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Função para destacar o botão ativo
function destacarBotaoGraficoAtivoReparo(botaoClicado) {
    graficosReparo.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Função para esconder todos os gráficos
function ocultarTodosOsContainersReparo() {
    todosContainersReparo.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Função para obter filtros
function obterFiltrosReparo() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos para o setor de Reparo
graficosReparo.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersReparo();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoReparo(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim, operador } = obterFiltrosReparo();
            funcao(dataInicio, dataFim, operador);
        }
    });
});

// 🔁 Mapeamento de links, containers e funções de carregamento (Qualidade)
const graficosQualidade = [
    {
        linkId: "quantidade_equipamentos",
        containerIds: ["graficoquantidadeequipamentos"],
        funcao: carregarquantidadeEquip
    },
    {
        linkId: "principais_servicos",
        containerIds: ["graficoprincipaisservicos"],
        funcao: carregarPrincipaisServicos
    },
    {
        linkId: "principais_laudos",
        containerIds: ["graficoprincipaislaudos"],
        funcao: carregarPrincipaisLaudos
    },
    {
        linkId: "quantidade_sem_conserto",
        containerIds: ["graficosemconserto"],
        funcao: carregarEquipSemConserto
    }
];

// 🔁 Coleta todos os containers únicos do setor Qualidade
const todosContainersQualidade = [...new Set(graficosQualidade.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Função para destacar o botão ativo no setor Qualidade
function destacarBotaoGraficoAtivoQualidade(botaoClicado) {
    graficosQualidade.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Função para esconder todos os containers do setor Qualidade
function ocultarTodosOsContainersQualidade() {
    todosContainersQualidade.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Função para obter filtros globais
function obterFiltrosQualidade() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos do setor de Qualidade
graficosQualidade.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersQualidade();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoQualidade(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim, operador } = obterFiltrosQualidade();
            funcao(dataInicio, dataFim, operador);
        }
    });
});

// 🔁 Mapeamento de links, containers e funções de carregamento (Financeiro)
const graficosFinanceiro = [
    {
        linkId: "orcamentos_gerados_analise",
        containerIds: ["orcamentosGeradosContainerAnalise"],
        funcao: carregarOrcamentosGeradosAnalise
    },
    {
        linkId: "orcamentos_gerados_reparo",
        containerIds: ["orcamentosGeradosContainerReparo"],
        funcao: carregarOrcamentosGeradosReparo
    },
    {
        linkId: "custos_produtos",
        containerIds: ["custosProdutosContainer"],
        funcao: carregarCustosProdutos
    }
    
];

// 🔁 Coleta todos os containers únicos do setor Qualidade
const todosContainersFinanceiro = [...new Set(graficosFinanceiro.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Função para destacar o botão ativo no setor Qualidade
function destacarBotaoGraficoAtivoFinanceiro(botaoClicado) {
    graficosFinanceiro.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Função para esconder todos os containers do setor Qualidade
function ocultarTodosOsContainersFinanceiro() {
    todosContainersFinanceiro.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Função para obter filtros globais
function obterFiltrosFinanceiro() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos do setor de Qualidade
graficosFinanceiro.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersFinanceiro();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoFinanceiro(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim, operador } = obterFiltrosFinanceiro();
            funcao(dataInicio, dataFim, operador);
        }
    });
});

function atualizarStatusOperadores() {
    fetch('https://kpi.stbextrema.com.br/DashBoard/backendDash/ADMIN/admin.php')
        .then(res => {
            // Verifica se a resposta está OK antes de fazer parse
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            // Verifica se há conteúdo antes de fazer parse JSON
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Resposta não é JSON válido");
            }
            return res.json();
        })
        .then(data => {
            if (!Array.isArray(data)) {
                console.warn('Dados de operadores inválidos:', data);
                return;
            }
            
            data.forEach((item, index) => {
                const box = document.getElementById(`operador${index + 1}`);
                if (!box) return;

                const statusSpan = box.querySelector('.status');
                const tempoSpan = box.querySelector('.tempo span');
                const setorSpan = box.querySelector('.setor span');
                const clienteSpan = box.querySelector('.cliente span');
                const qtdSpan = box.querySelector('.quantidade span');

                if (statusSpan) statusSpan.textContent = item.status || '--';
                if (tempoSpan) tempoSpan.textContent = item.tempo || '--';
                if (setorSpan) setorSpan.textContent = item.setor || '--';
                if (clienteSpan) clienteSpan.textContent = item.razao_social || '--';
                if (qtdSpan) qtdSpan.textContent = item.quantidade || '--';

                // Adiciona cor com base no status
                if (statusSpan && item.status) {
                    const statusClass = 'status-' + item.status.toLowerCase().replace(/\s+/g, '_');
                    statusSpan.className = `status ${statusClass}`;
                }
            });
        })
        .catch(error => {
            console.warn('⚠️ Erro ao atualizar status dos operadores:', error.message);
            // Não mostrar erro para o usuário - falha silenciosa para não poluir o console
        });
}

// 🔧 PASSO 4 - Temporariamente desabilitado para debug
// Atualiza a cada 10 segundos
// atualizarStatusOperadores();
// setInterval(atualizarStatusOperadores, 10000);

document.querySelectorAll('.btn-relatorio').forEach(btn => {
  btn.addEventListener('click', () => {
    const operadorParam = btn.dataset.operador; // ex.: "Rony_Rodrigues"
    const di = document.getElementById("data_inicial")?.value || "";
    const df = document.getElementById("data_final")?.value || "";

    const qs = new URLSearchParams();
    qs.set("operador", operadorParam);
    if (di) qs.set("data_inicio", di);
    if (df) qs.set("data_fim", df);

    const url = `https://kpi.stbextrema.com.br/DashBoard/backendDash/ADMIN/relatorio_operador_eventos.php?${qs.toString()}`;
    window.open(url, "_blank");
  });
});

// Para mobile, clique abre/fecha o menu
document.querySelectorAll(
    '.left-container, .left-container-analise, .left-container-reparo, .left-container-qualidade, .left-container-financeiro'
).forEach(menu => {
    let aberto = false;

    menu.addEventListener("click", () => {
        if (window.innerWidth < 900) {
            aberto = !aberto;
            menu.style.width = aberto ? "230px" : "80px";
        }
    });
});
Chart.defaults.plugins.legend.labels.color = "#050505ff";
Chart.defaults.scales = {
    x: {
        ticks: { color: "#0a0a0aff" },
        grid: { color: "rgba(255,255,255,0.07)" }
    },
    y: {
        ticks: { color: "#070707ff" },
        grid: { color: "rgba(255,255,255,0.07)" }
    }
};

});
</script>

        </section><!-- fim widgets-grid -->
        
        </section><!-- fim graficos-detalhados -->
        
    </main><!-- fim intelligence-canvas -->

<!-- ============================================
     JAVASCRIPT - RESUMO EXECUTIVO
     ============================================ -->
<script>
// 🎯 ESTADO GLOBAL - FONTE ÚNICA DE VERDADE
const filtroGlobal = {
    inicio: null,  // Backend espera 'inicio'
    fim: null,     // Backend espera 'fim'
    operador: ''
};

let dadosGlobaisCache = {};
let areaAtiva = null;
let carregando = false;

// 🎯 INICIALIZAÇÃO AUTOMÁTICA
document.addEventListener('DOMContentLoaded', function() {
    inicializarFiltros();
    configurarEventos();
    carregarResumoExecutivo();
});

// 🎯 INICIALIZAR FILTROS COM VALORES PADRÃO
function inicializarFiltros() {
    const hoje = new Date();
    const seteDiasAtras = new Date();
    seteDiasAtras.setDate(hoje.getDate() - 7);
    
    // Definir valores padrão
    const inputInicio = document.getElementById('data_inicial');
    const inputFim = document.getElementById('data_final');
    
    if (inputInicio && !inputInicio.value) {
        inputInicio.value = formatarData(seteDiasAtras);
    }
    if (inputFim && !inputFim.value) {
        inputFim.value = formatarData(hoje);
    }
    
    // Atualizar estado global
    atualizarFiltroGlobal();
}

// 🎯 ATUALIZAR ESTADO GLOBAL
function atualizarFiltroGlobal() {
    filtroGlobal.inicio = document.getElementById('data_inicial')?.value || '';
    filtroGlobal.fim = document.getElementById('data_final')?.value || '';
    filtroGlobal.operador = document.getElementById('operador')?.value || '';
    
    // Atualizar texto do período nos KPIs
    atualizarTextoPeriodo();
}

// 🎯 CONFIGURAR EVENTOS
function configurarEventos() {
    // Botão Filtrar - Controlador Global
    const btnFiltrar = document.getElementById('btnFiltrar');
    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', aplicarFiltroGlobal);
    }
    
    // Enter nos campos de data
    document.getElementById('data_inicial')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') aplicarFiltroGlobal();
    });
    document.getElementById('data_final')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') aplicarFiltroGlobal();
    });
}

// 🎯 APLICAR FILTRO GLOBAL (FONTE ÚNICA)
async function aplicarFiltroGlobal() {
    if (carregando) return;
    
    try {
        carregando = true;
        mostrarLoading();
        
        // Atualizar estado global
        atualizarFiltroGlobal();
        
        // Validar datas
        if (!filtroGlobal.inicio || !filtroGlobal.fim) {
            alert('Por favor, selecione o período (data inicial e final)');
            return;
        }
        
        // Verificar se data inicial é menor que final
        if (new Date(filtroGlobal.inicio) > new Date(filtroGlobal.fim)) {
            alert('Data inicial não pode ser maior que data final');
            return;
        }
        
        // Recarregar TUDO (insights e visão por área são gerados automaticamente)
        await carregarKPIsGlobais();
        
        // Se estiver vendo gráficos, atualizar também
        if (areaAtiva && document.getElementById('graficosDetalhados').style.display === 'block') {
            // Simular clique novamente na área para recarregar gráficos
            const botaoArea = document.querySelector(`button[onclick*="${areaAtiva}"]`);
            if (botaoArea) botaoArea.click();
        }
        
        // Mostrar indicador de filtro aplicado
        mostrarFiltroAplicado();
        
    } catch (error) {
        console.error('Erro ao aplicar filtro:', error);
        alert('Erro ao carregar dados. Tente novamente.');
    } finally {
        carregando = false;
        esconderLoading();
    }
}

// 🎯 CARREGAR RESUMO EXECUTIVO (INICIAL)
async function carregarResumoExecutivo() {
    try {
        mostrarLoading();
        atualizarFiltroGlobal();
        
        // KPIs 3.0 carregam automaticamente insights e visão por área
        await carregarKPIsGlobais();
        
    } catch (error) {
        console.error('Erro ao carregar resumo executivo:', error);
    } finally {
        esconderLoading();
    }
}

// 1️⃣ CARREGAR KPIs GLOBAIS
async function carregarKPIsGlobais() {
    try {
        // Constrói URLs com filtro global
        const baseUrl = '/DashBoard/backendDash/kpis';
        const urls = {
            totalProcessado: construirURLFiltrada(`${baseUrl}/kpi-total-processado.php`, filtroGlobal),
            tempoMedio: construirURLFiltrada(`${baseUrl}/kpi-tempo-medio.php`, filtroGlobal),
            taxaSucesso: construirURLFiltrada(`${baseUrl}/kpi-taxa-sucesso.php`, filtroGlobal),
            semConserto: construirURLFiltrada(`${baseUrl}/kpi-sem-conserto.php`, filtroGlobal),
            valorOrcado: construirURLFiltrada(`${baseUrl}/kpi-valor-orcado.php`, filtroGlobal)
        };
        
        console.log('🔄 Carregando KPIs refinados 3.0...', urls);
        
        // Busca todos os KPIs em paralelo
        const respostas = await fetchLote(urls);
        
        // Valida respostas
        Object.keys(respostas).forEach(key => {
            if (!validarRespostaKPI(respostas[key])) {
                console.warn(`⚠️ KPI ${key} não está no formato padrão`);
            }
        });
        
        // 🎯 KPI 3.0: RENDERIZAR KPIS REFINADOS
        renderizarKPIRefinado('globalTotal', 'cardTotalProcessado', 'globalTotalVariacao', respostas.totalProcessado);
        renderizarKPIRefinado('globalTempo', 'cardTempoMedio', 'globalTempoVariacao', respostas.tempoMedio);
        renderizarKPIRefinado('globalSucesso', 'cardTaxaSucesso', 'globalSucessoVariacao', respostas.taxaSucesso);
        renderizarKPIRefinado('globalSemConserto', 'cardSemConserto', 'globalSemConsertoVariacao', respostas.semConserto);
        renderizarKPIRefinado('globalValor', 'cardValorOrcado', 'globalValorVariacao', respostas.valorOrcado);
        
        // Cache dos dados para insights
        dadosGlobaisCache = {
            totalProcessado: respostas.totalProcessado.data.valor || 0,
            tempoMedio: respostas.tempoMedio.data.valor || 0,
            taxaSucesso: respostas.taxaSucesso.data.valor || 0,
            semConserto: respostas.semConserto.data.valor || 0,
            valorOrcado: respostas.valorOrcado.data.valor || '0,00',
            meta: respostas.totalProcessado.meta
        };
        
        // 🎯 INSIGHTS 2.0: Gerar a partir dos KPIs refinados
        gerarInsightsAPartirDosKPIs(respostas);
        
        // 🎯 VISÃO POR ÁREA 2.0: Montar a partir dos KPIs refinados
        montarVisaoPorArea(respostas);
        
        console.log('✅ KPIs refinados 3.0 carregados:', dadosGlobaisCache);
        
    } catch (error) {
        console.error('❌ Erro ao carregar KPIs globais:', error);
        
        // Mostra valores padrão em caso de erro
        animarValor('globalTotal', '---');
        animarValor('globalTempo', '---');
        animarValor('globalSucesso', '---');
        animarValor('globalSemConserto', '---');
        animarValor('globalValor', '---');
    }
}

// 🎯 KPI 3.0: RENDERIZAR KPI REFINADO
function renderizarKPIRefinado(elementoValorId, cardId, variacaoId, resposta) {
    try {
        const data = resposta.data;
        
        // Formatar valor principal
        let valorFormatado = data.valor;
        if (typeof data.valor === 'number') {
            valorFormatado = data.valor.toLocaleString('pt-BR');
        }
        
        // Adiciona unidade ao valor
        if (data.unidade === '%') {
            valorFormatado = `${valorFormatado}%`;
        } else if (data.unidade === 'R$') {
            valorFormatado = `R$ ${valorFormatado}`;
        } else if (data.unidade === 'horas' || data.unidade === 'minutos') {
            valorFormatado = `${valorFormatado} ${data.unidade}`;
        }
        
        // Atualiza valor principal
        animarValor(elementoValorId, valorFormatado);
        
        // Formata variação
        const variacao = data.variacao;
        const variacaoEl = document.getElementById(variacaoId);
        
        if (variacaoEl && variacao) {
            const icone = variacao.direcao === 'up' ? '↑' : 
                         variacao.direcao === 'down' ? '↓' : '→';
            const sinal = variacao.percentual > 0 ? '+' : '';
            
            // Define cor baseada no estado
            let cor = '#6b7280'; // cinza padrão
            if (data.estado === 'success') cor = '#10b981'; // verde
            if (data.estado === 'warning') cor = '#f59e0b'; // amarelo
            if (data.estado === 'critical') cor = '#ef4444'; // vermelho
            
            variacaoEl.innerHTML = `
                <span style="color: ${cor}; font-weight: 600;">
                    ${icone} ${sinal}${variacao.percentual}%
                </span>
                <span style="color: #6b7280; font-size: 0.8em;">
                    vs ${data.referencia.tipo === 'media_30d' ? 'média 30d' : 
                        data.referencia.tipo === 'meta' ? 'meta' : 'período anterior'}
                </span>
            `;
        }
        
        // Aplica classe de estado ao card
        const card = document.getElementById(cardId);
        if (card && data.estado) {
            card.classList.remove('kpi-success', 'kpi-warning', 'kpi-critical');
            card.classList.add(`kpi-${data.estado}`);
        }
        
    } catch (error) {
        console.error('Erro ao renderizar KPI refinado:', error);
        animarValor(elementoValorId, '---');
    }
}

// 🎯 INSIGHTS 2.0: GERAR A PARTIR DOS KPIS REFINADOS
function gerarInsightsAPartirDosKPIs(kpis) {
    const container = document.getElementById('insightsGridResumo');
    
    if (!kpis || !container) {
        return;
    }
    
    const insights = [];
    
    // Analisa cada KPI e gera insights baseados no estado
    
    // 1. Total Processado
    const totalProc = kpis.totalProcessado.data;
    if (totalProc.estado === 'critical') {
        insights.push({
            tipo: 'critical',
            categoria: 'operacional',
            titulo: `Volume ${totalProc.variacao.direcao === 'up' ? 'acima' : 'abaixo'} da capacidade`,
            mensagem: `${totalProc.variacao.percentual > 0 ? '+' : ''}${totalProc.variacao.percentual}% vs ${totalProc.referencia.tipo === 'media_30d' ? 'média 30 dias' : 'período anterior'}. Verificar recursos disponíveis.`,
            icone: 'exclamation-triangle'
        });
    } else if (totalProc.estado === 'warning') {
        insights.push({
            tipo: 'warning',
            categoria: 'operacional',
            titulo: `Variação no volume de processamento`,
            mensagem: `${totalProc.variacao.percentual > 0 ? '+' : ''}${totalProc.variacao.percentual}% vs ${totalProc.referencia.tipo === 'media_30d' ? 'média' : 'anterior'}. Monitorar tendência.`,
            icone: 'chart-line'
        });
    }
    
    // 2. Tempo Médio
    const tempo = kpis.tempoMedio.data;
    if (tempo.estado === 'critical') {
        insights.push({
            tipo: 'critical',
            categoria: 'desempenho',
            titulo: 'SLA ultrapassado',
            mensagem: `Tempo médio de ${tempo.valor} ${tempo.unidade} está acima do limite. ${tempo.variacao.percentual > 0 ? 'Aumentou' : 'Reduziu'} ${Math.abs(tempo.variacao.percentual)}% vs período anterior.`,
            icone: 'clock'
        });
    } else if (tempo.estado === 'warning') {
        insights.push({
            tipo: 'warning',
            categoria: 'desempenho',
            titulo: 'Atenção ao tempo de processamento',
            mensagem: `Tempo médio próximo do limite: ${tempo.valor} ${tempo.unidade}. ${tempo.variacao.percentual > 0 ? 'Aumento' : 'Redução'} de ${Math.abs(tempo.variacao.percentual)}%.`,
            icone: 'hourglass-half'
        });
    } else if (tempo.variacao.direcao === 'down' && Math.abs(tempo.variacao.percentual) > 10) {
        insights.push({
            tipo: 'success',
            categoria: 'desempenho',
            titulo: 'Melhoria no tempo de processamento',
            mensagem: `Tempo médio reduziu ${Math.abs(tempo.variacao.percentual)}% para ${tempo.valor} ${tempo.unidade}. Ótimo desempenho!`,
            icone: 'check-circle'
        });
    }
    
    // 3. Taxa de Sucesso
    const taxa = kpis.taxaSucesso.data;
    if (taxa.estado === 'critical') {
        insights.push({
            tipo: 'critical',
            categoria: 'qualidade',
            titulo: 'Taxa de sucesso crítica',
            mensagem: `Apenas ${taxa.valor}% de sucesso (meta: 85%). ${taxa.variacao.percentual < 0 ? 'Queda' : 'Variação'} de ${Math.abs(taxa.variacao.percentual)}% vs anterior.`,
            icone: 'times-circle'
        });
    } else if (taxa.estado === 'warning') {
        insights.push({
            tipo: 'warning',
            categoria: 'qualidade',
            titulo: 'Taxa de sucesso abaixo da meta',
            mensagem: `${taxa.valor}% de sucesso. Meta: 85%. ${taxa.variacao.percentual < 0 ? 'Caiu' : 'Variou'} ${Math.abs(taxa.variacao.percentual)}%.`,
            icone: 'exclamation-circle'
        });
    } else if (taxa.valor >= 90) {
        insights.push({
            tipo: 'success',
            categoria: 'qualidade',
            titulo: 'Excelente taxa de sucesso',
            mensagem: `${taxa.valor}% de sucesso. Superou a meta de 85%!`,
            icone: 'trophy'
        });
    }
    
    // 4. Sem Conserto
    const semConserto = kpis.semConserto.data;
    if (semConserto.estado === 'critical') {
        insights.push({
            tipo: 'critical',
            categoria: 'qualidade',
            titulo: 'Alto índice sem conserto',
            mensagem: `${semConserto.valor} equipamentos sem conserto. Aumento de ${semConserto.variacao.percentual}% vs período anterior.`,
            icone: 'tools'
        });
    } else if (semConserto.estado === 'warning') {
        insights.push({
            tipo: 'warning',
            categoria: 'qualidade',
            titulo: 'Aumento em equipamentos sem conserto',
            mensagem: `${semConserto.valor} equipamentos sem conserto (+${semConserto.variacao.percentual}%).`,
            icone: 'wrench'
        });
    }
    
    // 5. Valor Orçado
    const valor = kpis.valorOrcado.data;
    if (valor.estado === 'critical') {
        insights.push({
            tipo: 'critical',
            categoria: 'financeiro',
            titulo: 'Queda significativa em orçamentos',
            mensagem: `R$ ${valor.valor} orçado. Queda de ${Math.abs(valor.variacao.percentual)}% vs período anterior.`,
            icone: 'dollar-sign'
        });
    } else if (valor.estado === 'warning') {
        insights.push({
            tipo: 'warning',
            categoria: 'financeiro',
            titulo: 'Redução no valor orçado',
            mensagem: `R$ ${valor.valor} orçado. ${Math.abs(valor.variacao.percentual)}% abaixo do período anterior.`,
            icone: 'chart-line'
        });
    } else if (valor.variacao.direcao === 'up' && valor.variacao.percentual > 15) {
        insights.push({
            tipo: 'success',
            categoria: 'financeiro',
            titulo: 'Crescimento em orçamentos',
            mensagem: `R$ ${valor.valor} orçado. Crescimento de ${valor.variacao.percentual}%!`,
            icone: 'arrow-up'
        });
    }
    
    // Limita a 3 insights (prioridade: critical > warning > success)
    const insightsPriorizados = [
        ...insights.filter(i => i.tipo === 'critical'),
        ...insights.filter(i => i.tipo === 'warning'),
        ...insights.filter(i => i.tipo === 'success')
    ].slice(0, 3);
    
    // Renderiza insights
    if (insightsPriorizados.length === 0) {
        container.innerHTML = `
            <div class="insight-card insight-success">
                <div class="insight-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="insight-content">
                    <h4>Operação normal</h4>
                    <p>Todos os indicadores estão dentro dos parâmetros esperados.</p>
                </div>
            </div>
        `;
    } else {
        container.innerHTML = insightsPriorizados.map(insight => `
            <div class="insight-card insight-${insight.tipo}">
                <div class="insight-icon">
                    <i class="fas fa-${insight.icone}"></i>
                </div>
                <div class="insight-content">
                    <h4>${insight.titulo}</h4>
                    <p>${insight.mensagem}</p>
                </div>
            </div>
        `).join('');
    }
}

// 🎯 VISÃO POR ÁREA 2.0: MONTAR A PARTIR DOS KPIS REFINADOS
function montarVisaoPorArea(kpis) {
    if (!kpis) {
        return;
    }
    
    const totalProc = kpis.totalProcessado.data;
    const tempo = kpis.tempoMedio.data;
    const taxa = kpis.taxaSucesso.data;
    const semConserto = kpis.semConserto.data;
    const valor = kpis.valorOrcado.data;
    
    // Volumes estimados por área (baseados em fluxo típico)
    const volumeRecebimento = totalProc.valor;
    const volumeAnalise = Math.round(totalProc.valor * 0.87);
    const volumeReparo = Math.round(totalProc.valor * 0.81);
    const volumeQualidade = Math.round(totalProc.valor * 0.74);
    
    // Recebimento: Estado baseado no volume
    document.getElementById('areaRecebVolume').textContent = volumeRecebimento.toLocaleString('pt-BR');
    document.getElementById('areaRecebTempo').textContent = '~1-2 dias';
    atualizarStatusArea('areaRecebStatus', totalProc.estado === 'critical' ? 'critico' : totalProc.estado === 'warning' ? 'atencao' : 'normal');
    
    // Análise: Estado baseado no tempo
    document.getElementById('areaAnaliseVolume').textContent = volumeAnalise.toLocaleString('pt-BR');
    const tempoHoras = tempo.unidade === 'horas' ? tempo.valor : tempo.valor / 60;
    document.getElementById('areaAnaliseTempo').textContent = `~${Math.round(tempoHoras / 24)} dias`;
    atualizarStatusArea('areaAnaliseStatus', tempo.estado === 'critical' ? 'critico' : tempo.estado === 'warning' ? 'atencao' : 'normal');
    
    // Reparo: Estado baseado no tempo (70% do total)
    document.getElementById('areaReparoVolume').textContent = volumeReparo.toLocaleString('pt-BR');
    document.getElementById('areaReparoTempo').textContent = `~${Math.round(tempoHoras / 24 * 0.7)} dias`;
    atualizarStatusArea('areaReparoStatus', tempo.estado === 'critical' ? 'critico' : 'normal');
    
    // Qualidade: Estado baseado na taxa de sucesso
    document.getElementById('areaQualidadeLaudos').textContent = volumeQualidade.toLocaleString('pt-BR');
    const percSemConserto = totalProc.valor > 0 ? ((semConserto.valor / totalProc.valor) * 100).toFixed(1) : '0.0';
    document.getElementById('areaQualidadeSC').textContent = `${percSemConserto}%`;
    atualizarStatusArea('areaQualidadeStatus', taxa.estado === 'critical' ? 'critico' : taxa.estado === 'warning' ? 'atencao' : 'normal');
    
    // Financeiro: Estado baseado no valor orçado
    const numOrcamentos = Math.round(volumeAnalise * 0.92);
    document.getElementById('areaFinanceiroOrc').textContent = numOrcamentos.toLocaleString('pt-BR');
    document.getElementById('areaFinanceiroValor').textContent = `R$ ${valor.valor}`;
    atualizarStatusArea('areaFinanceiroStatus', valor.estado === 'critical' ? 'critico' : valor.estado === 'warning' ? 'atencao' : 'normal');
}

// 🎯 ATUALIZAR STATUS DA ÁREA
function atualizarStatusArea(elementId, status) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const statusConfig = {
        normal: { icon: 'circle', text: 'Normal', class: 'status-normal' },
        atencao: { icon: 'exclamation-circle', text: 'Atenção', class: 'status-atencao' },
        critico: { icon: 'exclamation-triangle', text: 'Crítico', class: 'status-critico' }
    };
    
    const config = statusConfig[status] || statusConfig.normal;
    element.innerHTML = `<i class="fas fa-${config.icon}"></i> ${config.text}`;
    element.className = `area-status ${config.class}`;
}

// 🎯 ABRIR ÁREA DETALHADA (GRÁFICOS)
function abrirArea(area) {
    areaAtiva = area;
    
    // Esconder resumo executivo
    document.getElementById('resumoExecutivo').style.display = 'none';
    
    // Mostrar gráficos detalhados
    document.getElementById('graficosDetalhados').style.display = 'block';
    
    // Atualizar título
    const titulos = {
        recebimento: 'Recebimento - Análise Detalhada',
        analise: 'Análise - Análise Detalhada',
        reparo: 'Reparo - Análise Detalhada',
        qualidade: 'Qualidade - Análise Detalhada',
        financeiro: 'Financeiro - Análise Detalhada'
    };
    document.getElementById('tituloAreaAtiva').textContent = titulos[area] || 'Detalhes';
    
    // Simular clique no botão do menu lateral correspondente
    const botaoArea = document.querySelector(`button[onclick*="${area}"]`);
    if (botaoArea) {
        botaoArea.click();
    }
}

// 🎯 VOLTAR AO RESUMO EXECUTIVO
function voltarAoResumo() {
    areaAtiva = null;
    
    // Mostrar resumo executivo
    document.getElementById('resumoExecutivo').style.display = 'block';
    
    // Esconder gráficos detalhados
    document.getElementById('graficosDetalhados').style.display = 'none';
    
    // Esconder widgets de gráficos
    document.querySelectorAll('.grafico-container').forEach(el => {
        el.style.display = 'none';
    });
    
    // Recarregar dados do resumo
    carregarResumoExecutivo();
}

// 🎯 FUNÇÕES AUXILIARES

// Atualizar texto do período visível
function atualizarTextoPeriodo() {
    if (!filtroGlobal.inicio || !filtroGlobal.fim) return;
    
    const dias = calcularDiferencaDias(filtroGlobal.inicio, filtroGlobal.fim);
    const textoFormatado = formatarPeriodoLegivel(filtroGlobal.inicio, filtroGlobal.fim);
    
    // Atualizar todos os elementos de período
    document.getElementById('globalPeriodo').textContent = textoFormatado;
}

// Mostrar loading overlay
function mostrarLoading() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-sync-alt fa-spin"></i>
                <p>Carregando dados...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

// Esconder loading overlay
function esconderLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Mostrar indicador de filtro aplicado
function mostrarFiltroAplicado() {
    const status = document.getElementById('filtroStatus');
    if (status) {
        status.style.display = 'flex';
        status.style.animation = 'slideIn 0.3s ease';
        
        // Esconder após 3 segundos
        setTimeout(() => {
            status.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                status.style.display = 'none';
            }, 300);
        }, 3000);
    }
}

// Animar mudança de valor
function animarValor(elementId, novoValor) {
    const elemento = document.getElementById(elementId);
    if (!elemento) return;
    
    elemento.style.opacity = '0.5';
    setTimeout(() => {
        elemento.textContent = novoValor;
        elemento.style.opacity = '1';
    }, 150);
}

// Formatar data para input
function formatarData(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

// Formatar período legível
function formatarPeriodoLegivel(dataInicio, dataFim) {
    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);
    const dias = calcularDiferencaDias(dataInicio, dataFim);
    
    const opcoes = { day: '2-digit', month: '2-digit', year: 'numeric' };
    const inicioStr = inicio.toLocaleDateString('pt-BR', opcoes);
    const fimStr = fim.toLocaleDateString('pt-BR', opcoes);
    
    if (dias === 7) return 'Últimos 7 dias';
    if (dias === 30) return 'Últimos 30 dias';
    if (dias === 90) return 'Últimos 90 dias';
    
    return `${inicioStr} a ${fimStr} (${dias} dias)`;
}

// Calcular diferença em dias
function calcularDiferencaDias(dataInicio, dataFim) {
    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);
    const diff = Math.abs(fim - inicio);
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}
</script>

</body>
</html>
