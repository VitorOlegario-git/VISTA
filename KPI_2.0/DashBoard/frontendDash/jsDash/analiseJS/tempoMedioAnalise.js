// === Tempo Médio de Análise: gráfico + tabela (com reset de CSS local) ===
let chartTempoMedioAnalise = null;

(function ensureTabelaCSS() {
  if (document.getElementById("cssTabelaAnaliseFix")) return;
  const style = document.createElement("style");
  style.id = "cssTabelaAnaliseFix";
  style.textContent = `
    /* Reset visual local para evitar sobreposição/fantasma */
    .tabela-tempo-analise, .tabela-tempo-analise * {
      text-shadow: none !important;
      filter: none !important;
      mix-blend-mode: normal !important;
      transform: none !important;
    }
    .tabela-tempo-analise table {
      width: 100%;
      border-collapse: collapse;
      line-height: 1.25;
      background: #fff;
      font-size: 13px;
    }
    .tabela-tempo-analise thead th {
      position: sticky; top: 0; z-index: 1;
      background: #f7f7f7;
    }
    .tabela-tempo-analise th, .tabela-tempo-analise td {
      padding: 6px 8px;
      border-bottom: 1px solid #eee;
      white-space: nowrap;
    }
    .tabela-tempo-analise tbody tr:nth-child(even) { background: #fafafa; }
    #graficoTempoMedioAnaliseContainer { transform: none !important; }
  `;
  document.head.appendChild(style);
})();

function carregarTempoMedioAnalise(dataInicio, dataFim, operador = "") {
  const params = new URLSearchParams();
  if (dataInicio) params.append("data_inicial", dataInicio);
  if (dataFim)    params.append("data_final",   dataFim);
  if (operador)   params.append("operador",     operador);

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/analisePHP/tempo_medio_analise.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString()
  })
  .then(res => {
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  })
  .then(payload => {
    const wrapper = document.getElementById("graficoTempoMedioAnaliseContainer");
    if (!wrapper) return;

    // Compatibilidade: array puro (versões antigas) ou objeto {dados, registros}
    const dados     = Array.isArray(payload) ? payload : (payload.dados || []);
    const registros = Array.isArray(payload) ? []      : (payload.registros || []);

    if (!dados.length) {
      wrapper.innerHTML = "<p>Nenhum dado disponível para o período e operador selecionado.</p>";
      return;
    }

    // Monta estrutura (gráfico + tabela)
    wrapper.style.overflow = "visible";
wrapper.innerHTML = `
  <div style="height:360px; margin-bottom:16px;">
    <canvas id="graficoTempoMedioAnalise" style="height:100%; display:block;"></canvas>
  </div>

  <div class="tabela-tempo-analise">
    <h4 style="margin:4px 0 10px 0;">Análises concluídas (detalhe)</h4>
    <div style="max-height:320px; overflow:auto; border:1px solid #e3e3e3; border-radius:8px;">
      <table id="tabelaTempoMedioAnalise">
        <thead>
          <tr>
            <th>Operador</th>
            <th>Razão Social</th>
            <th>Nota Fiscal</th>
            <th>Quantidade</th>
            <th>Início</th>
            <th>Envio orçamento</th>
            <th style="text-align:right;">Dias</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
    <p id="tempoAnaliseResumo" style="margin-top:8px; font-weight:600;"></p>
  </div>
`;
    // === Gráfico ===
    const labels  = dados.map(i => i?.operador ?? "");
    const valores = dados.map(i => Number(i?.tempo_medio ?? 0));

    const ctx = document.getElementById("graficoTempoMedioAnalise").getContext("2d");
    if (chartTempoMedioAnalise instanceof Chart) chartTempoMedioAnalise.destroy();

    chartTempoMedioAnalise = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [{
          label: "Tempo médio por operador (dias)",
          data: valores,
          backgroundColor: "rgba(75, 192, 192, 0.5)",
          borderColor: "rgba(75, 192, 192, 1)",
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 24, right: 10, bottom: 10, left: 10 } },
        interaction: { mode: "nearest", intersect: false },
        plugins: {
          legend: {
            display: true,
            position: "bottom",
            labels: { padding: 12, boxWidth: 12, font: { size: 12 } }
          },
          datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#000',
            font: { weight: 'bold', size: 10 },
            offset: 4,
            clamp: true,
            formatter: v => `${Number.isFinite(v) ? v : 0}d`
          },
          tooltip: {
            callbacks: { label: ctx => `Tempo médio: ${ctx.raw} dias` }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: "Dias" },
            ticks: { precision: 0, padding: 6 },
            grid: { color: "rgba(0,0,0,0.08)" }
          },
          x: {
            ticks: { autoSkip: false, maxRotation: 60, font: { size: 11 }, padding: 6 },
            title: { display: false },
            grid: { display: false }
          }
        }
      },
      plugins: [ChartDataLabels]
    });

    // === Tabela (detalhe) ===
    const tbody = wrapper.querySelector("#tabelaTempoMedioAnalise tbody");
    const escape = (s) => { const el = document.createElement("div"); el.textContent = s ?? ""; return el.innerHTML; };

    let totalDias = 0;
    let contDias  = 0;

(registros || []).forEach(r => {
  const diasNum = Number.isFinite(Number(r?.dias)) ? Number(r.dias) : null;
  if (diasNum !== null) { totalDias += diasNum; contDias++; }

  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>${escape(r?.operador)}</td>
    <td>${escape(r?.razao_social)}</td>
    <td>${escape(r?.nota_fiscal)}</td>
    <td>${r?.quantidade_parcial ? escape(r.quantidade_parcial.toString()) : "—"}</td>
    <td>${escape(r?.data_inicio_analise)}</td>
    <td>${escape(r?.data_envio_orcamento)}</td>
    <td style="text-align:right;">${diasNum ?? "—"}</td>
  `;
  tbody.appendChild(tr);
});

    const mediaDetalhe = contDias ? (totalDias / contDias).toFixed(2) : "—";
    wrapper.querySelector("#tempoAnaliseResumo").textContent =
      `Registros listados: ${registros.length || 0} | Média (detalhe): ${mediaDetalhe} dias`;
  })
  .catch(err => {
    console.error("Erro ao carregar tempo médio de análise:", err);
    const c = document.getElementById("graficoTempoMedioAnaliseContainer");
    if (c) c.innerHTML = "<p style='color:#b00'>Erro ao carregar os dados.</p>";
  });
}
