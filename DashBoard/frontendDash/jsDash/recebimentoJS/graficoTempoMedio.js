let chartTempoMedio = null;

function carregarGraficoTempoMedio(dataInicio, dataFim) {
    console.log("Enviando requisição de Tempo Médio:", { dataInicio, dataFim });

    const container = document.getElementById("tempoMedioAnalise");
    container.innerHTML = "<p>Carregando...</p>";

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/tempo_medio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ data_inicial: dataInicio, data_final: dataFim })
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const dias = data.dados.map(item => item.dia);
        const temposMedios = data.dados.map(item => item.tempo_medio);

        if (chartTempoMedio instanceof Chart) {
            chartTempoMedio.destroy();
        }

        container.innerHTML = '<canvas id="graficoTempoMedio"></canvas>';
        const ctx = document.getElementById("graficoTempoMedio").getContext("2d");

        chartTempoMedio = new Chart(ctx, {
            type: "line",
            data: {
                labels: dias,
                datasets: [{
                    label: "Tempo Médio para Envio à Análise (dias)",
                    data: temposMedios,
                    borderColor: "rgba(75, 192, 192, 1)",
                    backgroundColor: "rgba(75, 192, 192, 0.2)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7
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
                            text: 'Dias'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dia'
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar gráfico de tempo médio:", error);
        container.innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
