<?php
// app/Controllers/PrestamoController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Prestamo.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/EntregaBeneficio.php';
require_once __DIR__ . '/../Models/Auditoria.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../../core/Evidencia.php';

class PrestamoController extends Controller {

    public function index(): void {
        $user = $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $prestamoModel = new Prestamo();
        $usuarioModel = new Usuario();
        $reunionModel = new Reunion();

        $prestamos = $prestamoModel->getTodosPrestamos();
        $socios = $usuarioModel->getAllSocios();
        $reuniones = $reunionModel->getReuniones();
        $reunionActual = $reunionModel->getReunionActual();

        $this->render('admin/prestamos', [
            'prestamos' => $prestamos,
            'socios' => $socios,
            'reuniones' => $reuniones,
            'reunionActual' => $reunionActual,
            'userRole' => $user['rol_nombre']
        ]);
    }

    public function guardar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        // Sin usuario valido en sesion no se registra nada (nunca a nombre de otro)
        $usuarioSesion = $this->exigirUsuarioSesion();

        $socioDeudorId = (int)($_POST['socio_deudor_id'] ?? 0);
        $nombreReferencia = trim($_POST['nombre_referencia'] ?? '');
        list($monto, $errMonto) = Money::validarPositivo($_POST['monto_prestado'] ?? null, 'El monto del préstamo');
        list($tasa, $errTasa) = $this->resolverTasa($_POST['tasa_interes_mensual'] ?? null, null);

        if ($socioDeudorId <= 0) {
            $_SESSION['error'] = "Por favor selecciona un socio deudor.";
            $this->redirectConBusqueda();
        }
        if ($errMonto !== null) {
            $_SESSION['error'] = $errMonto;
            $this->redirectConBusqueda();
        }
        if ($errTasa !== null) {
            $_SESSION['error'] = $errTasa;
            $this->redirectConBusqueda();
        }

        // Validación de tope si no es Secretaria General o Presidente
        $usuarioModel = new Usuario();
        $deudor = $usuarioModel->getSocioById($socioDeudorId);
        $tope = (float)($deudor['tope_prestamo_personalizado'] ?? Money::config('controladores.tope_socio_defecto', Money::config('prestamo.tope_defecto', 2000000)));

        if ($monto > $tope && !in_array($_SESSION['usuario']['rol_nombre'], ['Presidente', 'Secretaria General'])) {
            $_SESSION['error'] = "El monto solicitado excede el tope permitido del socio ($" . Money::format($tope) . "). Requiere autorización de Secretaria General o Presidente.";
            $this->redirectConBusqueda();
        }

