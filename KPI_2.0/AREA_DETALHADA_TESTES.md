# 🧪 Guia de Testes - Área Detalhada Recebimento

## ✅ Checklist de Testes

### 1. Testes de Acesso

#### Teste 1.1: Acesso direto via URL
```
URL: http://localhost/KPI_2.0/DashBoard/frontendDash/AreaDetalhada.php?area=recebimento
```
- [ ] Página carrega sem erros
- [ ] Header exibe "Recebimento - Análise Detalhada"
- [ ] Ícone de caminhão (`fa-truck`) aparece
- [ ] Cor azul (`#388bfd`) aplicada ao ícone

#### Teste 1.2: Navegação via Dashboard
```javascript
// No console do DashboardExecutivo.php
navigateTo('recebimento');
```
- [ ] Redirecionamento funciona
- [ ] URL contém `?area=recebimento`
- [ ] Filtros são preservados na URL

#### Teste 1.3: Área inválida
```
URL: AreaDetalhada.php?area=invalido
```
- [ ] Sistema redireciona para área padrão ou exibe erro

---

### 2. Testes de Filtros

#### Teste 2.1: Filtros via URL
```
URL: AreaDetalhada.php?area=recebimento&inicio=2024-01-01&fim=2024-01-31
```
- [ ] Subtítulo exibe "01/01/2024 - 31/01/2024"
- [ ] KPIs refletem o período correto
- [ ] Gráficos mostram apenas dados do período

#### Teste 2.2: Filtros via localStorage
1. Abrir Dashboard Executivo
2. Definir filtros (ex: último mês)
3. Navegar para Recebimento sem filtros na URL
- [ ] Sistema herda filtros do localStorage
- [ ] Período exibido corresponde aos filtros salvos

#### Teste 2.3: Sem filtros (default)
```
URL: AreaDetalhada.php?area=recebimento
```
- [ ] Sistema usa últimos 30 dias
- [ ] Data de hoje - 30 dias é aplicada
- [ ] KPIs carregam com período default

---

### 3. Testes de KPIs

#### Teste 3.1: KPI - Remessas Recebidas
```
Endpoint: /recebimentoPHP/kpi-remessas-recebidas.php?inicio=01/01/2024&fim=31/01/2024
```
- [ ] Card exibe ícone de caminhão
- [ ] Valor numérico aparece
- [ ] Unidade "remessas" visível
- [ ] Variação percentual exibida
- [ ] Ícone de seta (↑/↓/→) correto
- [ ] Cor da borda corresponde ao estado

#### Teste 3.2: KPI - Equipamentos Recebidos
```
Endpoint: /recebimentoPHP/kpi-equipamentos-recebidos.php
```
- [ ] Card exibe ícone de caixa
- [ ] Valor é SUM(quantidade)
- [ ] Unidade "equipamentos" visível

#### Teste 3.3: KPI - Tempo Médio até Análise
```
Endpoint: /recebimentoPHP/kpi-tempo-ate-analise.php
```
- [ ] Card exibe ícone de relógio
- [ ] Valor em dias (decimal)
- [ ] Cores invertidas (menos tempo = verde)

#### Teste 3.4: KPI - Taxa de Envio
```
Endpoint: /recebimentoPHP/kpi-taxa-envio-analise.php
```
- [ ] Card exibe ícone de porcentagem
- [ ] Valor percentual correto
- [ ] Estado verde se >= 95%

#### Teste 3.5: KPI - Backlog
```
Endpoint: /recebimentoPHP/kpi-backlog-atual.php
```
- [ ] Card exibe ícone de ampulheta
- [ ] Valor de equipamentos pendentes
- [ ] Cores invertidas (menos backlog = verde)

---

### 4. Testes de Insights

#### Teste 4.1: Insight de Gargalo
```
Cenário: Mais de 50 remessas pendentes
Endpoint: /recebimentoPHP/insights-recebimento.php
```
- [ ] Card de insight aparece
- [ ] Tipo "warning" (laranja)
- [ ] Título "Backlog Acima do Ideal"
- [ ] Mensagem com quantidade e tempo
- [ ] Causa sugerida
- [ ] Ação recomendada

#### Teste 4.2: Insight de Eficiência
```
Cenário: Operador com tempo médio > 3 dias
```
- [ ] Insight de tipo "info" aparece
- [ ] Título "Diferença de Desempenho"
- [ ] Nome do operador mencionado

#### Teste 4.3: Insight de Crescimento
```
Cenário: Variação > 20% vs período anterior
```
- [ ] Insight de tipo "success" ou "warning"
- [ ] Percentual de crescimento/queda
- [ ] Números comparativos (anterior → atual)

