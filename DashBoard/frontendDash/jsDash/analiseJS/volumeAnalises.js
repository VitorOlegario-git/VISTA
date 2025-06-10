let chartVolumeAnalisesOperador = null;

function carregarVolumeAnalises(dataInicio, dataFim) {
    const params = new URLSearchParams();
    if (dataInicio) params.append("data_inicial", dataInicio);
    if (dataFim) params.append("data_final", dataFim);

    fetch("../backendDash/analisePHP/volume_analises.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        const total = data.total_geral ?? 0;

        if (!data.por_operador || data.por_operador.length === 0) {
            document.getElementById("volumeAnalisesTexto").innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const labels = data.por_operador.map(item => item.operador);
        const valores = data.por_operador.map(item => item.total);

        // Texto com total geral e por operador
        let texto = `✅ Total de Análises Realizadas: <strong>${total}</strong><br><br>`;
        data.por_operador.forEach(op => {
            texto += `👤 <strong>${op.operador}</strong>: ${op.total}<br>`;
        });
        document.getElementById("volumeAnalisesTexto").innerHTML = texto;

        // Destroi gráfico anterior se existir
        if (chartVolumeAnalisesOperador instanceof Chart) {
            chartVolumeAnalisesOperador.destroy();
        }

        const ctx = document.getElementById("graficoVolumeAnalisesOperador").getContext("2d");

        chartVolumeAnalisesOperador = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Análises por Operador",
                    data: valores,
                    backgroundColor: "rgba(255, 99, 132, 0.5)",
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
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => value
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Total: ${context.raw}`
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: "Operador"
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Quantidade"
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("Erro ao carregar volume de análises:", err);
        document.getElementById("volumeAnalisesTexto").innerText = "Erro ao carregar dados.";
    });
}
