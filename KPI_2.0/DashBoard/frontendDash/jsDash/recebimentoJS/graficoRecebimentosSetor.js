let chartRecebimentosSetor = null;

function carregarGraficoSetor(dataInicio, dataFim) {
  console.log("Enviando requisição de Recebimentos por Setor:", { dataInicio, dataFim });

  fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/recebimentoPHP/recebimentos_por_setor.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `data_inicial=${dataInicio}&data_final=${dataFim}`
  })
  .then(response => {
    if (!response.ok) throw new Error("Erro na requisição: " + response.status);
    return response.json();
  })
  .then(data => {
    const container = document.getElementById("recebimentosSetor");

    if (!data.dados || data.dados.length === 0) {
      container.innerHTML = "<p>Nenhum dado disponível para o período selecionado.</p>";
      return;
    }

    const setores = data.dados.map(item => item.setor);
    const valores = data.dados.map(item => item.total_pecas);

    if (chartRecebimentosSetor instanceof Chart) chartRecebimentosSetor.destroy();

    // Estrutura: gráfico + tabela
    container.style.overflow = "visible";
    container.innerHTML = `
      <div style="height:380px;margin-bottom:16px;">
        <canvas id="graficoSetor" style="height:100%;display:block;"></canvas>
      </div>

      <div class="tabela-remessas">
        <h4 style="margin:4px 0 10px 0;">Remessas do período</h4>
        <div style="max-height:320px;overflow:auto;border:1px solid #e3e3e3;border-radius:8px;">
          <table id="tabelaRemessasSetor" style="width:100%;border-collapse:collapse;">
            <thead>
              <tr>
                <th style="position:sticky;top:0;background:#f7f7f7;">Data</th>
                <th style="position:sticky;top:0;background:#f7f7f7;">CNPJ</th>
                <th style="position:sticky;top:0;background:#f7f7f7;">Razão Social</th>
                <th style="position:sticky;top:0;background:#f7f7f7;">NF</th>
                <th style="position:sticky;top:0;background:#f7f7f7;">Setor</th>
                <th style="position:sticky;top:0;background:#f7f7f7;text-align:right;">Quantidade</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <p id="totalRemessasInfo" style="margin-top:8px;font-weight:600;"></p>
      </div>
    `;

    // === Gráfico ===
    const ctx = document.getElementById("graficoSetor").getContext("2d");
    chartRecebimentosSetor = new Chart(ctx, {
      type: "bar",
      data: {
        labels: setores,
        datasets: [{
          label: "Recebimentos por setor",
          data: valores,
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
          legend: { display: true, position: "bottom" },
          datalabels: {
            anchor: 'end',
            align: 'top',
            color: '#000',
            font: { weight: 'bold' },
            formatter: Math.round
          },
          tooltip: {
            callbacks: {
              label: (tooltipItem) => `Peças: ${tooltipItem.raw}`
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            title: { display: true, text: 'Quantidade' }
          },
          x: {
            title: { display: false },
            ticks: { autoSkip: false, maxRotation: 45 }
          }
        }
      },
      plugins: [ChartDataLabels]
    });

    // === Tabela de remessas ===
    const tbody = container.querySelector("#tabelaRemessasSetor tbody");
    const remessas = Array.isArray(data.remessas) ? data.remessas : [];
    let totalQtde = 0;

    const fmtCNPJ = c => c ? c.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, "$1.$2.$3/$4-$5") : "";
    const escape = (s) => {
      const el = document.createElement("div");
      el.textContent = s ?? "";
      return el.innerHTML;
    };

    remessas.forEach(r => {
      totalQtde += Number(r.quantidade || 0);
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escape(r.data_recebimento)}</td>
        <td>${escape(fmtCNPJ(r.cnpj))}</td>
        <td>${escape(r.razao_social)}</td>
        <td>${escape(r.nota_fiscal)}</td>
        <td>${escape(r.setor)}</td>
        <td style="text-align:right;">${Number(r.quantidade || 0)}</td>
      `;
      // linhas zebrada + borda suave
      tr.style.borderBottom = "1px solid #eee";
      tbody.appendChild(tr);
    });

    container.querySelector("#totalRemessasInfo").textContent =
      `Total de remessas listadas: ${remessas.length} | Quantidade somada: ${totalQtde}`;
  })
  .catch(error => {
    console.error("Erro ao carregar gráfico de setor:", error);
    document.getElementById("recebimentosSetor").innerHTML = "<p>Erro ao carregar os dados.</p>";
  });
}
