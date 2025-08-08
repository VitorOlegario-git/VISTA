<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false); // Compatibilidade adicional
header("Pragma: no-cache"); // Compatível com HTTP/1.0
header("Expires: 0"); // Expira imediatamente

$tempo_limite = 1200; // 20 minutos

// Verifica inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /sistema/KPI_2.0/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /sistema/KPI_2.0/FrontEnd/tela_login.php");
    exit();
}

$_SESSION['last_activity'] = time();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DashBoard Interativo</title>
    <link rel="stylesheet" href="cssDash/dashrecebimento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js principal -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Plugin de data labels (exibe números nas barras) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<!-- Plugin de annotation (linhas verticais com rótulos) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0/dist/chartjs-plugin-annotation.min.js"></script>

<!-- RECEBIMENTO -->
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoQuantidade.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoMedio.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosSetor.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosOperador.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoOperacoes.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTaxaRejeicao.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoOperacoes.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTopEmpresas.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosDia.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTendenciaMensal.js"></script>

<!-- ANÁLISE -->
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/analiseJS/produtividadeAnalise.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/analiseJS/graficoTicketMedio.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/analiseJS/tempoMedioAnalise.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/analiseJS/parcialCompleta.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/analiseJS/analisesCliente.js"></script>

<!-- Reparo -->  
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/produtividadeReparo.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/graficoEquipamentosPorOperador.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/tempoSolicitacaoNF.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/tempoReparoOperador.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/reparosPorCliente.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/produtosMaisReparados.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/servicosExecutados.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/graficoCustoTotalRemessa.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/graficoCustoMedioProduto.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/reparoJS/graficoProdutosMaiorCUsto.js"></script>

<!-- Qualidade -->
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/qualidadeJS/quantidadesEquip.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/qualidadeJS/principaisServicos.js"></script>
    <script src="/sistema/KPI_2.0/DashBoard/frontendDash/jsDash/qualidadeJS/principaisLaudos.js"></script>

</head>
<body>

    <div class="top-container">
        <!-- Formulário de seleção de datas -->
        <div class="data-container">
            <form action="http://172.16.0.50/sistema/KPI_2.0/DashBoard/equip_recebidos_analise.php" method="post">
                
                    <label for="data_inicial">De:</label>
                    <input type="date" id="data_inicial" name="data_inicial">
                
                    <label for="data_final">Até:</label>
                    <input type="date" id="data_final" name="data_final"> 

                    <label for="operador">Operador:</label>
                    <select id="operador" name="operador">
                        <option value="">Todos</option>
                        <option value="Vitor Olegario">Vitor Olegário</option>
                        <option value="Luan Oliveira">Luan Oliveira</option>
                        <option value="ronyrodrigues">Rony Rodrigues</option>
                        <option value="Ederson Santos">Ederson Santos</option>
                        <option value="Matheus Ferreira">Matheus Ferreira</option>
                        <!-- Você pode gerar essas opções dinamicamente com PHP, se preferir -->
                    </select>
                    <button id="btnFiltrar" type="button">Filtrar</button>
                    

                   <button type="button" id="admin">Admin</button>
            </form>
        </div>
    
        <!-- Botões de navegação -->
        <div class="buttons-container">
            <button type="button" id="recebimento">Recebimento</button>
            <button type="button" id="analise">Análise</button>
            <button type="button" id="reparo">Reparo</button>
            <button type="button" id="qualidade">Qualidade</button>
            <button type="button" id="financeiro">Financeiro</button>
            <button type="button" onclick="window.history.back()">Voltar</button>
        </div>
    </div>
    <!--Área administrativa exclusiva para Vitor Olegario-->
