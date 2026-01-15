/* ===============================================================
   SUNLAB - MOTOR DE INSIGHTS AUTOMATIZADOS v2.0
   Versão: 2.0 - Catálogo Oficial de KPIs
   Objetivo: Detectar exceções e gerar insights acionáveis
   =============================================================== */

/**
 * MOTOR DE INSIGHTS v2.0
 * Baseado exclusivamente nos 5 KPIs globais oficiais:
 * 1. Remessas Recebidas (COUNT)
 * 2. Equipamentos Recebidos (SUM quantidade)
 * 3. Equipamentos Expedidos (SUM quantidade)
 * 4. Taxa de Conclusão Técnica (%)
 * 5. Valor Total Orçado (R$)
 */
class InsightsEngineV2 {
    constructor() {
        this.insights = [];
        this.limiteInsights = 3;
    }

    /**
     * ANÁLISE PRINCIPAL - Gera insights baseados nos KPIs oficiais
     * @param {Object} kpis - Dados dos 5 KPIs globais oficiais
     * @returns {Array} Lista de insights priorizados (máximo 3)
     */
    analisar(kpis) {
        this.insights = [];

        // Validar entrada
        if (!kpis || !kpis.remessas || !kpis.equipRec || !kpis.equipExp || !kpis.conclusao || !kpis.valor) {
            console.warn('Dados incompletos para análise de insights');
            return [];
        }

        // Executar todas as análises
        this.analisarGargaloOperacional(kpis);
        this.analisarQuedaEficiencia(kpis);
        this.analisarCrescimentoComRisco(kpis);
        this.analisarOperacaoSaudavel(kpis);

        // Priorizar por gravidade e limitar quantidade
        return this.priorizarInsights();
    }

    /**
     * 🔴 INSIGHT 1: GARGALO OPERACIONAL
     * Detecta: Equipamentos recebidos ↑ e expedidos ↓
     * Causa: Acúmulo de equipamentos em processo
     */
    analisarGargaloOperacional(kpis) {
        const variacaoRecebidos = kpis.equipRec.referencia?.variacao || 0;
        const variacaoExpedidos = kpis.equipExp.referencia?.variacao || 0;
        const equipRecebidos = kpis.equipRec.valor || 0;
        const equipExpedidos = kpis.equipExp.valor || 0;

        // Regra: Recebidos cresceram E expedidos caíram
        if (variacaoRecebidos > 10 && variacaoExpedidos < -5) {
            const acumulo = equipRecebidos - equipExpedidos;
            
            this.insights.push({
                id: 'gargalo_operacional',
                type: 'critical',
                category: 'operacional',
                priority: 1,
                title: '🚨 Gargalo Operacional Detectado',
                message: `Entrada cresceu ${variacaoRecebidos.toFixed(1)}% mas saída caiu ${Math.abs(variacaoExpedidos).toFixed(1)}%. Acúmulo de ${acumulo.toLocaleString('pt-BR')} equipamentos em processo.`,
                causa: 'Possíveis causas: falta de recursos, análise lenta, peças em falta ou gargalo na qualidade.',
                acao: 'Priorize expedição de equipamentos finalizados e revise capacidade das etapas intermediárias.',
                action: {
                    label: 'Analisar Fluxo',
                    link: 'DashRecebimento.php#fluxo'
                },
                metadata: {
                    variacaoRecebidos,
                    variacaoExpedidos,
                    acumulo,
                    equipRecebidos,
                    equipExpedidos
                }
            });
        }
    }

    /**
     * 🟠 INSIGHT 2: QUEDA DE EFICIÊNCIA TÉCNICA
     * Detecta: Taxa de conclusão ↓ mais de 10pp
     * Causa: Equipamentos não estão sendo finalizados
     */
    analisarQuedaEficiencia(kpis) {
        const taxaAtual = kpis.conclusao.valor || 0;
        const variacaoPP = kpis.conclusao.referencia?.variacao || 0;
        const equipRecebidos = kpis.conclusao.detalhes?.recebidos || 0;
        const equipExpedidos = kpis.conclusao.detalhes?.expedidos || 0;

        // Regra: Taxa caiu mais de 10 pontos percentuais
        if (variacaoPP < -10) {
            const deficit = equipRecebidos - equipExpedidos;
            
            this.insights.push({
                id: 'queda_eficiencia',
                type: 'warning',
                category: 'eficiencia',
                priority: 2,
                title: '⚠️ Queda de Eficiência Técnica',
                message: `Taxa de conclusão caiu ${Math.abs(variacaoPP).toFixed(1)}pp para ${taxaAtual}%. Apenas ${equipExpedidos} de ${equipRecebidos} equipamentos foram finalizados.`,
                causa: 'Possíveis causas: aumento de laudos sem conserto, lentidão no reparo ou análises incompletas.',
                acao: 'Revise laudos "Sem Conserto", priorize reparos simples e agilize aprovações de orçamento.',
                action: {
                    label: 'Ver Qualidade',
                    link: 'DashRecebimento.php#qualidade'
                },
                metadata: {
                    taxaAtual,
                    variacaoPP,
                    deficit,
                    equipRecebidos,
                    equipExpedidos
                }
            });
        }
    }

