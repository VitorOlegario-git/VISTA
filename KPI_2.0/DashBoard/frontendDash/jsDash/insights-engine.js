/* ===============================================================
   SUNLAB - MOTOR DE INSIGHTS AUTOMATIZADOS
   Versão: 1.0
   Objetivo: Detectar exceções e gerar insights acionáveis
   =============================================================== */

/**
 * MOTOR DE INSIGHTS
 * Analisa dados operacionais e gera insights baseados em exceções
 */
class InsightsEngine {
    constructor() {
        this.insights = [];
        this.historico = this.carregarHistorico();
        this.limiteInsights = 3;
    }

    /**
     * Carrega histórico do localStorage (média dos últimos 30 dias)
     */
    carregarHistorico() {
        const historicoSalvo = localStorage.getItem('sunlab_historico');
        if (historicoSalvo) {
            return JSON.parse(historicoSalvo);
        }
        
        // Valores padrão baseados em histórico típico
        return {
            volumeMedio: 850,
            tempoMedioRecebimento: 2.3,
            tempoMedioAnalise: 5.5,
            tempoMedioReparo: 11.8,
            tempoMedioQualidade: 3.0,
            tempoMedioExpedicao: 1.7,
            taxaSemConsertoMedia: 11.2,
            custoMedio: 165,
            valorOrcadoMedio: 185000,
            ultimaAtualizacao: new Date().toISOString()
        };
    }

    /**
     * Salva histórico no localStorage
     */
    salvarHistorico(dados) {
        const historicoAtual = this.carregarHistorico();
        const novoHistorico = { ...historicoAtual, ...dados, ultimaAtualizacao: new Date().toISOString() };
        localStorage.setItem('sunlab_historico', JSON.stringify(novoHistorico));
        this.historico = novoHistorico;
    }

    /**
     * ANÁLISE PRINCIPAL - Gera todos os insights
     * @param {Object} dados - Dados do dashboard (KPIs, fluxo, qualidade, financeiro)
     * @returns {Array} Lista de insights priorizados
     */
    analisar(dados) {
        this.insights = [];

        // Executar todas as análises
        this.analisarVolume(dados.volume);
        this.analisarTempo(dados.tempo);
        this.analisarQualidade(dados.qualidade);
        this.analisarFinanceiro(dados.financeiro);
        this.analisarClienteProduto(dados.clienteProduto);

        // Priorizar por gravidade e limitar quantidade
        return this.priorizarInsights();
    }

    /**
     * 🔵 ANÁLISE DE VOLUME
     * Detecta volume significativamente acima ou abaixo do normal
     */
    analisarVolume(volumeData) {
        if (!volumeData || !volumeData.total) return;

        const volumeAtual = volumeData.total;
        const volumeMedio = this.historico.volumeMedio;
        const variacao = ((volumeAtual - volumeMedio) / volumeMedio) * 100;

        // Regra: Volume 20% acima do normal
        if (variacao > 20) {
            this.insights.push({
                id: 'volume_alto',
                type: 'warning',
                category: 'volume',
                priority: 2,
                title: 'Volume acima do normal',
                message: `O volume processado (${volumeAtual} equipamentos) está ${variacao.toFixed(0)}% acima da média histórica. Verifique capacidade operacional.`,
                action: {
                    label: 'Ver Recebimento',
                    link: 'DashRecebimento.php#recebimento'
                },
                metadata: {
                    volumeAtual,
                    volumeMedio,
                    variacao
                }
            });
        }

        // Regra: Volume 30% abaixo do normal (crítico para negócio)
        if (variacao < -30) {
            this.insights.push({
                id: 'volume_baixo',
                type: 'critical',
                category: 'volume',
                priority: 1,
                title: 'Queda crítica no volume',
                message: `Volume processado está ${Math.abs(variacao).toFixed(0)}% abaixo da média. Requer atenção imediata.`,
                action: {
                    label: 'Investigar',
                    link: 'DashRecebimento.php#recebimento'
                },
                metadata: {
                    volumeAtual,
                    volumeMedio,
                    variacao
                }
            });
        }
    }

