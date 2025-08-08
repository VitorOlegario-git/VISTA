function carregarTempoReparoOperador(dataInicio, dataFim, operador = "") {
    console.log("🔧 Carregando Tempo Médio de Reparo por Operador...", { dataInicio, dataFim, operador });

    const params = new URLSearchParams();
    params.append("data_inicial", dataInicio);
    params.append("data_final", dataFim);
    if (operador) params.append("operador", operador);

    fetch("/sistema/KPI_2.0/DashBoard/backendDash/reparoPHP/tempoReparoOperador.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString()
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Dados recebidos:", data);

        if (!Array.isArray(data) || data.length === 0) {
            document.getElementById("graficoReparoOperador").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operadores = data.map(item => item.operador || "Desconhecido");
        const tempos = data.map(item => item.tempo_medio);

        const ctx = document.getElementById("graficoReparoOperador").getContext("2d");

        if (window.graficoTempoReparoOperadorChart instanceof Chart) {
            window.graficoTempoReparoOperadorChart.destroy();
        }

        window.graficoTempoReparoOperadorChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operadores,
                datasets: [{
                    label: "Tempo Médio (dias)",
                    data: tempos,
                    backgroundColor: "rgba(54, 162, 235, 0.6)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: ""
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: { weight: 'bold', size: 10 },
                        formatter: value => `${value}d`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.raw} dias`
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: "Dias" }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            font: { size: 10 }
                        },
                        title: { display: true, text: "Técnico" }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("❌ Erro ao carregar dados:", error);
    });
}
