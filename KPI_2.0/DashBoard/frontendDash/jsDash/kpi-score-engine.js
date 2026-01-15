// 🏅 KPI Score Engine - Cálculo de score operacional agregando múltiplos KPIs
// Requisitos: score 0-100, pesos configuráveis, normalização, breakdown, módulo desacoplado, pronto para dashboards

(function () {
    /**
     * Estrutura de entrada:
     * {
     *   kpis: [
     *     { id, value, meta: { min, max, target, invert } },
     *   ],
     *   weights: { [kpiId]: peso },
     *   area: 'recebimento' | 'analise' | 'qualidade' | ...
     * }
     *
     * Normalização:
     * - Se invert: quanto menor, melhor (ex: backlog)
     * - Se não: quanto maior, melhor (ex: taxa de sucesso)
     * - Usa min/max/target para normalizar valor em 0-100
     */

    function normalizeKpiValue(value, meta = {}) {
        const { min = 0, max = 100, target, invert = false } = meta;
        if (typeof value !== 'number' || isNaN(value)) return 0;
        let norm;
        if (typeof target === 'number') {
            // Score máximo se atingir ou superar target
            if (!invert) {
                if (value >= target) return 100;
                norm = ((value - min) / (target - min)) * 100;
            } else {
                if (value <= target) return 100;
                norm = ((max - value) / (max - target)) * 100;
            }
        } else {
            // Linear min-max
            norm = invert ? ((max - value) / (max - min)) * 100 : ((value - min) / (max - min)) * 100;
        }
        return Math.max(0, Math.min(100, norm));
    }

    /**
     * Calcula score operacional
     * @param {Object} input
     * @param {Array} input.kpis - [{ id, value, meta }]
     * @param {Object} input.weights - { [kpiId]: peso }
     * @param {string} [input.area] - área opcional
     * @returns {Object} { score, breakdown: [{ id, value, norm, weight, partial }], area }
     */
    function calculateOperationalScore({ kpis = [], weights = {}, area = null }) {
        let totalWeight = 0;
        let weightedSum = 0;
        const breakdown = kpis.map(kpi => {
            const weight = weights[kpi.id] ?? 1;
            const norm = normalizeKpiValue(kpi.value, kpi.meta);
            const partial = norm * weight;
            totalWeight += weight;
            weightedSum += partial;
            return {
                id: kpi.id,
                value: kpi.value,
                norm: Math.round(norm),
                weight,
                partial: Math.round(partial),
                meta: kpi.meta
            };
        });
        const score = totalWeight > 0 ? Math.round(weightedSum / totalWeight) : 0;
        return { score, breakdown, area };
    }

    // Exemplo de cálculo com 3 KPIs
    // const kpis = [
    //     { id: 'backlog', value: 12, meta: { min: 0, max: 30, target: 5, invert: true } }, // quanto menor, melhor
    //     { id: 'taxa-sucesso', value: 93, meta: { min: 80, max: 100, target: 95 } },      // quanto maior, melhor
    //     { id: 'pendencias', value: 7, meta: { min: 0, max: 20, target: 3, invert: true } }
    // ];
    // const weights = { backlog: 2, 'taxa-sucesso': 3, pendencias: 1 };
    // const result = calculateOperationalScore({ kpis, weights, area: 'recebimento' });
    // console.log(result);

    // Exemplo de integração em dashboard
    // function renderScoreDashboard() {
    //     const { score, breakdown, area } = calculateOperationalScore({ kpis, weights, area: 'recebimento' });
    //     // Exibir score final e breakdown por KPI
    //     // ...
    // }

    window.calculateOperationalScore = calculateOperationalScore;
    window.normalizeKpiValue = normalizeKpiValue;
})();
