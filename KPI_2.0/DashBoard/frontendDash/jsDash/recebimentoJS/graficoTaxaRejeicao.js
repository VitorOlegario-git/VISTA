// Gráfico: Taxa de Rejeição
function carregarGraficoTaxaRejeicao(dataInicio, dataFim, operador) {
    console.log('Carregando gráfico de taxa de rejeição...', { dataInicio, dataFim, operador });
    
    // TODO: Implementar chamada ao backend quando necessário
    // Por enquanto, apenas um placeholder para evitar erro 404
    
    const ctx = document.getElementById('chartTaxaRejeicao');
    if (!ctx) {
        console.warn('Canvas chartTaxaRejeicao não encontrado');
        return;
    }

    // Dados placeholder
    const dados = {
        labels: ['Sem Defeito', 'Rejeitados', 'Aprovados'],
        datasets: [{
            label: 'Quantidade',
            data: [120, 15, 265],
            backgroundColor: [
                'rgba(16, 185, 129, 0.7)',
                'rgba(239, 68, 68, 0.7)',
                'rgba(56, 139, 253, 0.7)'
            ],
            borderColor: [
                'rgba(16, 185, 129, 1)',
                'rgba(239, 68, 68, 1)',
                'rgba(56, 139, 253, 1)'
            ],
            borderWidth: 2
        }]
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: dados,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        color: '#e8f4ff',
                        padding: 15,
                        font: { size: 12 }
                    }
                },
                title: {
                    display: true,
                    text: 'Taxa de Rejeição',
                    color: '#e8f4ff',
                    font: { size: 16, weight: 'bold' }
                }
            }
        }
    });
}
