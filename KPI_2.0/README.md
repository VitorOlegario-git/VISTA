# Sistema VISTA - KPI 2.0

Sistema de gestão e controle de processos para Suntech do Brasil.

## 📋 Estrutura do Projeto

```
KPI_2.0/
├── BackEnd/
│   ├── config.php              # Configurações centralizadas
│   ├── conexao.php             # Conexão com banco de dados
│   ├── Database.php            # Classe de gerenciamento de BD
│   ├── helpers.php             # Funções auxiliares e segurança
│   ├── Validator.php           # Validações centralizadas
│   ├── Analise/                # Módulo de Análise
│   ├── Consulta/               # Módulo de Consulta
│   ├── Expedicao/              # Módulo de Expedição
│   ├── Qualidade/              # Módulo de Qualidade
│   ├── Recebimento/            # Módulo de Recebimento
│   └── Reparo/                 # Módulo de Reparo
├── FrontEnd/
│   ├── CSS/                    # Estilos
│   ├── JS/                     # Scripts
│   └── html/                   # Páginas HTML/PHP
├── DashBoard/                  # Dashboard e relatórios
├── PHPMailer/                  # Biblioteca de email
├── logs/                       # Logs da aplicação
├── .env                        # Variáveis de ambiente (NÃO COMMITAR)
├── .env.example                # Template de configuração
└── .gitignore                  # Arquivos ignorados pelo Git

```

## 🚀 Começando

### Pré-requisitos

- PHP 8.0+
- MySQL 5.7+
- Servidor Web (Apache/Nginx)

### Instalação

1. Clone o repositório
2. Copie `.env.example` para `.env`
3. Configure as variáveis no `.env`:
   ```env
   DB_HOST=localhost
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha
   DB_NAME=vista
   ```
4. Importe o banco de dados: `kpi_2_0.sql`
5. Configure as permissões:
   ```bash
   chmod 755 logs/
   chmod 600 .env
   ```

## 🔒 Melhorias de Segurança Implementadas

### ✅ Críticas
- Credenciais em variáveis de ambiente (.env)
- Proteção CSRF em formulários
- Headers de segurança HTTP
- Sessões com regeneração de ID
- Display errors desabilitado em produção
- Logs estruturados

### ✅ Arquitetura
- Classe Database (Singleton)
- Validações centralizadas
- Helpers de segurança
- Funções reutilizáveis

## 📚 Documentação

- [SECURITY_IMPROVEMENTS.md](SECURITY_IMPROVEMENTS.md) - Detalhes das melhorias de segurança
- [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) - Guia completo de desenvolvimento

## 🛠️ Tecnologias

- **Backend:** PHP 8.0
- **Banco de Dados:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript, jQuery
- **Email:** PHPMailer
- **Autenticação:** Sessions com bcrypt

## 📦 Módulos

### Recebimento
Registro de entrada de equipamentos

### Análise
Análise técnica e geração de orçamentos

### Reparo
Gestão de reparos e manutenções

### Qualidade
Controle de qualidade pós-reparo

### Expedição
Gestão de envio de equipamentos

### Consulta
Consultas e relatórios do sistema

### Dashboard
Métricas e KPIs em tempo real

## 👥 Usuários

O sistema possui controle de acesso por usuário com autenticação segura.

### Cadastro
- Email corporativo @suntechdobrasil.com.br
- Confirmação por email
- Senha com hash bcrypt

## 🔐 Segurança

### Proteção Implementada
- ✅ SQL Injection (Prepared Statements)
- ✅ XSS (Sanitização de inputs)
- ✅ CSRF (Tokens em formulários)
- ✅ Session Fixation (Regeneração de ID)
- ✅ Clickjacking (X-Frame-Options)
- ✅ MIME Sniffing (X-Content-Type-Options)

### Recomendações Futuras
- [ ] Rate limiting no login
- [ ] Autenticação de dois fatores (2FA)
- [ ] Política de senhas mais robusta
- [ ] Sistema de permissões/roles
- [ ] HTTPS obrigatório

## 🧪 Como Desenvolver

### Criando Nova Página Protegida

```php
<?php
require_once '../BackEnd/helpers.php';

// Verifica autenticação
verificarSessao();
definirHeadersSeguranca();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Minha Página</title>
    <?php echo metaCSRF(); ?>
</head>
<body>
    <form method="POST">
        <?php echo campoCSRF(); ?>
        <!-- Seus campos aqui -->
    </form>
</body>
</html>
```

### Criando Nova API

```php
<?php
require_once '../BackEnd/helpers.php';

header('Content-Type: application/json');
definirHeadersSeguranca();

if (!verificarSessao(false)) {
    jsonError('Não autenticado', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCSRF();
    
    // Seu código aqui
    
    jsonSuccess($dados, 'Operação realizada!');
}
?>
```

### Usando o Database

```php
<?php
$db = getDb();

// SELECT
$usuario = $db->fetchOne(
    "SELECT * FROM usuarios WHERE id = ?",
    [$id],
    'i'
);

// INSERT
$novoId = $db->insert(
    "INSERT INTO tabela (campo1, campo2) VALUES (?, ?)",
    [$valor1, $valor2],
    'ss'
);

// UPDATE
$linhas = $db->execute(
    "UPDATE tabela SET campo = ? WHERE id = ?",
    [$novoValor, $id],
    'si'
);
?>
```

## 📞 Suporte

Para dúvidas técnicas, consulte a documentação ou entre em contato com a equipe de desenvolvimento.

## � Sistema de URLs Amigáveis

O sistema usa URLs limpas e profissionais sem expor a estrutura de diretórios:

**URLs Atuais:**
```
https://kpi.stbextrema.com.br/router_public.php?url=dashboard
https://kpi.stbextrema.com.br/router_public.php?url=login
https://kpi.stbextrema.com.br/router_public.php?url=analise
```

**Principais Rotas:**
- `?url=login` - Login do sistema
- `?url=dashboard` - Página principal
- `?url=analise` - Módulo de análise
- `?url=recebimento` - Módulo de recebimento
- `?url=reparo` - Módulo de reparo
- `?url=qualidade` - Módulo de qualidade
- `?url=expedicao` - Módulo de expedição
- `?url=consulta` - Módulo de consulta

**Arquivos do Sistema:**
- `router.php` - Sistema de roteamento com classe Router
- `router_public.php` - Front controller público
- `.htaccess` - Configuração Apache (funciona sem mod_rewrite)

**Detalhes:** Consulte [URL_SIMPLES.md](URL_SIMPLES.md) para documentação completa.

---

## 📚 Documentação Adicional

- [Histórico de Alterações](CHANGELOG.md) - Todas as mudanças do sistema
- [Resumo Executivo](EXECUTIVE_SUMMARY.md) - Visão geral das melhorias
- [Melhorias de Segurança](SECURITY_IMPROVEMENTS.md) - Detalhes técnicos de segurança
- [Guia do Desenvolvedor](DEVELOPER_GUIDE.md) - Como usar as novas funcionalidades
- [Guia de Migração](MIGRATION_GUIDE.md) - Como migrar módulos pendentes
- [Troubleshooting](TROUBLESHOOTING.md) - Solução de problemas comuns
- [URLs Amigáveis](URL_SIMPLES.md) - Sistema de roteamento e URLs limpas

## 📝 Licença

Uso interno - Suntech do Brasil

---

**Versão:** 2.1.0  
**Última Atualização:** 12 de Janeiro de 2026  
**Status:** ✅ Segurança + Arquitetura + URL Routing Implementados
