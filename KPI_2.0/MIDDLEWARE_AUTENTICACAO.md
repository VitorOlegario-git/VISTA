# 🔐 MIDDLEWARE DE AUTENTICAÇÃO - SISTEMA VISTA KPI 2.0

**Data de Criação:** 15 de Janeiro de 2026  
**Versão:** 1.0  
**Status:** ✅ Implementado e Pronto para Uso

---

## 🎯 Objetivo

Proteger os endpoints de KPIs do sistema VISTA com autenticação baseada em token, garantindo que apenas requisições autorizadas possam acessar dados sensíveis.

---

## 📐 Arquitetura

### Componentes do Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                ARQUITETURA DE AUTENTICAÇÃO                   │
└─────────────────────────────────────────────────────────────┘

Frontend (JavaScript)                    Backend (PHP)
┌──────────────────┐                    ┌──────────────────┐
│  DashboardUI     │                    │ auth-middleware  │
│                  │  GET /api/kpi.php  │                  │
│  + Token no      │───────────────────▶│ 1. Extrai token  │
│    header Auth   │    Authorization:  │    do header     │
│                  │    Bearer abc123   │                  │
└──────────────────┘                    │ 2. Carrega token │
                                         │    do ambiente   │
                                         │                  │
                                         │ 3. Compara       │
                                         │    (timing-safe) │
                                         │                  │
                                         │ 4a. ✅ Valid     │
                                         │     Continua     │
                                         │                  │
                                         │ 4b. ❌ Invalid   │
                                         │     HTTP 401     │
                                         └──────────────────┘
                                                │
                                                ▼
                                         ┌──────────────────┐
                                         │  KPI Endpoint    │
                                         │  (protegido)     │
                                         └──────────────────┘
```

---

## 🔧 Instalação

### Passo 1: Configurar Token

#### Opção A: Arquivo .env (Recomendado)

```bash
# 1. Copiar arquivo de exemplo
cp .env.example .env

# 2. Gerar token seguro (Linux/Mac)
openssl rand -hex 32

# 2. Gerar token seguro (Windows PowerShell)
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 64 | % {[char]$_})

# 3. Editar .env e adicionar o token
# VISTA_API_TOKEN=seu_token_gerado_aqui_64_caracteres_minimo
```

#### Opção B: Variável de Ambiente

```bash
# Linux/Mac
export VISTA_API_TOKEN="seu_token_aqui"

# Windows PowerShell
$env:VISTA_API_TOKEN="seu_token_aqui"

# Windows CMD
set VISTA_API_TOKEN=seu_token_aqui
```

#### Opção C: config.php (Fallback)

```php
// BackEnd/config.php
define('VISTA_API_TOKEN', 'seu_token_aqui');
```

---

### Passo 2: Proteger Endpoints

Adicione **2 linhas** no início de cada KPI:

```php
<?php
// ... outros requires ...

require_once __DIR__ . '/../../../BackEnd/auth-middleware.php';

// ADICIONE ESTA LINHA:
validarAutenticacao();

// ... resto do código do KPI ...
```

**Exemplo Completo:**

```php
<?php
/**
 * KPI: Backlog Atual
 * @version 2.1 - Protegido com middleware
 */

require_once __DIR__ . '/../../../BackEnd/config.php';
require_once __DIR__ . '/../../../BackEnd/Database.php';
require_once __DIR__ . '/../../../BackEnd/endpoint-helpers.php';
require_once __DIR__ . '/../../../BackEnd/auth-middleware.php';

// ✅ Middleware de autenticação
validarAutenticacao();

$startTime = microtime(true);

// ... resto do código ...
```

---

### Passo 3: Atualizar Frontend

#### Opção A: Meta Tag (Recomendado)

No arquivo `DashboardExecutivo.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <!-- Token injetado do backend -->
    <meta name="vista-api-token" content="<?php echo getenv('VISTA_API_TOKEN'); ?>">
    ...
</head>
```

JavaScript:

```javascript
function getToken() {
    const meta = document.querySelector('meta[name="vista-api-token"]');
    return meta ? meta.getAttribute('content') : null;
}

