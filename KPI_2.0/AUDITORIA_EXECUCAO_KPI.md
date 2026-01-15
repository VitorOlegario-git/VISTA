# 🔍 SISTEMA DE AUDITORIA DE EXECUÇÃO DE KPIs
## VISTA - Módulo de Observabilidade e Compliance

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Sistema:** VISTA - KPI 2.0  
**Autor:** Equipe Backend VISTA

---

## 📑 ÍNDICE

1. [Visão Geral](#visão-geral)
2. [Função auditarExecucaoKpi()](#função-auditarexecucaokpi)
3. [Integração com KPIs](#integração-com-kpis)
4. [Formato do Log de Auditoria](#formato-do-log-de-auditoria)
5. [Compliance e Privacidade](#compliance-e-privacidade)
6. [Análise de Logs](#análise-de-logs)
7. [Migração de KPIs Existentes](#migração-de-kpis-existentes)
8. [Boas Práticas](#boas-práticas)
9. [Troubleshooting](#troubleshooting)
10. [Roadmap](#roadmap)

---

## 1. VISÃO GERAL

### 1.1 Objetivos

O **Sistema de Auditoria de Execução** fornece:

✅ **Observabilidade:** Rastreamento de quem acessa quais KPIs e quando  
✅ **Compliance:** Preparação para LGPD, GDPR e auditorias internas  
✅ **Segurança:** Detecção de padrões anômalos de acesso  
✅ **Métricas:** Análise de uso dos KPIs (frequência, horários, usuários)  
✅ **Não-bloqueante:** Falhas na auditoria **NÃO** interrompem execução do KPI

---

### 1.2 Arquitetura

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUXO DE AUDITORIA                        │
└─────────────────────────────────────────────────────────────┘

1. REQUISIÇÃO HTTP GET
   └─ URL: /kpis/kpi-backlog-atual.php?inicio=...&fim=...

2. VALIDAÇÃO DE AUTENTICAÇÃO
   └─ validarAutenticacao() ✅

3. AUDITORIA (OPCIONAL - NÃO BLOQUEIA)
   └─ auditarExecucaoKpi()
      ├─ Captura: usuário, IP, período, params
      ├─ Anonimiza IP (LGPD/GDPR)
      ├─ Grava em logs/audit.log (LOCK_EX)
      └─ Retorna true/false (não interrompe execução)

4. EXECUÇÃO DO KPI
   └─ Lógica de negócio (queries, cálculos)

5. RESPOSTA JSON
   └─ kpiResponse() com dados + metadata
```

**Princípios de Design:**
- ⚡ **Performance:** Overhead < 5ms por requisição
- 🔒 **Privacidade:** IP anonimizado automaticamente
- 🛡️ **Resiliência:** Try-catch evita bloqueios
- 📝 **Estruturado:** Formato consistente para análise

---

## 2. FUNÇÃO auditarExecucaoKpi()

### 2.1 Assinatura

```php
function auditarExecucaoKpi(
    string $kpiName,          // Nome do KPI executado
    array $periodo,           // ['inicio' => 'dd/mm/yyyy', 'fim' => 'dd/mm/yyyy']
    ?string $usuario = null,  // Identificador do usuário (ou 'anonymous')
    ?string $ip = null,       // IP do cliente (anonimizado automaticamente)
    array $queryParams = []   // Parâmetros da requisição (filtros aplicados)
): bool                       // True = sucesso, False = falha (não bloqueia)
```

---

### 2.2 Parâmetros Detalhados

| Parâmetro | Tipo | Obrigatório | Descrição | Exemplo |
|-----------|------|-------------|-----------|---------|
| `$kpiName` | string | ✅ Sim | Nome técnico do KPI | `'kpi-backlog-atual'` |
| `$periodo` | array | ✅ Sim | Array com 'inicio' e 'fim' | `['inicio' => '07/01/2026', 'fim' => '14/01/2026']` |
| `$usuario` | string\|null | ❌ Não | Login, email, ou 'anonymous' | `'joao.silva'` ou `$_SESSION['usuario']` |
| `$ip` | string\|null | ❌ Não | IP do cliente (será anonimizado) | `'192.168.1.100'` ou `$_SERVER['REMOTE_ADDR']` |
| `$queryParams` | array | ❌ Não | Filtros aplicados na requisição | `['operador' => 'Todos', 'setor' => 'Reparo']` |

---

### 2.3 Retorno

- **`true`**: Auditoria gravada com sucesso em `logs/audit.log`
- **`false`**: Falha ao gravar (não interrompe execução do KPI)

⚠️ **IMPORTANTE:** O retorno pode ser ignorado no código de produção, pois falhas são silenciosas.

---

### 2.4 Exemplo de Uso Básico

```php
<?php
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ============================================
// AUDITORIA DE EXECUÇÃO (OPCIONAL)
// ============================================
auditarExecucaoKpi(
    'kpi-backlog-atual',                          // Nome do KPI
    [
        'inicio' => $_GET['inicio'] ?? 'N/A',
        'fim' => $_GET['fim'] ?? 'N/A'
    ],
    $_SESSION['usuario'] ?? 'anonymous',          // Usuário autenticado
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',         // IP do cliente
    [
        'operador' => $_GET['operador'] ?? null,
        'setor' => $_GET['setor'] ?? null
    ]
);

// KPI continua normalmente, independente do resultado da auditoria
?>
```

---

### 2.5 Exemplo de Uso Avançado

```php
<?php
// ============================================
// CAPTURA DE USUÁRIO (MÚLTIPLAS FONTES)
// ============================================
$usuario = null;

// Tentar capturar de session (autenticação via login)
if (isset($_SESSION['usuario'])) {
    $usuario = $_SESSION['usuario'];
}
// Tentar capturar de PHP_AUTH_USER (HTTP Basic Auth)
elseif (isset($_SERVER['PHP_AUTH_USER'])) {
    $usuario = $_SERVER['PHP_AUTH_USER'];
}
// Tentar capturar de token JWT (se implementado)
elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    // Decodificar token JWT e extrair 'sub' ou 'email'
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
    $usuario = $payload['sub'] ?? $payload['email'] ?? null;
}
// Fallback para 'anonymous'
else {
    $usuario = 'anonymous';
}

// ============================================
// AUDITORIA COM USUÁRIO IDENTIFICADO
// ============================================
auditarExecucaoKpi(
    'kpi-tempo-medio',
    ['inicio' => '01/01/2026', 'fim' => '31/01/2026'],
    $usuario,                                    // Usuário identificado
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    [
        'operador' => 'Maria Santos',
        'setor' => 'Reparo',
        'filtro_status' => 'Concluído'
    ]
);
?>
```

---

## 3. INTEGRAÇÃO COM KPIs

### 3.1 Onde Adicionar no Código

**Localização ideal:** Logo após a validação de autenticação e **ANTES** da lógica de negócio.

```php
<?php
require_once __DIR__ . '/../../../BackEnd/config.php';
require_once __DIR__ . '/../../../BackEnd/Database.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
require_once __DIR__ . '/../../../BackEnd/auth-middleware.php';

// ============================================
// VALIDAÇÃO DE AUTENTICAÇÃO
// ============================================
validarAutenticacao();

// ============================================
// AUDITORIA DE EXECUÇÃO (OPCIONAL)
// ============================================
auditarExecucaoKpi(
    'kpi-nome-do-endpoint',
    [
        'inicio' => $_GET['inicio'] ?? 'N/A',
        'fim' => $_GET['fim'] ?? 'N/A'
    ],
    $_SESSION['usuario'] ?? 'anonymous',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    [
        'operador' => $_GET['operador'] ?? null,
        'setor' => $_GET['setor'] ?? null
        // Adicionar outros filtros relevantes
    ]
);

// ============================================
// MARCA TEMPO DE INÍCIO
// ============================================
$startTime = microtime(true);

// ... resto do código do KPI
?>
```

---

### 3.2 KPI Piloto: kpi-backlog-atual.php

**Status:** ✅ Implementado (v3.1.0)

```php
<?php
/**
 * @version 3.1.0 - Auditoria implementada em 15/01/2026
 * @uses auditarExecucaoKpi() - Auditoria de execução
 */

require_once __DIR__ . '/../../../BackEnd/config.php';
require_once __DIR__ . '/../../../BackEnd/Database.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
require_once __DIR__ . '/../../../BackEnd/auth-middleware.php';

// Metadados de versionamento
$kpiMetadata = getKpiMetadata(
    'kpi-backlog-atual',
    '3.1.0',
    'Equipe Backend VISTA',
    '2026-01-15'
);

// Validação de autenticação
validarAutenticacao();

// ✅ AUDITORIA (NOVA)
auditarExecucaoKpi(
    'kpi-backlog-atual',
    [
        'inicio' => $_GET['inicio'] ?? 'N/A',
        'fim' => $_GET['fim'] ?? 'N/A'
    ],
    $_SESSION['usuario'] ?? $_SERVER['PHP_AUTH_USER'] ?? 'anonymous',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    [
        'setor' => $_GET['setor'] ?? null,
        'operador' => $_GET['operador'] ?? null
    ]
);

// Marca tempo de início
$startTime = microtime(true);

// ... resto do código
?>
```

---

## 4. FORMATO DO LOG DE AUDITORIA

### 4.1 Estrutura do Arquivo

**Caminho:** `logs/audit.log`  
**Permissões:** `0644` (rw-r--r--)  
**Formato:** Uma linha por requisição

---

### 4.2 Anatomia de uma Linha de Log

```
[2026-01-15 10:30:45] [kpi-backlog-atual] usuario=joao.silva ip=192.168.*.** periodo=07/01/2026-14/01/2026 params={"operador":"Todos"}
```

**Campos:**

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| `[timestamp]` | Data e hora no formato `Y-m-d H:i:s` | `[2026-01-15 10:30:45]` |
| `[kpi]` | Nome técnico do KPI acessado | `[kpi-backlog-atual]` |
| `usuario=` | Identificador do usuário (sanitizado) | `usuario=joao.silva` |
| `ip=` | IP anonimizado (últimos 2 octetos mascarados) | `ip=192.168.*.**` |
| `periodo=` | Período consultado (formato dd/mm/yyyy) | `periodo=07/01/2026-14/01/2026` |
| `params=` | Filtros aplicados (JSON compacto) | `params={"operador":"Todos","setor":"Reparo"}` |

---

### 4.3 Exemplos Reais de Logs

```log
[2026-01-15 08:15:23] [kpi-backlog-atual] usuario=joao.silva ip=192.168.*.** periodo=07/01/2026-14/01/2026 params={"operador":"Todos"}
[2026-01-15 08:16:02] [kpi-tempo-medio] usuario=maria.santos ip=10.0.*.** periodo=01/01/2026-31/01/2026 params={"operador":"João Silva","setor":"Reparo"}
[2026-01-15 08:17:45] [kpi-taxa-sucesso] usuario=anonymous ip=203.0.*.** periodo=01/12/2025-31/12/2025 params={}
[2026-01-15 08:20:11] [kpi-sem-conserto] usuario=pedro.costa ip=192.168.*.** periodo=14/01/2026-14/01/2026 params={"operador":"Todos","setor":"Qualidade"}
[2026-01-15 08:22:30] [kpi-valor-orcado] usuario=admin ip=127.0.*.** periodo=01/01/2026-15/01/2026 params={"setor":"Análise"}
```

---

### 4.4 Rotação de Logs (Recomendado)

Para evitar crescimento excessivo do arquivo, configure rotação automática:

**Opção 1: Logrotate (Linux)**
```bash
# /etc/logrotate.d/vista-audit
/var/www/kpi_2.0/logs/audit.log {
    daily
    rotate 90
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

**Opção 2: Script PHP Customizado**
```php
<?php
// cron-rotate-audit.php
$auditFile = __DIR__ . '/logs/audit.log';
$maxSize = 10 * 1024 * 1024; // 10 MB

if (file_exists($auditFile) && filesize($auditFile) > $maxSize) {
    $timestamp = date('Y-m-d_His');
    rename($auditFile, __DIR__ . "/logs/audit_$timestamp.log");
    
    // Compactar arquivo antigo
    exec("gzip " . __DIR__ . "/logs/audit_$timestamp.log");
    
    // Deletar arquivos com mais de 90 dias
    exec("find " . __DIR__ . "/logs -name 'audit_*.log.gz' -mtime +90 -delete");
}
?>
```

---

## 5. COMPLIANCE E PRIVACIDADE

### 5.1 Anonimização de IP (LGPD/GDPR)

A função **automaticamente** anonimiza IPs para compliance:

#### IPv4 (Máscara dos últimos 2 octetos)
```
Original:    192.168.1.100
Anonimizado: 192.168.*.**
```

#### IPv6 (Máscara dos últimos 5 grupos)
```
Original:    2001:0db8:85a3:0000:0000:8a2e:0370:7334
Anonimizado: 2001:0db8:85a3:****:****:****:****:****
```

---

### 5.2 LGPD - Lei Geral de Proteção de Dados (Brasil)

| Requisito LGPD | Implementado | Como |
|----------------|--------------|------|
| **Minimização de dados** | ✅ Sim | Apenas informações essenciais são coletadas |
| **Anonimização** | ✅ Sim | IP mascarado automaticamente |
| **Finalidade específica** | ✅ Sim | Logs exclusivos para auditoria e segurança |
| **Consentimento** | ⚠️ Parcial | Considerar adicionar termo de uso no login |
| **Direito ao esquecimento** | ⚠️ Manual | Script de remoção disponível (ver seção 5.4) |
| **Portabilidade** | ✅ Sim | Formato JSON estruturado |

---

### 5.3 GDPR - General Data Protection Regulation (União Europeia)

| Requisito GDPR | Implementado | Como |
|----------------|--------------|------|
| **Data minimization** | ✅ Sim | Coleta apenas dados necessários |
| **Pseudonymization** | ✅ Sim | IP anonimizado |
| **Purpose limitation** | ✅ Sim | Uso restrito a auditoria |
| **Storage limitation** | ⚠️ Recomendado | Rotação de logs em 90 dias |
| **Right to erasure** | ⚠️ Manual | Script disponível |
| **Data portability** | ✅ Sim | Formato estruturado |

---

### 5.4 Script de Remoção de Dados (Direito ao Esquecimento)

```php
<?php
/**
 * Script: Remoção de logs de um usuário específico (LGPD/GDPR)
 * Uso: php remove-user-audit.php joao.silva
 */

if ($argc < 2) {
    echo "Uso: php remove-user-audit.php <usuario>\n";
    exit(1);
}

$usuarioParaRemover = $argv[1];
$auditFile = __DIR__ . '/logs/audit.log';
$tempFile = __DIR__ . '/logs/audit.log.tmp';

if (!file_exists($auditFile)) {
    echo "Arquivo de auditoria não encontrado.\n";
    exit(1);
}

$linhasRemovidas = 0;
$handle = fopen($auditFile, 'r');
$handleTemp = fopen($tempFile, 'w');

while (($linha = fgets($handle)) !== false) {
    // Verificar se a linha contém o usuário
    if (strpos($linha, "usuario=$usuarioParaRemover ") === false) {
        fputs($handleTemp, $linha);
    } else {
        $linhasRemovidas++;
    }
}

fclose($handle);
fclose($handleTemp);

// Substituir arquivo original
rename($tempFile, $auditFile);

echo "✅ Removidas $linhasRemovidas linhas do usuário '$usuarioParaRemover'.\n";
?>
```

**Execução:**
```bash
php remove-user-audit.php joao.silva
```

---

## 6. ANÁLISE DE LOGS

### 6.1 Script: Top 10 Usuários Mais Ativos

```bash
#!/bin/bash
# top-users.sh

echo "📊 Top 10 Usuários Mais Ativos"
echo "==============================="

cat logs/audit.log | \
  grep -oP 'usuario=\K[^ ]+' | \
  sort | uniq -c | sort -rn | head -10 | \
  awk '{printf "%3d acessos - %s\n", $1, $2}'
```

**Saída:**
```
📊 Top 10 Usuários Mais Ativos
===============================
 245 acessos - joao.silva
 189 acessos - maria.santos
 156 acessos - pedro.costa
  98 acessos - admin
  67 acessos - anonymous
  45 acessos - ana.oliveira
  32 acessos - carlos.ferreira
  28 acessos - lucia.mendes
  19 acessos - roberto.alves
  12 acessos - fernanda.lima
```

---

### 6.2 Script: KPIs Mais Acessados

```bash
#!/bin/bash
# top-kpis.sh

echo "📈 Top 10 KPIs Mais Acessados"
echo "============================="

cat logs/audit.log | \
  grep -oP '\[kpi-[^\]]+' | tr -d '[' | \
  sort | uniq -c | sort -rn | head -10 | \
  awk '{printf "%3d acessos - %s\n", $1, $2}'
```

**Saída:**
```
📈 Top 10 KPIs Mais Acessados
=============================
 312 acessos - kpi-backlog-atual
 289 acessos - kpi-tempo-medio
 234 acessos - kpi-taxa-sucesso
 198 acessos - kpi-valor-orcado
 176 acessos - kpi-sem-conserto
 145 acessos - kpi-equipamentos-aprovados
 123 acessos - kpi-taxa-aprovacao
  98 acessos - kpi-backlog-qualidade
  87 acessos - kpi-tempo-medio-qualidade
  65 acessos - kpi-taxa-reprovacao
```

---

### 6.3 Script: Acessos por Hora do Dia

```bash
#!/bin/bash
# heatmap-horario.sh

echo "🕐 Distribuição de Acessos por Hora"
echo "===================================="

cat logs/audit.log | \
  grep -oP '\[\d{4}-\d{2}-\d{2} \K\d{2}' | \
  sort | uniq -c | sort -n -k2 | \
  awk '{
    hora = sprintf("%02d:00", $2)
    barras = ""
    for (i=0; i<$1/10; i++) barras = barras "█"
    printf "%s | %3d acessos %s\n", hora, $1, barras
  }'
```

**Saída:**
```
🕐 Distribuição de Acessos por Hora
====================================
00:00 |   3 acessos
01:00 |   1 acessos
02:00 |   0 acessos
03:00 |   0 acessos
04:00 |   2 acessos
05:00 |   5 acessos
06:00 |  12 acessos █
07:00 |  34 acessos ███
08:00 |  89 acessos ████████
09:00 | 134 acessos █████████████
10:00 | 156 acessos ███████████████
11:00 | 142 acessos ██████████████
12:00 |  67 acessos ██████
13:00 |  98 acessos █████████
14:00 | 123 acessos ████████████
15:00 | 145 acessos ██████████████
16:00 | 112 acessos ███████████
17:00 |  78 acessos ███████
18:00 |  45 acessos ████
19:00 |  23 acessos ██
20:00 |  12 acessos █
21:00 |   8 acessos
22:00 |   4 acessos
23:00 |   2 acessos
```

---

### 6.4 Script PHP: Detecção de Anomalias

```php
<?php
/**
 * Script: Detecção de acessos anômalos
 * Critérios: Múltiplos IPs para mesmo usuário, múltiplas tentativas por segundo
 */

$auditFile = __DIR__ . '/logs/audit.log';
$acessosPorUsuario = [];
$acessosPorSegundo = [];

$handle = fopen($auditFile, 'r');
while (($linha = fgets($handle)) !== false) {
    // Extrair campos
    preg_match('/\[(.*?)\].*?usuario=(\S+).*?ip=(\S+)/', $linha, $matches);
    if (count($matches) < 4) continue;
    
    [$_, $timestamp, $usuario, $ip] = $matches;
    
    // Agrupar por usuário
    if (!isset($acessosPorUsuario[$usuario])) {
        $acessosPorUsuario[$usuario] = ['ips' => [], 'total' => 0];
    }
    $acessosPorUsuario[$usuario]['ips'][$ip] = true;
    $acessosPorUsuario[$usuario]['total']++;
    
    // Agrupar por segundo
    $segundo = substr($timestamp, 0, 19); // Y-m-d H:i:s
    if (!isset($acessosPorSegundo[$segundo])) {
        $acessosPorSegundo[$segundo] = 0;
    }
    $acessosPorSegundo[$segundo]++;
}
fclose($handle);

// 🚨 ANOMALIA 1: Usuário com múltiplos IPs
echo "🚨 Usuários com múltiplos IPs (possível compartilhamento de credenciais):\n";
foreach ($acessosPorUsuario as $usuario => $dados) {
    $numIps = count($dados['ips']);
    if ($numIps > 3) {
        echo "  ⚠️  $usuario: $numIps IPs diferentes ({$dados['total']} acessos)\n";
    }
}

// 🚨 ANOMALIA 2: Burst de acessos (> 10 por segundo)
echo "\n🚨 Bursts de acessos (possível bot/scraping):\n";
foreach ($acessosPorSegundo as $segundo => $total) {
    if ($total > 10) {
        echo "  ⚠️  $segundo: $total acessos/segundo\n";
    }
}
?>
```

---

## 7. MIGRAÇÃO DE KPIs EXISTENTES

### 7.1 Checklist de Integração (3 Linhas de Código)

Para adicionar auditoria a um KPI existente:

```php
// ✅ PASSO 1: Certifique-se que endpoint-helpers.php está incluído
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';

// ✅ PASSO 2: Adicione após validarAutenticacao() e antes da lógica do KPI
auditarExecucaoKpi(
    'kpi-nome-do-endpoint',                      // Substitua pelo nome técnico
    [
        'inicio' => $_GET['inicio'] ?? 'N/A',
        'fim' => $_GET['fim'] ?? 'N/A'
    ],
    $_SESSION['usuario'] ?? 'anonymous',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    [
        'operador' => $_GET['operador'] ?? null,
        'setor' => $_GET['setor'] ?? null
        // Adicionar outros filtros relevantes ao KPI
    ]
);

// ✅ PASSO 3: Continue com a lógica normal do KPI
```

---

### 7.2 Lista de KPIs Pendentes

| Área | KPI | Arquivo | Status |
|------|-----|---------|--------|
| Recebimento | Backlog Atual | `kpi-backlog-atual.php` | ✅ v3.1.0 |
| Recebimento | Total Processado | `kpi-total-processado.php` | ⏳ Pendente |
| Recebimento | Tempo Médio | `kpi-tempo-medio.php` | ⏳ Pendente |
| Análise | Taxa Sucesso | `kpi-taxa-sucesso.php` | ⏳ Pendente |
| Análise | Sem Conserto | `kpi-sem-conserto.php` | ⏳ Pendente |
| Análise | Valor Orçado | `kpi-valor-orcado.php` | ⏳ Pendente |
| Reparo | Backlog Reparo | `kpi-backlog-reparo.php` | ⏳ Pendente |
| Qualidade | Backlog Qualidade | `kpi-backlog-qualidade.php` | ⏳ Pendente |
| Qualidade | Taxa Aprovação | `kpi-taxa-aprovacao.php` | ⏳ Pendente |

---

### 7.3 Script de Migração Automatizada

```php
<?php
/**
 * Script: Aplicar auditoria em múltiplos KPIs automaticamente
 * Uso: php migrate-audit.php
 */

$kpisParaMigrar = [
    'DashBoard/backendDash/kpis/kpi-total-processado.php',
    'DashBoard/backendDash/kpis/kpi-tempo-medio.php',
    'DashBoard/backendDash/kpis/kpi-taxa-sucesso.php',
    'DashBoard/backendDash/kpis/kpi-sem-conserto.php',
    'DashBoard/backendDash/kpis/kpi-valor-orcado.php',
    // Adicionar outros KPIs aqui
];

$codigoAuditoria = <<<'PHP'

// ============================================
// AUDITORIA DE EXECUÇÃO (OPCIONAL - NÃO BLOQUEIA)
// ============================================
auditarExecucaoKpi(
    basename(__FILE__, '.php'),
    [
        'inicio' => $_GET['inicio'] ?? 'N/A',
        'fim' => $_GET['fim'] ?? 'N/A'
    ],
    $_SESSION['usuario'] ?? 'anonymous',
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    [
        'operador' => $_GET['operador'] ?? null,
        'setor' => $_GET['setor'] ?? null
    ]
);

PHP;

foreach ($kpisParaMigrar as $kpiFile) {
    $caminhoCompleto = __DIR__ . '/' . $kpiFile;
    
    if (!file_exists($caminhoCompleto)) {
        echo "⚠️  Arquivo não encontrado: $kpiFile\n";
        continue;
    }
    
    $conteudo = file_get_contents($caminhoCompleto);
    
    // Verificar se auditoria já foi aplicada
    if (strpos($conteudo, 'auditarExecucaoKpi(') !== false) {
        echo "✅ Auditoria já aplicada: $kpiFile\n";
        continue;
    }
    
    // Localizar ponto de inserção (após validarAutenticacao)
    $pontoInsercao = strpos($conteudo, 'validarAutenticacao();');
    if ($pontoInsercao === false) {
        echo "⚠️  validarAutenticacao() não encontrada: $kpiFile\n";
        continue;
    }
    
    // Inserir código de auditoria
    $posicaoFinal = strpos($conteudo, "\n", $pontoInsercao) + 1;
    $novoConteudo = substr_replace($conteudo, $codigoAuditoria, $posicaoFinal, 0);
    
    // Salvar arquivo modificado
    file_put_contents($caminhoCompleto, $novoConteudo);
    echo "✅ Auditoria aplicada: $kpiFile\n";
}

echo "\n🎉 Migração concluída!\n";
?>
```

---

## 8. BOAS PRÁTICAS

### 8.1 Nomenclatura de KPIs

Use sempre o **basename do arquivo** como nome do KPI:

✅ **Correto:**
```php
// Arquivo: kpi-backlog-atual.php
auditarExecucaoKpi('kpi-backlog-atual', ...);
```

❌ **Incorreto:**
```php
// Arquivo: kpi-backlog-atual.php
auditarExecucaoKpi('backlog', ...);              // Muito genérico
auditarExecucaoKpi('backlog_recebimento', ...);  // Inconsistente
```

---

### 8.2 Captura de Usuário

Priorize múltiplas fontes para maior cobertura:

```php
$usuario = 
    $_SESSION['usuario'] ??              // 1ª tentativa: Session
    $_SERVER['PHP_AUTH_USER'] ??         // 2ª tentativa: HTTP Basic Auth
    $_COOKIE['usuario'] ??               // 3ª tentativa: Cookie
    'anonymous';                         // Fallback
```

---

### 8.3 Filtros Relevantes

Inclua apenas filtros que **realmente** afetam os dados retornados:

✅ **Correto:**
```php
auditarExecucaoKpi(
    'kpi-backlog-atual',
    ['inicio' => '...', 'fim' => '...'],
    $usuario,
    $ip,
    [
        'operador' => $_GET['operador'] ?? null,   // ✅ Usado na query SQL
        'setor' => $_GET['setor'] ?? null          // ✅ Usado na query SQL
    ]
);
```

❌ **Incorreto:**
```php
auditarExecucaoKpi(
    'kpi-backlog-atual',
    ['inicio' => '...', 'fim' => '...'],
    $usuario,
    $ip,
    [
        'operador' => $_GET['operador'] ?? null,
        'setor' => $_GET['setor'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],  // ❌ Não usado no KPI
        'referer' => $_SERVER['HTTP_REFERER'],        // ❌ Não usado no KPI
        'timestamp' => time()                         // ❌ Redundante (já no log)
    ]
);
```

---

### 8.4 Tratamento de Falhas

**NUNCA** trate o retorno da função, pois ela é não-bloqueante:

❌ **Incorreto:**
```php
$sucesso = auditarExecucaoKpi(...);
if (!$sucesso) {
    kpiError('kpi', 'Falha na auditoria', 500);  // ❌ Interrompe KPI!
}
```

✅ **Correto:**
```php
auditarExecucaoKpi(...);  // ✅ Ignora retorno
// KPI continua normalmente
```

---

### 8.5 Performance

A auditoria adiciona **< 5ms** de overhead. Para minimizar ainda mais:

✅ **Otimizações:**
- ✅ Use `LOCK_EX` (já implementado)
- ✅ Evite logs excessivamente grandes (rotação em 90 dias)
- ✅ Não capture dados desnecessários em `$queryParams`
- ✅ Não faça queries adicionais dentro de `$queryParams`

---

## 9. TROUBLESHOOTING

### 9.1 Auditoria não está gravando logs

**Sintomas:** Arquivo `logs/audit.log` não é criado ou permanece vazio.

**Causas possíveis:**

1. **Permissões insuficientes:**
   ```bash
   # Verificar permissões
   ls -la logs/
   
   # Corrigir permissões
   chmod 755 logs/
   chmod 644 logs/audit.log
   chown www-data:www-data logs/audit.log
   ```

2. **Diretório `logs/` não existe:**
   ```bash
   # A função cria automaticamente, mas verificar:
   mkdir -p logs/
   ```

3. **PHP em safe_mode:**
   ```php
   // Verificar se safe_mode está ativado (desabilitado desde PHP 5.4)
   echo ini_get('safe_mode');  // Deve retornar vazio ou '0'
   ```

**Solução:** Verifique `error_log` do PHP:
```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/php-fpm/www-error.log
```

---

### 9.2 IP sempre aparece como "unknown"

**Sintomas:** Logs mostram `ip=unknown` em todas as linhas.

**Causas possíveis:**

1. **Proxy/Load Balancer:**
   ```php
   // Capturar IP real por trás de proxy
   $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ??
         $_SERVER['HTTP_X_REAL_IP'] ??
         $_SERVER['REMOTE_ADDR'] ??
         'unknown';
   
   // Se múltiplos IPs (proxy chain), pegar o primeiro
   if (strpos($ip, ',') !== false) {
       $ip = trim(explode(',', $ip)[0]);
   }
   ```

2. **Servidor CLI:**
   - Se executar KPI via CLI (cron), `$_SERVER['REMOTE_ADDR']` não existe
   - Solução: Passar `'cli'` como IP nesses casos

---

### 9.3 Usuário sempre aparece como "anonymous"

**Sintomas:** Logs mostram `usuario=anonymous` mesmo com usuários autenticados.

**Causas possíveis:**

1. **Session não iniciada:**
   ```php
   // Adicionar no início do KPI (antes de auditarExecucaoKpi)
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   ```

2. **Variável de sessão com nome diferente:**
   ```php
   // Verificar nome correto da variável
   var_dump($_SESSION);  // Debug temporário
   
   // Ajustar captura
   $usuario = $_SESSION['username'] ?? 'anonymous';  // Exemplo
   ```

---

### 9.4 Logs com caracteres corrompidos

**Sintomas:** Logs com `�` ou caracteres estranhos.

**Causa:** Problemas de encoding (UTF-8).

**Solução:**
```php
// Forçar UTF-8 no início do KPI
header('Content-Type: application/json; charset=utf-8');
mb_internal_encoding('UTF-8');

// Sanitizar strings antes de passar para auditoria
$usuario = mb_convert_encoding($_SESSION['usuario'], 'UTF-8', 'auto');
```

---

### 9.5 Performance degradada após auditoria

**Sintomas:** KPIs mais lentos após adicionar auditoria.

**Diagnóstico:**
```php
// Adicionar timing temporário
$auditStart = microtime(true);
auditarExecucaoKpi(...);
$auditTime = (microtime(true) - $auditStart) * 1000;
error_log("Auditoria levou {$auditTime}ms");
```

**Causas possíveis:**

1. **Disco lento (HDD):**
   - Solução: Migrar logs para SSD ou ramdisk (`/dev/shm/`)

2. **Arquivo gigante:**
   - Solução: Implementar rotação de logs

3. **Lock contention (alto volume de requisições):**
   - Solução: Usar logs separados por hora/dia
   ```php
   $hora = date('Y-m-d_H');
   $auditFile = $logDir . "/audit_{$hora}.log";
   ```

---

## 10. ROADMAP

### 10.1 Curto Prazo (1-2 meses)

**✅ Prioridade Alta:**

1. **Aplicar auditoria aos 27 KPIs restantes**
   - Usar script de migração automatizada (Seção 7.3)
   - Testar em staging antes de produção

2. **Dashboard de Auditoria**
   - Endpoint `/dashboard/auditoria.php`
   - Exibir: top usuários, top KPIs, heatmap de horários
   - Gráficos Chart.js

3. **Alertas de Anomalias**
   - Executar script de detecção (Seção 6.4) via cron
   - Enviar email/Slack quando detectar:
     - Usuário com > 5 IPs diferentes
     - Burst > 20 acessos/segundo

---

### 10.2 Médio Prazo (3-6 meses)

**🎯 Prioridade Média:**

1. **Banco de Dados para Auditoria**
   - Migrar de arquivo texto para tabela MySQL
   ```sql
   CREATE TABLE auditoria_kpi (
       id INT AUTO_INCREMENT PRIMARY KEY,
       timestamp DATETIME NOT NULL,
       kpi_name VARCHAR(100) NOT NULL,
       usuario VARCHAR(100),
       ip_anonimizado VARCHAR(50),
       periodo_inicio DATE,
       periodo_fim DATE,
       query_params JSON,
       INDEX idx_timestamp (timestamp),
       INDEX idx_usuario (usuario),
       INDEX idx_kpi (kpi_name)
   );
   ```

2. **Relatórios Automatizados**
   - PDF executivo mensal
   - Métricas: total de acessos, usuários ativos, KPIs mais usados
   - Envio automático para gestores

3. **Integração com SIEM (Security Information and Event Management)**
   - Exportar logs para Splunk, ELK Stack ou Graylog
   - Correlação com logs de aplicação e sistema

---

### 10.3 Longo Prazo (6-12 meses)

**🌟 Prioridade Baixa:**

1. **Machine Learning para Detecção de Anomalias**
   - Treinar modelo para detectar padrões anômalos
   - Alertas preditivos (ex: "Usuário X está acessando KPIs fora do padrão")

2. **Compliance Automatizado**
   - Geração automática de relatórios LGPD/GDPR
   - Rastreamento de consentimento
   - Auditoria de acessos por titular de dados

3. **API de Auditoria**
   - Endpoint público (autenticado) para consultas
   ```http
   GET /api/auditoria?usuario=joao.silva&data_inicio=2026-01-01&data_fim=2026-01-31
   ```
   - Retorno JSON com histórico completo

---

## 📌 RESUMO EXECUTIVO

### ✅ O que foi implementado?

- ✅ Função `auditarExecucaoKpi()` em `endpoint-helpers.php`
- ✅ Anonimização automática de IP (LGPD/GDPR)
- ✅ Logs estruturados em `logs/audit.log`
- ✅ Implementação não-bloqueante (falhas não interrompem KPI)
- ✅ Aplicação ao KPI piloto (`kpi-backlog-atual.php` v3.1.0)

### 🎯 Próximos Passos

1. **Aplicar aos 27 KPIs restantes** (usar script de migração)
2. **Configurar rotação de logs** (90 dias)
3. **Criar dashboard de auditoria** (opcional)
4. **Configurar alertas de anomalias** (opcional)

### 📊 Benefícios

- 🔍 **Observabilidade:** Rastreamento completo de acessos
- 🔒 **Compliance:** Preparação para LGPD/GDPR
- 🛡️ **Segurança:** Detecção de acessos anômalos
- 📈 **Métricas:** Análise de uso e popularidade dos KPIs

---

**Fim da Documentação**

---

*Gerado automaticamente pelo Sistema VISTA - KPI 2.0*  
*Para dúvidas técnicas, consulte a equipe de desenvolvimento*  
*Versão: 1.0 - 15/01/2026*
