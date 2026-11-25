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
        $nivelBuffer = ob_get_level();

        try {
            if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
                session_start();
            }

            // Token CSRF presente en cada peticion (el meta del header nunca sale vacio)
            Csrf::token();

            // Toda peticion POST debe traer un token valido; si no, no se ejecuta nada.
            if (strtoupper($method) === 'POST' && !Csrf::valido()) {
                Csrf::rechazar();
            }

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
        } catch (Throwable $e) {
            while (ob_get_level() > $nivelBuffer) {
                ob_end_clean();
            }
            $this->manejarError($e);
        }
    }

    /** Registra el detalle en logs/app.log y muestra un mensaje generico (detalle solo con APP_DEBUG=1). */
    private function manejarError($e) {
        $this->registrarError($e);
        $debug = $this->modoDebug();

        if (!headers_sent()) {
            http_response_code(500);
        }

        if (Csrf::esAjax()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            $out = array('success' => false, 'message' => 'Ocurrio un error inesperado. Intentalo de nuevo; si persiste, avisa a la directiva.');
            if ($debug) {
                $out['debug'] = get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
            }
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error</title></head>'
           . '<body style="font-family:system-ui,sans-serif;background:#f4f7f4;color:#23332b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">'
           . '<div style="max-width:460px;text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">'
           . '<h2 style="margin-top:0">Algo salio mal</h2>'
           . '<p>No pudimos completar la accion. Ya quedo registrado el problema. Intentalo de nuevo en unos minutos.</p>'
           . '<p><a href="/" style="color:#1f6b4a;font-weight:600">Volver al inicio</a></p>';
        if ($debug) {
            echo '<pre style="text-align:left;white-space:pre-wrap;font-size:12px;background:#f1f1f1;padding:1rem;border-radius:8px">'
               . htmlspecialchars((string)$e) . '</pre>';
        }
        echo '</div></body></html>';
    }

    private function registrarError($e) {
        try {
            $dir = __DIR__ . '/../logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-';
            $met = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '-';
            $uid = isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : '-';
            $linea = '[' . date('Y-m-d H:i:s') . "] $met $uri usuario=$uid " . get_class($e) . ': ' . $e->getMessage()
                   . ' en ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
            @file_put_contents($dir . '/app.log', $linea, FILE_APPEND | LOCK_EX);
        } catch (Throwable $ignorar) {
            // Registrar el error nunca debe causar otro error
        }
    }

    private function modoDebug() {
        $v = getenv('APP_DEBUG');
        if ($v === false && isset($_ENV['APP_DEBUG'])) {
            $v = $_ENV['APP_DEBUG'];
        }
        if ($v === false && isset($_SERVER['APP_DEBUG'])) {
            $v = $_SERVER['APP_DEBUG'];
        }
        return $v === '1' || $v === 1 || $v === true;
    }
}
