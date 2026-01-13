// =====================
// KPI: Orçamentos Gerados
// Exibe uma tabela com base em filtro por datas e mostra valor total
// =====================

function carregarOrcamentosGeradosReparo(dataInicio, dataFim) {
    const container = document.getElementById("orcamentosGeradosContainerReparo");
    const valorTotalEl = document.getElementById("valorTotalOrcamentos");

    if (!container || !valorTotalEl) return;

    container.querySelector(".tabela-laudos")?.remove();
    valorTotalEl.textContent = "Carregando...";

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/financeiroPHP/orcamentosGeradosReparo.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}`
    })
    .then(res => res.json())
    .then(json => {
        if (!json.ok) throw new Error(json.message || "Erro ao buscar dados");
        exibirTabelaOrcamentosReparo(json.data);
    })
    .catch(err => {
        console.error("Erro ao buscar orçamentos:", err);
        valorTotalEl.textContent = "Erro ao carregar dados.";
        container.innerHTML += `<p style="color:red; padding:8px;">Erro ao carregar os dados.</p>`;
    });
}

function exibirTabelaOrcamentosReparo(linhas) {
    const container = document.getElementById("orcamentosGeradosContainerReparo");
    const valorTotalEl = document.getElementById("valorTotalOrcamentosReparo");

    if (!container || !valorTotalEl) return;

    if (!Array.isArray(linhas) || linhas.length === 0) {
        valorTotalEl.textContent = "";
        container.innerHTML += `<p style="padding:8px;">Nenhum orçamento encontrado no período.</p>`;
        return;
    }

    const total = linhas.reduce((acc, l) => {
        const valorLimpo = ("" + l.valor_orcamento).replace(/\./g, "").replace(",", ".");
        const valorFloat = parseFloat(valorLimpo);
        return acc + (isNaN(valorFloat) ? 0 : valorFloat);
    }, 0);

    valorTotalEl.textContent = `💰 Valor Total: R$ ${total.toLocaleString("pt-BR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;

    const tabela = document.createElement("table");
    tabela.id = "tabelaOrcamentosReparo";
    tabela.className = "tabela-orcamentos";
    tabela.innerHTML = `
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Nota Fiscal</th>
                <th>Nº Orçamento</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            ${linhas.map(l => `
                <tr>
                    <td>${l.razao_social}</td>
                    <td>${l.nota_fiscal}</td>
                    <td>${l.numero_orcamento}</td>
                    <td style="text-align: right;">R$ ${l.valor_orcamento}</td>
                </tr>
            `).join("")}
        </tbody>
    `;

    let tabelaWrapper = container.querySelector(".tabela-laudos");
    if (!tabelaWrapper) {
        tabelaWrapper = document.createElement("div");
        tabelaWrapper.className = "tabela-laudos";
        container.appendChild(tabelaWrapper);
    }

    tabelaWrapper.innerHTML = "";
    tabelaWrapper.appendChild(tabela);
}


// 🔁 Mapeamento para integração com seu dashboard
const graficosFinanceiroReparo = [
    {
        linkId: "orcamentos_gerados_reparo",
        containerIds: ["orcamentosGeradosContainerReparo"],
        funcao: carregarOrcamentosGeradosReparo
    }
];

const todosContainersFinanceiroReparo = [...new Set(graficosFinanceiroReparo.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

function destacarBotaoGraficoAtivoFinanceiro(botaoClicado) {
    graficosFinanceiroReparo.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

function ocultarTodosOsContainersFinanceiro() {
    todosContainersFinanceiroReparo.forEach(container => {
        container.style.display = "none";
    });
}

function obterFiltrosFinanceiro() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || ""
    };
}

// 🔁 Inicializa eventos de clique
graficosFinanceiroReparo.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersFinanceiro();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoFinanceiro(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim } = obterFiltrosFinanceiro();
            funcao(dataInicio, dataFim);
        }
    });
});
