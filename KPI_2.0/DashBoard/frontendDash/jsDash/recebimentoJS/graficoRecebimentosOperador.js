// Gráfico: Recebimentos por Operador
function carregarGraficoRecebimentosOperador(dataInicio, dataFim, operador) {
    console.log('Carregando gráfico de recebimentos por operador...', { dataInicio, dataFim, operador });
    
    // TODO: Implementar chamada ao backend quando necessário
    // Por enquanto, apenas um placeholder para evitar erro 404
    
    const ctx = document.getElementById('chartRecebimentosOperador');
    if (!ctx) {
        console.warn('Canvas chartRecebimentosOperador não encontrado');
        return;
    }

    // Dados placeholder
    const dados = {
        labels: ['Operador 1', 'Operador 2', 'Operador 3', 'Operador 4', 'Operador 5'],
        datasets: [{
            label: 'Recebimentos',
            data: [45, 38, 52, 41, 47],
            backgroundColor: 'rgba(56, 139, 253, 0.7)',
            borderColor: 'rgba(56, 139, 253, 1)',
            borderWidth: 2
        }]
    };

    new Chart(ctx, {
        type: 'bar',
        data: dados,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Recebimentos por Operador',
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
