let chartDiaSemana = null;

function carregarGraficoDiaSemana(dataInicio, dataFim) {
    console.log("Enviando requisição de Recebimentos por Dia:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/recebimentos_por_dia.php", {
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
            document.getElementById("recebimentosDia").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const dias = data.dados.map(item => item.dia_semana);
        const valores = data.dados.map(item => item.total_recebimentos);

        if (chartDiaSemana instanceof Chart) chartDiaSemana.destroy();

        const container = document.getElementById("recebimentosDia");
        container.innerHTML = '<canvas id="graficoDiaSemana"></canvas>';
        const ctx = document.getElementById("graficoDiaSemana").getContext("2d");

        chartDiaSemana = new Chart(ctx, {
            type: "bar",
            data: {
                labels: dias,
                datasets: [{
                    label: "Recebimentos por Dia da Semana",
                    data: valores,
                    backgroundColor: "rgba(255, 206, 86, 0.6)",
                    borderColor: "rgba(255, 206, 86, 1)",
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
                                return `Recebimentos: ${tooltipItem.raw}`;
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
                            text: 'Dia da Semana'
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar gráfico de recebimentos por dia:", error);
        document.getElementById("recebimentosDia").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