#### Teste 4.4: Insight de Normalidade
```
Cenário: Nenhum alerta detectado
```
- [ ] Insight de tipo "success"
- [ ] Mensagem "Operação Dentro da Normalidade"

---

### 5. Testes de Gráficos

#### Teste 5.1: Gráfico de Evolução Temporal
```
Endpoint: /recebimentoPHP/grafico-volume-diario.php
Canvas: #chartEvolucao
```
- [ ] Gráfico de linha aparece
- [ ] 2 séries (remessas + equipamentos)
- [ ] Eixo X com datas formatadas (DD/MM)
- [ ] Eixo Y com valores inteiros
- [ ] Legenda visível e legível
- [ ] Tooltip funciona ao hover
- [ ] Cores: azul (#388bfd) e ciano (#11cfff)

#### Teste 5.2: Gráfico por Setor
```
Endpoint: /recebimentoPHP/grafico-por-setor.php
Canvas: #chartSetor
```
- [ ] Gráfico de rosca (doughnut) aparece
- [ ] Labels com nomes dos setores
- [ ] Cores variadas e distintas
- [ ] Legenda à direita
- [ ] Tooltip com valor e percentual

#### Teste 5.3: Gráfico de Operações
```
Endpoint: /recebimentoPHP/grafico-operacoes.php
Canvas: #chartOperacoes
```
- [ ] Gráfico de barras horizontal aparece
- [ ] Labels com fluxo "Origem → Destino"
- [ ] Barras ordenadas (maior → menor)
- [ ] Cor azul consistente

#### Teste 5.4: Gráfico de Tempo por Operador
```
Endpoint: /recebimentoPHP/grafico-tempo-medio.php
Canvas: #chartTempo
```
- [ ] Gráfico de barras vertical aparece
- [ ] Operadores no eixo X
- [ ] Tempo em dias no eixo Y
- [ ] Cor roxa (#8b5cf6)

---

### 6. Testes de Tabela

#### Teste 6.1: Carregamento Inicial
```
Endpoint: /recebimentoPHP/tabela-detalhada.php
```
- [ ] Tabela aparece com cabeçalho
- [ ] 8 colunas visíveis
- [ ] Primeiros 20 registros exibidos
- [ ] Contador "X registros" correto
- [ ] Paginação aparece (se > 20 registros)

#### Teste 6.2: Busca
```
Ação: Digitar no campo "Buscar..."
```
- [ ] Filtro em tempo real funciona
- [ ] Busca por Nota Fiscal funciona
- [ ] Busca por Cliente funciona
- [ ] Busca por CNPJ funciona
- [ ] Busca por Operador funciona
- [ ] Resultados atualizam instantaneamente

#### Teste 6.3: Ordenação
```
Seletor: #table-sort
```
- [ ] Opção "Data (Mais recente)" ordena DESC
- [ ] Opção "Data (Mais antiga)" ordena ASC
- [ ] Opção "Quantidade (Maior)" ordena DESC
- [ ] Opção "Quantidade (Menor)" ordena ASC
- [ ] Paginação reseta para página 1

#### Teste 6.4: Paginação
```
Ação: Clicar em botões de página
```
- [ ] Botão "Anterior" desabilitado na página 1
- [ ] Botão "Próximo" desabilitado na última página
- [ ] Números de página clicáveis
- [ ] Página ativa destacada visualmente
- [ ] Reticências (...) aparecem quando necessário
- [ ] Navegar entre páginas atualiza conteúdo

#### Teste 6.5: Status Badges
```
Verificar cores dos badges
```
- [ ] "Enviado Análise" = azul (info)
- [ ] "Em Análise" = laranja (warning)
- [ ] "Concluída" = verde (success)
- [ ] "Aguardando PG" = laranja (warning)

---

### 7. Testes de Navegação

#### Teste 7.1: Botão Voltar
```
Ação: Clicar no botão "← Voltar"
```
- [ ] Retorna para DashboardExecutivo.php
- [ ] Filtros são preservados na URL
- [ ] Dashboard reaplica filtros automaticamente

#### Teste 7.2: Breadcrumb
```
Verificar caminho visual
```
- [ ] Ícone e título da área visíveis
- [ ] Cor da área aplicada corretamente
- [ ] Período exibido no subtítulo

---

### 8. Testes de Estado Vazio

#### Teste 8.1: Sem Dados no Período
```
Cenário: Filtrar período sem registros
```
- [ ] Seção de insights oculta
- [ ] Gráficos exibem "Sem dados"
- [ ] Tabela vazia
- [ ] Empty state aparece com:
  - [ ] Ícone de pasta vazia
  - [ ] Mensagem "Nenhum dado encontrado"
  - [ ] Sugestão de ajustar filtros

---

### 9. Testes de Performance

#### Teste 9.1: Carregamento Inicial
- [ ] KPIs carregam em < 2 segundos
- [ ] Gráficos renderizam em < 3 segundos
- [ ] Tabela aparece em < 2 segundos
- [ ] Skeleton loading visível durante fetch

#### Teste 9.2: Busca na Tabela
- [ ] Filtro aplica em tempo real (< 100ms)
- [ ] Sem lag perceptível ao digitar
- [ ] Resultados atualizam suavemente

#### Teste 9.3: Troca de Página
- [ ] Paginação instantânea (sem fetch)
- [ ] Transição suave entre páginas

---

### 10. Testes de Responsividade

#### Teste 10.1: Desktop (> 1024px)
- [ ] KPIs em grid de 3 colunas
- [ ] Gráficos lado a lado
- [ ] Tabela com scroll horizontal se necessário

#### Teste 10.2: Tablet (768px - 1024px)
- [ ] KPIs em grid de 2 colunas
- [ ] Gráficos empilhados verticalmente
- [ ] Tabela responsiva

#### Teste 10.3: Mobile (< 768px)
- [ ] KPIs em 1 coluna
- [ ] Gráficos em 1 coluna
- [ ] Tabela com scroll
- [ ] Botão "Voltar" acessível

---

### 11. Testes de Erro

#### Teste 11.1: Endpoint Indisponível
```
Simular: Renomear arquivo PHP temporariamente
```
- [ ] Console exibe erro
- [ ] Página não quebra
- [ ] Mensagem de erro amigável (se implementada)

#### Teste 11.2: Timeout de Rede
```
Simular: Desconectar internet durante fetch
```
- [ ] Sistema lida gracefully com erro
- [ ] Loading não fica infinito

#### Teste 11.3: Resposta Inválida
```
Simular: Endpoint retorna HTML em vez de JSON
```
- [ ] Erro capturado no catch
- [ ] Console exibe erro legível

---

### 12. Testes de Integração

#### Teste 12.1: Fluxo Completo
1. Abrir Dashboard Executivo
2. Definir filtros (período, setor)
3. Clicar em "Recebimento"
4. Verificar herança de filtros
5. Analisar KPIs
6. Inspecionar insights
7. Explorar gráficos
8. Buscar na tabela
9. Voltar ao dashboard
- [ ] Todos os passos funcionam sequencialmente
- [ ] Filtros preservados em todo fluxo

#### Teste 12.2: Múltiplas Áreas
```
Navegar: Recebimento → Voltar → Análise → Voltar → Reparo
```
- [ ] Cada área carrega corretamente
- [ ] Contexto preservado entre áreas
- [ ] Sem conflitos de cache

---

## 🐛 Registro de Bugs

### Bug Encontrado
**Descrição**: _[Descrever bug]_  
**Severidade**: Alta / Média / Baixa  
**Passos para Reproduzir**:
1. _[Passo 1]_
2. _[Passo 2]_
3. _[Passo 3]_

**Comportamento Esperado**: _[Descrever]_  
**Comportamento Atual**: _[Descrever]_  
**Screenshot**: _[Anexar se possível]_  
**Console Errors**: _[Copiar erros do console]_

---

## ✅ Relatório de Testes

**Data**: _______________  
**Testador**: _______________  
**Versão**: 1.0

### Resumo
- **Total de Testes**: 60+
- **Testes Aprovados**: ___
- **Testes Falhados**: ___
- **Bugs Críticos**: ___
- **Bugs Médios**: ___
- **Bugs Baixos**: ___

### Conclusão
_[Escrever conclusão do teste]_

### Recomendações
_[Listar recomendações para correções ou melhorias]_

---

## 🔧 Comandos Úteis para Debug

### Verificar Endpoints no Console
```javascript
// No console do navegador
const filtros = obterFiltros();
console.log('Filtros:', filtros);

// Testar endpoint manualmente
fetch('/DashBoard/backendDash/recebimentoPHP/kpi-remessas-recebidas.php?inicio=01/01/2024&fim=31/01/2024')
  .then(r => r.json())
  .then(data => console.log('Resposta KPI:', data))
  .catch(err => console.error('Erro:', err));
```

### Limpar Cache
```javascript
// Limpar localStorage
localStorage.clear();
location.reload();
```

### Recriar Gráficos
```javascript
// Destruir e recriar gráfico específico
if (chartsInstances.evolucao) {
    chartsInstances.evolucao.destroy();
}
carregarGraficoEvolucao();
```

### Forçar Recarregamento
```javascript
// Recarregar todos os dados
carregarDadosArea();
```

---

**Observação**: Este guia deve ser executado sempre que houver alterações significativas no código ou antes de deploy para produção.
