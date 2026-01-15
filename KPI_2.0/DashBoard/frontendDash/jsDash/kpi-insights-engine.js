// 💡 KPI Insights Engine - Geração automática de insights para KPIs
// Requisitos: módulo desacoplado, entrada (série temporal, metadados), saída (lista de insights), insights configuráveis por KPI
// Pronto para integração com KpiCard, Dashboard Executivo/Operacional

(function () {
    /**
     * Estrutura de insight:
     * {
     *   id: 'queda-relevante',
     *   title: 'Queda Relevante',
     *   message: 'O KPI caiu 18% em relação ao período anterior',
     *   severity: 'warning' | 'critical' | 'info',
     *   kpiId: 'backlog-atual',
     *   data: { ... }
     * }
     */

    function generateKpiInsights({ series = [], meta = {}, rules = [] }) {
        const insights = [];
        if (!Array.isArray(series) || series.length < 2) return insights;
        const atual = series[series.length - 1];
        const anterior = series[series.length - 2];
        const mediaAnterior = series.slice(0, -1).reduce((a, b) => a + b, 0) / (series.length - 1);
        for (const rule of rules) {
            if (rule.type === 'relevant-drop') {
                const perc = ((atual - anterior) / anterior) * 100;
                if (perc <= -rule.threshold) {
                    insights.push({
                        id: rule.id,
                        title: rule.title || 'Queda Relevante',
                        message: rule.message?.replace('{perc}', Math.abs(perc).toFixed(1)) || `Queda de ${Math.abs(perc).toFixed(1)}% em relação ao período anterior`,
                        severity: rule.severity || 'warning',
                        kpiId: rule.kpiId,
                        data: { perc, atual, anterior }
                    });
                }
            }
            if (rule.type === 'relevant-rise') {
                const perc = ((atual - anterior) / anterior) * 100;
                if (perc >= rule.threshold) {
                    insights.push({
                        id: rule.id,
                        title: rule.title || 'Aumento Relevante',
                        message: rule.message?.replace('{perc}', perc.toFixed(1)) || `Aumento de ${perc.toFixed(1)}% em relação ao período anterior`,
                        severity: rule.severity || 'info',
                        kpiId: rule.kpiId,
                        data: { perc, atual, anterior }
                    });
                }
            }
            if (rule.type === 'persistent-trend') {
                if (series.length >= rule.periods) {
                    const lastN = series.slice(-rule.periods);
                    const isUp = lastN.every((v, i, arr) => i === 0 || v > arr[i - 1]);
                    const isDown = lastN.every((v, i, arr) => i === 0 || v < arr[i - 1]);
                    if (isUp && rule.direction === 'up') {
                        insights.push({
                            id: rule.id,
                            title: rule.title || 'Tendência de Alta',
                            message: rule.message || `Tendência de alta há ${rule.periods} períodos`,
                            severity: rule.severity || 'info',
                            kpiId: rule.kpiId,
                            data: { lastN }
                        });
                    }
                    if (isDown && rule.direction === 'down') {
                        insights.push({
                            id: rule.id,
                            title: rule.title || 'Tendência de Queda',
                            message: rule.message || `Tendência de queda há ${rule.periods} períodos`,
                            severity: rule.severity || 'warning',
                            kpiId: rule.kpiId,
                            data: { lastN }
                        });
                    }
                }
            }
            if (rule.type === 'compare-previous-avg') {
                const perc = ((atual - mediaAnterior) / mediaAnterior) * 100;
                if (Math.abs(perc) >= rule.threshold) {
                    insights.push({
                        id: rule.id,
                        title: rule.title || 'Comparação com Média',
                        message: rule.message?.replace('{perc}', perc.toFixed(1)) || `Valor atual está ${perc > 0 ? 'acima' : 'abaixo'} da média anterior em ${Math.abs(perc).toFixed(1)}%`,
                        severity: rule.severity || 'info',
                        kpiId: rule.kpiId,
                        data: { perc, atual, mediaAnterior }
                    });
                }
            }
        }
        return insights;
    }

    // Exemplo de configuração para 1 KPI
    const INSIGHT_RULES = {
        'backlog-atual': [
            {
                id: 'queda-relevante',
                type: 'relevant-drop',
                threshold: 10,
                title: 'Queda Relevante',
                message: 'Backlog caiu {perc}% em relação ao período anterior',
                severity: 'warning',
                kpiId: 'backlog-atual'
            },
            {
                id: 'aumento-relevante',
                type: 'relevant-rise',
                threshold: 15,
                title: 'Aumento Relevante',
                message: 'Backlog subiu {perc}% em relação ao período anterior',
                severity: 'info',
                kpiId: 'backlog-atual'
            },
            {
                id: 'tendencia-queda',
                type: 'persistent-trend',
                periods: 3,
                direction: 'down',
                title: 'Tendência de Queda',
                message: 'Backlog em queda há 3 períodos',
                severity: 'critical',
                kpiId: 'backlog-atual'
            },
            {
                id: 'comparacao-media',
                type: 'compare-previous-avg',
                threshold: 20,
                title: 'Comparação com Média',
                message: 'Backlog atual está {perc}% diferente da média anterior',
                severity: 'info',
                kpiId: 'backlog-atual'
            }
        ]
    };

    // Exemplo de geração de insights para 1 KPI
    // const series = [100, 120, 110, 90, 80];
    // const meta = { ... };
    // const insights = generateKpiInsights({ series, meta, rules: INSIGHT_RULES['backlog-atual'] });
    // console.log(insights);

    // Exemplo de integração em um componente (KpiCard, Dashboard)
    // async function provider({ params, signal }) {
    //     const series = [100, 120, 110, 90, 80];
    //     const meta = { ... };
    //     const insights = generateKpiInsights({ series, meta, rules: INSIGHT_RULES['backlog-atual'] });
    //     return {
    //         name: 'Backlog Atual',
    //         value: series[series.length - 1],
    //         unit: 'number',
    //         insights // lista de insights para exibir
    //     };
    // }

    window.generateKpiInsights = generateKpiInsights;
    window.INSIGHT_RULES = INSIGHT_RULES;
})();
