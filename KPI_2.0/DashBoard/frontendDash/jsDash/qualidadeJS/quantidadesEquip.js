function carregarquantidadeEquip(dataInicio, dataFim, operador = "") {
    console.log("📊 Carregando dados por modelo...", { dataInicio, dataFim, operador });

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/qualidadePHP/qtdEquipamentos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}&operador=${encodeURIComponent(operador)}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Dados por modelo recebidos:", data);

        const canvas = document.getElementById("graficoQuantidadeEquipamentos");
        if (!canvas) {
            console.error("❌ Canvas 'graficoQuantidadeEquipamentos' não encontrado.");
            return;
        }

        const modelosSet = new Set();
        const adicionarModelos = (lista) => lista.forEach(item => modelosSet.add(item.modelo));
        adicionarModelos(data.modelos_recebidos || []);
        adicionarModelos(data.modelos_analisados || []);
        adicionarModelos(data.modelos_reparados || []);

        const modelos = Array.from(modelosSet);

        const is4G = modelo => /^ST\d{4,}/.test(modelo);
        const modelos2G = modelos.filter(m => !is4G(m)).sort();
        const modelos4G = modelos.filter(m => is4G(m)).sort();
        const modelosOrdenados = [...modelos2G, ...modelos4G];
        const divisaoIndex = modelos2G.length;

        const gerarMapa = (lista) => {
            const mapa = {};
            lista.forEach(({ modelo, quantidade }) => {
                mapa[modelo] = quantidade;
            });
            return mapa;
        };

        const mapaRecebidos  = gerarMapa(data.modelos_recebidos || []);
        const mapaAnalisados = gerarMapa(data.modelos_analisados || []);
        const mapaReparados  = gerarMapa(data.modelos_reparados || []);

        const dadosRecebidos  = modelosOrdenados.map(m => mapaRecebidos[m]  || 0);
        const dadosAnalisados = modelosOrdenados.map(m => mapaAnalisados[m] || 0);
        const dadosReparados  = modelosOrdenados.map(m => mapaReparados[m]  || 0);

        const valorMaximo = Math.max(...dadosRecebidos, ...dadosAnalisados, ...dadosReparados) + 15;

        const ctx = canvas.getContext("2d");
        if (window.chartQuantidadeEquipamentos instanceof Chart) {
            window.chartQuantidadeEquipamentos.destroy();
        }

        window.chartQuantidadeEquipamentos = new Chart(ctx, {
            type: "bar",
            data: {
                labels: modelosOrdenados,
                datasets: [
                    {
                        label: "Recebidos",
                        data: dadosRecebidos,
                        backgroundColor: "rgba(54, 162, 235, 0.6)",
                        borderColor: "rgba(54, 162, 235, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Analisados",
                        data: dadosAnalisados,
                        backgroundColor: "rgba(255, 206, 86, 0.6)",
                        borderColor: "rgba(255, 206, 86, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Reparados",
                        data: dadosReparados,
                        backgroundColor: "rgba(75, 192, 192, 0.6)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 1
                    }
                ]
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
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#000',
                        font: {
                            weight: 'bold',
                            size: 9
                        },
                        formatter: value => `${value}x`
                    },
                    annotation: {
                        annotations: {
                            linhaDivisao: {
                                type: "line",
                                borderColor: "black",
                                borderWidth: 2,
                                scaleID: "x",
                                value: divisaoIndex - 0.5
                            },
                            texto2G: {
                                type: "label",
                                drawTime: "afterDraw",
                                display: true,
                                content: ["2G"],
                                xValue: (divisaoIndex - 1) / 2,
                                yScaleID: "y",
                                yValue: valorMaximo,
                                font: {
                                    size: 16,
                                    weight: "bold"
                                },
                                color: "#000"
                            },
                            texto4G: {
                                type: "label",
                                drawTime: "afterDraw",
                                display: true,
                                content: ["4G"],
                                xValue: divisaoIndex + (modelos4G.length - 1) / 2,
                                yScaleID: "y",
                                yValue: valorMaximo,
                                font: {
                                    size: 16,
                                    weight: "bold"
                                },
                                color: "#000"
                            }
                        }
                    }
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
                        title: {
                            display: true,
                            text: "Modelo"
                        },
                        ticks: {
                            autoSkip: false,
                            maxRotation: 90,
                            minRotation: 90,
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels, Chart.registry.getPlugin('annotation')]
        });
    })
    .catch(error => {
        console.error("❌ Erro ao carregar dados da qualidade:", error);
        const div = document.getElementById("graficoQuantidadeEquipamentos");
        if (div) {
            div.outerHTML = "<p>Erro ao carregar os dados.</p>";
        }
    });
}
