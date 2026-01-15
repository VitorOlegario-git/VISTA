# 📅 HELPER GLOBAL DE PERÍODO - resolvePeriod()

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Status:** ✅ Implementado e Pronto para Uso

---

## 🎯 Objetivo

Criar um **único padrão de resolução de períodos** para todos os KPIs do sistema VISTA, eliminando lógica duplicada e facilitando extensões futuras.

---

## 📐 Assinatura da Função

```php
function resolvePeriod(array $params = []): array
```

### Parâmetros

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `$params` | `array` | Array de parâmetros (tipicamente `$_GET`) |

### Retorno

Retorna um array associativo com:

```php
[
    'inicio' => 'Y-m-d',       // Data inicial normalizada
    'fim' => 'Y-m-d',          // Data final normalizada
    'tipo' => 'string',        // Tipo de período usado
    'descricao' => 'string',   // Descrição legível
    'dias' => int              // Número de dias no período
]
```

---

## 🔧 Modos de Operação

### Modo 1: Períodos Pré-Definidos (Recomendado)

Use o parâmetro `period` para períodos comuns:

#### Períodos Disponíveis:

| Valor | Descrição | Exemplo |
|-------|-----------|---------|
| `today` | Dia atual | Hoje (15/01/2026) |
| `yesterday` | Dia anterior | Ontem (14/01/2026) |
| `last_7_days` | Últimos 7 dias | 08/01 a 15/01/2026 |
| `last_30_days` | Últimos 30 dias | 16/12/2025 a 15/01/2026 |
| `last_90_days` | Últimos 90 dias | 17/10/2025 a 15/01/2026 |
| `current_week` | Semana atual (seg-hoje) | 13/01 a 15/01/2026 |
| `current_month` | Mês atual | 01/01 a 15/01/2026 |
| `last_month` | Mês anterior completo | 01/12 a 31/12/2025 |

**Exemplo de Uso:**
```php
// URL: ?period=last_7_days
$periodo = resolvePeriod($_GET);

// Resultado:
[
    'inicio' => '2026-01-08',
    'fim' => '2026-01-15',
    'tipo' => 'last_7_days',
    'descricao' => 'Últimos 7 dias',
    'dias' => 8
]
```

---

### Modo 2: Período Customizado

Use `inicio` e `fim` no formato `dd/mm/yyyy`:

**Exemplo de Uso:**
```php
// URL: ?inicio=01/01/2026&fim=15/01/2026
$periodo = resolvePeriod($_GET);

// Resultado:
[
    'inicio' => '2026-01-01',
    'fim' => '2026-01-15',
    'tipo' => 'custom',
    'descricao' => '01/01/2026 a 15/01/2026',
    'dias' => 15
]
```

---

### Modo 3: Fallback (Default)

Se nenhum parâmetro for fornecido, usa **últimos 7 dias**:

**Exemplo de Uso:**
```php
// URL: (sem parâmetros)
$periodo = resolvePeriod($_GET);

// Resultado:
[
    'inicio' => '2026-01-08',
    'fim' => '2026-01-15',
    'tipo' => 'default_7_days',
    'descricao' => 'Últimos 7 dias (padrão)',
    'dias' => 8
]
```

---

## 📊 Exemplo Completo de Implementação

### KPI Antes (sem resolvePeriod):

```php
<?php
// Lógica duplicada em cada KPI
$dataInicio = $_GET['inicio'] ?? null;
$dataFim = $_GET['fim'] ?? null;

if (!$dataInicio || !$dataFim) {
    // Fallback manual
    $dataFim = date('Y-m-d');
    $dataInicio = date('Y-m-d', strtotime('-7 days'));
} else {
    // Conversão manual
    $dataInicio = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
    $dataFim = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));
}

$diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / 86400;
// ... resto do código
?>
```

**Problemas:**
- ❌ Código duplicado em cada KPI
- ❌ Sem suporte a períodos pré-definidos
- ❌ Conversão manual de datas
- ❌ Cálculo manual de dias
- ❌ Difícil de manter e estender

---

### KPI Depois (com resolvePeriod):

