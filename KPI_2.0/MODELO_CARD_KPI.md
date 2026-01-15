# 🎴 MODELO DE CARD KPI - PADRÃO VISUAL VISTA
## Componente Reutilizável para Dashboard Executivo e Operacional

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Sistema:** VISTA - KPI 2.0  
**Autor:** Equipe Frontend VISTA

---

## 📑 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Anatomia do Card](#anatomia-do-card)
3. [Estrutura do Componente](#estrutura-do-componente)
4. [Props Esperadas](#props-esperadas)
5. [Estados Visuais](#estados-visuais)
6. [Exemplos de Uso](#exemplos-de-uso)
7. [Variações do Card](#variações-do-card)
8. [Acessibilidade](#acessibilidade)
9. [Integração com Backend](#integração-com-backend)
10. [Checklist de Implementação](#checklist-de-implementação)

---

## 1. VISÃO GERAL

### 1.1 Propósito

O **Card KPI** é o componente visual fundamental do sistema VISTA, responsável por exibir indicadores-chave de performance de forma clara, rápida e acionável.

**Objetivos:**
- ✅ Leitura em **< 3 segundos** (valor + tendência + estado)
- ✅ Comunicação visual imediata através de cores e ícones
- ✅ Drill-down para área detalhada (clique)
- ✅ Reutilizável em todas as camadas (Executivo, Operacional, Analítico)
- ✅ Responsivo (desktop, tablet, mobile)

---

### 1.2 Princípios de Design

**Hierarquia Visual:**
1. **Valor Principal** (maior destaque)
2. **Variação/Tendência** (segundo destaque)
3. **Nome do KPI** (contexto)
4. **Última Atualização** (metadado)

**Cores Semânticas:**
- 🟢 **Verde (Success):** KPI dentro do esperado
- 🟡 **Amarelo (Warning):** KPI requer atenção
- 🔴 **Vermelho (Critical):** KPI crítico, ação imediata

**Interatividade:**
- Hover: Destaque visual (borda, sombra)
- Click: Navegação para área detalhada
- Loading: Skeleton screen durante carregamento

---

## 2. ANATOMIA DO CARD

### 2.1 Estrutura Visual

```
┌─────────────────────────────────────┐
│  📦 Volume Processado         [ℹ️]  │ ← Header (nome + ícone + info)
├─────────────────────────────────────┤
│                                     │
│           1.250                     │ ← Valor Principal (grande)
│                                     │
│     +5.9% ↑   vs. média histórica   │ ← Badge de Variação + Contexto
│                                     │
├─────────────────────────────────────┤
│  Última atualização: 15/01 10:30    │ ← Footer (timestamp)
└─────────────────────────────────────┘
        ↑
  Estado: success (borda verde)
```

---

### 2.2 Elementos Obrigatórios

| Elemento | Obrigatório | Descrição |
|----------|-------------|-----------|
| **Nome do KPI** | ✅ Sim | Título descritivo (ex: "Volume Processado") |
| **Valor Principal** | ✅ Sim | Número, percentual, valor monetário ou tempo |
| **Unidade** | ⚠️ Recomendado | Ex: "equipamentos", "%", "R$", "dias" |
| **Variação** | ⚠️ Recomendado | Percentual de mudança vs. referência |
| **Tendência** | ⚠️ Recomendado | `alta` / `baixa` / `neutra` |
| **Estado Visual** | ✅ Sim | `success` / `warning` / `critical` |
| **Última Atualização** | ❌ Opcional | Timestamp da última atualização |
| **Ícone** | ❌ Opcional | FontAwesome ou similar |

---

### 2.3 Elementos Opcionais

| Elemento | Quando Usar |
|----------|-------------|
| **Badge "Novo"** | KPI adicionado recentemente (< 7 dias) |
| **Botão de Info** | KPI complexo que requer explicação |
| **Sparkline** | Tendência visual dos últimos 7 dias |
| **Comparação Múltipla** | "vs. semana passada", "vs. mês passado" |
| **Alerta Pulsante** | Estado crítico que exige ação imediata |

---

## 3. ESTRUTURA DO COMPONENTE

### 3.1 Classe JavaScript (ES6)

```javascript
/**
 * COMPONENTE: Card de KPI
 * 
 * Renderiza um indicador-chave com valor, variação, estado e interatividade.
 * 
 * @class KpiCard
 * @version 1.0.0
 * @author Equipe Frontend VISTA
 */
class KpiCard {
    /**
     * @param {string} containerId - ID do elemento HTML onde o card será renderizado
     * @param {Object} config - Configurações do card
     * @param {Function} config.onClick - Callback ao clicar no card
     * @param {boolean} config.clickable - Se o card é clicável (default: true)
     * @param {boolean} config.showTimestamp - Exibir última atualização (default: true)
     * @param {boolean} config.showIcon - Exibir ícone (default: true)
     */
    constructor(containerId, config = {}) {
        this.container = document.getElementById(containerId);
        
        if (!this.container) {
            throw new Error(`Elemento com ID "${containerId}" não encontrado`);
        }

        this.config = {
            clickable: true,
            showTimestamp: true,
            showIcon: true,
            onClick: null,
            ...config
        };

        this.data = null;
        this.loading = false;
    }

    /**
     * Renderizar card com dados do backend
     * 
     * @param {Object} kpiData - Dados do KPI (contrato JSON padronizado)
     * @param {Object} kpiData.meta - Metadados (timestamp, período, etc)
     * @param {Object} kpiData.data - Dados do KPI (valor, variação, estado, etc)
     * @param {string} kpiData.data.valor - Valor principal do KPI
     * @param {string} kpiData.data.unidade - Unidade do valor (ex: "equipamentos")
     * @param {Object} kpiData.data.variacao - Variação percentual
     * @param {number} kpiData.data.variacao.percentual - Percentual de variação
     * @param {string} kpiData.data.variacao.direcao - Direção (alta/baixa/neutra)
     * @param {string} kpiData.data.estado - Estado visual (success/warning/critical)
     * @param {string} kpiData.data.contexto - Contexto da variação
     */
    render(kpiData) {
        this.data = kpiData;
        this.loading = false;

        // Validar estrutura de dados
        if (!this._validateData(kpiData)) {
            this._renderError('Dados inválidos do KPI');
            return;
        }

        // Extrair dados
        const { meta, data } = kpiData;
        const estado = data.estado || 'success';
        const valor = this._formatarValor(data.valor, data.unidade);
        const variacao = data.variacao?.percentual ?? null;
        const direcao = data.variacao?.direcao || 'neutra';
        const contexto = data.contexto || '';
        const timestamp = meta?.timestamp || '';

        // Gerar HTML
        const html = `
            <div class="kpi-card kpi-card--${estado} ${this.config.clickable ? 'kpi-card--clickable' : ''}" 
                 data-kpi="${meta?.kpi || 'unknown'}"
                 role="button"
                 tabindex="${this.config.clickable ? '0' : '-1'}"
                 aria-label="${this._getAriaLabel(data)}">
                
                <!-- HEADER -->
                <div class="kpi-card__header">
                    ${this.config.showIcon ? `<i class="kpi-card__icon ${this._getIcon(data)}"></i>` : ''}
                    <span class="kpi-card__label">${this._getLabel(meta?.kpi || 'KPI')}</span>
                    ${this._renderBadges(data)}
                </div>

                <!-- BODY -->
                <div class="kpi-card__body">
                    <div class="kpi-card__value">${valor}</div>
                    ${variacao !== null ? this._renderVariacao(variacao, direcao, contexto) : ''}
                </div>

                <!-- FOOTER -->
                ${this.config.showTimestamp ? `
                    <div class="kpi-card__footer">
                        <span class="kpi-card__timestamp">
                            <i class="fa fa-clock"></i>
                            ${this._formatarTimestamp(timestamp)}
                        </span>
                    </div>
                ` : ''}

                <!-- ESTADO VISUAL (borda colorida) -->
                <div class="kpi-card__border kpi-card__border--${estado}"></div>
            </div>
        `;

        this.container.innerHTML = html;
        this._attachEvents();
    }

    /**
     * Renderizar badge de variação
     */
    _renderVariacao(percentual, direcao, contexto) {
        const icon = direcao === 'alta' ? 'fa-arrow-up' : 
                     direcao === 'baixa' ? 'fa-arrow-down' : 
                     'fa-minus';
        
        const badge = direcao === 'alta' ? 'kpi-card__badge--up' : 
                      direcao === 'baixa' ? 'kpi-card__badge--down' : 
                      'kpi-card__badge--neutral';

        return `
            <div class="kpi-card__variacao">
                <span class="kpi-card__badge ${badge}">
                    <i class="fa ${icon}"></i>
                    ${Math.abs(percentual).toFixed(1)}%
                </span>
                ${contexto ? `<span class="kpi-card__contexto">${contexto}</span>` : ''}
            </div>
        `;
    }

    /**
     * Renderizar badges especiais (Novo, Crítico, etc)
     */
    _renderBadges(data) {
        const badges = [];

        // Badge "Novo" (KPI recente)
        if (data.novo) {
            badges.push('<span class="kpi-card__badge-new">Novo</span>');
        }

        // Badge "Crítico" (estado crítico com destaque)
        if (data.estado === 'critical') {
            badges.push('<span class="kpi-card__badge-critical">!</span>');
        }

        return badges.length > 0 ? `<div class="kpi-card__badges">${badges.join('')}</div>` : '';
    }

    /**
     * Renderizar estado de loading
     */
    renderLoading() {
        this.loading = true;
        this.container.innerHTML = `
            <div class="kpi-card kpi-card--loading">
                <div class="kpi-card__skeleton">
                    <div class="kpi-card__skeleton-header"></div>
                    <div class="kpi-card__skeleton-value"></div>
                    <div class="kpi-card__skeleton-badge"></div>
                </div>
            </div>
        `;
    }

    /**
     * Renderizar estado de erro
     */
    _renderError(message) {
        this.container.innerHTML = `
            <div class="kpi-card kpi-card--error">
                <div class="kpi-card__error">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span>${message}</span>
                    <button class="kpi-card__retry" onclick="this.closest('.kpi-card').dispatchEvent(new CustomEvent('retry'))">
                        Tentar novamente
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Atualizar apenas o valor (com animação)
     */
    updateValue(novoValor) {
        const valueElement = this.container.querySelector('.kpi-card__value');
        if (valueElement) {
            valueElement.classList.add('kpi-card__value--updating');
            setTimeout(() => {
                valueElement.textContent = this._formatarValor(novoValor, this.data?.data?.unidade);
                valueElement.classList.remove('kpi-card__value--updating');
            }, 300);
        }
    }

    /**
     * Anexar event listeners
     */
    _attachEvents() {
        const card = this.container.querySelector('.kpi-card');
        
        if (!card) return;

        // Click no card
        if (this.config.clickable && this.config.onClick) {
            card.addEventListener('click', (e) => {
                this.config.onClick(this.data, e);
            });

            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.config.onClick(this.data, e);
                }
            });
        }

        // Retry em caso de erro
        card.addEventListener('retry', () => {
            this.renderLoading();
            if (this.config.onRetry) {
                this.config.onRetry();
            }
        });
    }

    /**
     * Validar estrutura de dados
     */
    _validateData(kpiData) {
        if (!kpiData || typeof kpiData !== 'object') return false;
        if (!kpiData.data || typeof kpiData.data !== 'object') return false;
        if (kpiData.data.valor === undefined || kpiData.data.valor === null) return false;
        return true;
    }

    /**
     * Formatar valor com unidade
     */
    _formatarValor(valor, unidade) {
        // Números
        if (!isNaN(valor)) {
            const num = parseFloat(valor);
            
            // Percentuais
            if (unidade === '%' || unidade === 'percentual') {
                return `${num.toFixed(1)}%`;
            }
            
            // Moeda
            if (unidade === 'R$' || unidade === 'BRL') {
                return `R$ ${num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }
            
            // Números inteiros grandes
            if (num >= 1000) {
                return num.toLocaleString('pt-BR');
            }
            
            return num.toString();
        }

        // Strings (ex: "4d 12h 30m")
        return valor.toString();
    }

    /**
     * Formatar timestamp
     */
    _formatarTimestamp(timestamp) {
        if (!timestamp) return 'Não disponível';

        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);

        // Há menos de 1 minuto
        if (diffMins < 1) return 'Agora mesmo';
        
        // Há menos de 60 minutos
        if (diffMins < 60) return `Há ${diffMins} min`;
        
        // Há menos de 24 horas
        if (diffMins < 1440) {
            const hours = Math.floor(diffMins / 60);
            return `Há ${hours}h`;
        }

        // Formato completo
        return date.toLocaleString('pt-BR', { 
            day: '2-digit', 
            month: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }

    /**
     * Obter ícone baseado no tipo de KPI
     */
    _getIcon(data) {
        // Mapear tipos de KPI para ícones FontAwesome
        const iconMap = {
            'volume': 'fa-box',
            'tempo': 'fa-clock',
            'sucesso': 'fa-check-circle',
            'conserto': 'fa-exclamation-triangle',
            'valor': 'fa-dollar-sign',
            'backlog': 'fa-hourglass-half',
            'aprovacao': 'fa-thumbs-up',
            'reprovacao': 'fa-thumbs-down'
        };

        // Tentar identificar tipo por nome
        const kpiName = this.data?.meta?.kpi || '';
        for (const [key, icon] of Object.entries(iconMap)) {
            if (kpiName.includes(key)) {
                return `fa ${icon}`;
            }
        }

        return 'fa fa-chart-line'; // Ícone padrão
    }

    /**
     * Obter label amigável do KPI
     */
    _getLabel(kpiName) {
        const labelMap = {
            'total-processado': 'Volume Processado',
            'tempo-medio': 'Tempo Médio',
            'taxa-sucesso': 'Taxa de Sucesso',
            'sem-conserto': 'Sem Conserto',
            'valor-orcado': 'Valor Orçado',
            'backlog-atual': 'Backlog Atual',
            'taxa-aprovacao': 'Taxa de Aprovação',
            'taxa-reprovacao': 'Taxa de Reprovação'
        };

        return labelMap[kpiName] || kpiName.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Obter aria-label para acessibilidade
     */
    _getAriaLabel(data) {
        const label = this._getLabel(this.data?.meta?.kpi || 'KPI');
        const valor = this._formatarValor(data.valor, data.unidade);
        const variacao = data.variacao?.percentual;
        const direcao = data.variacao?.direcao;
        const estado = data.estado;

        let aria = `${label}: ${valor}`;

        if (variacao !== null && variacao !== undefined) {
            const tendencia = direcao === 'alta' ? 'aumentou' : 
                              direcao === 'baixa' ? 'diminuiu' : 
                              'manteve-se estável';
            aria += `, ${tendencia} ${Math.abs(variacao).toFixed(1)}%`;
        }

        if (estado === 'critical') {
            aria += ', estado crítico';
        } else if (estado === 'warning') {
            aria += ', requer atenção';
        }

        return aria;
    }
}
```

---

## 4. PROPS ESPERADAS

### 4.1 Objeto de Configuração (`config`)

```javascript
{
    // Clicável (habilita drill-down)
    clickable: true,                     // boolean (default: true)
    
    // Callback ao clicar
    onClick: (kpiData, event) => {       // function | null
        console.log('Card clicado:', kpiData);
        window.location.href = '/area-detalhada?kpi=' + kpiData.meta.kpi;
    },
    
    // Callback ao tentar novamente (erro)
    onRetry: () => {                     // function | null
        console.log('Tentando recarregar KPI...');
        this.loadData();
    },
    
    // Exibir timestamp
    showTimestamp: true,                 // boolean (default: true)
    
    // Exibir ícone
    showIcon: true,                      // boolean (default: true)
    
    // Tema (para variações visuais)
    theme: 'default'                     // 'default' | 'compact' | 'expanded'
}
```

---

### 4.2 Objeto de Dados (`kpiData`)

**Estrutura Esperada (Contrato JSON Backend):**

```javascript
{
    // METADADOS
    "meta": {
        "kpi": "backlog-recebimento",         // string (identificador único)
        "inicio": "2026-01-07",               // string (data início)
        "fim": "2026-01-14",                  // string (data fim)
        "timestamp": "2026-01-15T10:30:45Z",  // string ISO 8601
        "operador": "Todos",                  // string | null
        "kpi_version": "3.1.0",               // string (versionamento)
        "kpi_owner": "Equipe Backend VISTA"  // string
    },

    // DADOS DO KPI
    "data": {
        // OBRIGATÓRIOS
        "valor": "1250",                      // string | number
        "unidade": "equipamentos",            // string (ex: "%", "R$", "dias")
        "estado": "success",                  // "success" | "warning" | "critical"
        
        // RECOMENDADOS
        "variacao": {
            "percentual": 5.9,                // number (percentual de mudança)
            "direcao": "alta"                 // "alta" | "baixa" | "neutra"
        },
        "contexto": "vs. média histórica",    // string (explicação da variação)
        
        // OPCIONAIS
        "referencia": {
            "tipo": "media_30d",              // string
            "valor": "1180"                   // string | number
        },
        "periodo": "Últimos 7 dias",          // string
        "novo": false,                        // boolean (badge "Novo")
        "sparkline": [120, 135, 128, ...]    // array<number> (mini gráfico)
    }
}
```

---

### 4.3 Validação de Props

```javascript
/**
 * Schema de validação (JSON Schema simplificado)
 */
const KPI_DATA_SCHEMA = {
    meta: {
        required: ['kpi', 'timestamp'],
        properties: {
            kpi: { type: 'string', minLength: 1 },
            timestamp: { type: 'string', format: 'date-time' },
            inicio: { type: 'string', format: 'date' },
            fim: { type: 'string', format: 'date' }
        }
    },
    data: {
        required: ['valor', 'estado'],
        properties: {
            valor: { type: ['string', 'number'] },
            unidade: { type: 'string' },
            estado: { type: 'string', enum: ['success', 'warning', 'critical'] },
            variacao: {
                properties: {
                    percentual: { type: 'number' },
                    direcao: { type: 'string', enum: ['alta', 'baixa', 'neutra'] }
                }
            }
        }
    }
};
```

---

## 5. ESTADOS VISUAIS

### 5.1 Estado: Success (Normal)

**Quando usar:**
- KPI dentro do esperado
- Variação entre -10% e +25%
- Processo operando normalmente

**Características Visuais:**
- 🟢 Borda: Verde `#10b981`
- 🟢 Ícone de tendência: Verde
- 🟢 Background: Branco/Neutro

**Exemplo:**
```javascript
{
    "data": {
        "valor": "1250",
        "unidade": "equipamentos",
        "estado": "success",
        "variacao": { "percentual": 5.9, "direcao": "alta" }
    }
}
```

---

### 5.2 Estado: Warning (Alerta)

**Quando usar:**
- KPI requer atenção
- Variação entre +25% e +50% (ou -10% a -25%)
- Possível gargalo se persistir

**Características Visuais:**
- 🟡 Borda: Amarelo/Laranja `#f59e0b`
- 🟡 Ícone de tendência: Amarelo
- 🟡 Background: Levemente amarelado (opcional)

**Exemplo:**
```javascript
{
    "data": {
        "valor": "1850",
        "unidade": "equipamentos",
        "estado": "warning",
        "variacao": { "percentual": 32.5, "direcao": "alta" }
    }
}
```

---

### 5.3 Estado: Critical (Crítico)

**Quando usar:**
- KPI crítico, ação imediata necessária
- Variação > +50% ou < -25%
- Sistema em risco operacional

**Características Visuais:**
- 🔴 Borda: Vermelho `#ef4444`
- 🔴 Ícone de tendência: Vermelho
- 🔴 Background: Levemente avermelhado (opcional)
- 🔴 Badge "!" pulsante
- 🔴 Animação de pulso (keyframes)

**Exemplo:**
```javascript
{
    "data": {
        "valor": "2450",
        "unidade": "equipamentos",
        "estado": "critical",
        "variacao": { "percentual": 78.3, "direcao": "alta" }
    }
}
```

---

### 5.4 Estado: Loading (Carregando)

**Quando usar:**
- Dados ainda não carregados
- Requisição em andamento

**Características Visuais:**
- ⚪ Skeleton screen (placeholders animados)
- ⚪ Shimmer effect (brilho deslizante)

**Código:**
```javascript
const card = new KpiCard('kpi-volume');
card.renderLoading(); // Exibe skeleton

setTimeout(() => {
    card.render(kpiData); // Substitui por dados reais
}, 1000);
```

---

### 5.5 Estado: Error (Erro)

**Quando usar:**
- Falha ao carregar dados
- Erro de conexão
- Timeout

**Características Visuais:**
- 🔴 Ícone de erro
- 🔴 Mensagem de erro
- 🔄 Botão "Tentar novamente"

**Código:**
```javascript
try {
    const data = await fetchKPI(url);
    card.render(data);
} catch (error) {
    card._renderError('Falha ao carregar KPI');
}
```

---

## 6. EXEMPLOS DE USO

### 6.1 Uso Básico

```javascript
// HTML
<div id="kpi-volume"></div>

// JavaScript
const kpiVolumeCard = new KpiCard('kpi-volume', {
    clickable: true,
    onClick: (data, event) => {
        window.location.href = '/area-detalhada?area=recebimento';
    }
});

// Carregar dados
const dados = await fetchKPI('/DashBoard/backendDash/kpis/kpi-total-processado.php?inicio=07/01/2026&fim=14/01/2026');

kpiVolumeCard.render(dados);
```

---

### 6.2 Dashboard com Múltiplos Cards

```javascript
// Dashboard Executivo com 5 KPIs
class DashboardExecutivo {
    constructor() {
        this.cards = {
            volume: new KpiCard('kpi-volume', { 
                onClick: () => this.navigateTo('recebimento') 
            }),
            tempo: new KpiCard('kpi-tempo', { 
                onClick: () => this.navigateTo('expedicao') 
            }),
            sucesso: new KpiCard('kpi-sucesso', { 
                onClick: () => this.navigateTo('qualidade') 
            }),
            semConserto: new KpiCard('kpi-sem-conserto', { 
                onClick: () => this.navigateTo('analise') 
            }),
            valor: new KpiCard('kpi-valor', { 
                onClick: () => this.navigateTo('financeiro') 
            })
        };
    }

    async loadData() {
        // Exibir loading em todos os cards
        Object.values(this.cards).forEach(card => card.renderLoading());

        // Buscar dados em paralelo
        const [volume, tempo, sucesso, semConserto, valor] = await Promise.all([
            fetchKPI('/kpis/kpi-total-processado.php?inicio=07/01/2026&fim=14/01/2026'),
            fetchKPI('/kpis/kpi-tempo-medio.php?inicio=07/01/2026&fim=14/01/2026'),
            fetchKPI('/kpis/kpi-taxa-sucesso.php?inicio=07/01/2026&fim=14/01/2026'),
            fetchKPI('/kpis/kpi-sem-conserto.php?inicio=07/01/2026&fim=14/01/2026'),
            fetchKPI('/kpis/kpi-valor-orcado.php?inicio=07/01/2026&fim=14/01/2026')
        ]);

        // Renderizar cards
        this.cards.volume.render(volume);
        this.cards.tempo.render(tempo);
        this.cards.sucesso.render(sucesso);
        this.cards.semConserto.render(semConserto);
        this.cards.valor.render(valor);
    }

    navigateTo(area) {
        window.location.href = `/DashBoard/frontendDash/AreaDetalhada.php?area=${area}`;
    }
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    const dashboard = new DashboardExecutivo();
    dashboard.loadData();
});
```

---

### 6.3 Card Compacto (Sem Timestamp)

```javascript
const card = new KpiCard('kpi-backlog', {
    showTimestamp: false,
    showIcon: false,
    theme: 'compact'
});

card.render({
    meta: { kpi: 'backlog-atual', timestamp: '2026-01-15T10:30:45Z' },
    data: {
        valor: '340',
        unidade: 'equipamentos',
        estado: 'warning',
        variacao: { percentual: 12.3, direcao: 'alta' },
        contexto: 'vs. ontem'
    }
});
```

---

### 6.4 Card com Auto-Refresh

```javascript
class AutoRefreshKpiCard extends KpiCard {
    constructor(containerId, config, refreshInterval = 60000) {
        super(containerId, config);
        this.refreshInterval = refreshInterval;
        this.intervalId = null;
    }

    async loadAndRender(url) {
        try {
            this.renderLoading();
            const data = await fetchKPI(url);
            this.render(data);
        } catch (error) {
            this._renderError('Falha ao carregar KPI');
        }
    }

    startAutoRefresh(url) {
        this.loadAndRender(url);
        
        this.intervalId = setInterval(() => {
            this.loadAndRender(url);
        }, this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
}

// Uso
const card = new AutoRefreshKpiCard('kpi-volume', { clickable: true }, 30000); // 30 segundos
card.startAutoRefresh('/kpis/kpi-total-processado.php?inicio=07/01/2026&fim=14/01/2026');
```

---

## 7. VARIAÇÕES DO CARD

### 7.1 Card Compacto

**Uso:** Dashboards com muitos KPIs (> 8)

**Diferenças:**
- Altura reduzida (80px vs 120px)
- Sem footer (timestamp removido)
- Fonte menor para valor principal

```javascript
<div class="kpi-card kpi-card--compact">
    <!-- Estrutura simplificada -->
</div>
```

---

### 7.2 Card Expandido

**Uso:** Foco em 1-3 KPIs principais

**Diferenças:**
- Altura maior (160px)
- Sparkline (mini gráfico de tendência)
- Comparação múltipla (vs. semana, vs. mês)

```javascript
<div class="kpi-card kpi-card--expanded">
    <!-- Inclui sparkline canvas -->
    <canvas class="kpi-card__sparkline" width="200" height="40"></canvas>
</div>
```

---

### 7.3 Card com Alerta Pulsante

**Uso:** Estado crítico que exige atenção imediata

**Diferenças:**
- Animação de pulso (keyframes CSS)
- Badge "!" piscante
- Som opcional (beep discreto)

```javascript
<div class="kpi-card kpi-card--critical kpi-card--pulsing">
    <!-- Badge crítico com animação -->
    <span class="kpi-card__badge-critical kpi-card__badge--pulse">!</span>
</div>
```

---

## 8. ACESSIBILIDADE

### 8.1 ARIA Labels

**Implementação:**
```html
<div class="kpi-card" 
     role="button" 
     tabindex="0"
     aria-label="Volume Processado: 1.250 equipamentos, aumentou 5,9%, estado normal">
    <!-- Conteúdo do card -->
</div>
```

---

### 8.2 Navegação por Teclado

**Teclas Suportadas:**
- `Tab`: Navegar entre cards
- `Enter` ou `Space`: Ativar card (drill-down)
- `Esc`: Fechar modal (se aplicável)

**Implementação:**
```javascript
card.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.config.onClick(this.data, e);
    }
});
```

---

### 8.3 Contraste de Cores

**WCAG AA Compliance:**
- Texto sobre fundo branco: mínimo 4.5:1
- Ícones sobre fundo branco: mínimo 3:1
- Bordas coloridas: suficientemente grossas (3px)

---

## 9. INTEGRAÇÃO COM BACKEND

### 9.1 Fluxo de Dados

```
1. Frontend solicita dados
   └─ fetchKPI('/kpis/kpi-total-processado.php?...')

2. Backend retorna JSON padronizado
   └─ { meta: {...}, data: {...} }

3. Card valida estrutura
   └─ _validateData(kpiData)

4. Card renderiza HTML
   └─ render(kpiData)

5. Card anexa event listeners
   └─ _attachEvents()
```

---

### 9.2 Exemplo de Resposta Backend

```json
{
    "meta": {
        "kpi": "backlog-recebimento",
        "inicio": "2026-01-07",
        "fim": "2026-01-14",
        "timestamp": "2026-01-15T10:30:45Z",
        "operador": "Todos",
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

---

### 9.3 Tratamento de Erro

```javascript
try {
    const kpiData = await fetchKPI(url);
    card.render(kpiData);
} catch (error) {
    if (error.message.includes('401')) {
        // Não autorizado - redirecionar para login
        window.location.href = '/FrontEnd/tela_login.php';
    } else if (error.message.includes('500')) {
        // Erro de servidor
        card._renderError('Erro no servidor. Tente novamente.');
    } else {
        // Erro genérico
        card._renderError('Falha ao carregar KPI.');
    }
}
```

---

## 10. CHECKLIST DE IMPLEMENTAÇÃO

### 10.1 Fase 1: Estrutura Base

- [ ] Criar classe `KpiCard` com constructor
- [ ] Implementar método `render(kpiData)`
- [ ] Implementar método `renderLoading()`
- [ ] Implementar método `_renderError(message)`
- [ ] Validação de dados (`_validateData`)
- [ ] Formatação de valores (`_formatarValor`)
- [ ] Formatação de timestamp (`_formatarTimestamp`)

---

### 10.2 Fase 2: Estados Visuais

- [ ] Implementar estado `success` (verde)
- [ ] Implementar estado `warning` (amarelo)
- [ ] Implementar estado `critical` (vermelho)
- [ ] Implementar skeleton screen (loading)
- [ ] Implementar estado de erro com retry

---

### 10.3 Fase 3: Interatividade

- [ ] Event listener de clique (drill-down)
- [ ] Event listener de teclado (Enter/Space)
- [ ] Hover state (destaque visual)
- [ ] Animação de atualização de valor
- [ ] Callback `onClick` configurável

---

### 10.4 Fase 4: Acessibilidade

- [ ] ARIA labels dinâmicos
- [ ] Navegação por teclado (Tab, Enter, Space)
- [ ] Contraste WCAG AA
- [ ] Screen reader friendly

---

### 10.5 Fase 5: Variações

- [ ] Card compacto (`kpi-card--compact`)
- [ ] Card expandido com sparkline
- [ ] Card com alerta pulsante (crítico)
- [ ] Tema dark mode (futuro)

---

### 10.6 Fase 6: Testes

- [ ] Teste com dados válidos
- [ ] Teste com dados inválidos (erro)
- [ ] Teste com dados parciais (sem variação)
- [ ] Teste de loading → render
- [ ] Teste de clique (drill-down)
- [ ] Teste de acessibilidade (teclado)
- [ ] Teste responsivo (mobile, tablet, desktop)

---

## 📌 RESUMO EXECUTIVO

### ✅ Componente Definido

**Estrutura:**
- Classe JavaScript ES6 (`KpiCard`)
- HTML gerado dinamicamente
- Estados visuais bem definidos (success/warning/critical)
- Totalmente acessível (ARIA, keyboard)

**Props Principais:**
```javascript
{
    containerId: 'kpi-volume',
    config: {
        clickable: true,
        onClick: (data, event) => { ... },
        showTimestamp: true,
        showIcon: true
    }
}
```

**Dados Esperados:**
```javascript
{
    meta: { kpi, timestamp, inicio, fim, ... },
    data: { valor, unidade, variacao, estado, contexto, ... }
}
```

**Estados Visuais:**
- 🟢 Success: Normal, tudo dentro do esperado
- 🟡 Warning: Requer atenção, possível gargalo
- 🔴 Critical: Ação imediata necessária

**Variações:**
- Card Compacto (altura reduzida)
- Card Expandido (com sparkline)
- Card com Alerta Pulsante (crítico)

---

### 🎯 Critérios de Aceite

✅ **Padrão reutilizável**
- Componente JavaScript único para todos os KPIs
- Props configuráveis (clickable, showTimestamp, onClick)
- Compatível com contrato JSON backend

✅ **Leitura rápida**
- Hierarquia visual clara (valor → variação → nome)
- Cores semânticas (verde/amarelo/vermelho)
- Leitura em < 3 segundos

✅ **Base para alertas**
- Estados visuais bem definidos (success/warning/critical)
- Badge crítico pulsante para atenção imediata
- Integração futura com notificações push

---

### 📊 Próximos Passos

1. **Implementar código JavaScript** da classe `KpiCard`
2. **Criar CSS base** (estrutura, cores, animações)
3. **Integrar com fetch-helpers.js** (buscar dados do backend)
4. **Testar em Dashboard Executivo** com 5 KPIs reais
5. **Refinar animações** e transições

---

**Fim da Documentação**

---

*Gerado automaticamente pelo Sistema VISTA - KPI 2.0*  
*Para dúvidas técnicas, consulte a equipe de frontend*  
*Versão: 1.0 - 15/01/2026*