        $reunionId = !empty($_POST['reunion_id']) ? (int)$_POST['reunion_id'] : null;
        $fechaPrestamo = !empty($_POST['fecha_prestamo']) ? trim($_POST['fecha_prestamo']) : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPrestamo)) {
            $_SESSION['error'] = "La fecha del préstamo no es válida.";
            $this->redirectConBusqueda();
        }

        $prestamoModel = new Prestamo();

        // La reunion del egreso: la indicada o la actual (nunca se asume una reunion arbitraria)
        $reunionFinalId = $reunionId;
        if (!$reunionFinalId) {
            $reunionModel = new Reunion();
            $rAct = $reunionModel->getReunionActual();
            $reunionFinalId = $rAct ? (int)$rAct['id'] : null;
        }
        if (!$reunionFinalId) {
            $_SESSION['error'] = "No hay una reunión activa a la cual asociar el préstamo. Selecciona una reunión.";
            $this->redirectConBusqueda();
        }

        // Evidencias opcionales: se validan (imagen real, tamano) antes de crear nada
        try {
            $firmaBytes = Evidencia::validarFirma();
            $foto = Evidencia::validarFoto();
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        try {
            // El prestamo queda ligado a la reunion donde se registra su egreso
            $prestamoId = $prestamoModel->crearPrestamo([
                'socio_deudor_id' => $socioDeudorId,
                'reunion_id' => $reunionFinalId,
                'fecha_prestamo' => $fechaPrestamo,
                'nombre_referencia' => $nombreReferencia,
                'monto_prestado' => $monto,
                'tasa_interes_mensual' => $tasa,
                'tipo_prestamo' => 'DIRECTO',
                'es_autoprestamo' => 0
            ]);
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($prestamoId) {
            // Firma Digital y Foto Evidencia son opcionales; el egreso se registra siempre
            $firmaPath = null;
            $fotoPath = null;
            $escritos = [];
            try {
                if ($firmaBytes !== null) {
                    $firmaPath = Evidencia::guardarFirma($firmaBytes, $socioDeudorId, 'PRESTAMO', $escritos);
                }
                if ($foto !== null) {
                    $fotoPath = Evidencia::guardarFoto($foto, $socioDeudorId, 'PRESTAMO', $escritos);
                }
            } catch (RuntimeException $e) {
                // No se pudo guardar un archivo: el prestamo sigue, solo sin esa evidencia
                foreach ($escritos as $a) { @unlink($a); }
                $firmaPath = null;
                $fotoPath = null;
            }

            try {
                // Idempotente por prestamo_id: no duplica si la entrega ya existia
                $prestamoModel->asegurarEntregaPrestamo(
                    (int)$prestamoId, (int)$reunionFinalId, $socioDeudorId, $monto,
                    $firmaPath, $fotoPath, (int)$usuarioSesion['id']
                );
            } catch (Throwable $e) {
                // Sin egreso el cierre quedaria descuadrado: se revierte el prestamo recien creado
                try { $prestamoModel->eliminarPrestamo((int)$prestamoId); } catch (Throwable $e2) {}
                $_SESSION['error'] = "No se pudo registrar el préstamo ni su egreso. Intenta de nuevo.";
                $this->redirect('/admin/prestamos');
            }

            if (!empty($firmaPath) || !empty($fotoPath)) {
                $_SESSION['success'] = "Préstamo N° {$prestamoId} registrado exitosamente con firma y evidencia.";
            } else {
                $_SESSION['success'] = "Préstamo N° {$prestamoId} registrado exitosamente.";
            }
        } else {
            $_SESSION['error'] = "No se pudo registrar el préstamo.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function actualizar(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $id = (int)($_POST['prestamo_id'] ?? 0);
        $socioDeudorId = (int)($_POST['socio_deudor_id'] ?? 0);
        $reunionId = !empty($_POST['reunion_id']) ? (int)$_POST['reunion_id'] : null;
        $this->exigirUsuarioSesion();
        list($monto, $errMonto) = Money::validarPositivo($_POST['monto_prestado'] ?? null, 'El monto del préstamo');
        $nombreReferencia = trim($_POST['nombre_referencia'] ?? '');
        $fechaPrestamo = !empty($_POST['fecha_prestamo']) ? trim($_POST['fecha_prestamo']) : date('Y-m-d');
        $estado = trim($_POST['estado'] ?? 'ACTIVO');

        if ($id <= 0 || $socioDeudorId <= 0) {
            $_SESSION['error'] = "Datos de préstamo inválidos para actualizar.";
            $this->redirectConBusqueda();
        }
        if ($errMonto !== null) {
            $_SESSION['error'] = $errMonto;
            $this->redirectConBusqueda();
        }

        $prestamoModel = new Prestamo();
        $antes = $prestamoModel->getPrestamoById($id);
        if (!$antes) {
            $_SESSION['error'] = "El préstamo no existe.";
            $this->redirectConBusqueda();
        }
        // Solo el Presidente fija una tasa distinta de la por defecto; en una edicion los demas conservan la tasa vigente
        list($tasa, $errTasa) = $this->resolverTasa($_POST['tasa_interes_mensual'] ?? null, (float)$antes['tasa_interes_mensual']);
        if ($errTasa !== null) {
            $_SESSION['error'] = $errTasa;
            $this->redirectConBusqueda();
        }

        $auditoria = new Auditoria();
        try {
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_prestamos', $id, [], 'ACTUALIZAR_PRESTAMO');
        } catch (Throwable $e) {
            $_SESSION['error'] = "No se pudo guardar el respaldo del préstamo; no se hizo ningún cambio.";
            $this->redirectConBusqueda();
        }
        try {
            $ok = $prestamoModel->actualizarPrestamo($id, [
                'socio_deudor_id' => $socioDeudorId,
                'reunion_id' => $reunionId,
                'fecha_prestamo' => $fechaPrestamo,
                'monto_prestado' => $monto,
                'tasa_interes_mensual' => $tasa,
                'nombre_referencia' => $nombreReferencia,
                'estado' => $estado
            ]);
        } catch (InvalidArgumentException $e) {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($ok) {
            $auditoria->completar($auditId, $auditoria->fila('natillera_prestamos', $id));
            $_SESSION['success'] = "Préstamo N° {$id} actualizado correctamente.";
        } else {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = "No se pudo actualizar el préstamo.";
        }

        $this->redirectConBusqueda();
    }

    public function abono(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $prestamoId = (int)($_POST['prestamo_id'] ?? 0);
        $reunionId = !empty($_POST['reunion_id']) ? (int)$_POST['reunion_id'] : null;
        $montoCapital = Money::parse($_POST['monto_capital_pagado'] ?? null, 0.0);
        $montoInteres = Money::parse($_POST['monto_interes_pagado'] ?? null, 0.0);

        if ($montoCapital === null || $montoInteres === null) {
            $_SESSION['error'] = "Los montos del abono no son válidos (no pueden ser negativos ni tener formato incorrecto).";
            $this->redirectConBusqueda();
        }
        $maxMonto = (float)Money::config('controladores.monto_maximo', 100000000);
        if ($montoCapital > $maxMonto || $montoInteres > $maxMonto) {
            $_SESSION['error'] = "El valor del abono supera el máximo permitido ($" . Money::format($maxMonto) . ").";
            $this->redirectConBusqueda();
        }

        if ($prestamoId <= 0 || ($montoCapital <= 0 && $montoInteres <= 0)) {
            $_SESSION['error'] = "Ingresa al menos un valor a abonar en capital o interés.";
            $this->redirectConBusqueda();
        }

        $usuarioId = (int)$this->exigirUsuarioSesion()['id'];

        $prestamoModel = new Prestamo();
        try {
            $ok = $prestamoModel->registrarAbono($prestamoId, $montoCapital, $montoInteres, $usuarioId, $reunionId);
        } catch (InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($ok) {
            $_SESSION['success'] = "Abono registrado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo registrar el abono.";
        }

        $this->redirectConBusqueda();
    }

    public function obtenerAbonos(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);
        $prestamoId = (int)($_GET['prestamo_id'] ?? 0);

        header('Content-Type: application/json');
        if ($prestamoId <= 0) {
            echo json_encode(['success' => false, 'abonos' => []]);
            return;
        }

        $prestamoModel = new Prestamo();
        $abonos = $prestamoModel->getAbonosPorPrestamo($prestamoId);
        $prestamo = $prestamoModel->getPrestamoById($prestamoId);

        echo json_encode([
            'success' => true,
            'prestamo' => $prestamo,
            'abonos' => $abonos,
            'interes' => $prestamoModel->calcularInteresPrestamo($prestamoId)
        ]);
    }

    public function actualizarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $abonoId = (int)($_POST['abono_id'] ?? 0);
        $this->exigirUsuarioSesion();
        $montoCapital = Money::parse($_POST['monto_capital_pagado'] ?? null, 0.0);
        $montoInteres = Money::parse($_POST['monto_interes_pagado'] ?? null, 0.0);
        $fechaAbono = trim($_POST['fecha_abono'] ?? '');

        if ($montoCapital === null || $montoInteres === null) {
            $_SESSION['error'] = "Los montos del abono no son válidos (no pueden ser negativos ni tener formato incorrecto).";
            $this->redirectConBusqueda();
        }
        $maxMonto = (float)Money::config('controladores.monto_maximo', 100000000);
        if ($montoCapital > $maxMonto || $montoInteres > $maxMonto) {
            $_SESSION['error'] = "El valor del abono supera el máximo permitido ($" . Money::format($maxMonto) . ").";
            $this->redirectConBusqueda();
        }

        if ($abonoId <= 0) {
            $_SESSION['error'] = "Abono no válido.";
            $this->redirectConBusqueda();
        }

        $prestamoModel = new Prestamo();
        $auditoria = new Auditoria();
        try {
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_abonos_prestamos', $abonoId, [], 'ACTUALIZAR_ABONO');
        } catch (Throwable $e) {
            $_SESSION['error'] = "No se encontró el abono o no se pudo guardar su respaldo; no se hizo ningún cambio.";
            $this->redirectConBusqueda();
        }
        try {
            $ok = $prestamoModel->actualizarAbono($abonoId, $montoCapital, $montoInteres, $fechaAbono);
        } catch (InvalidArgumentException $e) {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($ok) {
            $auditoria->completar($auditId, $auditoria->fila('natillera_abonos_prestamos', $abonoId));
            $_SESSION['success'] = "Cuota/Abono actualizado correctamente.";
        } else {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = "No se pudo actualizar el abono.";
        }

        $this->redirectConBusqueda();
    }

    public function eliminarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $abonoId = (int)($_POST['abono_id'] ?? 0);

        if ($abonoId <= 0) {
            $_SESSION['error'] = "Abono no válido.";
            $this->redirectConBusqueda();
        }

        $this->exigirUsuarioSesion();
        $prestamoModel = new Prestamo();
        $auditoria = new Auditoria();
        try {
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_abonos_prestamos', $abonoId, [], 'ELIMINAR_ABONO');
        } catch (Throwable $e) {
            $_SESSION['error'] = "No se encontró el abono o no se pudo guardar su respaldo; no se eliminó nada.";
            $this->redirectConBusqueda();
        }
        try {
            $ok = $prestamoModel->eliminarAbono($abonoId);
        } catch (InvalidArgumentException $e) {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($ok) {
            $_SESSION['success'] = "Cuota/Abono eliminado correctamente.";
        } else {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = "No se pudo eliminar el abono.";
        }

        $this->redirectConBusqueda();
    }

    public function actualizarTope(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $socioId = (int)($_POST['socio_id'] ?? 0);
        list($nuevoTope, $errTope) = Money::validarPositivo($_POST['nuevo_tope'] ?? null, 'El tope de crédito');

        if ($socioId <= 0 || $errTope !== null) {
            $_SESSION['error'] = $errTope !== null ? $errTope : "Datos de tope de crédito no válidos.";
            $this->redirectConBusqueda();
        }

        $usuarioModel = new Usuario();
        $ok = $usuarioModel->updateTopePrestamo($socioId, $nuevoTope);

        if ($ok) {
            $_SESSION['success'] = "Tope de préstamo del socio actualizado a $" . Money::format($nuevoTope) . " COP.";
        } else {
            $_SESSION['error'] = "Error al actualizar el tope del socio.";
        }

        $this->redirectConBusqueda();
    }

    public function eliminar(): void {
        $this->requireRole(['Presidente']);

        $id = (int)($_POST['prestamo_id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['error'] = "Identificador de préstamo no válido para eliminar.";
            $this->redirectConBusqueda();
        }

        $this->exigirUsuarioSesion();
        $prestamoModel = new Prestamo();
        $auditoria = new Auditoria();
        // Instantanea del prestamo + abonos + entregas (+ cuotas de ahorro que lo referencian) ANTES del borrado fisico
        try {
            $auditId = $auditoria->archivarAntesDeBorrar('natillera_prestamos', $id, [
                'natillera_abonos_prestamos' => 'prestamo_id',
                'natillera_entregas_beneficios' => 'prestamo_id',
                'natillera_ahorros_cuotas' => 'prestamo_id_asociado',
            ], 'ELIMINAR_PRESTAMO');
        } catch (Throwable $e) {
            $_SESSION['error'] = "No se encontró el préstamo o no se pudo guardar su respaldo; no se eliminó nada.";
            $this->redirectConBusqueda();
        }
        try {
            $ok = $prestamoModel->eliminarPrestamo($id);
        } catch (InvalidArgumentException $e) {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = $e->getMessage();
            $this->redirectConBusqueda();
        }

        if ($ok) {
            $_SESSION['success'] = "Préstamo N° {$id} eliminado correctamente de la natillera.";
        } else {
            $auditoria->descartar($auditId);
            $_SESSION['error'] = "No se pudo eliminar el préstamo.";
        }

        $this->redirectConBusqueda();
    }

    /**
     * Usuario de la sesion verificado en la BD. Si no existe se aborta con mensaje claro y SIN tocar datos
     * (jamas se registra la accion a nombre de otro usuario).
     */
    private function exigirUsuarioSesion(): array {
        $u = Auditoria::usuarioSesion();
        if (!$u) {
            $_SESSION['error'] = "No se pudo verificar tu usuario. Cierra sesión, vuelve a ingresar e intenta de nuevo. No se registró nada.";
            $this->redirect('/login');
        }
        return $u;
    }

    /**
     * Tasa mensual a usar. Solo el Presidente puede fijar una tasa distinta de la por defecto.
     * $tasaVigente: en una edicion, tasa actual del prestamo (la conservan quienes no son Presidente).
     * Devuelve [tasa|null, error|null].
     */
    private function resolverTasa($enviada, $tasaVigente): array {
        $defecto = (float)Money::config('prestamo.tasa_mensual_defecto', 10.0);
        $min = (float)Money::config('controladores.tasa_prestamo_min', 0.0);
        $max = (float)Money::config('controladores.tasa_prestamo_max', 20.0);

        $rol = $_SESSION['usuario']['rol_nombre'] ?? '';
        if ($rol !== 'Presidente') {
            return [$tasaVigente !== null ? $tasaVigente : $defecto, null];
        }
        if ($enviada === null || trim((string)$enviada) === '') {
            return [$tasaVigente !== null ? $tasaVigente : $defecto, null];
        }
        $t = str_replace(',', '.', trim((string)$enviada));
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $t)) {
            return [null, "La tasa de interés no es válida. Escribe un porcentaje mensual entre {$min} y {$max}."];
        }
        $t = (float)$t;
        if ($t < $min || $t > $max) {
            return [null, "La tasa de interés debe estar entre {$min}% y {$max}% mensual."];
        }
        return [$t, null];
    }
    private function redirectConBusqueda(string $defaultPath = '/admin/prestamos'): void {
        $search = trim($_POST['search_query'] ?? ($_GET['search_query'] ?? ($_GET['search'] ?? '')));
        if ($search !== '') {
            $this->redirect($defaultPath . '?search=' . urlencode($search));
        } else {
            $this->redirect($defaultPath);
        }
    }
}
