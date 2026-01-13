# 🔧 Guia de Troubleshooting - Erro HTTP 500

## ❌ Problema Identificado
Erro HTTP 500 ao acessar a tela de login após as atualizações.

## ✅ Correção Aplicada
**Problema:** O arquivo `tela_login.php` não estava incluindo o `conexao.php`, causando erro ao tentar usar `$conn`.

**Solução:** Adicionado `require_once __DIR__ . '/../BackEnd/conexao.php';` no início do arquivo.

---

## 🔍 Como Diagnosticar Erros 500

### **Passo 1: Verificar Logs do PHP**

**No Windows (XAMPP/WAMP):**
```
C:\xampp\apache\logs\error.log
C:\wamp64\logs\php_error.log
```

**No Linux:**
```
/var/log/apache2/error.log
/var/log/php/error.log
```

**No Sistema:**
```
Z:\KPI_2.0\logs\php_errors.log
```

### **Passo 2: Ativar Exibição de Erros Temporariamente**

Edite `.env` temporariamente:
```env
APP_ENV=development
APP_DEBUG=true
```

Depois de resolver, volte para:
```env
APP_ENV=production
APP_DEBUG=false
```

### **Passo 3: Usar Script de Teste**

Acesse via navegador:
```
http://kpi.stbextrema.com.br/test_config.php
```

Este script testa:
- ✅ Arquivo .env existe
- ✅ Config.php carrega
- ✅ Helpers.php carrega
- ✅ Conexão com banco
- ✅ Classes funcionam
- ✅ Permissões de logs

**⚠️ IMPORTANTE:** Remova `test_config.php` após o teste!

---

## 🐛 Erros Comuns e Soluções

### **Erro 1: "Arquivo .env não encontrado"**
```
Sintoma: Página em branco ou erro 500
Causa: Arquivo .env não existe
```

**Solução:**
```bash
cd Z:\KPI_2.0
cp .env.example .env
# Edite .env com credenciais corretas
```

### **Erro 2: "Call to undefined function..."**
```
Sintoma: Fatal error: Call to undefined function url()
Causa: helpers.php não foi carregado
```

**Solução:**
Adicione no início do arquivo:
```php
require_once __DIR__ . '/../BackEnd/helpers.php';
```

### **Erro 3: "Undefined variable: $conn"**
```
Sintoma: Notice/Warning sobre variável não definida
Causa: conexao.php não foi incluído
```

**Solução:**
Adicione após helpers.php:
```php
require_once __DIR__ . '/../BackEnd/conexao.php';
```

### **Erro 4: "Failed to open stream"**
```
Sintoma: Warning: require_once(...): failed to open stream
Causa: Caminho do arquivo incorreto
```

**Solução:**
Verifique se está usando `__DIR__` corretamente:
```php
// Correto
require_once __DIR__ . '/../BackEnd/config.php';

// Errado
require_once '../BackEnd/config.php';
```

### **Erro 5: "mysqli::prepare(): Couldn't fetch mysqli"**
```
Sintoma: Erro ao preparar statement
Causa: Conexão com banco perdida
```

**Solução:**
Verifique credenciais no .env:
```env
DB_HOST=localhost
DB_USERNAME=kpi
DB_PASSWORD=kpi456
DB_NAME=vista
```

### **Erro 6: "headers already sent"**
```
Sintoma: Warning: Cannot modify header information
Causa: Espaços ou output antes de header()
```

**Solução:**
- Remova espaços/linhas em branco antes de `<?php`
- Não use `echo` antes de `header()`
- Use `ob_start()` no início do arquivo se necessário

### **Erro 7: Permissões de diretório**
```
Sintoma: Erro ao escrever logs
Causa: Diretório logs/ sem permissão
```

**Solução (Linux):**
```bash
chmod 755 Z:\KPI_2.0\logs
chmod 644 Z:\KPI_2.0\logs\*.log
```

**Solução (Windows):**
- Clique direito em `logs/` > Propriedades > Segurança
- Dê permissões de escrita ao usuário do servidor web

---

## 🔄 Ordem Correta de Carregamento

### **Para Páginas de Frontend:**
```php
<?php
// 1. Sempre primeiro
require_once __DIR__ . '/../BackEnd/helpers.php';

// 2. Se precisar de banco de dados
require_once __DIR__ . '/../BackEnd/conexao.php';

// 3. Se precisar de validações
require_once __DIR__ . '/../BackEnd/Validator.php';

// 4. Se precisar de email
require_once __DIR__ . '/../BackEnd/EmailService.php';

// 5. Verificar autenticação (se necessário)
verificarSessao();
?>
```

### **Para APIs Backend:**
```php
<?php
// 1. Helpers primeiro
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/helpers.php';

// 2. Headers
header('Content-Type: application/json');
definirHeadersSeguranca();

// 3. Verificar autenticação
if (!verificarSessao(false)) {
    jsonError('Não autenticado', 401);
}

// 4. CSRF se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
}

// 5. Conexão e outras classes
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';
?>
```

---

## 📋 Checklist de Verificação

Antes de acessar a aplicação, verifique:

- [ ] Arquivo `.env` existe e está configurado
- [ ] Credenciais do banco no `.env` estão corretas
- [ ] Diretório `logs/` existe
- [ ] Diretório `logs/` tem permissão de escrita
- [ ] Servidor web (Apache/Nginx) está rodando
- [ ] PHP está instalado e funcionando
- [ ] MySQL está rodando
- [ ] Banco de dados `vista` existe
- [ ] Tabelas do banco foram criadas (importar `kpi_2_0.sql`)
- [ ] Extensão mysqli do PHP está habilitada

---

## 🧪 Teste Rápido via Linha de Comando

**Testar sintaxe do PHP:**
```bash
# Se PHP estiver no PATH
php -l Z:\KPI_2.0\FrontEnd\tela_login.php
php -l Z:\KPI_2.0\BackEnd\config.php
php -l Z:\KPI_2.0\BackEnd\helpers.php
```

**Testar conexão com MySQL:**
```bash
mysql -u kpi -p vista
# Digite a senha: kpi456
# Se conectar, está OK
```

---

## 🔧 Correções Aplicadas Nesta Sessão

### ✅ `FrontEnd/tela_login.php`
**Problema:** Variável `$conn` não definida
**Correção:** Adicionado `require_once conexao.php`

```php
// ANTES
<?php
require_once __DIR__ . '/../BackEnd/helpers.php';

// DEPOIS
<?php
require_once __DIR__ . '/../BackEnd/helpers.php';
require_once __DIR__ . '/../BackEnd/conexao.php';
```

---

## 📞 Próximos Passos

1. **Acesse o teste:** http://kpi.stbextrema.com.br/test_config.php
2. **Verifique todos os ✅** - Se algum mostrar ❌, corrija antes de prosseguir
3. **Remova test_config.php** após os testes
4. **Acesse o login:** http://kpi.stbextrema.com.br/FrontEnd/tela_login.php
5. **Se funcionar:** Pronto! ✅
6. **Se não funcionar:** Verifique os logs conforme orientado acima

---

## 📝 Logs Úteis

**Habilitar logs detalhados temporariamente:**

Crie `Z:\KPI_2.0\debug.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/debug.log');

require_once __DIR__ . '/BackEnd/helpers.php';
require_once __DIR__ . '/BackEnd/conexao.php';

echo "OK - Arquivos carregados sem erro!";
?>
```

Acesse e veja se aparece "OK".

---

**Última Atualização:** 12 de Janeiro de 2026  
**Status:** ✅ Correção Aplicada - Teste Necessário
