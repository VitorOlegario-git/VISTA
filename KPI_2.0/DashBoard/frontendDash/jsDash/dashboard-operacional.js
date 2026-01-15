// 🏭 Dashboard Operacional - Foco em execução diária e ação
// Requisitos: KPIs operacionais, tabelas de trabalho, integração com período global e engine de alertas, itens críticos no topo, composição pura.
// Reutiliza: KpiCard, KpiTable, evaluateKpiAlerts. Não faz fetch direto, usa providers injetados. Pronto para adaptação por área.

(function () {
    // Definição dos KPIs operacionais (exemplo para área de recebimento)
    const KPI_OPS = [
        {
            id: 'backlog-operador',
            title: 'Backlog por Operador',
            unit: 'number',
            rules: [
                { id: 'backlog-critico', type: 'upper-limit', threshold: 20, message: 'Backlog crítico para operador', severity: 'critical' },
                { id: 'backlog-alerta', type: 'upper-limit', threshold: 10, message: 'Backlog alto para operador', severity: 'alert' }
            ],
            providerKey: 'backlogOperador'
        },
        {
            id: 'pendencias-dia',
            title: 'Pendências do Dia',
            unit: 'number',
            rules: [
                { id: 'pendencia-critica', type: 'upper-limit', threshold: 15, message: 'Pendências críticas hoje', severity: 'critical' },
                { id: 'pendencia-alerta', type: 'upper-limit', threshold: 8, message: 'Pendências acima do ideal', severity: 'alert' }
            ],
            providerKey: 'pendenciasDia'
        }
    ];

    // Mock providers para exemplo
    const MOCK_PROVIDERS = {
        backlogOperador: async ({ params }) => {
            // Simula 3 operadores
            const operadores = [
                { nome: 'Ana', valor: 22, previous: 18, meta: { history: [15, 18, 22] } },
                { nome: 'Bruno', valor: 9, previous: 10, meta: { history: [12, 10, 9] } },
                { nome: 'Carlos', valor: 16, previous: 14, meta: { history: [13, 14, 16] } }
            ];
            // Aplica engine de alertas
            return operadores.map(op => {
                const { state, alerts } = window.evaluateKpiAlerts({ value: op.valor, previous: op.previous, meta: op.meta, rules: KPI_OPS[0].rules });
                return {
                    name: `Backlog - ${op.nome}`,
                    value: op.valor,
                    unit: 'number',
                    variation: ((op.valor - op.previous) / op.previous) * 100,
                    trend: op.valor > op.previous ? 'up' : op.valor < op.previous ? 'down' : 'neutral',
                    updatedAt: '2026-01-15T10:30:00Z',
                    context: alerts.length ? alerts[0].message : 'vs. dia anterior',
                    state,
                    alerts,
                    operador: op.nome
                };
            });
        },
        pendenciasDia: async ({ params }) => {
            // Simula valor único
            const valor = 17, previous = 12, meta = { history: [10, 12, 17] };
            const { state, alerts } = window.evaluateKpiAlerts({ value: valor, previous, meta, rules: KPI_OPS[1].rules });
            return {
                name: 'Pendências do Dia',
                value: valor,
                unit: 'number',
                variation: ((valor - previous) / previous) * 100,
                trend: valor > previous ? 'up' : valor < previous ? 'down' : 'neutral',
                updatedAt: '2026-01-15T10:30:00Z',
                context: alerts.length ? alerts[0].message : 'vs. dia anterior',
                state,
                alerts
            };
        },
        itensPendentes: async ({ params, page, pageSize, sortBy, sortDir }) => {
            // Simula 20 itens, alguns críticos
            let all = Array.from({ length: 20 }, (_, i) => ({
                id: 2000 + i,
                descricao: `Item ${i + 1}`,
                status: i % 5 === 0 ? 'Crítico' : 'Pendente',
                prioridade: i % 5 === 0 ? 'Alta' : 'Normal',
                operador: ['Ana', 'Bruno', 'Carlos'][i % 3],
                data: '2026-01-15'
            }));
            // Itens críticos no topo
            all = all.sort((a, b) => (b.status === 'Crítico') - (a.status === 'Crítico'));
            // Ordenação customizada
            if (sortBy) {
                all = [...all].sort((a, b) => {
                    if (a[sortBy] < b[sortBy]) return sortDir === 'asc' ? -1 : 1;
                    if (a[sortBy] > b[sortBy]) return sortDir === 'asc' ? 1 : -1;
                    return 0;
                });
            }
            const start = (page - 1) * pageSize;
            const paged = all.slice(start, start + pageSize);
            return { data: paged, total: all.length };
        }
    };

    /**
     * Composição principal do Dashboard Operacional
     * @param {Object} options
     * @param {string} options.containerId - id do container raiz
     * @param {Object} [options.providers] - mapa de funções async por providerKey
     * @returns {Function} destroy - limpa listeners e DOM gerado
     */
    function composeOperationalDashboard({ containerId, providers = {} }) {
        const root = document.getElementById(containerId);
        if (!root) throw new Error(`Container '${containerId}' não encontrado`);
        const mergedProviders = { ...MOCK_PROVIDERS, ...providers };
        const periodParams = window.globalState?.getApiParams ? window.globalState.getApiParams() : {};

        // Renderiza KPIs operacionais (exemplo: backlog por operador)
        mergedProviders.backlogOperador({ params: periodParams }).then(kpis => {
            // KPIs críticos no topo
            kpis.sort((a, b) => (b.state === 'critical') - (a.state === 'critical'));
            root.innerHTML = `
                <section class="ops-dashboard" aria-label="Dashboard Operacional">
                    <div class="ops-dashboard__kpis">
                        ${kpis.map((kpi, i) => `<div class="ops-dashboard__kpi-cell" id="ops-kpi-${i}"></div>`).join('')}
                    </div>
                    <div class="ops-dashboard__tables">
                        <div class="ops-dashboard__table-cell" id="ops-table-pendencias"></div>
                    </div>
                </section>
            `;
            // Instancia KpiCard para cada operador
            kpis.forEach((kpi, i) => {
                new window.KpiCard(`ops-kpi-${i}`, {
                    kpiKey: 'backlog-operador',
                    title: kpi.name,
                    unit: 'number',
                    dataProvider: async () => kpi,
                    // Pode customizar visual conforme kpi.state/alerts
                });
            });
            // Instancia KpiTable para itens pendentes
            new window.KpiTable({
                containerId: 'ops-table-pendencias',
                columns: [
                    { key: 'id', label: 'ID' },
                    { key: 'descricao', label: 'Descrição' },
                    { key: 'status', label: 'Status' },
                    { key: 'prioridade', label: 'Prioridade' },
                    { key: 'operador', label: 'Operador' },
                    { key: 'data', label: 'Data' }
                ],
                dataProvider: mergedProviders.itensPendentes,
                onRowClick: (row) => {
                    // Exemplo: alert(JSON.stringify(row));
                },
                periodParams
            });
        });

        // Retorna função para cleanup
        return function destroy() {
            root.innerHTML = '';
        };
    }

    // Exemplo de uso com dados mockados
    // document.addEventListener('DOMContentLoaded', () => {
    //     composeOperationalDashboard({
    //         containerId: 'ops-dashboard-root',
    //         providers: MOCK_PROVIDERS
    //     });
    // });

    window.composeOperationalDashboard = composeOperationalDashboard;
})();
