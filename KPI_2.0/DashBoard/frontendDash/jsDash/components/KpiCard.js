// 🎴 KpiCard Component - Reutilizável em todo o dashboard
// Requisitos: exibe nome, valor, variação %, tendência, última atualização; observador do GlobalState; clicável para drill-down;
// Não conhece backend: recebe dados via dataProvider() ou render(data).

const DEFAULT_THRESHOLDS = {
    warningHigh: 25,   // variação >= 25% -> alerta
    warningLow: -10,   // variação <= -10% -> alerta
    criticalHigh: 50,  // variação >= 50% -> crítico
    criticalLow: -25   // variação <= -25% -> crítico
};

/**
 * Interface esperada (props)
 *
 * new KpiCard(containerId, {
 *   kpiKey: 'backlog-atual',        // identificador lógico do KPI
 *   title: 'Backlog Atual',         // nome exibido
 *   unit: 'number' | 'percent' | 'currency' | 'time',
 *   thresholds: { warningHigh, warningLow, criticalHigh, criticalLow },
 *   dataProvider: async (params) => ({
 *       name: 'Backlog Atual',
 *       value: 1250,
 *       unit: 'number',
 *       variation: 5.9,              // percentual vs período anterior
 *       trend: 'up' | 'down' | 'neutral',
 *       updatedAt: '2026-01-15T10:30:00Z',
 *       context: 'vs. média histórica'
 *   }),
 *   onClick: (data) => {},           // drill-down handler
 * });
 */