    /**
     * 🟠 ANÁLISE DE TEMPO / GARGALO
     * Detecta etapas com tempo acima do esperado
     */
    analisarTempo(tempoData) {
        if (!tempoData || !tempoData.etapas) return;

        const etapas = tempoData.etapas;
        const limiarAumento = 15; // 15% de aumento é significativo

        // Mapear nomes amigáveis e histórico
        const etapasConfig = {
            recebimento: { nome: 'Recebimento', historico: this.historico.tempoMedioRecebimento },
            analise: { nome: 'Análise', historico: this.historico.tempoMedioAnalise },
            reparo: { nome: 'Reparo', historico: this.historico.tempoMedioReparo },
            qualidade: { nome: 'Qualidade', historico: this.historico.tempoMedioQualidade },
            expedicao: { nome: 'Expedição', historico: this.historico.tempoMedioExpedicao }
        };

        // Analisar cada etapa
        Object.keys(etapas).forEach(etapa => {
            const tempoAtual = etapas[etapa];
            const config = etapasConfig[etapa];
            
            if (!config) return;

            const tempoMedio = config.historico;
            const variacao = ((tempoAtual - tempoMedio) / tempoMedio) * 100;

            // Regra: Tempo 15% acima do normal
            if (variacao > limiarAumento) {
                // Crítico se for Reparo (etapa mais longa e cara)
                const tipoCriticidade = etapa === 'reparo' ? 'critical' : 'warning';
                const prioridade = etapa === 'reparo' ? 1 : 2;

                this.insights.push({
                    id: `gargalo_${etapa}`,
                    type: tipoCriticidade,
                    category: 'tempo',
                    priority: prioridade,
                    title: 'Gargalo operacional detectado',
                    message: `A etapa de ${config.nome} está levando ${variacao.toFixed(0)}% mais tempo que o normal (${tempoAtual.toFixed(1)}d vs. média de ${tempoMedio.toFixed(1)}d).`,
                    action: {
                        label: 'Ver Análise de Fluxo',
                        link: 'DashRecebimento.php#' + etapa
                    },
                    metadata: {
                        etapa,
                        tempoAtual,
                        tempoMedio,
                        variacao
                    }
                });
            }
        });
    }

    /**
     * 🔴 ANÁLISE DE QUALIDADE
     * Detecta degradação na taxa de sucesso
     */
    analisarQualidade(qualidadeData) {
        if (!qualidadeData || qualidadeData.taxaSemConserto === undefined) return;

        const taxaAtual = qualidadeData.taxaSemConserto;
        const taxaMedia = this.historico.taxaSemConsertoMedia;
        const variacao = taxaAtual - taxaMedia;

        // Regra: Taxa de sem conserto acima da média
        if (variacao > 2) { // Acima de 2 pontos percentuais é relevante
            const tipo = variacao > 5 ? 'critical' : 'warning';
            const prioridade = variacao > 5 ? 1 : 2;

            this.insights.push({
                id: 'qualidade_queda',
                type: tipo,
                category: 'qualidade',
                priority: prioridade,
                title: 'Taxa de sem conserto elevada',
                message: `${taxaAtual.toFixed(1)}% dos equipamentos sem conserto (média: ${taxaMedia.toFixed(1)}%). Analisar causas recorrentes.`,
                action: {
                    label: 'Ver Qualidade',
                    link: 'DashRecebimento.php#qualidade'
                },
                metadata: {
                    taxaAtual,
                    taxaMedia,
                    variacao
                }
            });
        }

        // Análise de laudos recorrentes (se disponível)
        if (qualidadeData.laudosRecorrentes && qualidadeData.laudosRecorrentes.length > 0) {
            const laudoPrincipal = qualidadeData.laudosRecorrentes[0];
            
            // Se um laudo representa mais de 25% do total
            if (laudoPrincipal.percentual > 25) {
                this.insights.push({
                    id: 'laudo_recorrente',
                    type: 'info',
                    category: 'qualidade',
                    priority: 3,
                    title: 'Falha recorrente identificada',
                    message: `"${laudoPrincipal.nome}" representa ${laudoPrincipal.percentual.toFixed(0)}% dos casos. Considerar ação preventiva.`,
                    action: {
                        label: 'Ver Laudos',
                        link: 'DashRecebimento.php#qualidade'
                    },
                    metadata: {
                        laudo: laudoPrincipal.nome,
                        percentual: laudoPrincipal.percentual
                    }
                });
            }
        }
    }

