<?php
/**
 * Simple HTTP router: method + path pattern to handler.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    /**
     * @param callable(array<string,string>):void $handler
     */
    public function get(string $pattern, callable $handler): self
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'handler' => $handler];
        return $this;
    }

    /**
     * @param callable(array<string,string>):void $handler
     */
    public function post(string $pattern, callable $handler): self
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'handler' => $handler];
        return $this;
    }

    public function dispatch(): void
    {
        $method = request_method();
        $path = \resolve_request_route_path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $params = $this->match($route['pattern'], $path);
            if ($params !== null) {
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        $c = new \App\Controllers\HomeController();
        $c->notFound();
    }

    /**
     * @return array<string,string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }
        $regex = '@^' . preg_replace_callback(
            '@\{([a-zA-Z_][a-zA-Z0-9_]*)\}@',
            static fn (array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        ) . '$@';
        if (!preg_match($regex, $path, $m)) {
            return null;
        }
        $params = [];
        foreach ($m as $k => $v) {
            if (is_string($k) && !is_int($k)) {
                $params[$k] = (string) $v;
            }
        }
        return $params;
    }
}