class KpiCard {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) throw new Error(`Container '${containerId}' não encontrado`);

        this.options = {
            kpiKey: options.kpiKey || 'kpi',
            title: options.title || 'KPI',
            unit: options.unit || 'number',
            thresholds: { ...DEFAULT_THRESHOLDS, ...(options.thresholds || {}) },
            dataProvider: options.dataProvider || null,
            onClick: typeof options.onClick === 'function' ? options.onClick : null
        };

        this._lastRenderHash = null;
        this._lastPeriodKey = null;
        this._unsubscribe = null;

        // Observa mudanças de período no GlobalState se existir
        if (typeof window !== 'undefined' && window.globalState && typeof window.globalState.subscribe === 'function') {
            this._unsubscribe = window.globalState.subscribe((event) => {
                if (event.type === 'period') {
                    this.refresh();
                }
            });
        }

        // Render inicial (loading placeholder)
        this.renderLoading();
        // Se dataProvider foi passado, faz primeira carga
        this.refresh();
    }

    /**
     * Dispara atualização usando dataProvider (se existir)
     */
    async refresh() {
        if (!this.options.dataProvider) return;
        try {
            const params = window.globalState?.getApiParams ? window.globalState.getApiParams() : {};
            const signal = window.globalState?.getAbortSignal ? window.globalState.getAbortSignal() : undefined;
            this.renderLoading();
            const data = await this.options.dataProvider({ params, signal });
            this.render(data);
        } catch (error) {
            if (error.name === 'AbortError') return; // mudança rápida de período
            this.renderError(error.message || 'Erro ao carregar KPI');
        }
    }

    /**
     * Renderiza estado de loading simples
     */
    renderLoading() {
        this.container.innerHTML = `
            <div class="kpi-card kpi-card--loading" role="status" aria-live="polite">
                <div class="kpi-card__header">
                    <div class="kpi-card__title">${this.options.title}</div>
                    <div class="kpi-card__info">Carregando...</div>
                </div>
                <div class="kpi-card__body">--</div>
                <div class="kpi-card__footer">Atualizando</div>
            </div>
        `;
    }

    /**
     * Renderiza erro
     */
    renderError(message) {
        this.container.innerHTML = `
            <div class="kpi-card kpi-card--error" role="alert">
                <div class="kpi-card__header">
                    <div class="kpi-card__title">${this.options.title}</div>
                    <div class="kpi-card__info">!</div>
                </div>
                <div class="kpi-card__body">${message}</div>
                <div class="kpi-card__footer">Tente novamente</div>
            </div>
        `;
    }

    /**
     * Renderiza o KPI com dados fornecidos
     * @param {Object} data - { name, value, unit, variation, trend, updatedAt, context }
     */
    render(data) {
        if (!data) return;
        const hash = this._hashData(data);
        const periodKey = this._getPeriodKey();
        if (hash === this._lastRenderHash && periodKey === this._lastPeriodKey) {
            return; // evita re-render desnecessário
        }

        const state = this._determineState(data.variation, this.options.thresholds);
        const trendIcon = this._trendIcon(data.trend);
        const valueStr = this._formatValue(data.value, data.unit || this.options.unit);
        const variationStr = typeof data.variation === 'number' ? `${data.variation.toFixed(1)}%` : '--';
        const updatedStr = this._formatTimestamp(data.updatedAt);
        const ariaLabel = `${this.options.title}, valor ${valueStr}, variação ${variationStr}, tendência ${data.trend || 'neutral'}, atualizado em ${updatedStr}`;

        this.container.innerHTML = `
            <div class="kpi-card kpi-card--${state}" role="button" tabindex="0" aria-label="${ariaLabel}">
                <div class="kpi-card__header">
                    <div class="kpi-card__title">${data.name || this.options.title}</div>
                    <div class="kpi-card__info">${data.context || ''}</div>
                </div>
                <div class="kpi-card__body">
                    <div class="kpi-card__value">${valueStr}</div>
                    <div class="kpi-card__variation kpi-card__variation--${state}">
                        <span class="kpi-card__trend">${trendIcon}</span>
                        <span class="kpi-card__variation-value">${variationStr}</span>
                    </div>
                </div>
                <div class="kpi-card__footer">
                    <span class="kpi-card__timestamp">Atualizado: ${updatedStr}</span>
                </div>
            </div>
        `;

        // Eventos de drill-down e acessibilidade
        const cardEl = this.container.querySelector('.kpi-card');
        if (this.options.onClick) {
            cardEl.addEventListener('click', () => this.options.onClick(data));
            cardEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.options.onClick(data);
                }
            });
        }

        this._lastRenderHash = hash;
        this._lastPeriodKey = periodKey;
    }

    /**
     * Determina estado visual com base na variação e thresholds
     */
    _determineState(variation, thresholds) {
        if (typeof variation !== 'number') return 'normal';
        const { warningHigh, warningLow, criticalHigh, criticalLow } = thresholds;
        if (variation >= criticalHigh || variation <= criticalLow) return 'critical';
        if (variation >= warningHigh || variation <= warningLow) return 'alert';
        return 'normal';
    }

    _trendIcon(trend) {
        if (trend === 'up') return '↑';
        if (trend === 'down') return '↓';
        return '→';
    }

    _formatValue(value, unit) {
        if (value === null || value === undefined || Number.isNaN(value)) return '--';
        switch (unit) {
            case 'percent':
                return `${Number(value).toFixed(1)}%`;
            case 'currency':
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 2 }).format(value);
            case 'time':
                return this._formatDuration(value);
            default:
                return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(value);
        }
    }

    _formatDuration(seconds) {
        if (typeof seconds !== 'number') return '--';
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        if (hrs > 0) return `${hrs}h ${String(mins).padStart(2, '0')}m`;
        if (mins > 0) return `${mins}m ${String(secs).padStart(2, '0')}s`;
        return `${secs}s`;
    }

    _formatTimestamp(timestamp) {
        if (!timestamp) return 'N/D';
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) return 'N/D';
        return date.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    _hashData(data) {
        try {
            return JSON.stringify({
                name: data.name,
                value: data.value,
                unit: data.unit,
                variation: data.variation,
                trend: data.trend,
                updatedAt: data.updatedAt,
                context: data.context
            });
        } catch (_) {
            return Math.random().toString();
        }
    }

    _getPeriodKey() {
        if (window.globalState && typeof window.globalState.getPeriod === 'function') {
            const p = window.globalState.getPeriod();
            return `${p.type}-${p.preset || ''}-${p.inicio || ''}-${p.fim || ''}`;
        }
        return 'no-period';
    }

    destroy() {
        if (this._unsubscribe) this._unsubscribe();
        this.container.innerHTML = '';
    }
}

// Exemplo de uso com dados mockados (ready-to-run na página)
// document.addEventListener('DOMContentLoaded', () => {
//     const mockProvider = async () => ({
//         name: 'Backlog Atual',
//         value: 1250,
//         unit: 'number',
//         variation: 5.9,
//         trend: 'up',
//         updatedAt: '2026-01-15T10:30:00Z',
//         context: 'vs. média histórica'
//     });
//
//     new KpiCard('kpi-card-backlog', {
//         kpiKey: 'backlog-atual',
//         title: 'Backlog Atual',
//         unit: 'number',
//         thresholds: {
//             warningHigh: 20,
//             warningLow: -5,
//             criticalHigh: 40,
//             criticalLow: -15
//         },
//         dataProvider: mockProvider,
//         onClick: (data) => {
//             console.log('Drill-down', data);
//         }
//     });
// });

window.KpiCard = KpiCard;