    /**
     * 🟣 ANÁLISE FINANCEIRA
     * Detecta risco financeiro (custo ↑ e receita ↓)
     */
    analisarFinanceiro(financeiroData) {
        if (!financeiroData || !financeiroData.custoMedio || !financeiroData.valorOrcado) return;

        const custoAtual = financeiroData.custoMedio;
        const custoMedio = this.historico.custoMedio;
        const variacaoCusto = ((custoAtual - custoMedio) / custoMedio) * 100;

        const valorAtual = financeiroData.valorOrcado;
        const valorMedio = this.historico.valorOrcadoMedio;
        const variacaoValor = ((valorAtual - valorMedio) / valorMedio) * 100;

        // Regra: Custo aumentando E valor diminuindo (tesoura abrindo)
        if (variacaoCusto > 10 && variacaoValor < -10) {
            this.insights.push({
                id: 'risco_financeiro',
                type: 'critical',
                category: 'financeiro',
                priority: 1,
                title: 'Risco financeiro detectado',
                message: `Custo médio subiu ${variacaoCusto.toFixed(0)}% enquanto valor orçado caiu ${Math.abs(variacaoValor).toFixed(0)}%. Revisar precificação.`,
                action: {
                    label: 'Ver Financeiro',
                    link: 'DashRecebimento.php#financeiro'
                },
                metadata: {
                    custoAtual,
                    custoMedio,
                    variacaoCusto,
                    valorAtual,
                    valorMedio,
                    variacaoValor
                }
            });
        }

        // Regra: Custo muito alto isoladamente
        if (variacaoCusto > 25) {
            this.insights.push({
                id: 'custo_alto',
                type: 'warning',
                category: 'financeiro',
                priority: 2,
                title: 'Custos operacionais elevados',
                message: `Custo médio ${variacaoCusto.toFixed(0)}% acima do padrão. Avaliar uso de peças e produtos.`,
                action: {
                    label: 'Ver Custos',
                    link: 'DashRecebimento.php#financeiro'
                },
                metadata: {
                    custoAtual,
                    custoMedio,
                    variacaoCusto
                }
            });
        }
    }

    /**
     * 🟢 ANÁLISE DE CLIENTE/PRODUTO
     * Detecta concentração de problemas em cliente ou produto específico
     */
    analisarClienteProduto(clienteProdutoData) {
        if (!clienteProdutoData) return;

        // Análise de clientes
        if (clienteProdutoData.clientes && clienteProdutoData.clientes.length > 0) {
            const clientePrincipal = clienteProdutoData.clientes[0];
            
            // Se um cliente representa mais de 30% do volume E tem alta taxa de problema
            if (clientePrincipal.percentualVolume > 30 && clienteProdutoData.taxaProblemaCliente > 15) {
                this.insights.push({
                    id: 'cliente_critico',
                    type: 'warning',
                    category: 'cliente',
                    priority: 2,
                    title: 'Cliente com alto impacto',
                    message: `${clientePrincipal.nome} concentra ${clientePrincipal.percentualVolume.toFixed(0)}% do volume com ${clienteProdutoData.taxaProblemaCliente.toFixed(0)}% de sem conserto. Revisar processo.`,
                    action: {
                        label: 'Ver Análise',
                        link: 'DashRecebimento.php#analise'
                    },
                    metadata: {
                        cliente: clientePrincipal.nome,
                        percentualVolume: clientePrincipal.percentualVolume,
                        taxaProblema: clienteProdutoData.taxaProblemaCliente
                    }
                });
            }
        }

        // Análise de produtos
        if (clienteProdutoData.produtos && clienteProdutoData.produtos.length > 0) {
            const produtoPrincipal = clienteProdutoData.produtos[0];
            
            // Se um produto tem alto volume E alta taxa de sem conserto
            if (produtoPrincipal.volume > 100 && produtoPrincipal.taxaSemConserto > 18) {
                this.insights.push({
                    id: 'produto_critico',
                    type: 'warning',
                    category: 'cliente',
                    priority: 2,
                    title: 'Produto problemático identificado',
                    message: `${produtoPrincipal.nome}: ${produtoPrincipal.volume} equipamentos com ${produtoPrincipal.taxaSemConserto.toFixed(0)}% de sem conserto. Avaliar viabilidade.`,
                    action: {
                        label: 'Ver Qualidade',
                        link: 'DashRecebimento.php#qualidade'
                    },
                    metadata: {
                        produto: produtoPrincipal.nome,
                        volume: produtoPrincipal.volume,
                        taxaSemConserto: produtoPrincipal.taxaSemConserto
                    }
                });
            }
        }
    }

