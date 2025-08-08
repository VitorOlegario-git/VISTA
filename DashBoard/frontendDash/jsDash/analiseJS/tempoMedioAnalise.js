let chartTempoMedioAnalise = null;

function carregarTempoMedioAnalise(dataInicio, dataFim, operador = "") {
    const params = new URLSearchParams();
    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);
    if (operador) params.append("operador", operador);

    fetch("../backendDash/analisePHP/tempo_medio_analise.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("graficoTempoMedioAnalise").parentElement.innerHTML = "<p>Nenhum dado disponível para o período e operador selecionado.</p>";
            return;
        }

        const labels = data.map(item => item.operador);
        const valores = data.map(item => item.tempo_medio);

        if (chartTempoMedioAnalise instanceof Chart) {
            chartTempoMedioAnalise.destroy();
        }

        const ctx = document.getElementById("graficoTempoMedioAnalise").getContext("2d");

        chartTempoMedioAnalise = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "",
                    data: valores,
                    backgroundColor: "rgba(75, 192, 192, 0.5)",
                    borderColor: "rgba(75, 192, 192, 1)",
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
                        display: false,
                        
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: ""
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
                            text: ""
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("Erro ao carregar tempo médio de análise:", err);
    });
}