```php
<?php
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

try {
    // Uma única linha resolve tudo!
    $periodo = resolvePeriod($_GET);
    
    $dataInicio = $periodo['inicio'];      // '2026-01-08'
    $dataFim = $periodo['fim'];            // '2026-01-15'
    $tipoPeriodo = $periodo['tipo'];       // 'last_7_days'
    $descricao = $periodo['descricao'];    // 'Últimos 7 dias'
    $diasPeriodo = $periodo['dias'];       // 8
    
    // Uso direto nas queries
    $sql = "SELECT COUNT(*) FROM recebimentos 
            WHERE data_recebimento BETWEEN ? AND ?";
    
    // ... resto do código
    
} catch (Exception $e) {
    kpiError('meu-kpi', $e->getMessage(), 400);
}
?>
```

**Benefícios:**
- ✅ Código limpo e conciso
- ✅ Suporte a 8 períodos pré-definidos
- ✅ Conversão automática de datas
- ✅ Cálculo automático de dias
- ✅ Validação integrada
- ✅ Fácil de manter e estender

---

## 🌐 Exemplos de URLs

### 1. Período Pré-Definido - Hoje
```
/api/kpi-backlog.php?period=today
```

### 2. Período Pré-Definido - Últimos 7 Dias
```
/api/kpi-backlog.php?period=last_7_days
```

### 3. Período Pré-Definido - Mês Atual
```
/api/kpi-backlog.php?period=current_month
```

### 4. Período Customizado
```
/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026
```

### 5. Com Filtros Adicionais
```
/api/kpi-backlog.php?period=last_30_days&setor=Qualidade&operador=João
```

### 6. Sem Parâmetros (Default)
```
/api/kpi-backlog.php
```

---

## ✅ Validações Integradas

### 1. Período Inválido
```php
// URL: ?period=invalid_period
// Exception: "Período inválido: 'invalid_period'. Valores aceitos: ..."
```

### 2. Formato de Data Inválido
```php
// URL: ?inicio=01-01-2026&fim=15-01-2026
// Exception: "Formato de data inválido. Use dd/mm/yyyy ou utilize o parâmetro period"
```

### 3. Data Final Antes da Inicial
```php
// URL: ?inicio=15/01/2026&fim=01/01/2026
// Exception: "Data final deve ser posterior ou igual à data inicial"
```

---

## 🔄 Integração com KPI Existente

### Passo 1: Substituir Lógica Antiga

**Antes:**
```php
$dataInicio = $_GET['inicio'] ?? null;
$dataFim = $_GET['fim'] ?? null;

if (!$dataInicio || !$dataFim) {
    sendError('Parâmetros inicio e fim são obrigatórios', 400);
}

$dataInicioSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataInicio)));
$dataFimSQL = date('Y-m-d', strtotime(str_replace('/', '-', $dataFim)));
```

**Depois:**
```php
try {
    $periodo = resolvePeriod($_GET);
} catch (Exception $e) {
    kpiError('meu-kpi', $e->getMessage(), 400);
}

$dataInicio = $periodo['inicio'];
$dataFim = $periodo['fim'];
```

---

### Passo 2: Enriquecer Resposta

Adicione informações do período na resposta:

```php
$data = [
    'valor' => $valorKPI,
    // ... outros campos
    'periodo_analise' => [
        'tipo' => $periodo['tipo'],
        'descricao' => $periodo['descricao'],
        'dias' => $periodo['dias'],
        'inicio' => $periodo['inicio'],
        'fim' => $periodo['fim']
    ]
];
```

---

## 🎨 Resposta JSON Enriquecida

```json
{
  "status": "success",
  "kpi": "backlog-recebimento",
  "period": "2026-01-08 / 2026-01-15",
  "data": {
    "valor": 125,
    "periodo_analise": {
      "tipo": "last_7_days",
      "descricao": "Últimos 7 dias",
      "dias": 8,
      "inicio": "2026-01-08",
      "fim": "2026-01-15"
    }
  },
  "meta": {
    "generatedAt": "2026-01-15T12:30:45-03:00",
    "executionTimeMs": 78.92,
    "source": "vista-kpi"
  }
}
```

---

## 🚀 Vantagens

### 1. **Código Limpo**
- Elimina 15-20 linhas de lógica duplicada por KPI
- Código mais legível e manutenível

### 2. **Flexibilidade**
- Suporta múltiplos formatos de entrada
- Fácil adicionar novos períodos pré-definidos

### 3. **Consistência**
- Todos os KPIs usam a mesma lógica
- Normalização automática de datas

