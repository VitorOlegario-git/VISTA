<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar</title>
    <link rel="stylesheet" href="../CSS/consulta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="/localhost/FrontEnd/CSS/imagens/VISTA.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/CnpjMask.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://unpkg.com/html-docx-js/dist/html-docx.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-legend"></script>
</head>
<body>
<div class="main-container">
    <div class="top-container">
        <h1>Área para consulta</h1>
        <div class="input-group">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" placeholder="Digite o cnpj para consulta">
        </div>
        <div class="input-group">
            <label for="nota_fiscal">Nota fiscal</label>
            <input type="text" id="nota_fiscal" placeholder="Digite a nota fiscal para consulta">
        </div>
        <div class="input-group">
            <label for="imei">IMEI</label>
            <input type="text" id="imei" placeholder="Digite o imei para consulta">
        </div>
        <div class="input-group2">
            <label for="status">STATUS</label>
            <i class="fas fa-industry"></i>
            <select id="status" name="status" required>
                <option value="">Selecione o status para pesquisa</option>
                <option value="aguardando_nf">Aguardando nota fiscal de entrada</option>
                <option value="envio_analise">Aguardando análise</option>
                <option value="em_analise">Em análise</option>
                <option value="aguardando_pg">Aguardando pagamento do orçamento</option>
                <option value="aguardando_NF_retorno">Aguardando nota fiscal de retorno</option>
                <option value="analise_pendente">Em análise pendênte</option>
                <option value="em_reparo">Em reparo</option>
                <option value="reparo_pendente">Em reparo pendênte</option>
                <option value="inspecao_qualidade">Em inspeção na qualidade</option>
                <option value="envio_expedicao">Em expedição</option>
            </select>
        </div>
    <div class="export-buttons">
        <button onclick="exportarExcel()">
           <i class="fas fa-file-excel"></i> Exportar para Excel
        </button>
        <button onclick="exportarPDF()">
           <i class="fas fa-file-pdf"></i> Exportar para PDF
        </button>
        <button onclick="exportarDOCX()">
           <i class="fas fa-file-word"></i> Exportar para Word
        </button>
    </div>
        <button type="submit" id="consultar">Consultar</button>
        <button id="consultar-status">Consultar Saldo Geral</button>
        <button onclick="voltarComReload()">Voltar</button>
    </div>
    <div id="status-geral" class="resultado-container" style="margin-top: 20px;">
       <canvas id="grafico-status" width="400" height="100" style="display: none; margin-top: 20px;"></canvas>
    </div>
    <!-- ✅ NOVA DIV PARA RESULTADOS -->
    <div id="resultado-consulta" class="resultado-container"></div>
</div>
<!-- INPUT "CÓD. RASTREIO" REMOVIDO -->
<!-- JS Atualizado para não incluir o campo -->
<script>

function voltarComReload() {
    // Redireciona e força o recarregamento
    window.location.href = "/localhost/FrontEnd/html/PaginaPrincipal.php?reload=" + new Date().getTime();
}

document.getElementById("consultar").addEventListener("click", function () {
    const formData = new FormData();
    formData.append("cnpj", document.getElementById("cnpj").value);
    formData.append("nota_fiscal", document.getElementById("nota_fiscal").value);
    formData.append("imei", document.getElementById("imei").value); // IMEI incluso
    formData.append("status", document.getElementById("status").value);

    console.log("🔎 Enviando dados:", Object.fromEntries(formData.entries()));

    fetch("http://localhost/BackEnd/Consulta/consulta_resumo_geral.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        console.log("📥 Resposta bruta (status:", response.status, "):", response);
        return response.json();
    })
    .then(data => {
        console.log("✅ Dados JSON recebidos:", data);

        const container = document.getElementById("resultado-consulta");
        container.innerHTML = "";

        if (data.success && data.dados.length > 0) {
            let tabela = "<table><thead><tr>";
            tabela += "<th>ID</th><th>CNPJ</th><th>Razão Social</th><th>Nota Fiscal</th><th>Quantidade</th><th>Status</th><th>Data Última</th>";
            tabela += "</tr></thead><tbody>";

            data.dados.forEach(item => {
                tabela += "<tr>";
                tabela += `<td>${item.id}</td>`;
                tabela += `<td>${item.cnpj}</td>`;
                tabela += `<td>${item.razao_social}</td>`;
                tabela += `<td>${item.nota_fiscal}</td>`;
                tabela += `<td>${item.quantidade}</td>`;
                tabela += `<td>${item.status}</td>`;
                tabela += `<td>${item.data_ultimo_registro}</td>`;
                tabela += "</tr>";
            });

            tabela += "</tbody></table>";
            container.innerHTML = tabela;
        } else {
            console.warn("⚠️ Nenhum resultado encontrado ou erro:", data);
            container.innerHTML = "<p style='color: darkred;'>Nenhum resultado encontrado.</p>";
        }
    })
    .catch(error => {
        console.error("❌ Erro na requisição:", error);
        document.getElementById("resultado-consulta").innerHTML = "<p style='color: red;'>Erro na consulta.</p>";
    });
});

