let chartAnalisesCliente = null;

function carregarAnalisesPorCliente(dataInicio, dataFim, operador = "") {
  const params = new URLSearchParams();
  if (dataInicio) params.append("data_inicial", dataInicio);
  if (dataFim)    params.append("data_final", dataFim);
  if (operador)   params.append("operador", operador);

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/analisePHP/analises_cliente.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString()
  })
  .then(res => res.json())
  .then(data => {
    const wrapper = document.getElementById("graficoAnalisesCliente").parentElement;

    if (!Array.isArray(data) || data.length === 0) {
      wrapper.innerHTML = "<p>Nenhum dado disponível para o período e operador selecionados.</p>";
      return;
    }

    // Ordena decrescente
    const ordenado = [...data].sort((a, b) => Number(b.total || 0) - Number(a.total || 0));

    const fullLabels = ordenado.map(item => item.razao_social ?? "");
    const valores    = ordenado.map(item => Number(item.total || 0));

    // Função de quebra de texto
    const wrapLabel = (texto, max = 18) => {
      if (!texto) return "";
      const palavras = texto.split(" ");
      const linhas = [];
      let linha = "";

      for (const p of palavras) {
        if ((linha + " " + p).trim().length <= max) {
          linha = (linha ? linha + " " : "") + p;
        } else {
          if (linha) linhas.push(linha);
          
          if (p.length > max) {
            for (let i = 0; i < p.length; i += max) linhas.push(p.slice(i, i + max));
            linha = "";
          } else {
            linha = p;
          }
        }
      }

      if (linha) linhas.push(linha);

      return linhas.join("\n");
    };

    const labels = fullLabels.map(l => wrapLabel(l));

    // Definições dinâmicas
    const many = labels.length > 12;
    const canvas = document.getElementById("graficoAnalisesCliente");
    const altura = many
      ? Math.min(700, Math.max(380, labels.length * 28))
      : 360;

    canvas.style.height = `${altura}px`;
    canvas.style.display = "block";
    wrapper.style.overflow = "visible";

    if (chartAnalisesCliente instanceof Chart) chartAnalisesCliente.destroy();

    const ctx = canvas.getContext("2d");

    chartAnalisesCliente = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [{
          label: "Análises por cliente",
          data: valores,
          backgroundColor: "rgba(153, 102, 255, 0.5)",
          borderColor: "rgba(153, 102, 255, 1)",
          borderWidth: 1,
          categoryPercentage: many ? 0.7 : 0.8,
          barPercentage: many ? 0.8 : 0.9
        }]
      },
      options: {
        indexAxis: many ? "y" : "x",
        responsive: true,
        maintainAspectRatio: false,

        layout: { padding: { top: 24, right: 12, bottom: 12, left: 12 } },

        interaction: { mode: "nearest", intersect: false },

        plugins: {
          legend: {
            display: true,
            position: "bottom",
            labels: { boxWidth: 12, padding: 12, font: { size: 12 } }
          },
          datalabels: {
            anchor: many ? "center" : "end",
            align: many ? "right" : "top",
            offset: 4,
            clamp: true,
            color: "#000",
            font: { weight: "bold", size: 10 },
            formatter: v => Number.isFinite(v) ? v : ""
          },
          tooltip: {
            callbacks: {
              title: items => items.length ? fullLabels[items[0].dataIndex] : "",
              label: ctx => `Total: ${ctx.raw}`
            }
          }
        },

        scales: many
          ? {
              x: {
                beginAtZero: true,
                grid: { color: "rgba(0,0,0,0.08)" },
                ticks: { precision: 0, padding: 6 }
              },
              y: {
                grid: { display: false },
                ticks: { autoSkip: false, font: { size: 11 }, padding: 4 }
              }
            }
          : {
              x: {
                grid: { display: false },
                ticks: {
                  autoSkip: false,
                  maxRotation: 45,
                  minRotation: 45,
                  font: { size: 11 },
                  padding: 6
                }
              },
              y: {
                beginAtZero: true,
                grid: { color: "rgba(0,0,0,0.08)" },
                ticks: { precision: 0, padding: 6 }
              }
            }
      },
      plugins: [ChartDataLabels]
    });
  })
  .catch(err => {
    console.error("Erro ao carregar análises por cliente:", err);
  });
}