    /**
     * 🟡 INSIGHT 3: CRESCIMENTO FINANCEIRO COM RISCO
     * Detecta: Valor orçado ↑ mas taxa de conclusão ↓
     * Causa: Aumentaram orçamentos mas não finalizaram equipamentos
     */
    analisarCrescimentoComRisco(kpis) {
        const variacaoValor = kpis.valor.referencia?.variacao || 0;
        const variacaoConclusao = kpis.conclusao.referencia?.variacao || 0;
        const taxaAtual = kpis.conclusao.valor || 0;
        const valorAtual = kpis.valor.valor || '0,00';

        // Regra: Valor cresceu E taxa caiu
        if (variacaoValor > 15 && variacaoConclusao < -5) {
            this.insights.push({
                id: 'crescimento_com_risco',
                type: 'warning',
                category: 'financeiro',
                priority: 3,
                title: '💰 Crescimento com Risco Operacional',
                message: `Valor orçado subiu ${variacaoValor.toFixed(1)}% (R$ ${valorAtual}), mas taxa de conclusão caiu ${Math.abs(variacaoConclusao).toFixed(1)}pp para ${taxaAtual}%.`,
                causa: 'Possíveis causas: orçamentos aprovados mas reparos não iniciados, ou aumento de equipamentos complexos.',
                acao: 'Priorize conclusão de reparos aprovados para evitar acúmulo. Revise prazo médio de finalização.',
                action: {
                    label: 'Ver Financeiro',
                    link: 'DashRecebimento.php#financeiro'
                },
                metadata: {
                    variacaoValor,
                    variacaoConclusao,
                    taxaAtual,
                    valorAtual
                }
            });
        }
    }

    /**
     * ✅ INSIGHT 4: OPERAÇÃO SAUDÁVEL
     * Detecta: Taxa ≥ 85% E expedidos ≥ recebidos
     * Situação: Operação em equilíbrio
     */
    analisarOperacaoSaudavel(kpis) {
        const taxaAtual = kpis.conclusao.valor || 0;
        const equipRecebidos = kpis.equipRec.valor || 0;
        const equipExpedidos = kpis.equipExp.valor || 0;
        const variacaoValor = kpis.valor.referencia?.variacao || 0;

        // Regra: Taxa alta E expedidos >= recebidos
        if (taxaAtual >= 85 && equipExpedidos >= equipRecebidos) {
            // Se já houver insights críticos ou de warning, não exibir "saudável"
            const temProblemas = this.insights.some(i => i.type === 'critical' || i.type === 'warning');
            
            if (!temProblemas) {
                this.insights.push({
                    id: 'operacao_saudavel',
                    type: 'info',
                    category: 'status',
                    priority: 4,
                    title: '✅ Operação em Equilíbrio',
                    message: `Taxa de conclusão saudável (${taxaAtual}%) com ${equipExpedidos.toLocaleString('pt-BR')} equipamentos expedidos de ${equipRecebidos.toLocaleString('pt-BR')} recebidos.`,
                    causa: 'Sistema operando dentro dos parâmetros esperados. Capacidade adequada para demanda atual.',
                    acao: variacaoValor > 0 
                        ? `Aproveite momentum: valor orçado cresceu ${variacaoValor.toFixed(1)}%. Mantenha ritmo de expedição.`
                        : 'Continue monitorando indicadores para detectar desvios precocemente.',
                    action: {
                        label: 'Ver Dashboard',
                        link: 'DashRecebimento.php'
                    },
                    metadata: {
                        taxaAtual,
                        equipRecebidos,
                        equipExpedidos
                    }
                });
            }
        }
    }

    /**
     * PRIORIZAÇÃO DE INSIGHTS
     * Ordena por prioridade e limita a 3 insights
     */
    priorizarInsights() {
        // Ordenar por prioridade (1 = mais urgente)
        this.insights.sort((a, b) => a.priority - b.priority);

        // Retornar no máximo 3 insights
        return this.insights.slice(0, this.limiteInsights);
    }
}

// Exportar instância global
const insightsEngine = new InsightsEngineV2();

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
