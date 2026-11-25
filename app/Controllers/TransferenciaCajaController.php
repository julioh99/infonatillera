<?php
// app/Controllers/TransferenciaCajaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../Models/TransferenciaCaja.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Actividad.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';

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

        // Sin usuario valido en sesion no se registra nada (nunca a nombre de otro)
        $usuario = $this->exigirUsuarioSesion();

        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        $actividadId = !empty($_POST['actividad_id']) ? (int)$_POST['actividad_id'] : null;
        $tipoMovimiento = trim($_POST['tipo_movimiento'] ?? '');
        list($monto, $errMonto) = Money::validarPositivo($_POST['monto'] ?? null, 'El monto del movimiento');
        $concepto = trim($_POST['concepto'] ?? '');

        if ($errMonto !== null) {
            $_SESSION['error'] = $errMonto;
            $this->redirect('/admin/transferencias-cajas');
        }
        if ($reunionId <= 0 || $concepto === '' || !in_array($tipoMovimiento, ['PRESTAMO_A_ACTIVIDAD', 'DEVOLUCION_A_CAJA_MAYOR'], true)) {
            $_SESSION['error'] = "Por favor completa todos los campos requeridos correctamente.";
            $this->redirect('/admin/transferencias-cajas');
        }
        if (strlen($concepto) > 255) {
            $concepto = substr($concepto, 0, 255);
        }

        try {
            $transfModel = new TransferenciaCaja();
            $ok = $transfModel->registrarTransferencia([
                'reunion_id' => $reunionId,
                'actividad_id' => $actividadId,
                'tipo_movimiento' => $tipoMovimiento,
                'monto' => $monto,
                'concepto' => $concepto,
                'registrado_por_usuario_id' => (int)$usuario['id']
            ]);

            if ($ok) {
                try {
                    (new Auditoria())->registrar('CREAR_TRANSFERENCIA', 'natillera_transferencias_cajas', null, null, [
                        'reunion_id' => $reunionId, 'actividad_id' => $actividadId,
                        'tipo_movimiento' => $tipoMovimiento, 'monto' => $monto, 'concepto' => $concepto
                    ]);
                } catch (Throwable $ea) {
                    error_log('[Auditoria] ' . $ea->getMessage());
                }
                $lbl = ($tipoMovimiento === 'PRESTAMO_A_ACTIVIDAD') ? 'Préstamo a Caja de Actividades' : 'Devolución/Transferencia a Caja Mayor';
                $_SESSION['success'] = "{$lbl} por $" . Money::format($monto) . " COP registrado correctamente.";
            } else {
                $_SESSION['error'] = "No se pudo registrar el movimiento entre cajas.";
            }
        } catch (Throwable $e) {
            error_log('[TransferenciaCajaController::crear] ' . $e->getMessage());
            $_SESSION['error'] = "No se pudo registrar el movimiento entre cajas. Verifica los datos e intenta de nuevo.";
        }

        $this->redirect('/admin/transferencias-cajas');
    }

    private function exigirUsuarioSesion(): array {
        $u = Auditoria::usuarioSesion();
        if (!$u) {
            $_SESSION['error'] = "No se pudo verificar tu usuario. Cierra sesión, vuelve a ingresar e intenta de nuevo. No se registró nada.";
            $this->redirect('/login');
        }
        return $u;
    }
}
