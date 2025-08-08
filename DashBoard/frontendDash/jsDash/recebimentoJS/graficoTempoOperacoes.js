let chartTempoOperacoes = null;

function carregarGraficoTempoOperacoes(dataInicio, dataFim) {
    console.log("Enviando requisição de Tempo Médio entre Operações:", { dataInicio, dataFim });

    fetch("/sistema/KPI_2.0/DashBoard/backendDash/recebimentoPHP/tempo_medio_operacoes.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
            data_inicial: dataInicio,
            data_final: dataFim
        }).toString()
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json(); // ✅ já retorna um objeto JS
    })
    .then(data => {
        console.log("Dados recebidos:", data);

        const container = document.getElementById("tempoOperacoes");

        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operacoes = data.dados.map(item => `${item.operacao_origem} → ${item.operacao_destino}`);
        const tempos = data.dados.map(item => item.tempo_medio);

        if (chartTempoOperacoes instanceof Chart) {
            chartTempoOperacoes.destroy();
        }

        container.innerHTML = '<canvas id="graficoTempoOperacoes"></canvas>';
        const ctx = document.getElementById("graficoTempoOperacoes").getContext("2d");

        chartTempoOperacoes = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operacoes.map(op => op.replace(" → ", "\n→ ")), // quebra linha
                datasets: [{
                    label: "Tempo médio entre operações",
                    data: tempos,
                    backgroundColor: "rgba(255, 206, 86, 0.6)",
                    borderColor: "rgba(255, 206, 86, 1)",
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
                        formatter: value => `${value}d`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Tempo médio: ${context.raw} dias`
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
                            text: 'Tempo médio (dias)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Operações'
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
        console.error("Erro ao carregar gráfico de tempo médio entre operações:", error);
        document.getElementById("tempoOperacoes").innerHTML = `
            <p style="color:red">Erro ao carregar os dados.</p>
        `;
    });
}