<?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'Vitor Olegario'): ?>
    <div id="area-administrativa" style="display: none;">
        <h3>Monitoramento em Tempo Real - Operadores</h3>
        <div class="operadores-container">
            <!-- Operador 1 -->
            <div class="operador-box" id="operador1">
                <h4>Vitor Olegario</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Vitor_Olegario" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 2 -->
            <div class="operador-box" id="operador2">
                <h4>Luan Oliveira</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Luan_Oliveira" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 3 -->
            <div class="operador-box" id="operador3">
                <h4>Rony Rodrigues</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Rony_Rodrigues" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>

            </div>
            <!-- Operador 4 -->
            <div class="operador-box" id="operador4">
                <h4>Ederson Santos</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Ederson_Santos" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>
            </div>
            <!-- Operador 5 -->
            <div class="operador-box" id="operador5">
                <h4>Matheus Ferreira</h4>
                <p>Status: <span class="status">Carregando...</span></p>
                <p class="tempo">⏱️ Em atividade há: <span>--</span></p>
                <p class="setor">🧩 Setor: <span>--</span></p>
                <p class="cliente">🏢 Cliente:<br><span>--</span></p>
                <p class="quantidade">📦 QTD: <span>--</span></p>
                <button class="btn-relatorio" data-operador="Matheus_Ferreira" title="Ver relatório">
                   <i class="fas fa-file-alt"></i>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>



    <!--Area do recebimento-->
    <div class="left-container" id="leftContainer_recebimento" style="display: none;">
        <p>📊 KPI's Operacionais para Gestão de Recebimentos</p>
        <a id="quantidade-recebida" class="link-dashboard" data-target="quantidadeRecebimento">📦 QTD. Equip. Recebidos</a>
        <br>
        <a id="tempo-medio" class="link-dashboard" data-target="tempoMedioAnalise">⏳ Tempo Médio para Envio à Análise</a>
        <br>
        <a id="recebimento-setor" class="link-dashboard" data-target="recebimentosSetor">📊 Recebimentos por Setor</a>
        <br>
        <a id="operacoes-origem-destino" class="link-dashboard" data-target="operacoesOrigemDestino">📍 QTD. remessas nas operações</a>
        <!--<a id="recebimento-operador" class="link-dashboard" data-target="recebimentosOperador">📈 Equipamentos Recebidos por Operador</a>
        <a id="taxa-rejeicao" class="link-dashboard" data-target="taxaRejeicao">⚠️ Taxa de Rejeição ou Reenvio</a>-->
        
        <br>
        <a id="tempo-operacoes" class="link-dashboard" data-target="tempoOperacoes">🕒 Tempo Médio Entre Operações</a>
        <br>
        <a id="top-empresas" class="link-dashboard" data-target="topEmpresas">📌 Top 10 Empresas</a>
        <br>
        <!--<a id="recebimento-dia" class="link-dashboard" data-target="recebimentosDia">📅 Distribuição por Dia da Semana</a>-->
       
        <a id="tendencia-mensal" class="link-dashboard" data-target="tendenciaMensal">📈 Tendência Mensal de remessas recebidas</a>
    </div>    

    <div class="dados-container" id="dadosContainerRecebimento" style="display: none;">
        <!--Quantidade Total de Equipamentos Recebidos-->
        
        <div id="quantidadeRecebimento" >
            <div class="quantidade-recebida" id="dadosQuantidade"></div>
            <div class="grafico-container grafico-grande" id="graficoQuantidadeSemanal" style="display: none;" >
                <canvas id="graficoRecebimentosSemanal"></canvas> 
            </div>
            <div class="grafico-container grafico-grande" id="graficoQuantidadeMensal" style="display: none;" >
                <canvas id="graficoRecebimentosMensal"></canvas>
            </div>
        </div>

        <!--Tempo Médio para Envio à Análise-->
        <div class="grafico-container grafico-grande" id="tempoMedioAnalise" style="display: none;">
            <canvas id="graficoTempoMedio"></canvas>
        </div>  
        
        <!--Recebimentos por Setor-->
        <div class="grafico-container grafico-grande" id="recebimentosSetor" style="display: none;">
            <canvas id="graficoSetor"></canvas>
        </div>

        <!--Quantidade de Equipamentos Recebidos por Operador-->
        <div class="grafico-container grafico-grande" id="recebimentosOperador" style="display: none;">
            <canvas id="graficoOperador"></canvas>
        </div>

        <!--Principais Operações de Origem e Destino-->
        <div class="grafico-container grafico-grande" id="operacoesOrigemDestino" style="display: none;">
            <canvas id="graficoOperacoes"></canvas>
        </div>

        <!--Taxa de Rejeição ou Reenvio-->
        <div class="grafico-container grafico-grande" id="taxaRejeicao" style="display: none;">
            <canvas id="graficoRejeicao"></canvas>
        </div>

        <!--Tempo Médio Entre Operações-->
        <div class="grafico-container grafico-grande" id="tempoOperacoes" style="display: none;">
            <canvas id="graficoTempoOperacoes"></canvas>
        </div>

        <!--Top 5 Empresas com Maior Volume de Recebimentos-->
        <div class="grafico-container grafico-grande" id="topEmpresas" style="display: none;">
            <canvas id="graficoEmpresas"></canvas>
        </div>
 
        <!--Distribuição de Recebimentos por Dia da Semana-->
        <div class="grafico-container grafico-grande" id="recebimentosDia" style="display: none;">
            <canvas id="graficoDiaSemana"></canvas>
        </div>
  
        <!--Tendência Mensal de Recebimentos-->
        <div class="grafico-container grafico-grande" id="tendenciaMensal" style="display: none;">
            <canvas id="graficoTendenciaMensal"></canvas>
        </div>
             
    </div>

    <!--Area da analise-->
    <div class="left-container-analise" id="leftContainerAnalise" style="display: none;">
        <h3>📊 KPIs para Assistência Técnica</h3>
        <br>
        
        <a id="equipamentos_finalizados" data-target="quantidadeAnalise">🔹 QTD. analisadas</a>
        <br>
        <!--<a id="financeiro" data-target="graficoTicketContainer">💰 Orçamentos gerados</a>-->
        
        <a id="tempo_medio_analise" data-target="graficoTempoMedioAnaliseContainer">📈 Tempo Médio análise</a>
        <br>
        <a id="parcial_vs_completa" data-target="graficoParcialCompletaContainer">📊Remessas analisadas: parciais vs. completas</a>
        <br>
        <a id="analises_por_cliente" data-target="graficoAnalisesClienteContainer">🏢 Análises por Cliente</a>
      </div>
      
    <div class="dados-container-analise" id="dadosAnalise" style="display: none;">
        
          <div class="grafico-container grafico-grande" id="graficoQuantidadeFinalisadaSemanal" style="display: none;">
            <div style="overflow-x: auto; width: 100%;">
                <canvas id="graficoProdutividadeSemanal"></canvas>
            </div>
          </div>
          <div class="grafico-container grafico-grande" id="graficoQuantidadeFinalisadaMensal" style="display: none;">
            <canvas id="graficoProdutividadeMensal"></canvas>
          </div>
          
          <!--Faturamento-->
          <div class="grafico-container grafico-grande" id="graficoTicketContainer" style="display: none;">
            <canvas id="graficoTicketMedio"></canvas>
          </div>
 
          <div class="grafico-container grafico-grande" id="graficoTempoMedioAnaliseContainer" style="display: none;">
            <canvas id="graficoTempoMedioAnalise"></canvas>
          </div>

          <div class="grafico-container grafico-grande" id="graficoParcialCompletaContainer" style="display: none;">
            <canvas id="graficoParcialCompleta"></canvas>
          </div>

          <div class="grafico-container grafico-grande" id="graficoAnalisesClienteContainer" style="display: none;">
            <canvas id="graficoAnalisesCliente"></canvas>
          </div>     
    </div>

    <!-- Área do Reparo -->
