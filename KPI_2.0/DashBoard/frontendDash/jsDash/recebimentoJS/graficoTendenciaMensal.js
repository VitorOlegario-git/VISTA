let chartTendenciaMensal = null;

function carregarGraficoTendenciaMensal(dataInicio, dataFim) {
  console.log("Enviando requisição de Tendência Mensal:", { dataInicio, dataFim });

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/tendencia_mensal.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `data_inicial=${dataInicio}&data_final=${dataFim}`
  })
  .then(response => {
    if (!response.ok) throw new Error("Erro na requisição: " + response.status);
    return response.json();
  })
  .then(data => {
    const container = document.getElementById("tendenciaMensal");

    if (!data.dados || data.dados.length === 0) {
      container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
      return;
    }

    const meses   = data.dados.map(item => item.mes);
    const valores = data.dados.map(item => Number(item.total_recebimentos || 0));

    if (chartTendenciaMensal instanceof Chart) chartTendenciaMensal.destroy();

    // 🔧 Espaço e altura para melhor visualização
    container.style.overflow = "visible";
    container.innerHTML = '<canvas id="graficoTendenciaMensal" style="height:360px; display:block;"></canvas>';

    const ctx = document.getElementById("graficoTendenciaMensal").getContext("2d");

    chartTendenciaMensal = new Chart(ctx, {
      type: "line",
      data: {
        labels: meses,
        datasets: [{
          label: "Recebimentos por mês",
          data: valores,
          borderColor: "rgba(75, 192, 192, 1)",
          backgroundColor: "rgba(75, 192, 192, 0.18)",
          borderWidth: 2,
          fill: true,
          tension: 0.35,
          pointRadius: 3.5,
          pointHoverRadius: 6,
          pointHitRadius: 12
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,

        // ✅ Respiro para não colar título/legenda/datalabels nas bordas
        layout: { padding: { top: 24, right: 12, bottom: 12, left: 12 } },

        interaction: { mode: "nearest", intersect: false },

        plugins: {
          legend: {
            display: true,
            position: "bottom",
            align: "center",
            labels: { boxWidth: 12, padding: 12, font: { size: 12 } }
          },
          datalabels: {
            anchor: "end",
            align: "top",
            color: "#000",
            clamp: true,         // evita extrapolar o canvas
            clip: false,
            offset: 4,
            font: { weight: "bold", size: 10 },
            formatter: (v) => (Number.isFinite(v) ? `${v}` : "")
          },
          tooltip: {
            callbacks: { label: (ctx) => `Total: ${ctx.raw}` }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: "Quantidade" },
            grid: { color: "rgba(0,0,0,0.08)" },
            ticks: {
              precision: 0, // só inteiros
              padding: 6
            }
          },
          x: {
            title: { display: false },
            grid: { display: false },
            ticks: {
              autoSkip: false,
              maxRotation: 0,  // rótulos na horizontal quando couber
              minRotation: 0,
              padding: 6
            }
          }
        }
      },
      plugins: [ChartDataLabels]
    });
  })
  .catch(error => {
    console.error("Erro ao carregar gráfico de tendência mensal:", error);
    document.getElementById("tendenciaMensal").innerHTML = "<p>Erro ao carregar os dados.</p>";
  });
}
