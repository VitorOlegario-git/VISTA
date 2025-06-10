function carregarCustoMedioPorProduto(dataInicio, dataFim) {
    fetch("/localhost/DashBoard/backendDash/reparoPHP/getCustoMedioPorProduto.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        if (!data || data.length === 0) {
            document.getElementById("graficoCustoMedioCanvas").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const produtos = data.map(item => item.produto || "Desconhecido");
        const valores = data.map(item => parseFloat(item.custo_medio));

        const ctx = document.getElementById("graficoCustoMedioCanvas").getContext("2d");
        if (window.custoMedioChart) window.custoMedioChart.destroy();

        window.custoMedioChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: produtos,
                datasets: [{
                    label: "Custo Médio Unitário (R$)",
                    data: valores,
                    backgroundColor: "rgba(153, 102, 255, 0.6)",
                    borderColor: "rgba(153, 102, 255, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Custo Médio por Produto"
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `R$ ${value.toFixed(2)}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `R$ ${parseFloat(context.raw).toFixed(2)}`
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "R$"
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: "Produto"
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar dados:", error);
    });
}
