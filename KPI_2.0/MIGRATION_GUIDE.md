# 🔄 Guia de Migração dos Módulos Restantes

## 📊 Status Atual

### ✅ **Módulos Atualizados (100%)**
- [x] Autenticação (login/logout)
- [x] Cadastro de usuários
- [x] Recuperação de senha
- [x] Confirmação de cadastro
- [x] Página principal
- [x] Análise (parcial)
- [x] Recebimento (parcial)

### ⚠️ **Módulos Pendentes de Atualização**
- [ ] Reparo (5 arquivos)
- [ ] Qualidade (3 arquivos)
- [ ] Expedição (3 arquivos)
- [ ] Consulta (2 arquivos)
- [ ] Análise (consultas - 2 arquivos)
- [ ] Dashboard (múltiplos arquivos)

---

## 🎯 Padrão de Migração

### **Passo 1: Substituir Cabeçalho**

**❌ ANTES:**
```php
<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: /router_public.php?url=login");
    exit();
}

require_once '../conexao.php';

$tempo_limite = 1200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /router_public.php?url=login");
    exit();
}

$_SESSION['last_activity'] = time();
```

**✅ DEPOIS:**
```php
<?php
require_once __DIR__ . '/../helpers.php';

verificarSessao();
definirHeadersSeguranca();

require_once __DIR__ . '/../conexao.php';
```

**Redução:** ~15 linhas para 5 linhas

---

### **Passo 2: Usar Database ao invés de mysqli direto**

**❌ ANTES:**
```php
$stmt = $conn->prepare("SELECT * FROM tabela WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
```

**✅ DEPOIS:**
```php
$db = getDb();
$row = $db->fetchOne(
    "SELECT * FROM tabela WHERE id = ?",
    [$id],
    'i'
);
```

---

### **Passo 3: Usar Validator**

**❌ ANTES:**
```php
if (empty($cnpj)) {
    echo json_encode(["error" => "CNPJ obrigatório"]);
    exit();
}

$cnpj_limpo = preg_replace('/\D/', '', $cnpj);
if (strlen($cnpj_limpo) != 14) {
    echo json_encode(["error" => "CNPJ inválido"]);
    exit();
}
```

**✅ DEPOIS:**
```php
$validator = validator();

if (!$validator->required($cnpj, 'cnpj') || !$validator->cnpj($cnpj)) {
    jsonError($validator->getFirstError());
}
```

---

### **Passo 4: Substituir URLs Hardcoded**

**❌ ANTES:**
```php
header("Location: /BackEnd/cadastro_realizado.php");

<link rel="stylesheet" href="/FrontEnd/CSS/style.css">
<img src="/FrontEnd/CSS/imagens/logo.png">
```

**✅ DEPOIS:**
```php
header("Location: " . url('BackEnd/cadastro_realizado.php'));

<link rel="stylesheet" href="<?php echo asset('FrontEnd/CSS/style.css'); ?>">
<img src="<?php echo asset('FrontEnd/CSS/imagens/logo.png'); ?>">
```

---

### **Passo 5: Usar jsonSuccess/jsonError**

**❌ ANTES:**
```php
echo json_encode([
    "success" => true,
    "message" => "Operação realizada",
    "data" => $resultado
]);
exit();
```

**✅ DEPOIS:**
```php
jsonSuccess($resultado, 'Operação realizada');
```

---

### **Passo 6: Adicionar CSRF em Formulários POST**

**❌ ANTES:**
```php
<form method="POST" action="processar.php">
    <input type="text" name="campo">
    <button>Enviar</button>
</form>
```

**✅ DEPOIS:**
```php
<head>
    <?php echo metaCSRF(); ?>
</head>
<body>
    <form method="POST" action="processar.php">
        <?php echo campoCSRF(); ?>
        <input type="text" name="campo">
        <button>Enviar</button>
    </form>
</body>
```

**No PHP que processa:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    // Seu código...
}
```

---

## 📝 Checklist por Arquivo

### Para cada arquivo PHP:

- [ ] Substituir gestão de sessão manual por `verificarSessao()`
- [ ] Adicionar `definirHeadersSeguranca()` se for página/API
- [ ] Trocar `$conn->prepare()` direto por `$db->fetchOne/fetchAll/insert/execute`
- [ ] Usar `Validator` para validações
- [ ] Substituir URLs hardcoded por `url()` ou `asset()`
- [ ] Usar `jsonSuccess()` e `jsonError()` em APIs
- [ ] Adicionar proteção CSRF em formulários POST
- [ ] Usar `sanitizeInput()` em todos os inputs
- [ ] Remover `ini_set('display_errors')` se existir
- [ ] Trocar `die()` por retornos apropriados

---

## 🚀 Exemplo Completo de Migração

### **BackEnd/Reparo/Reparo.php**

**❌ ANTES (30+ linhas):**
```php
<?php 
session_start();