<div class="left-container-reparo" id="leftContainerReparo" style="display: none;">
  <h3>🔧 KPIs para o Setor de Reparo (Suntech)</h3><br>
  <a id="quantidade_reparados">QTD. reparada por Semana/Mês</a><br>

  
  <!--<a id="tempoMedioSolicitacaoNf">⏱ Tempo Médio para Solicitação de NF após Início do Reparo</a>-->
  <a id="tempoMedioReparoOperador">⏱ Tempo Médio de Reparo por Operador</a><br>

  
  <a id="reparoPorCliente">🧾 Distribuição de Reparos por Cliente</a><br>

  
  <a id="custoTotalReparos">💰 Custo Total dos Reparos</a>
  <a id="custoMedioReparo">📊 Custo Médio por Reparo</a>
  <a id="maiorCustoAcumulado">📊 Maior Custo Acumulado por Produto</a>
</div>

<div class="dados-container-reparo" id="dadosReparo" style="display: none;">

  <!-- Gráfico Semanal -->
  <div class="grafico-container grafico-grande" id="graficoQuantidadeReparadaSemanal" style="display: none;">
    <canvas id="graficoReparoSemanal"></canvas>
  </div>

  <!-- Gráfico Mensal -->
  <div class="grafico-container grafico-grande" id="graficoQuantidadeReparadaMensal" style="display: none;">
    <canvas id="graficoReparoMensal"></canvas>
  </div>

  <!-- Tempo Médio para Solicitação de NF 
  <div class="grafico-container grafico-medio" id="graficoTempoSolicitacaoNf" style="display: none;">
    <canvas id="graficoTempoNf"></canvas>
  </div>-->

  <!-- Tempo Médio de Reparo por Operador -->
  <div class="grafico-container grafico-medio" id="graficoTempoReparoOperador" style="display: none;">
    <canvas id="graficoReparoOperador"></canvas>
  </div>

  <!-- Distribuição de Reparos por Cliente -->
  <div class="grafico-container grafico-medio" id="graficoTotalReparoCliente" style="display: none;">
    <canvas id="graficoReparoCliente"></canvas>
  </div>

  <!-- Principais Serviços Executados -->
  <div class="grafico-container grafico-medio" id="graficoServicosExecutados" style="display: none;">
    <canvas id="graficoServicos"></canvas>
  </div>

  <!-- Produtos mais Reparados por Remessa -->
  <div class="grafico-container grafico-medio" id="graficoPorProduto" style="display: none;">
    <canvas id="graficoProduto"></canvas>
  </div>

  <!-- Custo Total dos Reparos -->
  <div class="grafico-container grafico-alto" id="graficoCustoTotal" style="display: none;">
    <canvas id="graficoCustoTotalCanvas"></canvas>
  </div>

  <!-- Custo Médio por Reparo -->
  <div class="grafico-container grafico-alto" id="graficoCustoMedio" style="display: none;">
    <canvas id="graficoCustoMedioCanvas"></canvas>
  </div>

  <!-- Maior Custo Acumulado por Produto -->
  <div class="grafico-container grafico-alto" id="containerMaiorCustoProduto" style="display: none;">
    <canvas id="graficoMaiorCustoProduto"></canvas>
  </div>

