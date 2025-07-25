let chartTempoOrcamento = null;

function carregarTempoOrcamento(dataInicio, dataFim) {
    const params = new URLSearchParams();

    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);

    fetch("../backendDash/analisePHP/tempo_orcamento.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("graficoTempoOrcamento").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const labels = data.map(item => item.operador);
        const valores = data.map(item => item.tempo_medio);

        if (chartTempoOrcamento instanceof Chart) {
            chartTempoOrcamento.destroy();
        }

        const ctx = document.getElementById("graficoTempoOrcamento").getContext("2d");

        chartTempoOrcamento = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Tempo Médio para Orçamento (dias)",
                    data: valores,
                    backgroundColor: "rgba(255, 159, 64, 0.5)",
                    borderColor: "rgba(255, 159, 64, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `${value}d`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Tempo médio: ${context.raw} dias`
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Dias"
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: "Operador"
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("Erro ao carregar tempo de orçamento:", err);
    });
}
