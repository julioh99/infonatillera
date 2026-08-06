<?php
// app/Controllers/ReunionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';

class ReunionController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $reunionModel = new Reunion();
        $usuarioModel = new Usuario();

        $reuniones = $reunionModel->getReuniones();
        $socios = $usuarioModel->getAllSocios();

        $this->render('admin/reuniones', [
            'reuniones' => $reuniones,
            'socios' => $socios
        ]);
    }

    public function crear(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $fecha = trim($_POST['fecha_reunion'] ?? '');
        $hora = trim($_POST['hora_reunion'] ?? '14:00:00');
        $cuota = (float)str_replace('.', '', $_POST['valor_cuota_base'] ?? 55000);
        $evento = trim($_POST['tipo_evento_extra'] ?? 'NINGUNO');
        $premio = (float)str_replace('.', '', $_POST['monto_premio_extra'] ?? 0);

        if (empty($fecha) || $cuota <= 0) {
            $_SESSION['error'] = "Ingresa una fecha y valor de cuota válidos.";
            $this->redirect('/admin/reuniones');
        }

        $reunionModel = new Reunion();
        $ok = $reunionModel->crearReunion([
            'fecha_reunion' => $fecha,
            'hora_reunion' => $hora,
            'valor_cuota_base' => $cuota,
            'tipo_evento_extra' => $evento,
            'monto_premio_extra' => $premio
        ]);

        if ($ok) {
            $_SESSION['success'] = "Nueva reunión programada exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al crear la reunión.";
        }

        $this->redirect('/admin/reuniones');
    }

    public function actualizar(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $id = (int)($_POST['reunion_id'] ?? 0);
        $fecha = trim($_POST['fecha_reunion'] ?? '');
        $hora = trim($_POST['hora_reunion'] ?? '14:00:00');
        $cuota = (float)str_replace('.', '', $_POST['valor_cuota_base'] ?? 55000);
        $evento = trim($_POST['tipo_evento_extra'] ?? 'NINGUNO');
        $premio = (float)str_replace('.', '', $_POST['monto_premio_extra'] ?? 0);
        $ganadorId = !empty($_POST['ganador_socio_id']) ? (int)$_POST['ganador_socio_id'] : null;
        $estado = trim($_POST['estado'] ?? 'PROGRAMADA');

        if ($id <= 0 || empty($fecha) || $cuota <= 0) {
            $_SESSION['error'] = "Fecha y valor de cuota son obligatorios.";
            $this->redirect('/admin/reuniones');
        }

        $reunionModel = new Reunion();
        $ok = $reunionModel->actualizarReunion($id, [
            'fecha_reunion' => $fecha,
            'hora_reunion' => $hora,
            'valor_cuota_base' => $cuota,
            'tipo_evento_extra' => $evento,
            'monto_premio_extra' => $premio,
            'ganador_socio_id' => $ganadorId,
            'estado' => $estado
        ]);

        if ($ok) {
            $_SESSION['success'] = "Reunión actualizada exitosamente.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar la reunión.";
        }

        $this->redirect('/admin/reuniones');
    }
}