</div>


<!-- Área da Qualidade -->
<div class="left-container-qualidade" id="leftContainerQualidade" style="display: none;">
    <h3>📊 KPIs para qualidade</h3>
</br>
    <a id="quantidade_equipamentos">QTD. por modelo de equipamentos</a></br>
    <a id="principais_servicos">Principais serviços no reparo</a></br>
    <a id="principais_laudos">Principais laudos enviados</a></br>
    
</div> 
<div class="dados-container-qualidade" id="dadosQualidade" style="display: none;">

<!-- Quantidade por equipamentos recebidos, analisados e reparados -->
  <div class="grafico-container grafico-grande" id="graficoquantidadeequipamentos" style="display: none;">
    <canvas id="graficoQuantidadeEquipamentos"></canvas>
  </div>
<!-- Principais serviços no reparo -->
  <div class="grafico-container grafico-grande" id="graficoprincipaisservicos" style="display: none;">
    <canvas id="graficoPrincipaisServicos"></canvas>
  </div>
  <!-- Principais laudos enviados por modelo -->
  <div class="grafico-container" id="graficoprincipaislaudos" style="display: none;">
    <h3>📋 Principais Laudos Técnicos</h3>
    <label for="filtroModelo">Modelo:</label>
    <select id="filtroModelo">
        <option value="">Todos os modelos</option>
        <!-- Opções serão preenchidas via JS -->
    </select>
    <div class="tabela-laudos">
        <table id="tabelaLaudos">
            <thead>
                <tr>
                    <th>Modelo</th>
                    <th>Laudo</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <!-- Dados serão preenchidos via JS -->
            </tbody>
        </table>
    </div>
