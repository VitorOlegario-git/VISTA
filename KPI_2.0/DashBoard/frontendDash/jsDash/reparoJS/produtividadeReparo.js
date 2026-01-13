// 🎯 Variáveis globais
let graficoReparoSemanal = null;
let graficoReparoMensal = null;
let exibirNomesDosMesesReparo = true;

// 🎨 Paleta de cores
const CORES_REPARO = {
    SEMANAL: { background: "rgba(52, 199, 89, 0.6)", border: "rgba(0, 122, 51, 1)" },
    MENSAL: { background: "rgba(0, 122, 255, 0.6)", border: "rgba(0, 64, 128, 1)" },
    TENDENCIA: { border: "rgba(255, 99, 132, 1)" },
    SEPARADOR_MES: { border: "rgba(100, 100, 100, 1)" },
    SEPARADOR_SEMANA: { border: "rgba(180, 180, 180, 0.5)" }
};

// 🖋️ Fonte padrão
const FONTE_PADRAO_REPARO = { size: 12, style: 'italic', weight: 'bold' };

// 🔄 Formata "2025-23" para "1ª semana de março"
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

// 📈 Calcula a linha de tendência
function calcularTrendlineReparo(yData) {
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

// 🚀 Carrega e renderiza gráficos
async function carregarProdutividadeReparo(dataInicio, dataFim, operador = "") {
    if (!dataInicio || !dataFim) {
        alert("Por favor, forneça datas de início e fim válidas.");
        return;
    }

    try {
        const resposta = await fetch("../backendDash/reparoPHP/produtividade_reparo.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}&operador=${encodeURIComponent(operador)}`
        });

        if (!resposta.ok) throw new Error(`Erro ao buscar dados: ${resposta.status}`);
        const dados = await resposta.json();

        if (!dados.semanal?.length && !dados.mensal?.length) {
            alert("Não há dados para o período selecionado.");
            return;
        }

        const etiquetasSemanal = dados.semanal.map(item => `${item.operador} - ${formatarSemana(item.periodo)}`);
        const valoresSemanal = dados.semanal.map(item => item.quantidade);

        const etiquetasMensal = dados.mensal.map(item => `${item.operador} - ${item.periodo}`);
        const valoresMensal = dados.mensal.map(item => item.quantidade);
        const tendenciaMensal = calcularTrendlineReparo(valoresMensal);

        if (graficoReparoSemanal) graficoReparoSemanal.destroy();
        if (graficoReparoMensal) graficoReparoMensal.destroy();

        // === Anotações do gráfico semanal ===
        const agrupamentoMeses = {};
        etiquetasSemanal.forEach((etiqueta, i) => {
            const mes = etiqueta.match(/de ([a-zç]+)/i)?.[1] || "Indefinido";
            if (!agrupamentoMeses[mes]) agrupamentoMeses[mes] = [];
            agrupamentoMeses[mes].push(i);
        });

        const anotacoesTexto = exibirNomesDosMesesReparo
            ? Object.entries(agrupamentoMeses).reduce((acc, [mes, indices], i) => {
                  const media = indices.reduce((a, b) => a + b, 0) / indices.length;
                  acc[`mes${i}`] = {
                      type: 'label',
                      xValue: media,
                      yValue: Math.max(...valoresSemanal) * 1.05,
                      backgroundColor: 'transparent',
                      content: mes.charAt(0).toUpperCase() + mes.slice(1),
                      font: FONTE_PADRAO_REPARO,
                      color: 'black',
                      position: 'center',
                      xAdjust: 0,
                      yAdjust: -10
                  };
                  return acc;
              }, {})
            : {};

        const separadores = [];
        let acumulado = 0;
        Object.entries(agrupamentoMeses).forEach(([_, indices], i) => {
            if (i > 0) {
                separadores.push({
                    type: 'line',
                    scaleID: 'x',
                    value: acumulado - 0.5,
                    borderColor: CORES_REPARO.SEPARADOR_MES.border,
                    borderWidth: 3
                });
            }
            acumulado += indices.length;
        });
        
        // 🔸 Separadores de mês (já existentes)
        const anotacoesM = Object.fromEntries(separadores.map((linha, i) => [`linhaMes${i}`, linha]));
        
        // 🔹 Separadores de semana (um por item)
        const separadoresSemanais = etiquetasSemanal.map((_, i) => ({
            type: 'line',
            scaleID: 'x',
            value: i - 0.5,
            borderColor: CORES_REPARO.SEPARADOR_SEMANA.border,
            borderWidth: 1
        }));
        const anotacoesS = Object.fromEntries(separadoresSemanais.map((linha, i) => [`linhaSemana${i}`, linha]));
        
        // 🧩 Junta tudo
        const anotacoesSemanal = {
            ...anotacoesTexto,
            ...anotacoesM,
            ...anotacoesS
        };


        // === Gráfico Semanal ===
        const ctxSemanal = document.getElementById("graficoReparoSemanal")?.getContext("2d");
        if (ctxSemanal) {
            graficoReparoSemanal = new Chart(ctxSemanal, {
                type: 'bar',
                data: {
                    labels: etiquetasSemanal,
                    datasets: [{
                        label: "Finalizados",
                        data: valoresSemanal,
                        backgroundColor: CORES_REPARO.SEMANAL.background,
                        borderColor: CORES_REPARO.SEMANAL.border,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 50, bottom: 10 }
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#000',
                            font: { weight: 'bold' },
                            formatter: Math.round
                        },
                        annotation: { annotations: anotacoesSemanal },
                        legend: {
                        display: true,
                        position: 'top',      // ou 'right' para lateral
                        align: 'end',            // posiciona no canto
                        labels: {
                            boxWidth: 12,        // tamanho da caixinha de cor
                            padding: 5,          // espaço interno
                            font: {
                                size: 10         // tamanho da fonte
                            },
                            color: '#555'        // cor discreta
    },
                            onClick: (e, item, legend) => {
                                if (item.text === "Finalizados") {
                                    exibirNomesDosMesesReparo = !exibirNomesDosMesesReparo;
                                    const novas = exibirNomesDosMesesReparo
                                        ? { ...anotacoesTexto, ...Object.fromEntries(separadores.map((l, i) => [`linha${i}`, l])) }
                                        : Object.fromEntries(separadores.map((l, i) => [`linha${i}`, l]));
                                    legend.chart.options.plugins.annotation.annotations = novas;
                                    legend.chart.update();
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { autoSkip: false, maxRotation: 60, font: { size: 10 } } },
                        y: { beginAtZero: true, suggestedMax: Math.max(...valoresSemanal) * 1.2 }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        // === Gráfico Mensal ===
        const separadoresMensais = etiquetasMensal.reduce((acc, label, i, arr) => {
            const atual = label.split(" - ")[1];
            const anterior = i > 0 ? arr[i - 1].split(" - ")[1] : atual;
            if (atual !== anterior) {
                acc.push({
                    type: 'line',
                    scaleID: 'x',
                    value: i - 0.5,
                    borderColor: CORES_REPARO.SEPARADOR_MES.border,
                    borderWidth: 3
                });
            }
            return acc;
        }, []);

        const anotacoesMensal = Object.fromEntries(separadoresMensais.map((linha, i) => [`linhaMensal${i}`, linha]));

        const ctxMensal = document.getElementById("graficoReparoMensal")?.getContext("2d");
        if (ctxMensal) {
            graficoReparoMensal = new Chart(ctxMensal, {
                type: 'bar',
                data: {
                    labels: etiquetasMensal,
                    datasets: [
                        {
                            label: "Finalizados",
                            data: valoresMensal,
                            backgroundColor: CORES_REPARO.MENSAL.background,
                            borderColor: CORES_REPARO.MENSAL.border,
                            borderWidth: 1
                        },
                        {
                            label: "Tendência",
                            data: tendenciaMensal.valores,
                            type: 'line',
                            borderColor: CORES_REPARO.TENDENCIA.border,
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                            datalabels: {
                                display: ctx => ctx.dataIndex === ctx.chart.data.labels.length - 1,
                                formatter: () => tendenciaMensal.texto,
                                anchor: 'end',
                                align: 'top',
                                font: FONTE_PADRAO_REPARO,
                                color: 'black'
                            }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 50, bottom: 10 }
                    },
                    plugins: {
                        datalabels: { anchor: 'end',
                            align: 'top',
                            color: '#000',
                            font: { weight: 'bold' },
                            formatter: Math.round},
                        annotation: { annotations: anotacoesMensal },
                        legend: {
                        display: true,
                        position: 'top',     // Posiciona abaixo
                        align: 'end',           // Alinha à direita
                        labels: {
                            boxWidth: 12,       // Diminui o tamanho da caixinha de cor
                            padding: 5,         // Reduz o espaço interno
                            font: { size: 10 }, // Fonte menor
                            color: '#555'       // Cor mais discreta
                        }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `Finalizado: ${ctx.raw}`
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { autoSkip: true, maxRotation: 45, font: { size: 10 } } },
                        y: { beginAtZero: true, suggestedMax: Math.max(...valoresMensal) * 1.2, title: { display: true, text: '' },
                            grace: 10 }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

    } catch (erro) {
        console.error("❌ Erro ao carregar produtividade do reparo:", erro);
        alert("Erro ao carregar os dados. Verifique o console para mais detalhes.");
    }
}
