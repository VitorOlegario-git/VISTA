# 📋 CONTRATO PADRÃO DE RESPOSTA KPI - SISTEMA VISTA

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Status:** ✅ Implementado e Pronto para Uso

---

## 🎯 Objetivo

Estabelecer um **contrato único e consistente** para todos os endpoints de KPI do sistema VISTA, garantindo:

- ✅ Previsibilidade nas respostas
- ✅ Facilidade de integração no frontend
- ✅ Rastreabilidade e debugging
- ✅ Performance monitorada
- ✅ Tratamento de erro padronizado

---

## 📐 Contrato de Resposta (Sucesso)

### Estrutura JSON

```json
{
  "status": "success",
  "kpi": "string",
  "period": "YYYY-MM-DD / YYYY-MM",
  "data": {},
  "meta": {
    "generatedAt": "ISO_DATE",
    "executionTimeMs": number,
    "source": "vista-kpi"
  }
}
```

### Descrição dos Campos

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `status` | `string` | ✅ Sim | Sempre `"success"` em respostas bem-sucedidas |
| `kpi` | `string` | ✅ Sim | Identificador único do KPI (ex: `"volume-processado"`) |
| `period` | `string` | ✅ Sim | Período no formato `YYYY-MM-DD / YYYY-MM-DD` ou `YYYY-MM` |
| `data` | `object` | ✅ Sim | Estrutura livre com os dados do KPI |
| `meta` | `object` | ✅ Sim | Metadados sobre a resposta |
| `meta.generatedAt` | `string` | ✅ Sim | Timestamp ISO 8601 da geração |
| `meta.executionTimeMs` | `number` | ✅ Sim | Tempo de execução em milissegundos |
| `meta.source` | `string` | ✅ Sim | Sempre `"vista-kpi"` |

---

## 📐 Contrato de Resposta (Erro)

### Estrutura JSON

```json
{
  "status": "error",
  "kpi": "string",
  "message": "string",
  "meta": {
    "generatedAt": "ISO_DATE",
    "source": "vista-kpi"
  }
}
```

### Descrição dos Campos

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `status` | `string` | ✅ Sim | Sempre `"error"` em respostas de erro |
| `kpi` | `string` | ✅ Sim | Identificador único do KPI |
| `message` | `string` | ✅ Sim | Mensagem descritiva do erro |
| `meta` | `object` | ✅ Sim | Metadados sobre a resposta |
| `meta.generatedAt` | `string` | ✅ Sim | Timestamp ISO 8601 da geração |
| `meta.source` | `string` | ✅ Sim | Sempre `"vista-kpi"` |

---

## 🔧 Implementação PHP

### Função: `kpiResponse()`

**Localização:** `BackEnd/endpoint-helpers.php`

**Assinatura:**
```php
function kpiResponse(
    string $kpi,
    string $period,
    array $data,
    float $executionTimeMs,
    int $httpCode = 200
): void
```

**Parâmetros:**

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `$kpi` | `string` | Identificador único do KPI | `'volume-processado'` |
| `$period` | `string` | Período formatado | `'2026-01-07 / 2026-01-14'` |
| `$data` | `array` | Dados estruturados do KPI | `['valor' => 1250, ...]` |
| `$executionTimeMs` | `float` | Tempo de execução em ms | `45.23` |
| `$httpCode` | `int` | Código HTTP (opcional) | `200` (default) |

**Exemplo de Uso:**
```php
<?php
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

$startTime = microtime(true);

// ... lógica do KPI ...

$data = [
    'valor' => 1250,
    'unidade' => 'equipamentos',
    'variacao' => ['percentual' => 5.9, 'tendencia' => 'alta']
];

$executionTime = (microtime(true) - $startTime) * 1000;

kpiResponse(
    'volume-processado',
    '2026-01-07 / 2026-01-14',
    $data,
    $executionTime
);
?>
```

---

### Função: `kpiError()`

