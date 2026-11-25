<?php
// app/Controllers/NotificacionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/PushSubscription.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../../core/WebPush.php';

class NotificacionController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $pushModel = new PushSubscription();
        $usuarioModel = new Usuario();

        $historial = $pushModel->getHistorialNotificaciones();
        $socios = $usuarioModel->getAllSocios();
        $llaves = WebPush::llaves();

        $this->render('admin/notificaciones', [
            'historial' => $historial,
            'socios' => $socios,
            'vapidPublicKey' => $llaves['public'],
            'pushConfigurado' => WebPush::desdeConfiguracion()->configurado()
        ]);
    }

    public function suscribir(): void {
        $user = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['endpoint']) || empty($input['keys']['p256dh']) || empty($input['keys']['auth'])) {
            $this->json(['success' => false, 'message' => 'Suscripción inválida.'], 400);
        }

        if (!WebPush::desdeConfiguracion()->endpointPermitido($input['endpoint'])) {
            $this->json(['success' => false, 'message' => 'Este navegador usa un servicio de notificaciones no compatible.'], 400);
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

        if ($destinatarioTipo !== 'TODOS' && $destinatarioTipo !== 'SOCIO_ESPECIFICO') {
            $destinatarioTipo = 'TODOS';
        }
        if ($destinatarioTipo === 'SOCIO_ESPECIFICO' && !$socioId) {
            $_SESSION['error'] = "Seleccione el socio que recibirá la notificación.";
            $this->redirect('/admin/notificaciones');
        }
        if ($destinatarioTipo === 'TODOS') {
            $socioId = null;
        }

        $pushModel = new PushSubscription();
        $notifId = $pushModel->registrarNotificacionId($titulo, $mensaje, $destinatarioTipo, $socioId, (int)$_SESSION['usuario']['id']);

        if ($notifId <= 0) {
            $_SESSION['error'] = "No se pudo emitir la notificación.";
            $this->redirect('/admin/notificaciones');
        }

        $suscripciones = $pushModel->getSuscripciones($socioId);
        $wp = WebPush::desdeConfiguracion();
        $_SESSION['success'] = "Notificación emitida y guardada en la campanita de los socios.";
        if (!$wp->configurado()) {
            $_SESSION['success'] .= " El envío al navegador no está activo porque faltan las llaves VAPID.";
        } elseif (count($suscripciones) === 0) {
            $_SESSION['success'] .= " Ningún dispositivo tiene activadas las notificaciones del navegador todavía.";
        } else {
            $_SESSION['success'] .= " Se está avisando a " . count($suscripciones) . " dispositivo(s) en segundo plano.";
        }

        // Se responde primero al usuario y el envío push se hace después, para no hacerle esperar.
        $this->cerrarRespuesta('/admin/notificaciones');
        if ($wp->configurado() && $suscripciones) {
            $this->enviarPush($wp, $pushModel, $suscripciones, $notifId);
        }
        exit;
    }

    /** Envía una notificación de prueba solo a los dispositivos del usuario que la pide. */
    public function prueba(): void {
        $user = $this->requireAuth();
        $pushModel = new PushSubscription();
        $wp = WebPush::desdeConfiguracion();

        if (!$wp->configurado()) {
            $this->json(['success' => false, 'message' => 'El envío push no está configurado en el servidor (faltan las llaves VAPID).'], 500);
        }
        $suscripciones = $pushModel->getSuscripciones((int)$user['id']);
        if (!$suscripciones) {
            $this->json(['success' => false, 'message' => 'Este dispositivo aún no está activado. Pulsa «Activar en mi Navegador» primero.'], 404);
        }

        $notifId = $pushModel->registrarNotificacionId(
            'Prueba de notificaciones',
            'Si ves este aviso, las notificaciones de InfoNatillera funcionan en tu dispositivo.',
            'SOCIO_ESPECIFICO',
            (int)$user['id'],
            (int)$user['id']
        );
        $resultados = $this->enviarPush($wp, $pushModel, $suscripciones, $notifId > 0 ? $notifId : null);
        $enviados = 0;
        foreach ($resultados as $r) {
            if ($r['ok']) {
                $enviados++;
            }
        }
        if ($enviados > 0) {
            $this->json(['success' => true, 'message' => "Prueba enviada a $enviados dispositivo(s). Debería llegarte en unos segundos.", 'enviados' => $enviados]);
        }
        $this->json(['success' => false, 'message' => 'El servicio de notificaciones no aceptó el envío. Vuelve a pulsar «Activar en mi Navegador» e inténtalo de nuevo.'], 502);
    }

    /**
     * Envía push vacío a cada suscripción, registra el resultado y borra las caducadas (404/410).
     * Nunca lanza error: un fallo de push no debe romper la petición.
     */
    private function enviarPush(WebPush $wp, PushSubscription $pushModel, array $suscripciones, ?int $notifId): array {
        try {
            $resultados = $wp->enviarVarios($suscripciones);
            foreach ($suscripciones as $i => $sub) {
                $r = $resultados[$i];
                $pushModel->registrarEnvio($notifId, (int)$sub['id'], (int)$sub['socio_id'], (int)$r['status'], (bool)$r['ok'], $r['detalle']);
                if ($r['caducada']) {
                    $pushModel->eliminarSuscripcion((int)$sub['id']);
                }
            }
            return $resultados;
        } catch (Throwable $e) {
            error_log('[push] fallo al enviar: ' . $e->getMessage());
            return [];
        }
    }

    /** Cierra la respuesta HTTP (redirección) y deja seguir al script en segundo plano. */
    private function cerrarRespuesta(string $url): void {
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }
        header('Location: ' . $url);
        header('Content-Length: 0');
        header('Connection: close');
        session_write_close();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }
            flush();
        }
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
