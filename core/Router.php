<?php
// core/Router.php

class Router {
    private $routes = array();

    public function get($path, $handler) {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    private function normalizePath($path) {
        $path = trim($path, '/');
        if (strpos($path, 'public/') === 0) {
            $path = substr($path, 7);
        } elseif ($path === 'public') {
            $path = '';
        }
        return $path === '' ? '/' : '/' . $path;
    }

    public function dispatch($uri, $method) {
        $parsedUrl = parse_url($uri, PHP_URL_PATH);
        $path = $this->normalizePath($parsedUrl);

        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            $controllerClass = $handler[0];
            $action = $handler[1];
            
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                }
            }
        }

        http_response_code(404);
        echo "<h2 style='font-family:sans-serif; text-align:center; margin-top:50px;'>404 - Ruta No Encontrada (" . htmlspecialchars($path) . ")</h2>";
    }
}