**Assinatura:**
```php
function kpiError(
    string $kpi,
    string $message,
    int $httpCode = 500
): void
```

**Parâmetros:**

| Parâmetro | Tipo | Descrição | Exemplo |
|-----------|------|-----------|---------|
| `$kpi` | `string` | Identificador único do KPI | `'volume-processado'` |
| `$message` | `string` | Mensagem de erro | `'Erro ao processar dados'` |
| `$httpCode` | `int` | Código HTTP de erro (opcional) | `500` (default) |

**Exemplo de Uso:**
```php
<?php
try {
    // ... lógica do KPI ...
} catch (Exception $e) {
    kpiError(
        'volume-processado',
        'Erro ao processar dados: ' . $e->getMessage(),
        500
    );
}
?>
```

---

## 📊 Estrutura Recomendada para `data`

Embora o campo `data` seja livre, recomenda-se seguir este padrão para consistência:

```json
{
  "valor": "número ou string formatada",
  "valor_formatado": "string com formatação regional",
  "unidade": "equipamentos | minutos | R$ | % | etc",
  "contexto": "Descrição textual do KPI",
  "detalhes": {
    "campo1": "valor adicional",
    "campo2": "valor adicional"
  },
  "referencia": {
    "tipo": "media_30d | periodo_anterior | meta",
    "valor": "número de comparação",
    "descricao": "Texto descritivo"
  },
  "variacao": {
    "percentual": "número (ex: 5.9)",
    "tendencia": "alta | baixa | estavel",
    "estado": "success | warning | critical"
  },
  "filtros_aplicados": {
    "data_inicio": "YYYY-MM-DD",
    "data_fim": "YYYY-MM-DD",
    "operador": "string ou null",
    "setor": "string ou null"
  }
}
```

---

## 🎨 Exemplo Completo de Resposta

### Requisição:
```http
GET /DashBoard/backendDash/kpis/kpi-volume-processado.php?inicio=07/01/2026&fim=14/01/2026&operador=Todos
```

### Resposta (HTTP 200):
```json
{
  "status": "success",
  "kpi": "volume-processado",
  "period": "2026-01-07 / 2026-01-14",
  "data": {
    "valor": 1250,
    "valor_formatado": "1.250",
    "unidade": "equipamentos",
    "contexto": "Volume processado no período",
    "detalhes": {
      "quantidade_total": 3750,
      "media_por_recebimento": 3.0
    },
    "referencia": {
      "tipo": "media_30d",
      "valor": 1180,
      "descricao": "Média dos últimos 30 dias"
    },
    "variacao": {
      "percentual": 5.93,
      "tendencia": "alta",
      "estado": "success"
    },
    "filtros_aplicados": {
      "data_inicio": "2026-01-07",
      "data_fim": "2026-01-14",
      "operador": "Todos"
    }
  },
  "meta": {
    "generatedAt": "2026-01-15T10:30:45-03:00",
    "executionTimeMs": 45.23,
    "source": "vista-kpi"
  }
}
```

---

## ❌ Exemplo de Resposta de Erro

### Requisição:
```http
GET /DashBoard/backendDash/kpis/kpi-volume-processado.php?inicio=INVALIDO&fim=14/01/2026
```

### Resposta (HTTP 400):
```json
{
  "status": "error",
  "kpi": "volume-processado",
  "message": "Formato de data inválido. Use dd/mm/yyyy",
  "meta": {
    "generatedAt": "2026-01-15T10:30:45-03:00",
    "source": "vista-kpi"
  }
}
```

---

## 📋 Checklist de Migração

Para migrar um endpoint KPI existente para o novo contrato:

### 1️⃣ Preparação
- [ ] Abrir o arquivo PHP do KPI
- [ ] Garantir que `endpoint-helpers.php` está incluído
- [ ] Adicionar medição de tempo no início do arquivo

