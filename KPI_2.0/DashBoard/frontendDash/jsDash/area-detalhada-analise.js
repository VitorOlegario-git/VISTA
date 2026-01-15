/**
 * ÁREA DETALHADA - ANÁLISE
 * JavaScript específico para visualização operacional da área de Análise
 */

// ========================================
// 2. CARREGAR KPIs OPERACIONAIS
// ========================================
async function carregarKPIs() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        // Buscar KPIs em paralelo
        const [backlog, analisados, taxaConversao, tempoMedio, valorOrcado] = await Promise.all([
            fetch(`/DashBoard/backendDash/analisePHP/kpi-equipamentos-em-analise.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/analisePHP/kpi-equipamentos-analisados.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/analisePHP/kpi-taxa-conversao-analise.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/analisePHP/tempo_medio_analise.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/analisePHP/kpi-valor-orcado-analise.php?${params}`).then(r => r.json())
        ]);

        const kpisContainer = document.getElementById('kpis-container');
        kpisContainer.innerHTML = '';

        // KPI 1: Equipamentos em Análise (Backlog)
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-hourglass-half',
            label: 'Equipamentos em Análise',
            valor: backlog.data.valor,
            unidade: 'equipamentos',
            variacao: backlog.data.referencia.variacao,
            estado: backlog.data.referencia.estado,
            inverterCores: true
        }));

        // KPI 2: Equipamentos Analisados
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-clipboard-check',
            label: 'Equipamentos Analisados',
            valor: analisados.data.valor,
            unidade: 'equipamentos',
            variacao: analisados.data.referencia.variacao,
            estado: analisados.data.referencia.estado
        }));

        // KPI 3: Taxa de Conversão
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-percentage',
            label: 'Taxa de Conversão',
            valor: taxaConversao.data.valor,
            unidade: '%',
            variacao: taxaConversao.data.referencia.variacao,
            estado: taxaConversao.data.referencia.estado
        }));

        // KPI 4: Tempo Médio de Análise
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-clock',
            label: 'Tempo Médio de Análise',
            valor: tempoMedio.data.valor,
            unidade: 'dias',
            variacao: tempoMedio.data.referencia.variacao,
            estado: tempoMedio.data.referencia.estado,
            inverterCores: true
        }));

        // KPI 5: Valor Orçado
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-dollar-sign',
            label: 'Valor Orçado na Análise',
            valor: valorOrcado.data.valor,
            unidade: 'R$',
            variacao: valorOrcado.data.referencia.variacao,
            estado: valorOrcado.data.referencia.estado,
            formatarMoeda: true
        }));

    } catch (error) {
        console.error('Erro ao carregar KPIs:', error);
    }
}

function criarCardKPI(config) {
    const card = document.createElement('div');
    card.className = 'kpi-card';
    card.setAttribute('data-estado', config.estado);

    const iconeCor = {
        'success': 'var(--accent-green)',
        'warning': 'var(--accent-orange)',
        'critical': 'var(--accent-red)'
    }[config.estado] || 'var(--accent-cyan)';

    // Determinar direção da variação
    let variacaoIcon = '→';
    let variacaoClass = 'neutral';
    
    if (config.variacao > 0) {
        variacaoIcon = '↑';
        variacaoClass = config.inverterCores ? 'negative' : 'positive';
    } else if (config.variacao < 0) {
        variacaoIcon = '↓';
        variacaoClass = config.inverterCores ? 'positive' : 'negative';
    }

    // Formatar valor
    let valorFormatado = config.valor;
    if (config.formatarMoeda) {
        valorFormatado = config.valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    } else {
        valorFormatado = config.valor.toLocaleString('pt-BR');
    }

    card.innerHTML = `
        <div class="kpi-header">
            <div class="kpi-icon" style="color: ${iconeCor}; border: 1px solid ${iconeCor};">
                <i class="fas ${config.icone}"></i>
            </div>
        </div>
        <div class="kpi-label">${config.label}</div>
        <div class="kpi-value">
            ${config.unidade === 'R$' ? config.unidade + ' ' : ''}${valorFormatado}
            ${config.unidade !== 'R$' ? '<span class="kpi-unit">' + config.unidade + '</span>' : ''}
        </div>
        <div class="kpi-comparison ${variacaoClass}">
            <span>${variacaoIcon} ${Math.abs(config.variacao).toFixed(1)}%</span>
            <span style="color: var(--text-muted); font-size: 11px;">vs período anterior</span>
        </div>
    `;

    return card;
}

