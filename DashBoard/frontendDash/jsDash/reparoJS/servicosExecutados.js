function carregarServicosExecutados(dataInicio, dataFim) {
    console.log("🔍 Carregando Principais Serviços Executados...", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/reparoPHP/servicosExecutados.php", {
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
            document.getElementById("graficoServicos").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const servicos = data.map(item => item.servico || "Desconhecido");
        const totais = data.map(item => item.total_servicos);

        const ctx = document.getElementById("graficoServicos").getContext("2d");

        if (window.servicosExecutadosChart instanceof Chart) {
            window.servicosExecutadosChart.destroy();
        }

        window.servicosExecutadosChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: servicos,
                datasets: [{
                    label: "Quantidade",
                    data: totais,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
                    borderColor: "rgba(75, 192, 192, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Principais Serviços Executados"
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `${value}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.raw} vezes`
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Quantidade"
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            font: { size: 10 }
                        },
                        title: {
                            display: true,
                            text: "Serviço"
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
