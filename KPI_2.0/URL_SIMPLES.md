# 🔗 URLs Amigáveis - MODO SIMPLES (Sem Acesso ao Servidor)

## ✅ Solução que Funciona SEM Configuração do Servidor

Esta solução usa apenas PHP e um `.htaccess` mínimo que funciona em qualquer hospedagem.

---

## 📋 Como Funciona

### **Arquitetura**
```
Requisição → .htaccess → router_public.php → router.php → Página Final
```

O sistema redireciona tudo para `router_public.php` que decide qual página carregar.

---

## 🚀 Instalação Rápida

### **Opção 1: Servidor COM mod_rewrite (Recomendado)**

1. **Renomeie os arquivos:**
```powershell
# No terminal PowerShell
cd Z:\KPI_2.0
Move-Item .htaccess .htaccess_backup
Move-Item .htaccess_simples .htaccess
```

2. **Pronto!** Agora você pode usar:
```
/login
/dashboard
/analise
```

### **Opção 2: Servidor SEM mod_rewrite (Alternativa)**

Se o mod_rewrite não funcionar, use URLs com `router_public.php`:

```
/router_public.php?url=login
/router_public.php?url=dashboard
/router_public.php?url=analise
```

Para ativar este modo, edite [router.php](router.php):

```php
// Encontre esta linha (aprox. linha 16):
$uri = $_SERVER['REQUEST_URI'];

// Adicione estas linhas LOGO DEPOIS:
if (isset($_GET['url'])) {
    $uri = '/' . trim($_GET['url'], '/');
}
```

---

## 📝 URLs Disponíveis

### **Autenticação**
- `/login` → Login
- `/cadastro` → Cadastro de usuário
- `/recuperar-senha` → Recuperação de senha
- `/nova-senha` → Definir nova senha
- `/confirmar-cadastro` → Confirmação de e-mail
- `/logout` → Sair do sistema

### **Páginas Principais**
- `/dashboard` ou `/home` → Página principal
- `/analise` → Análise
- `/recebimento` → Recebimento
- `/reparo` → Reparo
- `/qualidade` → Qualidade
- `/expedicao` → Expedição
- `/consulta` → Consulta

### **Redirecionamentos Automáticos**
URLs antigas redirecionam automaticamente:
- `/FrontEnd/tela_login.php` → `/login`
- `/FrontEnd/html/PaginaPrincipal.php` → `/dashboard`
- E assim por diante...

---

## 🧪 Testando

### **Teste 1: Verifica se mod_rewrite está funcionando**
```
Acesse: /login
```
- ✅ **Funciona?** Mod_rewrite OK!
- ❌ **Erro 404?** Use a Opção 2

### **Teste 2: Verifica redirecionamentos**
```
Acesse: /FrontEnd/tela_login.php
```
Deve redirecionar automaticamente para `/login`

### **Teste 3: Página 404 personalizada**
```
Acesse: /pagina-inexistente
```
Deve mostrar uma página 404 bonita

---

## 🔧 Adicionando Novas Rotas

Edite [router.php](router.php) e adicione na seção de rotas:

```php
// Adicione após as rotas existentes (aprox. linha 50)
$router->add('/minha-pagina', 'FrontEnd/html/minha_pagina.php');
```

**Exemplo com parâmetros:**
```php
// Rota com ID: /produto/123
$router->add('/produto/(\d+)', function($matches) {
    $id = $matches[1];
    require "FrontEnd/html/produto.php";
});
```

---

## 🎯 Atualizando Links no Código

### **Em PHP:**
```php
// Antes
header("Location: FrontEnd/html/PaginaPrincipal.php");

// Depois
header("Location: /dashboard");
```

### **Em HTML:**
```html
<!-- Antes -->
<a href="FrontEnd/tela_login.php">Login</a>

<!-- Depois -->
<a href="/login">Login</a>
```

### **Em JavaScript:**
```javascript
// Antes
window.location.href = '/FrontEnd/html/PaginaPrincipal.php';

// Depois
window.location.href = '/dashboard';
```

---

## 🛡️ Segurança

O sistema já inclui:
- ✅ Bloqueia acesso ao `.env`
- ✅ Bloqueia arquivos `.md`, `.log`, `.sql`
- ✅ Desabilita listagem de diretórios
- ✅ Página 404 personalizada e segura

---

## ⚙️ Customização do Router

### **Adicionar Middleware (Autenticação)**

Edite [router.php](router.php):

```php
// Adicione esta função antes de createRouter()
function verificarAutenticacao() {
    session_start();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login');
        exit;
    }
}

// Depois use nas rotas protegidas:
$router->add('/dashboard', function() {
    verificarAutenticacao();
    require 'FrontEnd/html/PaginaPrincipal.php';
});
```

### **Adicionar Logs de Acesso**

```php
// Adicione no início do método dispatch() (linha 23)
file_put_contents(
    __DIR__ . '/logs/access.log',
    date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_URI'] . "\n",
    FILE_APPEND
);
```

---

## ⚠️ Troubleshooting

### **Problema: URLs não funcionam, erro 404**

**Solução 1:** Verifique se o arquivo `.htaccess` está na raiz
```powershell
Test-Path Z:\KPI_2.0\.htaccess
```

**Solução 2:** Use o modo alternativo com `?url=`
```
/router_public.php?url=login
```

### **Problema: CSS/JS não carregam**

Certifique-se que os caminhos dos assets usam `/` absoluto:
```html
<!-- ✅ Correto -->
<link rel="stylesheet" href="/FrontEnd/CSS/style.css">

<!-- ❌ Errado -->
<link rel="stylesheet" href="FrontEnd/CSS/style.css">
```

### **Problema: Página em branco**

Verifique se o arquivo PHP existe:
```php
// Adicione debug temporário no router.php
echo "URI: " . $uri . "<br>";
echo "File: " . $file . "<br>";
```

---

## 📊 Vantagens desta Solução

| Característica | Sem Router | Com Router PHP |
|---------------|------------|----------------|
| Configuração do servidor | ❌ Necessária | ✅ Não necessária |
| URLs amigáveis | ❌ Não | ✅ Sim |
| Funciona em qualquer hospedagem | ⚠️ Depende | ✅ Sim |
| Redirecionamentos | ❌ Manual | ✅ Automático |
| Página 404 customizada | ❌ Não | ✅ Sim |
| Fácil adicionar rotas | ❌ Difícil | ✅ Fácil |

---

## 🔄 Rollback (Voltar ao Normal)

Se quiser desativar:

```powershell
# Restaura .htaccess original
Move-Item .htaccess .htaccess_router
Move-Item .htaccess_backup .htaccess

# Remove arquivos do router
Remove-Item router.php
Remove-Item router_public.php
```

---

## 📞 Próximos Passos

1. ✅ Teste as URLs no navegador
2. ✅ Atualize links antigos no código
3. ✅ Adicione novas rotas conforme necessário
4. ✅ Monitore o arquivo `logs/access.log`

---

**Vantagem Principal:** Esta solução funciona **100% sem acesso ao servidor**, apenas com PHP padrão! 🎉

**Última Atualização:** 12 de Janeiro de 2026  
**Status:** ✅ Pronto para uso
