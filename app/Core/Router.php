<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

/**
 * Router minimalista.
 *
 *  - Sintaxis de parámetros: `/users/{id}` (regex `[^/]+`).
 *  - Handlers: callable, o string `"Controller@method"` que resuelve a
 *    `App\Controllers\Controller::method`.
 *  - Middleware: por grupo (`group(['middleware' => [...]], ...)`) y por
 *    ruta (encadenable: `->middleware($mw)`). Sin globales.
 *  - 404 / 405 plain text. La diferenciación HTML/JSON según prefijo se
 *    afina en bloques posteriores.
 *
 * Diseño como instancia (no estático) para simplificar tests; el front
 * controller crea una sola en cada request.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, params: list<string>, handler: mixed, middleware: list<callable|string>}> */
    private array $routes = [];

    /** @var list<array{prefix: string, middleware: list<callable|string>}> */
    private array $groupStack = [];

    public function get(string $path, mixed $handler): RouteEntry
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): RouteEntry
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): RouteEntry
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): RouteEntry
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): RouteEntry
    {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * Agrupa rutas con prefijo y/o middleware compartidos. Anidable.
     *
     * @param array{prefix?: string, middleware?: list<callable|string>} $opts
     */
    public function group(array $opts, Closure $register): void
    {
        $this->groupStack[] = [
            'prefix'     => $opts['prefix'] ?? '',
            'middleware' => $opts['middleware'] ?? [],
        ];
        try {
            $register($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $methodAllowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodAllowed[$route['method']] = true;
                continue;
            }
            $params = [];
            foreach ($route['params'] as $name) {
                $params[$name] = $matches[$name] ?? '';
            }
            return $this->runPipeline($route['middleware'], $route['handler'], $request, $params);
        }

        if ($methodAllowed !== []) {
            return new Response(
                'Method Not Allowed',
                405,
                ['content-type' => 'text/plain; charset=utf-8', 'allow' => implode(', ', array_keys($methodAllowed))],
            );
        }

        return new Response('Not Found', 404, ['content-type' => 'text/plain; charset=utf-8']);
    }

    private function add(string $method, string $path, mixed $handler): RouteEntry
    {
        [$prefix, $groupMiddleware] = $this->collapseGroupStack();
        $fullPath = $this->normalizePath($prefix . $path);

        [$regex, $params] = $this->compilePath($fullPath);

        $this->routes[] = [
            'method'     => $method,
            'regex'      => $regex,
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => $groupMiddleware,
        ];

        // Devolvemos un RouteEntry que apunta al último elemento para que
        // el caller pueda añadir middleware con ->middleware($mw).
        return new RouteEntry($this, array_key_last($this->routes));
    }

    /**
     * @internal Llamado por RouteEntry::middleware().
     */
    public function appendMiddlewareTo(int $index, callable|string $middleware): void
    {
        if (!isset($this->routes[$index])) {
            throw new RuntimeException("Ruta {$index} no existe — appendMiddlewareTo() llamado fuera de orden.");
        }
        $this->routes[$index]['middleware'][] = $middleware;
    }

    /**
     * @return array{0: string, 1: list<callable|string>}
     */
    private function collapseGroupStack(): array
    {
        $prefix = '';
        $middleware = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'];
            $middleware = [...$middleware, ...$g['middleware']];
        }
        return [$prefix, $middleware];
    }

    private function normalizePath(string $path): string
    {
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return $path;
    }

    /**
     * Compila `/users/{id}/posts/{slug}` a regex con grupos nombrados.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function compilePath(string $path): array
    {
        $params = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $path,
        );
        // Escape de caracteres regex que NO son los `{}` ya consumidos.
        // preg_replace_callback ya entregó la cadena con los grupos; solo
        // hace falta escapar el '/' (con preg_quote sin delimitador), pero
        // la cadena que tenemos ya es válida — anclamos y cerramos.
        return ['#^' . $regex . '$#', $params];
    }

    /**
     * @param list<callable|string> $middleware
     * @param array<string, string> $params
     */
    private function runPipeline(array $middleware, mixed $handler, Request $request, array $params): Response
    {
        $core = function (Request $req) use ($handler, $params): Response {
            return $this->invokeHandler($handler, $req, $params);
        };

        // Construye la cadena de fuera hacia dentro.
        $pipeline = array_reduce(
            array_reverse($middleware),
            function (Closure $next, callable|string $mw): Closure {
                $resolved = is_string($mw) ? $this->resolveMiddleware($mw) : $mw;
                return static fn(Request $req) => $resolved($req, $next);
            },
            $core,
        );

        return $pipeline($request);
    }

    /**
     * @param array<string, string> $params
     */
    private function invokeHandler(mixed $handler, Request $request, array $params): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request, $params);
            if (!$result instanceof Response) {
                throw new RuntimeException('Handler debe devolver App\\Core\\Response.');
            }
            return $result;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $fqcn = "App\\Controllers\\{$class}";
            if (!class_exists($fqcn)) {
                throw new RuntimeException("Controller no encontrado: {$fqcn}");
            }
            $instance = new $fqcn();
            if (!method_exists($instance, $method)) {
                throw new RuntimeException("Método {$method} no existe en {$fqcn}");
            }
            $result = $instance->{$method}($request, $params);
            if (!$result instanceof Response) {
                throw new RuntimeException("{$fqcn}::{$method} debe devolver App\\Core\\Response.");
            }
            return $result;
        }

        throw new RuntimeException('Handler no soportado: usa callable o "Controller@method".');
    }

    private function resolveMiddleware(string $name): callable
    {
        $fqcn = str_contains($name, '\\') ? $name : "App\\Middleware\\{$name}";
        if (!class_exists($fqcn)) {
            throw new RuntimeException("Middleware no encontrado: {$fqcn}");
        }
        $instance = new $fqcn();
        if (!is_callable($instance)) {
            throw new RuntimeException("{$fqcn} no es invocable — implementa __invoke(Request, Closure): Response.");
        }
        return $instance;
    }
}