// ========================================
// 3. CARREGAR INSIGHTS AUTOMÁTICOS
// ========================================
async function carregarInsights() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/analisePHP/insights-analise.php?${params}`);
        const data = await response.json();

        if (!data.data || data.data.length === 0) {
            document.getElementById('insights-section').style.display = 'none';
            return;
        }

        const insightsContainer = document.getElementById('insights-container');
        insightsContainer.innerHTML = '';

        data.data.slice(0, 3).forEach(insight => {
            const card = document.createElement('div');
            card.className = `insight-card ${insight.tipo || 'info'}`;

            const iconeMap = {
                'gargalo': 'hourglass-half',
                'conversao': 'chart-line',
                'tempo': 'clock',
                'valor': 'dollar-sign',
                'operacao': 'check-circle'
            };

            const icone = iconeMap[insight.categoria] || 'lightbulb';

            card.innerHTML = `
                <div class="insight-icon">
                    <i class="fas fa-${icone}"></i>
                </div>
                <div class="insight-content">
                    <div class="insight-title">${insight.titulo}</div>
                    <div class="insight-message">${insight.mensagem}</div>
                    ${insight.causa ? `
                        <div class="insight-causa">
                            <strong><i class="fas fa-search"></i> Causa Provável:</strong> ${insight.causa}
                        </div>
                    ` : ''}
                    ${insight.acao ? `
                        <div class="insight-acao">
                            <strong><i class="fas fa-wrench"></i> Ação Recomendada:</strong> ${insight.acao}
                        </div>
                    ` : ''}
                </div>
            `;

            insightsContainer.appendChild(card);
        });

        document.getElementById('insights-section').style.display = 'block';

    } catch (error) {
        console.error('Erro ao carregar insights:', error);
        document.getElementById('insights-section').style.display = 'none';
    }
}

// ========================================
// 4. CARREGAR GRÁFICOS OPERACIONAIS
// ========================================
let chartsInstances = {};

async function carregarGraficos() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        data_inicial: filtros.inicio,
        data_final: filtros.fim
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        // Gráfico A: Evolução de Análises
        await carregarGraficoEvolucao(params);

        // Gráfico B: Análises por Cliente
        await carregarGraficoClientes(params);

        // Gráfico C: Parciais vs Completas
        await carregarGraficoParciais(params);

        // Gráfico D: Tempo Médio por Operador
        await carregarGraficoTempo(params);

    } catch (error) {
        console.error('Erro ao carregar gráficos:', error);
    }
}

async function carregarGraficoEvolucao(params) {
    const response = await fetch(`/DashBoard/backendDash/analisePHP/grafico-evolucao-analises.php?${params}`);
    const data = await response.json();

    const ctx = document.getElementById('chartEvolucao').getContext('2d');

    if (chartsInstances.evolucao) {
        chartsInstances.evolucao.destroy();
    }

    chartsInstances.evolucao = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Equipamentos Analisados',
                data: data.analisados,
                backgroundColor: 'rgba(17, 207, 255, 0.7)',
                borderColor: 'rgba(17, 207, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(232, 244, 255, 0.9)',
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(168, 197, 224, 1)',
                    borderColor: 'rgba(17, 207, 255, 0.3)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(17, 207, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(17, 207, 255, 0.05)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

async function carregarGraficoClientes(params) {
    const response = await fetch(`/DashBoard/backendDash/analisePHP/grafico-por-cliente.php?${params}`);
    const data = await response.json();

    const ctx = document.getElementById('chartSetor').getContext('2d');

    if (chartsInstances.setor) {
        chartsInstances.setor.destroy();
    }

    chartsInstances.setor = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.valores,
                backgroundColor: [
                    'rgba(17, 207, 255, 0.8)',
                    'rgba(56, 139, 253, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderWidth: 2,
                borderColor: 'rgba(10, 14, 26, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: 'rgba(232, 244, 255, 0.9)',
                        font: { size: 11 },
                        padding: 12
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(168, 197, 224, 1)',
                    borderColor: 'rgba(17, 207, 255, 0.3)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percent = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

async function carregarGraficoParciais(params) {
    const response = await fetch(`/DashBoard/backendDash/analisePHP/grafico-parciais-completas.php?${params}`);
    const data = await response.json();

    const ctx = document.getElementById('chartOperacoes').getContext('2d');

    if (chartsInstances.operacoes) {
        chartsInstances.operacoes.destroy();
    }

    chartsInstances.operacoes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Análises Parciais',
                data: data.parciais,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1
            }, {
                label: 'Análises Completas',
                data: data.completas,
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(232, 244, 255, 0.9)',
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(168, 197, 224, 1)',
                    borderColor: 'rgba(17, 207, 255, 0.3)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: {
                        color: 'rgba(17, 207, 255, 0.05)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(17, 207, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

async function carregarGraficoTempo(params) {
    const response = await fetch(`/DashBoard/backendDash/analisePHP/grafico-tempo-operador.php?${params}`);
    const data = await response.json();

    const ctx = document.getElementById('chartTempo').getContext('2d');

    if (chartsInstances.tempo) {
        chartsInstances.tempo.destroy();
    }

    chartsInstances.tempo = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Tempo Médio (dias)',
                data: data.valores,
                backgroundColor: 'rgba(17, 207, 255, 0.7)',
                borderColor: 'rgba(17, 207, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: 'rgba(232, 244, 255, 0.9)',
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(168, 197, 224, 1)',
                    borderColor: 'rgba(17, 207, 255, 0.3)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return `Tempo: ${context.parsed.y.toFixed(1)} dias`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(17, 207, 255, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(17, 207, 255, 0.05)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

// ========================================
// 5. CARREGAR TABELA OPERACIONAL
// ========================================
let dadosTabela = [];
let paginaAtual = 1;
const itensPorPagina = 20;

async function carregarTabelaOperacional() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        data_inicial: filtros.inicio,
        data_final: filtros.fim
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/analisePHP/tabela-detalhada.php?${params}`);
        const data = await response.json();

        dadosTabela = data.data || [];

        renderizarCabecalhoTabela();
        renderizarTabela();

        // Configurar busca e ordenação
        document.getElementById('table-search').addEventListener('input', filtrarTabela);
        document.getElementById('table-sort').addEventListener('change', ordenarTabela);

    } catch (error) {
        console.error('Erro ao carregar tabela:', error);
    }
}

