function carregarProdutosMaiorCusto(dataInicio, dataFim) {
    fetch("/localhost/DashBoard/backendDash/reparoPHP/getProdutosMaiorCustoAcumulado.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
            console.warn("Nenhum dado retornado para Produtos com Maior Custo.");
            document.getElementById("graficoMaiorCustoProduto").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const produtos = data.map(item => item.produto || "Desconhecido");
        const valores = data.map(item => parseFloat(item.custo_total));

        const ctx = document.getElementById("graficoMaiorCustoProduto").getContext("2d");
        if (window.maiorCustoChart) window.maiorCustoChart.destroy();

        window.maiorCustoChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: produtos,
                datasets: [{
                    label: "Custo Acumulado (R$)",
                    data: valores,
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
                        text: "Produtos com Maior Custo Acumulado"
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 10
                        },
                        formatter: value => `R$ ${value.toFixed(2)}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `R$ ${context.raw.toFixed(2)}`
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "R$"
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            font: {
                                size: 10
                            }
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
        console.error("Erro ao carregar dados:", error);
    });
}
