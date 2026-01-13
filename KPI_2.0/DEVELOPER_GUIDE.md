# 🚀 Guia de Melhorias - Sistema VISTA KPI 2.0

## 📚 Índice
1. [Novas Classes Criadas](#novas-classes-criadas)
2. [Como Usar](#como-usar)
3. [Exemplos Práticos](#exemplos-práticos)
4. [Migração de Código Legado](#migração-de-código-legado)

---

## 🎯 Novas Classes Criadas

### 1. **Database.php** - Gerenciamento de Conexões
Classe Singleton para gerenciar conexões com banco de dados de forma eficiente e segura.

**Benefícios:**
- ✅ Conexão única (Singleton pattern)
- ✅ Prepared statements automáticos
- ✅ Tratamento de erros centralizado
- ✅ Suporte a transações
- ✅ Métodos helpers para operações comuns

### 2. **Validator.php** - Validações Centralizadas
Classe para validação de dados com métodos reutilizáveis.

**Validações Disponíveis:**
- Email, CNPJ, CPF
- Números, datas, URLs
- Comprimento de strings
- Regex customizado
- E muito mais!

### 3. **helpers.php** - Funções Utilitárias + CSRF
Funções auxiliares para sessão, sanitização e proteção CSRF.

**Novas Funcionalidades:**
- Proteção CSRF completa
- Geração de tokens seguros
- Helpers para formulários
- Meta tags para AJAX

---

## 🔧 Como Usar

### **1. Usando a Classe Database**

#### Método Tradicional (Compatibilidade)
```php
<?php
require_once 'BackEnd/conexao.php';

// $conn já está disponível (mysqli tradicional)
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
?>
```

#### Método Novo (Recomendado)
```php
<?php
require_once 'BackEnd/conexao.php';

$db = getDb(); // Obtém instância do Database

// SELECT único registro
$usuario = $db->fetchOne(
    "SELECT * FROM usuarios WHERE id = ?",
    [$id],
    'i'
);

// SELECT múltiplos registros
$usuarios = $db->fetchAll(
    "SELECT * FROM usuarios WHERE ativo = ?",
    [1],
    'i'
);

// INSERT
$userId = $db->insert(
    "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)",
    [$nome, $email, $senhaHash],
    'sss'
);

// UPDATE/DELETE
$affected = $db->execute(
    "UPDATE usuarios SET nome = ? WHERE id = ?",
    [$novoNome, $id],
    'si'
);

// Transações
try {
    $db->beginTransaction();
    
    $db->insert("INSERT INTO tabela1 ...", [...]);
    $db->insert("INSERT INTO tabela2 ...", [...]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    error_log($e->getMessage());
}
?>
```

---

### **2. Usando o Validator**

```php
<?php
require_once 'BackEnd/Validator.php';

$validator = validator(); // ou new Validator()

// Validações simples
if (!$validator->required($nome, 'nome')) {
    echo $validator->getFirstError();
}

// Validação de email corporativo
if (!$validator->corporateEmail($email)) {
    echo $validator->getFirstError();
}

// Validação de CNPJ
if (!$validator->cnpj($cnpj)) {
    echo $validator->getFirstError();
}

// Múltiplas validações
$validator->required($senha, 'senha');
$validator->minLength($senha, 6, 'senha');

if ($validator->hasErrors()) {
    $erros = $validator->getErrors(); // Array de erros
    $primeiroErro = $validator->getFirstError();
    $todosErros = $validator->getErrorsAsString('<br>');
}

// Validação customizada
$validator->regex(
    $telefone,
    '/^\(\d{2}\) \d{4,5}-\d{4}$/',
    'telefone',
    'Formato inválido. Use (00) 00000-0000'
);
?>
```

---

### **3. Proteção CSRF**

#### Em Formulários HTML
```php
<?php require_once 'BackEnd/helpers.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <?php echo metaCSRF(); ?>
</head>
<body>
    <form method="POST" action="processar.php">
        <?php echo campoCSRF(); ?>
        
        <input type="text" name="nome">
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
```

#### Validação no Backend
```php
<?php
require_once 'BackEnd/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF(); // Retorna erro 403 se inválido
    
    // Seu código aqui...
}
?>
```

#### Em Requisições AJAX
```javascript
// jQuery
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$.post('/BackEnd/Analise/Analise.php', {
    cnpj: '12345678000100',
    // csrf_token não precisa ser enviado manualmente
}, function(response) {
    console.log(response);
});

// JavaScript Vanilla
fetch('/BackEnd/api.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({data: 'value'})
});
```

---

## 📋 Exemplos Práticos

### **Exemplo 1: Login Seguro**

```php
<?php
require_once 'BackEnd/helpers.php';
require_once 'BackEnd/Validator.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    
    $validator = validator();
    $email = sanitizeInput($_POST['email']);
    $senha = $_POST['senha'];
    
    // Validações
    $validator->required($email, 'email');
    $validator->email($email);
    $validator->required($senha, 'senha');
    
    if ($validator->hasErrors()) {
        jsonError($validator->getFirstError());
    }
    
    // Busca usuário
    $db = getDb();
    $usuario = $db->fetchOne(
        "SELECT id, nome, senha FROM usuarios WHERE email = ?",
        [$email],
        's'
    );
    
    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        jsonError('Email ou senha inválidos');
    }
    
    // Autenticação bem-sucedida
    autenticarUsuario($usuario['id'], $usuario['nome']);
    jsonSuccess(['redirect' => 'dashboard.php']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php echo metaCSRF(); ?>
</head>
<body>
    <form method="POST">
        <?php echo campoCSRF(); ?>
        <input type="email" name="email" required>
        <input type="password" name="senha" required>
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
```

### **Exemplo 2: Cadastro com Validação**

```php
<?php
require_once 'BackEnd/helpers.php';
require_once 'BackEnd/Validator.php';

verificarSessao(); // Apenas usuários autenticados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    
    $validator = validator();
    $cnpj = sanitizeInput($_POST['cnpj']);
    $razaoSocial = sanitizeInput($_POST['razao_social']);
    $quantidade = intval($_POST['quantidade']);
    
    // Validações
    $validator->required($cnpj, 'cnpj');
    $validator->cnpj($cnpj);
    $validator->required($razaoSocial, 'razao_social');
    $validator->positive($quantidade, 'quantidade');
    
    if ($validator->hasErrors()) {
        jsonError($validator->getErrorsAsString());
    }
    
    // Insere no banco
    try {
        $db = getDb();
        $id = $db->insert(
            "INSERT INTO clientes (cnpj, razao_social, quantidade, criado_por) VALUES (?, ?, ?, ?)",
            [$cnpj, $razaoSocial, $quantidade, getUsuarioId()],
            'ssii'
        );
        
        jsonSuccess(['id' => $id], 'Cliente cadastrado com sucesso!');
    } catch (Exception $e) {
        error_log($e->getMessage());
        jsonError('Erro ao cadastrar cliente');
    }
}
?>
```

### **Exemplo 3: API com Transação**

```php
<?php
require_once 'BackEnd/helpers.php';

header('Content-Type: application/json');
definirHeadersSeguranca();

if (!verificarSessao(false)) {
    jsonError('Não autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    
    $cnpj = sanitizeInput($_POST['cnpj']);
    $items = $_POST['items']; // Array de items
    
    $db = getDb();
    
    try {
        $db->beginTransaction();
        
        // Insere cabeçalho
        $pedidoId = $db->insert(
            "INSERT INTO pedidos (cnpj, data_criacao, usuario_id) VALUES (?, NOW(), ?)",
            [$cnpj, getUsuarioId()],
            'si'
        );
        
        // Insere itens
        foreach ($items as $item) {
            $db->insert(
                "INSERT INTO pedido_items (pedido_id, produto, quantidade) VALUES (?, ?, ?)",
                [$pedidoId, $item['produto'], $item['quantidade']],
                'isi'
            );
        }
        
        $db->commit();
        jsonSuccess(['pedido_id' => $pedidoId], 'Pedido criado com sucesso!');
        
    } catch (Exception $e) {
        $db->rollback();
        error_log($e->getMessage());
        jsonError('Erro ao criar pedido');
    }
}
?>
```

---

## 🔄 Migração de Código Legado

### **Antes (Código Antigo)**
```php
<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'conexao.php';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Email inválido");
}
?>
```

### **Depois (Código Novo)**
```php
<?php
require_once 'BackEnd/helpers.php';
require_once 'BackEnd/Validator.php';

verificarSessao();

$db = getDb();
$usuario = $db->fetchOne(
    "SELECT * FROM usuarios WHERE id = ?",
    [$id],
    'i'
);

$validator = validator();
if (!$validator->email($email)) {
    jsonError($validator->getFirstError());
}
?>
```

---

## 📊 Checklist de Migração

Para migrar arquivos existentes:

- [ ] Substituir `session_start()` e verificação manual por `verificarSessao()`
- [ ] Adicionar `verificarCSRF()` em formulários POST
- [ ] Substituir `new mysqli()` por `getDb()` ou `getConnection()`
- [ ] Usar `Validator` ao invés de validações manuais
- [ ] Substituir `echo json_encode()` por `jsonSuccess()` ou `jsonError()`
- [ ] Usar `sanitizeInput()` em todos os inputs
- [ ] Adicionar `definirHeadersSeguranca()` em páginas protegidas
- [ ] Trocar URLs hardcoded por `url()` ou `asset()`

---

## 🎓 Boas Práticas

1. **Sempre use Prepared Statements** - Mesmo com a classe Database
2. **Valide no Backend** - Nunca confie apenas em validação frontend
3. **Use CSRF em todos os formulários** - Proteção essencial
4. **Sanitize inputs** - Use `sanitizeInput()` sempre
5. **Log de erros** - Nunca exiba erros técnicos ao usuário
6. **Transações** - Use para operações com múltiplos INSERTs/UPDATEs
7. **Constantes para URLs** - Facilita migração entre ambientes
8. **Use URLs do router** - Sempre use `router_public.php?url=` para navegação
9. **Assets absolutos** - Use `asset()` helper para imagens/CSS/JS

---

## 🔗 Sistema de URL Routing

### **Como Funciona**

O sistema intercepta todas as requisições e roteia para os arquivos corretos:

```
Requisição → .htaccess → router_public.php → router.php → Página Final
```

### **Usando URLs no Código**

**Redirecionamentos PHP:**
```php
// ❌ Evite URLs diretas
header("Location: FrontEnd/html/PaginaPrincipal.php");

// ✅ Use o router
header("Location: https://kpi.stbextrema.com.br/router_public.php?url=dashboard");

// ✅ Com parâmetros
header("Location: https://kpi.stbextrema.com.br/router_public.php?url=dashboard&reload=" . time());
```

**JavaScript:**
```javascript
// ❌ Evite
window.location.href = '/FrontEnd/html/PaginaPrincipal.php';

// ✅ Use
window.location.href = 'https://kpi.stbextrema.com.br/router_public.php?url=dashboard';

// ✅ Com cache busting
window.location.href = `https://kpi.stbextrema.com.br/router_public.php?url=dashboard&reload=${Date.now()}`;
```

**Imagens e Assets:**
```php
<!-- ❌ Caminhos relativos NÃO funcionam com router -->
<img src="../CSS/imagens/logo.png">
<link rel="stylesheet" href="../CSS/style.css">

<!-- ✅ Use o helper asset() -->
<img src="<?php echo asset('FrontEnd/CSS/imagens/logo.png'); ?>">
<link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/style.css'); ?>">

<!-- ✅ Em background-image inline -->
<div style="background-image:url('<?php echo asset('FrontEnd/CSS/imagens/bg.png'); ?>');">
```

### **Rotas Disponíveis**

| Rota | Arquivo Real | Descrição |
|------|-------------|-----------|
| `?url=login` | `FrontEnd/tela_login.php` | Login do sistema |
| `?url=dashboard` | `FrontEnd/html/PaginaPrincipal.php` | Página principal |
| `?url=analise` | `FrontEnd/html/analise.php` | Módulo análise |
| `?url=recebimento` | `FrontEnd/html/recebimento.php` | Módulo recebimento |
| `?url=reparo` | `FrontEnd/html/reparo.php` | Módulo reparo |
| `?url=qualidade` | `FrontEnd/html/qualidade.php` | Módulo qualidade |
| `?url=expedicao` | `FrontEnd/html/expedicao.php` | Módulo expedição |
| `?url=consulta` | `FrontEnd/html/consulta.php` | Módulo consulta |
| `?url=cadastrar-cliente` | `FrontEnd/html/cadastrar_cliente.php` | Cadastro cliente |
| `?url=cadastro-entrada` | `FrontEnd/html/cadastro_excel_entrada.php` | Cadastro entrada |

### **Adicionando Novas Rotas**

Edite `router.php` na seção de rotas principais (aprox. linha 40):

```php
// Adicione após as rotas existentes
$router->add('/minha-nova-rota', 'FrontEnd/html/minha_pagina.php');

// Com função callback
$router->add('/custom', function() {
    require 'FrontEnd/html/custom.php';
});
```

### **Estrutura dos Arquivos**

```
KPI_2.0/
├── .htaccess                 # Redireciona tudo para router
├── router.php                # Classe Router e configuração de rotas
├── router_public.php         # Front controller público
└── [resto dos arquivos...]
```

### **Troubleshooting de Rotas**

**Problema:** Página em branco
```php
// Verifique se o arquivo existe
$fullPath = __DIR__ . '/FrontEnd/html/minha_pagina.php';
echo file_exists($fullPath) ? 'Existe' : 'Não existe';
```

**Problema:** CSS/JS não carrega
```php
// Sempre use caminhos absolutos com asset()
❌ <script src="../JS/script.js"></script>
✅ <script src="<?php echo asset('FrontEnd/JS/script.js'); ?>"></script>
```

**Problema:** Redirecionamento não funciona
```php
// Certifique-se de usar URL completa
❌ header("Location: /dashboard");
✅ header("Location: https://kpi.stbextrema.com.br/router_public.php?url=dashboard");
```

**Documentação Completa:** Consulte [URL_SIMPLES.md](URL_SIMPLES.md)

---

## 🆘 Troubleshooting

### Erro: "Arquivo .env não encontrado"
**Solução:** Copie `.env.example` para `.env` e configure

### Erro: "Token CSRF inválido"
**Solução:** Adicione `<?php echo campoCSRF(); ?>` no formulário

### Erro: "Usuário não autenticado"
**Solução:** Certifique-se de chamar `verificarSessao()` antes de acessar `$_SESSION`

### Erro de conexão com banco
**Solução:** Verifique credenciais no arquivo `.env`

### Erro: 404 em assets (imagens/CSS)
**Solução:** Use helper `asset()` ao invés de caminhos relativos

### Erro: Redirecionamento não funciona após login
**Solução:** Use URL completa com router: `https://kpi.stbextrema.com.br/router_public.php?url=dashboard`

---

**Última atualização:** 12 de Janeiro de 2026  
**Versão:** 2.0 - Arquitetura Melhorada
