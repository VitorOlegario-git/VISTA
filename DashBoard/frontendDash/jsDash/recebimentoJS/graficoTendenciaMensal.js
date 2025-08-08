let chartTendenciaMensal = null;

function carregarGraficoTendenciaMensal(dataInicio, dataFim) {
    console.log("Enviando requisição de Tendência Mensal:", { dataInicio, dataFim });

    fetch("/sistema/KPI_2.0/DashBoard/backendDash/recebimentoPHP/tendencia_mensal.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        const container = document.getElementById("tendenciaMensal");

        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const meses = data.dados.map(item => item.mes);
        const valores = data.dados.map(item => item.total_recebimentos);

        if (chartTendenciaMensal instanceof Chart) {
            chartTendenciaMensal.destroy();
        }

        container.innerHTML = '<canvas id="graficoTendenciaMensal"></canvas>';
        const ctx = document.getElementById("graficoTendenciaMensal").getContext("2d");

        chartTendenciaMensal = new Chart(ctx, {
            type: "line",
            data: {
                labels: meses,
                datasets: [{
                    label: "",
                    data: valores,
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
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: ''
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: ''
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
        console.error("Erro ao carregar gráfico de tendência mensal:", error);
        document.getElementById("tendenciaMensal").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
