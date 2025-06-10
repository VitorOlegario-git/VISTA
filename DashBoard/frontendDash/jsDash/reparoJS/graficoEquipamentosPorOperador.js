function carregarEquipamentosPorOperador(dataInicio, dataFim) {
    console.log("Enviando requisição com:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/reparoPHP/getEquipamentosReparadosPorTecnico.php", {
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
            console.warn("Nenhum dado retornado para Equipamentos por Técnico.");
            document.getElementById("graficoQuantidadeOperador").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const operadores = data.map(item => item.operador);
        const valores = data.map(item => item.total_reparado);

        const ctx = document.getElementById("graficoQuantidadeOperador").getContext("2d");
        if (window.reparoTecnicoChart) window.reparoTecnicoChart.destroy();

        window.reparoTecnicoChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: operadores,
                datasets: [{
                    label: "Equipamentos Reparados",
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
                        text: "Equipamentos Reparados por Técnico"
                    },
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
                            label: context => {
                                const valor = context.raw;
                                return valor === 0 ? "Sem dados disponíveis" : `Total: ${valor}`;
                            }
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
                            maxRotation: 60,
                            font: {
                                size: 10
                            }
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
        console.error("Erro ao carregar dados:", error);
    });
}
