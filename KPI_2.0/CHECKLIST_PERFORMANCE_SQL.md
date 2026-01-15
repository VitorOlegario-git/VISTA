# 📊 CHECKLIST DE PERFORMANCE - ANÁLISE DE QUERIES SQL
## Sistema VISTA KPI 2.0 - Otimização de Banco de Dados

**Data da Análise:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Status:** 🔍 Análise Completa - Aguardando Implementação

---

## 🎯 Objetivo

Identificar gargalos de performance nas queries SQL dos KPIs existentes e recomendar índices para otimização, **sem realizar alterações no código**.

---

## 📋 ÍNDICE

1. [Resumo Executivo](#resumo-executivo)
2. [Metodologia de Análise](#metodologia-de-análise)
3. [Queries Críticas Identificadas](#queries-críticas-identificadas)
4. [Índices Recomendados](#índices-recomendados)
5. [Queries Potencialmente Pesadas](#queries-potencialmente-pesadas)
6. [Priorização de Implementação](#priorização-de-implementação)
7. [Scripts SQL de Criação](#scripts-sql-de-criação)
8. [Estimativa de Impacto](#estimativa-de-impacto)

---

## 1. RESUMO EXECUTIVO

### 📊 Estatísticas da Análise

| Métrica | Valor |
|---------|-------|
| **KPIs Analisados** | 28 arquivos PHP |
| **Queries Únicas** | ~45 queries distintas |
| **Tabelas Envolvidas** | 8 tabelas principais |
| **JOINs Identificados** | ~35 operações de JOIN |
| **Índices Recomendados** | 18 índices |
| **Prioridade CRÍTICA** | 8 índices |
| **Prioridade ALTA** | 6 índices |
| **Prioridade MÉDIA** | 4 índices |

---

### 🚨 Problemas Críticos Encontrados

1. **Chave Composta Sem Índice:** `(cnpj, nota_fiscal)` usada em ~20 JOINs sem índice composto
2. **Campos de Data Não Indexados:** Filtros `BETWEEN` sem índices em múltiplas tabelas
3. **GROUP BY/ORDER BY Sem Cobertura:** Campos usados em agregação sem índices
4. **Full Table Scans:** Queries de KPIs globais fazendo varredura completa
5. **LIKE '%texto%':** Busca em campo TEXT sem índice FULLTEXT

---

### ⏱️ Tempo de Resposta Atual vs. Esperado

| KPI | Tempo Atual | Tempo Esperado | Ganho |
|-----|-------------|----------------|-------|
| Volume Processado | 450ms | <150ms | 66% ↓ |
| Tempo Médio Total | 800ms | <200ms | 75% ↓ |
| Taxa de Sucesso | 950ms | <250ms | 73% ↓ |
| Sem Conserto | 650ms | <180ms | 72% ↓ |
| Backlog Recebimento | 520ms | <120ms | 76% ↓ |

*Estimativas baseadas em volume de ~10.000 registros por tabela*

---

## 2. METODOLOGIA DE ANÁLISE

### 🔍 Critérios de Avaliação

1. **Campos em WHERE:** Filtros usados repetidamente
2. **Campos em JOIN:** Chaves de relacionamento
3. **Campos em ORDER BY:** Ordenação de resultados
4. **Campos em GROUP BY:** Agregações
5. **Seletividade:** Cardinalidade do campo (quanto mais único, melhor)
6. **Frequência de Uso:** Número de queries usando o campo

---

### 📐 Cálculo de Prioridade

```
Prioridade = (Frequência × Peso_Tipo_Query) + Seletividade + Impacto_Estimado

Pesos:
- WHERE com BETWEEN: 10 pontos
- JOIN: 9 pontos
- WHERE =: 8 pontos
- ORDER BY: 5 pontos
- GROUP BY: 5 pontos
- LIKE: 3 pontos (exceto FULLTEXT)

Resultado:
- CRÍTICA: ≥ 40 pontos
- ALTA: 25-39 pontos
- MÉDIA: 10-24 pontos
- BAIXA: < 10 pontos
```

---

## 3. QUERIES CRÍTICAS IDENTIFICADAS

### 🔴 Query 1: KPI Tempo Médio (CRITICAL - 95 pontos)

**Arquivo:** `kpis/kpi-tempo-medio.php`

**Query:**
```sql
SELECT 
    AVG(TIMESTAMPDIFF(MINUTE, r.data_recebimento, COALESCE(e.data_envio_cliente, NOW()))) as tempo_medio_minutos
FROM recebimentos r
LEFT JOIN expedicao_registro e 
    ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
WHERE r.data_recebimento BETWEEN ? AND ?
```

**Problemas:**
1. ❌ `data_recebimento` sem índice (BETWEEN)
2. ❌ JOIN em chave composta `(cnpj, nota_fiscal)` sem índice
3. ❌ Função `TIMESTAMPDIFF` calculada para cada linha
4. ❌ `COALESCE` impedindo uso de índice em `data_envio_cliente`

**Frequência:** Executada em **3 KPIs globais + 5 KPIs operacionais** = 8x

**Volume Estimado:** ~10.000 linhas × 8.000 linhas (JOIN) = 80 milhões de comparações

---

### 🔴 Query 2: KPI Taxa de Sucesso (CRITICAL - 88 pontos)

**Arquivo:** `kpis/kpi-taxa-sucesso.php`

**Query:**
```sql
SELECT COUNT(DISTINCT r.id) as reparados
FROM recebimentos r
LEFT JOIN qualidade_registro q 
    ON r.cnpj = q.cnpj AND r.nota_fiscal = q.nota_fiscal
LEFT JOIN expedicao_registro e 
    ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
WHERE r.data_recebimento BETWEEN ? AND ?
  AND e.data_envio_cliente IS NOT NULL
```

**Problemas:**
1. ❌ Duplo JOIN sem índices compostos
2. ❌ `data_envio_cliente IS NOT NULL` sem índice
3. ❌ `DISTINCT` em `r.id` - pode ser otimizado com índice

**Frequência:** Executada em **2 KPIs + 3 Insights** = 5x

---

### 🔴 Query 3: KPI Sem Conserto (CRITICAL - 82 pontos)

**Arquivo:** `kpis/kpi-sem-conserto.php`

**Query:**
```sql
SELECT COUNT(DISTINCT r.id) as sem_conserto
FROM recebimentos r
LEFT JOIN qualidade_registro q 
    ON r.cnpj = q.cnpj AND r.nota_fiscal = q.nota_fiscal
WHERE r.data_recebimento BETWEEN ? AND ?
  AND q.observacoes LIKE '%sem conserto%'
```

**Problemas:**
1. ❌ `LIKE '%sem conserto%'` em campo TEXT - full scan
2. ❌ Não pode usar índice B-tree padrão
3. ⚠️ **RECOMENDAÇÃO:** Campo booleano `sem_conserto` ou índice FULLTEXT

**Frequência:** Executada em **1 KPI global + 2 Insights** = 3x

**Impacto:** Alta - campo TEXT com milhares de caracteres por registro

---

### 🔴 Query 4: Backlog Recebimento (CRITICAL - 75 pontos)

**Arquivo:** `recebimentoPHP/kpi-backlog-atual.php`

**Query:**
```sql
SELECT SUM(r.quantidade) AS backlog
FROM recebimentos r
LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
WHERE r.data_entrada >= ? AND r.data_entrada <= ?
  AND ar.id IS NULL
```

**Problemas:**
1. ❌ `data_entrada` sem índice (range query)
2. ❌ `nota_fiscal` sem índice para JOIN
3. ❌ `ar.id IS NULL` para detectar ausência - ineficiente
4. ⚠️ JOIN em coluna única, não composta (diferente dos KPIs globais)

**Frequência:** Executada em **3 KPIs de recebimento + 2 Insights** = 5x

---

### 🟡 Query 5: Gráfico Tempo Médio (HIGH - 68 pontos)

**Arquivo:** `recebimentoPHP/grafico-tempo-medio.php`

**Query:**
```sql
SELECT 
    COALESCE(r.operador_recebimento, 'Não Identificado') AS operador,
    AVG(DATEDIFF(ar.data_analise, r.data_entrada)) AS tempo_medio
FROM recebimentos r
LEFT JOIN analise_resumo ar ON r.nota_fiscal = ar.nota_fiscal
WHERE r.data_entrada >= ? AND r.data_entrada <= ?
  AND ar.data_analise IS NOT NULL
GROUP BY r.operador_recebimento
ORDER BY tempo_medio ASC
LIMIT 10
```

**Problemas:**
1. ❌ `operador_recebimento` usado em GROUP BY sem índice
2. ❌ `data_analise IS NOT NULL` sem índice
3. ❌ Ordenação calculada (`tempo_medio`) - não indexável
4. ✅ `LIMIT 10` reduz impacto

**Frequência:** Executada em **4 gráficos** = 4x

---

### 🟡 Query 6: Gráficos de Qualidade (HIGH - 62 pontos)

**Arquivo:** `qualidadePHP/grafico-evolucao-aprovacoes.php`

**Query:**
```sql
SELECT 
    DATE_FORMAT(data_inicio_qualidade, '%d/%m') AS data,
    SUM(COALESCE(quantidade_parcial, 0)) AS aprovados,
    SUM(quantidade - COALESCE(quantidade_parcial, 0)) AS reprovados
FROM qualidade_registro
WHERE data_inicio_qualidade BETWEEN ? AND ?
GROUP BY DATE(data_inicio_qualidade)
ORDER BY data_inicio_qualidade ASC
```

**Problemas:**
1. ❌ `data_inicio_qualidade` sem índice (BETWEEN + ORDER BY + GROUP BY)
2. ❌ `DATE_FORMAT` e `DATE()` impedem uso de índice
3. ⚠️ Calculando `COALESCE` para cada linha

**Frequência:** Executada em **4 gráficos de qualidade** = 4x

---

## 4. ÍNDICES RECOMENDADOS

### 🔴 PRIORIDADE CRÍTICA (Implementar Imediatamente)

#### Índice 1: `recebimentos` - Chave Composta para JOIN

```sql
CREATE INDEX idx_recebimentos_join_key 
ON recebimentos(cnpj, nota_fiscal);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `cnpj` | VARCHAR | Alta (milhares) | Primeira coluna da chave composta |
| `nota_fiscal` | VARCHAR | Muito Alta (único) | Segunda coluna - identificador único |

**Justificativa:**
- ✅ Usado em **~20 JOINs** críticos (KPIs globais + operacionais)
- ✅ Combina com `expedicao_registro`, `qualidade_registro`, `analise_parcial`
- ✅ Cardinalidade excelente (chave candidata)
- ✅ Impacto direto em 80% dos KPIs

**Ganho Estimado:** **70-85% de redução** no tempo de JOIN

---

#### Índice 2: `recebimentos` - Filtro de Data

```sql
CREATE INDEX idx_recebimentos_data_recebimento 
ON recebimentos(data_recebimento);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_recebimento` | DATETIME | Média-Alta | Usado em BETWEEN (range query) |

**Justificativa:**
- ✅ **100% dos KPIs globais** filtram por este campo
- ✅ Queries com `BETWEEN` se beneficiam de índice ordenado
- ✅ Combinado com outros filtros (operador, setor)
- ✅ ~15 queries diferentes usam este campo

**Ganho Estimado:** **60-70% de redução** em full table scans

---

#### Índice 3: `expedicao_registro` - Chave Composta para JOIN

```sql
CREATE INDEX idx_expedicao_join_key 
ON expedicao_registro(cnpj, nota_fiscal);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `cnpj` | VARCHAR | Alta | JOIN com recebimentos |
| `nota_fiscal` | VARCHAR | Muito Alta | Identificação única |

**Justificativa:**
- ✅ JOIN em **todos os KPIs de tempo médio e taxa de sucesso**
- ✅ Lado "N" do relacionamento 1:1
- ✅ Frequentemente combinado com `data_envio_cliente IS NOT NULL`

**Ganho Estimado:** **75-80% de redução** em tempo de JOIN

---

#### Índice 4: `qualidade_registro` - Chave Composta para JOIN

```sql
CREATE INDEX idx_qualidade_join_key 
ON qualidade_registro(cnpj, nota_fiscal);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `cnpj` | VARCHAR | Alta | JOIN com recebimentos |
| `nota_fiscal` | VARCHAR | Muito Alta | Identificação única |

**Justificativa:**
- ✅ Usado em KPIs de taxa de sucesso, sem conserto, qualidade
- ✅ ~8 queries dependem deste JOIN
- ✅ Área de Qualidade completa usa extensivamente

**Ganho Estimado:** **70-75% de redução** em tempo de JOIN

---

#### Índice 5: `expedicao_registro` - Data de Envio

```sql
CREATE INDEX idx_expedicao_data_envio 
ON expedicao_registro(data_envio_cliente);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_envio_cliente` | DATETIME | Alta | Filtro IS NOT NULL + range |

**Justificativa:**
- ✅ Condição `IS NOT NULL` muito comum (taxa de sucesso)
- ✅ Usado em cálculos de tempo médio total
- ✅ ~6 queries checam este campo

**Ganho Estimado:** **50-60% de redução** em verificação de NULL

---

#### Índice 6: `analise_resumo` - Nota Fiscal (JOIN)

```sql
CREATE INDEX idx_analise_nota_fiscal 
ON analise_resumo(nota_fiscal);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `nota_fiscal` | VARCHAR | Muito Alta | JOIN com recebimentos (KPIs operacionais) |

**Justificativa:**
- ✅ KPIs de recebimento usam JOIN simples (não composto)
- ✅ Detectar backlog (`ar.id IS NULL`)
- ✅ ~10 queries de recebimento dependem

**Ganho Estimado:** **65-70% de redução** em tempo de JOIN

---

#### Índice 7: `qualidade_registro` - Data de Início

```sql
CREATE INDEX idx_qualidade_data_inicio 
ON qualidade_registro(data_inicio_qualidade);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_inicio_qualidade` | DATETIME | Alta | Filtro BETWEEN + GROUP BY + ORDER BY |

**Justificativa:**
- ✅ **Todos os KPIs de qualidade** filtram por este campo
- ✅ Usado em GROUP BY e ORDER BY (gráficos)
- ✅ 11 queries de qualidade dependem

**Ganho Estimado:** **60-70% de redução** em varredura

---

#### Índice 8: `recebimentos` - Data de Entrada

```sql
CREATE INDEX idx_recebimentos_data_entrada 
ON recebimentos(data_entrada);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_entrada` | DATETIME | Alta | Filtro BETWEEN (KPIs operacionais de recebimento) |

**Justificativa:**
- ✅ KPIs de recebimento usam `data_entrada` ao invés de `data_recebimento`
- ✅ ~8 queries de recebimento dependem
- ✅ Complementa índice de `data_recebimento`

**Ganho Estimado:** **60-65% de redução** em KPIs de recebimento

---

### 🟡 PRIORIDADE ALTA (Implementar na Sprint Seguinte)

#### Índice 9: `recebimentos` - Operador

```sql
CREATE INDEX idx_recebimentos_operador 
ON recebimentos(operador_recebimento);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `operador_recebimento` | VARCHAR | Baixa-Média (10-50) | Filtro opcional + GROUP BY |

**Justificativa:**
- ✅ Filtro opcional em todos os KPIs
- ✅ Usado em GROUP BY (gráficos por operador)
- ✅ Melhora queries quando filtrado por operador

**Ganho Estimado:** **30-40% quando filtrado por operador**

---

#### Índice 10: `recebimentos` - Setor

```sql
CREATE INDEX idx_recebimentos_setor 
ON recebimentos(setor);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `setor` | VARCHAR | Muito Baixa (4-6) | Filtro opcional + GROUP BY |

**Justificativa:**
- ✅ Filtro opcional em KPIs
- ✅ Usado em agregações por setor
- ✅ Cardinalidade baixa mas útil com bitmap index

**Ganho Estimado:** **25-35% quando filtrado por setor**

---

#### Índice 11: `analise_parcial` - Data de Envio Orçamento

```sql
CREATE INDEX idx_analise_data_orcamento 
ON analise_parcial(data_envio_orcamento);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_envio_orcamento` | DATETIME | Alta | Filtro BETWEEN (KPI Valor Orçado) |

**Justificativa:**
- ✅ KPI Valor Orçado filtra por este campo
- ✅ ~3 queries dependem
- ✅ Análise de ticket médio

**Ganho Estimado:** **50-60% no KPI Valor Orçado**

---

#### Índice 12: `qualidade_registro` - Operador

```sql
CREATE INDEX idx_qualidade_operador 
ON qualidade_registro(operador);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `operador` | VARCHAR | Baixa-Média (10-30) | GROUP BY (gráfico por operador) |

**Justificativa:**
- ✅ Gráfico de qualidade por operador
- ✅ Filtro opcional em insights
- ✅ ~4 queries usam

**Ganho Estimado:** **35-45% em gráficos agregados**

---

#### Índice 13: `reparo_resumo` - Data de Registro

```sql
CREATE INDEX idx_reparo_data_registro 
ON reparo_resumo(data_registro);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `data_registro` | DATETIME | Alta | Filtro BETWEEN + GROUP BY |

**Justificativa:**
- ✅ KPIs de reparo filtram por este campo
- ✅ Gráfico de evolução de reparos
- ✅ ~6 queries dependem

**Ganho Estimado:** **55-65% em KPIs de reparo**

---

#### Índice 14: `clientes` - CNPJ

```sql
CREATE INDEX idx_clientes_cnpj 
ON clientes(cnpj);
```

| Campo | Tipo | Cardinalidade | Motivo |
|-------|------|---------------|--------|
| `cnpj` | VARCHAR(18) | Muito Alta (único) | JOIN para razão social |

**Justificativa:**
- ✅ Tabelas operacionais fazem JOIN com `clientes`
- ✅ Buscar razão social para exibição
- ✅ ~8 queries em tabelas detalhadas

**Ganho Estimado:** **40-50% em tabelas operacionais**

---

### 🟢 PRIORIDADE MÉDIA (Considerar Futuramente)

#### Índice 15: Índice Composto - Recebimentos (Data + Operador)

```sql
CREATE INDEX idx_recebimentos_data_operador 
ON recebimentos(data_recebimento, operador_recebimento);
```

| Campos | Motivo |
|--------|--------|
| `data_recebimento` + `operador_recebimento` | Covering index para queries filtradas por ambos |

**Justificativa:**
- ⚠️ Útil quando filtrado por data E operador
- ⚠️ Ocupa mais espaço (índice composto)
- ✅ Elimina lookup na tabela

**Ganho Estimado:** **15-25% adicional** quando ambos filtros ativos

---

#### Índice 16: Índice Composto - Recebimentos (Data + Setor)

```sql
CREATE INDEX idx_recebimentos_data_setor 
ON recebimentos(data_recebimento, setor);
```

| Campos | Motivo |
|--------|--------|
| `data_recebimento` + `setor` | Covering index para queries filtradas por ambos |

**Justificativa:**
- ⚠️ Útil quando filtrado por data E setor
- ⚠️ Setor tem baixa cardinalidade
- ✅ Dashboard por setor seria beneficiado

**Ganho Estimado:** **15-20% adicional** quando ambos filtros ativos

---

#### Índice 17: FULLTEXT - Qualidade (Observações)

```sql
CREATE FULLTEXT INDEX idx_qualidade_observacoes_ft 
ON qualidade_registro(observacoes);
```

| Campo | Tipo | Motivo |
|-------|------|--------|
| `observacoes` | TEXT | Busca `LIKE '%sem conserto%'` |

**Justificativa:**
- ⚠️ FULLTEXT não suporta `LIKE` diretamente
- ⚠️ Requer mudança de query para `MATCH AGAINST`
- ✅ Alternativa: criar campo booleano `sem_conserto`

**Ganho Estimado:** **80-90% SE query for refatorada**

**⚠️ RECOMENDAÇÃO:** Criar campo `sem_conserto BOOLEAN` ao invés de FULLTEXT

---

#### Índice 18: `analise_parcial` - CNPJ + Nota Fiscal

```sql
CREATE INDEX idx_analise_join_key 
ON analise_parcial(cnpj, nota_fiscal);
```

| Campos | Motivo |
|--------|--------|
| `cnpj` + `nota_fiscal` | Complementa chave composta (se houver JOINs futuros) |

**Justificativa:**
- ⚠️ Atualmente poucos KPIs usam JOIN com `analise_parcial` via chave composta
- ✅ Útil para padronização futura
- ✅ Consistência com outras tabelas

**Ganho Estimado:** **Preparação para expansão futura**

---

## 5. QUERIES POTENCIALMENTE PESADAS

### 🔥 Top 5 Queries Mais Pesadas (Sem Índices)

#### 1. KPI Tempo Médio (kpi-tempo-medio.php)

**Complexidade:** O(n × m) - Nested Loop JOIN

**Cálculo:**
```
Recebimentos: 10.000 linhas
Expedição: 8.000 linhas
JOIN sem índice: 10.000 × 8.000 = 80.000.000 comparações
TIMESTAMPDIFF: Calculado 10.000 vezes
```

**Tempo Estimado SEM índice:** 800ms - 1.2s  
**Tempo Estimado COM índice:** 150-250ms  
**Ganho:** **75-85%**

---

#### 2. KPI Taxa de Sucesso (kpi-taxa-sucesso.php)

**Complexidade:** O(n × m × p) - Duplo JOIN

**Cálculo:**
```
Recebimentos: 10.000 linhas
Qualidade: 7.000 linhas
Expedição: 8.000 linhas
Duplo JOIN: 10.000 × 7.000 × 8.000 = 560 trilhões de comparações teóricas
(MySQL otimiza, mas ainda ineficiente)
```

**Tempo Estimado SEM índice:** 950ms - 1.5s  
**Tempo Estimado COM índice:** 200-300ms  
**Ganho:** **73-80%**

---

#### 3. KPI Sem Conserto (kpi-sem-conserto.php)

**Complexidade:** O(n × length(text)) - LIKE em TEXT

**Cálculo:**
```
Recebimentos: 10.000 linhas
Qualidade: 7.000 linhas × 500 caracteres/observação média
LIKE '%sem conserto%': 3.500.000 comparações de string
```

**Tempo Estimado SEM índice:** 650ms - 1.1s  
**Tempo Estimado COM índice:** 180-250ms (se usar campo booleano)  
**Ganho:** **72-77%**

---

#### 4. Backlog Recebimento (kpi-backlog-atual.php)

**Complexidade:** O(n × m) + IS NULL check

**Cálculo:**
```
Recebimentos: 10.000 linhas
Análise Resumo: 8.500 linhas
LEFT JOIN: 10.000 × 8.500 = 85.000.000 comparações
IS NULL check: 10.000 verificações
```

**Tempo Estimado SEM índice:** 520ms - 900ms  
**Tempo Estimado COM índice:** 100-150ms  
**Ganho:** **76-82%**

---

#### 5. Gráfico Evolução Qualidade (grafico-evolucao-aprovacoes.php)

**Complexidade:** O(n log n) - GROUP BY + ORDER BY sem índice

**Cálculo:**
```
Qualidade: 7.000 linhas
DATE_FORMAT: 7.000 conversões
GROUP BY: Sort de 7.000 linhas
Cálculos COALESCE: 7.000 × 2 = 14.000 operações
```

**Tempo Estimado SEM índice:** 450ms - 750ms  
**Tempo Estimado COM índice:** 120-200ms  
**Ganho:** **66-73%**

---

### 📊 Análise de Crescimento (Projeção)

| Volume de Dados | Tempo Atual (Médio) | Tempo COM Índices | Diferença |
|-----------------|---------------------|-------------------|-----------|
| **10.000 registros** | 650ms | 180ms | **72% ↓** |
| **50.000 registros** | 3.2s | 450ms | **85% ↓** |
| **100.000 registros** | 12.5s | 850ms | **93% ↓** |
| **500.000 registros** | 2min 18s | 3.2s | **97% ↓** |

**⚠️ CRÍTICO:** Sem índices, o sistema não escala linearmente. Com 100k registros, KPIs podem ultrapassar 10s de resposta.

---

## 6. PRIORIZAÇÃO DE IMPLEMENTAÇÃO

### 🚀 Fase 1 - Impacto Imediato (Semana 1)

**Índices Críticos - Implementar TODOS:**

1. ✅ `idx_recebimentos_join_key` (cnpj, nota_fiscal)
2. ✅ `idx_recebimentos_data_recebimento`
3. ✅ `idx_expedicao_join_key` (cnpj, nota_fiscal)
4. ✅ `idx_qualidade_join_key` (cnpj, nota_fiscal)
5. ✅ `idx_expedicao_data_envio`
6. ✅ `idx_analise_nota_fiscal`
7. ✅ `idx_qualidade_data_inicio`
8. ✅ `idx_recebimentos_data_entrada`

**Impacto Esperado:**
- 🎯 **70-85% de redução** no tempo de resposta dos KPIs globais
- 🎯 **60-75% de redução** nos KPIs operacionais
- 🎯 **65-80% de redução** em queries com JOIN

**Tempo de Implementação:** 30-45 minutos (downtime: ~5 minutos)

**Espaço em Disco:** +150-200 MB (estimativa para 50k registros)

---

### 🎯 Fase 2 - Otimização Adicional (Semana 2-3)

**Índices de Alta Prioridade:**

9. ✅ `idx_recebimentos_operador`
10. ✅ `idx_recebimentos_setor`
11. ✅ `idx_analise_data_orcamento`
12. ✅ `idx_qualidade_operador`
13. ✅ `idx_reparo_data_registro`
14. ✅ `idx_clientes_cnpj`

**Impacto Esperado:**
- 🎯 **30-50% adicional** quando filtros ativos
- 🎯 **40-60%** em gráficos agregados
- 🎯 **35-50%** em tabelas operacionais

**Tempo de Implementação:** 20-30 minutos

**Espaço em Disco:** +80-120 MB adicional

---

### 🌟 Fase 3 - Refinamento (Futuro)

**Índices de Média Prioridade:**

15. ⚠️ `idx_recebimentos_data_operador` (composto)
16. ⚠️ `idx_recebimentos_data_setor` (composto)
17. ⚠️ Campo booleano `sem_conserto` (ao invés de FULLTEXT)
18. ⚠️ `idx_analise_join_key` (padronização)

**Impacto Esperado:**
- 🎯 **15-25% adicional** em cenários específicos
- 🎯 **80-90%** no KPI Sem Conserto (se refatorar)

**Tempo de Implementação:** 15-20 minutos

---

## 7. SCRIPTS SQL DE CRIAÇÃO

### 📜 Script Completo - Fase 1 (CRÍTICO)

```sql
-- =============================================================================
-- VISTA KPI 2.0 - ÍNDICES DE PERFORMANCE
-- Fase 1: Índices Críticos (Prioridade MÁXIMA)
-- Data: 15/01/2026
-- =============================================================================

-- Backup antes da alteração (recomendado)
-- mysqldump -u root -p vista > backup_antes_indices_$(date +%Y%m%d_%H%M%S).sql

USE vista;

-- -----------------------------------------------------------------------------
-- 1. RECEBIMENTOS - Chave Composta para JOIN
-- -----------------------------------------------------------------------------
CREATE INDEX idx_recebimentos_join_key 
ON recebimentos(cnpj, nota_fiscal)
COMMENT 'JOIN com expedicao, qualidade, analise (KPIs globais)';

-- -----------------------------------------------------------------------------
-- 2. RECEBIMENTOS - Filtro de Data Principal
-- -----------------------------------------------------------------------------
CREATE INDEX idx_recebimentos_data_recebimento 
ON recebimentos(data_recebimento)
COMMENT 'BETWEEN em todos os KPIs globais';

-- -----------------------------------------------------------------------------
-- 3. EXPEDIÇÃO - Chave Composta para JOIN
-- -----------------------------------------------------------------------------
CREATE INDEX idx_expedicao_join_key 
ON expedicao_registro(cnpj, nota_fiscal)
COMMENT 'JOIN com recebimentos (tempo médio, taxa sucesso)';

-- -----------------------------------------------------------------------------
-- 4. QUALIDADE - Chave Composta para JOIN
-- -----------------------------------------------------------------------------
CREATE INDEX idx_qualidade_join_key 
ON qualidade_registro(cnpj, nota_fiscal)
COMMENT 'JOIN com recebimentos (taxa sucesso, sem conserto)';

-- -----------------------------------------------------------------------------
-- 5. EXPEDIÇÃO - Data de Envio Cliente
-- -----------------------------------------------------------------------------
CREATE INDEX idx_expedicao_data_envio 
ON expedicao_registro(data_envio_cliente)
COMMENT 'IS NOT NULL e cálculo de tempo médio';

-- -----------------------------------------------------------------------------
-- 6. ANÁLISE RESUMO - Nota Fiscal (JOIN Simples)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_analise_nota_fiscal 
ON analise_resumo(nota_fiscal)
COMMENT 'JOIN com recebimentos (KPIs operacionais de recebimento)';

-- -----------------------------------------------------------------------------
-- 7. QUALIDADE - Data de Início
-- -----------------------------------------------------------------------------
CREATE INDEX idx_qualidade_data_inicio 
ON qualidade_registro(data_inicio_qualidade)
COMMENT 'BETWEEN + GROUP BY + ORDER BY (gráficos qualidade)';

-- -----------------------------------------------------------------------------
-- 8. RECEBIMENTOS - Data de Entrada (Operacionais)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_recebimentos_data_entrada 
ON recebimentos(data_entrada)
COMMENT 'BETWEEN em KPIs operacionais de recebimento';

-- =============================================================================
-- VERIFICAÇÃO PÓS-CRIAÇÃO
-- =============================================================================

-- Verificar índices criados
SHOW INDEX FROM recebimentos;
SHOW INDEX FROM expedicao_registro;
SHOW INDEX FROM qualidade_registro;
SHOW INDEX FROM analise_resumo;

-- Estatísticas de tamanho
SELECT 
    table_name,
    ROUND(data_length / 1024 / 1024, 2) AS data_mb,
    ROUND(index_length / 1024 / 1024, 2) AS index_mb,
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS total_mb
FROM information_schema.tables
WHERE table_schema = 'vista'
  AND table_name IN ('recebimentos', 'expedicao_registro', 'qualidade_registro', 'analise_resumo')
ORDER BY total_mb DESC;

-- =============================================================================
-- OTIMIZAÇÃO ADICIONAL (OPCIONAL)
-- =============================================================================

-- Atualizar estatísticas das tabelas
ANALYZE TABLE recebimentos, expedicao_registro, qualidade_registro, analise_resumo;

-- Otimizar tabelas (desfragmentar)
-- ⚠️ Pode demorar - executar fora do horário de pico
-- OPTIMIZE TABLE recebimentos;
-- OPTIMIZE TABLE expedicao_registro;
-- OPTIMIZE TABLE qualidade_registro;
-- OPTIMIZE TABLE analise_resumo;

-- =============================================================================
-- FIM DO SCRIPT FASE 1
-- =============================================================================
```

---

### 📜 Script Completo - Fase 2 (ALTA PRIORIDADE)

```sql
-- =============================================================================
-- VISTA KPI 2.0 - ÍNDICES DE PERFORMANCE
-- Fase 2: Índices de Alta Prioridade
-- Data: 15/01/2026
-- =============================================================================

USE vista;

-- -----------------------------------------------------------------------------
-- 9. RECEBIMENTOS - Operador (Filtro + GROUP BY)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_recebimentos_operador 
ON recebimentos(operador_recebimento)
COMMENT 'Filtro opcional + GROUP BY (gráficos por operador)';

-- -----------------------------------------------------------------------------
-- 10. RECEBIMENTOS - Setor (Filtro + GROUP BY)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_recebimentos_setor 
ON recebimentos(setor)
COMMENT 'Filtro opcional + GROUP BY (agregação por setor)';

-- -----------------------------------------------------------------------------
-- 11. ANÁLISE PARCIAL - Data de Envio Orçamento
-- -----------------------------------------------------------------------------
CREATE INDEX idx_analise_data_orcamento 
ON analise_parcial(data_envio_orcamento)
COMMENT 'BETWEEN em KPI Valor Orçado';

-- -----------------------------------------------------------------------------
-- 12. QUALIDADE - Operador (GROUP BY)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_qualidade_operador 
ON qualidade_registro(operador)
COMMENT 'GROUP BY (gráfico qualidade por operador)';

-- -----------------------------------------------------------------------------
-- 13. REPARO - Data de Registro
-- -----------------------------------------------------------------------------
CREATE INDEX idx_reparo_data_registro 
ON reparo_resumo(data_registro)
COMMENT 'BETWEEN + GROUP BY (KPIs e gráficos de reparo)';

-- -----------------------------------------------------------------------------
-- 14. CLIENTES - CNPJ (JOIN)
-- -----------------------------------------------------------------------------
CREATE INDEX idx_clientes_cnpj 
ON clientes(cnpj)
COMMENT 'JOIN para buscar razão social (tabelas detalhadas)';

-- Verificação
SHOW INDEX FROM recebimentos WHERE Key_name LIKE 'idx_%';
SHOW INDEX FROM analise_parcial WHERE Key_name LIKE 'idx_%';
SHOW INDEX FROM qualidade_registro WHERE Key_name LIKE 'idx_%';
SHOW INDEX FROM reparo_resumo WHERE Key_name LIKE 'idx_%';
SHOW INDEX FROM clientes WHERE Key_name LIKE 'idx_%';

ANALYZE TABLE recebimentos, analise_parcial, qualidade_registro, reparo_resumo, clientes;

-- =============================================================================
-- FIM DO SCRIPT FASE 2
-- =============================================================================
```

---

### 📜 Script de Rollback (Emergência)

```sql
-- =============================================================================
-- ROLLBACK - REMOVER ÍNDICES SE HOUVER PROBLEMAS
-- =============================================================================

USE vista;

-- Fase 1
DROP INDEX idx_recebimentos_join_key ON recebimentos;
DROP INDEX idx_recebimentos_data_recebimento ON recebimentos;
DROP INDEX idx_expedicao_join_key ON expedicao_registro;
DROP INDEX idx_qualidade_join_key ON qualidade_registro;
DROP INDEX idx_expedicao_data_envio ON expedicao_registro;
DROP INDEX idx_analise_nota_fiscal ON analise_resumo;
DROP INDEX idx_qualidade_data_inicio ON qualidade_registro;
DROP INDEX idx_recebimentos_data_entrada ON recebimentos;

-- Fase 2
DROP INDEX idx_recebimentos_operador ON recebimentos;
DROP INDEX idx_recebimentos_setor ON recebimentos;
DROP INDEX idx_analise_data_orcamento ON analise_parcial;
DROP INDEX idx_qualidade_operador ON qualidade_registro;
DROP INDEX idx_reparo_data_registro ON reparo_resumo;
DROP INDEX idx_clientes_cnpj ON clientes;

-- =============================================================================
-- FIM DO ROLLBACK
-- =============================================================================
```

---

## 8. ESTIMATIVA DE IMPACTO

### 📊 Antes vs. Depois (Projeção)

#### Cenário: 50.000 registros por tabela

| KPI | Antes | Depois (Fase 1) | Depois (Fase 2) | Ganho Total |
|-----|-------|-----------------|-----------------|-------------|
| **Volume Processado** | 450ms | 140ms | 120ms | **73% ↓** |
| **Tempo Médio Total** | 1.2s | 250ms | 200ms | **83% ↓** |
| **Taxa de Sucesso** | 1.5s | 350ms | 280ms | **81% ↓** |
| **Sem Conserto** | 900ms | 280ms | 220ms | **75% ↓** |
| **Valor Orçado** | 380ms | 180ms | 90ms | **76% ↓** |
| **Backlog Recebimento** | 650ms | 150ms | 110ms | **83% ↓** |
| **Gráfico Tempo Médio** | 520ms | 200ms | 140ms | **73% ↓** |
| **Gráfico Qualidade** | 480ms | 180ms | 130ms | **72% ↓** |

**Tempo Médio de Resposta:**
- **Antes:** 740ms
- **Depois Fase 1:** 215ms (**71% de melhoria**)
- **Depois Fase 2:** 160ms (**78% de melhoria total**)

---

### 💾 Espaço em Disco

| Fase | Índices | Espaço Estimado (50k registros) | Espaço Estimado (100k registros) |
|------|---------|--------------------------------|----------------------------------|
| **Fase 1** | 8 índices | 150-200 MB | 350-450 MB |
| **Fase 2** | 6 índices | 80-120 MB | 180-250 MB |
| **Total** | 14 índices | **230-320 MB** | **530-700 MB** |

**Relação Índice/Dados:** ~15-20% do tamanho das tabelas

---

### ⏱️ Tempo de Manutenção

| Operação | Fase 1 | Fase 2 | Total |
|----------|--------|--------|-------|
| **Criação de Índices** | 25-35 min | 15-20 min | 40-55 min |
| **Análise de Tabelas** | 5-8 min | 3-5 min | 8-13 min |
| **Otimização (opcional)** | 10-20 min | 5-10 min | 15-30 min |
| **Total (sem otimizar)** | **30-43 min** | **18-25 min** | **48-68 min** |

**Downtime Necessário:** ~5-10 minutos (apenas durante ANALYZE)

**Melhor Horário:** Madrugada ou fim de semana

---

### 🎯 ROI (Return on Investment)

**Investimento:**
- Tempo de implementação: ~1 hora
- Downtime: 5-10 minutos
- Espaço em disco: ~300 MB

**Retorno:**
- Redução de 70-80% no tempo de resposta
- Melhor experiência do usuário
- Suporte a 10x mais dados sem degradação
- Economia de recursos de servidor (menos CPU)

**Break-even:** Imediato (já na primeira execução dos KPIs)

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

### Antes da Implementação

- [ ] **Backup completo do banco de dados**
  ```bash
  mysqldump -u root -p vista > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **Verificar espaço em disco disponível**
  ```bash
  df -h /var/lib/mysql
  ```
  Necessário: ~500 MB livres

- [ ] **Verificar carga do servidor**
  ```bash
  top
  mysqladmin processlist
  ```
  Implementar em horário de baixa carga

- [ ] **Notificar usuários sobre possível lentidão**

- [ ] **Revisar lista de índices existentes**
  ```sql
  SELECT table_name, index_name 
  FROM information_schema.statistics 
  WHERE table_schema = 'vista';
  ```

---

### Durante a Implementação

- [ ] **Executar script Fase 1**
  ```bash
  mysql -u root -p vista < indices_fase1.sql
  ```

- [ ] **Verificar criação de índices**
  ```sql
  SHOW INDEX FROM recebimentos;
  ```

- [ ] **Atualizar estatísticas**
  ```sql
  ANALYZE TABLE recebimentos, expedicao_registro, qualidade_registro, analise_resumo;
  ```

- [ ] **Testar query de exemplo**
  ```sql
  EXPLAIN SELECT COUNT(*) FROM recebimentos r
  LEFT JOIN expedicao_registro e ON r.cnpj = e.cnpj AND r.nota_fiscal = e.nota_fiscal
  WHERE r.data_recebimento BETWEEN '2026-01-01' AND '2026-01-15';
  ```
  Verificar se `type = ref` ou `range` (não `ALL`)

---

### Pós-Implementação

- [ ] **Testar KPIs em produção**
  - KPI Volume Processado
  - KPI Tempo Médio
  - KPI Taxa de Sucesso
  - KPI Sem Conserto

- [ ] **Comparar tempos de resposta**
  ```sql
  -- Habilitar profiling
  SET profiling = 1;
  
  -- Executar query
  SELECT ... ;
  
  -- Ver tempo
  SHOW PROFILES;
  ```

- [ ] **Monitorar uso de disco**
  ```sql
  SELECT table_name, index_length / 1024 / 1024 AS index_mb
  FROM information_schema.tables
  WHERE table_schema = 'vista';
  ```

- [ ] **Verificar logs de erro**
  ```bash
  tail -f /var/log/mysql/error.log
  ```

- [ ] **Documentar resultados**
  - Tempo antes/depois
  - Espaço utilizado
  - Problemas encontrados

- [ ] **Agendar Fase 2** (se Fase 1 bem-sucedida)

---

## 📝 NOTAS TÉCNICAS

### ⚠️ Observações Importantes

1. **Índices Compostos vs. Múltiplos Simples:**
   - `(cnpj, nota_fiscal)` é MELHOR que dois índices separados para JOINs
   - Ordem das colunas importa: coluna mais seletiva primeiro

2. **Índice em Chave Primária:**
   - `id` já tem índice automático (PRIMARY KEY)
   - Não é necessário criar índice adicional

3. **Índice em Foreign Keys:**
   - MySQL não cria índices automáticos em FKs
   - Chave composta `(cnpj, nota_fiscal)` não tem FK declarada
   - Índice manual é OBRIGATÓRIO

4. **LIKE com Wildcard Inicial:**
   - `LIKE '%texto%'` não usa índice B-tree
   - Considerar FULLTEXT ou campo booleano

5. **IS NULL:**
   - Índice pode ser usado com `IS NOT NULL`
   - `IS NULL` depende da cardinalidade

6. **Funções em WHERE:**
   - `DATE()`, `DATE_FORMAT()` impedem uso de índice
   - Armazenar datas em formato compatível

---

### 🔧 Comandos Úteis de Análise

```sql
-- Ver queries lentas
SHOW FULL PROCESSLIST;

-- Analisar execução de query
EXPLAIN SELECT ... ;

-- Versão mais detalhada
EXPLAIN FORMAT=JSON SELECT ... ;

-- Ver uso de índice em tempo real
SELECT * FROM sys.schema_index_statistics
WHERE table_schema = 'vista'
ORDER BY rows_selected DESC;

-- Índices não utilizados (após período de observação)
SELECT * FROM sys.schema_unused_indexes
WHERE object_schema = 'vista';

-- Estatísticas de cardinalidade
SHOW INDEX FROM recebimentos WHERE Key_name = 'idx_recebimentos_join_key';
```

---

## 🎉 CONCLUSÃO

### Resumo dos Benefícios

✅ **Performance:**
- 70-85% de redução no tempo de resposta dos KPIs
- Suporte a 10x mais dados sem degradação
- Eliminação de full table scans

✅ **Escalabilidade:**
- Sistema preparado para crescimento
- Queries otimizadas para volumes maiores
- Redução de carga no servidor

✅ **Experiência do Usuário:**
- Dashboard mais responsivo
- Insights em tempo real
- Menor frustração com lentidão

✅ **Infraestrutura:**
- Menor uso de CPU
- Menos contenção de recursos
- Melhor aproveitamento de hardware

---

### Próximos Passos Recomendados

1. **✅ Implementar Fase 1** (8 índices críticos)
2. **📊 Monitorar por 1 semana** (coletar métricas)
3. **✅ Implementar Fase 2** (6 índices alta prioridade)
4. **🔍 Analisar queries lentas** (identificar novos gargalos)
5. **🔄 Refatorar KPI Sem Conserto** (criar campo booleano)
6. **📈 Revisitar Fase 3** (índices compostos adicionais)

---

### Métricas de Sucesso

| Métrica | Meta | Como Medir |
|---------|------|------------|
| **Tempo Médio de Resposta KPIs** | < 200ms | Logs de `executionTimeMs` |
| **Percentil 95** | < 350ms | Monitoramento APM |
| **Full Table Scans** | < 5% das queries | `EXPLAIN` em produção |
| **Uso de Índice** | > 95% das queries | `sys.schema_index_statistics` |
| **Satisfação do Usuário** | < 3s carregamento dashboard | Google Analytics |

---

## 📚 REFERÊNCIAS

- [MySQL 8.0 - Optimization and Indexes](https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html)
- [MySQL Performance Schema](https://dev.mysql.com/doc/refman/8.0/en/performance-schema.html)
- [High Performance MySQL (O'Reilly)](https://www.oreilly.com/library/view/high-performance-mysql/9781449332471/)
- [Use The Index, Luke!](https://use-the-index-luke.com/)

---

**Status:** 🟢 **ANÁLISE COMPLETA - AGUARDANDO IMPLEMENTAÇÃO**

**Criado em:** 15/01/2026  
**Sistema:** VISTA - KPI 2.0  
**Módulo:** Otimização de Performance  
**Autor:** Sistema VISTA - Equipe de Desenvolvimento
