let chartTempoOperacoes = null;

function carregarGraficoTempoOperacoes(dataInicio, dataFim) {
  console.log("Enviando requisição de Tempo Médio entre Operações:", { dataInicio, dataFim });

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/tempo_medio_operacoes.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ data_inicial: dataInicio, data_final: dataFim }).toString()
  })
  .then(r => { if (!r.ok) throw new Error("Erro na requisição: " + r.status); return r.json(); })
  .then(data => {
    console.log("Dados recebidos:", data);

    const container = document.getElementById("tempoOperacoes");

    if (!data.dados || data.dados.length === 0) {
      container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
      return;
    }

    const operacoes = data.dados.map(i => `${i.operacao_origem} → ${i.operacao_destino}`);
    const tempos    = data.dados.map(i => i.tempo_medio);

    if (chartTempoOperacoes instanceof Chart) chartTempoOperacoes.destroy();

    // 🔧 Garanta altura e overflow visível (evita corte da legenda)
    container.innerHTML = '<canvas id="graficoTempoOperacoes" style="height:380px; display:block;"></canvas>';
    container.style.overflow = "visible";

    const ctx = document.getElementById("graficoTempoOperacoes").getContext("2d");

    chartTempoOperacoes = new Chart(ctx, {
      type: "bar",
      data: {
        labels: operacoes.map(op => op.replace(" → ", "\n→ ")),
        datasets: [{
          label: "Tempo médio entre operações",
          data: tempos,
          backgroundColor: "rgba(255, 206, 86, 0.6)",
          borderColor: "rgba(255, 206, 86, 1)",
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,

        // ✅ Reserva espaço extra para legenda/título
        layout: {
          padding: { top: 28, right: 10, bottom: 10, left: 10 }
        },

        plugins: {
          // Se quiser mostrar a legenda, deixe display:true e posicione embaixo
          legend: {
            display: true,            // <-- troque para false se não quiser legenda
            position: "bottom",
            align: "center",
            labels: {
              boxWidth: 12,
              padding: 16,
              font: { size: 12 }
            }
          },
          // Caso use título, esse padding evita sobreposição
          title: {
            display: false,           // coloque true se usar title.text
            text: "Tempo médio entre operações",
            padding: { top: 6, bottom: 16 }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#000',
            font: { weight: 'bold' },
            formatter: v => `${v}d`
          },
          tooltip: {
            callbacks: { label: ctx => `Tempo médio: ${ctx.raw} dias` }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Tempo médio (dias)' }
          },
          x: {
            title: { display: true, text: 'Operações' },
            ticks: { autoSkip: false, maxRotation: 45 }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  })
  .catch(err => {
    console.error("Erro ao carregar gráfico de tempo médio entre operações:", err);
    document.getElementById("tempoOperacoes").innerHTML =
      `<p style="color:red">Erro ao carregar os dados.</p>`;
  });
}