</div>

</div>   
<script>

    
document.addEventListener("DOMContentLoaded", function () {
    const btnFiltrar = document.getElementById("btnFiltrar");

if (btnFiltrar) {
    btnFiltrar.addEventListener("click", (event) => {
        event.preventDefault();

        const filtros = {
            dataInicio: document.getElementById("data_inicial").value || "",
            dataFim: document.getElementById("data_final").value || "",
            operador: document.getElementById("operador").value || ""
        };

        // Validação obrigatória de período
        if (!filtros.dataInicio || !filtros.dataFim) {
            alert("Por favor, selecione a data inicial e final para aplicar o filtro.");
            return;
        }

        console.log("Filtros aplicados:", filtros);
        executarFiltros(filtros);
    });
}
function executarFiltros({ dataInicio, dataFim, operador }) {
    const graficos = [
        // ANALISE
        { id: "graficoQuantidadeFinalisadaSemanal", func: carregarProdutividadeAnalise },
        { id: "graficoTempoMedioAnaliseContainer", func: carregarTempoMedioAnalise },
        { id: "graficoParcialCompletaContainer", func: carregarParcialCompleta },
        { id: "graficoAnalisesClienteContainer", func: carregarAnalisesPorCliente },

        // RECEBIMENTO
        { id: "graficoQuantidadeSemanal", func: carregarQuantidadeRecebidaEGraficos },
        { id: "graficoQuantidadeMensal", func: carregarQuantidadeRecebidaEGraficos },
        { id: "tempoMedioAnalise", func: carregarGraficoTempoMedio },
        { id: "recebimentosSetor", func: carregarGraficoSetor },
        { id: "operacoesOrigemDestino", func: carregarGraficoOperacoes },
        { id: "tempoOperacoes", func: carregarGraficoTempoOperacoes },
        { id: "topEmpresas", func: carregarGraficoEmpresas },
        { id: "tendenciaMensal", func: carregarGraficoTendenciaMensal },

        // REPARO
        { id: "graficoQuantidadeReparadaSemanal", func: carregarProdutividadeReparo },
        { id: "graficoQuantidadeReparadaMensal", func: carregarProdutividadeReparo },
        { id: "graficoQuantidadeReparadaOperador", func: carregarEquipamentosPorOperador },
        { id: "graficoTempoSolicitacaoNf", func: carregarTempoSolicitacaoNF },
        { id: "graficoTempoReparoOperador", func: carregarTempoReparoOperador },
        { id: "graficoTotalReparoCliente", func: carregarReparosPorCliente },
        { id: "graficoCustoTotal", func: carregarCustoTotalPorProduto },
        { id: "graficoCustoMedio", func: carregarCustoMedioPorProduto },
        { id: "containerMaiorCustoProduto", func: carregarProdutosMaiorCusto },

        // QUALIDADE
        { id: "graficoquantidadeequipamentos", func: carregarquantidadeEquip},
        { id: "graficoprincipaisservicos", func: carregarPrincipaisServicos },
        { id: "graficoprincipaislaudos", func: carregarPrincipaisLaudos }
    ];

    const chamados = new Set(); // para evitar chamadas duplicadas da mesma função

    graficos.forEach(({ id, func }) => {
        const el = document.getElementById(id);
        if (el && window.getComputedStyle(el).display === "block" && !chamados.has(func)) {
            func(dataInicio, dataFim, operador);
            chamados.add(func);
        }
    });
}

        // 🔁 Mapeamento entre botões e seus containers 
const setores = [
    {
        botao: document.getElementById("recebimento"),
        containers: [
            document.getElementById("leftContainer_recebimento"),
            document.getElementById("dadosContainerRecebimento")
        ],
        onAtivar: () => {} // Não precisa carregar dados ao ativar Recebimento
    },
    {
        botao: document.getElementById("analise"),
        containers: [
            document.getElementById("leftContainerAnalise"),
            document.getElementById("dadosAnalise")
        ],
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            // ⚠️ Carregar dados se necessário
        }
    },
    {
        botao: document.getElementById("reparo"),
        containers: [
            document.getElementById("leftContainerReparo"),
            document.getElementById("dadosReparo")
        ],
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            carregarProdutividadeReparo(dataInicio, dataFim, operador);
        }
    },
    {
        botao: document.getElementById("qualidade"),
        containers: [
            document.getElementById("leftContainerQualidade"),
            document.getElementById("dadosQualidade")
        ],
        onAtivar: () => {
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            const operador = document.getElementById("operador").value || "";
            carregarquantidadeEquip(dataInicio, dataFim, operador); // ✅ CORRETO
        }
    }

];

