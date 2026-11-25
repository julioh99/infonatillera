<?php
// app/Controllers/CierreController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/CierreReunion.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';

class CierreController extends Controller {

    /**
     * Registra un error en logs/cierre_reunion.log y en el error_log de PHP.
     */
    private function logError(Throwable $e): void {
        $linea = sprintf(
            "[%s] %s: %s en %s:%d | uri=%s | usuario=%s\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $_SERVER['REQUEST_URI'] ?? '',
            $_SESSION['usuario']['cedula'] ?? '?',
            $e->getTraceAsString()
        );
        error_log($linea);
        $dir = __DIR__ . '/../../logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/cierre_reunion.log', $linea, FILE_APPEND);
    }

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        ob_start();
        try {
            $this->renderIndex();
            ob_end_flush();
        } catch (Throwable $e) {
            ob_end_clean();
            $this->logError($e);
            http_response_code(500);
            echo '<pre style="padding:16px;white-space:pre-wrap">Ocurrió un error al cargar el Cierre de Reunión. '
                . 'Revisa logs/cierre_reunion.log en el servidor.</pre>';
        }
    }

    private function renderIndex(): void {
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
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 0);
        if ($usuarioId <= 0 || !$usuarioModel->getSocioById($usuarioId)) {
            $_SESSION['error'] = "No se pudo identificar al usuario de la sesión. Inicia sesión nuevamente para realizar el cierre.";
            $this->redirect('/admin/cierre-reunion?reunion_id=' . $reunionId);
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
        } catch (RuntimeException $e) {
            // Mensajes de negocio (p. ej. reunión ya cerrada)
            $_SESSION['error'] = $e->getMessage();
        } catch (Throwable $e) {
            $this->logError($e);
            $_SESSION['error'] = "Ocurrió un error al cerrar la reunión. Intenta nuevamente o revisa logs/cierre_reunion.log.";
        }

        $this->redirect('/admin/cierre-reunion?reunion_id=' . $reunionId);
    }
}
