<?php
/**
 * Sistema de Roteamento PHP
 * VISTA – Front Controller estável e previsível
 */

class Router
{
    private array $routes = [];
    private $notFoundCallback = null;

    /**
     * Registra uma rota
     * @param string $pattern
     * @param string|callable $target
     */
    public function add(string $pattern, $target): void
    {
        $this->routes[$this->normalize($pattern)] = $target;
    }

    /**
     * Define callback para 404
     */
    public function notFound(callable $callback): void
    {
        $this->notFoundCallback = $callback;
    }

    /**
     * Executa o roteamento
     */
    public function dispatch(): void
    {
        $uri = $this->getUri();

        // 1. Rota exata
        if (isset($this->routes[$uri])) {
            $this->resolve($this->routes[$uri]);
            return;
        }

        // 2. Rotas por regex
        foreach ($this->routes as $pattern => $target) {
            if (@preg_match('#^' . $pattern . '$#', $uri, $matches)) {
                array_shift($matches);
                $this->resolve($target, $matches);
                return;
            }
        }

        // 3. 404
        $this->handleNotFound();
    }

    /**
     * Resolve o destino da rota
     */
    private function resolve($target, array $params = []): void
    {
        // Callback (redirect, lógica)
        if (is_callable($target)) {
            call_user_func_array($target, $params);
            exit;
        }

        // Arquivo PHP
        $this->loadFile($target, $params);
        exit;
    }

    /**
     * Carrega arquivo PHP
     */
    private function loadFile(string $file, array $params = []): void
    {
        $fullPath = __DIR__ . '/' . ltrim($file, '/');

        if (!file_exists($fullPath)) {
            http_response_code(500);
            echo "<h1>Erro interno</h1>";
            echo "<p>Arquivo não encontrado:</p>";
            echo "<pre>{$fullPath}</pre>";
            exit;
        }

        extract($params, EXTR_SKIP);
        require $fullPath;
    }

    /**
     * Trata página não encontrada
     */
    private function handleNotFound(): void
    {
        http_response_code(404);

        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
            exit;
        }

        echo "<h1>404 - Página não encontrada</h1>";
        exit;
    }

    /**
     * Normaliza padrões de rota
     */
    private function normalize(string $path): string
    {
        return $path === '/' ? '/' : '/' . trim($path, '/');
    }

    /**
     * Obtém a URI requisitada
     */
    private function getUri(): string
    {
        if (isset($_GET['url'])) {
            return '/' . trim($_GET['url'], '/');
        }

        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $uri = rtrim($uri, '/');

        return $uri === '' ? '/' : $uri;
    }
}

/**
 * Cria e configura o roteador
 */
