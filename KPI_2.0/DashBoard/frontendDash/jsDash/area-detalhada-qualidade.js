/**
 * ÁREA DETALHADA - QUALIDADE
 * Sistema completo de visualização e análise da etapa de qualidade
 */

// =============================================
// CONSTANTES E CONFIGURAÇÃO
// =============================================

const API_BASE = '/DashBoard/backendDash/qualidadePHP/';
let chartInstances = {};
let currentFilters = {
    inicio: null,
    fim: null,
    setor: null,
    operador: null
};

// =============================================
// INICIALIZAÇÃO
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    initializeQualidade();
});

async function initializeQualidade() {
    try {
        extractFiltersFromURL();
        await Promise.all([
            carregarKPIs(),
            carregarInsights(),
            carregarGraficos(),
            carregarTabelaOperacional()
        ]);
        
        setupEventListeners();
        console.log('✅ Área de Qualidade inicializada com sucesso');
    } catch (error) {
        console.error('❌ Erro ao inicializar área de Qualidade:', error);
        showError('Erro ao carregar dashboard de Qualidade');
    }
}

function extractFiltersFromURL() {
    const params = new URLSearchParams(window.location.search);
    currentFilters = {
        inicio: params.get('inicio') || getDefaultStartDate(),
        fim: params.get('fim') || getDefaultEndDate(),
        setor: params.get('setor') || null,
        operador: params.get('operador') || null
    };
}

function getDefaultStartDate() {
    const date = new Date();
    date.setDate(date.getDate() - 30);
    return formatDate(date);
}

function getDefaultEndDate() {
    return formatDate(new Date());
}

function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// =============================================
// KPIs
// =============================================

async function carregarKPIs() {
    const kpis = [
        { id: 'backlog-qualidade', endpoint: 'kpi-backlog-qualidade.php', titulo: 'Backlog em Qualidade' },
        { id: 'equipamentos-aprovados', endpoint: 'kpi-equipamentos-aprovados.php', titulo: 'Equipamentos Aprovados' },
        { id: 'taxa-aprovacao', endpoint: 'kpi-taxa-aprovacao.php', titulo: 'Taxa de Aprovação' },
        { id: 'tempo-medio-qualidade', endpoint: 'kpi-tempo-medio-qualidade.php', titulo: 'Tempo Médio Qualidade' },
        { id: 'taxa-reprovacao', endpoint: 'kpi-taxa-reprovacao.php', titulo: 'Taxa de Reprovação' }
    ];
    
    const promises = kpis.map(kpi => carregarKPI(kpi));
    await Promise.all(promises);
}

async function carregarKPI(kpi) {
    try {
        const url = buildURL(API_BASE + kpi.endpoint, currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderKPI(kpi.id, kpi.titulo, data.data);
        } else {
            console.error(`Erro ao carregar KPI ${kpi.id}:`, data.message);
        }
    } catch (error) {
        console.error(`Erro ao carregar KPI ${kpi.id}:`, error);
    }
}

function renderKPI(id, titulo, data) {
    const container = document.getElementById(`kpi-${id}`);
    if (!container) return;
    
    const { valor, referencia, extras } = data;
    const { valor_anterior, variacao, estado } = referencia;
    
    // Determinar ícone de tendência
    const iconeTendencia = variacao > 0 ? '↑' : variacao < 0 ? '↓' : '→';
    const corTendencia = getCorEstado(estado);
    
    // Formatar valor principal
    let valorFormatado = valor;
    if (id.includes('taxa')) {
        valorFormatado = `${valor.toFixed(1)}%`;
    } else if (id.includes('tempo')) {
        valorFormatado = `${valor.toFixed(1)} dias`;
    } else {
        valorFormatado = valor.toLocaleString('pt-BR');
    }
    
    // HTML do KPI
    let html = `
        <div class="kpi-header">
            <h3>${titulo}</h3>
            <span class="kpi-estado ${estado}">${estado.toUpperCase()}</span>
        </div>
        <div class="kpi-valor">${valorFormatado}</div>
        <div class="kpi-referencia">
            <span class="tendencia" style="color: ${corTendencia}">
                ${iconeTendencia} ${Math.abs(variacao).toFixed(1)}%
            </span>
            <span class="periodo-anterior">vs. período anterior</span>
        </div>
    `;
    
    // Adicionar extras se existirem
    if (extras) {
        html += '<div class="kpi-extras">';
        
        if (extras.media_diaria !== undefined) {
            html += `<div class="extra-item">
                <span class="extra-label">Média Diária:</span>
                <span class="extra-valor">${extras.media_diaria.toFixed(1)} un/dia</span>
            </div>`;
        }
        
        if (extras.reprovados !== undefined) {
            html += `<div class="extra-item">
                <span class="extra-label">Reprovados:</span>
                <span class="extra-valor">${extras.reprovados} unidades</span>
            </div>`;
        }
        
        if (extras.total !== undefined) {
            html += `<div class="extra-item">
                <span class="extra-label">Total:</span>
                <span class="extra-valor">${extras.total} unidades</span>
            </div>`;
        }
        
        html += '</div>';
    }
    
    container.innerHTML = html;
}

