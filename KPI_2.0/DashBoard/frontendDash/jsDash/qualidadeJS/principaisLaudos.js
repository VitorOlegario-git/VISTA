function carregarPrincipaisLaudos(dataInicio, dataFim, modelo = "") {
    console.log("🔍 Buscando laudos técnicos...", { dataInicio, dataFim, modelo });

    fetch("https://kpi.stbextrema.com.br/DashBoard/backendDash/qualidadePHP/principaisLaudos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `data_inicial=${encodeURIComponent(dataInicio)}&data_final=${encodeURIComponent(dataFim)}&modelo=${encodeURIComponent(modelo)}`
    })
    .then(res => {
        if (!res.ok) throw new Error("Erro na requisição: " + res.status);
        return res.json();
    })
    .then(data => {
        console.log("✅ Laudos recebidos:", data);

        const select = document.getElementById("filtroModelo");
        const tbody = document.querySelector("#tabelaLaudos tbody");

        // Atualiza o SELECT de modelos (somente se veio lista de modelos)
        if (data.modelos && Array.isArray(data.modelos)) {
            select.innerHTML = '<option value="">Todos os modelos</option>';
            data.modelos.forEach(mod => {
                const opt = document.createElement("option");
                opt.value = mod;
                opt.textContent = mod;
                if (mod === data.modeloSelecionado) opt.selected = true;
                select.appendChild(opt);
            });
        }

        // Atualiza a tabela
        tbody.innerHTML = "";

        if (!data.laudos || data.laudos.length === 0) {
            tbody.innerHTML = "<tr><td colspan='3'>Nenhum dado encontrado.</td></tr>";
            return;
        }

        // Cabeçalho dinâmico
        const thead = document.querySelector("#tabelaLaudos thead");
        if (modelo) {
            thead.innerHTML = "<tr><th>Laudo</th><th>Quantidade</th></tr>";
        } else {
            thead.innerHTML = "<tr><th>Modelo</th><th>Laudo</th><th>Quantidade</th></tr>";
        }

        // Preenche as linhas
        data.laudos.forEach(item => {
            const row = document.createElement("tr");

            if (modelo) {
                row.innerHTML = `
                    <td>${item.laudo}</td>
                    <td>${item.total}</td>
                `;
            } else {
                row.innerHTML = `
                    <td>${item.modelo}</td>
                    <td>${item.laudo}</td>
                    <td>${item.total}</td>
                `;
            }

            tbody.appendChild(row);
        });

        // Reaplica ação ao mudar o modelo
        select.onchange = () => {
            carregarPrincipaisLaudos(dataInicio, dataFim, select.value);
        };
    })
    .catch(err => {
        console.error("❌ Erro ao carregar laudos:", err);
    });
}
