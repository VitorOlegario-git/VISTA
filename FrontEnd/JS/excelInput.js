document.addEventListener("DOMContentLoaded", function () {
    let jsonData = [];

    // Importar dados do Excel
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

                updateFieldsFromJSON(jsonData);
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

    // Envia os dados para o PHP
    function saveToDatabase() {
        if (jsonData.length === 0) {
            alert("Importe os dados do Excel antes de cadastrar.");
            return;
        }

        const entradaId = document.getElementById("entrada_id").value.trim();
        if (!entradaId) {
            alert("ID de entrada não informado.");
            return;
        }

        const btn = document.getElementById("save-to-database");
        btn.disabled = true;
        btn.textContent = "Salvando...";

        const formData = new FormData();
        formData.append("jsonData", JSON.stringify(jsonData));
        formData.append("entrada_id", entradaId);

        fetch("http://localhost/BackEnd/Analise/salvar_dados_no_banco.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(response => {
            console.log("Resposta do servidor:", response);

            if (response.success) {
                alert("Dados salvos com sucesso!");
                setTimeout(() => {
                    // Redirecionamento mais seguro
                    window.location.href = "/localhost/BackEnd/cadastro_realizado.php";
                }, 1000);
            } else {
                alert("Erro ao salvar: " + (response.error || "Erro desconhecido."));
                btn.disabled = false;
                btn.textContent = "Cadastrar";
            }
        })
        .catch(err => {
            console.error("Erro de requisição:", err);
            alert("Erro ao comunicar com o servidor.");
            btn.disabled = false;
            btn.textContent = "Cadastrar";
        });
    }

    // Eventos
    document.getElementById("import-excel").addEventListener("click", importExcel);
    document.getElementById("save-to-database").addEventListener("click", saveToDatabase);
});
