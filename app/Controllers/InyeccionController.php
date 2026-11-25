<?php
// app/Controllers/InyeccionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../Models/InyeccionCapital.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Auditoria.php';

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
        $reunionActual = $reunionModel->getReunionActual();

        $this->render('admin/inyecciones', [
            'inyecciones' => $inyecciones,
            'resumen' => $resumen,
            'socios' => $socios,
            'reuniones' => $reuniones,
            'reunionActual' => $reunionActual
        ]);
    }

    public function crear(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        // Sin usuario valido en sesion no se registra nada (nunca a nombre de otro)
        $usuario = $this->exigirUsuarioSesion();

        $socioId = (int)($_POST['socio_id'] ?? 0);
        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        list($monto, $errMonto) = Money::validarPositivo($_POST['monto_inyectado'] ?? null, 'El monto de la inyección');
        $tasaDefecto = (float)Money::config('controladores.inyeccion_tasa_defecto', 5.0);
        $tasaMax = (float)Money::config('controladores.inyeccion_tasa_max', 20.0);
        $tasaEnviada = $_POST['tasa_rendimiento_porcentaje'] ?? '';
        $tasa = (trim((string)$tasaEnviada) === '') ? $tasaDefecto : Money::parsePorcentaje($tasaEnviada, 0.0, $tasaMax);
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($socioId <= 0 || $reunionId <= 0) {
            $_SESSION['error'] = "Por favor selecciona el socio y la reunión.";
            $this->redirect('/admin/inyecciones');
        }
        if ($errMonto !== null) {
            $_SESSION['error'] = $errMonto;
            $this->redirect('/admin/inyecciones');
        }
        if ($tasa === null) {
            $_SESSION['error'] = "El porcentaje de rendimiento no es válido. Escribe un valor entre 0 y {$tasaMax}.";
            $this->redirect('/admin/inyecciones');
        }
        if (strlen($observaciones) > 255) {
            $observaciones = substr($observaciones, 0, 255);
        }

        try {
            $inyeccionModel = new InyeccionCapital();
            $ok = $inyeccionModel->crearInyeccion([
                'socio_id' => $socioId,
                'reunion_id' => $reunionId,
                'monto_inyectado' => $monto,
                'tasa_rendimiento_porcentaje' => $tasa,
                'observaciones' => $observaciones,
                'registrado_por_usuario_id' => (int)$usuario['id']
            ]);

            if ($ok) {
                $this->auditar('CREAR_INYECCION', 'natillera_inyecciones_capital', null, null, [
                    'socio_id' => $socioId, 'reunion_id' => $reunionId, 'monto' => $monto, 'tasa' => $tasa
                ]);
                $_SESSION['success'] = "Inyección de capital por $" . Money::format($monto) . " COP al {$tasa}% registrada exitosamente. Queda congelada por 6 meses.";
            } else {
                $_SESSION['error'] = "No se pudo registrar la inyección de capital.";
            }
        } catch (Throwable $e) {
            error_log('[InyeccionController::crear] ' . $e->getMessage());
            $_SESSION['error'] = "No se pudo registrar la inyección de capital. Verifica los datos e intenta de nuevo.";
        }

        $this->redirect('/admin/inyecciones');
    }

    public function retirar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $usuario = $this->exigirUsuarioSesion();

        $inyeccionId = (int)($_POST['inyeccion_id'] ?? 0);
        $reunionIdRetiro = (int)($_POST['reunion_id_retiro'] ?? 0);

        if ($inyeccionId <= 0) {
            $_SESSION['error'] = "Inyección de capital no especificada.";
            $this->redirect('/admin/inyecciones');
        }

        $auditoria = null;
        $auditId = 0;
        try {
            // Instantanea antes del cambio de estado (permite restaurar)
            $auditoria = new Auditoria();
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_inyecciones_capital', $inyeccionId, [], 'RETIRAR_INYECCION');

            $inyeccionModel = new InyeccionCapital();
            $ok = $inyeccionModel->retirarInyeccion($inyeccionId, (int)$usuario['id'], $reunionIdRetiro);

            if ($ok) {
                $auditoria->completar($auditId, $auditoria->fila('natillera_inyecciones_capital', $inyeccionId));
                $_SESSION['success'] = "Devolución / Retiro de inyección de capital procesado correctamente y registrado como salida de reunión.";
            } else {
                $auditoria->descartar($auditId);
                $_SESSION['error'] = "No se pudo retirar la inyección de capital.";
            }
        } catch (PDOException $e) {
            if ($auditoria && $auditId) $auditoria->descartar($auditId);
            error_log('[InyeccionController::retirar] ' . $e->getMessage());
            $_SESSION['error'] = "No se pudo retirar la inyección de capital. Intenta de nuevo.";
        } catch (Throwable $e) {
            if ($auditoria && $auditId) $auditoria->descartar($auditId);
            // Mensajes de regla de negocio del modelo (ej. inyeccion aun congelada)
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/inyecciones');
    }

    private function exigirUsuarioSesion(): array {
        $u = Auditoria::usuarioSesion();
        if (!$u) {
            $_SESSION['error'] = "No se pudo verificar tu usuario. Cierra sesión, vuelve a ingresar e intenta de nuevo. No se registró nada.";
            $this->redirect('/login');
        }
        return $u;
    }

    private function auditar($accion, $entidad, $entidadId, $antes, $despues): void {
        try {
            (new Auditoria())->registrar($accion, $entidad, $entidadId, $antes, $despues);
        } catch (Throwable $e) {
            error_log('[Auditoria] ' . $e->getMessage());
        }
    }
}
