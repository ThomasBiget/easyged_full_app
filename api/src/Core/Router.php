<?php

namespace App\Core;

use App\Middleware\JwtMiddleware;

class Router
{
    private array $routes = [];
    private JwtMiddleware $jwtMiddleware;

    public function __construct(JwtMiddleware $jwtMiddleware)
    {
        $this->jwtMiddleware = $jwtMiddleware;
    }

    public function get(string $path, callable $action, bool $protected = false): void
    {
        $this->routes['GET'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function post(string $path, callable $action, bool $protected = false): void
    {
        $this->routes['POST'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function put(string $path, callable $action, bool $protected = false): void
    {
        $this->routes['PUT'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function delete(string $path, callable $action, bool $protected = false): void
    {
        $this->routes['DELETE'][$path] = [
            'action' => $action,
            'protected' => $protected
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        if (!isset($this->routes[$method][$uri])) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Route not found',
                'method' => $method,
                'uri' => $uri
            ]);
            return;
        }

        $route = $this->routes[$method][$uri];

        if ($route['protected']) {
            $this->jwtMiddleware->handle();
        }

        call_user_func($route['action']);
    }
}