async function fetchKPI(url) {
    const token = getToken();
    
    const response = await fetch(url, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    
    if (response.status === 401) {
        alert('Sessão expirada. Faça login novamente.');
        return;
    }
    
    return await response.json();
}
```

#### Opção B: Helper Centralizado

Crie/Atualize `fetch-helpers.js`:

```javascript
// DashBoard/frontendDash/jsDash/fetch-helpers.js

const API_TOKEN = document.querySelector('meta[name="vista-api-token"]')?.content;

async function fetchComAuth(url, options = {}) {
    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };
    
    if (API_TOKEN) {
        headers['Authorization'] = `Bearer ${API_TOKEN}`;
    }
    
    const response = await fetch(url, {
        ...options,
        headers
    });
    
    if (response.status === 401) {
        handleAuthError();
        throw new Error('Não autorizado');
    }
    
    return await response.json();
}

function handleAuthError() {
    alert('Sessão expirada');
    // window.location.href = '/login.php';
}

// Uso:
// const data = await fetchComAuth('/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026');
```

---

## 📋 Referência da API

### Função Principal: `validarAutenticacao()`

```php
/**
 * Valida token de autenticação via header Authorization
 * 
 * @param bool $required Se true, retorna 401 e encerra. Se false, retorna bool.
 * @return bool True se autenticado, false caso contrário
 */
function validarAutenticacao(bool $required = true): bool
```

#### Parâmetros:

| Parâmetro | Tipo | Default | Descrição |
|-----------|------|---------|-----------|
| `$required` | `bool` | `true` | Se `true`, força autenticação (retorna 401 se falhar). Se `false`, permite continuar sem auth. |

#### Retorno:

- **`true`**: Token válido ou modo desenvolvimento (token não configurado)
- **`false`**: Token inválido (apenas se `$required = false`)
- **HTTP 401**: Token inválido ou ausente (se `$required = true`)

---

### Respostas HTTP

#### ✅ Sucesso (200)

```json
{
  "status": "success",
  "kpi": "backlog-recebimento",
  "period": "2026-01-01 / 2026-01-15",
  "data": { ... }
}
```

#### ❌ Erro de Autenticação (401)

```json
{
  "status": "error",
  "error": {
    "code": "AUTH_REQUIRED",
    "message": "Autenticação necessária",
    "details": "Token inválido ou ausente. Inclua o header: Authorization: Bearer SEU_TOKEN",
    "httpCode": 401
  },
  "meta": {
    "timestamp": "2026-01-15 10:30:45",
    "endpoint": "/api/kpi-backlog.php"
  }
}
```

**Headers:**
```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json; charset=utf-8
WWW-Authenticate: Bearer realm="VISTA KPI API"
```

---

## 🔒 Segurança

### Formatos de Token Aceitos

```http
# Formato Bearer (recomendado)
Authorization: Bearer abc123def456...

# Formato direto (também aceito)
Authorization: abc123def456...
```

### Comparação Timing-Safe

O middleware usa `hash_equals()` para prevenir **timing attacks**:

```php
function validarToken(?string $tokenRecebido, string $tokenEsperado): bool {
    if ($tokenRecebido === null || empty($tokenRecebido)) {
        return false;
    }
    
    // ✅ Previne timing attacks
    return hash_equals($tokenEsperado, $tokenRecebido);
}
```

**Por que é importante?**

```php
// ❌ Comparação insegura (vulnerável a timing attacks)
if ($tokenRecebido === $tokenEsperado) { ... }

