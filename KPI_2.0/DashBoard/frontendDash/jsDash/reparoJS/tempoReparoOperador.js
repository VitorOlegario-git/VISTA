function carregarTempoReparoOperador(dataInicio, dataFim, operador = "") {
  console.log("🔧 Carregando Tempo Médio de Reparo por Operador...", { dataInicio, dataFim, operador });

  const params = new URLSearchParams();
  params.append("data_inicial", dataInicio);
  params.append("data_final",   dataFim);
  if (operador) params.append("operador", operador);

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/reparoPHP/tempoReparoOperador.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString()
  })
  .then(res => { if (!res.ok) throw new Error("Erro na requisição: " + res.status); return res.json(); })
  .then(payload => {
    console.log("✅ Dados recebidos:", payload);

    const wrapper = document.getElementById("graficoTempoReparoOperador");
    if (!wrapper) return;

    // Compatibilidade: se backend antigo retornar array puro
    const dados     = Array.isArray(payload) ? payload : (payload.dados || []);
    const registros = Array.isArray(payload) ? []      : (payload.registros || []);

    if (!dados.length) {
      wrapper.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
      return;
    }

    // Injeta CSS reset (anti “sobreposição” visual) uma única vez
    if (!document.getElementById("cssTabelaReparoFix")) {
      const style = document.createElement("style");
      style.id = "cssTabelaReparoFix";
      style.textContent = `
        .tabela-reparo, .tabela-reparo * { text-shadow:none!important; filter:none!important; mix-blend-mode:normal!important; transform:none!important; }
        .tabela-reparo table { width:100%; border-collapse:collapse; background:#fff; line-height:1.25; font-size:13px; }
        .tabela-reparo thead th { position:sticky; top:0; z-index:1; background:#f7f7f7; }
        .tabela-reparo th, .tabela-reparo td { padding:6px 8px; border-bottom:1px solid #eee; white-space:nowrap; }
        .tabela-reparo tbody tr:nth-child(even) { background:#fafafa; }
      `;
      document.head.appendChild(style);
    }

    // Monta estrutura: gráfico + tabela
    wrapper.style.overflow = "visible";
    wrapper.innerHTML = `
      <div style="height:360px; margin-bottom:16px;">
        <canvas id="graficoReparoOperador" style="height:100%; display:block;"></canvas>
      </div>

      <div class="tabela-reparo">
        <h4 style="margin:4px 0 10px 0;">Reparos concluídos (detalhe)</h4>
        <div style="max-height:320px; overflow:auto; border:1px solid #e3e3e3; border-radius:8px;">
          <table id="tabelaTempoReparoOperador">
            <thead>
              <tr>
                <th>Técnico</th>
                <th>Razão Social</th>
                <th>Nota Fiscal</th>
                <th>Quantidade</th>
                <th>Início</th>
                <th>Solicitação NF</th>
                <th style="text-align:right;">Dias</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <p id="tempoReparoResumo" style="margin-top:8px; font-weight:600;"></p>
      </div>
    `;

    // === Gráfico ===
    const operadores = dados.map(i => i.operador || "Desconhecido");
    const tempos     = dados.map(i => Number(i.tempo_medio ?? 0));

    const ctx = document.getElementById("graficoReparoOperador").getContext("2d");
    if (window.graficoTempoReparoOperadorChart instanceof Chart) {
      window.graficoTempoReparoOperadorChart.destroy();
    }

    window.graficoTempoReparoOperadorChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: operadores,
        datasets: [{
          label: "Tempo Médio (dias)",
          data: tempos,
          backgroundColor: "rgba(54, 162, 235, 0.6)",
          borderColor: "rgba(54, 162, 235, 1)",
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { top: 24, right: 10, bottom: 10, left: 10 } },
        plugins: {
          title: { display: true, text: "" },
          datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#000',
            font: { weight: 'bold', size: 10 },
            offset: 4,
            clamp: true,
            formatter: v => `${Number.isFinite(v) ? v : 0}d`
          },
          tooltip: { callbacks: { label: ctx => `${ctx.raw} dias` } },
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: "Dias" }, ticks: { precision: 0, padding: 6 }, grid: { color:"rgba(0,0,0,0.08)" } },
          x: { ticks: { autoSkip:false, maxRotation:60, font:{ size:10 }, padding:6 }, title: { display:true, text:"Técnico" }, grid:{ display:false } }
        }
      },
      plugins: [ChartDataLabels]
    });

    // === Tabela (detalhe) ===
    const tbody = document.querySelector("#tabelaTempoReparoOperador tbody");
    const safe = (s) => { const el = document.createElement("div"); el.textContent = s ?? ""; return el.innerHTML; };

    let totalDias = 0, contDias = 0;
    (registros || []).forEach(r => {
      const diasNum = Number.isFinite(Number(r?.dias)) ? Number(r.dias) : null;
      if (diasNum !== null) { totalDias += diasNum; contDias++; }

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${safe(r?.operador)}</td>
        <td>${safe(r?.razao_social)}</td>
        <td>${safe(r?.nota_fiscal)}</td>
        <td>${r?.quantidade_parcial ?? "—"}</td>
        <td>${safe(r?.data_inicio_reparo)}</td>
        <td>${safe(r?.data_solicitacao_nf)}</td>
        <td style="text-align:right;">${diasNum ?? "—"}</td>
      `;
      tbody.appendChild(tr);
    });

    const media = contDias ? (totalDias / contDias).toFixed(2) : "—";
    document.getElementById("tempoReparoResumo").textContent =
      `Registros listados: ${registros.length || 0} | Média (detalhe): ${media} dias`;
  })
  .catch(error => {
    console.error("❌ Erro ao carregar dados:", error);
    document.getElementById("graficoTempoReparoOperador").innerHTML =
      "<p style='color:#b00'>Erro ao carregar os dados.</p>";
  });
}
