/**
 * ÁREA DETALHADA - REPARO
 * JavaScript específico para visualização operacional da área de Reparo
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
        const [backlog, reparados, taxaConversao, tempoMedio, valorOrcado] = await Promise.all([
            fetch(`/DashBoard/backendDash/reparoPHP/kpi-equipamentos-em-reparo.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/reparoPHP/kpi-equipamentos-reparados.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/reparoPHP/kpi-taxa-conversao-reparo.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/reparoPHP/kpi-tempo-medio-reparo.php?${params}`).then(r => r.json()),
            fetch(`/DashBoard/backendDash/reparoPHP/kpi-valor-orcado-reparo.php?${params}`).then(r => r.json())
        ]);

        const kpisContainer = document.getElementById('kpis-container');
        kpisContainer.innerHTML = '';

        // KPI 1: Equipamentos em Reparo (Backlog)
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-wrench',
            label: 'Equipamentos em Reparo',
            valor: backlog.data.valor,
            unidade: 'equipamentos',
            variacao: backlog.data.referencia.variacao,
            estado: backlog.data.referencia.estado,
            inverterCores: true
        }));

        // KPI 2: Equipamentos Reparados
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-check-double',
            label: 'Equipamentos Reparados',
            valor: reparados.data.valor,
            unidade: 'equipamentos',
            variacao: reparados.data.referencia.variacao,
            estado: reparados.data.referencia.estado,
            extras: reparados.data.extras
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

        // KPI 4: Tempo Médio de Reparo
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-hourglass-half',
            label: 'Tempo Médio de Reparo',
            valor: tempoMedio.data.valor,
            unidade: 'dias',
            variacao: tempoMedio.data.referencia.variacao,
            estado: tempoMedio.data.referencia.estado,
            inverterCores: true
        }));

        // KPI 5: Valor Orçado
        kpisContainer.appendChild(criarCardKPI({
            icone: 'fa-dollar-sign',
            label: 'Valor Orçado em Reparo',
            valor: valorOrcado.data.valor,
            unidade: 'R$',
            variacao: valorOrcado.data.referencia.variacao,
            estado: valorOrcado.data.referencia.estado,
            extras: valorOrcado.data.extras
        }));

    } catch (error) {
        console.error('Erro ao carregar KPIs de Reparo:', error);
        mostrarErroKPIs();
    }
}

// ========================================
// 3. CRIAR CARD KPI
// ========================================
function criarCardKPI(config) {
    const card = document.createElement('div');
    card.className = 'kpi-card';
    
    // Border color baseado no estado
    const borderColors = {
        success: '#10b981',
        warning: '#f59e0b',
        critical: '#ef4444',
        neutral: '#3b82f6'
    };
    
    const borderColor = borderColors[config.estado] || borderColors.neutral;
    card.style.borderLeft = `4px solid ${borderColor}`;
    
    // Ícone
    const icone = document.createElement('div');
    icone.className = 'kpi-icon';
    icone.innerHTML = `<i class="fas ${config.icone}"></i>`;
    
    // Conteúdo
    const content = document.createElement('div');
    content.className = 'kpi-content';
    
    // Label
    const label = document.createElement('div');
    label.className = 'kpi-label';
    label.textContent = config.label;
    
    // Valor
    const valor = document.createElement('div');
    valor.className = 'kpi-value';
    
    if (config.unidade === 'R$') {
        const valorNumerico = parseFloat(config.valor);
        valor.innerHTML = `<span class="kpi-currency">R$</span> ${valorNumerico.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    } else {
        valor.textContent = `${config.valor} ${config.unidade}`;
    }
    
    // Variação
    const variacao = document.createElement('div');
    variacao.className = 'kpi-comparison';
    
    const variacaoNum = parseFloat(config.variacao);
    let iconeVariacao = 'fa-minus';
    let corVariacao = '#64748b';
    
    if (variacaoNum > 0) {
        iconeVariacao = 'fa-arrow-up';
        corVariacao = config.inverterCores ? '#ef4444' : '#10b981';
    } else if (variacaoNum < 0) {
        iconeVariacao = 'fa-arrow-down';
        corVariacao = config.inverterCores ? '#10b981' : '#ef4444';
    }
    
    variacao.innerHTML = `
        <span class="comparison-badge" style="color: ${corVariacao};">
            <i class="fas ${iconeVariacao}"></i> ${Math.abs(variacaoNum).toFixed(1)}%
        </span>
        <span class="comparison-text">vs. período anterior</span>
    `;
    
    // Extras (se houver)
    if (config.extras) {
        const extras = document.createElement('div');
        extras.className = 'kpi-extras';
        
        if (config.extras.media_diaria !== undefined) {
            extras.innerHTML += `<span><i class="fas fa-calendar-day"></i> ${config.extras.media_diaria}/dia</span>`;
        }
        if (config.extras.remessas !== undefined) {
            extras.innerHTML += `<span><i class="fas fa-boxes"></i> ${config.extras.remessas} remessas</span>`;
        }
        if (config.extras.valor_medio !== undefined) {
            extras.innerHTML += `<span><i class="fas fa-calculator"></i> R$ ${parseFloat(config.extras.valor_medio).toLocaleString('pt-BR', {minimumFractionDigits: 2})}/equip</span>`;
        }
        if (config.extras.num_orcamentos !== undefined) {
            extras.innerHTML += `<span><i class="fas fa-file-invoice-dollar"></i> ${config.extras.num_orcamentos} orçamentos</span>`;
        }
        
        content.appendChild(extras);
    }
    
    content.appendChild(label);
    content.appendChild(valor);
    content.appendChild(variacao);
    
    card.appendChild(icone);
    card.appendChild(content);
    
    return card;
}

// ========================================
// 4. CARREGAR INSIGHTS
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
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/insights-reparo.php?${params}`);
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            const insightsSection = document.getElementById('insights-section');
            const insightsGrid = document.getElementById('insights-grid');
            
            insightsGrid.innerHTML = '';
            
            result.data.slice(0, 3).forEach(insight => {
                const card = criarCardInsight(insight);
                insightsGrid.appendChild(card);
            });
            
            insightsSection.style.display = 'block';
        }
    } catch (error) {
        console.error('Erro ao carregar insights:', error);
    }
}

function criarCardInsight(insight) {
    const card = document.createElement('div');
    card.className = `insight-card insight-${insight.tipo}`;
    
    const icon = document.createElement('div');
    icon.className = 'insight-icon';
    
    const iconMap = {
        critical: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-circle',
        success: 'fa-check-circle',
        info: 'fa-info-circle'
    };
    
    icon.innerHTML = `<i class="fas ${iconMap[insight.tipo] || iconMap.info}"></i>`;
    
    const content = document.createElement('div');
    content.className = 'insight-content';
    
    const header = document.createElement('div');
    header.className = 'insight-header';
    header.innerHTML = `
        <span class="insight-category">${insight.categoria}</span>
        <h4>${insight.titulo}</h4>
    `;
    
    const message = document.createElement('p');
    message.className = 'insight-message';
    message.textContent = insight.mensagem;
    
    content.appendChild(header);
    content.appendChild(message);
    
    if (insight.causa) {
        const causa = document.createElement('div');
        causa.className = 'insight-detail';
        causa.innerHTML = `<strong>Causa:</strong> ${insight.causa}`;
        content.appendChild(causa);
    }
    
    if (insight.acao) {
        const acao = document.createElement('div');
        acao.className = 'insight-action';
        acao.innerHTML = `<i class="fas fa-lightbulb"></i> ${insight.acao}`;
        content.appendChild(acao);
    }
    
    card.appendChild(icon);
    card.appendChild(content);
    
    return card;
}

// ========================================
// 5. CARREGAR GRÁFICOS
// ========================================
async function carregarGraficos() {
    const filtros = obterFiltros();
    
    await Promise.all([
        carregarGraficoEvolucao(filtros),
        carregarGraficoClientes(filtros),
        carregarGraficoTempoOperador(filtros),
        carregarGraficoServicos(filtros)
    ]);
}

async function carregarGraficoEvolucao(filtros) {
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/grafico-evolucao-reparos.php?${params}`);
        const result = await response.json();

        if (result.success && result.data) {
            const ctx = document.getElementById('chart-evolucao').getContext('2d');
            
            if (window.chartEvolucao) {
                window.chartEvolucao.destroy();
            }
            
            window.chartEvolucao = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: result.data.labels,
                    datasets: [{
                        label: 'Equipamentos Reparados',
                        data: result.data.reparados,
                        backgroundColor: 'rgba(251, 146, 60, 0.2)',
                        borderColor: '#f59e0b',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Evolução de Reparos no Tempo',
                            color: '#e2e8f0'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        },
                        x: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de evolução:', error);
    }
}

async function carregarGraficoClientes(filtros) {
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/grafico-por-cliente.php?${params}`);
        const result = await response.json();

        if (result.success && result.data) {
            const ctx = document.getElementById('chart-clientes').getContext('2d');
            
            if (window.chartClientes) {
                window.chartClientes.destroy();
            }
            
            window.chartClientes = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: result.data.labels,
                    datasets: [{
                        data: result.data.valores,
                        backgroundColor: [
                            '#3b82f6', '#8b5cf6', '#f59e0b', '#10b981',
                            '#ef4444', '#11cfff', '#ec4899', '#14b8a6'
                        ],
                        borderWidth: 2,
                        borderColor: '#0a0e1a'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: { color: '#e2e8f0' }
                        },
                        title: {
                            display: true,
                            text: 'Reparos por Cliente',
                            color: '#e2e8f0'
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de clientes:', error);
    }
}

async function carregarGraficoTempoOperador(filtros) {
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);

    try {
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/grafico-tempo-operador.php?${params}`);
        const result = await response.json();

        if (result.success && result.data) {
            const ctx = document.getElementById('chart-tempo-operador').getContext('2d');
            
            if (window.chartTempoOperador) {
                window.chartTempoOperador.destroy();
            }
            
            window.chartTempoOperador = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: result.data.labels,
                    datasets: [{
                        label: 'Tempo Médio (dias)',
                        data: result.data.valores,
                        backgroundColor: 'rgba(139, 92, 246, 0.2)',
                        borderColor: '#8b5cf6',
                        borderWidth: 2
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Tempo Médio por Operador',
                            color: '#e2e8f0'
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        },
                        y: {
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de tempo por operador:', error);
    }
}

async function carregarGraficoServicos(filtros) {
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/grafico-servicos.php?${params}`);
        const result = await response.json();

        if (result.success && result.data) {
            const ctx = document.getElementById('chart-servicos').getContext('2d');
            
            if (window.chartServicos) {
                window.chartServicos.destroy();
            }
            
            window.chartServicos = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: result.data.labels,
                    datasets: [{
                        label: 'Quantidade de Serviços',
                        data: result.data.valores,
                        backgroundColor: 'rgba(16, 185, 129, 0.2)',
                        borderColor: '#10b981',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Principais Serviços / Laudos',
                            color: '#e2e8f0'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#94a3b8' },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        },
                        x: {
                            ticks: { 
                                color: '#94a3b8',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: { color: 'rgba(56, 139, 253, 0.1)' }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Erro ao carregar gráfico de serviços:', error);
    }
}

// ========================================
// 6. CARREGAR TABELA OPERACIONAL
// ========================================
async function carregarTabelaOperacional() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        inicio: filtros.inicio.split('-').reverse().join('/'),
        fim: filtros.fim.split('-').reverse().join('/')
    });

    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);

    try {
        const response = await fetch(`/DashBoard/backendDash/reparoPHP/tabela-detalhada.php?${params}`);
        const result = await response.json();

        if (result.success && result.data) {
            renderizarTabela(result.data);
        }
    } catch (error) {
        console.error('Erro ao carregar tabela:', error);
    }
}

function renderizarTabela(dados) {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';

    if (!dados || dados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 40px; color: #64748b;">Nenhum dado encontrado para o período selecionado</td></tr>';
        return;
    }

    dados.forEach(row => {
        const tr = document.createElement('tr');
        
        // Destaque visual para backlog alto
        if (row.backlog > 5) {
            tr.classList.add('row-warning');
        }
        
        // Cor do status
        const statusClass = {
            'completo': 'status-success',
            'parcial': 'status-warning',
            'pendente': 'status-critical'
        }[row.status] || 'status-neutral';
        
        tr.innerHTML = `
            <td>${row.data_registro}</td>
            <td>${row.nota_fiscal}</td>
            <td>${row.cliente}</td>
            <td>${row.quantidade_total}</td>
            <td>${row.quantidade_reparada}</td>
            <td class="${row.backlog > 0 ? 'text-warning' : ''}">${row.backlog}</td>
            <td>${row.operador}</td>
            <td><span class="badge ${statusClass}">${row.status}</span></td>
            <td>R$ ${row.valor_orcamento}</td>
            <td>${row.servico}</td>
        `;
        
        tbody.appendChild(tr);
    });

    // Implementar busca e paginação (simplificado)
    implementarBuscaTabela(dados);
}

function implementarBuscaTabela(dadosOriginais) {
    const searchInput = document.getElementById('table-search');
    
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const termo = e.target.value.toLowerCase();
            const dadosFiltrados = dadosOriginais.filter(row => 
                row.nota_fiscal.toLowerCase().includes(termo) ||
                row.cliente.toLowerCase().includes(termo) ||
                row.operador.toLowerCase().includes(termo) ||
                row.servico.toLowerCase().includes(termo)
            );
            renderizarTabela(dadosFiltrados);
        });
    }
}

// ========================================
// HELPERS
// ========================================
function mostrarErroKPIs() {
    const kpisContainer = document.getElementById('kpis-container');
    kpisContainer.innerHTML = '<p style="color: #ef4444; text-align: center; padding: 40px;">Erro ao carregar KPIs. Por favor, recarregue a página.</p>';
}

function voltarDashboard() {
    const filtros = obterFiltros();
    const params = new URLSearchParams({
        inicio: filtros.inicio,
        fim: filtros.fim
    });
    
    if (filtros.setor) params.append('setor', filtros.setor);
    if (filtros.operador) params.append('operador', filtros.operador);
    
    window.location.href = `DashboardExecutivo.php?${params.toString()}`;
}