### 2️⃣ Modificações
- [ ] Adicionar no topo: `$startTime = microtime(true);`
- [ ] Manter toda a lógica de query existente
- [ ] Estruturar dados em array `$data`
- [ ] Calcular: `$executionTime = (microtime(true) - $startTime) * 1000;`
- [ ] Formatar `$period` como `"YYYY-MM-DD / YYYY-MM-DD"`

### 3️⃣ Substituições
- [ ] Substituir `enviarSucesso()` por `kpiResponse()`
- [ ] Substituir `enviarErro()` por `kpiError()` nos blocos catch
- [ ] Atualizar identificador do KPI (ex: `'volume-processado'`)

### 4️⃣ Validação
- [ ] Testar endpoint no navegador ou Postman
- [ ] Validar JSON com JSONLint
- [ ] Verificar que frontend ainda funciona
- [ ] Confirmar tempo de execução < 500ms

### 5️⃣ Documentação
- [ ] Atualizar comentários do arquivo
- [ ] Adicionar exemplo de resposta no header do arquivo

---

## 🔄 Retrocompatibilidade

**Status:** ✅ Mantida

- As funções antigas `enviarSucesso()` e `enviarErro()` **continuam funcionando**
- Migração pode ser **gradual**, KPI por KPI
- Frontend não precisa ser atualizado imediatamente
- Ambos os contratos coexistem no sistema

**Recomendação:** Migrar progressivamente durante ciclos de manutenção

---

## ⚡ Performance

### Benchmarks Esperados

| KPI | Tempo Esperado | Alerta | Crítico |
|-----|----------------|--------|---------|
| Volume Processado | < 200ms | > 500ms | > 1000ms |
| Tempo Médio | < 300ms | > 700ms | > 1500ms |
| Taxa de Sucesso | < 250ms | > 600ms | > 1200ms |
| Valor Orçado | < 200ms | > 500ms | > 1000ms |
| Sem Conserto | < 150ms | > 400ms | > 800ms |

**Meta Global:** 90% dos KPIs devem responder em < 500ms

---

## 🛡️ Segurança

Headers de segurança incluídos automaticamente:

```php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
```

**Proteções:**
- ✅ Prevents MIME type sniffing
- ✅ Prevents clickjacking
- ✅ UTF-8 encoding
- ✅ CORS configurável (se necessário)

---

## 📚 Recursos Adicionais

### Arquivos Relacionados

| Arquivo | Descrição |
|---------|-----------|
| `BackEnd/endpoint-helpers.php` | Implementação das funções |
| `DashBoard/backendDash/kpis/EXEMPLO_USO_KPI_RESPONSE.php` | Exemplo completo |
| `RELATORIO_KPIS_DASHBOARD.md` | Documentação do sistema |

### Referências Externas

- [RFC 3339 - Date and Time on the Internet](https://tools.ietf.org/html/rfc3339)
- [JSON Schema Specification](https://json-schema.org/)
- [HTTP Status Codes](https://httpstatuses.com/)

---

## ✅ Critérios de Aceite

- [x] ✅ Função `kpiResponse()` criada e testada
- [x] ✅ Função `kpiError()` criada e testada
- [x] ✅ Headers de segurança implementados
- [x] ✅ Timestamp ISO 8601 funcionando
- [x] ✅ Tempo de execução sendo medido
- [x] ✅ Retrocompatibilidade mantida
- [x] ✅ Exemplo completo documentado
- [x] ✅ Sem quebra de KPIs existentes

**Status Final:** ✅ **TODOS OS CRITÉRIOS ATENDIDOS**

---

## 🔄 Changelog

| Versão | Data | Alteração |
|--------|------|-----------|
| 1.0 | 15/01/2026 | Criação do contrato padrão e implementação inicial |

---

## 👥 Contato

**Sistema:** VISTA - Sistema de Gestão Integrada  
**Módulo:** KPI Dashboard  
**Equipe:** Desenvolvimento SUNLAB  

**Para dúvidas ou sugestões sobre este contrato, consulte a equipe de desenvolvimento.**

---

**Fim do Documento**
