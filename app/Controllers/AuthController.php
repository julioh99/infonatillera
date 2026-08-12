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

            if (empty($user['password_changed'])) {
                $this->redirect('/cambiar-password-inicial');
            }

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

    public function showCambiarPasswordInicial(): void {
        if (!isset($_SESSION['usuario'])) {
            $this->redirect('/login');
        }
        if (!empty($_SESSION['usuario']['password_changed'])) {
            $this->redirect('/socio/dashboard');
        }
        $this->render('auth/cambiar_password_inicial');
    }

    public function procesarCambiarPasswordInicial(): void {
        if (!isset($_SESSION['usuario'])) {
            $this->redirect('/login');
        }

        $passwordNuevo = trim($_POST['password_nuevo'] ?? '');
        $passwordConfirmar = trim($_POST['password_confirmar'] ?? '');

        if (strlen($passwordNuevo) < 6) {
            $_SESSION['error'] = "La nueva contraseña debe tener al menos 6 caracteres.";
            $this->redirect('/cambiar-password-inicial');
        }

        if ($passwordNuevo !== $passwordConfirmar) {
            $_SESSION['error'] = "Las contraseñas ingresadas no coinciden.";
            $this->redirect('/cambiar-password-inicial');
        }

        $usuarioModel = new Usuario();
        $userId = (int)$_SESSION['usuario']['id'];
        $ok = $usuarioModel->cambiarPasswordInicial($userId, $passwordNuevo);

        if ($ok) {
            $_SESSION['usuario']['password_changed'] = 1;
            $_SESSION['success'] = "¡Tu contraseña ha sido actualizada con éxito! Bienvenido(a) a la plataforma.";
            
            if (in_array($_SESSION['usuario']['rol_nombre'], ['Presidente', 'Tesorera', 'Secretaria General', 'Secretaria Actividades'])) {
                $this->redirect('/admin/llamado-lista');
            } else {
                $this->redirect('/socio/dashboard');
            }
        } else {
            $_SESSION['error'] = "No se pudo actualizar la contraseña. Inténtalo nuevamente.";
            $this->redirect('/cambiar-password-inicial');
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
