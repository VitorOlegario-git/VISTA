// 🏠 Dashboard Executivo - Composição oficial de KPIs
// Requisitos: até 6 KPIs, usa KpiCard, observa GlobalState, sem acoplamento a backend.
// Dependências esperadas no global scope: window.KpiCard (components/KpiCard.js) e opcional window.globalState.

(function () {
    const KPI_DEFINITIONS = [
        {
            id: 'kpi-backlog-atual',
            title: 'Backlog Atual',
            unit: 'number',
            thresholds: { warningHigh: 15, warningLow: -5, criticalHigh: 30, criticalLow: -15 },
            providerKey: 'backlogAtual'
        },
        {
            id: 'kpi-tempo-medio-total',
            title: 'Tempo Médio Total',
            unit: 'time',
            thresholds: { warningHigh: 10, warningLow: -10, criticalHigh: 20, criticalLow: -20 },
            providerKey: 'tempoMedioTotal'
        },
        {
            id: 'kpi-taxa-sucesso',
            title: 'Taxa de Sucesso',
            unit: 'percent',
            thresholds: { warningHigh: -5, warningLow: -10, criticalHigh: -10, criticalLow: -20 },
            providerKey: 'taxaSucesso'
        },
        {
            id: 'kpi-volume-processado',
            title: 'Volume Processado',
            unit: 'number',
            thresholds: { warningHigh: -5, warningLow: -10, criticalHigh: -10, criticalLow: -20 },
            providerKey: 'volumeProcessado'
        },
        {
            id: 'kpi-critico-dia',
            title: 'KPI Crítico do Dia',
            unit: 'number',
            thresholds: { warningHigh: 10, warningLow: -10, criticalHigh: 20, criticalLow: -20 },
            providerKey: 'criticoDia'
        },
        {
            id: 'kpi-tendencia-geral',
            title: 'Tendência Geral',
            unit: 'percent',
            thresholds: { warningHigh: 5, warningLow: -5, criticalHigh: 10, criticalLow: -10 },
            providerKey: 'tendenciaGeral'
        }
    ];

    // Mock providers para exemplo; em produção, injete serviços reais via composeExecutiveDashboard(..., { providers })
    const MOCK_PROVIDERS = {
        backlogAtual: async () => ({
            name: 'Backlog Atual', value: 1250, unit: 'number', variation: 5.9, trend: 'up', updatedAt: '2026-01-15T10:30:00Z', context: 'vs. média histórica'
        }),
        tempoMedioTotal: async () => ({
            name: 'Tempo Médio Total', value: 37200, unit: 'time', variation: -8.5, trend: 'down', updatedAt: '2026-01-15T10:20:00Z', context: 'segundos totais'
        }),
        taxaSucesso: async () => ({
            name: 'Taxa de Sucesso', value: 92.3, unit: 'percent', variation: -2.1, trend: 'down', updatedAt: '2026-01-15T10:25:00Z', context: 'ordens concluídas'
        }),
        volumeProcessado: async () => ({
            name: 'Volume Processado', value: 18450, unit: 'number', variation: 12.0, trend: 'up', updatedAt: '2026-01-15T10:10:00Z', context: 'últimos 30 dias'
        }),
        criticoDia: async () => ({
            name: 'KPI Crítico do Dia', value: 58.0, unit: 'percent', variation: 18.0, trend: 'up', updatedAt: '2026-01-15T09:55:00Z', context: 'Tempo ocioso em Reparo'
        }),
        tendenciaGeral: async () => ({
            name: 'Tendência Geral', value: 3.4, unit: 'percent', variation: 1.2, trend: 'up', updatedAt: '2026-01-15T10:05:00Z', context: 'média ponderada dos KPIs'
        })
    };

    /**
     * Composição principal do Dashboard Executivo.
     * Puro: não conhece backend; recebe providers injetados; fácil adicionar/remover KPI.
     * @param {Object} options
     * @param {string} options.containerId - id do container raiz
     * @param {Object} [options.providers] - mapa de funções async por providerKey
     * @param {Function} [options.onCardClick] - callback de drill-down (data) => {}
     * @returns {Function} destroy - limpa listeners e DOM gerado
     */
    function composeExecutiveDashboard({ containerId, providers = {}, onCardClick = null }) {
        const root = document.getElementById(containerId);
        if (!root) throw new Error(`Container '${containerId}' não encontrado`);

        const mergedProviders = { ...MOCK_PROVIDERS, ...providers };

        // Cria grid estática (6 slots)
        root.innerHTML = `
            <section class="exec-dashboard" aria-label="Dashboard Executivo de KPIs">
                <div class="exec-dashboard__grid">
                    ${KPI_DEFINITIONS.map(kpi => `<div class="exec-dashboard__cell" id="cell-${kpi.id}"></div>`).join('')}
                </div>
            </section>
        `;

        const instances = [];

        KPI_DEFINITIONS.forEach((kpi) => {
            const cellId = `cell-${kpi.id}`;
            const provider = mergedProviders[kpi.providerKey];

            const dataProvider = async ({ params, signal }) => {
                if (!provider) throw new Error(`Provider não definido para ${kpi.providerKey}`);
                // Providers recebem params (period) e signal (AbortController) se quiserem usar
                return provider({ params, signal });
            };

            const card = new window.KpiCard(cellId, {
                kpiKey: kpi.id,
                title: kpi.title,
                unit: kpi.unit,
                thresholds: kpi.thresholds,
                dataProvider,
                onClick: onCardClick
            });

            instances.push(card);
        });

        // Retorna função para cleanup
        return function destroy() {
            instances.forEach(c => c.destroy());
            root.innerHTML = '';
        };
    }

    // Exemplo de composição com dados mockados (pronto para copiar/colar no PHP/HTML)
    // document.addEventListener('DOMContentLoaded', () => {
    //     composeExecutiveDashboard({
    //         containerId: 'exec-dashboard-root',
    //         providers: MOCK_PROVIDERS,
    //         onCardClick: (data) => {
    //             console.log('Drill-down KPI', data.name, data);
    //         }
    //     });
    // });

    // Exporta para uso global
    window.composeExecutiveDashboard = composeExecutiveDashboard;
})();
