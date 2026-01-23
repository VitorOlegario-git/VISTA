<?php
declare(strict_types=1);

namespace BackEnd\Core\Middleware;

use BackEnd\Core\ApiController;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(ApiController $api, callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['username']) && !isset($_SESSION['usuario_id'])) {
            // Use ApiController method to return 401 JSON
            $api->unauthorized();
        }

        // Continue
        $next();
    }
}
