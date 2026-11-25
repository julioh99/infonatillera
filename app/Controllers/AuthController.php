<?php
// app/Controllers/AuthController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/LoginIntento.php';

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

        $cedula = substr($cedula, 0, 30);
        $ip = LoginIntento::ipCliente();
        $intentos = new LoginIntento();

        // Limite de intentos fallidos por cédula + IP
        $espera = $intentos->minutosBloqueo($cedula, $ip);
        if ($espera > 0) {
            $_SESSION['error'] = "Demasiados intentos fallidos. Espera {$espera} " . ($espera === 1 ? 'minuto' : 'minutos') . " antes de volver a intentarlo.";
            $this->redirect('/login');
        }

        $usuarioModel = new Usuario();
        $user = $usuarioModel->login($cedula, $password);

        if ($user) {
            $intentos->limpiar($cedula, $ip);
            // Evita la fijación de sesión: nuevo id de sesión al autenticar (conserva el token CSRF)
            session_regenerate_id(true);

            $_SESSION['usuario'] = $user;
            $_SESSION['active_mode'] = $user['rol_nombre']; // 'Presidente', 'Tesorera', 'Socio', etc.

            if (empty($user['password_changed'])) {
                $this->redirect('/cambiar-password-inicial');
            }

            if ($user['rol_nombre'] === 'Presidente') {
                $this->redirect('/admin/dashboard-presidente');
            } elseif (in_array($user['rol_nombre'], ['Tesorera', 'Secretaria General', 'Secretaria Actividades'])) {
                $this->redirect('/admin/llamado-lista');
            } else {
                $this->redirect('/socio/dashboard');
            }
        } else {
            $intentos->registrarFallo($cedula, $ip);
            $espera = $intentos->minutosBloqueo($cedula, $ip);
            if ($espera > 0) {
                $_SESSION['error'] = "Demasiados intentos fallidos. Espera {$espera} " . ($espera === 1 ? 'minuto' : 'minutos') . " antes de volver a intentarlo.";
            } else {
                $_SESSION['error'] = "Credenciales incorrectas o usuario inactivo.";
            }
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

        $minPass = (int)$this->natillera('seguridad.password_min', 8);
        if (strlen($passwordNuevo) < $minPass) {
            $_SESSION['error'] = "La nueva contraseña debe tener al menos {$minPass} caracteres.";
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
            
            if ($_SESSION['usuario']['rol_nombre'] === 'Presidente') {
                $this->redirect('/admin/dashboard-presidente');
            } elseif (in_array($_SESSION['usuario']['rol_nombre'], ['Tesorera', 'Secretaria General', 'Secretaria Actividades'])) {
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
            $newMode = ($currentMode === 'Presidente') ? 'Socio' : 'Presidente';
            $_SESSION['active_mode'] = $newMode;

            if ($newMode === 'Presidente') {
                $this->redirect('/admin/dashboard-presidente');
            } else {
                $this->redirect('/socio/dashboard');
            }
        }

        $this->redirect('/socio/dashboard');
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('/login');
    }
}
