function carregarPrincipaisServicos(dataInicio, dataFim) {
  console.log("📦 Buscando principais serviços...", { dataInicio, dataFim });

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/qualidadePHP/principaisServicos.php", {
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

    // 🔎 Produtos únicos (ordenados)
    const todosProdutosSet = new Set();
    for (const servico in data) {
      for (const produto in data[servico]) todosProdutosSet.add(produto);
    }
    const produtosOriginais = Array.from(todosProdutosSet).sort();

    // 🧠 Quebra label longa em várias linhas
    const wrap = (txt, max = 14) => {
      if (!txt) return "";
      const out = [];
      let linha = "";
      for (const palavra of String(txt).split(" ")) {
        if ((linha + " " + palavra).trim().length <= max) {
          linha = (linha ? linha + " " : "") + palavra;
        } else {
          if (linha) out.push(linha);
          if (palavra.length > max) {
            for (let i = 0; i < palavra.length; i += max) out.push(palavra.slice(i, i + max));
            linha = "";
          } else {
            linha = palavra;
          }
        }
      }
      if (linha) out.push(linha);
      return out.join("\n");
    };
    const labels = produtosOriginais.map(p => wrap(p, 16));

    // 📊 Datasets (um por serviço)
    const datasets = Object.keys(data).map((servico, i) => {
      const cor = gerarCor(i);
      const valores = produtosOriginais.map(prod => data[servico][prod] || 0);
      return {
        label: servico,
        data: valores,
        backgroundColor: cor.background,
        borderColor: cor.border,
        borderWidth: 1,
        categoryPercentage: 0.8,
        barPercentage: 0.9
      };
    });

    // 📐 Ajustes dinâmicos: rotação e altura
// 📐 Altura dinâmica — para barras horizontais vale a pena aumentar um pouco
const muitosProdutos = labels.length > 18;
const altura = Math.min(1200, Math.max(420, labels.length * (muitosProdutos ? 28 : 24)));
canvas.style.height = `${altura}px`;
canvas.style.display = "block";

window.chartServicosReparo = new Chart(ctx, {
  type: "bar",
  data: { labels, datasets },
  options: {
    indexAxis: 'y',                 // ⬅️ deixa as categorias no eixo Y (à esquerda)
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: { top: 24, right: 16, bottom: 12, left: 16 } },
    plugins: {
      title: { display: true, text: "" },
      legend: { position: "top" },
      tooltip: {
        callbacks: {
          title: (items) => items.length ? produtosOriginais[items[0].dataIndex] : "",
          label: (ctx) => `${ctx.dataset.label}: ${ctx.raw}x`
        }
      },
      datalabels: {
        anchor: 'end',
        align: 'right',             // ⬅️ rótulo no fim da barra, à direita
        offset: 6,
        clamp: true,
        rotation: 0,                // ⬅️ sem rotação
        color: '#000',
        font: { size: 10, weight: 'bold' },
        formatter: val => (val > 0 ? `${val}x` : '')
      }
    },
    scales: {
      y: {                          // ⬅️ agora o Y é categórico (produtos)
        stacked: true,
        grid: { display: false },
        ticks: {
          autoSkip: false,
          maxRotation: 0,
          minRotation: 0,
          font: { size: 10 },
          padding: 4,
          // opcional: truncar visualmente nomes muito longos
          //callback: (v) => (String(v).length > 60 ? String(v).slice(0, 60)+'…' : v)
        },
        title: { display: true, text: "Produtos" }
      },
      x: {                          // ⬅️ agora o X é numérico (quantidade)
        stacked: true,
        beginAtZero: true,
        grid: { color: "rgba(0,0,0,0.08)" },
        ticks: { precision: 0, padding: 6 },
        title: { display: true, text: "Quantidade" }
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

// 🔹 Cores
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
