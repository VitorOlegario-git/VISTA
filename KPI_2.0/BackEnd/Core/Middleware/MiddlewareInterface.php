<?php
declare(strict_types=1);

namespace BackEnd\Core\Middleware;

use BackEnd\Core\ApiController;

interface MiddlewareInterface
{
    /**
     * Handle the middleware logic.
     * @param ApiController $api
     * @param callable $next
     */
    public function handle(ApiController $api, callable $next): void;
}
