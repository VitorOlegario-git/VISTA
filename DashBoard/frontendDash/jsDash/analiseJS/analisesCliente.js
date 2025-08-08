let chartAnalisesCliente = null;

function carregarAnalisesPorCliente(dataInicio, dataFim, operador = "") {
    const params = new URLSearchParams();
    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);
    if (operador) params.append("operador", operador); // Novo filtro

    fetch("../backendDash/analisePHP/analises_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("graficoAnalisesCliente").parentElement.innerHTML = "<p>Nenhum dado disponível para o período e operador selecionados.</p>";
            return;
        }

        const labels = data.map(item => item.razao_social);
        const valores = data.map(item => item.total);

        if (chartAnalisesCliente instanceof Chart) {
            chartAnalisesCliente.destroy();
        }

        const ctx = document.getElementById("graficoAnalisesCliente").getContext("2d");
        chartAnalisesCliente = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "",
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
                        display: false,
                        
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
                            text: ""
                        }
                    },
                    y: {
                        beginAtZero: true,
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
        console.error("Erro ao carregar análises por cliente:", err);
    });
}
