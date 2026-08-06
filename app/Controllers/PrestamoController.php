<?php
// app/Controllers/PrestamoController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Prestamo.php';
require_once __DIR__ . '/../Models/Usuario.php';

class PrestamoController extends Controller {

    public function index(): void {
        $user = $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $prestamoModel = new Prestamo();
        $usuarioModel = new Usuario();

        $prestamos = $prestamoModel->getTodosPrestamos();
        $socios = $usuarioModel->getAllSocios();

        $this->render('admin/prestamos', [
            'prestamos' => $prestamos,
            'socios' => $socios,
            'userRole' => $_SESSION['usuario']['rol_nombre']
        ]);
    }

    public function guardar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $socioDeudorId = (int)($_POST['socio_deudor_id'] ?? 0);
        $socioFiadorId = !empty($_POST['socio_fiador_id']) ? (int)$_POST['socio_fiador_id'] : null;
        $terceroNombre = trim($_POST['tercero_nombre'] ?? '');
        $monto = (float)($_POST['monto_prestado'] ?? 0);
        $tasa = (float)($_POST['tasa_interes_mensual'] ?? 10.00);
        $tipo = trim($_POST['tipo_prestamo'] ?? 'DIRECTO');

        if ($socioDeudorId <= 0 || $monto <= 0) {
            $_SESSION['error'] = "Por favor selecciona un socio deudor y un monto válido.";
            $this->redirect('/admin/prestamos');
        }

        // Validación de tope si no es Secretaria General o Presidente modificando
        $usuarioModel = new Usuario();
        $deudor = $usuarioModel->getSocioById($socioDeudorId);
        $tope = (float)($deudor['tope_prestamo_personalizado'] ?? 2000000.00);

        if ($monto > $tope && !in_array($_SESSION['usuario']['rol_nombre'], ['Presidente', 'Secretaria General'])) {
            $_SESSION['error'] = "El monto solicita excede el tope permitido del socio ($" . number_format($tope, 0, ',', '.') . "). Requiere autorización de Secretaria General o Presidente.";
            $this->redirect('/admin/prestamos');
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->crearPrestamo([
            'socio_deudor_id' => $socioDeudorId,
            'socio_fiador_id' => $socioFiadorId,
            'tercero_nombre' => $terceroNombre,
            'monto_prestado' => $monto,
            'tasa_interes_mensual' => $tasa,
            'tipo_prestamo' => $tipo,
            'es_autoprestamo' => 0
        ]);

        if ($ok) {
            $_SESSION['success'] = "Préstamo registrado exitosamente.";
        } else {
            $_SESSION['error'] = "No se pudo registrar el préstamo.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function abono(): void {
        $this->requireRole(['Presidente', 'Tesorera']);

        $prestamoId = (int)($_POST['prestamo_id'] ?? 0);
        $montoCapital = (float)($_POST['monto_capital_pagado'] ?? 0);
        $montoInteres = (float)($_POST['monto_interes_pagado'] ?? 0);

        if ($prestamoId <= 0 || ($montoCapital <= 0 && $montoInteres <= 0)) {
            $_SESSION['error'] = "Ingresa al menos un valor a abonar en capital o interés.";
            $this->redirect('/admin/prestamos');
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->registrarAbono($prestamoId, $montoCapital, $montoInteres, $_SESSION['usuario']['id']);

        if ($ok) {
            $_SESSION['success'] = "Abono registrado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo registrar el abono.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function actualizarTope(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $socioId = (int)($_POST['socio_id'] ?? 0);
        $nuevoTope = (float)($_POST['nuevo_tope'] ?? 2000000.00);

        if ($socioId <= 0 || $nuevoTope <= 0) {
            $_SESSION['error'] = "Datos de tope de crédito no válidos.";
            $this->redirect('/admin/prestamos');
        }

        $usuarioModel = new Usuario();
        $ok = $usuarioModel->updateTopePrestamo($socioId, $nuevoTope);

        if ($ok) {
            $_SESSION['success'] = "Tope de préstamo del socio actualizado a $" . number_format($nuevoTope, 0, ',', '.') . " COP.";
        } else {
            $_SESSION['error'] = "Error al actualizar el tope del socio.";
        }

        $this->redirect('/admin/prestamos');
    }
}
