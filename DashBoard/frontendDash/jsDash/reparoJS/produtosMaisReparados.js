function carregarProdutosMaisReparados(dataInicio, dataFim) {
    console.log("🔍 Carregando Produtos mais Reparados por Remessa...", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/reparoPHP/produtosMaisReparados.php", {
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
            document.getElementById("graficoProduto").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const produtos = data.map(item => item.produto || "Desconhecido");
        const totais = data.map(item => item.total_reparos);

        const ctx = document.getElementById("graficoProduto").getContext("2d");

        if (window.produtosMaisReparadosChart instanceof Chart) {
            window.produtosMaisReparadosChart.destroy();
        }

        window.produtosMaisReparadosChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: produtos,
                datasets: [{
                    label: "Quantidade Reparada",
                    data: totais,
                    backgroundColor: "rgba(255, 206, 86, 0.6)",
                    borderColor: "rgba(255, 206, 86, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Produtos mais Reparados por Remessa"
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `${value}x`
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
                            text: "Quantidade de Reparos"
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
                            text: "Produto"
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
