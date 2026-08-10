<?php
// app/Controllers/PrestamoController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Prestamo.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/EntregaBeneficio.php';

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

        $socioDeudorId = (int)($_POST['socio_deudor_id'] ?? 0);
        $nombreReferencia = trim($_POST['nombre_referencia'] ?? '');
        $monto = (float)str_replace('.', '', $_POST['monto_prestado'] ?? 0);
        $tasa = (float)($_POST['tasa_interes_mensual'] ?? 10.00);

        if ($socioDeudorId <= 0 || $monto <= 0) {
            $_SESSION['error'] = "Por favor selecciona un socio deudor y un monto válido.";
            $this->redirect('/admin/prestamos');
        }

        // Validación de tope si no es Secretaria General o Presidente
        $usuarioModel = new Usuario();
        $deudor = $usuarioModel->getSocioById($socioDeudorId);
        $tope = (float)($deudor['tope_prestamo_personalizado'] ?? 2000000.00);

        if ($monto > $tope && !in_array($_SESSION['usuario']['rol_nombre'], ['Presidente', 'Secretaria General'])) {
            $_SESSION['error'] = "El monto solicitado excede el tope permitido del socio ($" . number_format($tope, 0, ',', '.') . "). Requiere autorización de Secretaria General o Presidente.";
            $this->redirect('/admin/prestamos');
        }

        $reunionId = !empty($_POST['reunion_id']) ? (int)$_POST['reunion_id'] : null;

        $prestamoModel = new Prestamo();
        $prestamoId = $prestamoModel->crearPrestamo([
            'socio_deudor_id' => $socioDeudorId,
            'reunion_id' => $reunionId,
            'nombre_referencia' => $nombreReferencia,
            'monto_prestado' => $monto,
            'tasa_interes_mensual' => $tasa,
            'tipo_prestamo' => 'DIRECTO',
            'es_autoprestamo' => 0
        ]);

        if ($prestamoId) {
            // Verificar si enviaron Firma Digital o Foto Evidencia en la creación directa
            $firmasDir = __DIR__ . '/../../public/uploads/firmas';
            $evidenciasDir = __DIR__ . '/../../public/uploads/evidencias';

            if (!is_dir($firmasDir)) @mkdir($firmasDir, 0777, true);
            if (!is_dir($evidenciasDir)) @mkdir($evidenciasDir, 0777, true);

            $firmaPath = null;
            if (!empty($_POST['firma_base64'])) {
                $firmaData = $_POST['firma_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $firmaData, $type)) {
                    $data = substr($firmaData, strpos($firmaData, ',') + 1);
                    $data = base64_decode($data);
                    if ($data !== false) {
                        $fileNameFirma = "firma_{$socioDeudorId}_PRESTAMO_" . time() . ".png";
                        @file_put_contents("{$firmasDir}/{$fileNameFirma}", $data);
                        $firmaPath = "/uploads/firmas/{$fileNameFirma}";
                    }
                }
            }

            $fotoPath = null;
            if (!empty($_FILES['foto_evidencia']['tmp_name']) && is_uploaded_file($_FILES['foto_evidencia']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['foto_evidencia']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $fileNameFoto = "foto_{$socioDeudorId}_PRESTAMO_" . time() . ".{$ext}";
                    if (@move_uploaded_file($_FILES['foto_evidencia']['tmp_name'], "{$evidenciasDir}/{$fileNameFoto}")) {
                        $fotoPath = "/uploads/evidencias/{$fileNameFoto}";
                    }
                }
            }

            if (!empty($firmaPath) || !empty($fotoPath)) {
                $entregaModel = new EntregaBeneficio();
                $reunionFinalId = $reunionId;
                if (!$reunionFinalId) {
                    $reunionModel = new Reunion();
                    $rAct = $reunionModel->getReunionActual();
                    $reunionFinalId = $rAct ? (int)$rAct['id'] : 1;
                }

                $entregadoPor = (int)($_SESSION['usuario']['id'] ?? 1);
                $entregaModel->registrarEntrega([
                    'reunion_id' => $reunionFinalId,
                    'socio_id' => $socioDeudorId,
                    'prestamo_id' => $prestamoId,
                    'tipo_beneficio' => 'PRESTAMO',
                    'monto_entregado' => $monto,
                    'firma_digital_path' => $firmaPath,
                    'foto_evidencia_path' => $fotoPath,
                    'entregado_por_usuario_id' => $entregadoPor
                ]);
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
        $monto = (float)str_replace('.', '', $_POST['monto_prestado'] ?? 0);
        $tasa = (float)($_POST['tasa_interes_mensual'] ?? 10.0);
        $nombreReferencia = trim($_POST['nombre_referencia'] ?? '');
        $estado = trim($_POST['estado'] ?? 'ACTIVO');

        if ($id <= 0 || $socioDeudorId <= 0 || $monto <= 0) {
            $_SESSION['error'] = "Datos de préstamo inválidos para actualizar.";
            $this->redirect('/admin/prestamos');
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->actualizarPrestamo($id, [
            'socio_deudor_id' => $socioDeudorId,
            'reunion_id' => $reunionId,
            'monto_prestado' => $monto,
            'tasa_interes_mensual' => $tasa,
            'nombre_referencia' => $nombreReferencia,
            'estado' => $estado
        ]);

        if ($ok) {
            $_SESSION['success'] = "Préstamo N° {$id} actualizado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar el préstamo.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function abono(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $prestamoId = (int)($_POST['prestamo_id'] ?? 0);
        $reunionId = !empty($_POST['reunion_id']) ? (int)$_POST['reunion_id'] : null;
        $montoCapital = (float)str_replace('.', '', $_POST['monto_capital_pagado'] ?? 0);
        $montoInteres = (float)str_replace('.', '', $_POST['monto_interes_pagado'] ?? 0);

        if ($prestamoId <= 0 || ($montoCapital <= 0 && $montoInteres <= 0)) {
            $_SESSION['error'] = "Ingresa al menos un valor a abonar en capital o interés.";
            $this->redirect('/admin/prestamos');
        }

        $usuarioModel = new Usuario();
        $usuarioId = (int)($_SESSION['usuario']['id'] ?? 1);
        if (!$usuarioModel->getSocioById($usuarioId)) {
            $todosSocios = $usuarioModel->getAllSocios();
            $usuarioId = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->registrarAbono($prestamoId, $montoCapital, $montoInteres, $usuarioId, $reunionId);

        if ($ok) {
            $_SESSION['success'] = "Abono registrado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo registrar el abono.";
        }

        $this->redirect('/admin/prestamos');
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
            'abonos' => $abonos
        ]);
    }

    public function actualizarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $abonoId = (int)($_POST['abono_id'] ?? 0);
        $montoCapital = (float)str_replace('.', '', $_POST['monto_capital_pagado'] ?? 0);
        $montoInteres = (float)str_replace('.', '', $_POST['monto_interes_pagado'] ?? 0);
        $fechaAbono = trim($_POST['fecha_abono'] ?? '');

        if ($abonoId <= 0) {
            $_SESSION['error'] = "Abono no válido.";
            $this->redirect('/admin/prestamos');
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->actualizarAbono($abonoId, $montoCapital, $montoInteres, $fechaAbono);

        if ($ok) {
            $_SESSION['success'] = "Cuota/Abono actualizado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar el abono.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function eliminarAbono(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $abonoId = (int)($_POST['abono_id'] ?? 0);

        if ($abonoId <= 0) {
            $_SESSION['error'] = "Abono no válido.";
            $this->redirect('/admin/prestamos');
        }

        $prestamoModel = new Prestamo();
        $ok = $prestamoModel->eliminarAbono($abonoId);

        if ($ok) {
            $_SESSION['success'] = "Cuota/Abono eliminado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo eliminar el abono.";
        }

        $this->redirect('/admin/prestamos');
    }

    public function actualizarTope(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $socioId = (int)($_POST['socio_id'] ?? 0);
        $nuevoTope = (float)str_replace('.', '', $_POST['nuevo_tope'] ?? 2000000.00);

        if ($socioId <= 0 || $nuevoTope <= 0) {
            $_SESSION['error'] = "Datos de tope de crédito no válidos.";
            $this->redirect('/admin/prestamos');
        }

        $usuarioModel = new Usuario();
        $ok = $usuarioModel->updateTopePrestamo($socioId, $nuevoTope);

        if ($ok) {
            $_SESSION['success'] = "Tope de préstamo del socio actualizado a $" . number_format($nuevoTope, 0, ',', '.') . " COP.";
        } else {
            $_SESSION['error'] = "Error al actualizar el tope del socio.";
        }

        $this->redirect('/admin/prestamos');
    }
}
