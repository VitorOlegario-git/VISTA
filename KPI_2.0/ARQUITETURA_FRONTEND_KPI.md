# 🏗️ ARQUITETURA DE FRONTEND - SISTEMA KPI VISTA
## Visão Moderna e Escalável para Dashboard Executivo

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Sistema:** VISTA - KPI 2.0  
**Autor:** Equipe Frontend VISTA

---

## 📑 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Três Camadas de Visualização](#três-camadas-de-visualização)
3. [Estrutura de Diretórios](#estrutura-de-diretórios)
4. [Responsabilidades por Camada](#responsabilidades-por-camada)
5. [Fluxo de Dados (KPI → UI)](#fluxo-de-dados-kpi--ui)
6. [Componentes Reutilizáveis](#componentes-reutilizáveis)
7. [Padrões de Consumo de API](#padrões-de-consumo-de-api)
8. [Estado e Gerenciamento de Dados](#estado-e-gerenciamento-de-dados)
9. [Performance e Otimizações](#performance-e-otimizações)
10. [Roadmap de Evolução](#roadmap-de-evolução)

---

## 1. VISÃO GERAL

### 1.1 Objetivos da Arquitetura

A arquitetura de frontend do VISTA foi projetada para:

✅ **Separação de Responsabilidades:** Três camadas distintas (Executivo, Operacional, Analítico)  
✅ **Escalabilidade:** Suportar crescimento de KPIs sem refatoração massiva  
✅ **Reutilização:** Componentes compartilhados entre camadas  
✅ **Performance:** Carregamento otimizado e cache inteligente  
✅ **Manutenibilidade:** Código modular e bem documentado  
✅ **Compatibilidade:** Backend JSON padronizado (contrato estável)

---

### 1.2 Stack Tecnológico

| Camada | Tecnologia | Justificativa |
|--------|------------|---------------|
| **Estrutura** | Vanilla JavaScript ES6+ | Sem dependências externas, performance nativa |
| **Gráficos** | Chart.js 4.x | Biblioteca leve, flexível e bem documentada |
| **HTTP** | Fetch API | Nativa do browser, promises modernas |
| **Estilo** | CSS3 + Variáveis CSS | Temas dinâmicos, fácil manutenção |
| **Build** | Nenhum (sem bundler) | Simplicidade operacional, debug direto |
| **Versionamento** | Git + Semantic Versioning | Rastreabilidade de mudanças |

---

### 1.3 Princípios de Design

**Progressive Enhancement:**
- Core funcional sem JavaScript (HTML puro)
- JavaScript adiciona interatividade
- CSS adiciona estilização avançada

**Mobile First:**
- Design responsivo desde o início
- Breakpoints: 320px, 768px, 1024px, 1440px

**Performance First:**
- Lazy loading de gráficos
- Debounce em buscas
- Cache de dados no localStorage

**Accessibility (A11y):**
- ARIA labels em todos os cards
- Navegação por teclado (tab, enter, esc)
- Contraste WCAG AA

---

## 2. TRÊS CAMADAS DE VISUALIZAÇÃO

### 2.1 Camada 1: Visão Executiva (C-Level)

**Público-alvo:** CEO, CFO, Diretoria  
**Objetivo:** Visão panorâmica da operação em 5-10 segundos  
**Características:**

- 📊 **5 KPIs Globais:** Cards com valor, variação, estado (success/warning/critical)
- 🔍 **3 Insights Automáticos:** Exceções detectadas por IA (motor de insights)
- 📈 **2-3 Gráficos Estratégicos:** Tendências de longo prazo (30/60/90 dias)
- 🎨 **Visualização Densa:** Máximo de informação em espaço mínimo
- ⚡ **Carregamento Rápido:** Todas as queries paralelas, < 2s para primeira renderização

**Arquivo Principal:** `DashboardExecutivo.php`

**Exemplo Visual:**
```
┌──────────────────────────────────────────────────────┐
│  🏠 Dashboard Executivo - VISTA                      │
├──────────────────────────────────────────────────────┤
│                                                        │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐    │
│  │ 1.250  │  │ 4d 12h │  │ 92.3%  │  │  45    │    │
│  │ Volume │  │ Tempo  │  │ Sucesso│  │Sem Cons│    │
│  │ +5.9%↑ │  │ +2.1%↑ │  │ -1.2%↓ │  │ +15%↑  │    │
│  └────────┘  └────────┘  └────────┘  └────────┘    │
│                                                        │
│  ┌────────┐                                          │
│  │ R$ 185K│  🔍 INSIGHTS AUTOMÁTICOS                │
│  │ Orçado │  🚨 Gargalo em Reparo (35% acima)       │
│  │ +8.3%↑ │  ⚠️  Volume alto (+20%) - verificar cap │
│  └────────┘  ✅ Qualidade estável (95% aprovação)   │
│                                                        │
│  📈 Tendência de Volume (30 dias)   [Gráfico Line] │
│  📊 Top 5 Clientes                  [Gráfico Bar]   │
└──────────────────────────────────────────────────────┘
```

---

### 2.2 Camada 2: Visão Operacional (Gestores)

**Público-alvo:** Gerentes, Supervisores, Coordenadores  
**Objetivo:** Gestão do dia-a-dia e identificação de gargalos  
**Características:**

- 📦 **KPIs por Área:** Recebimento, Análise, Reparo, Qualidade, Expedição
- 📊 **5-7 Gráficos por Área:** Evolução temporal, comparativos, rankings
- 📝 **Tabela Operacional:** Registros detalhados com busca e ordenação
- 🔍 **Filtros Avançados:** Período, operador, setor, status, cliente
- 🔗 **Drill-down:** Clique em card → navega para área detalhada

**Arquivo Principal:** `AreaDetalhada.php?area=recebimento|analise|reparo|qualidade`

**Exemplo Visual (Área de Recebimento):**
```
┌──────────────────────────────────────────────────────┐
│  📦 Área Detalhada - RECEBIMENTO                     │
├──────────────────────────────────────────────────────┤
│                                                        │
│  🔍 FILTROS: [07/01 - 14/01] [Todos operadores] 🔄  │
│                                                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │  Backlog │  │ Remessas │  │Equipament│           │
│  │    340   │  │    125   │  │   1.250  │           │
│  │  +12% ⚠️ │  │   +5% ✅ │  │   +8% ✅ │           │
│  └──────────┘  └──────────┘  └──────────┘           │
│                                                        │
│  📈 Evolução Diária (7 dias)     [Gráfico Bar]      │
│  👥 Produtividade por Operador   [Gráfico H-Bar]    │
│  🏢 Top 10 Clientes              [Gráfico Doughnut] │
│                                                        │
│  📋 TABELA OPERACIONAL (340 registros)               │
│  ┌────┬──────┬────────┬─────┬─────────┬────────┐   │
│  │ NF │ Data │ Cliente│ Qtd │ Operador│ Status │   │
│  ├────┼──────┼────────┼─────┼─────────┼────────┤   │
│  │1234│14/01 │ACME   │  15 │ João    │Pendente│   │
│  │1235│14/01 │XYZ    │  23 │ Maria   │Enviado │   │
│  └────┴──────┴────────┴─────┴─────────┴────────┘   │
│                                                        │
│  🔍 Buscar: [____________] 🔄 Atualizar              │
└──────────────────────────────────────────────────────┘
```

---

### 2.3 Camada 3: Visão Analítica (Analistas)

**Público-alvo:** Analistas de dados, BI, Auditoria  
**Objetivo:** Exploração profunda, comparações, exportação de dados  
**Características:**

- 📊 **Comparação de Períodos:** Mês atual vs anterior, YoY, QoQ
- 📈 **Gráficos Avançados:** Sankey, heatmaps, scatter plots
- 📥 **Exportação:** CSV, Excel, PDF (relatórios)
- 🔍 **Filtros Combinados:** AND/OR lógico, ranges customizados
- 📊 **Métricas Calculadas:** Média móvel, desvio padrão, tendências

**Arquivo Futuro:** `DashboardAnalitico.php` (roadmap)

**Exemplo Visual (futuro):**
```
┌──────────────────────────────────────────────────────┐
│  📊 Dashboard Analítico - VISTA                      │
├──────────────────────────────────────────────────────┤
│                                                        │
│  🔍 COMPARAÇÃO DE PERÍODOS                           │
│  [Jan/2026] vs [Dez/2025] vs [Jan/2025]             │
│                                                        │
│  📈 Volume Processado (comparativo 3 períodos)       │
│  [Gráfico Line com 3 séries]                         │
│                                                        │
│  📊 Correlação Tempo x Volume (Scatter)              │
│  📈 Fluxo de Equipamentos (Sankey Diagram)           │
│  🗺️ Mapa de Calor - Gargalos (Heatmap)             │
│                                                        │
│  📥 EXPORTAR: [📄 PDF] [📊 Excel] [📋 CSV]           │
└──────────────────────────────────────────────────────┘
```

---

## 3. ESTRUTURA DE DIRETÓRIOS

### 3.1 Estrutura Atual (Pré-Refatoração)

```
DashBoard/
├── backendDash/                    # Backend PHP (KPIs, queries)
│   ├── kpis/                       # KPIs globais (5 endpoints)
│   ├── recebimentoPHP/             # Dados de recebimento
│   ├── analisePHP/                 # Dados de análise
│   ├── reparoPHP/                  # Dados de reparo
│   └── qualidadePHP/               # Dados de qualidade
│
└── frontendDash/                   # Frontend (UI, JavaScript)
    ├── DashboardExecutivo.php      # Camada 1: Visão Executiva
    ├── AreaDetalhada.php           # Camada 2: Visão Operacional
    ├── DashRecebimento.php         # (legado - migrar para AreaDetalhada)
    │
    ├── cssDash/                    # Estilos
    │   ├── dashboard-executivo.css
    │   ├── area-detalhada.css
    │   └── dashrecebimento.css
    │
    └── jsDash/                     # JavaScript (modular)
        ├── fetch-helpers.js        # 🔹 Funções de API
        ├── insights-engine.js      # 🔹 Motor de insights
        │
        ├── area-detalhada-recebimento.js
        ├── area-detalhada-analise.js
        ├── area-detalhada-reparo.js
        ├── area-detalhada-qualidade.js
        │
        ├── recebimentoJS/          # Scripts específicos (legado)
        ├── analisePHP/
        ├── reparoPHP/
        └── qualidadeJS/
```

---

### 3.2 Estrutura Proposta (Refatoração Futura)

```
DashBoard/
├── backendDash/                    # Backend PHP (sem mudanças)
│   └── [estrutura atual mantida]
│
└── frontendDash/
    │
    ├── views/                      # 🆕 Páginas HTML/PHP
    │   ├── executivo/
    │   │   └── DashboardExecutivo.php
    │   ├── operacional/
    │   │   ├── AreaDetalhada.php
    │   │   └── _partials/          # Componentes reutilizáveis
    │   │       ├── kpi-card.php
    │   │       ├── insight-card.php
    │   │       ├── tabela-operacional.php
    │   │       └── filtros-periodo.php
    │   └── analitico/              # 🔮 Futuro
    │       └── DashboardAnalitico.php
    │
    ├── assets/                     # 🆕 Recursos estáticos
    │   ├── css/
    │   │   ├── core/               # Estilos base (reset, variáveis)
    │   │   │   ├── _variables.css
    │   │   │   ├── _reset.css
    │   │   │   └── _utilities.css
    │   │   ├── components/         # Componentes reutilizáveis
    │   │   │   ├── kpi-card.css
    │   │   │   ├── insight-card.css
    │   │   │   ├── chart-container.css
    │   │   │   └── data-table.css
    │   │   └── views/              # Estilos específicos de página
    │   │       ├── dashboard-executivo.css
    │   │       ├── area-detalhada.css
    │   │       └── dashboard-analitico.css
    │   │
    │   └── js/
    │       ├── core/               # 🆕 Core framework
    │       │   ├── App.js          # Inicialização global
    │       │   ├── Router.js       # Gerenciamento de rotas (SPA futuro)
    │       │   └── State.js        # Gerenciamento de estado global
    │       │
    │       ├── services/           # 🆕 Camada de serviços (API)
    │       │   ├── KpiService.js   # Consumo de KPIs
    │       │   ├── InsightService.js
    │       │   ├── ChartService.js
    │       │   └── AuthService.js  # Autenticação (futuro)
    │       │
    │       ├── components/         # 🆕 Componentes UI (Web Components futuro)
    │       │   ├── KpiCard.js
    │       │   ├── InsightCard.js
    │       │   ├── ChartContainer.js
    │       │   ├── DataTable.js
    │       │   └── FilterPanel.js
    │       │
    │       ├── views/              # 🆕 Lógica de páginas
    │       │   ├── DashboardExecutivo.js
    │       │   ├── AreaDetalhadaRecebimento.js
    │       │   ├── AreaDetalhadaAnalise.js
    │       │   ├── AreaDetalhadaReparo.js
    │       │   └── AreaDetalhadaQualidade.js
    │       │
    │       └── utils/              # 🆕 Utilitários
    │           ├── DateUtils.js    # Formatação de datas
    │           ├── NumberUtils.js  # Formatação de números
    │           ├── ValidationUtils.js
    │           └── CacheUtils.js   # Gerenciamento de cache
    │
    └── config/                     # 🆕 Configurações
        ├── kpi-catalog.json        # Catálogo de KPIs (metadados)
        ├── chart-themes.json       # Temas de gráficos
        └── endpoints.json          # Mapeamento de URLs
```

---

### 3.3 Migração Incremental

**Fase 1 (Atual):** Estrutura híbrida mantida  
**Fase 2 (Próximos 3 meses):** Refatorar JavaScript para `services/` e `components/`  
**Fase 3 (6 meses):** Migrar CSS para `core/` + `components/` + `views/`  
**Fase 4 (12 meses):** Implementar SPA com Router.js (opcional)

---

## 4. RESPONSABILIDADES POR CAMADA

### 4.1 Camada de Serviços (Services)

**Responsabilidade:** Comunicação com backend, transformação de dados, cache.

#### 📄 `KpiService.js`

```javascript
/**
 * SERVIÇO DE KPIs - Consumo de endpoints backend
 */
class KpiService {
    constructor() {
        this.cache = new Map();
        this.cacheDuration = 5 * 60 * 1000; // 5 minutos
    }

    /**
     * Buscar KPI individual
     * @param {string} kpiName - Nome do KPI (ex: 'backlog-atual')
     * @param {Object} filters - Filtros (inicio, fim, operador, setor)
     * @returns {Promise<Object>} Dados do KPI
     */
    async fetchKpi(kpiName, filters = {}) {
        const cacheKey = this._buildCacheKey(kpiName, filters);
        
        // Verificar cache
        if (this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < this.cacheDuration) {
                return cached.data;
            }
        }

        // Buscar do backend
        const url = this._buildUrl(kpiName, filters);
        const response = await fetchKPI(url); // fetch-helpers.js

        // Armazenar em cache
        this.cache.set(cacheKey, {
            data: response,
            timestamp: Date.now()
        });

        return response;
    }

    /**
     * Buscar múltiplos KPIs em paralelo
     * @param {Array<Object>} requests - Array de {kpiName, filters}
     * @returns {Promise<Object>} Mapa de resultados {kpiName: data}
     */
    async fetchMultiple(requests) {
        const promises = requests.map(req => 
            this.fetchKpi(req.kpiName, req.filters)
                .then(data => ({ [req.kpiName]: data }))
                .catch(err => ({ [req.kpiName]: { error: err.message } }))
        );

        const results = await Promise.all(promises);
        return Object.assign({}, ...results);
    }

    /**
     * Invalidar cache (útil após mudança de período)
     */
    clearCache() {
        this.cache.clear();
    }

    // Métodos privados
    _buildUrl(kpiName, filters) { /* ... */ }
    _buildCacheKey(kpiName, filters) { /* ... */ }
}
```

**Responsabilidades:**
- ✅ Construir URLs com query params
- ✅ Gerenciar cache (5 minutos default)
- ✅ Paralelizar requisições
- ✅ Tratamento de erro centralizado
- ✅ Invalidação de cache

---

#### 📄 `ChartService.js`

```javascript
/**
 * SERVIÇO DE GRÁFICOS - Configuração e renderização de Chart.js
 */
class ChartService {
    constructor() {
        this.chartInstances = new Map();
        this.themes = {
            light: { /* ... */ },
            dark: { /* ... */ }
        };
        this.currentTheme = 'light';
    }

    /**
     * Criar ou atualizar gráfico
     * @param {string} canvasId - ID do canvas
     * @param {string} type - Tipo (line, bar, doughnut, etc)
     * @param {Object} data - Dados do gráfico
     * @param {Object} options - Opções customizadas
     */
    renderChart(canvasId, type, data, options = {}) {
        // Destruir instância anterior se existir
        if (this.chartInstances.has(canvasId)) {
            this.chartInstances.get(canvasId).destroy();
        }

        const canvas = document.getElementById(canvasId);
        const ctx = canvas.getContext('2d');

        // Mesclar opções padrão com customizadas
        const mergedOptions = this._mergeOptions(type, options);

        // Criar nova instância
        const chart = new Chart(ctx, {
            type: type,
            data: data,
            options: mergedOptions
        });

        this.chartInstances.set(canvasId, chart);
        return chart;
    }

    /**
     * Atualizar dados de gráfico existente
     */
    updateChart(canvasId, newData) {
        const chart = this.chartInstances.get(canvasId);
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    }

    /**
     * Destruir todos os gráficos
     */
    destroyAll() {
        this.chartInstances.forEach(chart => chart.destroy());
        this.chartInstances.clear();
    }

    // Métodos privados
    _mergeOptions(type, custom) { /* ... */ }
}
```

**Responsabilidades:**
- ✅ Configuração padrão de Chart.js
- ✅ Temas (light/dark)
- ✅ Gerenciamento de instâncias
- ✅ Atualização otimizada (sem recriar canvas)

---

### 4.2 Camada de Componentes (Components)

**Responsabilidade:** Renderização de UI, eventos, atualização visual.

#### 📄 `KpiCard.js`

```javascript
/**
 * COMPONENTE: Card de KPI
 * Renderiza um KPI com valor, variação, estado e ícone
 */
class KpiCard {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.data = null;
    }

    /**
     * Renderizar card com dados
     * @param {Object} kpiData - Dados do backend (contrato padronizado)
     */
    render(kpiData) {
        this.data = kpiData;

        const estado = kpiData.data.estado || 'success';
        const valor = this._formatValor(kpiData.data.valor, kpiData.data.unidade);
        const variacao = kpiData.data.variacao?.percentual || 0;
        const direcao = kpiData.data.variacao?.direcao || 'neutro';

        const html = `
            <div class="kpi-card kpi-card--${estado}" data-kpi="${kpiData.kpi}">
                <div class="kpi-card__header">
                    <i class="kpi-card__icon ${this._getIcon(kpiData.kpi)}"></i>
                    <span class="kpi-card__label">${this._getLabel(kpiData.kpi)}</span>
                </div>
                <div class="kpi-card__body">
                    <div class="kpi-card__value">${valor}</div>
                    <div class="kpi-card__badge kpi-card__badge--${direcao}">
                        <i class="fa fa-arrow-${direcao === 'alta' ? 'up' : 'down'}"></i>
                        ${Math.abs(variacao).toFixed(1)}%
                    </div>
                </div>
                <div class="kpi-card__footer">
                    <span class="kpi-card__context">${kpiData.data.contexto}</span>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
        this._attachEvents();
    }

    /**
     * Atualizar apenas valor (animado)
     */
    updateValue(newValue) {
        // Animação de contagem (countUp.js ou similar)
    }

    // Métodos privados
    _formatValor(valor, unidade) { /* ... */ }
    _getIcon(kpiName) { /* ... */ }
    _getLabel(kpiName) { /* ... */ }
    _attachEvents() { /* ... */ }
}
```

**Responsabilidades:**
- ✅ Renderizar HTML do card
- ✅ Aplicar classes CSS baseadas em estado
- ✅ Formatar valores (números, percentuais, moedas)
- ✅ Animações de transição
- ✅ Eventos de clique (drill-down)

---

#### 📄 `DataTable.js`

```javascript
/**
 * COMPONENTE: Tabela de Dados Operacional
 * Tabela com busca, ordenação, paginação
 */
class DataTable {
    constructor(containerId, config = {}) {
        this.container = document.getElementById(containerId);
        this.config = {
            columns: [],
            data: [],
            pageSize: 50,
            searchable: true,
            sortable: true,
            ...config
        };
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.searchTerm = '';
    }

    /**
     * Renderizar tabela completa
     */
    render() {
        const html = `
            <div class="data-table">
                ${this._renderToolbar()}
                ${this._renderTable()}
                ${this._renderPagination()}
            </div>
        `;
        this.container.innerHTML = html;
        this._attachEvents();
    }

    /**
     * Atualizar dados (sem recriar estrutura)
     */
    setData(newData) {
        this.config.data = newData;
        this._updateTableBody();
    }

    // Métodos privados
    _renderToolbar() { /* Busca + botão refresh */ }
    _renderTable() { /* <table> com thead + tbody */ }
    _renderPagination() { /* Controles de página */ }
    _attachEvents() { /* Click handlers */ }
    _updateTableBody() { /* Apenas <tbody> */ }
    _applyFilters() { /* Busca + ordenação */ }
}
```

**Responsabilidades:**
- ✅ Renderizar tabela HTML
- ✅ Busca client-side (debounce 300ms)
- ✅ Ordenação por coluna
- ✅ Paginação (lazy loading opcional)
- ✅ Highlight de linhas críticas

---

### 4.3 Camada de Views (Views)

**Responsabilidade:** Orquestração de componentes, lógica de página, roteamento.

#### 📄 `DashboardExecutivo.js`

```javascript
/**
 * VIEW: Dashboard Executivo (Camada 1)
 * Orquestra carregamento de 5 KPIs + 3 insights + 2 gráficos
 */
class DashboardExecutivoView {
    constructor() {
        this.kpiService = new KpiService();
        this.chartService = new ChartService();
        this.insightEngine = new InsightsEngineV2();
        
        this.kpiCards = {
            volume: new KpiCard('kpi-volume'),
            tempo: new KpiCard('kpi-tempo'),
            sucesso: new KpiCard('kpi-sucesso'),
            semConserto: new KpiCard('kpi-sem-conserto'),
            valor: new KpiCard('kpi-valor')
        };

        this.currentFilters = {
            inicio: this._getDefaultInicio(),
            fim: this._getDefaultFim()
        };
    }

    /**
     * Inicializar dashboard
     */
    async init() {
        this._setupEventListeners();
        await this.loadData();
        this._startAutoRefresh(60000); // 1 minuto
    }

    /**
     * Carregar todos os dados em paralelo
     */
    async loadData() {
        try {
            // Exibir loading
            this._showLoading();

            // Buscar 5 KPIs globais em paralelo
            const kpiRequests = [
                { kpiName: 'total-processado', filters: this.currentFilters },
                { kpiName: 'tempo-medio', filters: this.currentFilters },
                { kpiName: 'taxa-sucesso', filters: this.currentFilters },
                { kpiName: 'sem-conserto', filters: this.currentFilters },
                { kpiName: 'valor-orcado', filters: this.currentFilters }
            ];

            const kpis = await this.kpiService.fetchMultiple(kpiRequests);

            // Renderizar cards
            this.kpiCards.volume.render(kpis['total-processado']);
            this.kpiCards.tempo.render(kpis['tempo-medio']);
            this.kpiCards.sucesso.render(kpis['taxa-sucesso']);
            this.kpiCards.semConserto.render(kpis['sem-conserto']);
            this.kpiCards.valor.render(kpis['valor-orcado']);

            // Gerar insights
            const insights = this.insightEngine.analisar({
                remessas: kpis['total-processado'].data.valor,
                equipRec: kpis['total-processado'].data.valor,
                equipExp: kpis['total-processado'].data.valor * 0.85, // Estimativa
                conclusao: kpis['taxa-sucesso'].data.valor,
                valor: kpis['valor-orcado'].data.valor
            });

            this._renderInsights(insights);

            // Carregar gráficos
            await this._loadCharts();

            // Esconder loading
            this._hideLoading();

        } catch (error) {
            console.error('Erro ao carregar dashboard:', error);
            this._showError('Falha ao carregar dados. Tente novamente.');
        }
    }

    // Métodos privados
    _setupEventListeners() { /* Event handlers */ }
    _showLoading() { /* Exibe spinner */ }
    _hideLoading() { /* Remove spinner */ }
    _showError(msg) { /* Toast de erro */ }
    _renderInsights(insights) { /* Renderiza 3 insights */ }
    _loadCharts() { /* Carrega gráficos */ }
    _startAutoRefresh(interval) { /* setInterval */ }
    _getDefaultInicio() { /* -7 dias */ }
    _getDefaultFim() { /* hoje */ }
}

// Inicialização global
document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new DashboardExecutivoView();
    dashboard.init();
});
```

**Responsabilidades:**
- ✅ Orquestrar carregamento de dados
- ✅ Gerenciar estado de filtros
- ✅ Coordenar componentes (cards, insights, gráficos)
- ✅ Auto-refresh periódico
- ✅ Tratamento de erro global

---

## 5. FLUXO DE DADOS (KPI → UI)

### 5.1 Fluxo Completo (8 Etapas)

```
┌────────────────────────────────────────────────────────────┐
│                  FLUXO DE DADOS - KPI → UI                  │
└────────────────────────────────────────────────────────────┘

1. USUÁRIO INTERAGE COM UI
   └─ Clique em botão "7 dias" ou "Atualizar"
   └─ Event listener captura ação

2. VIEW ATUALIZA FILTROS
   └─ DashboardExecutivoView.currentFilters = { inicio: '...', fim: '...' }

3. VIEW SOLICITA DADOS AO SERVICE
   └─ this.kpiService.fetchKpi('total-processado', this.currentFilters)

4. SERVICE VERIFICA CACHE
   ├─ Se cache válido (< 5 min): retorna dados armazenados
   └─ Se cache inválido: continua para etapa 5

5. SERVICE FAZ REQUISIÇÃO HTTP
   └─ fetch('/DashBoard/backendDash/kpis/kpi-total-processado.php?inicio=...&fim=...')
   └─ Headers: { 'Authorization': 'Bearer TOKEN' } (se autenticado)

6. BACKEND PROCESSA REQUISIÇÃO
   ├─ validarAutenticacao() ✅
   ├─ auditarExecucaoKpi() 📝
   ├─ Query SQL no banco de dados 🗄️
   ├─ Cálculo de métricas (valor, referência, variação) 📊
   └─ kpiResponse() retorna JSON padronizado 📤

7. SERVICE RECEBE RESPOSTA JSON
   {
     "meta": { "inicio": "2026-01-07", "fim": "2026-01-14", ... },
     "data": {
       "valor": "1250",
       "unidade": "equipamentos",
       "variacao": { "percentual": 5.9, "direcao": "alta" },
       "estado": "success",
       ...
     }
   }
   └─ Armazena em cache
   └─ Retorna para View

8. VIEW PASSA DADOS AO COMPONENTE
   └─ this.kpiCards.volume.render(kpis['total-processado'])

9. COMPONENTE RENDERIZA UI
   ├─ Formata valores (1250 → "1.250")
   ├─ Aplica classes CSS baseadas em estado (success → verde)
   ├─ Renderiza HTML no DOM
   └─ Anima transição (fade-in, countUp)

10. UI ATUALIZADA
    └─ Usuário vê card atualizado em tela
```

---

### 5.2 Diagrama de Sequência

```
Usuário       View                Service             Backend           Banco
  │            │                    │                   │                │
  │─ Clique ──▶│                    │                   │                │
  │            │─ fetchKpi() ──────▶│                   │                │
  │            │                    │─ Verifica cache   │                │
  │            │                    │                   │                │
  │            │                    │─ fetch(url) ─────▶│                │
  │            │                    │                   │─ SQL query ───▶│
  │            │                    │                   │◀─ ResultSet ───│
  │            │                    │                   │                │
  │            │                    │                   │─ Calcula       │
  │            │                    │                   │  variação      │
  │            │                    │◀─ JSON response ──│                │
  │            │◀─ Promise resolve ─│                   │                │
  │            │─ render() ────────▶│                   │                │
  │            │                    │  (Componente)     │                │
  │◀─ UI ─────│                    │                   │                │
```

---

### 5.3 Tratamento de Erro (Cascata)

```
1. BACKEND: Try-catch em PHP
   └─ kpiError('kpi', 'Erro SQL', 500)
   └─ JSON: { "error": true, "message": "..." }

2. SERVICE: Try-catch em JavaScript
   └─ catch (error) { console.error(error); return { error: true }; }

3. VIEW: Verificação de erro
   └─ if (kpis['total-processado'].error) { this._showError(...); }

4. COMPONENTE: Estado de erro
   └─ <div class="kpi-card kpi-card--error">
         <span>Falha ao carregar</span>
         <button>Tentar novamente</button>
       </div>
```

---

## 6. COMPONENTES REUTILIZÁVEIS

### 6.1 Biblioteca de Componentes

| Componente | Arquivo | Usado Em | Responsabilidade |
|------------|---------|----------|------------------|
| **KpiCard** | `KpiCard.js` | Executivo, Operacional | Renderizar card de KPI |
| **InsightCard** | `InsightCard.js` | Executivo | Exibir insight automatizado |
| **ChartContainer** | `ChartContainer.js` | Todas as views | Wrapper de Chart.js |
| **DataTable** | `DataTable.js` | Operacional, Analítico | Tabela com busca/ordenação |
| **FilterPanel** | `FilterPanel.js` | Todas as views | Filtros de período/operador |
| **LoadingSpinner** | `LoadingSpinner.js` | Global | Indicador de carregamento |
| **Toast** | `Toast.js` | Global | Notificações (sucesso/erro) |
| **Modal** | `Modal.js` | Operacional | Diálogos (confirmação, detalhes) |

---

### 6.2 Exemplo de Uso (Composição)

```javascript
// DashboardExecutivo.js - Composição de componentes

class DashboardExecutivoView {
    constructor() {
        // Serviços
        this.kpiService = new KpiService();
        this.chartService = new ChartService();

        // Componentes
        this.filterPanel = new FilterPanel('filters-container', {
            onFilterChange: (filters) => this.onFiltersChange(filters)
        });

        this.kpiCards = [
            new KpiCard('kpi-volume', { 
                label: 'Volume Processado',
                icon: 'fa-box',
                onClick: () => this.navigateTo('recebimento')
            }),
            new KpiCard('kpi-tempo', { 
                label: 'Tempo Médio',
                icon: 'fa-clock'
            }),
            // ... outros cards
        ];

        this.insightCards = [
            new InsightCard('insight-1'),
            new InsightCard('insight-2'),
            new InsightCard('insight-3')
        ];

        this.charts = [
            new ChartContainer('chart-tendencia', { type: 'line' }),
            new ChartContainer('chart-clientes', { type: 'bar' })
        ];
    }

    async init() {
        // Renderizar filtros
        this.filterPanel.render();

        // Carregar dados
        await this.loadData();
    }

    async loadData() {
        const filters = this.filterPanel.getFilters();
        const kpis = await this.kpiService.fetchMultiple([...]);

        // Renderizar cada componente
        this.kpiCards[0].render(kpis['total-processado']);
        this.kpiCards[1].render(kpis['tempo-medio']);
        // ...
    }
}
```

---

## 7. PADRÕES DE CONSUMO DE API

### 7.1 Contrato de Resposta (Backend)

**Sucesso:**
```json
{
  "meta": {
    "inicio": "2026-01-07",
    "fim": "2026-01-14",
    "operador": "Todos",
    "timestamp": "2026-01-15 10:30:45",
    "kpi_version": "3.1.0",
    "kpi_owner": "Equipe Backend VISTA",
    "last_updated": "2026-01-15"
  },
  "data": {
    "valor": "1250",
    "unidade": "equipamentos",
    "periodo": "Últimos 7 dias",
    "contexto": "Total processado",
    "referencia": {
      "tipo": "media_30d",
      "valor": "1180"
    },
    "variacao": {
      "percentual": 5.9,
      "direcao": "alta"
    },
    "estado": "success"
  }
}
```

**Erro:**
```json
{
  "error": true,
  "message": "Parâmetros inicio e fim são obrigatórios",
  "kpi": "backlog-recebimento",
  "timestamp": "2026-01-15 10:30:45"
}
```

---

### 7.2 Padrão de Consumo (Frontend)

```javascript
// ✅ CORRETO: Uso de fetch-helpers.js
async function carregarKpi() {
    try {
        const url = '/DashBoard/backendDash/kpis/kpi-total-processado.php?inicio=07/01/2026&fim=14/01/2026';
        const response = await fetchKPI(url);

        // Validar estrutura
        if (!response.data || !response.meta) {
            throw new Error('Resposta inválida do servidor');
        }

        // Usar dados
        const valor = response.data.valor;
        const estado = response.data.estado;

        return response;

    } catch (error) {
        console.error('Erro ao carregar KPI:', error);
        throw error; // Propagar para View tratar
    }
}
```

```javascript
// ❌ INCORRETO: Fetch direto sem tratamento
async function carregarKpi() {
    const response = await fetch(url); // Sem tratamento de erro
    const data = await response.json(); // Pode falhar se não for JSON
    return data.data.valor; // Assume estrutura sem validar
}
```

---

### 7.3 Autenticação (Header)

```javascript
// Service adiciona automaticamente token
class KpiService {
    async fetchKpi(kpiName, filters) {
        const token = localStorage.getItem('vista_api_token');
        
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.status === 401) {
            // Token inválido ou expirado
            this._redirectToLogin();
            return;
        }

        return response.json();
    }

    _redirectToLogin() {
        window.location.href = '/FrontEnd/tela_login.php';
    }
}
```

---

## 8. ESTADO E GERENCIAMENTO DE DADOS

### 8.1 Estado Global (State.js)

```javascript
/**
 * GERENCIADOR DE ESTADO GLOBAL
 * Implementação simples de pub/sub para comunicação entre componentes
 */
class State {
    constructor() {
        this.state = {
            filters: {
                inicio: this._getDefaultInicio(),
                fim: this._getDefaultFim(),
                operador: 'Todos',
                setor: null
            },
            user: {
                nome: null,
                role: null,
                authenticated: false
            },
            theme: 'light'
        };

        this.listeners = new Map();
    }

    /**
     * Obter valor do estado
     */
    get(key) {
        return key.split('.').reduce((obj, k) => obj?.[k], this.state);
    }

    /**
     * Atualizar estado e notificar listeners
     */
    set(key, value) {
        const keys = key.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((obj, k) => obj[k] = obj[k] || {}, this.state);
        
        target[lastKey] = value;

        // Notificar listeners
        this._notify(key, value);
    }

    /**
     * Registrar listener para mudanças
     */
    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);
    }

    /**
     * Remover listener
     */
    unsubscribe(key, callback) {
        if (this.listeners.has(key)) {
            const callbacks = this.listeners.get(key);
            const index = callbacks.indexOf(callback);
            if (index > -1) callbacks.splice(index, 1);
        }
    }

    // Métodos privados
    _notify(key, value) {
        if (this.listeners.has(key)) {
            this.listeners.get(key).forEach(cb => cb(value));
        }
    }

    _getDefaultInicio() { /* ... */ }
    _getDefaultFim() { /* ... */ }
}

// Singleton global
window.AppState = new State();
```

---

### 8.2 Exemplo de Uso (Filtros Globais)

```javascript
// FilterPanel.js - Atualiza estado ao mudar filtros
class FilterPanel {
    onPeriodChange(inicio, fim) {
        AppState.set('filters.inicio', inicio);
        AppState.set('filters.fim', fim);
    }
}

// DashboardExecutivo.js - Reage a mudanças de filtros
class DashboardExecutivoView {
    constructor() {
        // Inscrever-se para mudanças de filtros
        AppState.subscribe('filters.inicio', () => this.loadData());
        AppState.subscribe('filters.fim', () => this.loadData());
    }

    async loadData() {
        const filters = {
            inicio: AppState.get('filters.inicio'),
            fim: AppState.get('filters.fim')
        };

        const kpis = await this.kpiService.fetchMultiple([...], filters);
        // ...
    }
}
```

---

## 9. PERFORMANCE E OTIMIZAÇÕES

### 9.1 Cache Estratégico

**Níveis de Cache:**

1. **Browser Cache (HTTP Headers):**
   ```php
   // Backend: endpoint-helpers.php
   header('Cache-Control: public, max-age=300'); // 5 minutos
   header('ETag: ' . md5($jsonResponse));
   ```

2. **Service Cache (JavaScript):**
   ```javascript
   class KpiService {
       cache = new Map();
       cacheDuration = 5 * 60 * 1000; // 5 minutos
   }
   ```

3. **LocalStorage (Histórico):**
   ```javascript
   // Armazenar médias históricas
   localStorage.setItem('kpi_historico', JSON.stringify({
       volumeMedio: 1180,
       tempoMedio: 4.5,
       ultimaAtualizacao: '2026-01-15T10:30:00Z'
   }));
   ```

---

### 9.2 Lazy Loading de Gráficos

```javascript
// Carregar gráficos apenas quando visíveis (Intersection Observer)
class ChartContainer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.loaded = false;

        // Observar visibilidade
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.loaded) {
                    this.load();
                }
            });
        });

        observer.observe(this.canvas);
    }

    async load() {
        this.loaded = true;
        const data = await this.fetchData();
        this.render(data);
    }
}
```

---

### 9.3 Debounce em Buscas

```javascript
// utils/debounce.js
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// DataTable.js - Aplicar debounce na busca
class DataTable {
    constructor() {
        this.debouncedSearch = debounce(this._performSearch.bind(this), 300);
    }

