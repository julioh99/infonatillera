<?php
// app/Controllers/NotificacionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/PushSubscription.php';
require_once __DIR__ . '/../Models/Usuario.php';

class NotificacionController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $pushModel = new PushSubscription();
        $usuarioModel = new Usuario();

        $historial = $pushModel->getHistorialNotificaciones();
        $socios = $usuarioModel->getAllSocios();
        $vapidConfig = require __DIR__ . '/../../config/vapid_keys.php';

        $this->render('admin/notificaciones', [
            'historial' => $historial,
            'socios' => $socios,
            'vapidPublicKey' => $vapidConfig['VAPID_PUBLIC_KEY']
        ]);
    }

    public function suscribir(): void {
        $user = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['endpoint']) || empty($input['keys']['p256dh']) || empty($input['keys']['auth'])) {
            $this->json(['success' => false, 'message' => 'Suscripción inválida.'], 400);
        }

        $pushModel = new PushSubscription();
        $ok = $pushModel->guardarSuscripcion(
            (int)$user['id'],
            $input['endpoint'],
            $input['keys']['p256dh'],
            $input['keys']['auth']
        );

        if ($ok) {
            $this->json(['success' => true, 'message' => 'Suscripción Web Push registrada con éxito.']);
        } else {
            $this->json(['success' => false, 'message' => 'No se pudo registrar la suscripción.'], 500);
        }
    }

    public function enviar(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $titulo = trim($_POST['titulo'] ?? '');
        $mensaje = trim($_POST['mensaje'] ?? '');
        $destinatarioTipo = trim($_POST['destinatario_tipo'] ?? 'TODOS');
        $socioId = !empty($_POST['socio_id']) ? (int)$_POST['socio_id'] : null;

        if (empty($titulo) || empty($mensaje)) {
            $_SESSION['error'] = "Debe ingresar título y mensaje de la notificación.";
            $this->redirect('/admin/notificaciones');
        }

        $pushModel = new PushSubscription();
        $ok = $pushModel->registrarNotificacion($titulo, $mensaje, $destinatarioTipo, $socioId, $_SESSION['usuario']['id']);

        if ($ok) {
            $_SESSION['success'] = "Notificación emitida y registrada en la cola de envíos.";
        } else {
            $_SESSION['error'] = "No se pudo emitir la notificación.";
        }

        $this->redirect('/admin/notificaciones');
    }

    public function notificacionesJson(): void {
        $user = $this->requireAuth();
        $pushModel = new PushSubscription();
        $notificaciones = $pushModel->getNotificacionesPorSocio((int)$user['id']);
        $this->json(['success' => true, 'notificaciones' => $notificaciones]);
    }

    public function notificacionesPendientesJson(): void {
        $user = $this->requireAuth();
        $pushModel = new PushSubscription();
        $pendientes = $pushModel->getNotificacionesPendientesPorSocio((int)$user['id']);
        $this->json(['success' => true, 'pendientes' => $pendientes]);
    }

    public function marcarLeida(): void {
        $user = $this->requireAuth();
        $notificacionId = !empty($_POST['notificacion_id']) ? (int)$_POST['notificacion_id'] : 0;
        if ($notificacionId <= 0) {
            $this->json(['success' => false, 'message' => 'ID inválido.'], 400);
        }
        $pushModel = new PushSubscription();
        $ok = $pushModel->marcarNotificacionComoLeida($notificacionId, (int)$user['id']);
        $this->json(['success' => $ok]);
    }
}