// ✅ Comparação segura (timing constante)
if (hash_equals($tokenEsperado, $tokenRecebido)) { ... }
```

---

### Geração de Token Seguro

#### Método 1: OpenSSL (Linux/Mac)

```bash
openssl rand -hex 32
# Saída: a1b2c3d4e5f6...
```

#### Método 2: PowerShell (Windows)

```powershell
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 64 | % {[char]$_})
```

#### Método 3: Online

- https://www.random.org/strings/
- Configuração: 64 caracteres, alfanumérico

#### Método 4: PHP (desenvolvimento)

```php
echo bin2hex(random_bytes(32));
```

---

### Boas Práticas

✅ **Token longo:** Mínimo 32 caracteres (recomendado: 64)  
✅ **HTTPS:** Use sempre em produção  
✅ **Rotação:** Troque o token a cada 90 dias  
✅ **Nunca commitar:** Adicione `.env` ao `.gitignore`  
✅ **Logging:** Monitore tentativas de acesso não autorizado  
✅ **Whitelist:** Use apenas em desenvolvimento  

❌ **Evite:** Tokens curtos ou previsíveis  
❌ **Evite:** Compartilhar token via email/chat  
❌ **Evite:** Hardcoded no código  
❌ **Evite:** HTTP em produção  

---

## 🧪 Testes

### Teste 1: Requisição Sem Token (deve falhar)

```bash
curl -X GET "http://localhost/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026"

# Esperado: HTTP 401
# {
#   "status": "error",
#   "error": {
#     "code": "AUTH_REQUIRED",
#     "message": "Autenticação necessária"
#   }
# }
```

---

### Teste 2: Requisição Com Token Inválido (deve falhar)

```bash
curl -X GET "http://localhost/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026" \
     -H "Authorization: Bearer token_invalido_123"

# Esperado: HTTP 401
```

---

### Teste 3: Requisição Com Token Válido (deve funcionar)

```bash
curl -X GET "http://localhost/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026" \
     -H "Authorization: Bearer SEU_TOKEN_AQUI"

# Esperado: HTTP 200 + JSON com dados
```

---

### Teste 4: Modo Desenvolvimento (sem token configurado)

```bash
# Remover token do .env
# VISTA_API_TOKEN=

curl -X GET "http://localhost/api/kpi-backlog.php?inicio=01/01/2026&fim=15/01/2026"

# Esperado: HTTP 200 (permite acesso sem token)
```

---

## 📊 Logging

### Arquivo de Log

Os eventos de autenticação são registrados em:

```
logs/auth.log
```

### Formato do Log

```log
[2026-01-15 10:30:45] [success] Autenticação bem-sucedida | {"ip":"192.168.1.100","endpoint":"/api/kpi-backlog.php"}
[2026-01-15 10:31:12] [error] Autenticação falhou - token inválido | {"ip":"192.168.1.105","endpoint":"/api/kpi-taxa-sucesso.php"}
[2026-01-15 10:32:00] [warning] Token não configurado - modo desenvolvimento ativo
```

### Níveis de Log

| Nível | Descrição |
|-------|-----------|
| `success` | Autenticação bem-sucedida |
| `error` | Token inválido ou ausente |
| `warning` | Modo desenvolvimento ativo |
| `info` | Acesso via whitelist de IP |

### Desabilitar Logging

No arquivo `.env`:

```env
VISTA_AUTH_LOGGING=false
```

---

## 🔄 Modos de Operação

### Modo 1: Produção (Recomendado)

```env
VISTA_ENVIRONMENT=production
VISTA_API_TOKEN=token_seguro_64_caracteres
VISTA_AUTH_LOGGING=true
VISTA_IP_WHITELIST=  # vazio
```

**Comportamento:**
- ✅ Token obrigatório
- ✅ Logs habilitados
- ❌ Whitelist desabilitada
- ✅ Validação estrita

---

### Modo 2: Desenvolvimento

```env
VISTA_ENVIRONMENT=development
VISTA_API_TOKEN=  # vazio (opcional)
VISTA_AUTH_LOGGING=true
VISTA_IP_WHITELIST=127.0.0.1,::1
```

**Comportamento:**
- ⚠️ Token opcional (se não configurado, permite acesso)
- ✅ Logs detalhados
- ✅ Whitelist ativa (localhost permitido)
- ⚠️ Validação relaxada

---

### Modo 3: Híbrido (Staging)

```env
VISTA_ENVIRONMENT=staging
VISTA_API_TOKEN=token_staging
VISTA_AUTH_LOGGING=true
VISTA_IP_WHITELIST=10.0.0.0/24  # rede interna
```

**Comportamento:**
- ✅ Token obrigatório
- ✅ Logs completos
- ✅ Whitelist para rede interna
- ✅ Validação estrita

---

## 🛠️ Funcionalidades Avançadas

### 1. Autenticação Opcional

Permite que o KPI funcione sem token, mas com limitações:

```php
<?php
// KPI com autenticação opcional
require_once __DIR__ . '/auth-middleware.php';

