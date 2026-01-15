# 🔍 FILTRO GLOBAL DE PERÍODO - SISTEMA KPI VISTA
## Fonte Única da Verdade para Temporalidade do Dashboard

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Sistema:** VISTA - KPI 2.0  
**Autor:** Equipe Frontend VISTA

---

## 📑 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Anatomia do Componente](#anatomia-do-componente)
3. [Estado Global (GlobalState)](#estado-global-globalstate)
4. [Fluxo de Atualização em Cascata](#fluxo-de-atualização-em-cascata)
5. [Integração com Backend](#integração-com-backend)
6. [Componente PeriodFilter](#componente-periodfilter)
7. [Sincronização URL (Deep Linking)](#sincronização-url-deep-linking)
8. [Persistência Local](#persistência-local)
9. [Casos de Uso](#casos-de-uso)
10. [Checklist de Implementação](#checklist-de-implementação)

---

## 1. VISÃO GERAL

### 1.1 Propósito

O **Filtro Global de Período** é o componente responsável por gerenciar a **fonte única da verdade** sobre o período temporal aplicado a todos os KPIs do dashboard.

**Princípios Fundamentais:**

✅ **Single Source of Truth:** Um único estado global controla o período  
✅ **Zero Duplicação:** Nenhum componente mantém período local  
✅ **UX Previsível:** Atualização síncrona e visual de todos os elementos  
✅ **Persistência:** Período sobrevive a recarregamento de página  
✅ **Deep Linking:** URL compartilhável com período aplicado  

---

### 1.2 Objetivos

**Funcionalidade:**
- Permitir seleção de período pré-definido (hoje, 7d, 30d, 90d)
- Permitir intervalo customizado (datepicker)
- Propagar mudança para todos os KPIs automaticamente
- Atualizar URL sem reload (History API)
- Salvar preferência no localStorage

**Performance:**
- Debounce de 300ms em datepicker (evitar requests excessivos)
- Loading state durante atualização de KPIs
- Cancelamento de requests anteriores (AbortController)

**Acessibilidade:**
- Navegação por teclado (Tab, Enter, Esc)
- ARIA labels descritivos
- Feedback visual durante loading

---

### 1.3 Critérios de Aceite

| Critério | Validação |
|----------|-----------|
| **Fonte Única da Verdade** | GlobalState.period é a única referência de período |
| **Zero Duplicação** | Nenhum componente armazena período localmente |
| **UX Previsível** | Mudança de período atualiza todos os KPIs sincronizadamente |
| **Persistência** | Período persiste após F5 (localStorage) |
| **Deep Linking** | URL reflete período atual (?period=last_30_days) |
| **Performance** | Debounce de 300ms, cancelamento de requests |

---

## 2. ANATOMIA DO COMPONENTE

### 2.1 Estrutura Visual

```
┌─────────────────────────────────────────────────────────────────┐
│  📊 Dashboard Executivo - VISTA                          👤 User │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  📅 Período:  [ Últimos 30 dias ▼ ]  [ 01/12/2025 - 15/01/2026 ]│ ← Filtro Global
│                                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │  1.250   │  │  4d 12h  │  │  92.3%   │  │   45     │        │
│  │  Backlog │  │  Ciclo   │  │  Qualid. │  │  Reparo  │        │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘        │
│                                                                   │
│  📈 Gráfico de Tendências (30 dias)                              │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  /\    /\                                               │    │
│  │ /  \  /  \__                                            │    │
│  └─────────────────────────────────────────────────────────┘    │
└───────────────────────────────────────────────────────────────┘
```

**Elementos:**

1. **Dropdown de Presets** (`<select>`)
   - Hoje
   - Últimos 7 dias
   - Últimos 30 dias
   - Últimos 90 dias
   - Intervalo customizado

2. **Range de Datas** (readonly `<input>`)
   - Exibe datas efetivas (dd/mm/yyyy - dd/mm/yyyy)
   - Clicável quando "Intervalo customizado" selecionado

3. **Datepicker Modal** (quando customizado)
   - Data início + Data fim
   - Validação (início ≤ fim)
   - Botão "Aplicar" + "Cancelar"

---

### 2.2 Estados do Componente

```
┌─────────────────────────────────────────────────────────────┐
│  Idle (pronto)                                              │
│  📅 Período: [ Últimos 30 dias ▼ ]  [ 01/12/25 - 15/01/26 ]│
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Loading (atualizando KPIs)                                 │
│  📅 Período: [ Últimos 7 dias ▼ ]  [ 08/01/26 - 15/01/26 ] │
│  [🔄 Atualizando...]                                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Custom Mode (datepicker aberto)                            │
│  📅 Período: [ Intervalo customizado ▼ ]                    │
│  ┌─────────────────────────────────────────────┐            │
│  │ Selecionar Intervalo                        │            │
│  │ Data Início: [01/01/2026]                   │            │
│  │ Data Fim:    [15/01/2026]                   │            │
│  │           [Cancelar]  [Aplicar]             │            │
│  └─────────────────────────────────────────────┘            │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. ESTADO GLOBAL (GlobalState)

### 3.1 Estrutura de Dados

O estado global é gerenciado por um objeto singleton `GlobalState` que armazena o período atual e notifica observadores quando há mudanças.

```javascript
/**
 * 🌍 GLOBAL STATE - Fonte Única da Verdade
 * 
 * Singleton que gerencia o período global aplicado a todos os KPIs.
 * Implementa o padrão Observer para notificar componentes sobre mudanças.
 */
class GlobalState {
    constructor() {
        // Estado privado
        this._state = {
            period: {
                type: 'preset',        // 'preset' | 'custom'
                preset: 'last_30_days', // 'today' | 'last_7_days' | 'last_30_days' | 'last_90_days'
                inicio: null,           // 'YYYY-MM-DD' (quando custom)
                fim: null,              // 'YYYY-MM-DD' (quando custom)
                descricao: 'Últimos 30 dias' // String legível para exibição
            },
            loading: false,             // Flag de atualização em progresso
            lastUpdate: null            // Timestamp da última atualização
        };
        
        // Lista de observadores (callbacks)
        this._observers = [];
        
        // AbortController para cancelar requests em andamento
        this._abortController = null;
        
        // Inicializa a partir da URL ou localStorage
        this._initializeFromUrlOrStorage();
    }
    
    /**
     * Retorna o período atual (somente leitura)
     */
    getPeriod() {
        return { ...this._state.period };
    }
    
    /**
     * Retorna o estado completo (somente leitura)
     */
    getState() {
        return { ...this._state };
    }
    
    /**
     * Define um novo período (preset)
     * 
     * @param {string} preset - 'today' | 'last_7_days' | 'last_30_days' | 'last_90_days'
     */
    setPeriodPreset(preset) {
        const descricoes = {
            'today': 'Hoje',
            'last_7_days': 'Últimos 7 dias',
            'last_30_days': 'Últimos 30 dias',
            'last_90_days': 'Últimos 90 dias'
        };
        
        this._state.period = {
            type: 'preset',
            preset: preset,
            inicio: null,
            fim: null,
            descricao: descricoes[preset]
        };
        
        this._notifyChange();
    }
    
    /**
     * Define um período customizado
     * 
     * @param {string} inicio - Data inicial (YYYY-MM-DD)
     * @param {string} fim - Data final (YYYY-MM-DD)
     */
    setPeriodCustom(inicio, fim) {
        // Valida formato
        if (!this._isValidDate(inicio) || !this._isValidDate(fim)) {
            throw new Error('Formato de data inválido. Use YYYY-MM-DD');
        }
        
        // Valida ordem
        if (new Date(fim) < new Date(inicio)) {
            throw new Error('Data final deve ser posterior à data inicial');
        }
        
        this._state.period = {
            type: 'custom',
            preset: null,
            inicio: inicio,
            fim: fim,
            descricao: this._formatDateRange(inicio, fim)
        };
        
        this._notifyChange();
    }
    
    /**
     * Adiciona observador para mudanças de estado
     * 
     * @param {Function} callback - Função chamada quando estado muda
     * @returns {Function} Função para remover observador
     */
    subscribe(callback) {
        this._observers.push(callback);
        
        // Retorna função de unsubscribe
        return () => {
            this._observers = this._observers.filter(obs => obs !== callback);
        };
    }
    
    /**
     * Define estado de loading
     */
    setLoading(loading) {
        this._state.loading = loading;
        this._notifyObservers({ type: 'loading', loading });
    }
    
    /**
     * Cancela requests em andamento
     */
    cancelPendingRequests() {
        if (this._abortController) {
            this._abortController.abort();
        }
        this._abortController = new AbortController();
    }
    
    /**
     * Retorna signal para AbortController
     */
    getAbortSignal() {
        if (!this._abortController) {
            this._abortController = new AbortController();
        }
        return this._abortController.signal;
    }
    
    /**
     * Retorna parâmetros de query para API
     */
    getApiParams() {
        const { type, preset, inicio, fim } = this._state.period;
        
        if (type === 'preset') {
            return { period: preset };
        } else {
            // Converte YYYY-MM-DD para dd/mm/yyyy (formato do backend)
            const inicioFormatted = this._toBackendFormat(inicio);
            const fimFormatted = this._toBackendFormat(fim);
            return { inicio: inicioFormatted, fim: fimFormatted };
        }
    }
    
    // ============================================
    // MÉTODOS PRIVADOS
    // ============================================
    
    _notifyChange() {
        // Persiste no localStorage
        this._saveToLocalStorage();
        
        // Atualiza URL
        this._updateUrl();
        
        // Atualiza timestamp
        this._state.lastUpdate = new Date().toISOString();
        
        // Cancela requests anteriores
        this.cancelPendingRequests();
        
        // Notifica observadores
        this._notifyObservers({ type: 'period', period: this._state.period });
    }
    
    _notifyObservers(event) {
        this._observers.forEach(callback => {
            try {
                callback(event);
            } catch (error) {
                console.error('Erro ao notificar observador:', error);
            }
        });
    }
    
    _initializeFromUrlOrStorage() {
        // 1. Tenta carregar da URL (prioridade)
        const urlParams = new URLSearchParams(window.location.search);
        const periodParam = urlParams.get('period');
        const inicioParam = urlParams.get('inicio');
        const fimParam = urlParams.get('fim');
        
        if (periodParam) {
            this.setPeriodPreset(periodParam);
            return;
        }
        
        if (inicioParam && fimParam) {
            // Converte dd/mm/yyyy para YYYY-MM-DD
            const inicio = this._fromBackendFormat(inicioParam);
            const fim = this._fromBackendFormat(fimParam);
            this.setPeriodCustom(inicio, fim);
            return;
        }
        
        // 2. Tenta carregar do localStorage
        const saved = localStorage.getItem('vista_global_period');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                this._state.period = parsed;
                return;
            } catch (error) {
                console.warn('Erro ao carregar período do localStorage:', error);
            }
        }
        
        // 3. Fallback: Últimos 30 dias (padrão)
        this.setPeriodPreset('last_30_days');
    }
    
    _saveToLocalStorage() {
        try {
            localStorage.setItem('vista_global_period', JSON.stringify(this._state.period));
        } catch (error) {
            console.warn('Erro ao salvar período no localStorage:', error);
        }
    }
    
    _updateUrl() {
        const params = this.getApiParams();
        const urlParams = new URLSearchParams(window.location.search);
        
        // Remove parâmetros antigos
        urlParams.delete('period');
        urlParams.delete('inicio');
        urlParams.delete('fim');
        
        // Adiciona novos parâmetros
        Object.entries(params).forEach(([key, value]) => {
            urlParams.set(key, value);
        });
        
        // Atualiza URL sem reload
        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
        window.history.replaceState({}, '', newUrl);
    }
    
    _isValidDate(dateString) {
        const regex = /^\d{4}-\d{2}-\d{2}$/;
        if (!regex.test(dateString)) return false;
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }
    
    _formatDateRange(inicio, fim) {
        const inicioDate = new Date(inicio);
        const fimDate = new Date(fim);
        return `${this._formatDate(inicioDate)} a ${this._formatDate(fimDate)}`;
    }
    
    _formatDate(date) {
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    _toBackendFormat(dateString) {
        // YYYY-MM-DD -> dd/mm/yyyy
        const [year, month, day] = dateString.split('-');
        return `${day}/${month}/${year}`;
    }
    
    _fromBackendFormat(dateString) {
        // dd/mm/yyyy -> YYYY-MM-DD
        const [day, month, year] = dateString.split('/');
        return `${year}-${month}-${day}`;
    }
}

// Instância singleton global
const globalState = new GlobalState();
```

---

### 3.2 API do GlobalState

| Método | Descrição | Retorno |
|--------|-----------|---------|
| `getPeriod()` | Retorna período atual (readonly) | `{ type, preset, inicio, fim, descricao }` |
| `getState()` | Retorna estado completo (readonly) | `{ period, loading, lastUpdate }` |
| `setPeriodPreset(preset)` | Define período pré-definido | `void` |
| `setPeriodCustom(inicio, fim)` | Define período customizado | `void` |
| `subscribe(callback)` | Adiciona observador | `Function (unsubscribe)` |
| `setLoading(loading)` | Define estado de loading | `void` |
| `cancelPendingRequests()` | Cancela requests em andamento | `void` |
| `getAbortSignal()` | Retorna signal para fetch | `AbortSignal` |
| `getApiParams()` | Retorna params para API | `{ period: string } \| { inicio: string, fim: string }` |

---

### 3.3 Eventos do GlobalState

O `GlobalState` emite dois tipos de eventos para observadores:

```javascript
// Evento de mudança de período
{
    type: 'period',
    period: {
        type: 'preset',
        preset: 'last_7_days',
        inicio: null,
        fim: null,
        descricao: 'Últimos 7 dias'
    }
}

// Evento de mudança de loading
{
    type: 'loading',
    loading: true
}
```

---

## 4. FLUXO DE ATUALIZAÇÃO EM CASCATA

### 4.1 Diagrama de Fluxo

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. Usuário altera período no PeriodFilter                      │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2. PeriodFilter chama globalState.setPeriodPreset()            │
│    ou globalState.setPeriodCustom()                             │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3. GlobalState executa:                                         │
│    ✓ Cancela requests anteriores (AbortController)             │
│    ✓ Salva no localStorage                                      │
│    ✓ Atualiza URL (History API)                                │
│    ✓ Notifica todos os observadores                            │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4. Observadores reagem em paralelo:                             │
│    ┌─────────────────────────────────────────────────┐          │
│    │ DashboardView                                   │          │
│    │ ✓ Exibe loading global                          │          │
│    │ ✓ Dispara atualização de todos os KPIs         │          │
│    └─────────────────────────────────────────────────┘          │
│    ┌─────────────────────────────────────────────────┐          │
│    │ KpiCard (5 instâncias)                          │          │
│    │ ✓ Cada card mostra skeleton screen             │          │
│    │ ✓ Chama KpiService.fetchKpi() com novo período │          │
│    └─────────────────────────────────────────────────┘          │
│    ┌─────────────────────────────────────────────────┐          │
│    │ ChartComponent (2 instâncias)                   │          │
│    │ ✓ Mostra loading indicator                      │          │
│    │ ✓ Chama KpiService.fetchMultiple() com período │          │
│    └─────────────────────────────────────────────────┘          │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5. KpiService faz requests paralelos:                           │
│    Promise.all([                                                │
│      fetch('/kpi-backlog-atual?period=last_7_days'),           │
│      fetch('/kpi-ciclo-medio?period=last_7_days'),             │
│      fetch('/kpi-qualidade?period=last_7_days'),               │
│      ...                                                        │
│    ], { signal: globalState.getAbortSignal() })                │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6. Backend processa com resolvePeriod():                        │
│    ✓ period=last_7_days → inicio/fim calculados                │
│    ✓ Executa query SQL com filtro de data                      │
│    ✓ Retorna JSON padronizado { meta, data }                   │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7. Componentes recebem dados e renderizam:                      │
│    ✓ KpiCard.render(kpiData) - Atualiza valor/variação         │
│    ✓ ChartComponent.update(chartData) - Re-renderiza gráfico   │
│    ✓ DashboardView.setLoading(false) - Remove loading global   │
└─────────────────────────────────────────────────────────────────┘
```

---

### 4.2 Sequência Temporal

```
t=0ms     Usuário clica "Últimos 7 dias"
t=10ms    GlobalState.setPeriodPreset('last_7_days')
t=20ms    localStorage atualizado
t=30ms    URL atualizada (sem reload)
t=40ms    Observadores notificados (5 KpiCards + 2 Charts + DashboardView)
t=50ms    8 componentes mostram skeleton/loading
t=60ms    8 requests paralelos disparados (fetch com AbortSignal)
t=200ms   Backend responde (cache/query rápida)
t=220ms   Componentes renderizam novos dados
t=250ms   Animação de transição (fade-in)
t=300ms   Loading state removido - UI estável
```

**Tempo Total:** ~300ms para atualização completa do dashboard

---

### 4.3 Tratamento de Erros

```javascript
// No observador (ex: KpiCard)
globalState.subscribe(async (event) => {
    if (event.type !== 'period') return;
    
    try {
        // Mostra loading
        this.renderLoading();
        
        // Busca dados com novo período
        const params = globalState.getApiParams();
        const signal = globalState.getAbortSignal();
        const kpiData = await KpiService.fetchKpi('backlog-atual', params, signal);
        
        // Renderiza sucesso
        this.render(kpiData);
        
    } catch (error) {
        // Request foi abortado (período mudou novamente)
        if (error.name === 'AbortError') {
            console.log('Request cancelado (período mudou)');
            return;
        }
        
        // Erro real (timeout, network, 500)
        console.error('Erro ao atualizar KPI:', error);
        this.renderError('Erro ao carregar dados', () => {
            // Retry: dispara nova atualização
            globalState.setPeriodPreset(globalState.getPeriod().preset);
        });
    }
});
```

---

## 5. INTEGRAÇÃO COM BACKEND

### 5.1 Contrato de API

O `GlobalState` envia parâmetros que o backend já reconhece via `resolvePeriod()`:

**Modo Preset:**
```
GET /api/kpi-backlog-atual?period=last_30_days
```

**Modo Custom:**
```
GET /api/kpi-backlog-atual?inicio=01/01/2026&fim=15/01/2026
```

---

### 5.2 Processamento no Backend

```php
// endpoint-helpers.php já possui resolvePeriod()
function resolvePeriod(?string $period = null, ?string $inicio = null, ?string $fim = null): array {
    // MODO 1: Período pré-definido
    if ($period) {
        // Retorna inicio, fim, tipo, descricao, dias
    }
    
    // MODO 2: Datas customizadas
    if ($inicio && $fim) {
        // Valida e retorna datas formatadas
    }
    
    // MODO 3: Fallback - últimos 7 dias
    return [...];
}
```

**Uso no Endpoint:**

```php
// kpi-backlog-atual.php
$periodo = resolvePeriod($_GET['period'] ?? null, $_GET['inicio'] ?? null, $_GET['fim'] ?? null);

$sql = "SELECT COUNT(*) as total 
        FROM entrada 
        WHERE data_entrada BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $periodo['inicio'], $periodo['fim']);
// ...
```

---

### 5.3 Cache no Backend

Para períodos pré-definidos (today, last_7_days, etc), o backend pode implementar cache:

```php
// Exemplo conceitual (não implementado ainda)
$cacheKey = "kpi_backlog_{$periodo['tipo']}_{$periodo['inicio']}_{$periodo['fim']}";
$cached = apcu_fetch($cacheKey);

if ($cached !== false) {
    echo json_encode($cached);
    exit;
}

// Executa query...
$resultado = [...];

// Salva cache por 5 minutos
apcu_store($cacheKey, $resultado, 300);
```

---

## 6. COMPONENTE PeriodFilter

### 6.1 Estrutura HTML Conceitual

```html
<div class="period-filter" role="region" aria-label="Filtro de período">
    <!-- Dropdown de Presets -->
    <div class="period-filter__presets">
        <label for="period-preset" class="sr-only">Selecione o período</label>
        <select id="period-preset" class="period-filter__select">
            <option value="today">Hoje</option>
            <option value="last_7_days">Últimos 7 dias</option>
            <option value="last_30_days" selected>Últimos 30 dias</option>
            <option value="last_90_days">Últimos 90 dias</option>
            <option value="custom">Intervalo customizado...</option>
        </select>
    </div>
    
    <!-- Range de Datas (readonly) -->
    <div class="period-filter__range">
        <input 
            type="text" 
            id="period-range" 
            class="period-filter__range-display"
            readonly
            value="01/12/2025 - 15/01/2026"
            aria-label="Período selecionado"
        />
    </div>
    
    <!-- Modal de Datepicker (hidden por padrão) -->
    <div id="period-custom-modal" class="period-filter__modal" hidden>
        <div class="period-filter__modal-content">
            <h3>Selecionar Intervalo</h3>
            
            <div class="period-filter__date-inputs">
                <label>
                    Data Início:
                    <input type="date" id="period-custom-start" />
                </label>
                <label>
                    Data Fim:
                    <input type="date" id="period-custom-end" />
                </label>
            </div>
            
            <div class="period-filter__modal-actions">
                <button type="button" class="btn-secondary" id="period-custom-cancel">
                    Cancelar
                </button>
                <button type="button" class="btn-primary" id="period-custom-apply">
                    Aplicar
                </button>
            </div>
        </div>
    </div>
</div>
```

---

### 6.2 Classe JavaScript

```javascript
/**
 * 🔍 PERIOD FILTER - Componente de Filtro Global de Período
 * 
 * Renderiza UI de seleção de período e sincroniza com GlobalState.
 */
class PeriodFilter {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container ${containerId} não encontrado`);
        }
        
        this.presetSelect = null;
        this.rangeDisplay = null;
        this.customModal = null;
        this.customStartInput = null;
        this.customEndInput = null;
        
        this._render();
        this._attachEvents();
        this._syncFromGlobalState();
        
        // Observa mudanças externas no GlobalState (ex: navegação por URL)
        globalState.subscribe((event) => {
            if (event.type === 'period') {
                this._syncFromGlobalState();
            }
        });
    }
    
    /**
     * Renderiza estrutura HTML
     */
    _render() {
        this.container.innerHTML = `
            <div class="period-filter" role="region" aria-label="Filtro de período">
                <div class="period-filter__presets">
                    <label for="period-preset" class="sr-only">Selecione o período</label>
                    <select id="period-preset" class="period-filter__select">
                        <option value="today">Hoje</option>
                        <option value="last_7_days">Últimos 7 dias</option>
                        <option value="last_30_days">Últimos 30 dias</option>
                        <option value="last_90_days">Últimos 90 dias</option>
                        <option value="custom">Intervalo customizado...</option>
                    </select>
                </div>
                
                <div class="period-filter__range">
                    <input 
                        type="text" 
                        id="period-range" 
                        class="period-filter__range-display"
                        readonly
                        aria-label="Período selecionado"
                    />
                </div>
                
                <div id="period-custom-modal" class="period-filter__modal" hidden>
                    <div class="period-filter__modal-backdrop"></div>
                    <div class="period-filter__modal-content">
                        <h3>Selecionar Intervalo</h3>
                        
                        <div class="period-filter__date-inputs">
                            <label>
                                Data Início:
                                <input type="date" id="period-custom-start" />
                            </label>
                            <label>
                                Data Fim:
                                <input type="date" id="period-custom-end" />
                            </label>
                        </div>
                        
                        <div class="period-filter__modal-actions">
                            <button type="button" class="btn-secondary" id="period-custom-cancel">
                                Cancelar
                            </button>
                            <button type="button" class="btn-primary" id="period-custom-apply">
                                Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Cache de elementos
        this.presetSelect = document.getElementById('period-preset');
        this.rangeDisplay = document.getElementById('period-range');
        this.customModal = document.getElementById('period-custom-modal');
        this.customStartInput = document.getElementById('period-custom-start');
        this.customEndInput = document.getElementById('period-custom-end');
    }
    
    /**
     * Anexa event listeners
     */
    _attachEvents() {
        // Mudança no select de presets
        this.presetSelect.addEventListener('change', (e) => {
            const value = e.target.value;
            
            if (value === 'custom') {
                this._openCustomModal();
            } else {
                globalState.setPeriodPreset(value);
            }
        });
        
        // Botão "Cancelar" no modal
        document.getElementById('period-custom-cancel').addEventListener('click', () => {
            this._closeCustomModal();
            // Reverte select para preset anterior
            const currentPeriod = globalState.getPeriod();
            if (currentPeriod.type === 'preset') {
                this.presetSelect.value = currentPeriod.preset;
            }
        });
        
        // Botão "Aplicar" no modal
        document.getElementById('period-custom-apply').addEventListener('click', () => {
            this._applyCustomPeriod();
        });
        
        // Fechar modal ao clicar no backdrop
        this.customModal.querySelector('.period-filter__modal-backdrop').addEventListener('click', () => {
            document.getElementById('period-custom-cancel').click();
        });
        
        // Fechar modal com Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.customModal.hidden) {
                document.getElementById('period-custom-cancel').click();
            }
        });
    }
    
    /**
     * Sincroniza UI com GlobalState
     */
    _syncFromGlobalState() {
        const period = globalState.getPeriod();
        
        // Atualiza select
        if (period.type === 'preset') {
            this.presetSelect.value = period.preset;
        } else {
            this.presetSelect.value = 'custom';
        }
        
        // Atualiza display de range
        this.rangeDisplay.value = period.descricao;
    }
    
    /**
     * Abre modal de período customizado
     */
    _openCustomModal() {
        // Define datas padrão (últimos 30 dias)
        const hoje = new Date();
        const trintaDiasAtras = new Date(hoje);
        trintaDiasAtras.setDate(hoje.getDate() - 30);
        
        this.customStartInput.value = this._formatDateForInput(trintaDiasAtras);
        this.customEndInput.value = this._formatDateForInput(hoje);
        
        // Mostra modal
        this.customModal.hidden = false;
        this.customStartInput.focus();
    }
    
    /**
     * Fecha modal de período customizado
     */
    _closeCustomModal() {
        this.customModal.hidden = true;
    }
    
    /**
     * Aplica período customizado selecionado
     */
    _applyCustomPeriod() {
        const inicio = this.customStartInput.value; // YYYY-MM-DD
        const fim = this.customEndInput.value;       // YYYY-MM-DD
        
        if (!inicio || !fim) {
            alert('Selecione ambas as datas');
            return;
        }
        
        try {
            globalState.setPeriodCustom(inicio, fim);
            this._closeCustomModal();
        } catch (error) {
            alert(error.message);
        }
    }
    
    /**
     * Formata Date para input type="date" (YYYY-MM-DD)
     */
    _formatDateForInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
}
```

---

### 6.3 Uso do Componente

```javascript
// No DashboardExecutivo.php (script)
document.addEventListener('DOMContentLoaded', () => {
    // Inicializa filtro global
    const periodFilter = new PeriodFilter('period-filter-container');
    
    // Componentes KPI automaticamente reagem a mudanças via globalState.subscribe()
});
```

---

## 7. SINCRONIZAÇÃO URL (Deep Linking)

### 7.1 Objetivo

Permitir que usuários compartilhem URLs com período aplicado:

```
https://vista.com/dashboard?period=last_30_days
https://vista.com/dashboard?inicio=01/01/2026&fim=15/01/2026
```

---

### 7.2 Implementação

O `GlobalState` já implementa sincronização automática de URL:

```javascript
_updateUrl() {
    const params = this.getApiParams();
    const urlParams = new URLSearchParams(window.location.search);
    
    // Remove parâmetros antigos
    urlParams.delete('period');
    urlParams.delete('inicio');
    urlParams.delete('fim');
    
    // Adiciona novos parâmetros
    Object.entries(params).forEach(([key, value]) => {
        urlParams.set(key, value);
    });
    
    // Atualiza URL sem reload
    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.replaceState({}, '', newUrl);
}
```

---

### 7.3 Inicialização a partir da URL

Quando o usuário acessa URL com período:

```javascript
_initializeFromUrlOrStorage() {
    // 1. Tenta carregar da URL (PRIORIDADE)
    const urlParams = new URLSearchParams(window.location.search);
    const periodParam = urlParams.get('period');
    
    if (periodParam) {
        this.setPeriodPreset(periodParam);
        return; // URL tem prioridade sobre localStorage
    }
    
    // 2. Tenta carregar do localStorage
    // 3. Fallback: last_30_days
}
```

---

## 8. PERSISTÊNCIA LOCAL

### 8.1 LocalStorage

O período selecionado é salvo automaticamente no `localStorage`:

```javascript
_saveToLocalStorage() {
    try {
        localStorage.setItem('vista_global_period', JSON.stringify(this._state.period));
    } catch (error) {
        console.warn('Erro ao salvar período no localStorage:', error);
    }
}
```

**Estrutura Salva:**

```json
{
    "type": "preset",
    "preset": "last_30_days",
    "inicio": null,
    "fim": null,
    "descricao": "Últimos 30 dias"
}
```

---

### 8.2 Prioridade de Fontes

1. **URL** (máxima prioridade): `?period=last_7_days`
2. **localStorage**: Última escolha do usuário
3. **Padrão**: `last_30_days`

---

## 9. CASOS DE USO

### 9.1 Caso 1: Usuário Muda Período (Preset)

**Fluxo:**

1. Usuário seleciona "Últimos 7 dias" no dropdown
2. `PeriodFilter` chama `globalState.setPeriodPreset('last_7_days')`
3. `GlobalState` cancela requests anteriores
4. `GlobalState` salva no localStorage
5. `GlobalState` atualiza URL para `?period=last_7_days`
6. `GlobalState` notifica todos os observadores
7. 5 `KpiCard` mostram skeleton screen
8. 2 `ChartComponent` mostram loading
9. 8 requests paralelos são feitos com `period=last_7_days`
10. Backend retorna dados filtrados
11. Componentes renderizam novos dados
12. Loading states removidos

**Tempo Total:** ~300ms

---

### 9.2 Caso 2: Usuário Define Período Customizado

**Fluxo:**

1. Usuário seleciona "Intervalo customizado..." no dropdown
2. Modal abre com datepicker
3. Usuário define 01/01/2026 (início) e 15/01/2026 (fim)
4. Usuário clica "Aplicar"
5. `PeriodFilter` chama `globalState.setPeriodCustom('2026-01-01', '2026-01-15')`
6. `GlobalState` valida datas (início ≤ fim)
7. `GlobalState` cancela requests anteriores
8. `GlobalState` salva no localStorage
9. `GlobalState` atualiza URL para `?inicio=01/01/2026&fim=15/01/2026`
10. `GlobalState` notifica observadores
11. Componentes atualizam com novo período
12. Modal fecha

**Tempo Total:** ~350ms (modal + atualização)

---

### 9.3 Caso 3: Usuário Compartilha URL

**Fluxo:**

1. Usuário A tem período "Últimos 7 dias" ativo
2. URL atual: `https://vista.com/dashboard?period=last_7_days`
3. Usuário A copia URL e envia para Usuário B
4. Usuário B acessa URL
5. `GlobalState._initializeFromUrlOrStorage()` detecta `?period=last_7_days`
6. `GlobalState` define período a partir da URL (prioridade)
7. Dashboard carrega com "Últimos 7 dias" já aplicado
8. KPIs renderizam com dados corretos

---

### 9.4 Caso 4: Usuário Recarrega Página (F5)

**Fluxo:**

1. Usuário tem período "Últimos 90 dias" ativo
2. `localStorage` contém `{ preset: 'last_90_days', ... }`
3. URL contém `?period=last_90_dias`
4. Usuário aperta F5 (recarregar)
5. Página recarrega
6. `GlobalState._initializeFromUrlOrStorage()` lê URL primeiro
7. Período restaurado sem interação do usuário
8. Dashboard mantém estado

---

### 9.5 Caso 5: Usuário Muda Período Rapidamente (Debounce)

**Fluxo:**

1. Usuário seleciona "Últimos 7 dias"
2. `GlobalState` dispara requests (com AbortSignal)
3. **Antes das respostas chegarem**, usuário seleciona "Últimos 30 dias"
4. `GlobalState.cancelPendingRequests()` aborta requests anteriores
5. Novos requests são disparados
6. Requests abortados retornam `AbortError`
7. Componentes ignoram `AbortError` (silenciosamente)
8. Apenas os dados de "Últimos 30 dias" são renderizados

**Resultado:** Sem race conditions, sem renderizações duplicadas

---

## 10. CHECKLIST DE IMPLEMENTAÇÃO

### 10.1 Fase 1: GlobalState (Base)

- [ ] Criar `globalState.js` com classe `GlobalState`
- [ ] Implementar métodos `setPeriodPreset()` e `setPeriodCustom()`
- [ ] Implementar padrão Observer (`subscribe()`, `_notifyObservers()`)
- [ ] Implementar `getApiParams()` (conversão para formato backend)
- [ ] Implementar persistência no `localStorage`
- [ ] Implementar sincronização de URL (`_updateUrl()`)
- [ ] Implementar inicialização a partir de URL/localStorage
- [ ] Implementar `AbortController` para cancelamento de requests
- [ ] Testar isoladamente (console.log de eventos)

---

### 10.2 Fase 2: Componente PeriodFilter

- [ ] Criar `PeriodFilter.js` com classe `PeriodFilter`
- [ ] Implementar `_render()` (HTML do dropdown + range display + modal)
- [ ] Implementar `_attachEvents()` (listeners para select, botões, Esc)
- [ ] Implementar `_syncFromGlobalState()` (UI reflete estado global)
- [ ] Implementar modal de datepicker customizado
- [ ] Validar datas (início ≤ fim)
- [ ] Integrar com `globalState` (chamadas a `setPeriod*`)
- [ ] Testar interações (presets, custom, cancelar, Esc)

---

### 10.3 Fase 3: Integração com KpiService

- [ ] Adicionar suporte a `AbortSignal` no `KpiService.fetchKpi()`
  ```javascript
  async fetchKpi(kpiName, params, signal) {
      const response = await fetch(url, { signal });
      // ...
  }
  ```
- [ ] Modificar `KpiCard` para usar `globalState.getApiParams()` e `globalState.getAbortSignal()`
- [ ] Modificar `ChartComponent` para usar `globalState.getApiParams()` e `globalState.getAbortSignal()`
- [ ] Implementar tratamento de `AbortError` (silencioso)
- [ ] Testar cancelamento de requests (mudança rápida de período)

---

### 10.4 Fase 4: Integração com Componentes

- [ ] Modificar `DashboardExecutivo.php` para incluir container do filtro
- [ ] Inicializar `PeriodFilter` no `DOMContentLoaded`
- [ ] Fazer todos os `KpiCard` observarem `globalState`
  ```javascript
  globalState.subscribe((event) => {
      if (event.type === 'period') {
          this.loadData(); // Recarrega dados
      }
  });
  ```
- [ ] Fazer todos os `ChartComponent` observarem `globalState`
- [ ] Testar atualização cascata (mudança de período → todos os KPIs atualizam)

---

### 10.5 Fase 5: Estilos CSS

- [ ] Criar `period-filter.css`
- [ ] Estilizar dropdown de presets
- [ ] Estilizar range display (readonly input)
- [ ] Estilizar modal de datepicker (backdrop, content, botões)
- [ ] Adicionar estados de hover/focus
- [ ] Adicionar animações de abertura/fechamento do modal
- [ ] Garantir responsividade (mobile, tablet, desktop)
- [ ] Testar contraste (WCAG AA)

---

### 10.6 Fase 6: Testes

**Testes Funcionais:**

- [ ] Teste 1: Alterar preset → KPIs atualizam
- [ ] Teste 2: Definir custom → KPIs atualizam
- [ ] Teste 3: URL com `?period=last_7_days` → Carrega correto
- [ ] Teste 4: URL com `?inicio=X&fim=Y` → Carrega correto
- [ ] Teste 5: F5 (reload) → Período persiste
- [ ] Teste 6: Mudança rápida de período → Sem race conditions
- [ ] Teste 7: Cancelar modal → Volta ao preset anterior
- [ ] Teste 8: Validação de datas (fim < início) → Mostra erro

**Testes de Acessibilidade:**

- [ ] Navegação por teclado (Tab, Enter, Esc)
- [ ] Screen reader anuncia mudanças de período
- [ ] ARIA labels corretos
- [ ] Contraste de cores (WCAG AA)

**Testes de Performance:**

- [ ] Mudança de período < 300ms (total)
- [ ] Requests cancelados corretamente (AbortController)
- [ ] Sem memory leaks (observadores desconectados)

---

## 11. ARQUITETURA DE RESPONSABILIDADES

### 11.1 Separação de Camadas

| Camada | Responsabilidade | NÃO faz |
|--------|------------------|---------|
| **GlobalState** | Armazenar período único, notificar mudanças, persistir, sincronizar URL | Renderizar UI, fazer requests HTTP |
| **PeriodFilter** | Renderizar UI do filtro, capturar input do usuário, validar datas | Armazenar estado, fazer requests HTTP |
| **KpiService** | Fazer requests HTTP, incluir parâmetros de período, tratar AbortSignal | Armazenar período, renderizar UI |
| **KpiCard** | Renderizar KPI, observar mudanças de período, mostrar loading | Armazenar período, fazer requests diretamente |
| **DashboardView** | Orquestrar componentes, coordenar loading global | Armazenar período, fazer requests |

---

### 11.2 Diagrama de Dependências

```
┌──────────────────────────────────────────────────────┐
│                    GlobalState                       │
│          (Fonte Única da Verdade)                    │
│  - Armazena período                                  │
│  - Notifica mudanças (Observer)                      │
│  - Persiste (localStorage)                           │
│  - Sincroniza URL                                    │
└────────────────┬──────────────────┬──────────────────┘
                 │                  │
                 │ observa          │ observa
                 │                  │
        ┌────────▼────────┐  ┌──────▼──────────┐
        │  PeriodFilter   │  │  KpiCard (5x)   │
        │  - Renderiza UI │  │  - Mostra KPI   │
        │  - Valida input │  │  - Loading      │
        └─────────────────┘  └────────┬────────┘
                                      │
                                      │ usa
                                      │
                             ┌────────▼────────┐
                             │   KpiService    │
                             │  - Fetch API    │
                             │  - AbortSignal  │
                             └─────────────────┘
```

---

## 12. COMPARAÇÃO: ANTES vs. DEPOIS

### 12.1 Antes (Sem Filtro Global)

**Problemas:**

❌ **Duplicação de Estado:** Cada KpiCard mantinha período local  
❌ **Inconsistência:** KPIs podiam ter períodos diferentes simultaneamente  
❌ **UX Confusa:** Usuário não sabia qual período estava aplicado  
❌ **Sem Persistência:** Período resetava a cada reload  
❌ **Sem Deep Linking:** Impossível compartilhar URL com período  

**Código (Problemático):**

```javascript
// ANTES: Cada KpiCard tinha período próprio
class KpiCard {
    constructor(containerId, kpiName) {
        this.kpiName = kpiName;
        this.period = 'last_30_days'; // Duplicação!
    }
    
    loadData() {
        fetch(`/api/${this.kpiName}?period=${this.period}`)
            .then(res => res.json())
            .then(data => this.render(data));
    }
}

// KPI 1 com last_7_days
const card1 = new KpiCard('card1', 'backlog');
card1.period = 'last_7_days';

// KPI 2 com last_30_days (INCONSISTENTE!)
const card2 = new KpiCard('card2', 'ciclo');
card2.period = 'last_30_days';
```

---

### 12.2 Depois (Com Filtro Global)

**Benefícios:**

✅ **Fonte Única da Verdade:** `globalState.period` é a única referência  
✅ **Consistência Garantida:** Todos os KPIs usam o mesmo período  
✅ **UX Clara:** Filtro global visível no topo da tela  
✅ **Persistência:** Período sobrevive a reload (localStorage)  
✅ **Deep Linking:** URL compartilhável (`?period=last_7_days`)  
✅ **Performance:** Cancelamento de requests anteriores (AbortController)  

**Código (Correto):**

```javascript
// DEPOIS: GlobalState como fonte única
class KpiCard {
    constructor(containerId, kpiName) {
        this.kpiName = kpiName;
        
        // Observa mudanças globais (NÃO armazena período)
        globalState.subscribe((event) => {
            if (event.type === 'period') {
                this.loadData(); // Recarrega automaticamente
            }
        });
    }
    
    loadData() {
        // Sempre usa período global
        const params = globalState.getApiParams();
        const signal = globalState.getAbortSignal();
        
        fetch(`/api/${this.kpiName}?${new URLSearchParams(params)}`, { signal })
            .then(res => res.json())
            .then(data => this.render(data))
            .catch(err => {
                if (err.name === 'AbortError') return; // Ignorar cancelamentos
                this.renderError(err.message);
            });
    }
}

// Todos os KPIs automaticamente usam o mesmo período
const card1 = new KpiCard('card1', 'backlog');
const card2 = new KpiCard('card2', 'ciclo');

// Mudança global afeta todos
globalState.setPeriodPreset('last_7_days'); // Ambos atualizam!
```

---

## 13. RESUMO EXECUTIVO

### 13.1 O Que Foi Definido

1. **GlobalState (Singleton):**
   - Classe JavaScript que armazena período único
   - Implementa padrão Observer para notificar mudanças
   - Persiste no localStorage
   - Sincroniza com URL (History API)
   - Gerencia AbortController para cancelamento de requests

2. **PeriodFilter (Componente UI):**
   - Dropdown com presets (hoje, 7d, 30d, 90d, custom)
   - Range display (readonly) mostrando período efetivo
   - Modal de datepicker para período customizado
   - Integração com GlobalState

3. **Fluxo de Atualização:**
   - Usuário muda período → GlobalState notifica → Todos os KPIs recarregam
   - Requests anteriores cancelados automaticamente
   - Loading states coordenados
   - Tempo total: ~300ms

4. **Integração com Backend:**
   - Usa `resolvePeriod()` existente (endpoint-helpers.php)
   - Parâmetros: `period=last_30_days` ou `inicio=dd/mm/yyyy&fim=dd/mm/yyyy`
   - 100% compatível com contrato atual

---

### 13.2 Critérios de Aceite Atendidos

| Critério | Status | Evidência |
|----------|--------|-----------|
| **✔️ Fonte única da verdade** | ✅ Atendido | GlobalState é a única referência de período |
| **✔️ Sem duplicação de lógica** | ✅ Atendido | Nenhum componente armazena período localmente |
| **✔️ UX previsível** | ✅ Atendido | Mudança de período atualiza todos os KPIs sincronizadamente |

---

### 13.3 Próximos Passos

**Implementação (Sequencial):**

1. **Criar GlobalState.js** (~200 linhas) - 1h
2. **Criar PeriodFilter.js** (~150 linhas) - 1h
3. **Criar period-filter.css** (~100 linhas) - 30min
4. **Modificar KpiService** (adicionar AbortSignal) - 30min
5. **Modificar KpiCard** (observar GlobalState) - 30min
6. **Integrar em DashboardExecutivo.php** - 30min
7. **Testar fluxo completo** - 1h

**Tempo Total Estimado:** 5-6 horas

---

## 14. REFERÊNCIAS

**Documentos Relacionados:**
- [ARQUITETURA_FRONTEND_KPI.md](ARQUITETURA_FRONTEND_KPI.md) - Arquitetura geral do frontend
- [MODELO_CARD_KPI.md](MODELO_CARD_KPI.md) - Especificação do componente KpiCard
- [endpoint-helpers.php](BackEnd/endpoint-helpers.php) - Função `resolvePeriod()`

**Padrões Utilizados:**
- **Observer Pattern:** GlobalState notifica observadores sobre mudanças
- **Singleton Pattern:** GlobalState é instância única global
- **Debounce Pattern:** Previne múltiplos requests em sequência rápida

**APIs Web Utilizadas:**
- **History API:** `window.history.replaceState()` para atualizar URL
- **localStorage API:** Persistência de período entre sessões
- **AbortController API:** Cancelamento de requests HTTP

---

**FIM DO DOCUMENTO**
