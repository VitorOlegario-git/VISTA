// 📡 KpiService - Consumo de endpoints reais de KPI do backend
// Função principal: fetchKpi(kpiId, params, signal)
// Requisitos: monta URL com período, suporta AbortController, trata erros, retorna dados normalizados para KpiCard/KpiTable
// Serviço único, sem acoplamento visual, compatível com { status, data, meta }


const KpiService = (function () {
    const BASE_URL = '/api/'; // Ajuste conforme backend
    const TIMEOUT_MS = 10000;
    const RETRY_LIMIT = 2;
    const RETRY_DELAY = 800;
    // In-memory cache: { [cacheKey]: { promise, timestamp, data, meta } }
    const cache = {};
    // Deduplication: { [cacheKey]: promise }
    const inflight = {};

    // Sanitiza parâmetros (simples)
    function sanitizeParams(params = {}) {
        const safe = {};
        for (const [k, v] of Object.entries(params)) {
            // Permite apenas string, number, boolean simples
            if (typeof v === 'string' || typeof v === 'number' || typeof v === 'boolean') {
                safe[k.replace(/[^a-zA-Z0-9_\-]/g, '')] = v;
            }
        }
        return safe;
    }

    function buildUrl(kpiId, params = {}) {
        const url = new URL(BASE_URL + encodeURIComponent(kpiId), window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) url.searchParams.set(key, value);
        });
        return url.toString();
    }

    // Gera chave única para cache/dedup por kpiId+params
    function getCacheKey(kpiId, params) {
        return kpiId + ':' + JSON.stringify(params);
    }

    // Retry controlado para falhas transitórias
    async function fetchWithRetry(url, options, retries = RETRY_LIMIT) {
        for (let attempt = 0; attempt <= retries; attempt++) {
            try {
                const response = await fetch(url, options);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response;
            } catch (err) {
                if (attempt === retries || (err.name === 'AbortError')) throw err;
                await new Promise(res => setTimeout(res, RETRY_DELAY * (attempt + 1)));
            }
        }
    }

    /**
     * Consome endpoint de KPI com cache, dedup, retry, logs, header auth opcional
     * @param {string} kpiId
     * @param {Object} params
     * @param {AbortSignal} [signal]
     * @param {Object} [options] - { authHeader }
     * @returns {Promise<{ data, meta }>}
     */
    async function fetchKpi(kpiId, params = {}, signal, options = {}) {
        const safeParams = sanitizeParams(params);
        const cacheKey = getCacheKey(kpiId, safeParams);
        // Cache curto (30s)
        const CACHE_TTL = 30 * 1000;
        const now = Date.now();
        if (cache[cacheKey] && (now - cache[cacheKey].timestamp < CACHE_TTL)) {
            return Promise.resolve({ data: cache[cacheKey].data, meta: cache[cacheKey].meta });
        }
        if (inflight[cacheKey]) {
            // Dedup: retorna mesma promise se chamada simultânea
            return inflight[cacheKey];
        }
        let controller, timeoutId;
        if (!signal) {
            controller = new AbortController();
            signal = controller.signal;
        }
        const url = buildUrl(kpiId, safeParams);
        const fetchOptions = {
            signal,
            headers: {}
        };
        // Suporte a header de autenticação (não implementa auth real)
        if (options.authHeader) {
            fetchOptions.headers['Authorization'] = options.authHeader;
        }
        // Timeout manual
        const timeoutPromise = new Promise((_, reject) => {
            timeoutId = setTimeout(() => {
                if (controller) controller.abort();
                reject(new Error('Timeout ao consultar KPI'));
            }, TIMEOUT_MS);
        });
        // Observabilidade
        function logError(msg, err) {
            if (window && window.console) {
                console.error('[KpiService]', msg, err);
            }
        }
        // Promise principal
        const mainPromise = (async () => {
            try {
                const fetchPromise = fetchWithRetry(url, fetchOptions, RETRY_LIMIT);
                const response = await Promise.race([fetchPromise, timeoutPromise]);
                clearTimeout(timeoutId);
                const json = await response.json();
                if (json.status !== 'ok') {
                    logError('Erro no backend', json);
                    throw new Error(json.message || 'Erro no backend');
                }
                // Salva no cache
                cache[cacheKey] = {
                    data: json.data,
                    meta: json.meta,
                    timestamp: Date.now()
                };
                return { data: json.data, meta: json.meta };
            } catch (error) {
                logError('Erro ao consultar KPI', error);
                if (error.name === 'AbortError') throw error;
                throw new Error(error.message || 'Erro desconhecido ao consultar KPI');
            } finally {
                delete inflight[cacheKey];
            }
        })();
        inflight[cacheKey] = mainPromise;
        return mainPromise;
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