function renderizarCabecalhoTabela() {
    const thead = document.getElementById('table-header');
    thead.innerHTML = `
        <tr>
            <th>Data Início</th>
            <th>Nota Fiscal</th>
            <th>Cliente</th>
            <th>Qtd Total</th>
            <th>Qtd Analisada</th>
            <th>Backlog</th>
            <th>Operador</th>
            <th>Status</th>
            <th>Valor Orçado</th>
        </tr>
    `;
}

function renderizarTabela() {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';

    const inicio = (paginaAtual - 1) * itensPorPagina;
    const fim = inicio + itensPorPagina;
    const dadosPaginados = dadosTabela.slice(inicio, fim);

    dadosPaginados.forEach(item => {
        const tr = document.createElement('tr');

        const dataFormatada = new Date(item.data_inicio + 'T00:00:00').toLocaleDateString('pt-BR');
        
        const backlogClass = item.backlog > 0 ? 'text-warning' : '';
        
        const statusClass = {
            'parcial': 'warning',
            'completo': 'success',
            'pendente': 'info'
        }[item.status] || 'info';

        const statusTexto = {
            'parcial': 'Parcial',
            'completo': 'Completo',
            'pendente': 'Pendente'
        }[item.status] || item.status;

        const valorFormatado = item.valor_orcamento ? 
            'R$ ' + parseFloat(item.valor_orcamento).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : 
            '-';

        tr.innerHTML = `
            <td>${dataFormatada}</td>
            <td><strong>${item.nota_fiscal}</strong></td>
            <td>${item.razao_social}</td>
            <td><strong>${item.quantidade_total}</strong></td>
            <td><strong>${item.quantidade_analisada}</strong></td>
            <td class="${backlogClass}"><strong>${item.backlog}</strong></td>
            <td>${item.operador || 'N/A'}</td>
            <td><span class="status-badge ${statusClass}">${statusTexto}</span></td>
            <td>${valorFormatado}</td>
        `;

        tbody.appendChild(tr);
    });

    // Atualizar contador
    document.getElementById('record-count').textContent = `${dadosTabela.length} registros`;

    // Renderizar paginação
    renderizarPaginacao();
}

