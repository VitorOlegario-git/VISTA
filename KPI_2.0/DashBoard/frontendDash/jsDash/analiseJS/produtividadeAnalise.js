// 🎯 Variáveis globais para os gráficos de produtividade da análise
let graficoProdutividadeSemanal = null;
let graficoProdutividadeMensal = null;
let exibirNomesDosMesesAnalise = true;

// 🎨 Paleta de cores unificada
const CORES_ANALISE = {
    SEMANAL: { background: "rgba(52, 199, 89, 0.6)", border: "rgba(0, 122, 51, 1)" },
    MENSAL: { background: "rgba(0, 122, 255, 0.6)", border: "rgba(0, 64, 128, 1)" },
    TENDENCIA: { border: "rgba(255, 99, 132, 1)" },
    SEPARADOR_MES: { border: "rgba(100, 100, 100, 1)" },
    SEPARADOR_SEMANA: { border: "rgba(180, 180, 180, 0.5)" }
};

// 🖋️ Fonte padrão
const FONTE_PADRAO_ANALISE = { size: 12, style: 'italic', weight: 'bold' };

// 📈 Função para calcular a linha de tendência
function calcularTrendlineAnalise(yData) {
    const n = yData.length;
    const xData = Array.from({ length: n }, (_, i) => i + 1);
    const sumX = xData.reduce((a, b) => a + b, 0);
    const sumY = yData.reduce((a, b) => a + b, 0);
    const sumXY = xData.reduce((sum, x, i) => sum + x * yData[i], 0);
    const sumXX = xData.reduce((sum, x) => sum + x * x, 0);

    const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
    const intercept = (sumY - slope * sumX) / n;

    let textoTendencia = "";
    if (slope > 5) textoTendencia = "";
    else if (slope < -5) textoTendencia = "";

    return {
        valores: xData.map(x => slope * x + intercept),
        texto: textoTendencia,
        valorFinal: slope * (n - 1) + intercept
    };
}
/**
 * Carrega a quantidade recebida e renderiza os gráficos semanal e mensal.
 * @param {string} dataInicio - Data inicial no formato AAAA-MM-DD.
 * @param {string} dataFim - Data final no formato AAAA-MM-DD.
 */
