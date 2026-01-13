function carregarReparosPorCliente(dataInicio, dataFim, operador = "") {
    console.log("📊 Carregando Distribuição de Reparos por Cliente...", { dataInicio, dataFim, operador });

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/reparoPHP/reparosPorCliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}&operador=${encodeURIComponent(operador)}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Dados recebidos:", data);

        if (!Array.isArray(data) || data.length === 0) {
            document.getElementById("graficoReparoCliente").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const clientes = data.map(item => item.razao_social || "Desconhecido");
        const totais = data.map(item => item.total_reparos);

        const ctx = document.getElementById("graficoReparoCliente").getContext("2d");

        if (window.reparosPorClienteChart instanceof Chart) {
            window.reparosPorClienteChart.destroy();
        }

        window.reparosPorClienteChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: clientes,
                datasets: [{
                    label: "",
                    data: totais,
                    backgroundColor: "rgba(255, 99, 132, 0.6)",
                    borderColor: "rgba(255, 99, 132, 1)",
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
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `${value}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.raw} equipamentos`
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: ""
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 60,
                            font: { size: 10 }
                        },
                        title: {
                            display: true,
                            text: "Cliente"
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
