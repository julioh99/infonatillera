<?php
// app/Controllers/ActividadController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../Models/Actividad.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Auditoria.php';

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

        $usuario = $this->exigirUsuarioSesion(false);

        $nombre = trim($_POST['nombre_actividad'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha = trim($_POST['fecha_actividad'] ?? date('Y-m-d'));
        $ingresos = Money::parse($_POST['ingresos_totales'] ?? null, 0.0);
        $gastos = Money::parse($_POST['gastos_totales'] ?? null, 0.0);
        $cuotaBase = Money::parse($_POST['cuota_por_socio'] ?? null, 0.0);

        $checkedParticipantes = isset($_POST['participantes']) && is_array($_POST['participantes']) ? $_POST['participantes'] : [];
        $cuotasIndividuales = isset($_POST['cuotas_individuales']) && is_array($_POST['cuotas_individuales']) ? $_POST['cuotas_individuales'] : [];

        if ($nombre === '' || $fecha === '') {
            $_SESSION['error'] = "Ingresa el nombre y fecha de la actividad.";
            $this->redirect('/admin/actividades');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $_SESSION['error'] = "La fecha de la actividad no es válida.";
            $this->redirect('/admin/actividades');
        }
        $max = (float)Money::config('controladores.monto_maximo', 100000000);
        if ($ingresos === null || $gastos === null || $cuotaBase === null) {
            $_SESSION['error'] = "Revisa los valores de ingresos, gastos y cuota: deben ser números positivos, por ejemplo 150.000.";
            $this->redirect('/admin/actividades');
        }
        if ($ingresos > $max || $gastos > $max || $cuotaBase > $max) {
            $_SESSION['error'] = "Alguno de los valores supera el máximo permitido ($" . Money::format($max) . ").";
            $this->redirect('/admin/actividades');
        }

        $participantesCuotas = [];
        foreach ($checkedParticipantes as $sId) {
            $sId = (int)$sId;
            if ($sId <= 0) continue;
            $rawIndiv = isset($cuotasIndividuales[$sId]) ? $cuotasIndividuales[$sId] : $cuotaBase;
            $montoIndiv = Money::parse($rawIndiv, 0.0);
            if ($montoIndiv === null || $montoIndiv > $max) {
                $_SESSION['error'] = "La cuota de uno de los participantes no es válida.";
                $this->redirect('/admin/actividades');
            }
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
            'creado_por_usuario_id' => (int)$usuario['id']
        ], $participantesCuotas);

        if ($ok) {
            $this->auditar('CREAR_ACTIVIDAD', 'natillera_actividades', null, null, [
                'nombre' => $nombre, 'fecha' => $fecha, 'ingresos' => $ingresos, 'gastos' => $gastos,
                'cuota_por_socio' => $cuotaBase, 'participantes' => count($participantesCuotas)
            ]);
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

        $usuario = $this->exigirUsuarioSesion(false);

        $participanteId = (int)($_POST['participante_id'] ?? 0);
        $montoPagado = Money::parse($_POST['monto_pagado'] ?? null);
        $max = (float)Money::config('controladores.monto_maximo', 100000000);

        if ($participanteId <= 0) {
            $_SESSION['error'] = "Participante inválido.";
            $this->redirect('/admin/actividades');
        }
        if ($montoPagado === null || $montoPagado > $max) {
            $_SESSION['error'] = "El valor pagado no es válido. Escribe un monto positivo, por ejemplo 50.000.";
            $this->redirect('/admin/actividades');
        }

        $actividadModel = new Actividad();
        $auditoria = new Auditoria();
        $auditId = 0;
        $antes = $auditoria->fila('natillera_actividad_participantes', $participanteId);
        // Si el nuevo valor es menor o igual al actual el modelo SOBRESCRIBE el pago: se archiva antes
        if ($antes && $montoPagado <= (float)$antes['monto_pagado']) {
            try {
                $auditId = $auditoria->archivarAntesDeBorrar('natillera_actividad_participantes', $participanteId,
                    ['natillera_abonos_actividades' => 'actividad_participante_id'], 'ACTUALIZAR_PAGO_PARTICIPANTE');
            } catch (Throwable $e) {
                $_SESSION['error'] = "No se pudo guardar el respaldo del pago; no se hizo ningún cambio.";
                $this->redirect('/admin/actividades');
            }
        }

        $ok = $actividadModel->actualizarPagoParticipante($participanteId, $montoPagado, (int)$usuario['id']);

        if ($ok) {
            if ($auditId) $auditoria->completar($auditId, $auditoria->fila('natillera_actividad_participantes', $participanteId));
            $_SESSION['success'] = "Pago de la actividad actualizado correctamente.";
        } else {
            if ($auditId) $auditoria->descartar($auditId);
            $_SESSION['error'] = "No se pudo actualizar el pago.";
        }

        $this->redirect('/admin/actividades');
    }

    public function registrarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $isAjax = $this->esAjax();

        $usuario = $this->exigirUsuarioSesion($isAjax);

        $participanteId = (int)($_POST['participante_id'] ?? 0);
        list($montoAbono, $errMonto) = Money::validarPositivo($_POST['monto_abono'] ?? null, 'El abono');
        $observacion = trim($_POST['observacion'] ?? '');
        if (strlen($observacion) > 255) $observacion = substr($observacion, 0, 255);

        if ($participanteId <= 0 || $errMonto !== null) {
            $errMsg = $errMonto !== null ? $errMonto : "Selecciona un participante válido.";
            $this->responder($isAjax, false, $errMsg);
        }

        $actividadModel = new Actividad();
        try {
            $ok = $actividadModel->registrarAbono($participanteId, $montoAbono, (int)$usuario['id'], $observacion);
            if ($ok) {
                $this->auditar('REGISTRAR_ABONO_ACTIVIDAD', 'natillera_abonos_actividades', null, null, [
                    'participante_id' => $participanteId, 'monto' => $montoAbono, 'observacion' => $observacion
                ]);
                $this->responder($isAjax, true, "Abono de $" . Money::format($montoAbono) . " COP registrado exitosamente.");
            }
            $this->responder($isAjax, false, "No se pudo registrar el abono.");
        } catch (PDOException $e) {
            error_log('[ActividadController::registrarAbono] ' . $e->getMessage());
            $this->responder($isAjax, false, "No se pudo registrar el abono. Intenta de nuevo.");
        } catch (Exception $e) {
            // Mensajes de regla de negocio del modelo (ej. el abono excede la cuota)
            $this->responder($isAjax, false, $e->getMessage());
        }
    }

    public function eliminarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria Actividades']);

        $isAjax = $this->esAjax();

        $this->exigirUsuarioSesion($isAjax);

        $abonoId = (int)($_POST['abono_id'] ?? 0);

        if ($abonoId <= 0) {
            $this->responder($isAjax, false, "Abono inválido.");
        }

        $actividadModel = new Actividad();
        $auditoria = new Auditoria();
        try {
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_abonos_actividades', $abonoId, [], 'ELIMINAR_ABONO_ACTIVIDAD');
        } catch (Throwable $e) {
            $this->responder($isAjax, false, "No se encontró el abono o no se pudo guardar su respaldo; no se eliminó nada.");
        }

        $ok = $actividadModel->eliminarAbono($abonoId);

        if ($ok) {
            $this->responder($isAjax, true, "Abono eliminado correctamente y saldo recalculado.");
        }
        $auditoria->descartar($auditId);
        $this->responder($isAjax, false, "No se pudo eliminar el abono.");
    }

    // ------------------------------------------------------------------

    private function esAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['is_ajax']);
    }

    /** Responde JSON (ajax) o redirige con mensaje; termina la ejecucion. */
    private function responder(bool $isAjax, bool $exito, string $mensaje): void {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $exito, 'message' => $mensaje]);
            exit;
        }
        $_SESSION[$exito ? 'success' : 'error'] = $mensaje;
        $this->redirect('/admin/actividades');
    }

    /** Usuario de la sesion verificado en la BD; si no existe, aborta sin tocar datos. */
    private function exigirUsuarioSesion(bool $isAjax): array {
        $u = Auditoria::usuarioSesion();
        if (!$u) {
            $this->responder($isAjax, false, "No se pudo verificar tu usuario. Cierra sesión, vuelve a ingresar e intenta de nuevo. No se registró nada.");
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
