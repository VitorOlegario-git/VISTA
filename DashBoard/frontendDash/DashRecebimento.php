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
    header("Location: /localhost/FrontEnd/tela_login.php");
    exit();
}

// Verifica se a sessão está ativa
if (!isset($_SESSION['username'])) {
    header("Location: /localhost/FrontEnd/tela_login.php");
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoQuantidade.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoMedio.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosSetor.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosOperador.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoOperacoes.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTaxaRejeicao.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTempoOperacoes.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTopEmpresas.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoRecebimentosDia.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/recebimentoJS/graficoTendenciaMensal.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/produtividadeAnalise.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/graficoTicketMedio.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/tempoOrcamento.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/volumeAnalises.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/tempoMedioAnalise.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/parcialCompleta.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/analiseJS/analisesCliente.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/produtividadeReparo.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/graficoEquipamentosPorOperador.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/tempoSolicitacaoNF.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/tempoReparoOperador.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/reparosPorCliente.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/produtosMaisReparados.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/servicosExecutados.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/graficoCustoTotalRemessa.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/graficoCustoMedioProduto.js"></script>
    <script src="/localhost/DashBoard/frontendDash/jsDash/reparoJS/graficoProdutosMaiorCUsto.js"></script>
</head>
<body>

    <div class="top-container">
        <!-- Formulário de seleção de datas -->
        <div class="data-container">
            <form action="http://localhost/DashBoard/equip_recebidos_analise.php" method="post">
                
                    <label for="data_inicial">De:</label>
                    <input type="date" id="data_inicial" name="data_inicial">
                
                    <label for="data_final">Até:</label>
                    <input type="date" id="data_final" name="data_final"> 
            </form>
        </div>
    
        <!-- Botões de navegação -->
        <div class="buttons-container">
            <button type="button" id="recebimento">Recebimento</button>
            <button type="button" id="analise">Análise</button>
            <button type="button" id="reparo">Reparo</button>
            <button type="button" id="qualidade">Qualidade</button>
            <button type="button" id="expedicao">Expedição</button>
            <button type="button" onclick="window.history.back()">Voltar</button>
        </div>
    </div>
    <!--Area do recebimento-->
    <div class="left-container" id="leftContainer_recebimento" style="display: none;">
        <p>📊 KPI's Operacionais para Gestão de Recebimentos</p>
        <a id="quantidade-recebida" class="link-dashboard" data-target="quantidadeRecebimento">📦 Quantidade Total de Equipamentos Recebidos</a>
        <a id="tempo-medio" class="link-dashboard" data-target="tempoMedioAnalise">⏳ Tempo Médio para Envio à Análise</a>
        <a id="recebimento-setor" class="link-dashboard" data-target="recebimentosSetor">📊 Recebimentos por Setor</a>
        <a id="recebimento-operador" class="link-dashboard" data-target="recebimentosOperador">📈 Equipamentos Recebidos por Operador</a>
        <a id="operacoes-origem-destino" class="link-dashboard" data-target="operacoesOrigemDestino">📍 Operações de Origem e Destino</a>
        <a id="taxa-rejeicao" class="link-dashboard" data-target="taxaRejeicao">⚠️ Taxa de Rejeição ou Reenvio</a>
        
        <br>
        <p>KPI's de Desempenho e Qualidade</p>
        <a id="tempo-operacoes" class="link-dashboard" data-target="tempoOperacoes">🕒 Tempo Médio Entre Operações</a>
        <a id="top-empresas" class="link-dashboard" data-target="topEmpresas">📌 Top 5 Empresas com Maior Volume de Recebimentos</a>
        <a id="recebimento-dia" class="link-dashboard" data-target="recebimentosDia">📅 Distribuição por Dia da Semana</a>
        <a id="tendencia-mensal" class="link-dashboard" data-target="tendenciaMensal">📈 Tendência Mensal de Recebimentos</a>
    </div>    

    <div class="dados-container" id="dadosContainerRecebimento" style="display: none;">
        <!--Quantidade Total de Equipamentos Recebidos-->
        
        <div id="quantidadeRecebimento" >
            <div class="quantidade-recebida" id="dadosQuantidade"></div>
            <div class="grafico-quantidade-recebida-semanal" id="graficoQuantidadeSemanal" style="display: none;" >
                <canvas id="graficoRecebimentosSemanal"></canvas> 
            </div>
            <div class="grafico-quantidade-recebida-mensal" id="graficoQuantidadeMensal" style="display: none;" >
                <canvas id="graficoRecebimentosMensal"></canvas>
            </div>
        </div>

        <!--Tempo Médio para Envio à Análise-->
        <div class="tempo-medio-analise" id="tempoMedioAnalise" style="display: none;">
            <canvas id="graficoTempoMedio"></canvas>
        </div>  
        
        <!--Recebimentos por Setor-->
        <div class="recebimentos-setor" id="recebimentosSetor" style="display: none;">
            <canvas id="graficoSetor"></canvas>
        </div>

        <!--Quantidade de Equipamentos Recebidos por Operador-->
        <div class="recebimentos-operador" id="recebimentosOperador" style="display: none;">
            <canvas id="graficoOperador"></canvas>
        </div>

        <!--Principais Operações de Origem e Destino-->
        <div class="operacoes-origem-destino" id="operacoesOrigemDestino" style="display: none;">
            <canvas id="graficoOperacoes"></canvas>
        </div>

        <!--Taxa de Rejeição ou Reenvio-->
        <div class="taxa-rejeicao-container" id="taxaRejeicao" style="display: none;">
            <canvas id="graficoRejeicao"></canvas>
        </div>

        <!--Tempo Médio Entre Operações-->
        <div class="tempo-operacoes-container" id="tempoOperacoes" style="display: none;">
            <canvas id="graficoTempoOperacoes"></canvas>
        </div>

        <!--Top 5 Empresas com Maior Volume de Recebimentos-->
        <div class="top-empresas-container" id="topEmpresas" style="display: none;">
            <canvas id="graficoEmpresas"></canvas>
        </div>
 
        <!--Distribuição de Recebimentos por Dia da Semana-->
        <div class="recebimentos-dia-container" id="recebimentosDia" style="display: none;">
            <canvas id="graficoDiaSemana"></canvas>
        </div>
  
        <!--Tendência Mensal de Recebimentos-->
        <div class="tendencia-mensal-container" id="tendenciaMensal" style="display: none;">
            <canvas id="graficoTendenciaMensal"></canvas>
        </div>
             
    </div>

    <!--Area da analise-->
    <div class="left-container-analise" id="leftContainerAnalise" style="display: none;">
        <h3>📊 KPIs para Assistência Técnica</h3>
        <br>
        <p>🔹 PRODUTIVIDADE</p>
        <a id="equipamentos_finalizados" data-target="quantidadeAnalise">Equipamentos Embalados / HC no Setor</a>
        <a id="financeiro" data-target="graficoTicketContainer">💰 FINANCEIRO</a>
        <a id="tempo_orcamento" data-target="graficoTempoOrcamentoContainer">⏱️ Tempo Médio para Orçamento</a>
        <br>
        <p>📊 KPIs para Gestão da Análise</p>
        <a id="volume_analises" data-target="graficoVolumeAnalisesContainer">📊 Volume de Análises Realizadas</a>
        <a id="tempo_medio_analise" data-target="graficoTempoMedioAnaliseContainer">📈 Tempo Médio de Análise</a>
        <a id="parcial_vs_completa" data-target="graficoParcialCompletaContainer">📊 Parciais vs. Completas</a>
        <a id="analises_por_cliente" data-target="graficoAnalisesClienteContainer">🏢 Análises por Cliente</a>
      </div>
      
    <div class="dados-container-analise" id="dadosAnalise" style="display: none;">
        
          <div class="grafico-quantidade-finalisada-semanal" id="graficoQuantidadeFinalisadaSemanal" style="display: none;">
            <canvas id="graficoProdutividadeSemanal"></canvas>
          </div>
          <div class="grafico-quantidade-finalisada-mensal" id="graficoQuantidadeFinalisadaMensal" style="display: none;">
            <canvas id="graficoProdutividadeMensal"></canvas>
          </div>
          
          <!--Faturamento-->
          <div class="grafico-faturamento" id="graficoTicketContainer" style="display: none;">
            <canvas id="graficoTicketMedio"></canvas>
          </div>

          <!-- Tempo Médio -->
          <div class="grafico-tempo-orcamento" id="graficoTempoOrcamentoContainer" style="display: none;">
            <canvas id="graficoTempoOrcamento"></canvas>
          </div>
          
          <!--KPIs para Gestão da Análise-->
          <div class="volume-analises-container" id="graficoVolumeAnalisesContainer" style="display: none;">
            <h2 id="volumeAnalisesTexto"></h2>
            <div style="max-width: 500px; margin: auto;">
                <canvas id="graficoVolumeAnalisesOperador"></canvas>
            </div>
          </div>
        
        
          
          <div class="grafico-tempo-medio-analise" id="graficoTempoMedioAnaliseContainer" style="display: none;">
            <canvas id="graficoTempoMedioAnalise"></canvas>
          </div>

          <div class="grafico-parcial-completa-container" id="graficoParcialCompletaContainer" style="display: none;">
            <canvas id="graficoParcialCompleta"></canvas>
          </div>

          <div class="grafico-analises-cliente-container" id="graficoAnalisesClienteContainer" style="display: none;">
            <canvas id="graficoAnalisesCliente"></canvas>
          </div>     
    </div>

    <!--Area do reparo-->
    <div class="left-container-reparo" id="leftContainerReparo" style="display: none;">
        <h3>🔧 KPIs para o Setor de Reparo (Suntech)</h3>
        <br>
        <p>🔹 PRODUTIVIDADE</p>
        <a id="quantidade_reparados">🔹 Quantidade de Reparo por Semana/Mês</a>
        <a id="equipamentos_reparados">🔹 Equipamentos Reparados por Técnico</a>
        <br>
        <p>⏱ TEMPO DE PROCESSOS</p>
        <a id="tempoMedioSolicitacaoNf" >⏱ Tempo Médio para Solicitação de NF após Início do Reparo</a>
        <a id="tempoMedioReparoOperador">⏱ Tempo Médio de Reparo por Operadors</a>
        <br>
        <p>🧾 GESTÃO DE DEMANDAS</p>
        <a id="reparoPorCliente" >🧾 Distribuição de Reparos por Cliente</a>
        <br>
        <p>🔍 DETALHAMENTO DE SERVIÇOS</p>
        <a id="quantidadeProdutoRemessa">🔍 Produtos mais Reparados por Remessa</a>
        <a id="principaisServiços">🔍 Principais Serviços Executados</a>
        <p>💰 ANÁLISE FINANCEIRA</p>
        <a id="custoTotalReparos">💰 Custo Total dos Reparos</a>
        <a id="custoMedioReparo">📊 Custo Médio por Reparo</a>
        <a id="maiorCustoAcumulado">📊 Maior Custo Acumulado por Produto</a>
      </div>
      <div class="dados-container-reparo" id="dadosReparo" style="display: none;">
        
          <div class="grafico-quantidade-reparada-semanal" id="graficoQuantidadeReparadaSemanal" style="display: none;">
            <canvas id="graficoReparoSemanal"></canvas>
          </div>
          <div class="grafico-quantidade-reparada-mensal" id="graficoQuantidadeReparadaMensal" style="display: none;">
            <canvas id="graficoReparoMensal"></canvas>
          </div>
         
          <div class="grafico-quantidade-reparada-operador" id="graficoQuantidadeReparadaOperador" style="display: none;">
            <canvas id="graficoQuantidadeOperador"></canvas>
          </div>

          <div class="grafico-tempo-solicitacao-nf" id="graficoTempoSolicitacaoNf" style="display: none;">
            <canvas id="graficoTempoNf"></canvas>
          </div>
          
          <div class="grafico-tempo-reparo-operador" id="graficoTempoReparoOperador" style="display: none;">
            <canvas id="graficoReparoOperador"></canvas>
          </div>
        
          <div class="grafico-total-reparo-cliente" id="graficoTotalReparoCliente" style="display: none;">
            <canvas id="graficoReparoCliente"></canvas>
          </div>

          <div class="grafico-servicos-executados" id="graficoServicosExecutados" style="display: none;">
            <canvas id="graficoServicos"></canvas>
          </div>  
          <div class="grafico-produto" id="graficoPorProduto" style="display: none;">
            <canvas id="graficoProduto"></canvas>
          </div> 
          <div class="grafico-custo-total" id="graficoCustoTotal" style="display: none;">
            <canvas id="graficoCustoTotalCanvas" width="800" height="400"></canvas>
          </div>
          <div class="grafico-custo-medio" id="graficoCustoMedio" style="display: none;">
            <canvas id="graficoCustoMedioCanvas" width="800" height="400"></canvas>
          </div>
          <div class="grafico-maior-custo-produto" id="containerMaiorCustoProduto" style="display: none;">
            <canvas id="graficoMaiorCustoProduto"></canvas>
          </div>   
    </div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const btnRecebimento = document.getElementById("recebimento");
        const btnAnalise = document.getElementById("analise");
        const btnReparo = document.getElementById("reparo");
        const btnExpedicao = document.getElementById("expedicao");

        const containerRecebimento = document.getElementById("leftContainer_recebimento");
        const containerAnalise = document.getElementById("leftContainerAnalise");
        const containerReparo = document.getElementById("leftContainerReparo");
        const containerDadosRecebimento = document.getElementById("dadosContainerRecebimento");
        const containerDadosAnalise = document.getElementById("dadosAnalise");
        const containerDadosReparo = document.getElementById("dadosReparo");
        

        btnRecebimento.addEventListener("click", function(){
            if(containerRecebimento.style.display === "none" && containerDadosRecebimento.style.display === "none"){
                containerRecebimento.style.display = "block";
                containerDadosRecebimento.style.display = "block";
                containerAnalise.style.display = "none";
                containerDadosAnalise.style.display = "none";
                containerDadosReparo.style.display = "none";
                containerReparo.style.display = "none";
            } else {
                containerRecebimento.style.display = "none";
                containerDadosRecebimento.style.display = "none";
                containerAnalise.style.display = "none";
                containerDadosAnalise.style.display = "none";
                containerDadosReparo.style.display = "none";
                containerReparo.style.display = "none";
            }
        });

        btnAnalise.addEventListener("click", function(){
            if(containerAnalise.style.display === "none" && containerDadosAnalise.style.display === "none"){
                containerRecebimento.style.display = "none";
                containerDadosRecebimento.style.display = "none";
                containerAnalise.style.display = "block";
                containerDadosAnalise.style.display = "block";
                containerDadosReparo.style.display = "none";
                containerReparo.style.display = "none";

                // ✅ Pega as datas, mesmo que vazias
            let dataInicio = document.getElementById("data_inicial").value;
            let dataFim = document.getElementById("data_final").value;

            // ✅ Se estiverem vazias, envia strings vazias mesmo
            carregarProdutividadeAnalise(dataInicio || "", dataFim || "");
            } else {
                containerRecebimento.style.display = "none";
                containerDadosRecebimento.style.display = "none";
                containerAnalise.style.display = "none";
                containerDadosAnalise.style.display = "none";
                containerDadosReparo.style.display = "none";
                containerReparo.style.display = "none";

            }
        });
        btnReparo.addEventListener("click", function(){
            if(containerReparo.style.display === "none" && containerDadosReparo.style.display === "none"){
                containerRecebimento.style.display = "none";
                containerDadosRecebimento.style.display = "none";
                containerAnalise.style.display = "none";
                containerDadosAnalise.style.display = "none";
                containerDadosReparo.style.display = "block";
                containerReparo.style.display = "block";

                const dataInicio = document.getElementById("data_inicial").value || "";
                const dataFim = document.getElementById("data_final").value || "";
                carregarProdutividadeReparo(dataInicio, dataFim);
            } else {
                containerRecebimento.style.display = "none";
                containerDadosRecebimento.style.display = "none";
                containerAnalise.style.display = "none";
                containerDadosAnalise.style.display = "none";
                containerDadosReparo.style.display = "none";
                containerReparo.style.display = "none";

            }
        });

        /*Estruturação para abrir e esconder os graficos*/
      
    /*Area do recebimento*/
    const containerQuantidadeRecebimentoSemanal = document.getElementById("graficoQuantidadeSemanal"); 
    const containerQuantidadeRecebimentoMensal = document.getElementById("graficoQuantidadeMensal");     
    const containerTempoMedioAnalise = document.getElementById("tempoMedioAnalise"); 
    const containerRecebimentoSetor = document.getElementById("recebimentosSetor"); 
    const containerRecebimentoOperador = document.getElementById("recebimentosOperador"); 
    const containerOperacaoOrigemDestino = document.getElementById("operacoesOrigemDestino"); 
    const containerTaxaRejeicao = document.getElementById("taxaRejeicao"); 
    const containerTempoOperacoes = document.getElementById("tempoOperacoes"); 
    const containerTopEmpresas = document.getElementById("topEmpresas"); 
    const containerRecebimentosDia = document.getElementById("recebimentosDia"); 
    const containerTendenciaMensal = document.getElementById("tendenciaMensal");  

    const linkQuantidadeRecebimento = document.getElementById("quantidade-recebida");
    const linkTempoMedioAnalise = document.getElementById("tempo-medio");
    const linkRecebimentoSetor = document.getElementById("recebimento-setor");
    const linkRecebimentoOperador = document.getElementById("recebimento-operador");
    const linkOperacaoOrigemDestino = document.getElementById("operacoes-origem-destino");
    const linkTaxaRejeicao = document.getElementById("taxa-rejeicao");
    const linkTempoOperacoes = document.getElementById("tempo-operacoes");
    const linkTopEmpresas = document.getElementById("top-empresas");
    const linkRecebimentosDia= document.getElementById("recebimento-dia");
    const linkTendenciaMensal = document.getElementById("tendencia-mensal");

    /* Área de Recebimento - Controle de exibição dos gráficos */

// Quantidade Total
linkQuantidadeRecebimento.addEventListener("click", function() {
    if (containerQuantidadeRecebimentoSemanal.style.display === "none" && containerQuantidadeRecebimentoMensal.style.display === "none" ){
        containerQuantidadeRecebimentoSemanal.style.display = "block";
        containerQuantidadeRecebimentoMensal.style.display = "block";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarQuantidadeRecebidaEGraficos(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkTempoMedioAnalise.addEventListener("click", function() {
    if (containerTempoMedioAnalise.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "block";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoTempoMedio(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkRecebimentoSetor.addEventListener("click", function() {
    if (containerRecebimentoSetor.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "block";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoSetor(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkRecebimentoOperador.addEventListener("click", function() {
    if (containerRecebimentoOperador.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "block";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoOperador(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkOperacaoOrigemDestino.addEventListener("click", function() {
    if (containerOperacaoOrigemDestino.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "block";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoOperacoes(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkTaxaRejeicao.addEventListener("click", function() {
    if (containerTaxaRejeicao.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "block";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoRejeicao(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkTempoOperacoes.addEventListener("click", function() {
    if (containerTempoOperacoes.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "block";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoTempoOperacoes(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkTopEmpresas.addEventListener("click", function() {
    if (containerTopEmpresas.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "block";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoEmpresas(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkRecebimentosDia.addEventListener("click", function() {
    if (containerRecebimentosDia.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "block";
        containerTendenciaMensal.style.display = "none";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoDiaSemana(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});
linkTendenciaMensal.addEventListener("click", function() {
    if (containerTendenciaMensal.style.display === "none") {
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "block";

        const dataInicio = document.getElementById("data_inicial").value || "";
        const dataFim = document.getElementById("data_final").value || "";
        carregarGraficoTendenciaMensal(dataInicio, dataFim);
    }else{
        containerQuantidadeRecebimentoSemanal.style.display = "none";
        containerQuantidadeRecebimentoMensal.style.display = "none";
        containerTempoMedioAnalise.style.display = "none";
        containerRecebimentoSetor.style.display = "none";
        containerRecebimentoOperador.style.display = "none";
        containerOperacaoOrigemDestino.style.display = "none";
        containerTaxaRejeicao.style.display = "none";
        containerTempoOperacoes.style.display = "none";
        containerTopEmpresas.style.display = "none";
        containerRecebimentosDia.style.display = "none";
        containerTendenciaMensal.style.display = "none";
    }
});

     /*Area da analise*/
    const containerQuantidadeFinalisadaSemanal = document.getElementById("graficoQuantidadeFinalisadaSemanal");
    const containerQuantidadeFinalisadaMensal = document.getElementById("graficoQuantidadeFinalisadaMensal");
    const containerQuantidadeReparadaOperador = document.getElementById("graficoQuantidadeReparadaOperador");
    const containerTicketMedio = document.getElementById("graficoTicketContainer");
    const containerTempoOrcamento = document.getElementById("graficoTempoOrcamentoContainer");
    const containerVolumeAnalise = document.getElementById("graficoVolumeAnalisesContainer");
    const containerTempoMedio = document.getElementById("graficoTempoMedioAnaliseContainer");
    const containerParcialCompleta = document.getElementById("graficoParcialCompletaContainer");
    const containerAnalisesCliente = document.getElementById("graficoAnalisesClienteContainer");

    const linkQuantidadeAnalise = document.getElementById("equipamentos_finalizados");
    const linkFinanceiro = document.getElementById("financeiro");
    const linkTempoOrcamento = document.getElementById("tempo_orcamento");
    const linkVolumeAnalise = document.getElementById("volume_analises");
    const linkTempoMedio = document.getElementById("tempo_medio_analise");
    const linkParcialCompleta = document.getElementById("parcial_vs_completa");
    const linkAnalisesCliente = document.getElementById("analises_por_cliente");

    linkQuantidadeAnalise.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerQuantidadeFinalisadaSemanal.style.display === "none" && containerQuantidadeFinalisadaMensal.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "block";
           containerQuantidadeFinalisadaMensal.style.display = "block";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";
           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarProdutividadeAnalise(dataInicio, dataFim);

        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkFinanceiro.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerTicketMedio.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "block";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarTicketMedio(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkTempoOrcamento.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerTempoOrcamento.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "block";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarTempoOrcamento(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkVolumeAnalise.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerVolumeAnalise.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "block";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarVolumeAnalises(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkTempoMedio.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerTempoMedio.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "block";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarTempoMedioAnalise(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkParcialCompleta.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerParcialCompleta.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "block";
           containerAnalisesCliente.style.display = "none";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarParcialCompleta(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });
    linkAnalisesCliente.addEventListener("click", function() {
    console.log("Clicou em Equipamentos Finalizados");
       if (containerAnalisesCliente.style.display === "none") {
           console.log("Mostrando gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "block";

           const dataInicio = document.getElementById("data_inicial").value || "";
           const dataFim = document.getElementById("data_final").value || "";
           carregarAnalisesPorCliente(dataInicio, dataFim);
        } else {
           console.log("Escondendo gráficos");
           containerQuantidadeFinalisadaSemanal.style.display = "none";
           containerQuantidadeFinalisadaMensal.style.display = "none";
           containerTicketMedio.style.display = "none";
           containerTempoOrcamento.style.display = "none";
           containerVolumeAnalise.style.display = "none";
           containerTempoMedio.style.display = "none";
           containerParcialCompleta.style.display = "none";
           containerAnalisesCliente.style.display = "none";

        }
    });

    /*Area do reparo*/
    const linkQuantidadeReparada = document.getElementById("quantidade_reparados");
    const linkEquipamentoReparados = document.getElementById("equipamentos_reparados");
    const linkTemposolicitacaoNF = document.getElementById("tempoMedioSolicitacaoNf");
    const linkTempoReparoOperador = document.getElementById("tempoMedioReparoOperador");
    const linkTotalEquipamentoCliente = document.getElementById("reparoPorCliente");
    const linkQuantidadeProduto = document.getElementById("quantidadeProdutoRemessa");
    const linkServicosExecutados = document.getElementById("principaisServiços");
    const linkCustoTotal = document.getElementById("custoTotalReparos");
    const linkCustoMedio = document.getElementById("custoMedioReparo");
    const linkMaiorCustoAcumulado = document.getElementById("maiorCustoAcumulado");
   

    const containerQuantidadeReparadaSemanal = document.getElementById("graficoQuantidadeReparadaSemanal");
    const containerQuantidadeReparadaMensal = document.getElementById("graficoQuantidadeReparadaMensal");
    const containerEquipamentosReparados = document.getElementById("graficoQuantidadeReparadaOperador");
    const containerTemposolicitacaoNF = document.getElementById("graficoTempoSolicitacaoNf");
    const containerTempoReparoOperador = document.getElementById("graficoTempoReparoOperador");
    const containerTotalEquipamentoCliente = document.getElementById("graficoTotalReparoCliente");
    const containerServicosExecutados = document.getElementById("graficoServicosExecutados");
    const containerQuantidadeProduto = document.getElementById("graficoPorProduto");
    const containerCustoTotal = document.getElementById("graficoCustoTotal");
    const containerCustoMedio = document.getElementById("graficoCustoMedio");
    const containerMaiorCustoAcumulado = document.getElementById("containerMaiorCustoProduto");

    linkQuantidadeReparada.addEventListener("click", function(){
        if(containerQuantidadeReparadaSemanal.style.display === "none" && containerQuantidadeReparadaMensal.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "block";
            containerQuantidadeReparadaMensal.style.display = "block";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarProdutividadeReparo(dataInicio, dataFim);
            
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkEquipamentoReparados.addEventListener("click", function(){
        if(containerEquipamentosReparados.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "block";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarEquipamentosPorOperador(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkTemposolicitacaoNF.addEventListener("click", function(){
        if(containerTemposolicitacaoNF.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "block";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarTempoSolicitacaoNF(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkTempoReparoOperador.addEventListener("click", function(){
        if(containerTempoReparoOperador.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "block";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarTempoReparoOperador(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkTotalEquipamentoCliente.addEventListener("click", function(){
        if(containerTotalEquipamentoCliente.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "block";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
           
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarReparosPorCliente(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkServicosExecutados.addEventListener("click", function(){
        if(containerServicosExecutados.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "block";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarServicosExecutados(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkQuantidadeProduto.addEventListener("click", function(){
        if(containerQuantidadeProduto.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "block";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarProdutosMaisReparados(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkCustoTotal.addEventListener("click", function(){
        if(containerCustoTotal.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "block";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarCustoTotalPorProduto(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkCustoMedio.addEventListener("click", function(){
        if(containerCustoMedio.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "block";
            containerMaiorCustoAcumulado.style.display = "none";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarCustoMedioPorProduto(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
    linkMaiorCustoAcumulado.addEventListener("click", function(){
        if(containerMaiorCustoAcumulado.style.display === "none"){
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "block";
            

            const dataInicio = document.getElementById("data_inicial").value || "";
            const dataFim = document.getElementById("data_final").value || "";
            carregarProdutosMaiorCusto(dataInicio, dataFim);
        }else{
            containerQuantidadeReparadaSemanal.style.display = "none";
            containerQuantidadeReparadaMensal.style.display = "none";
            containerEquipamentosReparados.style.display = "none";
            containerTemposolicitacaoNF.style.display = "none";
            containerTempoReparoOperador.style.display = "none";
            containerTotalEquipamentoCliente.style.display = "none";
            containerServicosExecutados.style.display = "none";
            containerQuantidadeProduto.style.display = "none";
            containerCustoTotal.style.display = "none";
            containerCustoMedio.style.display = "none";
            containerMaiorCustoAcumulado.style.display = "none";
            
        }
    });
});
</script>
</body>
</html>
