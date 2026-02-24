<?php

namespace App\Core;

class Router
{
    private $routes = [];

    public function add($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch($method, $uri)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                [$controller, $action] = explode('@', $route['handler']);
                $controller = "App\\Controllers\\$controller";
                $instance = new $controller();
                $instance->$action();
                return;
            }
        }

        // 404
        http_response_code(404);
        echo "404 Not Found";
    }
}
