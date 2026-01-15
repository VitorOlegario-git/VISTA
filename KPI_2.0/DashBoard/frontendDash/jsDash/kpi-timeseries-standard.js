// 📊 Estrutura padrão de dados históricos para KPIs (preparação para IA preditiva)
// Compatível com insights, alertas, score operacional e arquitetura atual

/**
 * TimeSeriesKPI: estrutura padrão para séries temporais de KPIs
 *
 * @typedef {Object} TimeSeriesKPI
 * @property {string} kpiId - Identificador único do KPI
 * @property {string} version - Versão do contrato de dados (ex: '1.0.0')
 * @property {Array<TimeSeriesPoint>} series - Lista ordenada de pontos temporais
 * @property {Object} meta - Metadados relevantes (ex: unidade, área, fonte, período de coleta)
 *
 * @typedef {Object} TimeSeriesPoint
 * @property {string} timestamp - ISO 8601 (ex: '2026-01-15T10:00:00Z')
 * @property {number} value - Valor do KPI no instante
 * @property {Object} [meta] - Metadados opcionais (ex: origem, status, anotação)
 */

// Exemplo de série temporal de KPI
const backlogAtualTimeSeries = {
    kpiId: 'backlog-atual',
    version: '1.0.0',
    meta: {
        unidade: 'itens',
        area: 'recebimento',
        fonte: 'ERP',
        periodo: '2025-12-01/2026-01-15'
    },
    series: [
        { timestamp: '2026-01-10T10:00:00Z', value: 900 },
        { timestamp: '2026-01-11T10:00:00Z', value: 950 },
        { timestamp: '2026-01-12T10:00:00Z', value: 1200, meta: { anotacao: 'pico de demanda' } },
        { timestamp: '2026-01-13T10:00:00Z', value: 1100 },
        { timestamp: '2026-01-14T10:00:00Z', value: 1050 },
        { timestamp: '2026-01-15T10:00:00Z', value: 980 }
    ]
};

// Diretrizes de versionamento de dados:
// - Sempre incluir campo 'version' no objeto principal
// - Mudanças compatíveis (ex: novo campo opcional): incrementar patch (1.0.x)
// - Mudanças estruturais (ex: novo formato de ponto): incrementar minor/major (1.x.x ou 2.0.0)
// - Documentar mudanças relevantes em CHANGELOG ou documentação de contratos

// Contrato para coleta histórica:
// function getKpiTimeSeries(kpiId, params) => Promise<TimeSeriesKPI>
// - params: { inicio, fim, granularidade }
// - Retorna objeto TimeSeriesKPI

// Contrato para consumo por módulos de predição:
// - Entrada: TimeSeriesKPI
// - Saída: previsão, incerteza, explicações (fora do escopo deste módulo)

// Compatível com insights, alertas e score: basta consumir o array 'series' e metadados

window.TimeSeriesKPI = backlogAtualTimeSeries; // Exemplo global
