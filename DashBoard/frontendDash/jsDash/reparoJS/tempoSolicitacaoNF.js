function carregarTempoSolicitacaoNF(dataInicio, dataFim) {
    console.log("⏱️ Carregando Tempo Médio para Solicitação de NF...", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/reparoPHP/getTempoSolicitacaoNf.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Dados recebidos:", data);

        if (!Array.isArray(data) || data.length === 0) {
            document.getElementById("graficoTempoNf").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operadores = data.map(item => item.operador || "Desconhecido");
        const tempos = data.map(item => item.tempo_medio);

        const ctx = document.getElementById("graficoTempoNf").getContext("2d");

        if (window.graficoTempoNfChart instanceof Chart) {
            window.graficoTempoNfChart.destroy();
        }

        window.graficoTempoNfChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operadores,
                datasets: [{
                    label: "Tempo Médio (dias)",
                    data: tempos,
                    backgroundColor: "rgba(255, 159, 64, 0.6)",
                    borderColor: "rgba(255, 159, 64, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Tempo para Solicitação de NF"
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
                        title: {
                            display: true,
                            text: "Técnico"
                        }
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
