/* =========================================================
 * semConsertoProdutos.js — gráfico (empilhado) + tabela reativa
 * Requisitos:
 *  - Backend retorna: { ok, data: { labels, breakdown, modelos, tabela[ {modelo, produto, servico, quantidade} ] } }
 *  - Chart.js 4.x (ou 3.x) + (opcional) chartjs-plugin-datalabels
 *  - Canvas:   <canvas id="graficoSemConserto"></canvas>
 *  - Wrapper:  <div id="graficosemconserto"></div> (referência para inserir a tabela logo abaixo)
 * =======================================================*/

/* ===== Cores para serviços (empilhado) ===== */
const CORES_SERVICO = [
  "rgba(255, 99, 132, 0.6)",  // SR - Fora Garantia
  "rgba(255, 159, 64, 0.6)",  // SR - Subst. em Garantia
  "rgba(75, 192, 192, 0.6)"   // Rejeitado pelo Cliente
];
const CORES_BORDA = CORES_SERVICO.map(c => c.replace("0.6", "1"));

/* ===== Globs / Estado ===== */
window.chartSemConserto = null; // mantido global
let TABELA_SEM_CONSERTO_CACHE = []; // guarda as linhas da tabela vindas do backend
let SERVICOS_ATIVOS = new Set([
  "SEM REPARO - FORA DA GARANTIA",
  "SEM REPARO - SUBST. EM GARANTIA",
  "REJEITADO PELO CLIENTE"
]);

/* ===== Util: garante estrutura da tabela abaixo do gráfico ===== */
function ensureTabelaEstrutura() {
  let bloco = document.getElementById("blocoTabelaSemConserto");
  if (bloco) return bloco;

  const ref = document.getElementById("graficosemconserto");
  if (!ref) return null;

  bloco = document.createElement("div");
  bloco.className = "grafico-container";
  bloco.id = "blocoTabelaSemConserto";
  bloco.style.marginTop = "12px";
  bloco.innerHTML = `
    <h3>📋 Sem Conserto por Modelo</h3>
    <div style="margin: 8px 0;">
      <label for="filtroModeloSemConserto" style="margin-right:6px;">Modelo:</label>
      <select id="filtroModeloSemConserto">
        <option value="">Todos os modelos</option>
      </select>
    </div>
    <div class="tabela-laudos">
      <table id="tabelaSemConserto" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Modelo</th>
            <th style="text-align:left; padding:8px; border-bottom:1px solid #ccc;">Apontamento (sem conserto)</th>
            <th style="text-align:right; padding:8px; border-bottom:1px solid #ccc;">Quantidade</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>`;
  ref.insertAdjacentElement("afterend", bloco);
  return bloco;
}

/* ===== Util: popular dropdown de modelos ===== */
function popularModelosDropdown(modelos) {
  const bloco = ensureTabelaEstrutura();
  if (!bloco) return;

  const sel = document.getElementById("filtroModeloSemConserto");
  if (!sel) return;

  sel.innerHTML = `<option value="">Todos os modelos</option>`;
  (modelos || []).forEach((m) => {
    const opt = document.createElement("option");
    opt.value = m;
    opt.textContent = m;
    sel.appendChild(opt);
  });
}

/* ===== Util: obtém serviços ativos a partir do chart ===== */
function getServicosAtivosFromChart(chart) {
  const ativos = new Set();
  chart.data.datasets.forEach((ds, i) => {
    const meta = chart.getDatasetMeta(i);
    const hidden = meta.hidden === true || ds.hidden === true;
    if (!hidden) ativos.add(ds.label);
  });
  return ativos;
}

