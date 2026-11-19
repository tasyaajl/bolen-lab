<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Router berbasis regex sederhana dengan parameter dinamis: /menu/{slug}
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, regex: string, handler: array{0: class-string, 1: string}}> */
    private array $routes = [];

    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     */
    private function add(string $method, string $pattern, array $handler): void
    {
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static fn (array $match): string => '(?P<' . $match[1] . '>[^/]+)',
            rtrim($pattern, '/') ?: '/'
        );

        $this->routes[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'regex'   => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): string
    {
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = urldecode($value);
                }
            }

            [$class, $action] = $route['handler'];

            if (!class_exists($class)) {
                throw new RuntimeException('Controller tidak ditemukan: ' . $class);
            }

            $controller = new $class();

            if (!method_exists($controller, $action)) {
                throw new RuntimeException('Method controller tidak ditemukan: ' . $class . '::' . $action);
            }

            return (string) $controller->{$action}(...array_values($params));
        }

        http_response_code(404);

        return View::render('errors/404', ['title' => 'Halaman Tidak Ditemukan'], 'layouts/main');
    }

}
