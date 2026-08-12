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
            require_once __DIR__ . '/../app/Views/layouts/header.php';
            require_once $viewFile;
            require_once __DIR__ . '/../app/Views/layouts/footer.php';
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

        // Si es Presidente, puede acceder a todo si está en modo admin
        if ($userRole === 'Presidente') {
            return $user;
        }

        if (!in_array($userRole, (array)$allowedRoles, true)) {
            $_SESSION['error'] = "No tienes permisos suficientes para acceder a este módulo.";
            $this->redirect('/socio/dashboard');
        }

        return $user;
    }
}
