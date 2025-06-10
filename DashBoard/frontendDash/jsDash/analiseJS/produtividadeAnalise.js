let chartSemanal = null;
let chartMensal = null;

function carregarProdutividadeAnalise(dataInicio, dataFim) {
    console.log("Enviando requisição com:", { dataInicio, dataFim });

    fetch("../backendDash/analisePHP/produtividade_analise.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        if (!data.semanal || !data.mensal) {
            console.error("Dados inválidos: 'semanal' ou 'mensal' não encontrados no JSON");
            return;
        }

        const semanalLabels = data.semanal.map(item => `${item.operador} - ${item.periodo}`);
        const semanalData = data.semanal.map(item => item.quantidade);

        const mensalLabels = data.mensal.map(item => `${item.operador} - ${item.periodo}`);
        const mensalData = data.mensal.map(item => item.quantidade);

        if (chartSemanal instanceof Chart) chartSemanal.destroy();
        if (chartMensal instanceof Chart) chartMensal.destroy();

        // Gráfico semanal
        const ctxSemanal = document.getElementById("graficoProdutividadeSemanal").getContext("2d");
        chartSemanal = new Chart(ctxSemanal, {
            type: "bar",
            data: {
                labels: semanalLabels,
                datasets: [{
                    label: "Quantidade finalizada por Operador (Semanal)",
                    data: semanalData,
                    backgroundColor: "rgba(75, 192, 192, 0.5)",
                    borderColor: "rgba(75, 192, 192, 1)",
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
                            label: context => `Finalizado: ${context.raw}`
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
                            text: 'Operador - Período'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantidade'
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Gráfico mensal
        const ctxMensal = document.getElementById("graficoProdutividadeMensal").getContext("2d");
        chartMensal = new Chart(ctxMensal, {
            type: "bar",
            data: {
                labels: mensalLabels,
                datasets: [{
                    label: "Quantidade finalizada por Operador (Mensal)",
                    data: mensalData,
                    backgroundColor: "rgba(153, 102, 255, 0.5)",
                    borderColor: "rgba(153, 102, 255, 1)",
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
                            label: context => `Finalizado: ${context.raw}`
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
                            text: 'Operador - Período'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantidade'
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("Erro ao carregar produtividade:", err);
    });
}
