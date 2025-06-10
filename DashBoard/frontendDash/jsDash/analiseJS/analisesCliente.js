let chartAnalisesCliente = null;

function carregarAnalisesPorCliente(dataInicio, dataFim) {
    const params = new URLSearchParams();
    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);

    fetch("../backendDash/analisePHP/analises_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("graficoAnalisesCliente").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const labels = data.map(item => item.razao_social);
        const valores = data.map(item => item.total);

        // Destroi gráfico anterior, se houver
        if (chartAnalisesCliente instanceof Chart) {
            chartAnalisesCliente.destroy();
        }

        const ctx = document.getElementById("graficoAnalisesCliente").getContext("2d");
        chartAnalisesCliente = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Análises por Cliente",
                    data: valores,
                    backgroundColor: "rgba(153, 102, 255, 0.5)",
                    borderColor: "rgba(153, 102, 255, 1)",
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
                        formatter: value => `${value}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Total: ${context.raw}`
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 75,
                            minRotation: 45,
                            autoSkip: false,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: "Clientes"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Quantidade de Análises"
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("Erro ao carregar análises por cliente:", err);
    });
}