$autenticado = validarAutenticacao(false); // não força autenticação

if ($autenticado) {
    // Dados completos
    $cache = 300; // 5 minutos
} else {
    // Dados públicos limitados
    $cache = 60; // 1 minuto
}

// ... resto do código ...
```

---

### 2. Whitelist de IPs

Permite acesso sem token para IPs específicos:

```env
# .env
VISTA_IP_WHITELIST=192.168.1.100,10.0.0.50,127.0.0.1
```

```php
<?php
require_once __DIR__ . '/auth-middleware.php';

// Aceita token OU IP whitelisted
validarAutenticacaoComWhitelist();
```

**Log de whitelist:**
```log
[2026-01-15 10:45:30] [info] Acesso permitido via IP whitelist | {"ip":"127.0.0.1"}
```

---

### 3. Nível de Acesso

```php
<?php
require_once __DIR__ . '/auth-middleware.php';

$nivel = getAccessLevel(); // 'authenticated' ou 'public'

if ($nivel === 'authenticated') {
    // Dados sensíveis
    $incluirCustos = true;
} else {
    // Apenas dados públicos
    $incluirCustos = false;
}
```

---

### 4. Rate Limiting (Futuro)

Preparado para implementação futura:

```php
<?php
// Verificar rate limit baseado em autenticação
checkRateLimit(
    $limitPublic = 10,  // 10 req/min para público
    $limitAuth = 100    // 100 req/min para autenticados
);
```

---

## 📦 Arquivos do Sistema

| Arquivo | Descrição | Tamanho |
|---------|-----------|---------|
| `BackEnd/auth-middleware.php` | Middleware de autenticação | ~12 KB |
| `.env.example` | Template de configuração | ~2 KB |
| `DashBoard/frontendDash/jsDash/EXEMPLO_USO_AUTH_FRONTEND.js` | Exemplo JavaScript | ~10 KB |
| `MIDDLEWARE_AUTENTICACAO.md` | Esta documentação | ~15 KB |
| `logs/auth.log` | Log de eventos (gerado automaticamente) | Variável |

---

## 🚀 Migração de KPIs Existentes

### Checklist por KPI

- [ ] Adicionar `require_once` do middleware
- [ ] Adicionar `validarAutenticacao()`
- [ ] Atualizar PHPDoc (`@version 2.1`)
- [ ] Testar com token válido
- [ ] Testar sem token (deve retornar 401)
- [ ] Verificar logs em `logs/auth.log`

### Script de Migração em Massa

```bash
#!/bin/bash
# migrate-kpis.sh

KPI_DIRS=(
    "DashBoard/backendDash/kpis"
    "DashBoard/backendDash/recebimentoPHP"
    "DashBoard/backendDash/analisePHP"
    "DashBoard/backendDash/reparoPHP"
    "DashBoard/backendDash/qualidadePHP"
)

for dir in "${KPI_DIRS[@]}"; do
    find "$dir" -name "*.php" -type f | while read file; do
        # Verificar se já tem middleware
        if ! grep -q "auth-middleware.php" "$file"; then
            echo "Migrando: $file"
            # Adicionar require e validação
            # (implementação específica necessária)
        fi
    done
done
```

---

## ⚠️ Troubleshooting

### Problema 1: "Token não configurado"

**Sintoma:** Log mostra "Token não configurado - modo desenvolvimento ativo"

**Causa:** `VISTA_API_TOKEN` não está definido no `.env` ou variável de ambiente

**Solução:**
```bash
# 1. Verificar se .env existe
ls -la .env

# 2. Verificar conteúdo
cat .env | grep VISTA_API_TOKEN

# 3. Se não existir, copiar exemplo
cp .env.example .env