// 🔁 Essas funções DEVEM ficar fora do listener acima!

function exportarExcel() {
    const tabela = document.querySelector("#resultado-consulta table");
    if (!tabela) return alert("Nenhum dado para exportar.");

    const wb = XLSX.utils.table_to_book(tabela, { sheet: "Consulta" });
    XLSX.writeFile(wb, "consulta_resumo_geral.xlsx");
}

function exportarPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const tabela = document.querySelector("#resultado-consulta table");
    if (!tabela) return alert("Nenhum dado para exportar.");

    const headers = [...tabela.querySelectorAll("thead tr")].map(tr =>
        [...tr.cells].map(th => th.innerText)
    );

    const dados = [...tabela.querySelectorAll("tbody tr")].map(tr =>
        [...tr.cells].map(td => td.innerText)
    );

    doc.autoTable({
        head: headers,
        body: dados,
    });

    doc.save("consulta_resumo_geral.pdf");
}

function exportarDOCX() {
    const tabela = document.querySelector("#resultado-consulta table");
    if (!tabela) {
        alert("Nenhum dado para exportar.");
        return;
    }

    try {
        const html = `
            <!DOCTYPE html>
            <html xmlns:o='urn:schemas-microsoft-com:office:office'
                  xmlns:w='urn:schemas-microsoft-com:office:word'
                  xmlns='http://www.w3.org/TR/REC-html40'>
            <head>
                <meta charset='utf-8'>
                <title>Documento</title>
                <style>
                    table, th, td {
                        border: 1px solid black;
                        border-collapse: collapse;
                    }
                    th, td {
                        padding: 8px;
                        text-align: left;
                    }
                    h2 {
                        font-family: Arial, sans-serif;
                    }
                </style>
            </head>
            <body>
                <h2>Consulta de Resumo Geral</h2>
                ${tabela.outerHTML}
            </body>
            </html>`;

        const blob = window.htmlDocx.asBlob(html);
        saveAs(blob, 'consulta_resumo_geral.docx');

    } catch (error) {
        console.error("Erro ao exportar para Word:", error);
        alert("Erro ao exportar para Word. Verifique se todos os scripts foram carregados corretamente.");
    }
}


document.getElementById("consultar-status").addEventListener("click", function () {
    fetch("http://localhost/BackEnd/Consulta/consulta_status.php")
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("status-geral");
            container.innerHTML = "";

            if (data.success && data.dados) {
                Object.keys(data.dados).forEach(setor => {
                    const statusList = data.dados[setor];

                    const section = document.createElement("div");
                    section.style.marginBottom = "40px";

                    // Título
                    const title = document.createElement("h3");
                    title.innerText = `Setor: ${setor}`;
                    section.appendChild(title);

                    // Lista de status
                    const ul = document.createElement("ul");
                    const labels = [];
                    const valores = [];

                    statusList.forEach(item => {
                        const li = document.createElement("li");
                        li.innerHTML = `<strong>${item.status}:</strong> ${item.total}`;
                        ul.appendChild(li);

                        labels.push(item.status);
                        valores.push(item.total);
                    });

                    section.appendChild(ul);

                    // Canvas do gráfico
                    const canvas = document.createElement("canvas");
                    canvas.id = `grafico-${setor}`;
                    canvas.height = 300;
                    canvas.style.maxWidth = "600px";
                    section.appendChild(canvas);

                    container.appendChild(section);

                    // Gráfico de barras
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Quantidade',
                                data: valores,
                                backgroundColor: [
                                    'rgba(75, 192, 192, 0.6)',
                                    'rgba(255, 99, 132, 0.6)',
                                    'rgba(255, 205, 86, 0.6)',
                                    'rgba(54, 162, 235, 0.6)',
                                    'rgba(153, 102, 255, 0.6)',
                                    'rgba(201, 203, 207, 0.6)',
                                    'rgba(255, 159, 64, 0.6)',
                                    'rgba(100, 100, 255, 0.6)',
                                    'rgba(0, 200, 150, 0.6)',
                                    'rgba(200, 50, 150, 0.6)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                title: {
                                    display: true,
                                    text: `Distribuição de Remessas - ${setor}`
                                },
                                datalabels: {
                                    color: '#000',
                                    font: {
                                        weight: 'bold',
                                        size: 14
                                    },
                                    formatter: (value) => value
                                }
                            }
                        },
                        plugins: [ChartDataLabels]
                    });
                });
            } else {
                container.innerHTML = "<p style='color: darkred;'>Nenhum status encontrado.</p>";
            }
        })
        .catch(error => {
            console.error("Erro ao buscar os status:", error);
            document.getElementById("status-geral").innerHTML = "<p style='color: red;'>Erro ao carregar os status.</p>";
        });
});


</script>

</body>
</html>