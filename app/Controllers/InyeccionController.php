<?php
// app/Controllers/InyeccionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/InyeccionCapital.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';

class InyeccionController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $inyeccionModel = new InyeccionCapital();
        $usuarioModel = new Usuario();
        $reunionModel = new Reunion();

        $inyecciones = $inyeccionModel->getInyecciones();
        $resumen = $inyeccionModel->getResumenInyecciones();
        $socios = $usuarioModel->getAllSocios();
        $reuniones = $reunionModel->getReuniones();

        $this->render('admin/inyecciones', [
            'inyecciones' => $inyecciones,
            'resumen' => $resumen,
            'socios' => $socios,
            'reuniones' => $reuniones
        ]);
    }

    public function crear(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $socioId = (int)($_POST['socio_id'] ?? 0);
        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        $montoStr = trim($_POST['monto_inyectado'] ?? '0');
        $monto = (float)str_replace('.', '', $montoStr);
        $tasa = (float)($_POST['tasa_rendimiento_porcentaje'] ?? 5.00);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($socioId <= 0 || $reunionId <= 0 || $monto <= 0) {
            $_SESSION['error'] = "Por favor selecciona el socio, la reunión e ingresa un monto válido.";
            $this->redirect('/admin/inyecciones');
        }

        $usuarioModel = new Usuario();
        $registradoPor = (int)($_SESSION['usuario']['id'] ?? 1);
        if (!$usuarioModel->getSocioById($registradoPor)) {
            $todosSocios = $usuarioModel->getAllSocios();
            $registradoPor = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
        }

        try {
            $inyeccionModel = new InyeccionCapital();
            $ok = $inyeccionModel->crearInyeccion([
                'socio_id' => $socioId,
                'reunion_id' => $reunionId,
                'monto_inyectado' => $monto,
                'tasa_rendimiento_porcentaje' => $tasa,
                'observaciones' => $observaciones,
                'registrado_por_usuario_id' => $registradoPor
            ]);

            if ($ok) {
                $_SESSION['success'] = "Inyección de capital por $" . number_format($monto, 0, ',', '.') . " COP al {$tasa}% registrada exitosamente. Queda congelada por 6 meses.";
            } else {
                $_SESSION['error'] = "No se pudo registrar la inyección de capital.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error al registrar inyección: " . $e->getMessage();
        }

        $this->redirect('/admin/inyecciones');
    }

    public function retirar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $inyeccionId = (int)($_POST['inyeccion_id'] ?? 0);

        if ($inyeccionId <= 0) {
            $_SESSION['error'] = "Inyección de capital no especificada.";
            $this->redirect('/admin/inyecciones');
        }

        $usuarioModel = new Usuario();
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 1);
        if (!$usuarioModel->getSocioById($usuarioId)) {
            $todosSocios = $usuarioModel->getAllSocios();
            $usuarioId = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
        }

        try {
            $inyeccionModel = new InyeccionCapital();
            $ok = $inyeccionModel->retirarInyeccion($inyeccionId, $usuarioId);

            if ($ok) {
                $_SESSION['success'] = "Devolución / Retiro de inyección de capital procesado correctamente.";
            } else {
                $_SESSION['error'] = "No se pudo retirar la inyección de capital.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/inyecciones');
    }
}
