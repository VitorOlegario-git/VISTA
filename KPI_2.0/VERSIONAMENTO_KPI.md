# 🔖 SISTEMA DE VERSIONAMENTO DE KPIs

**Data de Implementação:** 15 de Janeiro de 2026  
**Sistema:** VISTA - KPI 2.0  
**Versão:** 1.0  
**Módulo:** Versionamento e Rastreabilidade

---

## 📑 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Função getKpiMetadata()](#função-getkpimetadata)
3. [Integração com kpiResponse()](#integração-com-kpiresponse)
4. [Versionamento Semântico](#versionamento-semântico)
5. [Implementação Prática](#implementação-prática)
6. [Exemplo de Resposta JSON](#exemplo-de-resposta-json)
7. [Migração de KPIs Existentes](#migração-de-kpis-existentes)
8. [Boas Práticas](#boas-práticas)

---

## 1. VISÃO GERAL

### 1.1 Objetivo

O sistema de versionamento foi criado para fornecer **rastreabilidade completa** dos KPIs, permitindo:

- **Auditoria:** Identificação da versão em uso
- **Responsabilidade:** Definição clara de ownership
- **Manutenção:** Rastreamento de atualizações
- **Documentação:** Histórico de mudanças automatizado

### 1.2 Campos do Versionamento

Cada KPI expõe **3 campos obrigatórios** no bloco `meta` da resposta JSON:

| Campo | Tipo | Descrição | Exemplo |
|-------|------|-----------|---------|
| `kpi_version` | `string` | Versão semântica do KPI | `"3.0.0"` |
| `kpi_owner` | `string` | Responsável pelo KPI | `"Equipe Backend VISTA"` |
| `last_updated` | `string` | Data última atualização (Y-m-d) | `"2026-01-15"` |

### 1.3 Benefícios

✅ **Centralizado:** Uma função reutilizável (`getKpiMetadata()`)  
✅ **Automático:** Integração transparente com `kpiResponse()`  
✅ **Consistente:** Formato padronizado em todos os KPIs  
✅ **Rastreável:** Histórico de versões visível no JSON  
✅ **Manutenível:** Fácil atualização futura

---

## 2. FUNÇÃO getKpiMetadata()

### 2.1 Localização

**Arquivo:** `BackEnd/endpoint-helpers.php` (linha ~201)

### 2.2 Assinatura

```php
function getKpiMetadata(
    string $kpiName,              // Nome técnico do KPI
    string $version = '1.0.0',    // Versão semântica
    string $owner = 'Equipe VISTA', // Responsável
    ?string $lastUpdated = null   // Data última atualização (Y-m-d)
): array
```

### 2.3 Parâmetros Detalhados

#### `$kpiName` (obrigatório)
- **Tipo:** `string`
- **Descrição:** Nome técnico do KPI (geralmente o nome do arquivo sem `.php`)
- **Exemplo:** `'kpi-backlog-atual'`, `'kpi-tempo-medio'`
- **Uso:** Identificação única do KPI no sistema

#### `$version` (opcional, default: `'1.0.0'`)
- **Tipo:** `string`
- **Descrição:** Versão semântica seguindo padrão MAJOR.MINOR.PATCH
- **Exemplo:** `'3.0.0'`, `'2.1.5'`, `'1.0.0'`
- **Uso:** Rastreamento de mudanças no KPI

#### `$owner` (opcional, default: `'Equipe VISTA'`)
- **Tipo:** `string`
- **Descrição:** Responsável técnico pelo KPI
- **Exemplo:** `'Equipe Backend VISTA'`, `'João Silva'`, `'Time de Analytics'`
- **Uso:** Identificação de ownership para manutenção

#### `$lastUpdated` (opcional, default: `null`)
- **Tipo:** `string|null`
- **Descrição:** Data da última atualização no formato `Y-m-d`
- **Exemplo:** `'2026-01-15'`, `'2026-12-31'`
- **Comportamento:** 
  - Se `null`, tenta buscar data de modificação do arquivo via `filemtime()`
  - Se arquivo não encontrado, usa data atual (`date('Y-m-d')`)

### 2.4 Retorno

```php
[
    'kpi_version' => '3.0.0',
    'kpi_owner' => 'Equipe Backend VISTA',
    'last_updated' => '2026-01-15'
]
```

### 2.5 Lógica Interna

#### 🔍 Detecção Automática de `last_updated`

Se o parâmetro `$lastUpdated` não for fornecido, a função tenta localizar o arquivo do KPI automaticamente em 5 diretórios padrão:

```php
$possiblePaths = [
    __DIR__ . '/../DashBoard/backendDash/kpis/' . $kpiName . '.php',
    __DIR__ . '/../DashBoard/backendDash/recebimentoPHP/' . $kpiName . '.php',
    __DIR__ . '/../DashBoard/backendDash/analisePHP/' . $kpiName . '.php',
    __DIR__ . '/../DashBoard/backendDash/reparoPHP/' . $kpiName . '.php',
    __DIR__ . '/../DashBoard/backendDash/qualidadePHP/' . $kpiName . '.php',
];
```

- **Se encontrado:** Usa `filemtime($path)` (data de modificação do arquivo)
- **Se não encontrado:** Usa data atual como fallback

**Vantagem:** Atualização automática da data quando o arquivo é modificado.

---

## 3. INTEGRAÇÃO COM kpiResponse()

### 3.1 Modificação na Função

A função `kpiResponse()` foi atualizada para aceitar um **6º parâmetro opcional**:

```php
function kpiResponse(
    string $kpi,
    string $period,
    array $data,
    float $executionTimeMs,
    int $httpCode = 200,
    ?array $metadata = null  // ✅ NOVO PARÂMETRO
): void
```

### 3.2 Lógica de Merge

Se `$metadata` for fornecido, seus campos são mesclados ao bloco `meta`:

```php
// Meta base (sempre presente)
$meta = [
    'generatedAt' => date('c'),
    'executionTimeMs' => round($executionTimeMs, 2),
    'source' => 'vista-kpi'
];

// ✅ ADICIONAR METADADOS DE VERSIONAMENTO (se fornecidos)
if ($metadata !== null) {
    $meta = array_merge($meta, $metadata);
}
```

**Resultado:** Bloco `meta` expandido com 3 novos campos.

### 3.3 Retrocompatibilidade

A mudança é **100% retrocompatível**:

- KPIs **sem** versionamento continuam funcionando (6º parâmetro é opcional)
- Bloco `meta` mantém campos originais (`generatedAt`, `executionTimeMs`, `source`)
- Novos campos (`kpi_version`, `kpi_owner`, `last_updated`) são adicionados apenas se `$metadata` for passado

---

## 4. VERSIONAMENTO SEMÂNTICO

### 4.1 Formato: MAJOR.MINOR.PATCH

Seguimos o padrão **Semantic Versioning 2.0.0** (https://semver.org/):

```
MAJOR.MINOR.PATCH
  │     │     │
  │     │     └─ Correções de bugs (backward-compatible)
  │     └─────── Novas funcionalidades (backward-compatible)
  └───────────── Mudanças incompatíveis (breaking changes)
```

### 4.2 Quando Incrementar Cada Parte

#### 🔴 MAJOR (Quebra de compatibilidade)

Incrementar quando houver **mudanças incompatíveis** que quebram integrações existentes:

**Exemplos:**
- Remover campo da resposta JSON (`data.valor` → removido)
- Mudar tipo de dado (`valor` de `string` → `int`)
- Renomear campo (`referencia` → `baseline`)
- Alterar formato de data (`dd/mm/yyyy` → `yyyy-mm-dd`)
- Mudar lógica de cálculo que altera significativamente os valores

**Versão:**
- `2.5.3` → `3.0.0` (MAJOR incrementado, MINOR e PATCH resetam para 0)

---

#### 🟡 MINOR (Nova funcionalidade)

Incrementar quando adicionar **novas funcionalidades** mantendo compatibilidade:

**Exemplos:**
- Adicionar novo campo opcional no JSON (`data.detalhes`)
- Adicionar novo parâmetro opcional na query string (`?setor=X`)
- Melhorar performance sem alterar resposta
- Adicionar filtro adicional (mantendo comportamento padrão)
- Adicionar validação extra (não bloqueia casos válidos)

**Versão:**
- `2.5.3` → `2.6.0` (MINOR incrementado, PATCH reseta para 0)

---

#### 🟢 PATCH (Correção de bugs)

Incrementar para **correções de bugs** sem adicionar funcionalidades:

**Exemplos:**
- Corrigir cálculo incorreto
- Corrigir tratamento de NULL
- Corrigir query SQL que retornava dados errados
- Corrigir validação de data
- Corrigir timezone

**Versão:**
- `2.5.3` → `2.5.4` (apenas PATCH incrementado)

---

### 4.3 Exemplos Práticos

#### Exemplo 1: Correção de bug no cálculo de variação

**Antes (v2.1.0):**
```php
$variacao = (($atual - $anterior) / $anterior) * 100; // Bug: divisão por zero
```

**Depois (v2.1.1):**
```php
$variacao = $anterior > 0 ? (($atual - $anterior) / $anterior) * 100 : 0; // Corrigido
```

**Versão:** `2.1.0` → `2.1.1` (PATCH - correção de bug)

---

#### Exemplo 2: Adicionar campo `media_diaria`

**Antes (v2.1.1):**
```json
{
  "data": {
    "valor": 1250,
    "unidade": "equipamentos"
  }
}
```

**Depois (v2.2.0):**
```json
{
  "data": {
    "valor": 1250,
    "unidade": "equipamentos",
    "media_diaria": 178  // ✅ NOVO CAMPO
  }
}
```

**Versão:** `2.1.1` → `2.2.0` (MINOR - nova funcionalidade)

---

#### Exemplo 3: Mudar `valor` de string para int

**Antes (v2.2.0):**
```json
{
  "data": {
    "valor": "1250"  // ❌ String
  }
}
```

**Depois (v3.0.0):**
```json
{
  "data": {
    "valor": 1250  // ✅ Integer (BREAKING CHANGE)
  }
}
```

**Versão:** `2.2.0` → `3.0.0` (MAJOR - mudança incompatível)

---

## 5. IMPLEMENTAÇÃO PRÁTICA

### 5.1 Checklist de Implementação

Para adicionar versionamento em um KPI existente:

- [ ] **1. Definir metadados no início do arquivo**
  ```php
  $kpiMetadata = getKpiMetadata('nome-do-kpi', '1.0.0', 'Responsável', 'YYYY-MM-DD');
  ```

- [ ] **2. Passar metadados para `kpiResponse()`**
  ```php
  kpiResponse($kpi, $period, $data, $executionTime, 200, $kpiMetadata);
  //                                                       ↑ 6º parâmetro
  ```

- [ ] **3. Atualizar docblock do arquivo**
  ```php
  /**
   * @version 1.0.0 - Descrição da versão
   * @owner Nome do responsável
   */
  ```

### 5.2 Exemplo Completo

**Arquivo:** `kpi-backlog-atual.php`

```php
<?php
/**
 * KPI: Backlog Atual
 * 
 * Equipamentos recebidos que ainda não foram enviados para análise.
 * 
 * @version 3.0.0 - Versionamento implementado em 15/01/2026
 * @owner Equipe Backend VISTA
 * @uses kpiResponse() - Contrato padronizado
 * @uses getKpiMetadata() - Versionamento de KPI
 */

require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ============================================
// METADADOS DE VERSIONAMENTO
// ============================================
$kpiMetadata = getKpiMetadata(
    'kpi-backlog-atual',           // Nome técnico
    '3.0.0',                        // Versão
    'Equipe Backend VISTA',         // Owner
    '2026-01-15'                    // Última atualização
);

// ... restante do código ...

try {
    // ... lógica do KPI ...

    // ============================================
    // RETORNA RESPOSTA COM VERSIONAMENTO
    // ============================================
    kpiResponse(
        'backlog-recebimento',
        $period,
        $data,
        $executionTime,
        200,
        $kpiMetadata  // ✅ Metadados incluídos
    );

} catch (Exception $e) {
    kpiError('backlog-recebimento', $e->getMessage(), 500);
}
?>
```

### 5.3 Uso Sem `last_updated` (Detecção Automática)

Se você preferir que a data seja detectada automaticamente do arquivo:

```php
$kpiMetadata = getKpiMetadata(
    'kpi-backlog-atual',
    '3.0.0',
    'Equipe Backend VISTA'
    // ✅ Sem 4º parâmetro: usa filemtime() automaticamente
);
```

**Comportamento:**
1. Procura arquivo `kpi-backlog-atual.php` nos diretórios padrão
2. Se encontrado: `last_updated = date('Y-m-d', filemtime($arquivo))`
3. Se não encontrado: `last_updated = date('Y-m-d')` (data atual)

---

## 6. EXEMPLO DE RESPOSTA JSON

### 6.1 Resposta Completa

**Request:**
```http
GET /DashBoard/backendDash/recebimentoPHP/kpi-backlog-atual.php?inicio=01/01/2026&fim=15/01/2026 HTTP/1.1
```

**Response:**
```json
{
  "status": "success",
  "kpi": "backlog-recebimento",
  "period": "2026-01-01 / 2026-01-15",
  "data": {
    "valor": 125,
    "valor_formatado": "125",
    "unidade": "equipamentos",
    "contexto": "Equipamentos aguardando envio para análise",
    "detalhes": {
      "percentual_criticidade": "medio"
    },
    "referencia": {
      "tipo": "periodo_anterior",
      "valor": 98,
      "periodo": "2025-12-17 a 2025-12-31",
      "descricao": "Backlog do período anterior (mesmo tamanho)"
    },
    "variacao": {
      "percentual": 27.55,
      "tendencia": "alta",
      "estado": "warning",
      "interpretacao": "Backlog aumentou - atenção necessária"
    },
    "filtros_aplicados": {
      "data_inicio": "2026-01-01",
      "data_fim": "2026-01-15",
      "setor": "Todos",
      "operador": "Todos"
    }
  },
  "meta": {
    "generatedAt": "2026-01-15T14:35:42-03:00",
    "executionTimeMs": 247.35,
    "source": "vista-kpi",
    "kpi_version": "3.0.0",               // ✅ NOVO
    "kpi_owner": "Equipe Backend VISTA",  // ✅ NOVO
    "last_updated": "2026-01-15"          // ✅ NOVO
  }
}
```

### 6.2 Comparação Antes/Depois

#### Antes (sem versionamento)
```json
{
  "meta": {
    "generatedAt": "2026-01-15T14:35:42-03:00",
    "executionTimeMs": 247.35,
    "source": "vista-kpi"
  }
}
```

#### Depois (com versionamento)
```json
{
  "meta": {
    "generatedAt": "2026-01-15T14:35:42-03:00",
    "executionTimeMs": 247.35,
    "source": "vista-kpi",
    "kpi_version": "3.0.0",               // ✅ ADICIONADO
    "kpi_owner": "Equipe Backend VISTA",  // ✅ ADICIONADO
    "last_updated": "2026-01-15"          // ✅ ADICIONADO
  }
}
```

---

## 7. MIGRAÇÃO DE KPIs EXISTENTES

### 7.1 Lista de KPIs Candidatos

Total de **28 KPIs** identificados para receber versionamento:

#### **KPIs Globais (5):**
- [ ] `kpi-total-processado.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-tempo-medio.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-sucesso.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-sem-conserto.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-valor-orcado.php` → Versão sugerida: `1.0.0`

#### **Recebimento (11):**
- [x] `kpi-backlog-atual.php` → **v3.0.0** ✅ (PILOTO - CONCLUÍDO)
- [ ] `kpi-equipamentos-recebidos.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-finalizacao.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-tempo-medio-recebimento.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-rejeicao.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-evolucao-recebimentos.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-top-clientes.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-recebimento-operador.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-tempo-medio.php` → Versão sugerida: `1.0.0`
- [ ] `insights-recebimento.php` → Versão sugerida: `1.0.0`
- [ ] `tabela-detalhada.php` → Versão sugerida: `1.0.0`

#### **Análise (6):**
- [ ] `kpi-backlog-analise.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-equipamentos-analisados.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-aprovacao-analise.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-tempo-medio-analise.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-reprovacao-analise.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-evolucao-analise.php` → Versão sugerida: `1.0.0`

#### **Reparo (6):**
- [ ] `kpi-backlog-reparo.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-equipamentos-reparados.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-sucesso-reparo.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-tempo-medio-reparo.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-custo-medio-reparo.php` → Versão sugerida: `1.0.0`
- [ ] `grafico-evolucao-reparo.php` → Versão sugerida: `1.0.0`

#### **Qualidade (5):**
- [ ] `kpi-backlog-qualidade.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-equipamentos-aprovados.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-aprovacao.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-tempo-medio-qualidade.php` → Versão sugerida: `1.0.0`
- [ ] `kpi-taxa-reprovacao.php` → Versão sugerida: `1.0.0`

### 7.2 Script de Migração Automatizada

**Arquivo:** `adicionar_versionamento_kpis.php`

```php
<?php
/**
 * Script de migração para adicionar versionamento em todos os KPIs
 * 
 * Uso: php adicionar_versionamento_kpis.php
 */

$kpiFiles = [
    'DashBoard/backendDash/kpis/kpi-total-processado.php',
    'DashBoard/backendDash/kpis/kpi-tempo-medio.php',
    'DashBoard/backendDash/kpis/kpi-taxa-sucesso.php',
    'DashBoard/backendDash/kpis/kpi-sem-conserto.php',
    'DashBoard/backendDash/kpis/kpi-valor-orcado.php',
    // ... adicionar todos os 27 restantes
];

$addedCount = 0;
$skippedCount = 0;

foreach ($kpiFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "⚠️ Arquivo não encontrado: {$file}\n";
        $skippedCount++;
        continue;
    }
    
    $content = file_get_contents($fullPath);
    
    // Verificar se já tem versionamento
    if (strpos($content, 'getKpiMetadata') !== false) {
        echo "⏭️ Já possui versionamento: {$file}\n";
        $skippedCount++;
        continue;
    }
    
    // Extrair nome do KPI do arquivo
    $kpiName = basename($file, '.php');
    
    // Adicionar metadados após os requires
    $metadataCode = "\n// ============================================\n";
    $metadataCode .= "// METADADOS DE VERSIONAMENTO\n";
    $metadataCode .= "// ============================================\n";
    $metadataCode .= "\$kpiMetadata = getKpiMetadata(\n";
    $metadataCode .= "    '{$kpiName}',\n";
    $metadataCode .= "    '1.0.0',\n";
    $metadataCode .= "    'Equipe Backend VISTA'\n";
    $metadataCode .= ");\n\n";
    
    // Inserir após o último require_once
    $content = preg_replace(
        '/(require_once.*?;)\n\n/s',
        "$1\n" . $metadataCode,
        $content,
        1
    );
    
    // Adicionar $kpiMetadata na chamada kpiResponse
    $content = preg_replace(
        '/kpiResponse\((.*?)\);/s',
        'kpiResponse($1, $kpiMetadata);',
        $content
    );
    
    file_put_contents($fullPath, $content);
    echo "✅ Versionamento adicionado: {$file}\n";
    $addedCount++;
}

echo "\n🎉 Migração concluída!\n";
echo "✅ Adicionados: {$addedCount}\n";
echo "⏭️ Pulados: {$skippedCount}\n";
?>
```

**Executar:**
```bash
php adicionar_versionamento_kpis.php
```

### 7.3 Migração Manual (Passo a Passo)

Para adicionar versionamento manualmente em 1 KPI:

1. **Abrir o arquivo do KPI**
   ```bash
   code DashBoard/backendDash/kpis/kpi-total-processado.php
   ```

2. **Adicionar metadados após os requires**
   ```php
   require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
   
   // ============================================
   // METADADOS DE VERSIONAMENTO
   // ============================================
   $kpiMetadata = getKpiMetadata(
       'kpi-total-processado',
       '1.0.0',
       'Equipe Backend VISTA'
   );
   ```

3. **Modificar chamada `kpiResponse()`**
   ```php
   // ANTES
   kpiResponse($kpi, $period, $data, $executionTime);
   
   // DEPOIS
   kpiResponse($kpi, $period, $data, $executionTime, 200, $kpiMetadata);
   ```

4. **Atualizar docblock**
   ```php
   /**
    * KPI: Volume Processado
    * 
    * @version 1.0.0 - Versionamento implementado
    * @owner Equipe Backend VISTA
    */
   ```

5. **Testar o endpoint**
   ```bash
   curl "http://localhost/DashBoard/backendDash/kpis/kpi-total-processado.php?inicio=01/01/2026&fim=15/01/2026"
   ```

6. **Validar resposta JSON**
   Verificar se bloco `meta` contém:
   - `kpi_version`
   - `kpi_owner`
   - `last_updated`

---

## 8. BOAS PRÁTICAS

### 8.1 Nomenclatura de Versões

✅ **DO (Faça):**
```php
getKpiMetadata('kpi-backlog-atual', '1.0.0', 'Equipe Backend VISTA');
getKpiMetadata('kpi-tempo-medio', '2.1.3', 'João Silva');
getKpiMetadata('kpi-taxa-sucesso', '3.0.0', 'Time Analytics');
```

❌ **DON'T (Não Faça):**
```php
getKpiMetadata('kpi-backlog-atual', 'v1', 'Backend');  // ❌ Versão incompleta
getKpiMetadata('kpi-backlog-atual', '1', 'Backend');   // ❌ Falta MINOR e PATCH
getKpiMetadata('backlog', '1.0.0', '');                // ❌ Nome inconsistente, owner vazio
```

### 8.2 Quando Atualizar Versões

#### Fluxo de Trabalho

1. **Modificação no KPI** → Avaliar tipo de mudança
2. **Incrementar versão apropriada** (MAJOR/MINOR/PATCH)
3. **Atualizar `last_updated`** (manualmente ou automático via `filemtime`)
4. **Documentar mudança** no docblock do arquivo
5. **Commit com mensagem descritiva**

#### Exemplo de Commit Messages

```bash
# PATCH (correção de bug)
git commit -m "fix(kpi-backlog): Corrige divisão por zero na variação - v2.1.4"

# MINOR (nova funcionalidade)
git commit -m "feat(kpi-backlog): Adiciona campo media_diaria - v2.2.0"

# MAJOR (breaking change)
git commit -m "BREAKING CHANGE(kpi-backlog): Muda valor de string para int - v3.0.0"
```

### 8.3 Ownership

#### Definições Sugeridas

| Owner | Responsabilidade | Exemplo |
|-------|------------------|---------|
| `Equipe Backend VISTA` | KPIs operacionais padrão | `kpi-backlog-atual.php` |
| `Equipe Analytics` | KPIs de análise e insights | `insights-recebimento.php` |
| `Time de Qualidade` | KPIs de qualidade | `kpi-taxa-aprovacao.php` |
| `João Silva` | Responsabilidade individual | KPIs customizados |

### 8.4 Detecção Automática vs Manual

#### Quando Usar `last_updated` Manual

✅ **Usar manual quando:**
- KPI é atualizado frequentemente (evitar timestamps constantes)
- Quer marcar uma versão específica (ex: release 2.0.0)
- Múltiplos arquivos compartilham mesma lógica

#### Quando Usar Detecção Automática

✅ **Usar automático quando:**
- KPI é estável e mudanças são raras
- Quer rastreamento fiel do timestamp de modificação
- Apenas 1 arquivo define a lógica

**Exemplo:**
```php
// Manual (recomendado para releases)
$kpiMetadata = getKpiMetadata('kpi-backlog-atual', '3.0.0', 'Backend', '2026-01-15');

// Automático (recomendado para desenvolvimento)
$kpiMetadata = getKpiMetadata('kpi-backlog-atual', '3.0.0', 'Backend');
```

### 8.5 Validação de Versões

#### Script de Validação

```php
<?php
/**
 * Valida versionamento de todos os KPIs
 */

$kpiFiles = glob('DashBoard/backendDash/**/*.php', GLOB_BRACE);
$erros = [];

foreach ($kpiFiles as $file) {
    $content = file_get_contents($file);
    
    // Verificar se tem getKpiMetadata
    if (strpos($content, 'getKpiMetadata') === false) {
        $erros[] = "❌ {$file} - Sem versionamento";
        continue;
    }
    
    // Verificar formato de versão (semver)
    if (!preg_match('/getKpiMetadata\([^,]+,\s*[\'"]\d+\.\d+\.\d+[\'"]/', $content)) {
        $erros[] = "⚠️ {$file} - Versão inválida (deve ser X.Y.Z)";
    }
    
    // Verificar se owner não está vazio
    if (!preg_match('/getKpiMetadata\([^,]+,[^,]+,\s*[\'"](?![\'"]).+?[\'"]/', $content)) {
        $erros[] = "⚠️ {$file} - Owner não definido";
    }
}

if (count($erros) > 0) {
    echo "Erros encontrados:\n";
    foreach ($erros as $erro) {
        echo "  {$erro}\n";
    }
    exit(1);
} else {
    echo "✅ Todos os KPIs estão versionados corretamente!\n";
    exit(0);
}
?>
```

**Executar:**
```bash
php validar_versionamento.php
```

### 8.6 Documentação de Mudanças

#### Template de Docblock

```php
<?php
/**
 * KPI: Backlog Atual
 * 
 * Equipamentos recebidos que ainda não foram enviados para análise.
 * 
 * @version 3.0.0 - Versionamento implementado em 15/01/2026
 * @owner Equipe Backend VISTA
 * 
 * Changelog:
 * - v3.0.0 (15/01/2026): Versionamento implementado, log de execução adicionado
 * - v2.1.0 (15/01/2026): Autenticação via middleware
 * - v2.0.0 (14/01/2026): Contrato padronizado kpiResponse()
 * - v1.0.0 (01/12/2025): Versão inicial
 */
```

---

## 9. INTEGRAÇÃO COM FRONTEND

### 9.1 Exibir Versão no Console

```javascript
// frontend: fetch-helpers.js

async function fetchKPI(url) {
    const response = await fetch(url);
    const data = await response.json();
    
    // ✅ Exibir informações de versionamento no console
    if (data.meta) {
        console.log(`[KPI ${data.kpi}] v${data.meta.kpi_version} | Owner: ${data.meta.kpi_owner} | Atualizado: ${data.meta.last_updated}`);
    }
    
    return data;
}
```

**Saída no console:**
```
[KPI backlog-recebimento] v3.0.0 | Owner: Equipe Backend VISTA | Atualizado: 2026-01-15
```

### 9.2 Badge de Versão na UI (Opcional)

```javascript
// Adicionar badge de versão no card do KPI

function renderKPI(kpiData) {
    const version = kpiData.meta.kpi_version;
    
    const badge = `<span class="version-badge">v${version}</span>`;
    const card = `
        <div class="kpi-card">
            <h3>${kpiData.kpi} ${badge}</h3>
            <div class="kpi-value">${kpiData.data.valor}</div>
        </div>
    `;
    
    return card;
}
```

**CSS:**
```css
.version-badge {
    font-size: 0.7em;
    color: #888;
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    margin-left: 8px;
}
```

---

## 10. MONITORAMENTO E ANÁLISE

### 10.1 Relatório de Versões

**Script:** `relatorio_versoes.php`

```php
<?php
/**
 * Gera relatório de versões de todos os KPIs
 */

$kpiFiles = glob('DashBoard/backendDash/**/*.php', GLOB_BRACE);
$versoes = [];

foreach ($kpiFiles as $file) {
    $content = file_get_contents($file);
    
    if (preg_match('/getKpiMetadata\([^,]+,\s*[\'"](\d+\.\d+\.\d+)[\'"]/', $content, $matchVersion)) {
        $version = $matchVersion[1];
        
        if (preg_match('/getKpiMetadata\([^,]+,[^,]+,\s*[\'"](.+?)[\'"]/', $content, $matchOwner)) {
            $owner = $matchOwner[1];
        } else {
            $owner = 'Não definido';
        }
        
        $versoes[] = [
            'arquivo' => basename($file),
            'versao' => $version,
            'owner' => $owner,
            'path' => $file
        ];
    }
}

// Ordenar por versão (mais recente primeiro)
usort($versoes, function($a, $b) {
    return version_compare($b['versao'], $a['versao']);
});

echo "📊 RELATÓRIO DE VERSÕES - SISTEMA VISTA\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($versoes as $kpi) {
    printf("%-40s v%-8s Owner: %s\n", $kpi['arquivo'], $kpi['versao'], $kpi['owner']);
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Total de KPIs versionados: " . count($versoes) . "\n";
?>
```

**Saída esperada:**
```
📊 RELATÓRIO DE VERSÕES - SISTEMA VISTA
================================================================================

kpi-backlog-atual.php                    v3.0.0   Owner: Equipe Backend VISTA
kpi-tempo-medio.php                      v2.1.0   Owner: Equipe Backend VISTA
kpi-taxa-sucesso.php                     v2.0.0   Owner: Time Analytics
kpi-equipamentos-recebidos.php           v1.0.0   Owner: Equipe Backend VISTA

================================================================================
Total de KPIs versionados: 4
```

---

## 11. TROUBLESHOOTING

### 11.1 Metadados não aparecem no JSON

**Sintoma:**
```json
{
  "meta": {
    "generatedAt": "2026-01-15T14:35:42-03:00",
    "executionTimeMs": 247.35,
    "source": "vista-kpi"
    // ❌ Faltam: kpi_version, kpi_owner, last_updated
  }
}
```

**Causa:** `$kpiMetadata` não foi passado para `kpiResponse()`

**Solução:**
```php
// ANTES (errado)
kpiResponse($kpi, $period, $data, $executionTime);

// DEPOIS (correto)
kpiResponse($kpi, $period, $data, $executionTime, 200, $kpiMetadata);
//                                                       ↑ 6º parâmetro
```

---

### 11.2 Versão inválida (`1` ou `v1.0`)

**Sintoma:**
```json
{
  "meta": {
    "kpi_version": "1"  // ❌ Incompleto
  }
}
```

**Causa:** Formato incorreto na chamada `getKpiMetadata()`

**Solução:**
```php
// ERRADO
getKpiMetadata('kpi-backlog', '1', 'Backend');
getKpiMetadata('kpi-backlog', 'v1.0', 'Backend');

// CORRETO
getKpiMetadata('kpi-backlog-atual', '1.0.0', 'Equipe Backend');
```

---

### 11.3 `last_updated` sempre retorna data atual

**Sintoma:**
Mesmo sem modificar o arquivo, `last_updated` muda a cada execução.

**Causa:** Sistema de cache ou `touch` está alterando `filemtime()`

**Solução:** Definir `last_updated` manualmente:
```php
getKpiMetadata('kpi-backlog-atual', '3.0.0', 'Backend', '2026-01-15');
//                                                       ↑ Data fixa
```

---

## 12. ROADMAP E MELHORIAS FUTURAS

### 12.1 Curto Prazo (1-2 meses)

- [ ] **Migrar 27 KPIs restantes** para usar versionamento
- [ ] **Criar endpoint `/meta/versions`** para listar todas as versões
- [ ] **Dashboard de versionamento** (página HTML com lista de KPIs e versões)
- [ ] **Validação automática via CI/CD** (GitHub Actions)

### 12.2 Médio Prazo (3-6 meses)

- [ ] **Changelog automático** (gerado a partir de commits)
- [ ] **Deprecation warnings** (avisar KPIs antigos)
- [ ] **Versionamento de dependências** (rastrear uso de helpers)
- [ ] **API de comparação de versões** (diff entre v1.0.0 e v2.0.0)

### 12.3 Longo Prazo (6-12 meses)

- [ ] **Semantic versioning enforcement** (bloquear commits com versões inválidas)
- [ ] **Rollback automatizado** (reverter para versão anterior em caso de erro)
- [ ] **Testes de compatibilidade** (garantir backward compatibility em MINOR/PATCH)
- [ ] **Documentação interativa** (Swagger/OpenAPI com versionamento)

---

## 13. CONCLUSÃO

### 13.1 Critérios de Aceite

✅ **Versão visível no JSON**
- Campo `kpi_version` presente no bloco `meta`
- Formato semântico MAJOR.MINOR.PATCH
- Valor dinâmico baseado em `getKpiMetadata()`

✅ **Padrão único**
- Uma função reutilizável (`getKpiMetadata()`)
- Integração transparente com `kpiResponse()`
- Formato consistente em todos os KPIs

✅ **Fácil manutenção futura**
- 3 linhas de código para adicionar versionamento em qualquer KPI
- Detecção automática de `last_updated` via `filemtime()`
- Retrocompatibilidade total (KPIs sem versionamento continuam funcionando)

### 13.2 Benefícios Obtidos

🎯 **Rastreabilidade:** Histórico completo de versões  
📊 **Auditoria:** Identificação de responsáveis (owner)  
🔧 **Manutenção:** Fácil atualização de metadados  
📈 **Evolução:** Planejamento de breaking changes (MAJOR)  
🚀 **Escalabilidade:** Pronto para 100+ KPIs

### 13.3 Próximos Passos

1. **Migrar todos os 27 KPIs restantes** (estimativa: 1-2 horas)
2. **Criar endpoint `/meta/versions`** (lista todas as versões)
3. **Implementar validação no CI/CD** (bloquear versões inválidas)
4. **Documentar changelog** de cada KPI no docblock

---

**Fim da Documentação**

---

*Gerado automaticamente pelo Sistema VISTA - KPI 2.0*  
*Para dúvidas técnicas, consulte: endpoint-helpers.php (linha ~201)*