// 🔁 Oculta todos os containers
function ocultarTodosOsSetores() {
    setores.forEach(({ containers }) => {
        containers.forEach(c => {
            if (c) c.style.display = "none";
        });
    });
}

// 🔁 Alterna exibição ao clicar no botão
setores.forEach(({ botao, containers, onAtivar }) => {
    if (!botao) return;

    botao.addEventListener("click", function () {
        const estaVisivel = containers.every(c => c && c.style.display === "block");

        ocultarTodosOsSetores();

        if (!estaVisivel) {
            destacarBotaoAtivo(botao);
            containers.forEach(c => {
                if (c) c.style.display = "block";
            });
            onAtivar();
        }
    });
});

// 🔁 Estiliza botão ativo
function destacarBotaoAtivo(botaoClicado) {
    setores.forEach(({ botao }) => botao?.classList.remove("setor-ativo"));
    botaoClicado.classList.add("setor-ativo");
}

// 🔁 Exibição da área administrativa
document.getElementById("admin")?.addEventListener("click", function () {
    const adminDiv = document.getElementById("area-administrativa");
    if (adminDiv) {
        const visivel = adminDiv.style.display === "block";
        adminDiv.style.display = visivel ? "none" : "block";
    }
});


      

        /*Estruturação para abrir e esconder os graficos*/
      
  // 🔁 Mapeamento de links, containers e funções de carregamento
  //Recebimento
const graficosRecebimento = [
    {
        linkId: "quantidade-recebida",
        containerIds: ["graficoQuantidadeSemanal", "graficoQuantidadeMensal"],
        funcao: carregarQuantidadeRecebidaEGraficos
    },
    {
        linkId: "tempo-medio",
        containerIds: ["tempoMedioAnalise"],
        funcao: carregarGraficoTempoMedio
    },
    {
        linkId: "recebimento-setor",
        containerIds: ["recebimentosSetor"],
        funcao: carregarGraficoSetor
    },
    {
        linkId: "operacoes-origem-destino",
        containerIds: ["operacoesOrigemDestino"],
        funcao: carregarGraficoOperacoes
    },
    {
        linkId: "tempo-operacoes",
        containerIds: ["tempoOperacoes"],
        funcao: carregarGraficoTempoOperacoes
    },
    {
        linkId: "top-empresas",
        containerIds: ["topEmpresas"],
        funcao: carregarGraficoEmpresas
    },
    {
        linkId: "tendencia-mensal",
        containerIds: ["tendenciaMensal"],
        funcao: carregarGraficoTendenciaMensal
    }
];

// 🔁 Coleta containers únicos
const todosContainers = [...new Set(graficosRecebimento.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Destaque visual do botão ativo
function destacarBotaoGraficoAtivoRecebimento(botaoClicado) {
    graficosRecebimento.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove('grafico-ativo');
    });
    botaoClicado.classList.add('grafico-ativo');
}

// 🔁 Função para ocultar todos os containers
function ocultarTodosOsContainers() {
    todosContainers.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Obter filtros comuns
function obterFiltros() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos
graficosRecebimento.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);

    link?.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const visivel = primeiroContainer?.style.display === "block";

        // Oculta todos
        ocultarTodosOsContainers();

        if (!visivel) {
            destacarBotaoGraficoAtivoRecebimento(this);

            // Exibe os containers definidos
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            // Executa a função de carregamento
            const { dataInicio, dataFim, operador } = obterFiltros();
            funcao(dataInicio, dataFim, operador);
        }
    });
});


