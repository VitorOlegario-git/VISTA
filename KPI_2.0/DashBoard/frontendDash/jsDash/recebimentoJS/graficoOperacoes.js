let chartOperacoes = null;

function carregarGraficoOperacoes(dataInicio, dataFim) {
    console.log("Enviando requisição de Operações Origem-Destino:", { dataInicio, dataFim });

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/operacoes_origem_destino.php", {
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
            console.warn("Nenhum dado de operações encontrado!");
            document.getElementById("operacoesOrigemDestino").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operacoes = data.dados.map(item => `${item.operacao_origem} → ${item.operacao_destino}`);
        const valores = data.dados.map(item => item.total_operacoes);

        if (chartOperacoes instanceof Chart) {
            chartOperacoes.destroy();
        }

        const container = document.getElementById("operacoesOrigemDestino");
        container.innerHTML = '<canvas id="graficoOperacoes"></canvas>';
        const ctx = document.getElementById("graficoOperacoes").getContext("2d");

        chartOperacoes = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operacoes,
                datasets: [{
                    label: "",
                    data: valores,
                    backgroundColor: "rgba(153, 102, 255, 0.6)",
                    borderColor: "rgba(153, 102, 255, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Barras horizontais
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'right',
                        color: '#000',
                        font: {
                            weight: 'bold'
                        },
                        formatter: Math.round
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return `Operações: ${tooltipItem.raw}`;
                            }
                        }
                    },
                    legend: {
                       display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: ''
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: ''
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar gráfico de operações:", error);
        document.getElementById("operacoesOrigemDestino").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
