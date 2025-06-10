let chartRecebimentosOperador = null;

function carregarGraficoOperador(dataInicio, dataFim) {
    console.log("Enviando requisição de Recebimentos por Operador:", { dataInicio, dataFim });

    fetch("//localhost/DashBoard/backendDash/recebimentoPHP/recebimentos_por_operador.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        if (!data.dados || data.dados.length === 0) {
            document.getElementById("recebimentosOperador").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operadores = data.dados.map(item => item.operador);
        const valores = data.dados.map(item => item.total_equipamentos);

        if (chartRecebimentosOperador instanceof Chart) {
            chartRecebimentosOperador.destroy();
        }

        const container = document.getElementById("recebimentosOperador");
        container.innerHTML = '<canvas id="graficoOperador"></canvas>';
        const ctx = document.getElementById("graficoOperador").getContext("2d");

        chartRecebimentosOperador = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operadores,
                datasets: [{
                    label: "Quantidade de Equipamentos por Operador",
                    data: valores,
                    backgroundColor: "rgba(255, 159, 64, 0.6)",
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
                            weight: 'bold'
                        },
                        formatter: Math.round
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return `Equipamentos: ${tooltipItem.raw}`;
                            }
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
                            text: 'Quantidade'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Operador'
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar gráfico de operadores:", error);
        document.getElementById("recebimentosOperador").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
