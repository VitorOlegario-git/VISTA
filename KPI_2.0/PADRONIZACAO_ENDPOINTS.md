# 🧱 PADRONIZAÇÃO GLOBAL DE ENDPOINTS — SUNLAB

## 📋 Índice
1. [Visão Geral](#visão-geral)
2. [Contrato de Entrada](#contrato-de-entrada)
3. [Contrato de Saída](#contrato-de-saída)
4. [Helpers Backend (PHP)](#helpers-backend-php)
5. [Helpers Frontend (JavaScript)](#helpers-frontend-javascript)
6. [Exemplos Práticos](#exemplos-práticos)
7. [Classificação de Endpoints](#classificação-de-endpoints)
8. [Checklist de Padronização](#checklist-de-padronização)

---

## 🎯 Visão Geral

**Objetivo**: Garantir que 100% dos dados do sistema:
- ✅ Respeitem o filtro global (data/operador)
- ✅ Retornem JSON sempre válido
- ✅ Se comportem de forma previsível
- ✅ Possam alimentar KPIs, Insights e Gráficos sem ambiguidades

**Arquitetura**:
```
Frontend (DashRecebimento.php)
    ↓ usa fetch-helpers.js
    ↓ constrói URL com filtroGlobal
    ↓
Backend (endpoints PHP)
    ↓ usa endpoint-helpers.php
    ↓ valida parâmetros padrão
    ↓ executa query com WHERE padronizado
    ↓ retorna JSON formatado
```

---

## 📥 Contrato de Entrada

**Parâmetros GET obrigatórios** (mesmo que não usados):

```
inicio   → dd/mm/yyyy
fim      → dd/mm/yyyy
operador → string | null
```

**Código padrão no início de TODOS os endpoints**:

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/endpoint-helpers.php';

validarConexao($conn);

$params = validarParametrosPadrao();
extract($params); // $dataInicio, $dataFim, $operador
```

✅ Isso garante:
- Header JSON automático
- Validação de datas (dd/mm/yyyy → Y-m-d)
- Resposta 400 se formato inválido
- Parâmetros prontos para uso

---

## 📤 Contrato de Saída

### ✅ Sucesso

```json
{
  "meta": {
    "inicio": "2025-12-01",
    "fim": "2025-12-31",
    "operador": "Todos",
    "timestamp": "2026-01-13 14:30:00"
  },
  "data": {
    "valor": 1247,
    "unidade": "equipamentos",
    "periodo": "Últimos 30 dias",
    "contexto": "Processados"
  }
}
```

**Código PHP**:
```php
enviarSucesso($dados, $dataInicio, $dataFim, $operador);
```

### ❌ Erro

```json
{
  "error": true,
  "message": "Descrição clara do erro",
  "timestamp": "2026-01-13 14:30:00"
}
```

**Código PHP**:
```php
enviarErro(400, 'Formato de data inválido');
```

---

## 🔧 Helpers Backend (PHP)

### `validarParametrosPadrao()`
Valida e retorna parâmetros:
```php
$params = validarParametrosPadrao();
// ['dataInicio' => 'Y-m-d', 'dataFim' => 'Y-m-d', 'operador' => string|null]
```

### `construirWherePadrao()`
Constrói WHERE clause segura:
```php
$whereInfo = construirWherePadrao(
    $dataInicio, 
    $dataFim, 
    $operador,
    'data_entrada',        // campo de data na tabela
    'operador_recebimento' // campo operador na tabela
);

// $whereInfo['where'] → "WHERE data_entrada BETWEEN ? AND ? AND operador_recebimento = ?"
// $whereInfo['params'] → ['2025-01-01', '2025-01-31', 'Rony Rodrigues']
// $whereInfo['types'] → 'sss'
```

### `executarQuery()`
Executa query preparada com tratamento de erro:
```php
$result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
$row = $result->fetch_assoc();
```

### `buscarUm()` / `buscarTodos()`
Atalhos para queries simples:
```php
$row = buscarUm($conn, $sql, $params, $types);
$rows = buscarTodos($conn, $sql, $params, $types);
```

### `formatarKPI()`
Formata KPI segundo contrato visual:
```php
$kpi = formatarKPI(
    1247,                           // valor
    'equipamentos',                 // unidade
    'Últimos 30 dias',              // periodo
    'Processados',                  // contexto
    ['icone' => 'fa-box', 'cor' => '#3b82f6'] // extras
);
```

### `enviarSucesso()` / `enviarErro()`
Envia resposta e encerra:
```php
enviarSucesso($dados, $dataInicio, $dataFim, $operador);
// ou
enviarErro(500, 'Erro ao processar consulta');
```

---

## 🎨 Helpers Frontend (JavaScript)

### `fetchKPI(url)`
Fetch padrão com tratamento de erro:
```javascript
const response = await fetchKPI('/endpoint.php');
console.log(response.data); // dados
console.log(response.meta); // metadados
```

### `construirURLFiltrada(baseUrl, filtroGlobal)`
Monta URL com parâmetros do filtro:
```javascript
const url = construirURLFiltrada('/kpis/kpi-total.php', filtroGlobal);
// /kpis/kpi-total.php?inicio=01/01/2025&fim=31/01/2025&operador=Rony
```

### `fetchLote(endpoints)`
Busca múltiplos em paralelo:
```javascript
const respostas = await fetchLote({
    total: '/kpis/kpi-total.php',
    tempo: '/kpis/kpi-tempo.php',
    sucesso: '/kpis/kpi-sucesso.php'
});

console.log(respostas.total.data);
console.log(respostas.tempo.data);
```

### `validarRespostaKPI(response)`
Valida estrutura da resposta:
```javascript
if (!validarRespostaKPI(response)) {
    console.warn('Resposta não está no padrão');
}
```

### `mostrarErroAmigavel(elemento, erro)`
Exibe erro de forma amigável:
```javascript
try {
    const data = await fetchKPI('/endpoint.php');
} catch (error) {
    mostrarErroAmigavel(document.getElementById('container'), error);
}
```

---

## 💡 Exemplos Práticos

### Exemplo 1: KPI Simples (Contagem)

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/endpoint-helpers.php';

validarConexao($conn);
$params = validarParametrosPadrao();
extract($params);

try {
    if (!$dataInicio || !$dataFim) {
        enviarErro(400, 'Período obrigatório');
    }

    $whereInfo = construirWherePadrao($dataInicio, $dataFim, $operador, 'data_entrada', 'operador');

    $sql = "SELECT COUNT(*) as total FROM recebimento {$whereInfo['where']}";
    $result = executarQuery($conn, $sql, $whereInfo['params'], $whereInfo['types']);
    $row = $result->fetch_assoc();

    $kpi = formatarKPI(
        $row['total'],
        'equipamentos',
        formatarPeriodoMeta($dataInicio, $dataFim),
        'Recebidos',
        ['icone' => 'fa-inbox', 'cor' => '#3b82f6']
    );

    enviarSucesso($kpi, $dataInicio, $dataFim, $operador);

} catch (Exception $e) {
    enviarErro(500, 'Erro ao processar KPI');
} finally {
    if (isset($conn)) $conn->close();
}
?>
```

### Exemplo 2: Frontend consumindo KPI

```javascript
async function carregarKPI() {
    try {
        const url = construirURLFiltrada('/kpis/kpi-total.php', filtroGlobal);
        const response = await fetchKPI(url);
        
        if (validarRespostaKPI(response)) {
            document.getElementById('valor').textContent = response.data.valor;
            document.getElementById('periodo').textContent = response.data.periodo;
        }
    } catch (error) {
        console.error('Erro ao carregar KPI:', error);
        document.getElementById('valor').textContent = '---';
    }
}
```

---

## 📊 Classificação de Endpoints

### 🔵 Tipo A — Dependem de data (DEVEM filtrar)

**Exemplos**: Total processado, Tempo médio, Taxa de sucesso

**Obrigatório**:
- Aceitar `inicio` e `fim`
- Validar que não são nulos
- Aplicar `BETWEEN` na query

```php
if (!$dataInicio || !$dataFim) {
    enviarErro(400, 'Período obrigatório para este KPI');
}
```

### 🟢 Tipo B — Mistos (filtram parcialmente)

**Exemplos**: Ranking de produtos no período, Top clientes

**Comportamento**:
- Filtram por data
- Agregam por entidade (cliente, produto)
- Retornam lista ordenada

### 🟣 Tipo C — Históricos fixos (NÃO filtram)

**Exemplos**: Base total de clientes, Produtos cadastrados

**Importante**:
- Devem deixar explícito no `meta`:
```json
"periodo": "historico"
```
- Nunca misturar com KPIs executivos

---

## ✅ Checklist de Padronização

### Backend (PHP)

- [ ] Inclui `endpoint-helpers.php`
- [ ] Usa `validarParametrosPadrao()`
- [ ] Usa `construirWherePadrao()` para queries
- [ ] Usa `executarQuery()` ou `buscarUm()`/`buscarTodos()`
- [ ] Usa `formatarKPI()` para KPIs
- [ ] Usa `enviarSucesso()` ou `enviarErro()`
- [ ] Try-catch em torno de toda lógica
- [ ] Fecha conexão no `finally`
- [ ] Valida se período é obrigatório (Tipo A)

### Frontend (JavaScript)

- [ ] Usa `fetchKPI()` em vez de `fetch()` direto
- [ ] Usa `construirURLFiltrada()` para montar URLs
- [ ] Usa `fetchLote()` para múltiplos endpoints
- [ ] Usa `validarRespostaKPI()` após fetch
- [ ] Trata erros com try-catch
- [ ] Mostra '---' em caso de erro
- [ ] Usa `mostrarErroAmigavel()` quando apropriado

---

## 🚀 Ordem de Padronização

**FAÇA ASSIM** (não tudo ao mesmo tempo):

1. ✅ **KPIs globais** (CONCLUÍDO)
   - kpi-total-processado.php
   - kpi-tempo-medio.php
   - kpi-taxa-sucesso.php
   - kpi-sem-conserto.php
   - kpi-valor-orcado.php

2. ⏳ **Insights** (dependem dos KPIs)
   - endpoint-insights.php

3. ⏳ **Cards por área**
   - resumo-recebimento.php
   - resumo-analise.php
   - resumo-reparo.php
   - resumo-qualidade.php
   - resumo-financeiro.php

4. ⏳ **Gráficos** (um por vez)
   - Recebimento (10 gráficos)
   - Análise (5 gráficos)
   - Reparo (3 gráficos)
   - Qualidade (4 gráficos)
   - Financeiro (3 gráficos)

---

## 📚 Referências Rápidas

### Parâmetros de entrada
```
?inicio=01/01/2025&fim=31/01/2025&operador=Rony Rodrigues
```

### Estrutura mínima de KPI
```json
{
  "valor": 1247,
  "unidade": "equipamentos",
  "periodo": "Últimos 30 dias",
  "contexto": "Processados"
}
```

### Resposta de erro
```json
{
  "error": true,
  "message": "Descrição clara"
}
```

---

## 🆘 Troubleshooting

### Erro: "Formato de data inválido"
**Causa**: Data enviada não está em dd/mm/yyyy  
**Solução**: Use `formatarDataParaURL()` no frontend

### Erro: "Resposta não é JSON válido"
**Causa**: PHP está emitindo warning/notice antes do JSON  
**Solução**: Adicione `error_reporting(0)` no topo do endpoint

### Erro: "Período obrigatório para este KPI"
**Causa**: Endpoint tipo A sem datas  
**Solução**: Sempre envie `inicio` e `fim` para KPIs globais

### KPI mostrando "---"
**Causa**: Erro no fetch ou resposta inválida  
**Solução**: Verifique console do navegador, valide estrutura da resposta

---

**Documento criado em**: 13/01/2026  
**Versão**: 1.0  
**Status**: ✅ Fase 1 (KPIs Globais) Concluída