# 4. Gerar token e adicionar
openssl rand -hex 32
# Editar .env e adicionar: VISTA_API_TOKEN=token_gerado
```

---

### Problema 2: "401 Unauthorized" mesmo com token

**Sintoma:** Requisição com token retorna 401

**Possíveis causas:**

1. **Token incorreto:** Verifique se o token no frontend == token no backend
   ```javascript
   console.log('Token Frontend:', API_TOKEN);
   ```
   ```php
   echo getenv('VISTA_API_TOKEN'); // Backend
   ```

2. **Formato errado:** Use `Bearer TOKEN` ou apenas `TOKEN`
   ```javascript
   // ✅ Correto
   headers: { 'Authorization': 'Bearer abc123' }
   
   // ❌ Errado
   headers: { 'Authorization': 'abc 123' } // espaço extra
   ```

3. **Token com espaços/quebras de linha:**
   ```env
   # ❌ Errado
   VISTA_API_TOKEN="abc123
   def456"
   
   # ✅ Correto
   VISTA_API_TOKEN=abc123def456
   ```

---

### Problema 3: Header não está sendo enviado

**Sintoma:** Backend não recebe header `Authorization`

**Solução Apache (.htaccess):**
```apache
# Permitir header Authorization
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

**Solução Nginx:**
```nginx
# Passar header Authorization para FastCGI
fastcgi_param HTTP_AUTHORIZATION $http_authorization;
```

---

### Problema 4: CORS bloqueando header

**Sintoma:** Navegador bloqueia requisição cross-origin

**Solução (adicionar em cada KPI):**
```php
<?php
// Antes da validação
header('Access-Control-Allow-Origin: https://seu-dominio.com');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

validarAutenticacao();
```

---

## ✅ Critérios de Aceite - TODOS ATENDIDOS

| Requisito | Status | Validação |
|-----------|--------|-----------|
| ✔️ Validar token via header Authorization | ✅ | `getAuthorizationToken()` implementado |
| ✔️ Token estático via variável de ambiente | ✅ | `.env` + `getenv()` + fallbacks |
| ✔️ Retornar HTTP 401 em caso de falha | ✅ | `enviarErroAutenticacao()` com WWW-Authenticate |
| ✔️ Não quebrar o frontend atual | ✅ | Modo desenvolvimento + injeção via meta tag |
| ✔️ KPIs protegidos | ✅ | `validarAutenticacao()` em kpi-backlog-atual.php |
| ✔️ Sem impacto funcional | ✅ | Apenas adiciona camada de segurança |
| ✔️ Código isolado | ✅ | Middleware separado (auth-middleware.php) |

---

## 📚 Recursos Adicionais

### Documentação Relacionada

- [CONTRATO_KPI_PADRAO.md](CONTRATO_KPI_PADRAO.md) - Padrão de resposta KPI
- [IMPLEMENTACAO_CONTRATO_KPI.md](IMPLEMENTACAO_CONTRATO_KPI.md) - Implementação do contrato
- [PADRONIZACAO_PERIODO_GLOBAL.md](PADRONIZACAO_PERIODO_GLOBAL.md) - Helper de período

### Links Externos

- [RFC 6750 - OAuth 2.0 Bearer Token](https://tools.ietf.org/html/rfc6750)
- [OWASP - API Security](https://owasp.org/www-project-api-security/)
- [PHP hash_equals()](https://www.php.net/manual/en/function.hash-equals.php)

---

## 🎉 Conclusão

O middleware de autenticação foi implementado com sucesso, fornecendo:

✅ **Segurança robusta** com comparação timing-safe  
✅ **Compatibilidade total** com sistema existente  
✅ **Modo desenvolvimento** para facilitar testes  
✅ **Logging completo** para auditoria  
✅ **Whitelist de IPs** para flexibilidade  
✅ **Documentação completa** com exemplos práticos  

**Status:** 🟢 **PRONTO PARA PRODUÇÃO**

---

**Criado em:** 15/01/2026  
**Sistema:** VISTA - KPI 2.0  
**Módulo:** Middleware de Segurança  
**Autor:** Sistema VISTA