### 4. **User Experience**
- Frontend pode usar botões como "Hoje", "Últimos 7 dias"
- URLs mais amigáveis: `?period=today` vs `?inicio=15/01/2026&fim=15/01/2026`

### 5. **Extensível**
- Adicionar novos períodos é trivial
- Centralizado em um único lugar

---

## 📋 Checklist de Migração

Para migrar um KPI existente:

- [ ] Substituir lógica de parsing de datas por `resolvePeriod()`
- [ ] Atualizar tratamento de erro para `try/catch`
- [ ] Usar `$periodo['inicio']` e `$periodo['fim']`
- [ ] (Opcional) Adicionar `periodo_analise` na resposta
- [ ] Testar com múltiplos formatos de URL
- [ ] Atualizar documentação da API

---

## 🧪 Testes Sugeridos

### Teste 1: Período Pré-Definido
```bash
curl "http://api/kpi.php?period=last_7_days"
# Verificar: inicio e fim corretos
```

### Teste 2: Período Customizado
```bash
curl "http://api/kpi.php?inicio=01/01/2026&fim=15/01/2026"
# Verificar: conversão para Y-m-d
```

### Teste 3: Fallback
```bash
curl "http://api/kpi.php"
# Verificar: últimos 7 dias por padrão
```

### Teste 4: Validação de Erro
```bash
curl "http://api/kpi.php?period=invalid"
# Verificar: HTTP 400 com mensagem clara
```

---

## 🔮 Extensões Futuras

### 1. Adicionar Novo Período

```php
// Em resolvePeriod(), adicionar novo case:
case 'current_quarter':
    $dataInicio = (clone $hoje)->modify('first day of this quarter')->format('Y-m-d');
    $tipo = 'current_quarter';
    $descricao = 'Trimestre atual';
    break;
```

### 2. Períodos Relativos

```php
// Exemplo: últimos N dias dinâmico
case 'last_N_days':
    $n = (int)($params['n'] ?? 7);
    $dataInicio = (clone $hoje)->modify("-$n days")->format('Y-m-d');
    $tipo = "last_{$n}_days";
    $descricao = "Últimos $n dias";
    break;
```

### 3. Comparação de Períodos

```php
// Retornar também período de comparação
return [
    'atual' => [...],
    'anterior' => [
        'inicio' => ...,
        'fim' => ...
    ]
];
```

---

## ✅ Critérios de Aceite - TODOS ATENDIDOS

| Requisito | Status | Validação |
|-----------|--------|-----------|
| ✔️ Um único padrão de datas | ✅ | Função centralizada |
| ✔️ Sem lógica duplicada | ✅ | Reutilizável em todos os KPIs |
| ✔️ Fácil extensão | ✅ | Adicionar período = 1 case novo |
| ✔️ Aceita múltiplos formatos | ✅ | 3 modos de operação |
| ✔️ Datas normalizadas | ✅ | Sempre retorna Y-m-d |
| ✔️ Validação integrada | ✅ | Exceções claras |
| ✔️ Documentação completa | ✅ | Este documento |
| ✔️ Exemplo de uso real | ✅ | EXEMPLO_USO_RESOLVE_PERIOD.php |

---

## 📦 Arquivos Relacionados

| Arquivo | Descrição |
|---------|-----------|
| `BackEnd/endpoint-helpers.php` | Implementação da função |
| `DashBoard/backendDash/kpis/EXEMPLO_USO_RESOLVE_PERIOD.php` | Exemplo completo |
| `PADRONIZACAO_PERIODO_GLOBAL.md` | Esta documentação |

---

## 🎉 Conclusão

A função `resolvePeriod()` foi implementada com sucesso, fornecendo:

✅ **Padrão único** para todos os KPIs  
✅ **Código limpo** e sem duplicação  
✅ **Fácil extensão** para novos períodos  
✅ **8 períodos pré-definidos** prontos para uso  
✅ **Suporte a períodos customizados**  
✅ **Validação integrada** com mensagens claras  
✅ **Fallback inteligente** (últimos 7 dias)  

**Status:** 🟢 **PRONTO PARA USO EM PRODUÇÃO**

---

**Criado em:** 15/01/2026  
**Sistema:** VISTA - KPI 2.0  
**Módulo:** Helpers Globais
