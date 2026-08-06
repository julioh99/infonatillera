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
        $ingresos = (float)($_POST['ingresos_totales'] ?? 0);
        $gastos = (float)($_POST['gastos_totales'] ?? 0);
        $participantes = isset($_POST['participantes']) && is_array($_POST['participantes']) ? $_POST['participantes'] : [];

        if (empty($nombre) || empty($fecha)) {
            $_SESSION['error'] = "Ingresa el nombre y fecha de la actividad.";
            $this->redirect('/admin/actividades');
        }

        $actividadModel = new Actividad();
        $ok = $actividadModel->crearActividad([
            'nombre_actividad' => $nombre,
            'descripcion' => $descripcion,
            'fecha_actividad' => $fecha,
            'ingresos_totales' => $ingresos,
            'gastos_totales' => $gastos,
            'creado_por_usuario_id' => $_SESSION['usuario']['id']
        ], $participantes);

        if ($ok) {
            $_SESSION['success'] = "Actividad registrada y utilidades liquidadas entre los socios participantes.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al guardar la actividad.";
        }

        $this->redirect('/admin/actividades');
    }
}
