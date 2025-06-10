let chartRecebimentosSemanal = null;
let chartRecebimentosMensal = null;

function carregarQuantidadeRecebidaEGraficos(dataInicio, dataFim) {
    console.log("Enviando requisição de quantidade recebida:", { dataInicio, dataFim });

    fetch("/localhost/DashBoard/backendDash/recebimentoPHP/equip_recebidos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${dataInicio}&data_final=${dataFim}`
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        const dadosQuantidade = document.getElementById("dadosQuantidade");
        if (dadosQuantidade) {
            dadosQuantidade.innerHTML = `<h3>Total Recebido: ${data.total_recebido}</h3>`;
        }

        if (!data.semanal || !data.mensal) {
            console.error("Dados inválidos: 'semanal' ou 'mensal' não encontrados");
            return;
        }

        const semanas = data.semanal.map(item => {
            const inicio = new Date(item.inicio);
            const fim = new Date(item.fim);
        
            const options = { day: '2-digit', month: 'short' }; // Ex: 01 Jan
            const inicioFormatado = inicio.toLocaleDateString('pt-BR', options);
            const fimFormatado = fim.toLocaleDateString('pt-BR', options);
        
            const ano = inicio.getFullYear();
            return `${inicioFormatado} - ${fimFormatado} ${ano}`;
        });
        
        const valoresSemanais = data.semanal.map(item => item.total_recebido);

        const meses = data.mensal.map(item => item.mes);
        const valoresMensais = data.mensal.map(item => item.total_recebido);

        if (chartRecebimentosSemanal instanceof Chart) chartRecebimentosSemanal.destroy();
        if (chartRecebimentosMensal instanceof Chart) chartRecebimentosMensal.destroy();

        const ctxSemanal = document.getElementById("graficoRecebimentosSemanal").getContext("2d");
        chartRecebimentosSemanal = new Chart(ctxSemanal, {
            type: "bar",
            data: {
                labels: semanas,
                datasets: [{
                    label: "Recebimentos Semanais",
                    data: valoresSemanais,
                    backgroundColor: "rgba(75, 192, 192, 0.6)",
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
                            weight: 'bold'
                        },
                        formatter: Math.round
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Semanas'
                        },
                        ticks: {
                            maxRotation: 45,
                            autoSkip: false
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

        const ctxMensal = document.getElementById("graficoRecebimentosMensal").getContext("2d");
        chartRecebimentosMensal = new Chart(ctxMensal, {
            type: "bar",
            data: {
                labels: meses,
                datasets: [{
                    label: "Recebimentos Mensais",
                    data: valoresMensais,
                    backgroundColor: "rgba(0, 0, 0, 0.2)",
                    borderColor: "rgba(0, 0, 0, 0.8)",
                    borderWidth: 2
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
                        formatter: Math.round
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Meses'
                        },
                        ticks: {
                            maxRotation: 45,
                            autoSkip: false
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
    .catch(error => {
        console.error("Erro ao carregar quantidade recebida e gráficos:", error);
    });
}