// 📦 Carrega os dados de produtividade do setor de análise e renderiza os gráficos
async function carregarProdutividadeAnalise(dataInicio, dataFim, operador = "") {
    if (!dataInicio || !dataFim) {
        console.error("❌ Datas de início e fim são obrigatórias");
        alert("Por favor, forneça datas de início e fim válidas.");
        return;
    }

    try {
        const resposta = await fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/analisePHP/produtividade_analise.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}&operador=${encodeURIComponent(operador)}`
        });

        if (!resposta.ok) throw new Error(`Erro ao buscar dados: ${resposta.status}`);
        const dados = await resposta.json();

        if (!dados.semanal || !dados.mensal) throw new Error("Dados incompletos: faltam campos 'semanal' ou 'mensal'.");

        const etiquetasSemanal = dados.semanal.map(item => `${item.operador} - ${formatarSemana(item.periodo)}`);
        const valoresSemanal = dados.semanal.map(item => item.quantidade);

        const etiquetasMensal = dados.mensal.map(item => `${item.operador} - ${item.periodo}`);
        const valoresMensal = dados.mensal.map(item => item.quantidade);
        const tendenciaMensal = calcularTrendlineAnalise(valoresMensal);

        if (graficoProdutividadeSemanal instanceof Chart) graficoProdutividadeSemanal.destroy();
        if (graficoProdutividadeMensal instanceof Chart) graficoProdutividadeMensal.destroy();

        const agrupamentoMeses = {};
        etiquetasSemanal.forEach((etiqueta, indice) => {
            const mesEncontrado = etiqueta.match(/de ([a-zç]+)/i)?.[1] || 'Indefinido';
            if (!agrupamentoMeses[mesEncontrado]) agrupamentoMeses[mesEncontrado] = [];
            agrupamentoMeses[mesEncontrado].push(indice);
        });

        const anotacoesTextoMeses = exibirNomesDosMesesAnalise ? Object.entries(agrupamentoMeses).reduce((acc, [mes, indices], i) => {
            const media = indices.reduce((a, b) => a + b, 0) / indices.length;
            acc[`mes${i}`] = {
                type: 'label',
                xValue: media,
                yValue: Math.max(...valoresSemanal) * 1.05,
                backgroundColor: 'transparent',
                content: mes.charAt(0).toUpperCase() + mes.slice(1),
                font: FONTE_PADRAO_ANALISE,
                color: 'black',
                position: 'center',
                xAdjust: 0,
                yAdjust: -10
            };
            return acc;
        }, {}) : {};

        const separadoresMensais = [];
        let acumulador = 0;
        Object.entries(agrupamentoMeses).forEach(([mesAtual, indices], i, array) => {
            if (i > 0) {
                separadoresMensais.push({
                    type: 'line',
                    scaleID: 'x',
                    value: acumulador - 0.5,
                    borderColor: CORES_ANALISE.SEPARADOR_MES.border,
                    borderWidth: 3,
                    borderDash: []
                });
            }
            acumulador += indices.length;
        });

        const anotacoesCompletasSemanal = {
            ...anotacoesTextoMeses,
            ...Object.fromEntries(separadoresMensais.map((linha, i) => [`linhaSeparadora${i}`, linha]))
        };

        const ctxSemanal = document.getElementById("graficoProdutividadeSemanal")?.getContext("2d");
        if (ctxSemanal) {
            graficoProdutividadeSemanal = new Chart(ctxSemanal, {
                type: "bar",
                data: {
                    labels: etiquetasSemanal,
                    datasets: [{
                        label: "Finalizados",
                        data: valoresSemanal,
                        backgroundColor: CORES_ANALISE.SEMANAL.background,
                        borderColor: CORES_ANALISE.SEMANAL.border,
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
                            font: { weight: 'bold' },
                            formatter: Math.round
                        },
                        annotation: { annotations: anotacoesCompletasSemanal },
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 12, padding: 5 },
                            onClick: (e, legendItem, legend) => {
                                if (legendItem.text === "Finalizados") {
                                    exibirNomesDosMesesAnalise = !exibirNomesDosMesesAnalise;
                                    const novasAnotacoes = {
                                        ...Object.fromEntries(separadoresMensais.map((linha, i) => [`linhaSeparadora${i}`, linha])),
                                        ...(exibirNomesDosMesesAnalise ? anotacoesTextoMeses : {})
                                    };
                                    legend.chart.options.plugins.annotation.annotations = novasAnotacoes;
                                    legend.chart.update();
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: contexto => `Finalizado: ${contexto.raw}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { autoSkip: false, maxRotation: 60, font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: Math.max(...valoresSemanal) * 1.2
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        const separadoresMensaisMensal = etiquetasMensal.reduce((acc, label, i, arr) => {
            const mesAtual = label.split(' - ')[1];
            const mesAnterior = i > 0 ? arr[i - 1].split(' - ')[1] : mesAtual;
            if (mesAtual !== mesAnterior) {
                acc.push({
                    type: 'line',
                    scaleID: 'x',
                    value: i - 0.5,
                    borderColor: CORES_ANALISE.SEPARADOR_MES.border,
                    borderWidth: 3,
                    borderDash: []
                });
            }
            return acc;
        }, []);

        const anotacoesMensal = Object.fromEntries(separadoresMensaisMensal.map((linha, i) => [`linhaSeparadoraMensal${i}`, linha]));

        const ctxMensal = document.getElementById("graficoProdutividadeMensal")?.getContext("2d");
        if (ctxMensal) {
            graficoProdutividadeMensal = new Chart(ctxMensal, {
                type: "bar",
                data: {
                    labels: etiquetasMensal,
                    datasets: [
                        {
                            label: "Finalizados",
                            data: valoresMensal,
                            backgroundColor: CORES_ANALISE.MENSAL.background,
                            borderColor: CORES_ANALISE.MENSAL.border,
                            borderWidth: 1
                        },
                        {
                            label: "Tendência",
                            data: tendenciaMensal.valores,
                            type: 'line',
                            borderColor: CORES_ANALISE.TENDENCIA.border,
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                            datalabels: {
                                display: context => context.dataIndex === context.chart.data.labels.length - 1,
                                formatter: () => tendenciaMensal.texto,
                                align: 'top',
                                anchor: 'end',
                                color: 'black',
                                font: FONTE_PADRAO_ANALISE
                            }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: { anchor: 'end',
                            align: 'top',
                            color: '#000',
                            font: { weight: 'bold' },
                            formatter: Math.round},
                        legend: {
                            display: true,
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 12, padding: 5 }
                        },
                        annotation: { annotations: anotacoesMensal },
                        tooltip: {
                            callbacks: {
                                label: contexto => `Finalizado: ${contexto.raw}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { autoSkip: false, maxRotation: 60, font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: Math.max(...valoresMensal) * 1.2
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

    } catch (erro) {
        console.error("❌ Erro ao carregar produtividade da análise:", erro);
        alert("Erro ao carregar os dados de produtividade. Tente novamente mais tarde.");
    }
}

// 🔄 Função para formatar "2025-23" como "1ª semana de março"
function formatarSemana(anoSemana) {
    const [ano, semanaTexto] = anoSemana.split('-');
    const semana = parseInt(semanaTexto);
    const dataInicial = new Date(ano, 0, 1 + (semana - 1) * 7);
    const diaSemana = dataInicial.getDay();
    const dataCorrigida = new Date(dataInicial);

    if (diaSemana <= 4) dataCorrigida.setDate(dataInicial.getDate() - diaSemana + 1);
    else dataCorrigida.setDate(dataInicial.getDate() + (8 - diaSemana));

    const mes = dataCorrigida.toLocaleString('pt-BR', { month: 'long' });
    const dia = dataCorrigida.getDate();
    const semanaDoMes = Math.ceil((dia + dataCorrigida.getDay()) / 7);

    return `${semanaDoMes}ª semana de ${mes}`;
}