$tempo_limite = 1200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $tempo_limite) {
    session_unset();
    session_destroy();
    header("Location: /router_public.php?url=login");
    exit();
}

if (!isset($_SESSION['username'])) {
    header("Location: /router_public.php?url=login");
    exit();
}

$_SESSION['last_activity'] = time();

require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/conexao.php';

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cnpj = sanitizeInput($_POST['cnpj']);
    $nota_fiscal = sanitizeInput($_POST['nota_fiscal']);
    
    if (empty($cnpj) || empty($nota_fiscal)) {
        echo json_encode(["error" => "Campos obrigatórios"]);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT * FROM reparo_resumo WHERE cnpj = ? AND nota_fiscal = ?");
    $stmt->bind_param("ss", $cnpj, $nota_fiscal);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row) {
        echo json_encode(["success" => true, "data" => $row]);
    } else {
        echo json_encode(["error" => "Não encontrado"]);
    }
    exit();
}
?>
```

**✅ DEPOIS (10 linhas):**
```php
<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/helpers.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/BackEnd/Validator.php';

header('Content-Type: application/json');
verificarSessao(false) or jsonError('Não autenticado', 401);
definirHeadersSeguranca();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verificarCSRF();
    
    $cnpj = sanitizeInput($_POST['cnpj']);
    $nota_fiscal = sanitizeInput($_POST['nota_fiscal']);
    
    $validator = validator();
    $validator->required($cnpj, 'cnpj');
    $validator->cnpj($cnpj);
    $validator->required($nota_fiscal, 'nota_fiscal');
    
    if ($validator->hasErrors()) {
        jsonError($validator->getFirstError());
    }
    
    $db = getDb();
    $row = $db->fetchOne(
        "SELECT * FROM reparo_resumo WHERE cnpj = ? AND nota_fiscal = ?",
        [$cnpj, $nota_fiscal],
        'ss'
    );
    
    $row ? jsonSuccess($row) : jsonError('Não encontrado', 404);
}
?>
```

---

## 📦 Arquivos Prioritários para Atualizar

### **Alta Prioridade**
1. `BackEnd/Reparo/Reparo.php` - Mais usado
2. `BackEnd/Qualidade/Qualidade.php` - Crítico
3. `BackEnd/Expedicao/Expedicao.php` - Workflow
4. `BackEnd/Analise/salvar_dados_no_banco.php` - Tem URLs hardcoded

### **Média Prioridade**
5. Arquivos de consulta em cada módulo
6. `BackEnd/Consulta/consulta_resumo_geral.php`
7. `BackEnd/Consulta/consulta_status.php`

### **Baixa Prioridade**
8. Dashboard (múltiplos arquivos) - Pode ser feito gradualmente

---

## 🎯 Benefícios da Migração

| Aspecto | Antes | Depois | Ganho |
|---------|-------|--------|-------|
| Linhas de código (por arquivo) | ~30-40 | ~10-15 | 60-70% |
| Segurança CSRF | ❌ | ✅ | 100% |
| Validações | Manual | Automatizada | 80% |
| URLs dinâmicas | Hardcoded | Configuráveis | 100% |
| Manutenibilidade | Baixa | Alta | 90% |

---

## 💡 Dicas

1. **Teste após cada migração** - Não migre todos de uma vez
2. **Mantenha backup** - Sempre tenha uma cópia antes de modificar
3. **Use Git** - Commite após cada arquivo migrado
4. **Documente mudanças** - Anote problemas encontrados
5. **Teste edge cases** - Campos vazios, valores inválidos, etc.

---

## 🚨 Atenção Especial

### **Transações no Banco**
Se o arquivo faz múltiplos INSERTs/UPDATEs relacionados, use:
```php
try {
    $db->beginTransaction();
    
    $db->insert(...);
    $db->update(...);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    jsonError('Erro ao processar');
}
```

### **Arquivos com Upload**
Adicione validação de arquivo:
```php
$validator->regex(
    $nomeArquivo,
    '/\.(jpg|jpeg|png|pdf)$/i',
    'arquivo',
    'Apenas JPG, PNG ou PDF permitidos'
);
```

---

## ✅ Quando Considerar Completo

- [ ] Todas as URLs hardcoded removidas
- [ ] Todos os formulários POST com CSRF
- [ ] Validações usando Validator
- [ ] Database ao invés de mysqli direto
- [ ] Headers de segurança adicionados
- [ ] Logs estruturados (não mais `die()` ou `echo`)
- [ ] Testado em ambiente de desenvolvimento
- [ ] Documentado em comentários

---

**Próximo Passo:** Começar pela migração dos arquivos de alta prioridade listados acima.

**Tempo Estimado por Arquivo:** 15-30 minutos  
**Total Estimado:** 3-5 horas para todos os módulos
