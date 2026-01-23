<?php
declare(strict_types=1);

/**
 * ApiController - base abstract controller for JSON APIs
 * Ensures consistent JSON responses, security headers, auth and a single shutdown handler.
 */
namespace BackEnd\Core;

require_once __DIR__ . '/../helpers.php';

abstract class ApiController
{
    protected $db;
    private static bool $shutdownRegistered = false;
    private bool $responded = false;

    public function __construct()
    {
        // Apply security headers when available
        $this->applySecurityHeaders();

        // Initialize middleware stack
        // Require middleware stack file if present
        $mwDir = __DIR__ . '/Middleware';
        if (is_dir($mwDir)) {
            require_once $mwDir . '/MiddlewareStack.php';
            require_once $mwDir . '/MiddlewareInterface.php';
            // Optional middlewares will be required by user code when adding
            $this->middleware = new \BackEnd\Core\Middleware\MiddlewareStack();
        }

        // Register a single shutdown handler across all controllers
        if (!self::$shutdownRegistered) {
            register_shutdown_function([$this, 'shutdownGuard']);
            self::$shutdownRegistered = true;
        }
    }

    protected \BackEnd\Core\Middleware\MiddlewareStack $middleware;

    // Child classes implement request handling here
    abstract public function handle(): void;

    // Low-level responder
    protected function respond(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        $this->responded = true;
        exit;
    }

    protected function ok(array $payload = []): void
    {
        $data = ['success' => true];
        if (!empty($payload)) {
            $data = array_merge($data, $payload);
        }
        $this->respond(200, $data);
    }

    protected function error(int $status, string $message, array $extra = []): void
    {
        $payload = array_merge(['success' => false, 'error' => $message], $extra);
        $this->respond($status, $payload);
    }

    protected function badRequest(string $message): void
    {
        $this->error(400, $message);
    }

    protected function unauthorized(): void
    {
        $this->error(401, 'Não autenticado');
    }

    protected function forbidden(): void
    {
        $this->error(403, 'Acesso negado');
    }

    protected function notFound(string $message = 'Recurso não encontrado'): void
    {
        $this->error(404, $message);
    }

    protected function serverError(string $message = 'Erro interno'): void
    {
        $this->error(500, $message);
    }

    protected function requireAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['username']) && !isset($_SESSION['usuario_id'])) {
            $this->unauthorized();
        }
    }

    protected function applySecurityHeaders(): void
    {
        if (function_exists('definirHeadersSeguranca')) {
            definirHeadersSeguranca();
        }
    }

    /**
     * Run the provided handler through the middleware stack.
     */
    protected function runWithMiddleware(callable $handler): void
    {
        if (isset($this->middleware) && $this->middleware instanceof \BackEnd\Core\Middleware\MiddlewareStack) {
            $this->middleware->run($this, $handler);
            return;
        }

        // No middleware configured — call directly
        $handler();
    }

    // Shutdown handler that returns structured JSON on fatal errors if possible
    public function shutdownGuard(): void
    {
        // If we already responded, avoid duplicating output
        if ($this->responded) return;

        $err = error_get_last();
        if ($err !== null) {
            // Log details for operators
            error_log('[ApiController] SHUTDOWN: ' . json_encode($err, JSON_UNESCAPED_UNICODE));
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Erro fatal no endpoint'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }
}