function createRouter(): Router
{
    $router = new Router();

    // =====================================================
    // AUTENTICAÇÃO
    // =====================================================
    $router->add('/', 'FrontEnd/tela_login.php');
    $router->add('/login', 'FrontEnd/tela_login.php');
    $router->add('/cadastro', 'FrontEnd/CadastroUsuario.php');
    $router->add('/recuperar-senha', 'FrontEnd/RecuperarSenha.php');
    $router->add('/nova-senha', 'FrontEnd/NovaSenha.php');
    $router->add('/confirmar-cadastro', 'FrontEnd/confirmar_cadastro.php');
    $router->add('/logout', 'BackEnd/logout.php');

    // =====================================================
    // DASHBOARD
    // =====================================================
    $router->add('/dashboard', 'FrontEnd/html/PaginaPrincipal.php');
    $router->add('/home', 'FrontEnd/html/PaginaPrincipal.php');

    // =====================================================
    // MÓDULOS
    // =====================================================
    $router->add('/recebimento', 'FrontEnd/html/recebimento.php');
    $router->add('/analise', 'FrontEnd/html/analise.php');
    $router->add('/reparo', 'FrontEnd/html/reparo.php');
    $router->add('/qualidade', 'FrontEnd/html/qualidade.php');
    $router->add('/expedicao', 'FrontEnd/html/expedicao.php');
    $router->add('/consulta', 'FrontEnd/html/consulta.php');
    $router->add('/consulta/id', 'FrontEnd/html/consulta_id.php');

    // =====================================================
    // CADASTROS
    // =====================================================
    $router->add('/cadastrar-cliente', 'FrontEnd/html/cadastrar_cliente.php');
    $router->add('/cadastro-entrada', 'FrontEnd/html/cadastro_excel_entrada.php');
    $router->add('/cadastro-pos-analise', 'FrontEnd/html/cadastro_excel_pos_analise.php');
    // Rota alternativa compatível com links/JS antigos
    $router->add('/cadastro-excel-pos-analise', 'FrontEnd/html/cadastro_excel_pos_analise.php');
    $router->add('/cadastro-realizado', 'FrontEnd/html/cadastro_realizado.php');

    // =====================================================
    // INVENTÁRIO FÍSICO
    // Rota pública para a nova view do inventário (frontend somente).
    $router->add('/inventario', 'FrontEnd/html/inventario.php');
    // Compatibilidade com links antigos que usavam inventario.php diretamente
    $router->add('/inventario.php', 'FrontEnd/html/inventario.php');
    // Rota móvel amigável (redirigida por router_public quando UA móvel)
    $router->add('/inventario_mobile', 'FrontEnd/html/inventario_mobile.php');
    // Backend API mínimo para suportar listagem (frontend)
    $router->add('/inventario-api', 'BackEnd/Inventario/InventarioApi.php');
    // Para reimplementar o módulo completo (backend) restaure BackEnd/Inventario.
    // =====================================================
    // Reparo - expor salvamento de apontamentos pós-análise
    $router->add('/BackEnd/Reparo/salvar_dados_no_banco_2.php', 'BackEnd/Reparo/salvar_dados_no_banco_2.php');
    // Alias público mais legível para JS
    $router->add('/reparo/salvar-apontamentos-pos-analise', 'BackEnd/Reparo/salvar_dados_no_banco_2.php');
    // Análise - expor salvamento de dados enviados pelo Excel
    $router->add('/BackEnd/Analise/salvar_dados_no_banco.php', 'BackEnd/Analise/salvar_dados_no_banco.php');
    // Alias público para JS
    $router->add('/analise/salvar-dados', 'BackEnd/Analise/salvar_dados_no_banco.php');

    // Temporary diagnostic: reveal env paths (no secrets)
    // Disabled: exposing environment/debug settings publicly is a security risk.
    // To re-enable, protect with admin-only auth or remove secrets from output.
    // $router->add('/reveal/env', 'BackEnd/reveal_env.php');

    // Expose lightweight diagnostic endpoints (temporary)
    $router->add('/BackEnd/db_config_check.php', 'BackEnd/db_config_check.php');
    $router->add('/BackEnd/db_connection_test.php', 'BackEnd/db_connection_test.php');
    // Friendly aliases
    $router->add('/db_config_check', 'BackEnd/db_config_check.php');
    $router->add('/db_connection_test', 'BackEnd/db_connection_test.php');

    // Temporary DB diagnostic endpoint (safe, JSON)
    $router->add('/diagnostico/db', 'BackEnd/diagnostico_db.php');

    // =====================================================
    // REDIRECIONAMENTOS LEGADOS (com exit)
    // =====================================================
    $router->add('/FrontEnd/html/analise.php', function () {
        header('Location: /router_public.php?url=analise', true, 301);
        exit;
    });

    $router->add('/FrontEnd/html/recebimento.php', function () {
        header('Location: /router_public.php?url=recebimento', true, 301);
        exit;
    });

    $router->add('/FrontEnd/html/reparo.php', function () {
        header('Location: /router_public.php?url=reparo', true, 301);
        exit;
    });

    $router->add('/FrontEnd/html/qualidade.php', function () {
        header('Location: /router_public.php?url=qualidade', true, 301);
        exit;
    });

    $router->add('/FrontEnd/html/expedicao.php', function () {
        header('Location: /router_public.php?url=expedicao', true, 301);
        exit;
    });

    $router->add('/FrontEnd/html/PaginaPrincipal.php', function () {
        header('Location: /router_public.php?url=dashboard', true, 301);
        exit;
    });

    // =====================================================
    // PÁGINA 404 (ÚNICA DEFINIÇÃO)
    // =====================================================
    // Rota de debug mínima para verificar se o front controller está ativo
    $router->add('/_debug', function () {
        header('Content-Type: text/plain; charset=utf-8');
        echo "ROUTER OK";
        exit;
    });

    $router->notFound(function () {
        http_response_code(404);
        require __DIR__ . '/FrontEnd/html/404.php';
        exit;
    });

    return $router;
}
