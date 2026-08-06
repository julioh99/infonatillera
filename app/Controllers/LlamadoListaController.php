<?php
// app/Controllers/LlamadoListaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';

class LlamadoListaController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera']);

        $reunionModel = new Reunion();
        $usuarioModel = new Usuario();

        $reuniones = $reunionModel->getReuniones();
        
        $selectedReunionId = isset($_GET['reunion_id']) ? (int)$_GET['reunion_id'] : 0;
        
        if ($selectedReunionId > 0) {
            $reunionActual = $reunionModel->getReunionById($selectedReunionId);
        } else {
            $reunionActual = $reunionModel->getReunionActual();
        }

        $socios = $usuarioModel->getAllSocios();
        $ahorrosRegistrados = [];

        if ($reunionActual) {
            $ahorrosRegistrados = $reunionModel->getAhorrosPorReunion((int)$reunionActual['id']);
        }

        $this->render('admin/llamado_lista', [
            'reuniones' => $reuniones,
            'reunionActual' => $reunionActual,
            'socios' => $socios,
            'ahorrosRegistrados' => $ahorrosRegistrados
        ]);
    }

    public function guardarBatch(): void {
        $this->requireRole(['Presidente', 'Tesorera']);

        $input = json_decode(file_get_contents('php://input'), true);
        $reunionId = isset($input['reunion_id']) ? (int)$input['reunion_id'] : 0;
        $registros = isset($input['registros']) && is_array($input['registros']) ? $input['registros'] : [];

        if ($reunionId <= 0 || empty($registros)) {
            $this->json(['success' => false, 'message' => 'Datos inválidos para procesar el llamado a lista.'], 400);
        }

        $reunionModel = new Reunion();
        try {
            $resultado = $reunionModel->guardarLlamadoListaBatch($reunionId, $registros);
            if ($resultado) {
                $this->json(['success' => true, 'message' => 'Llamado a lista guardado exitosamente.']);
            } else {
                $this->json(['success' => false, 'message' => 'Ocurrió un error al guardar los registros.'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function anularAutoprestamo24H(): void {
        $this->requireRole(['Presidente', 'Tesorera']);

        $ahorroId = isset($_POST['ahorro_id']) ? (int)$_POST['ahorro_id'] : 0;

        if ($ahorroId <= 0) {
            $_SESSION['error'] = "Identificador de registro no válido.";
            $this->redirect('/admin/llamado-lista');
        }

        $reunionModel = new Reunion();
        $ok = $reunionModel->anularAutoprestamo24Horas($ahorroId);

        if ($ok) {
            $_SESSION['success'] = "Autopréstamo anulado exitosamente dentro de las 24 horas sin recargo ni intereses.";
        } else {
            $_SESSION['error'] = "No se pudo anular el autopréstamo.";
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/llamado-lista');
    }
}
