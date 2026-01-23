<?php
declare(strict_types=1);

namespace BackEnd\Core\Middleware;

use BackEnd\Core\ApiController;

class AuditMiddleware implements MiddlewareInterface
{
    public function handle(ApiController $api, callable $next): void
    {
        // Gather audit info
        $user = $_SESSION['username'] ?? ($_SESSION['usuario_id'] ?? 'anonymous');
        $route = $_GET['url'] ?? ($_SERVER['REQUEST_URI'] ?? '');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $time = gmdate('Y-m-d\TH:i:s\Z');

        $entry = [
            'type' => 'audit',
            'user' => $user,
            'route' => $route,
            'method' => $method,
            'ip' => $ip,
            'time' => $time,
        ];

        error_log(json_encode($entry, JSON_UNESCAPED_UNICODE));

        // Continue without altering response
        $next();
    }
}
