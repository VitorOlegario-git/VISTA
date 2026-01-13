function carregarParcialCompleta(dataInicio, dataFim, operador = "") {
    const params = new URLSearchParams();
    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);
    if (operador) params.append("operador", operador); // Novo filtro

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/analisePHP/parcial_completa.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        const total = data.total ?? 0;
        const parciais = data.parciais ?? 0;
        const completas = total - parciais;
        const percentual = total > 0 ? ((parciais / total) * 100).toFixed(2) : 0;

        document.getElementById("graficoParcialCompletaContainer").style.display = "block";

        if (window.graficoParcialCompleta instanceof Chart) {
            window.graficoParcialCompleta.destroy();
        }

        const ctx = document.getElementById("graficoParcialCompleta").getContext("2d");

        window.graficoParcialCompleta = new Chart(ctx, {
            type: "pie",
            data: {
                labels: ["Parciais", "Completas"],
                datasets: [{
                    label: "Total de Análises",
                    data: [parciais, completas],
                    backgroundColor: ["#f39c12", "#2ecc71"],
                    borderColor: ["#e67e22", "#27ae60"],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `📊 ${percentual}% das análises realizadas são parciais`,
                        padding: { top: 10, bottom: 20 },
                        font: {
                            size: 16
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        formatter: function(value, context) {
                            const perc = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${value} (${perc}%)`;
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const perc = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                return `${label}: ${value} (${perc}%)`;
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
    .catch(err => {
        console.error("Erro ao carregar análise parcial vs. completa:", err);
    });
}
