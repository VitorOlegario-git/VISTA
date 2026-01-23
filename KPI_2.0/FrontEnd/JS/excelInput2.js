document.addEventListener("DOMContentLoaded", function () {
    let jsonData = [];

    // ======== FEEDBACK VISUAL: Monitorar seleção de arquivo ========
    const fileInput = document.getElementById("excel-file");
    const fileNameDisplay = document.getElementById("file-name-display");
    
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener("change", function() {
            if (this.files && this.files.length > 0) {
                fileNameDisplay.textContent = this.files[0].name;
                fileNameDisplay.style.color = "#28a745";
                fileNameDisplay.style.fontWeight = "600";
            } else {
                fileNameDisplay.textContent = "Nenhum arquivo selecionado";
                fileNameDisplay.style.color = "#666";
                fileNameDisplay.style.fontWeight = "normal";
            }
        });
    }

    // Importar dados do Excel com validação de colunas
    function importExcel() {
        const fileInput = document.getElementById("excel-file");
        if (!fileInput.files || fileInput.files.length === 0) {
            alert("Por favor, selecione um arquivo Excel.");
            return;
        }

        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });

                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                jsonData = XLSX.utils.sheet_to_json(worksheet);

                if (jsonData.length === 0) {
                    alert("O arquivo Excel está vazio ou inválido.");
                    return;
                }

                // Validação das colunas esperadas
                const colunasEsperadas = [
                    "IMEI", "MODELO", "GARANTIA", "IMEI_DEVOL", "RECLAMAÇÃO",
                    "PRODUTO", "SERVIÇO", "OCORRÊNCIA", "COND. GARANTIA VIOLADA", "ORÇAM"
                ];

                const colunasExcel = Object.keys(jsonData[0]);
                const colunasFaltando = colunasEsperadas.filter(c => !colunasExcel.includes(c));
                const colunasExtras = colunasExcel.filter(c => !colunasEsperadas.includes(c));

                if (colunasFaltando.length > 0 || colunasExtras.length > 0) {
                    alert("Erro: O arquivo Excel contém colunas inválidas.\n" +
                        (colunasFaltando.length > 0 ? "Faltando: " + colunasFaltando.join(", ") + "\n" : "") +
                        (colunasExtras.length > 0 ? "Colunas não esperadas: " + colunasExtras.join(", ") : ""));
                    return;
                }

                updateFieldsFromJSON(jsonData);
                
                // ======== FEEDBACK VISUAL: Mostrar seções de preview e ação ========
                const previewSection = document.getElementById("preview-section");
                const actionSection = document.getElementById("action-section");
                const rowCount = document.getElementById("row-count");
                
                if (previewSection) previewSection.style.display = "block";
                if (actionSection) actionSection.style.display = "block";
                if (rowCount) rowCount.textContent = `${jsonData.length} registros carregados`;
                
            } catch (err) {
                console.error("Erro ao ler o arquivo Excel:", err);
                alert("Erro ao processar o arquivo Excel.");
            }
        };

        reader.readAsArrayBuffer(file);
    }

    // Atualiza a visualização da tabela
    function updateFieldsFromJSON(data) {
        const imeiList = document.getElementById("imei-list");
        imeiList.innerHTML = "";

        const categories = {};
        data.forEach(entry => {
            for (let category in entry) {
                if (!categories[category]) categories[category] = [];
                categories[category].push(entry[category]);
            }
        });

        let resultHTML = "<table><tr>";
        for (let category in categories) resultHTML += `<th>${category}</th>`;
        resultHTML += "</tr>";

        const maxEntries = Math.max(...Object.values(categories).map(col => col.length));
        for (let i = 0; i < maxEntries; i++) {
            resultHTML += "<tr>";
            for (let category in categories) {
                const value = categories[category][i] || "";
                resultHTML += `<td>${value}</td>`;
            }
            resultHTML += "</tr>";
        }

        resultHTML += "</table>";
        imeiList.innerHTML = resultHTML;
    }

    // Envia os dados como JSON para o PHP
    function saveToDatabase() {
        if (jsonData.length === 0) {
            alert("Importe os dados do Excel antes de cadastrar.");
            return;
        }

        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        // VALIDAÇÃO: VERIFICAR SE ORÇAM ESTÁ SEMPRE PREENCHIDO
        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        const linhasComOrcamentoVazio = jsonData.filter(item =>
            !item["ORÇAM"] || item["ORÇAM"].toString().trim() === ""
        );

        if (linhasComOrcamentoVazio.length > 0) {
            alert("Existem linhas sem o valor de ORÇAM preenchido. Complete antes de salvar.");
            return;
        }
        // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

        const entradaId = document.getElementById("entrada_id").value.trim();
        if (!entradaId) {
            alert("ID de entrada não informado.");
            return;
        }

        const btn = document.getElementById("save-to-database");
        const btnText = btn.querySelector(".btn-text");
        const btnLoading = btn.querySelector(".btn-loading");
        
        // ======== FEEDBACK VISUAL: Ativar loading ========
        btn.disabled = true;
        if (btnText) btnText.style.display = "none";
        if (btnLoading) btnLoading.style.display = "flex";

        // Use public router so the request works regardless of server root or rewrite rules
        fetch("/router_public.php?url=reparo/salvar-apontamentos-pos-analise", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                jsonData: jsonData,
                entrada_id: entradaId
            })
        })
        .then(async res => {
            const text = await res.text();

            try {
                const response = JSON.parse(text);
                console.log("Resposta do servidor:", response);

                if (response.success) {
                    alert("Dados salvos com sucesso!");
                    setTimeout(() => {
                        // Redirect through public router to avoid 404 when URL rewriting is not configured
                        window.location.href = '/router_public.php?url=cadastro-realizado';
                    }, 1000);
                } else {
                    alert("Erro ao salvar: " + (response.error || "Erro desconhecido."));
                    // ======== FEEDBACK VISUAL: Restaurar botão ========
                    btn.disabled = false;
                    if (btnText) btnText.style.display = "inline";
                    if (btnLoading) btnLoading.style.display = "none";
                }
            } catch (e) {
                console.error("Erro ao interpretar JSON:", e);
                console.warn("Resposta bruta do servidor:", text);
                alert("O servidor retornou uma resposta inválida:\n\n" + text);
                // ======== FEEDBACK VISUAL: Restaurar botão ========
                btn.disabled = false;
                if (btnText) btnText.style.display = "inline";
                if (btnLoading) btnLoading.style.display = "none";
            }
        })
        .catch(err => {
            console.error("Erro de rede ou fetch():", err);
            alert("Erro de rede. Verifique sua conexão ou se o servidor está online.");
            // ======== FEEDBACK VISUAL: Restaurar botão ========
            btn.disabled = false;
            if (btnText) btnText.style.display = "inline";
            if (btnLoading) btnLoading.style.display = "none";
        });
    }

    // Eventos
    document.getElementById("import-excel").addEventListener("click", importExcel);
    document.getElementById("save-to-database").addEventListener("click", saveToDatabase);
});
