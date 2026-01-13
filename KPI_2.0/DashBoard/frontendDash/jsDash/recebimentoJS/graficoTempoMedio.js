let chartTempoMedio = null;

/**
 * Carrega e renderiza o gráfico de tempo médio.
 * @param {string} dataInicio - Data inicial no formato AAAA-MM-DD.
 * @param {string} dataFim - Data final no formato AAAA-MM-DD.
 */
function carregarGraficoTempoMedio(dataInicio, dataFim) {
    if (!dataInicio || !dataFim) {
        console.error("❌ Datas de início e fim são obrigatórias");
        alert("Por favor, forneça datas de início e fim válidas.");
        return;
    }

    const container = document.getElementById("tempoMedioAnalise");
    container.innerHTML = "<p>Carregando...</p>";

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/tempo_medio.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ data_inicial: dataInicio, data_final: dataFim })
    })
    .then(response => {
        if (!response.ok) throw new Error("Erro na requisição: " + response.status);
        return response.json();
    })
    .then(data => {
        if (!data.dados || data.dados.length === 0) {
            container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
            return;
        }

        const dias = data.dados.map(item => item.dia);
        const temposMedios = data.dados.map(item => item.tempo_medio);
        const detalhesPorDia = data.dados.map(item => item.detalhes);

        if (chartTempoMedio instanceof Chart) chartTempoMedio.destroy();

        container.innerHTML = `
            <div style="height: 300px; margin-bottom: 30px;">
                <canvas id="graficoTempoMedio"></canvas>
            </div>
            <div id="listaNFsContainer">
                <h4 style="margin-bottom: 10px;">Dias aguardando nota fiscal:</h4>
                <div id="listaNFs" style="font-family: Arial; font-size: 14px;"></div>
            </div>
        `;

        console.log("Dados para o gráfico:", { dias, temposMedios }); // Log para depuração
        const ctxTempo = document.getElementById("graficoTempoMedio").getContext("2d");
        if (!ctxTempo) {
            console.error("❌ Contexto do canvas 'graficoTempoMedio' não encontrado");
            container.innerHTML = "<p>Erro: Não foi possível carregar o gráfico.</p>";
            return;
        }

        chartTempoMedio = new Chart(ctxTempo, {
            type: "bar",
            data: {
                labels: dias,
                datasets: [{
                    label: "",
                    data: temposMedios,
                    borderColor: "rgba(75, 192, 192, 1)",
                    backgroundColor: "rgba(75, 192, 192, 0.2)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7
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
                        rotation: -90,
                        formatter: value => `${value}d`
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `Tempo médio: ${context.raw} `
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Dias' }
                    },
                    x: {
                        title: { display: true, text: 'Dia' },
                        ticks: { maxRotation: 45, autoSkip: false }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Renderiza a tabela com NF + Razão Social
        const listaContainer = document.getElementById("listaNFs");
        let tabelaHTML = `
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-family: Arial, sans-serif; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid #ccc; padding: 8px;">Dia</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Nota Fiscal</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Razão Social</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Tempo (dias)</th>
                    </tr>
                </thead>
                <tbody>
        `;

        dias.forEach((dia, index) => {
            const detalhes = detalhesPorDia[index];
            if (!detalhes || detalhes.length === 0) {
                tabelaHTML += `
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px;">${dia}</td>
                        <td colspan="3" style="border: 1px solid #ccc; padding: 8px; color: #777;">Nenhuma nota fiscal registrada</td>
                    </tr>
                `;
            } else {
                detalhes.forEach(item => {
                    const nf = item.nf.trim();
                    const cliente = item.razao_social.trim();
                    const diasEntre = item.dias_entre ?? '-';
                    tabelaHTML += `
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;">${dia}</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">${nf}</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">${cliente}</td>
                            <td style="border: 1px solid #ccc; padding: 8px;">${diasEntre}</td>
                        </tr>
                    `;
                });
            }
        });

        tabelaHTML += `
                </tbody>
            </table>
        `;

        listaContainer.innerHTML = tabelaHTML;
    })
    .catch(error => {
        console.error("❌ Erro ao carregar gráfico de tempo médio:", error);
        container.innerHTML = "<p>Erro ao carregar os dados. Tente novamente mais tarde.</p>";
    });
}