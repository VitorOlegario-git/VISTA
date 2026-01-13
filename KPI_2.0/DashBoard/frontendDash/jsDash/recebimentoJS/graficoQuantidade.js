const CORES = {
    SEMANAL: { background: "rgba(52, 199, 89, 0.6)", border: "rgba(0, 122, 51, 1)" },
    MENSAL: { background: "rgba(0, 122, 255, 0.6)", border: "rgba(0, 64, 128, 1)" },
    TENDENCIA: { border: "rgba(255, 99, 132, 1)" },
    SEPARADOR_MES: { border: "rgba(100, 100, 100, 1)" },
    SEPARADOR_SEMANA: { border: "rgba(180, 180, 180, 0.5)" }
};


const FONTE_PADRAO = { size: 12, style: 'italic', weight: 'bold' };

let chartRecebimentosSemanal = null;
let chartRecebimentosMensal = null;
let exibirNomesDosMeses = true; // 🔁 Altere para false para ocultar os nomes dos meses

// Registro dos plugins
Chart.register(ChartDataLabels);
Chart.register(window['chartjs-plugin-annotation']);

/**
 * Calcula a linha de tendência usando regressão linear simples.
 * @param {number[]} yData - Array de valores Y.
 * @returns {{valores: number[], texto: string, valorFinal: number}} Objeto com valores da tendência, texto descritivo e valor final.
 */
