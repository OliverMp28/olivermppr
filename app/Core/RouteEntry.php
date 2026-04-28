<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Handle devuelto por Router::get/post/put/patch/delete para permitir
 * encadenar middleware específico de la ruta:
 *
 *   $router->post('/levels', 'LevelController@store')
 *          ->middleware('AuthMiddleware')
 *          ->middleware('CsrfMiddleware');
 */
final class RouteEntry
{
    public function __construct(
        private readonly Router $router,
        private readonly int $index,
    ) {
    }

    public function middleware(callable|string $middleware): self
    {
        $this->router->appendMiddlewareTo($this->index, $middleware);
        return $this;
    }
}
