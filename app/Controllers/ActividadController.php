<?php
// app/Controllers/ActividadController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Actividad.php';
require_once __DIR__ . '/../Models/Usuario.php';

class ActividadController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $actividadModel = new Actividad();
        $usuarioModel = new Usuario();

        $actividades = $actividadModel->getTodasActividades();
        $socios = $usuarioModel->getAllSocios();

        $this->render('admin/actividades', [
            'actividades' => $actividades,
            'socios' => $socios
        ]);
    }

    public function guardar(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $nombre = trim($_POST['nombre_actividad'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha = trim($_POST['fecha_actividad'] ?? date('Y-m-d'));
        $ingresos = (float)str_replace('.', '', $_POST['ingresos_totales'] ?? 0);
        $gastos = (float)str_replace('.', '', $_POST['gastos_totales'] ?? 0);
        $cuotaBase = (float)str_replace('.', '', $_POST['cuota_por_socio'] ?? 0);

        $checkedParticipantes = isset($_POST['participantes']) && is_array($_POST['participantes']) ? $_POST['participantes'] : [];
        $cuotasIndividuales = isset($_POST['cuotas_individuales']) && is_array($_POST['cuotas_individuales']) ? $_POST['cuotas_individuales'] : [];

        if (empty($nombre) || empty($fecha)) {
            $_SESSION['error'] = "Ingresa el nombre y fecha de la actividad.";
            $this->redirect('/admin/actividades');
        }

        $participantesCuotas = [];
        foreach ($checkedParticipantes as $sId) {
            $sId = (int)$sId;
            $rawIndiv = isset($cuotasIndividuales[$sId]) ? $cuotasIndividuales[$sId] : $cuotaBase;
            $montoIndiv = (float)str_replace('.', '', $rawIndiv);
            $participantesCuotas[$sId] = $montoIndiv;
        }

        $actividadModel = new Actividad();
        $ok = $actividadModel->crearActividad([
            'nombre_actividad' => $nombre,
            'descripcion' => $descripcion,
            'fecha_actividad' => $fecha,
            'ingresos_totales' => $ingresos,
            'gastos_totales' => $gastos,
            'cuota_por_socio' => $cuotaBase,
            'creado_por_usuario_id' => $_SESSION['usuario']['id']
        ], $participantesCuotas);

        if ($ok) {
            $_SESSION['success'] = "Actividad registrada con cuotas individuales asignadas a los socios.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al guardar la actividad.";
        }

        $this->redirect('/admin/actividades');
    }

    public function participantesJson(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);
        $actividadId = (int)($_GET['actividad_id'] ?? 0);

        header('Content-Type: application/json');
        if ($actividadId <= 0) {
            echo json_encode(['success' => false, 'participantes' => []]);
            return;
        }

        $actividadModel = new Actividad();
        $actividad = $actividadModel->getActividadById($actividadId);
        $participantes = $actividadModel->getParticipantes($actividadId);

        echo json_encode([
            'success' => true,
            'actividad' => $actividad,
            'participantes' => $participantes
        ]);
    }

    public function actualizarPago(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $participanteId = (int)($_POST['participante_id'] ?? 0);
        $montoPagado = (float)str_replace('.', '', $_POST['monto_pagado'] ?? 0);

        if ($participanteId <= 0) {
            $_SESSION['error'] = "Participante inválido.";
            $this->redirect('/admin/actividades');
        }

        $actividadModel = new Actividad();
        $ok = $actividadModel->actualizarPagoParticipante($participanteId, $montoPagado, $_SESSION['usuario']['id']);

        if ($ok) {
            $_SESSION['success'] = "Pago de la actividad actualizado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar el pago.";
        }

        $this->redirect('/admin/actividades');
    }

    public function registrarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $participanteId = (int)($_POST['participante_id'] ?? 0);
        $montoAbono = (float)str_replace('.', '', $_POST['monto_abono'] ?? 0);
        $observacion = trim($_POST['observacion'] ?? '');

        if ($participanteId <= 0 || $montoAbono <= 0) {
            $_SESSION['error'] = "Ingresa un monto de abono válido mayor a $0.";
            $this->redirect('/admin/actividades');
        }

        $actividadModel = new Actividad();
        $ok = $actividadModel->registrarAbono($participanteId, $montoAbono, $_SESSION['usuario']['id'], $observacion);

        if ($ok) {
            $_SESSION['success'] = "Abono de $" . number_format($montoAbono, 0, ',', '.') . " COP registrado exitosamente.";
        } else {
            $_SESSION['error'] = "No se pudo registrar el abono.";
        }

        $this->redirect('/admin/actividades');
    }

    public function eliminarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $abonoId = (int)($_POST['abono_id'] ?? 0);

        if ($abonoId <= 0) {
            $_SESSION['error'] = "Abono inválido.";
            $this->redirect('/admin/actividades');
        }

        $actividadModel = new Actividad();
        $ok = $actividadModel->eliminarAbono($abonoId);

        if ($ok) {
            $_SESSION['success'] = "Abono eliminado correctamente y saldo recalculado.";
        } else {
            $_SESSION['error'] = "No se pudo eliminar el abono.";
        }

        $this->redirect('/admin/actividades');
    }
}