    onSearchInput(event) {
        const term = event.target.value;
        this.debouncedSearch(term); // Espera 300ms sem digitação
    }

    _performSearch(term) {
        // Filtrar dados
    }
}
```

---

### 9.4 Métricas de Performance

**Objetivos:**

| Métrica | Alvo | Crítico |
|---------|------|---------|
| **First Contentful Paint (FCP)** | < 1.5s | > 3s |
| **Largest Contentful Paint (LCP)** | < 2.5s | > 4s |
| **Time to Interactive (TTI)** | < 3.5s | > 7s |
| **API Response Time** | < 500ms | > 2s |
| **Total Bundle Size** | < 200KB | > 500KB |

**Monitoramento:**
```javascript
// Instrumentação de performance
performance.mark('kpi-load-start');
await this.kpiService.fetchKpi('total-processado', filters);
performance.mark('kpi-load-end');

performance.measure('kpi-load', 'kpi-load-start', 'kpi-load-end');
const measure = performance.getEntriesByName('kpi-load')[0];
console.log(`KPI carregado em ${measure.duration}ms`);
```

---

## 10. ROADMAP DE EVOLUÇÃO

### 10.1 Fase 1: Consolidação (1-3 meses)

**Objetivo:** Estabilizar arquitetura atual, refatorar código legado.

- [ ] Migrar `DashRecebimento.php` para `AreaDetalhada.php?area=recebimento`
- [ ] Consolidar JavaScript em `services/` e `components/`
- [ ] Criar `KpiService.js` e `ChartService.js`
- [ ] Implementar cache de 5 minutos em todos os KPIs
- [ ] Criar biblioteca de componentes (KpiCard, InsightCard, DataTable)
- [ ] Documentar catálogo de componentes (Storybook ou similar)

---

### 10.2 Fase 2: Modernização (3-6 meses)

**Objetivo:** Melhorar performance, adicionar features avançadas.

- [ ] Implementar `State.js` para gerenciamento global
- [ ] Lazy loading de gráficos (Intersection Observer)
- [ ] Implementar Service Worker (PWA) para cache offline
- [ ] Criar Dashboard Analítico (Camada 3)
- [ ] Exportação de dados (CSV, Excel, PDF)
- [ ] Temas dark/light mode
- [ ] Notificações push (Web Push API)

---

### 10.3 Fase 3: Inovação (6-12 meses)

**Objetivo:** Transformar em SPA, adicionar IA, mobile app.

- [ ] Migrar para SPA (Single Page Application) com `Router.js`
- [ ] Web Components nativos (Custom Elements)
- [ ] Progressive Web App (PWA) completo (offline, install)
- [ ] Aplicativo mobile (React Native ou Flutter)
- [ ] Dashboard preditivo com Machine Learning (previsão de gargalos)
- [ ] Integração com assistente virtual (Alexa, Google Assistant)
- [ ] Realidade aumentada (AR) para visualização 3D de fluxos

---

## 📌 RESUMO EXECUTIVO

### ✅ Arquitetura Definida

**3 Camadas de Visualização:**
1. **Executiva:** 5 KPIs globais + 3 insights + 2 gráficos (< 10s para decisão)
2. **Operacional:** Drill-down por área (Recebimento, Análise, Reparo, Qualidade)
3. **Analítica:** Exploração profunda, comparações, exportações (roadmap)

**Estrutura de Diretórios:**
```
frontendDash/
├── views/          # Páginas HTML/PHP
├── assets/
│   ├── css/        # Estilos (core, components, views)
│   └── js/
│       ├── core/       # App.js, Router.js, State.js
│       ├── services/   # KpiService, ChartService, AuthService
│       ├── components/ # KpiCard, DataTable, FilterPanel
│       ├── views/      # Lógica de páginas
│       └── utils/      # DateUtils, NumberUtils, CacheUtils
└── config/         # kpi-catalog.json, endpoints.json
```

**Responsabilidades:**
- **Services:** Comunicação com backend, cache, transformação de dados
- **Components:** Renderização de UI, eventos, atualização visual
- **Views:** Orquestração de componentes, lógica de página

**Fluxo de Dados:**
```
Usuário → View → Service → Backend → Banco
                   ↓          ↓
                 Cache     JSON
                   ↓
              Component → UI
```

---

### 🎯 Características-chave

✅ **Escalável:** Componentes reutilizáveis, fácil adicionar novos KPIs  
✅ **Performática:** Cache (5 min), lazy loading, debounce, < 2.5s LCP  
✅ **Compatível:** Backend JSON padronizado, sem breaking changes  
✅ **Modular:** Serviços independentes, componentes compostos  
✅ **Manutenível:** Código organizado, documentado, versionado

---

### 📊 Próximos Passos

1. **Refatorar JavaScript legado** para estrutura proposta
2. **Criar biblioteca de componentes** (KpiCard, DataTable, ChartContainer)
3. **Implementar KpiService.js** com cache de 5 minutos
4. **Migrar DashRecebimento.php** para AreaDetalhada.php
5. **Documentar padrões** de desenvolvimento (style guide)

---

**Fim da Documentação**

---

*Gerado automaticamente pelo Sistema VISTA - KPI 2.0*  
*Para dúvidas técnicas, consulte a equipe de frontend*  
*Versão: 1.0 - 15/01/2026*
