// Exemplos de uso do KpiService com hardening (cache, dedup, retry, logs, auth header, sanitização)

// Exemplo 1: Chamada simples com cache e deduplicação
async function exemploSimples() {
    try {
        const { data, meta } = await KpiService.fetchKpi('backlog-atual', { period: '2026-01' });
        console.log('KPI:', data, meta);
    } catch (err) {
        console.error('Erro:', err);
    }
}

// Exemplo 2: Múltiplas chamadas simultâneas (deduplica, só faz 1 request)
function exemploDedup() {
    const p1 = KpiService.fetchKpi('backlog-atual', { period: '2026-01' });
    const p2 = KpiService.fetchKpi('backlog-atual', { period: '2026-01' });
    Promise.all([p1, p2]).then(([r1, r2]) => {
        console.log('Dedup:', r1, r2); // Mesma resposta, 1 chamada
    });
}

// Exemplo 3: Retry controlado em falha transitória
async function exemploRetry() {
    try {
        // Supondo endpoint instável
        const { data } = await KpiService.fetchKpi('kpi-instavel', { period: '2026-01' });
        console.log('KPI instável:', data);
    } catch (err) {
        console.error('Falha após retries:', err);
    }
}

// Exemplo 4: Suporte a header de autenticação
async function exemploAuth() {
    const token = 'Bearer fake-token';
    const { data } = await KpiService.fetchKpi('backlog-atual', { period: '2026-01' }, undefined, { authHeader: token });
    console.log('Com auth header:', data);
}

// Exemplo 5: Parâmetros "sujos" são sanitizados
async function exemploSanitize() {
    const { data } = await KpiService.fetchKpi('backlog-atual', { 'period;DROP TABLE': '2026-01', '<script>': 'x' });
    console.log('Sanitizado:', data);
}

// Para testar, chame as funções no console do navegador ou em integração com dashboard.
