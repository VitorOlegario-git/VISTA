// 📡 KpiService - Consumo de endpoints reais de KPI do backend
// Função principal: fetchKpi(kpiId, params, signal)
// Requisitos: monta URL com período, suporta AbortController, trata erros, retorna dados normalizados para KpiCard/KpiTable
// Serviço único, sem acoplamento visual, compatível com { status, data, meta }

const KpiService = (function () {
    const BASE_URL = '/api/'; // Ajuste conforme backend
    const TIMEOUT_MS = 10000;

    /**
     * Monta URL com parâmetros
     */
    function buildUrl(kpiId, params = {}) {
        const url = new URL(BASE_URL + kpiId, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) url.searchParams.set(key, value);
        });
        return url.toString();
    }

    /**
     * Consome endpoint de KPI
     * @param {string} kpiId - ex: 'backlog-atual'
     * @param {Object} params - parâmetros de período (ex: { period: 'last_7_days' } ou { inicio, fim })
     * @param {AbortSignal} [signal] - para cancelamento
     * @returns {Promise<{ data, meta }>} - dados normalizados
     */
    async function fetchKpi(kpiId, params = {}, signal) {
        const url = buildUrl(kpiId, params);
        let controller, timeoutId;
        if (!signal) {
            controller = new AbortController();
            signal = controller.signal;
        }
        // Timeout manual
        const timeoutPromise = new Promise((_, reject) => {
            timeoutId = setTimeout(() => {
                if (controller) controller.abort();
                reject(new Error('Timeout ao consultar KPI'));
            }, TIMEOUT_MS);
        });
        try {
            const fetchPromise = fetch(url, { signal });
            const response = await Promise.race([fetchPromise, timeoutPromise]);
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
            }
            const json = await response.json();
            if (json.status !== 'ok') {
                throw new Error(json.message || 'Erro no backend');
            }
            return {
                data: json.data,
                meta: json.meta
            };
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            throw new Error(error.message || 'Erro desconhecido ao consultar KPI');
        }
    }

    return {
        fetchKpi
    };
})();

// Exemplo de chamada real:
// KpiService.fetchKpi('backlog-atual', { period: 'last_7_days' }, signal)
//   .then(({ data, meta }) => { ... })
//   .catch(err => { ... });

// Exemplo de integração com provider de um KPI:
// async function provider({ params, signal }) {
//     const { data, meta } = await KpiService.fetchKpi('backlog-atual', params, signal);
//     // Normaliza para KpiCard
//     return {
//         name: meta.kpi_name,
//         value: data.valor,
//         unit: meta.unit,
//         variation: data.variacao,
//         trend: data.trend,
//         updatedAt: meta.timestamp,
//         context: meta.contexto
//     };
// }

window.KpiService = KpiService;
