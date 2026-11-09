<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador simple: asocia "MÉTODO /ruta/{param}" con [Controlador, método].
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void  { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, array $handler): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [$method, '#^' . $regex . '$#u', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rawurldecode(parse_url($uri, PHP_URL_PATH) ?: '/');
        $path = $path !== '/' ? rtrim($path, '/') : '/';

        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                if ($method === 'POST') {
                    Csrf::verify();
                }
                (new $class())->$action(...$params);
                return;
            }
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
    }
}
