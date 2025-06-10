let chartTaxaRejeicao = null;

function carregarGraficoRejeicao(dataInicio, dataFim) {
    console.log("Enviando requisição de Taxa de Rejeição:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/taxa_rejeicao.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        if (!data || data.total_recebimentos === 0) {
            document.getElementById("taxaRejeicao").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const valores = [data.total_reenvios, data.total_recebimentos - data.total_reenvios];
        const labels = ["Reenvios", "Aprovados"];
        const total = data.total_recebimentos;

        if (chartTaxaRejeicao instanceof Chart) {
            chartTaxaRejeicao.destroy();
        }

        const container = document.getElementById("taxaRejeicao");
        container.innerHTML = '<canvas id="graficoRejeicao"></canvas>';
        const ctx = document.getElementById("graficoRejeicao").getContext("2d");

        chartTaxaRejeicao = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: labels,
                datasets: [{
                    label: "Taxa de Rejeição",
                    data: valores,
                    backgroundColor: [
                        "rgba(255, 99, 132, 0.6)", // Reenvios
                        "rgba(54, 162, 235, 0.6)"  // Aprovados
                    ],
                    borderColor: [
                        "rgba(255, 99, 132, 1)", 
                        "rgba(54, 162, 235, 1)"
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: function(value, context) {
                            const percentual = ((value / total) * 100).toFixed(1);
                            return `${percentual}%`;
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const valor = context.raw;
                                const percentual = ((valor / total) * 100).toFixed(2);
                                return `${context.label}: ${valor} (${percentual}%)`;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar gráfico de taxa de rejeição:", error);
        document.getElementById("taxaRejeicao").innerHTML = "<p>Erro ao carregar os dados.</p>";
    });
}
