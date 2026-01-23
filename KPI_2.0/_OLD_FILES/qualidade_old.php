<?php

session_start();

// Use apenas:
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");


$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
  session_unset();
  session_destroy();
  header("Location: /router_public.php?url=login");
  exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
  header("Location: /router_public.php?url=login");
  exit();
}

$_SESSION['last_activity'] = time();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Qualidade</title>
    <link rel="stylesheet" href="../CSS/qualidade.css">
    <link rel="icon" href="/FrontEnd/CSS/imagens/VISTA.png">

    <style>
        .button-group2 button.ativo {
            background-color: #1d3557;
            color: white;
            font-weight: bold;
            transform: scale(1.05);
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../JS/CnpjMask.js"></script>
</head>
<body>
<div class="qualidade-container">
    <form id="form-qualidade">
        <div id="loading" style="display: none;"></div>
        <div id="mensagemErro"></div>

        <!-- Inputs -->
        <div class="input-group1">
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" required oninput="applyCNPJMask(this);" maxlength="18" placeholder="Digite o CNPJ" readonly>
        </div>
        <div class="input-group2">
            <label for="nota_fiscal">NF</label>
            <input type="text" id="nota_fiscal" name="nota_fiscal" required placeholder="Nota fiscal de entrada" readonly>
        </div>
        <div class="input-group3">
            <label for="data_inicio_qualidade">Data do início da inspeção</label>
            <input type="date" id="data_inicio_qualidade" name="data_inicio_qualidade" required>
        </div>
        <div class="input-group4">
            <label for="data_envio_expedicao">Data do envio para expedição</label>
            <input type="date" id="data_envio_expedicao" name="data_envio_expedicao">
        </div>
        <div class="input-group5">
            <label for="razao_social">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" required placeholder="Razão Social do cliente" readonly>
        </div>
        <div class="input-group6">
            <label for="quantidade">Quantidade Total</label>
            <input type="number" id="quantidade" name="quantidade" required placeholder="Quantidade total de peças" readonly>
        </div>
        <div class="input-group7">
            <label for="quantidade_parcial">Quantidade Parcial</label>
            <input type="number" id="quantidade_parcial" name="quantidade_parcial" placeholder="Quantidade parcial analisada" readonly>
        </div>
        <div class="input-group8">
            <label for="operacao_origem">Operação Origem</label>
            <select id="operacao_origem" name="operacao_origem" required>
                <option value="">Selecione</option>
                <option value="aguardando_NF_retorno">Aguardando NF de retorno</option>
                <option value="inspecao_qualidade">Envio qualidade</option>
            </select>
        </div>
        <div class="input-group9">
            <label for="operacao_destino">Operação Destino</label>
            <select id="operacao_destino" name="operacao_destino" required>
                <option value="">Selecione</option>
                <option value="inspecao_qualidade">Envio qualidade</option>
                <option value="envio_expedicao">Enviado para expedicao</option>
            </select>
        </div>

        <div class="input-group10" readonly>
                <label for="setor">Setor</label>
                <i class="fas fa-industry"></i>
                <select id="setor" name="setor" required>
                    <option value="">Selecione o setor</option>
                    <option value="manut-varejo">Manutenção Varejo</option>
                    <option value="dev-varejo">Devolução Varejo</option>
                    <option value="manut-datora">Manutenção Datora</option>
                    <option value="manut-lumini">Manutenção Lumini</option>
                    <option value="dev-datora">Devolução Datora</option>
                    <option value="dev-lumini">Devolução Lumini</option>
                    
                </select>
            </div>
        <div class="input-group11" style="display: none;">
            <label for="operador">Operador</label>
            <input type="text" id="operador" name="operador" value="<?php echo $_SESSION['username'] ?? ''; ?>" readonly>
        </div>
        <div class="input-group12">
            <label for="obs">Observações</label>
            <textarea id="obs" name="obs" rows="4"></textarea>
        </div>

        <div class="button-group">
            <button type="submit">Cadastrar</button>
            <button type="button" onclick="voltarComReload()">Voltar</button>
        </div>
        <div class="input-group13">
            <label for="nota_fiscal_retorno">NF de retorno</label>
            <input type="text" id="nota_fiscal_retorno" name="nota_fiscal_retorno" required placeholder="Nota fiscal de retorno">
        </div>
    </form>

    <!-- Tabelas -->
    <div class="container-informacao">
        <div class="button-group2">
            <button type="button" id="btn-aguardando-nf-retorno">Aguardando NF de retorno</button>
            <button type="button" id="btn-setor-qualidade">Setor de Qualidade</button>
    </div>

        <input type="text" id="filtro-nf" placeholder="Pesquisar por NF entrada / retorno" class="filtro-nf-input">

        <table id="tabela-info-aguardando-nf-retorno" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Quantidade</th><th>Quantidade Parcial</th><th>Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <table id="tabela-info-em-inspecao" style="display: none;">
            <thead>
                <tr>
                    <th>Setor</th><th>CNPJ</th><th>Razão Social</th><th>NF</th>
                    <th>Data do envio para a qualidade</th><th>Quantidade</th><th>Quantidade Parcial</th><th>Status</th>
                    <th>Numero da NF de retorno</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <!-- Alerta grande global -->
<div id="mensagemAlerta" class="big-alert hidden" role="alert" aria-live="assertive">
  <div class="big-alert-box" tabindex="-1">
    <button class="big-alert-close" aria-label="Fechar">&times;</button>
    <div class="big-alert-title">⚠️ Atenção</div>
    <div class="big-alert-message"></div>
    <div class="big-alert-actions">
      <button id="bigAlertConfirm">Confirmar</button>
      <button id="bigAlertCancel">Cancelar</button>
    </div>
  </div>
</div>

    
<script>

function voltarComReload() {
  window.top.location.href = "/router_public.php?url=dashboard&reload=" + new Date().getTime();
}

let dadosAguardandoNFRetorno = [];
let dadosQualidade = [];

document.addEventListener("DOMContentLoaded", function () {
  // elementos principais
  const form = document.getElementById("form-qualidade");
  if (!form) { console.error("form-qualidade não encontrado"); return; }

  const mensagemErro = document.getElementById("mensagemErro");
  const mensagemAlertaInline = document.getElementById("mensagemAlertaInline"); // alerta pequeno
  // o modal grande permanece com id "mensagemAlerta" (se existir no HTML). showBigAlert cria um se precisar.

  const opOrigem = document.getElementById("operacao_origem");
  const opDestino = document.getElementById("operacao_destino");

  const btnAguardandoNfRetorno = document.getElementById('btn-aguardando-nf-retorno');
  const btnQualidade = document.getElementById('btn-setor-qualidade');
  const tabelaAguardando = document.getElementById('tabela-info-aguardando-nf-retorno');
  const tabelaQualidade = document.getElementById('tabela-info-em-inspecao');

  // ========= Utilitários de alerta (inline) =========
  function mostrarAlertaSubstituicaoInline(imeis = []) {
    const detalhe = (imeis && imeis.length)
      ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>`
      : "";
    if (mensagemAlertaInline) {
      mensagemAlertaInline.innerHTML = `⚠️ Existe equipamento substituído na remessa. Confira se está na caixa.${detalhe}`;
      mensagemAlertaInline.style.display = "block";
    } else {
      // fallback simples no console
      console.warn("Alerta inline não encontrado:", detalhe);
    }
  }
  function esconderAlertaSubstituicaoInline() {
    if (mensagemAlertaInline) {
      mensagemAlertaInline.style.display = "none";
      mensagemAlertaInline.innerHTML = "";
    }
  }

  // ========= Regra de origem/destino =========
  opOrigem.addEventListener("change", function () {
    if (opOrigem.value === "aguardando_NF_retorno") {
      opDestino.innerHTML = '';
      const opt = document.createElement("option");
      opt.value = "inspecao_qualidade";
      opt.textContent = "Envio qualidade";
      opDestino.appendChild(opt);
    } else {
      opDestino.innerHTML = `
        <option value="">Selecione</option>
        <option value="inspecao_qualidade">Envio qualidade</option>
        <option value="envio_expedicao">Enviado para expedição</option>
      `;
    }
  });

  // ========= showBigAlert robusto (cria modal se necessário) =========
  function showBigAlert({ title = "Atenção", message = "", detalheHtml = "", requireConfirm = true } = {}) {
    // cria o modal no DOM se não existir
    function ensureModalExists() {
      let wrapper = document.getElementById("mensagemAlerta");
      if (wrapper) return wrapper;

      wrapper = document.createElement("div");
      wrapper.id = "mensagemAlerta";
      wrapper.className = "big-alert hidden";
      wrapper.setAttribute("role", "alert");
      wrapper.setAttribute("aria-live", "assertive");
      wrapper.style.position = "fixed";
      wrapper.style.inset = "0";
      wrapper.style.display = "none";
      wrapper.style.alignItems = "center";
      wrapper.style.justifyContent = "center";
      wrapper.style.background = "rgba(0,0,0,0.55)";
      wrapper.style.zIndex = "99999";
      wrapper.style.padding = "20px";

      wrapper.innerHTML = `
        <div class="big-alert-box" tabindex="-1" role="dialog" aria-modal="true"
             style="background:#fff;border:6px solid #ff4d4d;color:#8b0000;width:min(980px,95%);border-radius:12px;padding:28px;box-shadow:0 10px 40px rgba(0,0,0,0.4);text-align:center;outline:none;position:relative;">
          <button class="big-alert-close" aria-label="Fechar" style="position:absolute;right:12px;top:8px;background:transparent;border:none;font-size:28px;color:#8b0000;cursor:pointer;">&times;</button>
          <div class="big-alert-title" style="font-size:34px;font-weight:800;margin-bottom:8px;">Atenção</div>
          <div class="big-alert-message" style="font-size:20px;font-weight:600;line-height:1.3;margin-top:6px;max-height:36vh;overflow:auto;"></div>
          <div class="big-alert-actions" style="margin-top:18px;display:flex;gap:12px;justify-content:center;">
            <button id="bigAlertConfirm" style="padding:12px 20px;font-size:18px;border-radius:8px;border:2px solid #8b0000;background:#fff;color:#8b0000;cursor:pointer;min-width:140px;">Confirmar</button>
            <button id="bigAlertCancel" style="padding:12px 20px;font-size:18px;border-radius:8px;border:2px solid #8b0000;background:#fff;color:#8b0000;cursor:pointer;min-width:140px;">Cancelar</button>
          </div>
        </div>
      `;
      document.body.appendChild(wrapper);
      return wrapper;
    }

    return new Promise((resolve) => {
      try {
        const wrapper = ensureModalExists();
        const box = wrapper.querySelector(".big-alert-box") || wrapper;
        const titleEl = wrapper.querySelector(".big-alert-title");
        const messageEl = wrapper.querySelector(".big-alert-message");
        const confirmBtn = wrapper.querySelector("#bigAlertConfirm");
        const cancelBtn = wrapper.querySelector("#bigAlertCancel");
        const closeBtn = wrapper.querySelector(".big-alert-close");

        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.innerHTML = `<div style="font-weight:700">${String(message)}</div>${detalheHtml || ''}`;

        // mostrar
        wrapper.style.display = "flex";
        wrapper.classList.remove("hidden");
        if (box && typeof box.focus === "function") box.focus();

        function cleanup(result) {
          try { wrapper.style.display = "none"; } catch (_) {}
          if (confirmBtn) confirmBtn.removeEventListener("click", onConfirm);
          if (cancelBtn) cancelBtn.removeEventListener("click", onCancel);
          if (closeBtn) closeBtn.removeEventListener("click", onCancel);
          document.removeEventListener("keydown", onKey);
          resolve(Boolean(result));
        }
        function onConfirm() { cleanup(true); }
        function onCancel() { cleanup(false); }
        function onKey(e) {
          if (e.key === "Escape") onCancel();
          if (e.key === "Enter" && requireConfirm) onConfirm();
        }

        if (confirmBtn) confirmBtn.addEventListener("click", onConfirm);
        if (cancelBtn) cancelBtn.addEventListener("click", onCancel);
        if (closeBtn) closeBtn.addEventListener("click", onCancel);
        document.addEventListener("keydown", onKey);

      } catch (err) {
        console.error("showBigAlert fallback (confirm):", err);
        const fallback = window.confirm(message + (detalheHtml ? "\n\n" + detalheHtml.replace(/<[^>]*>?/gm, '') : ''));
        resolve(Boolean(fallback));
      }
    });
  }

  // util para mostrar erro simples na UI
  function showError(msg) {
    console.error(msg);
    if (mensagemErro) mensagemErro.innerText = msg;
  }

  // ========= SUBMIT (pré-check -> confirmar -> salvar) =========
  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    if (mensagemErro) mensagemErro.innerText = "";

    const formData = new FormData(this);
    const checkData = new FormData(this);
    checkData.set("only_check", "1");

    try {
      // PRE-CHECK
      let res = await fetch("/BackEnd/Qualidade/Qualidade.php", {
        method: "POST",
        body: checkData
      });
      const text = await res.text();
      console.log("PRECHECK RAW:", text);

      if (!res.ok) {
        showError("Falha no servidor (pré-check): " + res.status);
        return;
      }

      let jsonCheck;
      try { jsonCheck = JSON.parse(text); } catch (err) {
        console.error("JSON inválido (pré-check):", err, text);
        showError("Resposta inválida do servidor (pré-check).");
        return;
      }

      // Se substituição detectada: mostrar modal grande para confirmar
      if (jsonCheck.substitution && jsonCheck.substitution.checked) {
        if (jsonCheck.substitution.has_substitution) {
          const imeis = Array.isArray(jsonCheck.substitution.imeis) ? jsonCheck.substitution.imeis : [];
          const detalhe = imeis.length ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>` : "";

          // Mostrar também o alerta inline (opcional)
          mostrarAlertaSubstituicaoInline(imeis);

          // pedir confirmação com modal grande
          const confirmado = await showBigAlert({
            title: "Existe equipamento substituído na remessa",
            message: "Existe equipamento substituído na remessa. Confira se está na caixa.",
            detalheHtml: detalhe,
            requireConfirm: true
          });
          if (!confirmado) {
            // usuário cancelou
            return;
          }
        } else {
          esconderAlertaSubstituicaoInline();
        }
      }

      // SAVE
      let resSave = await fetch("/BackEnd/Qualidade/Qualidade.php", {
        method: "POST",
        body: formData
      });
      let textSave = await resSave.text();
      console.log("SAVE RAW:", textSave);

      if (!resSave.ok) {
        showError("Falha no servidor: " + resSave.status);
        return;
      }

      let jsonSave;
      try { jsonSave = JSON.parse(textSave); } catch (err) {
        console.error("JSON inválido (save):", err, textSave);
        showError("Resposta inválida do servidor.");
        return;
      }

      // manter alerta visível após salvar caso necessário (informativo)
      if (jsonSave.substitution && jsonSave.substitution.checked) {
        if (jsonSave.substitution.has_substitution) {
          const imeis = Array.isArray(jsonSave.substitution.imeis) ? jsonSave.substitution.imeis : [];
          const detalhe = imeis.length ? `<div style="margin-top:6px;font-weight:400">IMEIs substituídos: ${imeis.join(", ")}</div>` : "";
          mostrarAlertaSubstituicaoInline(imeis);

          // mostrar modal informativo (não exige confirmação)
          await showBigAlert({
            title: "Atenção — Substituição detectada",
            message: "Existe equipamento substituído na remessa. Confira se está na caixa.",
            detalheHtml: detalhe,
            requireConfirm: false
          });
        } else {
          esconderAlertaSubstituicaoInline();
        }
      }

      if (jsonSave.success && jsonSave.redirect) {
        window.location.href = jsonSave.redirect;
        return;
      } else if (jsonSave.error) {
        showError(jsonSave.error);
        return;
      }

      if (jsonSave.success) {
        try { form.reset(); } catch (_) {}
        if (mensagemErro) mensagemErro.innerText = "";
      }

    } catch (error) {
      console.error("Erro geral na requisição:", error);
      showError("Erro na comunicação com o servidor.");
    }
  });

  // ========= Tabelas / preenchimento =========
  function mostrarTabela(tabela) {
    tabelaAguardando.style.display = 'none';
    tabelaQualidade.style.display = 'none';
    tabela.style.display = 'table';
    tabela.querySelector('tbody').innerHTML = '';
  }

  function preencherInputs(item, tipo) {
    document.querySelector('#cnpj').value = item.cnpj || '';
    document.querySelector('#razao_social').value = item.razao_social || '';
    document.querySelector('#nota_fiscal').value = item.nota_fiscal || '';
    document.querySelector("#setor").value = item.setor || '';
    document.querySelector('#quantidade').value = item.quantidade || '';
    document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';

    if (tipo === "aguardando") {
      document.querySelector('#operacao_origem').value = item.operacao_destino || '';
    } else if (tipo === "qualidade") {
      document.querySelector('#data_inicio_qualidade').value = item.data_inicio_qualidade || '';
      document.querySelector('#quantidade').value = item.quantidade || '';
      document.querySelector('#quantidade_parcial').value = item.quantidade_parcial || '';
      document.querySelector('#operacao_origem').value = item.operacao_destino || '';
      document.querySelector('#nota_fiscal_retorno').value = item.nota_fiscal_retorno || '';
    }
  }

  function preencherTabelaAguardando(dados) {
    mostrarTabela(tabelaAguardando);
    const tbody = tabelaAguardando.querySelector('tbody');
    dados.forEach(item => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${item.setor || ''}</td>
        <td>${item.cnpj || ''}</td>
        <td>${item.razao_social || ''}</td>
        <td>${item.nota_fiscal || ''}</td>
        <td>${item.quantidade || ''}</td>
        <td>${item.quantidade_parcial || ''}</td>
        <td>${item.operacao_destino || ''}</td>
      `;
      row.addEventListener('click', () => preencherInputs(item, "aguardando"));
      tbody.appendChild(row);
    });
  }

  function preencherTabelaQualidade(dados) {
    mostrarTabela(tabelaQualidade);
    const tbody = tabelaQualidade.querySelector('tbody');
    dados.forEach(item => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${item.setor || ''}</td>
        <td>${item.cnpj || ''}</td>
        <td>${item.razao_social || ''}</td>
        <td>${item.nota_fiscal || ''}</td>
        <td>${item.data_inicio_qualidade || ''}</td>
        <td>${item.quantidade || ''}</td>
        <td>${item.quantidade_parcial || ''}</td>
        <td>${item.operacao_destino || ''}</td>
        <td>${item.nota_fiscal_retorno || ''}</td>
      `;
      row.addEventListener('click', () => preencherInputs(item, "qualidade"));
      tbody.appendChild(row);
    });
  }

  function destacarBotao(btn) {
    btnAguardandoNfRetorno.classList.remove("ativo");
    btnQualidade.classList.remove("ativo");
    btn.classList.add("ativo");
  }

  function filtrarAguardando(listaAguardando, listaQualidade) {
    const chavesQualidade = new Set(listaQualidade.map(item => `${item.cnpj}-${item.nota_fiscal}`));
    return listaAguardando.filter(item => {
      const chave = `${item.cnpj}-${item.nota_fiscal}`;
      return !chavesQualidade.has(chave);
    });
  }

  // eventos para os botões (busca dados)
  btnAguardandoNfRetorno.addEventListener('click', () => {
    destacarBotao(btnAguardandoNfRetorno);
    fetch("/BackEnd/Qualidade/consulta_qualidade.php")
      .then(res => res.json())
      .then(qualidade => {
        dadosQualidade = qualidade;
        fetch("/BackEnd/Qualidade/consulta_aguardando_nf.php")
          .then(res => res.json())
          .then(aguardandoNF => {
            dadosAguardandoNFRetorno = aguardandoNF;
            const filtrados = filtrarAguardando(dadosAguardandoNFRetorno, dadosQualidade);
            preencherTabelaAguardando(filtrados);
          });
      });
  });

  btnQualidade.addEventListener('click', () => {
    destacarBotao(btnQualidade);
    fetch("/BackEnd/Qualidade/consulta_qualidade.php")
      .then(res => res.json())
      .then(dados => {
        dadosQualidade = dados;
        preencherTabelaQualidade(dados);
      });
  });

  // Inicializa com "Aguardando NF de retorno" visível
  btnAguardandoNfRetorno.click();

  // Filtro por NF entrada / NF retorno (exato)
  document.getElementById("filtro-nf").addEventListener("input", function () {
    const termo = this.value.toLowerCase().trim();
    if (!/^[\w\s\-./]*$/.test(termo)) return;

    const tabelas = [
      document.getElementById("tabela-info-aguardando-nf-retorno"),
      document.getElementById("tabela-info-em-inspecao")
    ];

    tabelas.forEach(tabela => {
      const linhas = tabela.querySelectorAll("tbody tr");
      linhas.forEach(linha => {
        const notaFiscal = linha.cells[3]?.textContent.trim().toLowerCase() || '';
        const notaFiscalRetorno = linha.cells[8]?.textContent.trim().toLowerCase() || ''; // só na tabela de inspeção
        linha.style.display = (notaFiscal === termo || notaFiscalRetorno === termo) ? "" : "none";
      });
    });
  });

}); // DOMContentLoaded
</script>

</body>
</html>