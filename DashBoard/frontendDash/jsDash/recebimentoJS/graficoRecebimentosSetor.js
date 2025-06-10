let chartRecebimentosSetor = null;

function carregarGraficoSetor(dataInicio, dataFim) {
    console.log("Enviando requisição de Recebimentos por Setor:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/recebimentos_por_setor.php", {
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
            document.getElementById("recebimentosSetor").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const setores = data.dados.map(item => item.setor);
        const valores = data.dados.map(item => item.total_pecas);

        if (chartRecebimentosSetor instanceof Chart) {
            chartRecebimentosSetor.destroy();
        }

        const container = document.getElementById("recebimentosSetor");
        container.innerHTML = '<canvas id="graficoSetor"></canvas>';
        const ctx = document.getElementById("graficoSetor").getContext("2d");

        chartRecebimentosSetor = new Chart(ctx, {
            type: "bar",
            data: {
                labels: setores,
                datasets: [{
                    label: "Quantidade de Peças Recebidas por Setor",
                    data: valores,
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                    borderColor: "rgba(54, 162, 235, 1)",
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
                                return `Peças: ${tooltipItem.raw}`;
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
                            text: 'Setor'
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
        console.error("Erro ao carregar gráfico de setor:", error);
        document.getElementById("recebimentosSetor").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
