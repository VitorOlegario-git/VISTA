// 🚨 KPI Alert Engine - Camada de regras semânticas e alertas visuais
// Requisitos: engine desacoplada, entrada (valor atual, anterior, metadados), saída (estado, alertas), regras configuráveis por KPI, integração simples com KpiCard.

(function () {
    /**
     * Estrutura de regra:
     * {
     *   id: 'backlog-high',
     *   type: 'upper-limit',
     *   threshold: 1000,
     *   message: 'Backlog acima do limite aceitável',
     *   severity: 'critical' | 'alert',
     *   applies: (input) => boolean // função opcional para lógica customizada
     * }
     */

    function evaluateKpiAlerts({ value, previous, meta = {}, rules = [] }) {
        const alerts = [];
        let state = 'normal';
        for (const rule of rules) {
            let triggered = false;
            if (rule.type === 'upper-limit' && value > rule.threshold) triggered = true;
            if (rule.type === 'lower-limit' && value < rule.threshold) triggered = true;
            if (rule.type === 'negative-trend') {
                // Exemplo: tendência negativa por N períodos
                if (Array.isArray(meta.history) && meta.history.length >= (rule.periods || 3)) {
                    const lastN = meta.history.slice(-rule.periods);
                    if (lastN.every((v, i, arr) => i === 0 || v < arr[i - 1])) triggered = true;
                } else if (typeof previous === 'number' && value < previous) {
                    triggered = true;
                }
            }
            if (typeof rule.applies === 'function' && rule.applies({ value, previous, meta })) {
                triggered = true;
            }
            if (triggered) {
                alerts.push({
                    id: rule.id,
                    severity: rule.severity,
                    message: rule.message,
                    rule
                });
                if (rule.severity === 'critical') state = 'critical';
                else if (rule.severity === 'alert' && state !== 'critical') state = 'alert';
            }
        }
        return { state, alerts };
    }

    // Exemplo de configuração para 2 KPIs
    const KPI_RULES = {
        'backlog-atual': [
            {
                id: 'backlog-high',
                type: 'upper-limit',
                threshold: 1000,
                message: 'Backlog acima do limite aceitável',
                severity: 'critical'
            },
            {
                id: 'backlog-warning',
                type: 'upper-limit',
                threshold: 800,
                message: 'Backlog se aproximando do limite',
                severity: 'alert'
            }
        ],
        'taxa-sucesso': [
            {
                id: 'taxa-baixa',
                type: 'lower-limit',
                threshold: 90,
                message: 'Taxa de sucesso abaixo do ideal',
                severity: 'alert'
            },
            {
                id: 'taxa-queda',
                type: 'negative-trend',
                periods: 3,
                message: 'Taxa de sucesso em queda há 3 períodos',
                severity: 'critical'
            }
        ]
    };

    // Exemplo de integração com KpiCard
    // document.addEventListener('DOMContentLoaded', () => {
    //     const rules = KPI_RULES['backlog-atual'];
    //     new window.KpiCard('kpi-card-backlog', {
    //         kpiKey: 'backlog-atual',
    //         title: 'Backlog Atual',
    //         unit: 'number',
    //         dataProvider: async () => {
    //             // Simula dados
    //             const value = 1200;
    //             const previous = 950;
    //             const meta = { history: [900, 950, 1200] };
    //             const { state, alerts } = evaluateKpiAlerts({ value, previous, meta, rules });
    //             return {
    //                 name: 'Backlog Atual',
    //                 value,
    //                 unit: 'number',
    //                 variation: ((value - previous) / previous) * 100,
    //                 trend: value > previous ? 'up' : value < previous ? 'down' : 'neutral',
    //                 updatedAt: '2026-01-15T10:30:00Z',
    //                 context: alerts.length ? alerts[0].message : 'vs. período anterior',
    //                 state, // pode ser usado para customizar visual
    //                 alerts // lista de alertas ativos
    //             };
    //         }
    //     });
    // });

    // Exporta para uso global
    window.evaluateKpiAlerts = evaluateKpiAlerts;
    window.KPI_RULES = KPI_RULES;
})();
