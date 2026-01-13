let chartTopEmpresas = null;

function carregarGraficoEmpresas(dataInicio, dataFim) {
    console.log("Enviando requisição de Top Empresas:", { dataInicio, dataFim });

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/top_empresas.php", {
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

        // 🔧 Define altura e permite overflow para não cortar legenda/labels
        container.innerHTML = '<canvas id="graficoEmpresas" style="height:380px; display:block;"></canvas>';
        container.style.overflow = "visible";

        const ctx = document.getElementById("graficoEmpresas").getContext("2d");

        chartTopEmpresas = new Chart(ctx, {
            type: "bar",
            data: {
                labels: empresas,
                datasets: [{
                    label: "Top Empresas",
                    data: valores,
                    backgroundColor: "rgba(255, 99, 132, 0.6)",
                    borderColor: "rgba(255, 99, 132, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,

                // ✅ Reserva espaço extra no topo
                layout: {
                    padding: { top: 28, right: 10, bottom: 10, left: 10 }
                },

                plugins: {
                    legend: {
                        display: true, // troque para false se não quiser legenda
                        position: "bottom",
                        align: "center",
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                            font: { size: 12 }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: { weight: 'bold' },
                        formatter: value => `${value}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Total de peças: ${context.raw}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: false
                        }
                    },
                    x: {
                        title: {
                            display: false
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
