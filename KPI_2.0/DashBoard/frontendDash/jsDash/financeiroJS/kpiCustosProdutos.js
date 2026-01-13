window.carregarCustosProdutos = async function (dataInicio, dataFim) {
  const di = dataInicio ?? document.getElementById("data_inicial")?.value ?? "";
  const df = dataFim    ?? document.getElementById("data_final")?.value   ?? "";

  const totalEl = document.getElementById("valorTotalCustos");
  const tabela  = document.getElementById("tabelaCustos");
  const theadRow = tabela?.querySelector("thead tr");
  const tbodyEl  = tabela?.querySelector("tbody");

  if (!totalEl || !tabela || !theadRow || !tbodyEl) return;

  totalEl.textContent = "Carregando...";
  tbodyEl.innerHTML = "";

  try {
    const resp = await fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/financeiroPHP/kpiCustosProdutos.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ data_inicial: di, data_final: df })
    });

    const texto = await resp.text();
    let dados;
    try {
      dados = JSON.parse(texto);
    } catch {
      console.error("Resposta não-JSON do backend:", texto);
      throw new Error("Resposta inválida do servidor (não é JSON).");
    }

    let total = 0;
    let produtos = [];
    let mensagem = "";

    if (dados && Array.isArray(dados.produtos)) {
      total = Number(dados.total || 0);
      produtos = dados.produtos;
      mensagem = dados.mensagem || "";
    } else if (dados && dados.ok && dados.data) {
      total = Number(dados.data.total || 0);
      produtos = Array.isArray(dados.data.produtos) ? dados.data.produtos : [];
      mensagem = dados.message || "";
    } else {
      throw new Error("Estrutura inesperada de resposta do backend.");
    }

    if (!produtos.length) {
      totalEl.textContent = mensagem || "Nenhum dado encontrado.";
      return;
    }

    totalEl.textContent = `💰 Valor Total (Reparo Fora da Garantia): R$ ${total.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`;

    // garante o cabeçalho com a coluna "Total (Somado)"
    if (theadRow && ![...theadRow.children].some(th => th.textContent.includes("Total (Somado)"))) {
      const thTotal = document.createElement("th");
      thTotal.textContent = "Total (Somado)";
      theadRow.insertBefore(thTotal, theadRow.children[2]);
    }

    // cria/recupera tfoot para o resumo
    let tfoot = tabela.querySelector("tfoot");
    if (!tfoot) {
      tfoot = document.createElement("tfoot");
      tabela.appendChild(tfoot);
    }
    tfoot.innerHTML = "";

    // acumuladores
    let somaTabela = 0;
    let qtdComPreco = 0;
    let qtdSemPreco = 0;

    const frag = document.createDocumentFragment();

    produtos.forEach(p => {
      const valorUnitNum = (p.valor_unit == null) ? null : Number(p.valor_unit);
      const qtdSomadoNum = Number(p.qtd_somado ?? 0);

      const valorSomadoNum = (p.valor_somado != null)
        ? Number(p.valor_somado) || 0
        : (Number(valorUnitNum ?? 0) * qtdSomadoNum);

      somaTabela += valorSomadoNum;

      if (valorUnitNum == null) qtdSemPreco++; else qtdComPreco++;

      const vUnitFmt = (valorUnitNum == null)
        ? "<em>Sem preço</em>"
        : `R$ ${valorUnitNum.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`;

      let servicosTxt = "—";
      if (Array.isArray(p.servicos)) servicosTxt = p.servicos.join(", ");
      else if (typeof p.servicos === "string") servicosTxt = p.servicos;

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${p.produto ?? "—"}</td>
        <td>${vUnitFmt}</td>
        <td style="text-align:right;">R$ ${valorSomadoNum.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</td>
        <td>${p.qtd_somado ?? 0}</td>
        <td>${p.qtd_nao_somado ?? 0}</td>
        <td>${servicosTxt}</td>
      `;
      frag.appendChild(tr);
    });

    tbodyEl.appendChild(frag);

    // rodapé com totais
    const trFoot = document.createElement("tr");
    trFoot.innerHTML = `
      <td colspan="2" style="font-weight:bold;">Total (Tabela)</td>
      <td style="text-align:right; font-weight:bold;">R$ ${somaTabela.toLocaleString("pt-BR", { minimumFractionDigits: 2 })}</td>
      <td colspan="3">
          <span style="margin-right:12px;">Com preço: <strong>${qtdComPreco}</strong></span>
          <span>Sem preço: <strong>${qtdSemPreco}</strong></span>
      </td>
    `;
    tfoot.appendChild(trFoot);

  } catch (error) {
    console.error("Erro ao carregar custos de produtos:", error);
    totalEl.textContent = "Erro ao carregar dados.";
  }
};
