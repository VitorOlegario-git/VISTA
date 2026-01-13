// Gráfico: Recebimentos por Dia
function carregarGraficoRecebimentosDia(dataInicio, dataFim, operador) {
    console.log('Carregando gráfico de recebimentos por dia...', { dataInicio, dataFim, operador });
    
    // TODO: Implementar chamada ao backend quando necessário
    // Por enquanto, apenas um placeholder para evitar erro 404
    
    const ctx = document.getElementById('chartRecebimentosDia');
    if (!ctx) {
        console.warn('Canvas chartRecebimentosDia não encontrado');
        return;
    }

    // Dados placeholder - últimos 7 dias
    const hoje = new Date();
    const labels = [];
    const dados = [];
    
    for (let i = 6; i >= 0; i--) {
        const data = new Date(hoje);
        data.setDate(hoje.getDate() - i);
        labels.push(data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        dados.push(Math.floor(Math.random() * 50) + 30); // Dados aleatórios entre 30-80
    }

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Recebimentos',
                data: dados,
                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                borderColor: 'rgba(56, 189, 248, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(56, 189, 248, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Recebimentos por Dia',
                    color: '#e8f4ff',
                    font: { size: 16, weight: 'bold' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#a8c5e0' },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                x: {
                    ticks: { color: '#a8c5e0' },
                    grid: { display: false }
                }
            }
        }
    });
}
