let chartTopEmpresas = null;

function carregarGraficoEmpresas(dataInicio, dataFim) {
    console.log("Enviando requisição de Top Empresas:", { dataInicio, dataFim });

    fetch("/sistema/KPI_2.0/DashBoard/backendDash/recebimentoPHP/top_empresas.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        const container = document.getElementById("topEmpresas");

        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        function decodeHTML(text) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
       }
    
        const empresas = data.dados.map(item => decodeHTML(item.empresa));

        const valores = data.dados.map(item => item.total_pecas);

        if (chartTopEmpresas instanceof Chart) {
            chartTopEmpresas.destroy();
        }

        container.innerHTML = '<canvas id="graficoEmpresas"></canvas>';
        const ctx = document.getElementById("graficoEmpresas").getContext("2d");

        chartTopEmpresas = new Chart(ctx, {
            type: "bar",
            data: {
                labels: empresas,
                datasets: [{
                    label: "",
                    data: valores,
                    backgroundColor: "rgba(255, 99, 132, 0.6)",
                    borderColor: "rgba(255, 99, 132, 1)",
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
                        formatter: value => `${value}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Total de peças: ${context.raw}`
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
        console.error("Erro ao carregar gráfico de Top Empresas:", error);
        document.getElementById("topEmpresas").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
