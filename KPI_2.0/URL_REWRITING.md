# 🔗 Guia de URLs Amigáveis

## 📋 Novas URLs do Sistema

### **Autenticação**
| Função | URL Antiga | URL Nova (Amigável) |
|--------|-----------|---------------------|
| Login | `/FrontEnd/tela_login.php` | `/login` |
| Cadastro | `/FrontEnd/CadastroUsuario.php` | `/cadastro` |
| Recuperar Senha | `/FrontEnd/RecuperarSenha.php` | `/recuperar-senha` |
| Nova Senha | `/FrontEnd/NovaSenha.php` | `/nova-senha` |
| Confirmar Cadastro | `/FrontEnd/confirmar_cadastro.php` | `/confirmar-cadastro` |
| Logout | `/BackEnd/logout.php` | `/logout` |

### **Páginas Principais**
| Função | URL Antiga | URL Nova (Amigável) |
|--------|-----------|---------------------|
| Dashboard | `/FrontEnd/html/PaginaPrincipal.php` | `/dashboard` ou `/home` |
| Análise | `/FrontEnd/html/analise.php` | `/analise` |
| Recebimento | `/FrontEnd/html/recebimento.php` | `/recebimento` |
| Reparo | `/FrontEnd/html/reparo.php` | `/reparo` |
| Qualidade | `/FrontEnd/html/qualidade.php` | `/qualidade` |
| Expedição | `/FrontEnd/html/expedicao.php` | `/expedicao` |
| Consulta | `/FrontEnd/html/consulta.php` | `/consulta` |

### **Exemplos de Uso**

**Antes:**
```
https://kpi.stbextrema.com.br/FrontEnd/html/PaginaPrincipal.php
https://kpi.stbextrema.com.br/FrontEnd/tela_login.php
https://kpi.stbextrema.com.br/FrontEnd/html/analise.php
```

**Depois:**
```
https://kpi.stbextrema.com.br/dashboard
https://kpi.stbextrema.com.br/login
https://kpi.stbextrema.com.br/analise
```

---

## 🚀 Como Ativar

### **1. Verificar se mod_rewrite está ativo**

**Windows (XAMPP):**
1. Abra `C:\xampp\apache\conf\httpd.conf`
2. Encontre a linha: `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Remova o `#` no início: `LoadModule rewrite_module modules/mod_rewrite.so`
4. Reinicie o Apache

**Linux:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### **2. Permitir .htaccess no VirtualHost**

Edite o arquivo de configuração do seu site e certifique-se de ter:

```apache
<Directory "/caminho/para/KPI_2.0">
    AllowOverride All
    Require all granted
</Directory>
```

### **3. Reiniciar o servidor**
```bash
# Windows
Reinicie o Apache no painel do XAMPP

# Linux
sudo systemctl restart apache2
```

---

## 🔄 Atualizando Links no Código

### **Em arquivos PHP:**

**Antes:**
```php
header("Location: FrontEnd/html/PaginaPrincipal.php");
<a href="FrontEnd/tela_login.php">Login</a>
```

**Depois:**
```php
header("Location: " . url('dashboard'));
<a href="<?php echo url('login'); ?>">Login</a>
```

### **Em JavaScript:**
```javascript
// Antes
window.location.href = '/FrontEnd/html/PaginaPrincipal.php';

// Depois
window.location.href = '/dashboard';
```

---

## 🛡️ Segurança Adicional

O `.htaccess` também implementa:

### **Bloqueios**
- ✅ Bloqueia acesso ao `.env`
- ✅ Bloqueia arquivos `.md`, `.log`, `.sql`
- ✅ Protege diretório `logs/`
- ✅ Impede acesso direto ao `BackEnd/`
- ✅ Desabilita listagem de diretórios

### **Headers de Segurança**
- ✅ X-Frame-Options (anti-clickjacking)
- ✅ X-Content-Type-Options (anti-MIME sniffing)
- ✅ X-XSS-Protection
- ✅ Referrer-Policy

### **Performance**
- ✅ Compressão GZIP
- ✅ Cache de assets estáticos
- ✅ Otimização de imagens

---

## 🧪 Testando

### **Teste 1: URLs Amigáveis**
```
✅ https://kpi.stbextrema.com.br/login
✅ https://kpi.stbextrema.com.br/dashboard
✅ https://kpi.stbextrema.com.br/analise
```

### **Teste 2: Redirecionamentos Automáticos**
Acesse a URL antiga e veja se redireciona:
```
https://kpi.stbextrema.com.br/FrontEnd/tela_login.php
    ↓ deve redirecionar para ↓
https://kpi.stbextrema.com.br/login
```

### **Teste 3: Bloqueios de Segurança**
Tente acessar (deve dar erro 403/404):
```
❌ https://kpi.stbextrema.com.br/.env
❌ https://kpi.stbextrema.com.br/logs/
❌ https://kpi.stbextrema.com.br/BackEnd/config.php
```

---

## 🔧 Personalizando URLs

Para adicionar novas URLs, edite o `.htaccess`:

```apache
# Adicione antes da linha "# APIs Backend"
RewriteRule ^minha-pagina$ FrontEnd/html/minha_pagina.php [L]
```

**Flags disponíveis:**
- `[L]` - Last rule (para aqui)
- `[R=301]` - Redirect permanente
- `[R=302]` - Redirect temporário
- `[F]` - Forbidden (403)
- `[NC]` - No Case (case-insensitive)

---

## 🎯 Boas Práticas

1. **Use URLs em lowercase:** `/analise` ao invés de `/Analise`
2. **Use hífens:** `/recuperar-senha` ao invés de `/recuperar_senha`
3. **Seja consistente:** Sempre use as mesmas convenções
4. **Mantenha curtas:** `/dashboard` melhor que `/painel-de-controle`
5. **Use helpers:** Sempre use `url()` ao invés de hardcoded URLs

---

## ⚠️ Troubleshooting

### **Erro 500 após adicionar .htaccess**
- Verifique se mod_rewrite está ativo
- Verifique sintaxe do .htaccess
- Veja logs: `apache/logs/error.log`

### **URLs não funcionam (404)**
- Confirme que `AllowOverride All` está configurado
- Reinicie o Apache
- Verifique se o arquivo .htaccess está na raiz do projeto

### **Redirect loop infinito**
- Verifique se não há conflitos entre regras
- Desabilite temporariamente o .htaccess para testar

---

## 📞 Suporte

Se precisar adicionar novas URLs ou customizar o comportamento, consulte a documentação do Apache mod_rewrite:
- https://httpd.apache.org/docs/current/mod/mod_rewrite.html

---

**Última Atualização:** 12 de Janeiro de 2026  
**Status:** ✅ Configurado e Testado
