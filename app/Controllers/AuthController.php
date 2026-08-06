<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Usuario.php';

class AuthController extends Controller {

    public function showLogin(): void {
        if (isset($_SESSION['usuario'])) {
            $this->redirect('/socio/dashboard');
        }
        $this->render('auth/login');
    }

    public function login(): void {
        $cedula = trim($_POST['cedula'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($cedula) || empty($password)) {
            $_SESSION['error'] = "Por favor ingresa cédula y contraseña.";
            $this->redirect('/login');
        }

        $usuarioModel = new Usuario();
        $user = $usuarioModel->login($cedula, $password);

        if ($user) {
            $_SESSION['usuario'] = $user;
            $_SESSION['active_mode'] = $user['rol_nombre']; // 'Presidente', 'Tesorera', 'Socio', etc.

            if (in_array($user['rol_nombre'], ['Presidente', 'Tesorera', 'Secretaria General', 'Secretaria Actividades'])) {
                $this->redirect('/admin/llamado-lista');
            } else {
                $this->redirect('/socio/dashboard');
            }
        } else {
            $_SESSION['error'] = "Credenciales incorrectas o usuario inactivo.";
            $this->redirect('/login');
        }
    }

    public function toggleMode(): void {
        $user = $this->requireAuth();

        if ($user['rol_nombre'] === 'Presidente') {
            $currentMode = $_SESSION['active_mode'] ?? 'Presidente';
            $_SESSION['active_mode'] = ($currentMode === 'Presidente') ? 'Socio' : 'Presidente';
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/socio/dashboard');
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('/login');
    }
}