// 🔁 Mapeamento de links, containers e funções de carregamento (ANÁLISE)
const graficosAnalise = [
    {
        linkId: "equipamentos_finalizados",
        containerIds: ["graficoQuantidadeFinalisadaSemanal", "graficoQuantidadeFinalisadaMensal"],
        funcao: carregarProdutividadeAnalise
    },
    // {
    //     linkId: "financeiro",
    //     containerIds: ["graficoTicketContainer"],
    //     funcao: carregarTicketMedio
    // },
    {
        linkId: "tempo_medio_analise",
        containerIds: ["graficoTempoMedioAnaliseContainer"],
        funcao: carregarTempoMedioAnalise
    },
    {
        linkId: "parcial_vs_completa",
        containerIds: ["graficoParcialCompletaContainer"],
        funcao: carregarParcialCompleta
    },
    {
        linkId: "analises_por_cliente",
        containerIds: ["graficoAnalisesClienteContainer"],
        funcao: carregarAnalisesPorCliente
    }
];

// 🔁 Coleta todos os containers únicos usados nos gráficos de análise
const todosContainersAnalise = [...new Set(graficosAnalise.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Destaque visual do botão ativo
function destacarBotaoGraficoAtivoAnalise(botaoClicado) {
    graficosAnalise.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Oculta todos os containers da área de Análise
function ocultarTodosOsContainersAnalise() {
    todosContainersAnalise.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Obtem filtros
function obterFiltrosAnalise() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos para a área de Análise
graficosAnalise.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        // Oculta todos
        ocultarTodosOsContainersAnalise();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoAnalise(this);

            // Exibe os containers definidos
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            // Executa função com os filtros
            const { dataInicio, dataFim, operador } = obterFiltrosAnalise();
            funcao(dataInicio, dataFim, operador);
        }
    });
});


// 🔁 Mapeamento de links, containers e funções de carregamento (REPARO)
const graficosReparo = [
    {
        linkId: "quantidade_reparados",
        containerIds: ["graficoQuantidadeReparadaSemanal", "graficoQuantidadeReparadaMensal"],
        funcao: carregarProdutividadeReparo
    },
    {
        linkId: "tempoMedioReparoOperador",
        containerIds: ["graficoTempoReparoOperador"],
        funcao: carregarTempoReparoOperador
    },
    {
        linkId: "reparoPorCliente",
        containerIds: ["graficoTotalReparoCliente"],
        funcao: carregarReparosPorCliente
    },
    {
        linkId: "custoTotalReparos",
        containerIds: ["graficoCustoTotal"],
        funcao: carregarCustoTotalPorProduto
    },
    {
        linkId: "custoMedioReparo",
        containerIds: ["graficoCustoMedio"],
        funcao: carregarCustoMedioPorProduto
    },
    {
        linkId: "maiorCustoAcumulado",
        containerIds: ["containerMaiorCustoProduto"],
        funcao: carregarProdutosMaiorCusto
    }
    // Para ativar futuramente:
    // {
    //     linkId: "tempoMedioSolicitacaoNf",
    //     containerIds: ["graficoTempoSolicitacaoNf"],
    //     funcao: carregarTempoSolicitacaoNF
    // },
    // {
    //     linkId: "quantidadeProdutoRemessa",
    //     containerIds: ["graficoPorProduto"],
    //     funcao: carregarProdutosMaisReparados
    // },
    // {
    //     linkId: "principaisServiços",
    //     containerIds: ["graficoServicosExecutados"],
    //     funcao: carregarServicosExecutados
    // }
];

// 🔁 Coleta todos os containers únicos
const todosContainersReparo = [...new Set(graficosReparo.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Função para destacar o botão ativo
function destacarBotaoGraficoAtivoReparo(botaoClicado) {
    graficosReparo.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Função para esconder todos os gráficos
function ocultarTodosOsContainersReparo() {
    todosContainersReparo.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Função para obter filtros
function obterFiltrosReparo() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos para o setor de Reparo
graficosReparo.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersReparo();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoReparo(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim, operador } = obterFiltrosReparo();
            funcao(dataInicio, dataFim, operador);
        }
    });
});