    /**
     * PRIORIZAÇÃO E LIMITAÇÃO
     * Retorna apenas os insights mais relevantes
     */
    priorizarInsights() {
        // Ordenar por prioridade (1 = mais crítico) e depois por tipo
        const ordenacao = {
            'critical': 1,
            'warning': 2,
            'info': 3
        };

        const insightsOrdenados = this.insights.sort((a, b) => {
            // Primeiro por priority (menor = mais importante)
            if (a.priority !== b.priority) {
                return a.priority - b.priority;
            }
            // Depois por type
            return ordenacao[a.type] - ordenacao[b.type];
        });

        // Retornar apenas os 3 primeiros
        return insightsOrdenados.slice(0, this.limiteInsights);
    }

    /**
     * ATUALIZAÇÃO DE HISTÓRICO
     * Atualiza médias com base nos dados atuais
     */
    atualizarHistorico(dadosAtuais) {
        const novoHistorico = {};

        if (dadosAtuais.volume && dadosAtuais.volume.total) {
            // Média móvel simples (70% histórico + 30% atual)
            novoHistorico.volumeMedio = Math.round(
                (this.historico.volumeMedio * 0.7) + (dadosAtuais.volume.total * 0.3)
            );
        }

        if (dadosAtuais.tempo && dadosAtuais.tempo.etapas) {
            const etapas = dadosAtuais.tempo.etapas;
            if (etapas.recebimento) {
                novoHistorico.tempoMedioRecebimento = 
                    (this.historico.tempoMedioRecebimento * 0.7) + (etapas.recebimento * 0.3);
            }
            if (etapas.analise) {
                novoHistorico.tempoMedioAnalise = 
                    (this.historico.tempoMedioAnalise * 0.7) + (etapas.analise * 0.3);
            }
            if (etapas.reparo) {
                novoHistorico.tempoMedioReparo = 
                    (this.historico.tempoMedioReparo * 0.7) + (etapas.reparo * 0.3);
            }
            if (etapas.qualidade) {
                novoHistorico.tempoMedioQualidade = 
                    (this.historico.tempoMedioQualidade * 0.7) + (etapas.qualidade * 0.3);
            }
            if (etapas.expedicao) {
                novoHistorico.tempoMedioExpedicao = 
                    (this.historico.tempoMedioExpedicao * 0.7) + (etapas.expedicao * 0.3);
            }
        }

        if (dadosAtuais.qualidade && dadosAtuais.qualidade.taxaSemConserto !== undefined) {
            novoHistorico.taxaSemConsertoMedia = 
                (this.historico.taxaSemConsertoMedia * 0.7) + (dadosAtuais.qualidade.taxaSemConserto * 0.3);
        }

        if (dadosAtuais.financeiro) {
            if (dadosAtuais.financeiro.custoMedio) {
                novoHistorico.custoMedio = 
                    (this.historico.custoMedio * 0.7) + (dadosAtuais.financeiro.custoMedio * 0.3);
            }
            if (dadosAtuais.financeiro.valorOrcado) {
                novoHistorico.valorOrcadoMedio = 
                    (this.historico.valorOrcadoMedio * 0.7) + (dadosAtuais.financeiro.valorOrcado * 0.3);
            }
        }

        this.salvarHistorico(novoHistorico);
    }

    /**
     * LIMPAR HISTÓRICO (para testes ou reset)
     */
    limparHistorico() {
        localStorage.removeItem('sunlab_historico');
        this.historico = this.carregarHistorico();
    }

    /**
     * EXPORTAR INSIGHTS (para logs ou análise)
     */
    exportarInsights() {
        return {
            timestamp: new Date().toISOString(),
            total: this.insights.length,
            exibidos: Math.min(this.insights.length, this.limiteInsights),
            insights: this.priorizarInsights(),
            historico: this.historico
        };
    }
}

/**
 * FUNÇÃO DE CONVENIÊNCIA - Criar e analisar em uma chamada
 */
function gerarInsights(dados) {
    const engine = new InsightsEngine();
    const insights = engine.analisar(dados);
    
    // Atualizar histórico para próximas análises
    engine.atualizarHistorico(dados);
    
    return insights;
}

/**
 * EXPORTAR PARA USO GLOBAL
 */
if (typeof window !== 'undefined') {
    window.InsightsEngine = InsightsEngine;
    window.gerarInsights = gerarInsights;
}
