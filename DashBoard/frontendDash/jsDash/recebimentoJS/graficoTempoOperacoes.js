let chartTempoOperacoes = null;

function carregarGraficoTempoOperacoes(dataInicio, dataFim) {
    console.log("Enviando requisição de Tempo Médio entre Operações:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/tempo_medio_operacoes.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        const container = document.getElementById("tempoOperacoes");

        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operacoes = data.dados.map(item => `${item.operacao_origem} → ${item.operacao_destino}`);
        const tempos = data.dados.map(item => item.tempo_medio);

        if (chartTempoOperacoes instanceof Chart) {
            chartTempoOperacoes.destroy();
        }

        container.innerHTML = '<canvas id="graficoTempoOperacoes"></canvas>';
        const ctx = document.getElementById("graficoTempoOperacoes").getContext("2d");

        chartTempoOperacoes = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operacoes,
                datasets: [{
                    label: "Tempo Médio Entre Operações (dias)",
                    data: tempos,
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
                        formatter: value => `${value}d`
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `Tempo médio: ${context.raw} dias`;
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
                            text: 'Dias'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Operações'
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
        console.error("Erro ao carregar gráfico de tempo médio entre operações:", error);
        document.getElementById("tempoOperacoes").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
