function carregarTicketMedio(dataInicio = "", dataFim = "") {
    const formData = new URLSearchParams();

    if (dataInicio && dataFim) {
        formData.append("data_inicial", dataInicio);
        formData.append("data_final", dataFim);
    }

    fetch("../backendDash/analisePHP/ticket_medio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (!data.ticket_medio || data.ticket_medio.length === 0) {
            document.getElementById("graficoTicketMedio").parentElement.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const labels = data.ticket_medio.map(item => `${item.operador} (${item.cnpj})`);
        const valores = data.ticket_medio.map(item => item.ticket_medio);

        const ctx = document.getElementById("graficoTicketMedio").getContext("2d");

        if (window.ticketMedioChart instanceof Chart) {
            window.ticketMedioChart.destroy();
        }

        window.ticketMedioChart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [{
                    label: "Ticket Médio (R$)",
                    data: valores,
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
                        formatter: value => `R$ ${parseFloat(value).toFixed(2)}`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Ticket Médio: R$ ${parseFloat(context.raw).toFixed(2)}`
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'R$'
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 75,
                            font: {
                                size: 10
                            }
                        },
                        title: {
                            display: true,
                            text: 'Operador (CNPJ)'
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(error => {
        console.error("Erro ao carregar Ticket Médio:", error);
    });
}
