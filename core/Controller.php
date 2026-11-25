<?php
// core/Controller.php

abstract class Controller {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function render($view, $data = array()) {
        extract($data);
        
        // Información del usuario autenticado
        $currentUser = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
        $activeMode = isset($_SESSION['active_mode']) ? $_SESSION['active_mode'] : (isset($currentUser['rol_nombre']) ? $currentUser['rol_nombre'] : 'Socio');

        $viewFile = __DIR__ . '/../app/Views/' . $view . '.php';

        if (file_exists($viewFile)) {
            // Se captura la salida para insertar el token CSRF en los formularios POST (core/Csrf.php)
            Csrf::token();
            ob_start();
            try {
                require_once __DIR__ . '/../app/Views/layouts/header.php';
                require_once $viewFile;
                require_once __DIR__ . '/../app/Views/layouts/footer.php';
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            echo Csrf::inyectar(ob_get_clean());
        } else {
            die("Vista no encontrada: " . htmlspecialchars($view));
        }
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect($url) {
        header("Location: " . $url);
        exit;
    }

    protected function requireAuth() {
        if (!isset($_SESSION['usuario'])) {
            $this->redirect('/login');
        }

        $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (empty($_SESSION['usuario']['password_changed']) 
            && strpos($currentUri, '/cambiar-password-inicial') === false 
            && strpos($currentUri, '/logout') === false) {
            $this->redirect('/cambiar-password-inicial');
        }

        return $_SESSION['usuario'];
    }

    protected function requireRole($allowedRoles) {
        $user = $this->requireAuth();
        $userRole = isset($_SESSION['usuario']['rol_nombre']) ? $_SESSION['usuario']['rol_nombre'] : '';
        $activeMode = isset($_SESSION['active_mode']) ? $_SESSION['active_mode'] : $userRole;

        // El Presidente accede a todo SOLO en modo Presidente; en modo Socio se trata como Socio.
        if ($userRole === 'Presidente') {
            if ($activeMode === 'Presidente') {
                return $user;
            }
            $userRole = 'Socio';
        }

        if (!in_array($userRole, (array)$allowedRoles, true)) {
            if (Csrf::esAjax()) {
                $this->json(['success' => false, 'message' => 'No tienes permisos suficientes.'], 403);
            }
            $_SESSION['error'] = "No tienes permisos suficientes para acceder a este módulo.";
            $this->redirect('/socio/dashboard');
        }

        return $user;
    }

    /** Lee una clave de config/natillera.php con notacion de puntos: natillera('seguridad.password_min', 8). */
    protected function natillera($clave, $defecto = null) {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/../config/natillera.php';
        }
        $nodo = $cfg;
        foreach (explode('.', $clave) as $parte) {
            if (!is_array($nodo) || !array_key_exists($parte, $nodo)) {
                return $defecto;
            }
            $nodo = $nodo[$parte];
        }
        return $nodo;
    }

    /** Token CSRF de la sesion (para vistas o controladores que lo necesiten). */
    protected function csrfToken() {
        return Csrf::token();
    }

    /** Valida el token de la peticion actual; responde 419 y termina si no es valido. */
    protected function verificarCsrf() {
        if (!Csrf::valido()) {
            Csrf::rechazar();
        }
    }
}