function renderizarPaginacao() {
    const totalPaginas = Math.ceil(dadosTabela.length / itensPorPagina);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';

    // Botão anterior
    const btnPrev = document.createElement('button');
    btnPrev.textContent = '‹ Anterior';
    btnPrev.disabled = paginaAtual === 1;
    btnPrev.onclick = () => {
        if (paginaAtual > 1) {
            paginaAtual--;
            renderizarTabela();
        }
    };
    pagination.appendChild(btnPrev);

    // Páginas
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaAtual - 1 && i <= paginaAtual + 1)) {
            const btnPage = document.createElement('button');
            btnPage.textContent = i;
            btnPage.className = i === paginaAtual ? 'active' : '';
            btnPage.onclick = () => {
                paginaAtual = i;
                renderizarTabela();
            };
            pagination.appendChild(btnPage);
        } else if (i === paginaAtual - 2 || i === paginaAtual + 2) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.style.padding = '8px';
            span.style.color = 'var(--text-muted)';
            pagination.appendChild(span);
        }
    }

    // Botão próximo
    const btnNext = document.createElement('button');
    btnNext.textContent = 'Próximo ›';
    btnNext.disabled = paginaAtual === totalPaginas;
    btnNext.onclick = () => {
        if (paginaAtual < totalPaginas) {
            paginaAtual++;
            renderizarTabela();
        }
    };
    pagination.appendChild(btnNext);
}

function filtrarTabela() {
    const termo = document.getElementById('table-search').value.toLowerCase();
    
    const dadosFiltrados = dadosTabela.filter(item => {
        return (
            item.nota_fiscal.toLowerCase().includes(termo) ||
            item.razao_social.toLowerCase().includes(termo) ||
            item.cnpj.toLowerCase().includes(termo) ||
            (item.operador && item.operador.toLowerCase().includes(termo))
        );
    });

    const original = dadosTabela;
    dadosTabela = dadosFiltrados;
    paginaAtual = 1;
    renderizarTabela();
    
    setTimeout(() => {
        if (document.getElementById('table-search').value === '') {
            dadosTabela = original;
        }
    }, 100);
}

function ordenarTabela() {
    const criterio = document.getElementById('table-sort').value;

    switch (criterio) {
        case 'data_desc':
            dadosTabela.sort((a, b) => new Date(b.data_inicio) - new Date(a.data_inicio));
            break;
        case 'data_asc':
            dadosTabela.sort((a, b) => new Date(a.data_inicio) - new Date(b.data_inicio));
            break;
        case 'quantidade_desc':
            dadosTabela.sort((a, b) => b.quantidade_total - a.quantidade_total);
            break;
        case 'quantidade_asc':
            dadosTabela.sort((a, b) => a.quantidade_total - b.quantidade_total);
            break;
    }

    paginaAtual = 1;
    renderizarTabela();
}