// 🔁 Mapeamento de links, containers e funções de carregamento (Qualidade)
const graficosQualidade = [
    {
        linkId: "quantidade_equipamentos",
        containerIds: ["graficoquantidadeequipamentos"],
        funcao: carregarquantidadeEquip
    },
    {
        linkId: "principais_servicos",
        containerIds: ["graficoprincipaisservicos"],
        funcao: carregarPrincipaisServicos
    },
    {
        linkId: "principais_laudos",
        containerIds: ["graficoprincipaislaudos"],
        funcao: carregarPrincipaisLaudos
    }
];

// 🔁 Coleta todos os containers únicos do setor Qualidade
const todosContainersQualidade = [...new Set(graficosQualidade.flatMap(g => g.containerIds))]
    .map(id => document.getElementById(id))
    .filter(Boolean);

// 🔁 Função para destacar o botão ativo no setor Qualidade
function destacarBotaoGraficoAtivoQualidade(botaoClicado) {
    graficosQualidade.forEach(g => {
        const link = document.getElementById(g.linkId);
        link?.classList.remove("grafico-ativo");
    });
    botaoClicado.classList.add("grafico-ativo");
}

// 🔁 Função para esconder todos os containers do setor Qualidade
function ocultarTodosOsContainersQualidade() {
    todosContainersQualidade.forEach(container => {
        container.style.display = "none";
    });
}

// 🔁 Função para obter filtros globais
function obterFiltrosQualidade() {
    return {
        dataInicio: document.getElementById("data_inicial").value || "",
        dataFim: document.getElementById("data_final").value || "",
        operador: document.getElementById("operador").value || ""
    };
}

// 🔁 Inicializa os eventos do setor de Qualidade
graficosQualidade.forEach(({ linkId, containerIds, funcao }) => {
    const link = document.getElementById(linkId);
    if (!link) return;

    link.addEventListener("click", function () {
        const primeiroContainer = document.getElementById(containerIds[0]);
        const estaVisivel = primeiroContainer?.style.display === "block";

        ocultarTodosOsContainersQualidade();

        if (!estaVisivel) {
            destacarBotaoGraficoAtivoQualidade(this);
            containerIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = "block";
            });

            const { dataInicio, dataFim, operador } = obterFiltrosQualidade();
            funcao(dataInicio, dataFim, operador);
        }
    });
});

function atualizarStatusOperadores() {
    fetch('/sistema/KPI_2.0/DashBoard/backendDash/ADMIN/admin.php')
        .then(res => res.json())
        .then(data => {
            data.forEach((item, index) => {
                const box = document.getElementById(`operador${index + 1}`);
                if (!box) return;

                const statusSpan = box.querySelector('.status');
                const tempoSpan = box.querySelector('.tempo span');
                const setorSpan = box.querySelector('.setor span');
                const clienteSpan = box.querySelector('.cliente span');
                const qtdSpan = box.querySelector('.quantidade span');

                statusSpan.textContent = item.status;
                tempoSpan.textContent = item.tempo;
                setorSpan.textContent = item.setor || '--';
                clienteSpan.textContent = item.razao_social || '--';
                qtdSpan.textContent = item.quantidade || '--';

                // Adiciona cor com base no status
                const statusClass = 'status-' + item.status.toLowerCase().replace(/\s+/g, '_');
                statusSpan.className = `status ${statusClass}`;
            });
        });
}

// Atualiza a cada 10 segundos
atualizarStatusOperadores();
setInterval(atualizarStatusOperadores, 10000);

document.querySelectorAll('.btn-relatorio').forEach(btn => {
    btn.addEventListener('click', () => {
        const operador = btn.dataset.operador;
        const url = `/sistema/KPI_2.0/DashBoard/backendDash/ADMIN/${operador}.php`;
        window.open(url, '_blank');
    });
});

});
</script>
</body>
</html>
