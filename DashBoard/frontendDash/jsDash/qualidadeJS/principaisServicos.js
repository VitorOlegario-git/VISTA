function carregarPrincipaisServicos(dataInicio, dataFim) {
    console.log("📦 Buscando principais serviços...", { dataInicio, dataFim });

    fetch("/sistema/KPI_2.0/DashBoard/backendDash/qualidadePHP/principaisServicos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Serviços recebidos:", data);

        const canvas = document.getElementById("graficoPrincipaisServicos");
        const ctx = canvas.getContext("2d");

        if (window.chartServicosReparo instanceof Chart) {
            window.chartServicosReparo.destroy();
        }

        // Obter todos os produtos únicos
        const todosProdutosSet = new Set();
        for (const servico in data) {
            for (const produto in data[servico]) {
                todosProdutosSet.add(produto);
            }
        }
        const produtos = Array.from(todosProdutosSet).sort();

        // Criar datasets por serviço
        const datasets = Object.keys(data).map((servico, index) => {
            const cor = gerarCor(index); // cor única por serviço
            const valores = produtos.map(produto => data[servico][produto] || 0);
            return {
                label: servico,
                data: valores,
                backgroundColor: cor.background,
                borderColor: cor.border,
                borderWidth: 1
            };
        });

        window.chartServicosReparo = new Chart(ctx, {
            type: "bar",
            data: {
                labels: produtos,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: ""
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${context.raw}x`
                        }
                    },
                    legend: {
                        position: "top"
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        font: { size: 10, weight: 'bold' },
                        formatter: val => val > 0 ? `${val}x` : '',
                        color: '#000'
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: {
                            autoSkip: false,
                            maxRotation: 90,
                            minRotation: 45,
                            font: { size: 10 }
                        },
                        title: {
                            display: true,
                            text: "Produtos"
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Quantidade"
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    })
    .catch(err => {
        console.error("❌ Erro ao carregar serviços:", err);
        const div = document.getElementById("graficoprincipaisservicos");
        if (div) div.innerHTML = "<p>Erro ao carregar os serviços.</p>";
    });
}

// 🔹 Função para gerar cores únicas para cada serviço
function gerarCor(index) {
    const cores = [
        "rgba(75, 192, 192, 0.6)",
        "rgba(255, 99, 132, 0.6)",
        "rgba(54, 162, 235, 0.6)",
        "rgba(255, 206, 86, 0.6)",
        "rgba(153, 102, 255, 0.6)",
        "rgba(255, 159, 64, 0.6)",
        "rgba(0, 200, 83, 0.6)",
        "rgba(255, 0, 255, 0.6)"
    ];
    const bordas = cores.map(c => c.replace('0.6', '1'));
    const i = index % cores.length;
    return { background: cores[i], border: bordas[i] };
}
