<?php
// app/Controllers/TransferenciaCajaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/TransferenciaCaja.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Actividad.php';
require_once __DIR__ . '/../Models/Usuario.php';

class TransferenciaCajaController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $transfModel = new TransferenciaCaja();
        $reunionModel = new Reunion();
        $actividadModel = new Actividad();

        $transferencias = $transfModel->getTransferencias();
        $resumen = $transfModel->getResumenIntercajas();
        $reuniones = $reunionModel->getReuniones();
        $actividades = $actividadModel->getTodasActividades();

        $this->render('admin/transferencias_cajas', [
            'transferencias' => $transferencias,
            'resumen' => $resumen,
            'reuniones' => $reuniones,
            'actividades' => $actividades
        ]);
    }

    public function crear(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        $actividadId = !empty($_POST['actividad_id']) ? (int)$_POST['actividad_id'] : null;
        $tipoMovimiento = trim($_POST['tipo_movimiento'] ?? '');
        $montoStr = trim($_POST['monto'] ?? '0');
        $monto = (float)str_replace('.', '', $montoStr);
        $concepto = trim($_POST['concepto'] ?? '');

        if ($reunionId <= 0 || $monto <= 0 || empty($concepto) || !in_array($tipoMovimiento, ['PRESTAMO_A_ACTIVIDAD', 'DEVOLUCION_A_CAJA_MAYOR'])) {
            $_SESSION['error'] = "Por favor completa todos los campos requeridos correctamente.";
            $this->redirect('/admin/transferencias-cajas');
        }

        $usuarioModel = new Usuario();
        $registradoPor = (int)($_SESSION['usuario']['id'] ?? 1);
        if (!$usuarioModel->getSocioById($registradoPor)) {
            $todosSocios = $usuarioModel->getAllSocios();
            $registradoPor = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
        }

        try {
            $transfModel = new TransferenciaCaja();
            $ok = $transfModel->registrarTransferencia([
                'reunion_id' => $reunionId,
                'actividad_id' => $actividadId,
                'tipo_movimiento' => $tipoMovimiento,
                'monto' => $monto,
                'concepto' => $concepto,
                'registrado_por_usuario_id' => $registradoPor
            ]);

            if ($ok) {
                $lbl = ($tipoMovimiento === 'PRESTAMO_A_ACTIVIDAD') ? 'Préstamo a Caja de Actividades' : 'Devolución/Transferencia a Caja Mayor';
                $_SESSION['success'] = "{$lbl} por $" . number_format($monto, 0, ',', '.') . " COP registrado correctamente.";
            } else {
                $_SESSION['error'] = "No se pudo registrar el movimiento entre cajas.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error al registrar movimiento: " . $e->getMessage();
        }

        $this->redirect('/admin/transferencias-cajas');
    }
}
