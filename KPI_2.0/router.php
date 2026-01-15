<?php
/**
 * Sistema de Roteamento PHP
 * Funciona sem precisar de configurações avançadas do Apache
 */

class Router {
    private $routes = [];
    private $notFound = null;
    
    /**
     * Adiciona uma rota
     */
    public function add($pattern, $file) {
        $this->routes[$pattern] = $file;
    }
    
    /**
     * Define página 404
     */
    public function notFound($callback) {
        $this->notFound = $callback;
    }
    
    /**
     * Processa a requisição
     */
    public function dispatch() {
        // Verifica se está usando query string (quando mod_rewrite não funciona)
        if (isset($_GET['url'])) {
            $uri = '/' . trim($_GET['url'], '/');
        } else {
            // Pega a URI requisitada
            $uri = $_SERVER['REQUEST_URI'];
            
            // Remove query string
            $uri = strtok($uri, '?');
            
            // Remove barra final
            $uri = rtrim($uri, '/');
        }
        
        // Se vazio, é a raiz
        if (empty($uri)) {
            $uri = '/';
        }
        
        // Procura rota exata
        if (isset($this->routes[$uri])) {
            return $this->loadPage($this->routes[$uri]);
        }
        
        // Procura rota com regex
        foreach ($this->routes as $pattern => $file) {
            if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                return $this->loadPage($file, $matches);
            }
        }
        
        // Página não encontrada
        if ($this->notFound) {
            return call_user_func($this->notFound);
        }
        
        http_response_code(404);
        echo "<h1>404 - Página não encontrada</h1>";
    }
    
    /**
     * Carrega arquivo PHP
     */
    private function loadPage($file, $params = []) {
        $fullPath = __DIR__ . '/' . $file;
        
        if (!file_exists($fullPath)) {
            http_response_code(500);
            echo "<h1>Erro: Arquivo não encontrado</h1>";
            echo "<p>$fullPath</p>";
            return;
        }
        
        // Torna parâmetros disponíveis para a página
        extract($params);
        
        // Inclui o arquivo
        require $fullPath;
    }
}

/**
 * Cria e configura o roteador
 */
function createRouter() {
    $router = new Router();
    
    // =====================================================
    // ROTAS PRINCIPAIS
    // =====================================================
    
    // Autenticação
    $router->add('/', 'FrontEnd/tela_login.php');
    $router->add('/login', 'FrontEnd/tela_login.php');
    $router->add('/cadastro', 'FrontEnd/CadastroUsuario.php');
    $router->add('/recuperar-senha', 'FrontEnd/RecuperarSenha.php');
    $router->add('/nova-senha', 'FrontEnd/NovaSenha.php');
    $router->add('/confirmar-cadastro', 'FrontEnd/confirmar_cadastro.php');
    $router->add('/logout', 'BackEnd/logout.php');
    
    // Dashboard
    $router->add('/dashboard', 'FrontEnd/html/PaginaPrincipal.php');
    $router->add('/home', 'FrontEnd/html/PaginaPrincipal.php');
    
    // Módulos
    $router->add('/analise', 'FrontEnd/html/analise.php');
    $router->add('/recebimento', 'FrontEnd/html/recebimento.php');
    $router->add('/reparo', 'FrontEnd/html/reparo.php');
    $router->add('/qualidade', 'FrontEnd/html/qualidade.php');
    $router->add('/expedicao', 'FrontEnd/html/expedicao.php');
    $router->add('/consulta', 'FrontEnd/html/consulta.php');
    $router->add('/consulta/id', 'FrontEnd/html/consulta_id.php');
    // Inventário Status
    $router->add('/inventario-status.php', 'FrontEnd/inventario/inventario-status.php');
    
    // Cadastros
    $router->add('/cadastrar-cliente', 'FrontEnd/html/cadastrar_cliente.php');
    $router->add('/cadastro-entrada', 'FrontEnd/html/cadastro_excel_entrada.php');
    $router->add('/cadastro-pos-analise', 'FrontEnd/html/cadastro_excel_pos_analise.php');
    
    // =====================================================
    // ROTAS ANTIGAS (Redirecionamento)
    // =====================================================
    
    $router->add('/FrontEnd/tela_login.php', function() {
        header('Location: /login', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/PaginaPrincipal.php', function() {
        header('Location: /dashboard', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/analise.php', function() {
        header('Location: /analise', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/recebimento.php', function() {
        header('Location: /recebimento', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/reparo.php', function() {
        header('Location: /reparo', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/qualidade.php', function() {
        header('Location: /qualidade', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/expedicao.php', function() {
        header('Location: /expedicao', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/html/consulta.php', function() {
        header('Location: /consulta', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/CadastroUsuario.php', function() {
        header('Location: /cadastro', true, 301);
        exit;
    });
    
    $router->add('/FrontEnd/RecuperarSenha.php', function() {
        header('Location: /recuperar-senha', true, 301);
        exit;
    });
    
    // =====================================================
    // PÁGINA 404
    // =====================================================
    
    $router->notFound(function() {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>404 - Página não encontrada</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }
                .container {
                    text-align: center;
                    padding: 40px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 20px;
                    backdrop-filter: blur(10px);
                    max-width: 500px;
                }
                h1 { font-size: 120px; margin-bottom: 20px; }
                h2 { font-size: 28px; margin-bottom: 15px; }
                p { font-size: 16px; margin-bottom: 30px; opacity: 0.9; }
                a {
                    display: inline-block;
                    padding: 12px 30px;
                    background: white;
                    color: #667eea;
                    text-decoration: none;
                    border-radius: 30px;
                    font-weight: bold;
                    transition: transform 0.3s;
                }
                a:hover { transform: translateY(-2px); }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>Página não encontrada</h2>
                <p>A página que você está procurando não existe ou foi movida.</p>
                <a href="/dashboard">Voltar ao Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    });
    
    return $router;
}