function calcularTrendline(yData) {
    const n = yData.length;
    const xData = Array.from({ length: n }, (_, i) => i + 1);
    const sumX = xData.reduce((a, b) => a + b, 0);
    const sumY = yData.reduce((a, b) => a + b, 0);
    const sumXY = xData.reduce((sum, x, i) => sum + x * yData[i], 0);
    const sumXX = xData.reduce((sum, x) => sum + x * x, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    let tendenciaTexto = "";
    if (slope > 5) tendenciaTexto = "";
    else if (slope < -5) tendenciaTexto = "";

    return {
        valores: xData.map(x => slope * x + intercept),
        texto: tendenciaTexto,
        valorFinal: slope * (n - 1) + intercept
    };
}

/**
 * Carrega a quantidade recebida e renderiza os gráficos semanal e mensal.
 * @param {string} dataInicio - Data inicial no formato AAAA-MM-DD.
 * @param {string} dataFim - Data final no formato AAAA-MM-DD.
 */
async function carregarQuantidadeRecebidaEGraficos(dataInicio, dataFim) {
    if (!dataInicio || !dataFim) {
        console.error("❌ Datas de início e fim são obrigatórias");
        alert("Por favor, forneça datas de início e fim válidas.");
        return;
    }

    console.log("🔄 Enviando requisição de quantidade recebida:", { dataInicio, dataFim });

    try {
        const response = await fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/equip_recebidos.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}`
        });

        if (!response.ok) throw new Error(`Erro na requisição: ${response.status}`);
        const data = await response.json();

        const dadosQuantidade = document.getElementById("dadosQuantidade");
        if (dadosQuantidade) {
            dadosQuantidade.innerHTML = `<h3>Total Recebido: ${data.total_recebido}</h3>`;
        } else {
            console.warn("⚠️ Elemento 'dadosQuantidade' não encontrado");
        }

        if (!data.semanal || !data.mensal) {
            throw new Error("❌ Dados inválidos: 'semanal' ou 'mensal' não encontrados");
        }

        const semanas = data.semanal.map((_, index) => `Semana ${index + 1}`);
        const valoresSemanais = data.semanal.map(item => item.total_recebido);
        const mesPorSemana = data.semanal.map(item => {
            const data = new Date(item.inicio);
            return data.toLocaleString('pt-BR', { month: 'long' });
        });

        const posicaoCentralMeses = {};
        mesPorSemana.forEach((mes, index) => {
            if (!posicaoCentralMeses[mes]) posicaoCentralMeses[mes] = [];
            posicaoCentralMeses[mes].push(index);
        });

        const maxValorSemanais = Math.max(...valoresSemanais, 0);
        const anotacoesTextoMeses = exibirNomesDosMeses ? Object.entries(posicaoCentralMeses).reduce((acc, [mes, indices], i) => {
            const mediaIndex = indices.reduce((a, b) => a + b, 0) / indices.length;
            acc[`mes${i}`] = {
                type: 'label',
                xValue: mediaIndex,
                yValue: maxValorSemanais * 1.1,
                backgroundColor: 'transparent',
                content: mes.charAt(0).toUpperCase() + mes.slice(1),
                font: FONTE_PADRAO,
                color: 'black',
                xAdjust: 0,
                yAdjust: 0,
                position: 'center'
            };
            return acc;
        }, {}) : {};

        const linhasSeparadoras = [];
        let mesAnterior = mesPorSemana[0];
        for (let i = 1; i < mesPorSemana.length; i++) {
            if (mesPorSemana[i] !== mesAnterior) {
                linhasSeparadoras.push({
                    type: 'line',
                    scaleID: 'x',
                    value: i - 0.5,
                    borderColor: CORES.SEPARADOR_MES.border,
                    borderWidth: 2,
                    borderDash: [4, 2]
                });
                mesAnterior = mesPorSemana[i];
            }
        }

        const anotacoesCompletas = {
            ...anotacoesTextoMeses,
            ...Object.fromEntries(linhasSeparadoras.map((linha, i) => [`linha${i}`, linha]))
        };

        const valoresMensais = data.mensal.map(item => parseFloat(item.total_recebido) || 0);
        const meses = data.mensal.map(item => item.mes);
        const tendenciaMensal = calcularTrendline(valoresMensais);

        if (chartRecebimentosSemanal instanceof Chart) chartRecebimentosSemanal.destroy();
        if (chartRecebimentosMensal instanceof Chart) chartRecebimentosMensal.destroy();

        const ctxSemanal = document.getElementById("graficoRecebimentosSemanal")?.getContext("2d");
        if (ctxSemanal) {
            chartRecebimentosSemanal = new Chart(ctxSemanal, {
                type: "bar",
                data: {
                    labels: semanas,
                    datasets: [
                        
                        {
                            label: "Finalizados",
                            data: valoresSemanais,
                            backgroundColor: CORES.SEMANAL.background,
                            borderColor: CORES.SEMANAL.border,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 50, bottom: 10 } },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#000',
                            font: { weight: 'bold' },
                            formatter: Math.round
                        },
                        annotation: { annotations: anotacoesCompletas },
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 12, padding: 5 },
                            onClick: (e, legendItem, legend) => {
                                if (legendItem.text === "Nomes dos Meses") {
                                    exibirNomesDosMeses = !exibirNomesDosMeses;
                                    const novasAnotacoes = {
                                        ...Object.fromEntries(linhasSeparadoras.map((linha, i) => [`linha${i}`, linha])),
                                        ...(exibirNomesDosMeses ? anotacoesTextoMeses : {})
                                    };
                                    legend.chart.options.plugins.annotation.annotations = novasAnotacoes;
                                    legend.chart.update();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: '' },
                            ticks: { maxRotation: 45, autoSkip: true, font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: maxValorSemanais * 1.2,
                            title: { display: true, text: '' },
                            grace: 10
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } else {
            console.error("❌ Elemento 'graficoRecebimentosSemanal' não encontrado");
        }

        const ctxMensal = document.getElementById("graficoRecebimentosMensal")?.getContext("2d");
        if (ctxMensal) {
            chartRecebimentosMensal = new Chart(ctxMensal, {
                type: "bar",
                data: {
                    labels: meses,
                    datasets: [
                        {
                            label: "Recebimentos",
                            data: valoresMensais,
                            backgroundColor: CORES.MENSAL.background,
                            borderColor: CORES.MENSAL.border,
                            borderWidth: 2,
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                color: '#000',
                                font: { weight: 'bold' },
                                formatter: Math.round
                            }
                        },
                        {
                            label: "Tendência",
                            data: tendenciaMensal.valores,
                            type: 'line',
                            borderColor: CORES.TENDENCIA.border,
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                            datalabels: {
                                display: context => context.dataIndex === context.chart.data.labels.length - 1,
                                formatter: () => tendenciaMensal.texto,
                                align: 'top',
                                anchor: 'end',
                                color: 'black',
                                font: FONTE_PADRAO
                            }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 50 } },
                    plugins: {
                        datalabels: { display: context => context.datasetIndex === 0 },
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 12, padding: 5 }
                        }
                    },
                    scales: {
                        x: {
                            title: { display: true, text: '' },
                            ticks: { maxRotation: 45, autoSkip: true, font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: '' },
                            grace: 10
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        } else {
            console.error("❌ Elemento 'graficoRecebimentosMensal' não encontrado");
        }
    } catch (error) {
        console.error("❌ Erro ao carregar quantidade recebida e gráficos:", error);
        alert("Não foi possível carregar os dados. Tente novamente mais tarde.");
    }
}