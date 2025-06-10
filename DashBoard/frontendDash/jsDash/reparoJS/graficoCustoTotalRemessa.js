function carregarCustoTotalPorProduto(dataInicio, dataFim) {
    console.log("Enviando requisição com:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/reparoPHP/getCustoTotalPorRemessa.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(res => {
        console.log("Resposta recebida:", res);
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("Dados recebidos do PHP:", data);

        if (!Array.isArray(data) || data.length === 0) {
            console.warn("Nenhum dado retornado para Custo por Produto.");
            document.getElementById("graficoCustoTotalCanvas").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const produtos = data.map(item => item.produto || "Desconhecido");
        const valores = data.map(item => parseFloat(item.valor_total));

        const ctx = document.getElementById("graficoCustoTotalCanvas").getContext("2d");
        if (window.custoProdutoChart) window.custoProdutoChart.destroy();

        window.custoProdutoChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: produtos,
                datasets: [{
                    label: "Custo Estimado por Produto (R$)",
                    data: valores,
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
                        text: "Custo Estimado por Produto"
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
                    legend: {
                        display: false
                    }
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
