/**
 * ÁREA DETALHADA - RECEBIMENTO
 * JavaScript específico para visualização operacional detalhada
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
        const [remessas, equipamentos, tempo, taxaEnvio, backlog] = await Promise.all([
            fetch(`/DashBoard/backendDash/recebimentoPHP/kpi-remessas-recebidas.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/recebimentoPHP/kpi-equipamentos-recebidos.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/recebimentoPHP/kpi-tempo-ate-analise.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/recebimentoPHP/kpi-taxa-envio-analise.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php?${params}`).then(r => r.json())
        ]);

        const kpisContainer = document.getElementById('kpis-container');
        kpisContainer.innerHTML = '';

        // KPI 1: Remessas Recebidas
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-truck',
            label: 'Remessas Recebidas',
            valor: remessas.data.valor,
            unidade: 'remessas',
            variacao: remessas.data.referencia.variacao,
            estado: remessas.data.referencia.estado
        }));

        // KPI 2: Equipamentos Recebidos
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-box-open',
            label: 'Equipamentos Recebidos',
            valor: equipamentos.data.valor,
            unidade: 'equipamentos',
            variacao: equipamentos.data.referencia.variacao,
            estado: equipamentos.data.referencia.estado
        }));

        // KPI 3: Tempo Médio até Análise
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-clock',
            label: 'Tempo Médio até Análise',
            valor: tempo.data.valor,
            unidade: 'dias',
            variacao: tempo.data.referencia.variacao,
            estado: tempo.data.referencia.estado,
            inverterCores: true
        }));

        // KPI 4: Taxa de Envio para Análise
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-percentage',
            label: '% Enviadas para Análise',
            valor: taxaEnvio.data.valor,
            unidade: '%',
            variacao: taxaEnvio.data.referencia.variacao,
            estado: taxaEnvio.data.referencia.estado
        }));

        // KPI 5: Backlog Atual
        if (backlog.data) {
            kpisContainer.appendChild(criarCardKPI({
                icone: 'fa-hourglass-half',
                label: 'Backlog Atual',
                valor: backlog.data.valor,
                unidade: 'equipamentos',
                variacao: backlog.data.referencia.variacao,
                estado: backlog.data.referencia.estado,
                inverterCores: true
            }));
        }

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
    }[config.estado] || 'var(--accent-blue)';

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

    card.innerHTML = `
        <div class="kpi-header">
            <div class="kpi-icon" style="color: ${iconeCor}; border: 1px solid ${iconeCor};">
                <i class="fas ${config.icone}"></i>
            </div>
        </div>
        <div class="kpi-label">${config.label}</div>
        <div class="kpi-value">
            ${config.valor.toLocaleString('pt-BR')}
            <span class="kpi-unit">${config.unidade}</span>
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
        const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/insights-recebimento.php?${params}`);
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
                'eficiencia': 'tachometer-alt',
                'crescimento': 'chart-line',
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
        // Gráfico A: Evolução Temporal (Volume Diário)
        await carregarGraficoEvolucao(params);

        // Gráfico B1: Distribuição por Setor
        await carregarGraficoSetor(params);

        // Gráfico B2: Operações (Origem → Destino)
        await carregarGraficoOperacoes(params);

        // Gráfico C: Tempo Médio por Etapa
        await carregarGraficoTempo(params);

    } catch (error) {
        console.error('Erro ao carregar gráficos:', error);
    }
}

async function carregarGraficoEvolucao(params) {
    const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/grafico-volume-diario.php?${params}`);
    const data = await response.json();

    const ctx = document.getElementById('chartEvolucao').getContext('2d');

    if (chartsInstances.evolucao) {
        chartsInstances.evolucao.destroy();
    }

    chartsInstances.evolucao = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Remessas Recebidas',
                data: data.remessas,
                borderColor: 'rgba(56, 139, 253, 1)',
                backgroundColor: 'rgba(56, 139, 253, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }, {
                label: 'Equipamentos Recebidos',
                data: data.equipamentos,
                borderColor: 'rgba(17, 207, 255, 1)',
                backgroundColor: 'rgba(17, 207, 255, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
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
                    borderColor: 'rgba(56, 139, 253, 0.3)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(56, 139, 253, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(56, 139, 253, 0.05)'
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

async function carregarGraficoSetor(params) {
    const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/grafico-por-setor.php?${params}`);
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
                    'rgba(56, 139, 253, 0.8)',
                    'rgba(17, 207, 255, 0.8)',
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
                    borderColor: 'rgba(56, 139, 253, 0.3)',
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

async function carregarGraficoOperacoes(params) {
    const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/grafico-operacoes.php?${params}`);
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
                label: 'Quantidade',
                data: data.valores,
                backgroundColor: 'rgba(56, 139, 253, 0.7)',
                borderColor: 'rgba(56, 139, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(168, 197, 224, 1)',
                    borderColor: 'rgba(56, 139, 253, 0.3)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(56, 139, 253, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                y: {
                    grid: {
                        color: 'rgba(56, 139, 253, 0.05)'
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
    const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/grafico-tempo-medio.php?${params}`);
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
                backgroundColor: 'rgba(139, 92, 246, 0.7)',
                borderColor: 'rgba(139, 92, 246, 1)',
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
                    borderColor: 'rgba(56, 139, 253, 0.3)',
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
                        color: 'rgba(56, 139, 253, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(168, 197, 224, 1)',
                        font: { size: 11 }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(56, 139, 253, 0.05)'
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
        const response = await fetch(`/DashBoard/backendDash/recebimentoPHP/tabela-detalhada.php?${params}`);
        const data = await response.json();

        dadosTabela = data.data || [];

        renderizarCabecalhoTabela();
        renderizarTabela();

        // Configurar busca
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
            <th>Data</th>
            <th>Nota Fiscal</th>
            <th>Cliente</th>
            <th>Quantidade</th>
            <th>Operação Origem</th>
            <th>Operação Destino</th>
            <th>Operador</th>
            <th>Status</th>
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

        const dataFormatada = new Date(item.data + 'T00:00:00').toLocaleDateString('pt-BR');
        
        const statusClass = {
            'enviado_analise': 'info',
            'em_analise': 'warning',
            'analise_concluida': 'success',
            'aguardando_pg': 'warning'
        }[item.operacao_destino] || 'info';

        const statusTexto = {
            'enviado_analise': 'Enviado Análise',
            'em_analise': 'Em Análise',
            'analise_concluida': 'Concluída',
            'aguardando_pg': 'Aguardando PG'
        }[item.operacao_destino] || item.operacao_destino;

        tr.innerHTML = `
            <td>${dataFormatada}</td>
            <td><strong>${item.nota_fiscal}</strong></td>
            <td>${item.razao_social}</td>
            <td><strong>${item.quantidade}</strong></td>
            <td>${formatarOperacao(item.operacao_origem)}</td>
            <td>${formatarOperacao(item.operacao_destino)}</td>
            <td>${item.operador || 'N/A'}</td>
            <td><span class="status-badge ${statusClass}">${statusTexto}</span></td>
        `;

        tbody.appendChild(tr);
    });

    // Atualizar contador
    document.getElementById('record-count').textContent = `${dadosTabela.length} registros`;

    // Renderizar paginação
    renderizarPaginacao();
}

function formatarOperacao(op) {
    const map = {
        'entrada_recebimento': 'Entrada Recebimento',
        'enviado_analise': 'Enviado Análise',
        'em_analise': 'Em Análise',
        'aguardando_pg': 'Aguardando PG'
    };
    return map[op] || op;
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

    // Atualizar dadosTabela temporariamente
    const original = dadosTabela;
    dadosTabela = dadosFiltrados;
    paginaAtual = 1;
    renderizarTabela();
    
    // Restaurar após renderização para ordenação funcionar
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
            dadosTabela.sort((a, b) => new Date(b.data) - new Date(a.data));
            break;
        case 'data_asc':
            dadosTabela.sort((a, b) => new Date(a.data) - new Date(b.data));
            break;
        case 'quantidade_desc':
            dadosTabela.sort((a, b) => b.quantidade - a.quantidade);
            break;
        case 'quantidade_asc':
            dadosTabela.sort((a, b) => a.quantidade - b.quantidade);
            break;
    }

    paginaAtual = 1;
    renderizarTabela();
}