/* ===== Tabela (render com filtro por modelo + serviços ativos) ===== */
function renderTabelaSemConserto(rows, servicosFiltro = SERVICOS_ATIVOS) {
  const tbody = document.querySelector("#tabelaSemConserto tbody");
  if (!tbody) {
    ensureTabelaEstrutura();
    return;
  }

  const filtroModelo = document.getElementById("filtroModeloSemConserto")?.value || "";

  const filtradas = (rows || []).filter(r => {
    const okModelo = !filtroModelo || r.modelo === filtroModelo;
    const okServico = !servicosFiltro || servicosFiltro.has(r.servico); // requer campo "servico" do backend
    return okModelo && okServico;
  });

  if (filtradas.length === 0) {
    tbody.innerHTML = `<tr><td colspan="3" style="padding:8px;">Sem registros para o filtro.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtradas.map(r => `
    <tr>
      <td>${r.modelo || "-"}</td>
      <td>${r.produto}</td>
      <td style="text-align:right;">${r.quantidade}</td>
    </tr>
  `).join("");
}

/* ===== Criação do gráfico (empilhado por serviço) ===== */
function montarGraficoSemConserto({ labels, breakdown }) {
  const canvas = document.getElementById("graficoSemConserto");
  if (!canvas) throw new Error("Canvas #graficoSemConserto não encontrado.");
  const ctx = canvas.getContext("2d");

  if (window.chartSemConserto instanceof Chart) {
    window.chartSemConserto.destroy();
  }

  const servicos = [
    "SEM REPARO - FORA DA GARANTIA",
    "SEM REPARO - SUBST. EM GARANTIA",
    "REJEITADO PELO CLIENTE"
  ];

  const datasets = servicos.map((srv, i) => {
    const mapa = breakdown?.[srv] || {};
    const valores = (labels || []).map(prod => mapa[prod] || 0);
    return {
      label: srv,
      data: valores,
      backgroundColor: CORES_SERVICO[i % CORES_SERVICO.length],
      borderColor: CORES_BORDA[i % CORES_BORDA.length],
      borderWidth: 1,
      maxBarThickness: 28
    };
  });

  window.chartSemConserto = new Chart(ctx, {
    type: "bar",
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
      plugins: {
        title: { display: true, text: "", padding: { top: 0, bottom: 20 } },
        legend: {
          position: "top",
          labels: { padding: 30, usePointStyle: true },
          onClick: (e, legendItem, legend) => {
            const chart = legend.chart;
            const index = legendItem.datasetIndex;
            const meta = chart.getDatasetMeta(index);

            // Toggle default do Chart.js
            meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
            chart.update();

            // Atualiza serviços ativos e re-render da tabela
            SERVICOS_ATIVOS = getServicosAtivosFromChart(chart);
            renderTabelaSemConserto(TABELA_SEM_CONSERTO_CACHE, SERVICOS_ATIVOS);
          }
        },
        tooltip: {
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${ctx.raw ?? 0}x`
          }
        },
        datalabels: {
          anchor: 'top',
          align: 'middle',
          offset: 8,
          clamp: true,
          formatter: v => (v > 0 ? `${v}` : ''),
          font: { size: 10, weight: 'bold' }
        }
      },
      layout: { padding: { top: 20 } },
      scales: {
        x: {
          stacked: true,
          ticks: { autoSkip: false, maxRotation: 90, minRotation: 45, font: { size: 10 } },
          title: { display: true, text: "Produtos" }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          title: { display: true, text: "Quantidade" }
        }
      }
    },
    plugins: (window.ChartDataLabels ? [ChartDataLabels] : [])
  });
}

/* ===== Função pública para carregar dados ===== */
window.carregarEquipSemConserto = function carregarEquipSemConserto(dataInicio, dataFim) {
  try {
    // exibe o container, se existir
    const cont = document.getElementById("graficosemconserto");
    if (cont) cont.style.display = "block";
  } catch (_) {}

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/qualidadePHP/sem_conserto_produtos.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ data_inicial: dataInicio, data_final: dataFim })
  })
  .then(res => {
    if (!res.ok) throw new Error("Erro na requisição: " + res.status);
    return res.json();
  })
  .then(({ ok, data, message }) => {
    if (!ok) throw new Error(message || "Falha ao obter dados.");

    // cache para a tabela
    TABELA_SEM_CONSERTO_CACHE = Array.isArray(data.tabela) ? data.tabela : [];

    // gráfico
    montarGraficoSemConserto({ labels: data.labels || [], breakdown: data.breakdown || {} });

    // dropdown + render inicial da tabela (já respeitando serviços ativos)
    popularModelosDropdown(data.modelos || []);
    SERVICOS_ATIVOS = getServicosAtivosFromChart(window.chartSemConserto);
    renderTabelaSemConserto(TABELA_SEM_CONSERTO_CACHE, SERVICOS_ATIVOS);

    // listener de mudança do filtro de modelo
    const select = document.getElementById("filtroModeloSemConserto");
    if (select && !select._listenerApplied) {
      select.addEventListener("change", () =>
        renderTabelaSemConserto(TABELA_SEM_CONSERTO_CACHE, SERVICOS_ATIVOS)
      );
      select._listenerApplied = true;
    }

    if (message) console.log("ℹ️", message);
  })
  .catch(err => {
    console.error("❌ Erro ao carregar sem conserto:", err);
    ensureTabelaEstrutura();
    const tbody = document.querySelector("#tabelaSemConserto tbody");
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="3" style="padding:8px;">Erro ao carregar os dados.</td></tr>`;
    }
  });
};
