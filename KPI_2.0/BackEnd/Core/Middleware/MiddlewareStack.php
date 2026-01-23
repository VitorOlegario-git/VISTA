<?php
declare(strict_types=1);

namespace BackEnd\Core\Middleware;

use BackEnd\Core\ApiController;

class MiddlewareStack
{
    /** @var MiddlewareInterface[] */
    private array $stack = [];

    public function add(MiddlewareInterface $m): void
    {
        $this->stack[] = $m;
    }

    /**
     * Run the stack and finish at the final handler.
     * @param ApiController $api
     * @param callable $finalHandler
     */
    public function run(ApiController $api, callable $finalHandler): void
    {
        // Build pipeline
        $next = $finalHandler;

        // Wrap in reverse so first added runs first
        foreach (array_reverse($this->stack) as $middleware) {
            $prev = $next;
            $next = function () use ($middleware, $api, $prev) {
                $middleware->handle($api, $prev);
            };
        }

        // Execute pipeline
        $next();
    }
}