function getCorEstado(estado) {
    const cores = {
        success: '#00e676',
        warning: '#ffd54f',
        critical: '#ff1744',
        neutral: '#9e9e9e'
    };
    return cores[estado] || cores.neutral;
}

// =============================================
// INSIGHTS
// =============================================

async function carregarInsights() {
    try {
        const url = buildURL(API_BASE + 'insights-qualidade.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data && data.data.insights) {
            renderInsights(data.data.insights);
        }
    } catch (error) {
        console.error('Erro ao carregar insights:', error);
    }
}

function renderInsights(insights) {
    const container = document.getElementById('insights-container');
    if (!container) return;
    
    if (insights.length === 0) {
        container.innerHTML = '<p class="sem-insights">Nenhum insight disponível no momento.</p>';
        return;
    }
    
    const html = insights.map(insight => {
        const iconeTipo = {
            critical: '🚨',
            warning: '⚠️',
            success: '✅',
            info: 'ℹ️'
        };
        
        return `
            <div class="insight-card ${insight.tipo}">
                <div class="insight-header">
                    <span class="insight-icone">${iconeTipo[insight.tipo] || 'ℹ️'}</span>
                    <h4>${insight.titulo}</h4>
                </div>
                <p class="insight-descricao">${insight.descricao}</p>
                <p class="insight-acao"><strong>Ação recomendada:</strong> ${insight.acao}</p>
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;
}

// =============================================
// GRÁFICOS
// =============================================

async function carregarGraficos() {
    await Promise.all([
        carregarGraficoEvolucao(),
        carregarGraficoMotivos(),
        carregarGraficoOperadores(),
        carregarGraficoTempoEtapas()
    ]);
}

async function carregarGraficoEvolucao() {
    try {
        const url = buildURL(API_BASE + 'grafico-evolucao-aprovacoes.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderGraficoEvolucao(data.data);
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de evolução:', error);
    }
}

function renderGraficoEvolucao(data) {
    const ctx = document.getElementById('grafico-evolucao-aprovacoes');
    if (!ctx) return;
    
    if (chartInstances['evolucao']) {
        chartInstances['evolucao'].destroy();
    }
    
    chartInstances['evolucao'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Aprovados',
                    data: data.aprovados,
                    backgroundColor: 'rgba(0, 230, 118, 0.8)',
                    borderColor: '#00e676',
                    borderWidth: 1
                },
                {
                    label: 'Reprovados',
                    data: data.reprovados,
                    backgroundColor: 'rgba(255, 23, 68, 0.8)',
                    borderColor: '#ff1744',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: '#ffffff' }
                },
                title: {
                    display: true,
                    text: 'Evolução: Aprovados vs Reprovados',
                    color: '#ffffff',
                    font: { size: 16 }
                }
            },
            scales: {
                x: {
                    stacked: false,
                    ticks: { color: '#9e9e9e' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                },
                y: {
                    stacked: false,
                    ticks: { color: '#9e9e9e' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                }
            }
        }
    });
}

async function carregarGraficoMotivos() {
    try {
        const url = buildURL(API_BASE + 'grafico-motivos-reprovacao.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderGraficoMotivos(data.data);
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de motivos:', error);
    }
}

function renderGraficoMotivos(data) {
    const ctx = document.getElementById('grafico-motivos-reprovacao');
    if (!ctx) return;
    
    if (chartInstances['motivos']) {
        chartInstances['motivos'].destroy();
    }
    
    const cores = [
        '#ff1744', '#ff5252', '#ff6e6e', '#ff8787',
        '#ffa0a0', '#ffb9b9', '#ffd2d2', '#ffebeb',
        '#ff5733', '#ff8c00'
    ];
    
    chartInstances['motivos'] = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.valores,
                backgroundColor: cores,
                borderColor: '#0a0e1a',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { 
                        color: '#ffffff',
                        padding: 10,
                        font: { size: 11 }
                    }
                },
                title: {
                    display: true,
                    text: 'Principais Motivos de Reprovação',
                    color: '#ffffff',
                    font: { size: 16 }
                }
            }
        }
    });
}

async function carregarGraficoOperadores() {
    try {
        const url = buildURL(API_BASE + 'grafico-qualidade-operador.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderGraficoOperadores(data.data);
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de operadores:', error);
    }
}

function renderGraficoOperadores(data) {
    const ctx = document.getElementById('grafico-qualidade-operador');
    if (!ctx) return;
    
    if (chartInstances['operadores']) {
        chartInstances['operadores'].destroy();
    }
    
    chartInstances['operadores'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Taxa de Aprovação (%)',
                data: data.valores,
                backgroundColor: data.valores.map(v => 
                    v >= 95 ? 'rgba(0, 230, 118, 0.8)' : 
                    v >= 85 ? 'rgba(255, 213, 79, 0.8)' : 
                    'rgba(255, 23, 68, 0.8)'
                ),
                borderColor: data.valores.map(v => 
                    v >= 95 ? '#00e676' : 
                    v >= 85 ? '#ffd54f' : 
                    '#ff1744'
                ),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Taxa de Aprovação por Operador',
                    color: '#ffffff',
                    font: { size: 16 }
                }
            },
            scales: {
                x: {
                    min: 0,
                    max: 100,
                    ticks: { 
                        color: '#9e9e9e',
                        callback: (value) => value + '%'
                    },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                },
                y: {
                    ticks: { color: '#9e9e9e' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                }
            }
        }
    });
}

async function carregarGraficoTempoEtapas() {
    try {
        const url = buildURL(API_BASE + 'grafico-tempo-etapas.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderGraficoTempoEtapas(data.data);
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de tempo:', error);
    }
}

function renderGraficoTempoEtapas(data) {
    const ctx = document.getElementById('grafico-tempo-etapas');
    if (!ctx) return;
    
    if (chartInstances['tempo']) {
        chartInstances['tempo'].destroy();
    }
    
    chartInstances['tempo'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Tempo Médio (dias)',
                data: data.valores,
                backgroundColor: ['rgba(17, 207, 255, 0.8)', 'rgba(56, 139, 253, 0.8)'],
                borderColor: ['#11cfff', '#388bfd'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Tempo Médio por Etapa',
                    color: '#ffffff',
                    font: { size: 16 }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#9e9e9e' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                },
                y: {
                    ticks: { 
                        color: '#9e9e9e',
                        callback: (value) => value + ' dias'
                    },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                }
            }
        }
    });
}

// =============================================
// TABELA OPERACIONAL
// =============================================

async function carregarTabelaOperacional() {
    try {
        const url = buildURL(API_BASE + 'tabela-detalhada.php', currentFilters);
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data) {
            renderTabelaOperacional(data.data.registros);
        }
    } catch (error) {
        console.error('Erro ao carregar tabela:', error);
    }
}

function renderTabelaOperacional(registros) {
    const container = document.getElementById('tabela-operacional');
    if (!container) return;
    
    if (registros.length === 0) {
        container.innerHTML = '<p class="sem-dados">Nenhum registro encontrado para o período selecionado.</p>';
        return;
    }
    
    let html = `
        <div class="tabela-wrapper">
            <table class="tabela-detalhada">
                <thead>
                    <tr>
                        <th>Data Início</th>
                        <th>NF</th>
                        <th>Cliente</th>
                        <th>Qtd Total</th>
                        <th>Aprovados</th>
                        <th>Reprovados</th>
                        <th>Taxa Rep.</th>
                        <th>Operador</th>
                        <th>Status</th>
                        <th>Motivo</th>
                        <th>Data Envio</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    registros.forEach(reg => {
        const classDestaque = reg.destaque === 'critical' ? 'destaque-critical' : 
                              reg.destaque === 'warning' ? 'destaque-warning' : '';
        
        html += `
            <tr class="${classDestaque}">
                <td>${reg.data_inicio}</td>
                <td>${reg.nota_fiscal}</td>
                <td>${reg.cliente}</td>
                <td>${reg.quantidade_total}</td>
                <td class="valor-positivo">${reg.quantidade_aprovada}</td>
                <td class="valor-negativo">${reg.reprovadas}</td>
                <td class="taxa-reprovacao">${reg.taxa_reprovacao}%</td>
                <td>${reg.operador}</td>
                <td><span class="status-badge ${reg.status.toLowerCase()}">${reg.status}</span></td>
                <td>${reg.motivo}</td>
                <td>${reg.data_envio}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// =============================================
// UTILITÁRIOS
// =============================================

function buildURL(base, filters) {
    const params = new URLSearchParams();
    
    if (filters.inicio) params.append('inicio', filters.inicio);
    if (filters.fim) params.append('fim', filters.fim);
    if (filters.setor) params.append('setor', filters.setor);
    if (filters.operador) params.append('operador', filters.operador);
    
    return `${base}?${params.toString()}`;
}

function setupEventListeners() {
    // Busca na tabela
    const searchInput = document.getElementById('search-tabela');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            currentFilters.busca = e.target.value;
            carregarTabelaOperacional();
        }, 500));
    }
    
    // Botão de atualizar
    const btnRefresh = document.getElementById('btn-refresh');
    if (btnRefresh) {
        btnRefresh.addEventListener('click', () => {
            initializeQualidade();
        });
    }
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showError(message) {
    console.error(message);
    // Aqui você pode adicionar um toast ou modal de erro
}

// Exportar funções para escopo global se necessário
window.qualidadeModule = {
    carregarKPIs,
    carregarInsights,
    carregarGraficos,
    carregarTabelaOperacional,
    initializeQualidade
};

console.log('📊 Módulo área-detalhada-qualidade.js carregado');
