<?php
// app/Controllers/CierreController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/CierreReunion.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';

class CierreController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $cierreModel = new CierreReunion();
        $reunionModel = new Reunion();

        $reuniones = $reunionModel->getReuniones();

        $reunionId = (int)($_GET['reunion_id'] ?? 0);
        if ($reunionId <= 0) {
            $actual = $reunionModel->getReunionActual();
            $reunionId = $actual ? (int)$actual['id'] : (!empty($reuniones) ? (int)$reuniones[0]['id'] : 0);
        }

        $resumen = null;
        $cierreExistente = null;

        if ($reunionId > 0) {
            $resumen = $cierreModel->calcularResumenReunion($reunionId);
            $cierreExistente = $cierreModel->getCierrePorReunion($reunionId);
        }

        $todosCierres = $cierreModel->getTodosCierres();

        $this->render('admin/cierre_reunion', [
            'reuniones' => $reuniones,
            'reunionId' => $reunionId,
            'resumen' => $resumen,
            'cierreExistente' => $cierreExistente,
            'todosCierres' => $todosCierres
        ]);
    }

    public function cerrar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $reunionId = (int)($_POST['reunion_id'] ?? 0);

        if ($reunionId <= 0) {
            $_SESSION['error'] = "Selecciona una reunión válida para realizar el cierre.";
            $this->redirect('/admin/cierre-reunion');
        }

        $usuarioModel = new Usuario();
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 1);
        if (!$usuarioModel->getSocioById($usuarioId)) {
            $todosSocios = $usuarioModel->getAllSocios();
            $usuarioId = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
        }

        try {
            $cierreModel = new CierreReunion();
            $resumen = $cierreModel->calcularResumenReunion($reunionId);
            $ok = $cierreModel->guardarCierre($reunionId, $resumen, $usuarioId);

            if ($ok) {
                $_SESSION['success'] = "¡Cierre financiero de la reunión Quincena Q{$resumen['reunion']['numero_quincena']} realizado exitosamente!";
            } else {
                $_SESSION['error'] = "No se pudo procesar el cierre financiero.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error al cerrar reunión: " . $e->getMessage();
        }

        $this->redirect('/admin/cierre-reunion?reunion_id=' . $reunionId);
    }
}
