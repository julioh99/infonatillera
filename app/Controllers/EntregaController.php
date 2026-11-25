<?php
// app/Controllers/EntregaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../../core/Evidencia.php';
require_once __DIR__ . '/../Models/FondoBeneficio.php';
require_once __DIR__ . '/../Models/EntregaBeneficio.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/CajaGlobal.php';

class EntregaController extends Controller {

    /** Monto por defecto de cada tipo de entrega (config). */
    private function montoPorDefecto(string $tipo): float {
        if ($tipo === 'RONDA') { return (float)Money::config('ronda.premio', 300000); }
        if ($tipo === 'RIFA') { return (float)Money::config('rifa.premio', 150000); }
        return (float)Money::config('prestamo.monto_defecto', 500000);
    }

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $fondoModel = new FondoBeneficio();
        $entregaModel = new EntregaBeneficio();
        $reunionModel = new Reunion();

        $resumenFondos = $fondoModel->getResumenFondos();
        $cronograma = $fondoModel->getCronograma();
        $entregas = $entregaModel->getTodasEntregas();
        $reuniones = $reunionModel->getReuniones();
        $sociosPrestamo = $entregaModel->getSociosPendientes('PRESTAMO');
        $sociosRonda = $entregaModel->getSociosPendientes('RONDA');
        $sociosRifa = $entregaModel->getSociosPendientes('RIFA');   // ya excluye a quienes ganaron una rifa

        $tipoQuery = trim($_GET['tipo'] ?? 'PRESTAMO');
        if (!in_array($tipoQuery, EntregaBeneficio::tiposValidos(), true)) {
            $tipoQuery = 'PRESTAMO';
        }
        $socioIdQuery = (int)($_GET['socio_id'] ?? 0);
        $montoQuery = (float)($_GET['monto'] ?? 0);
        $reunionIdQuery = (int)($_GET['reunion_id'] ?? 0);
        $prestamoIdQuery = !empty($_GET['prestamo_id']) ? (int)$_GET['prestamo_id'] : null;

        if ($reunionIdQuery <= 0) {
            $reunionAct = $reunionModel->getReunionActual();
            if ($reunionAct) {
                $reunionIdQuery = (int)$reunionAct['id'];
            }
        }

        $this->render('admin/entregas_beneficios', [
            'resumenFondos' => $resumenFondos,
            'cronograma' => $cronograma,
            'entregas' => $entregas,
            'reuniones' => $reuniones,
            'sociosPrestamo' => $sociosPrestamo,
            'sociosRonda' => $sociosRonda,
            'sociosRifa' => $sociosRifa,
            'siguienteTurno' => $sociosRonda ? $sociosRonda[0] : null,
            'ganadoresRonda' => $entregaModel->getGanadores('RONDA'),
            'ganadoresRifa' => $entregaModel->getGanadores('RIFA'),
            'cupos' => FondoBeneficio::cupos(),
            'montosDefecto' => [
                'PRESTAMO' => $this->montoPorDefecto('PRESTAMO'),
                'RONDA' => $this->montoPorDefecto('RONDA'),
                'RIFA' => $this->montoPorDefecto('RIFA'),
            ],
            'tipoQuery' => $tipoQuery,
            'socioIdQuery' => $socioIdQuery,
            'montoQuery' => $montoQuery,
            'reunionIdQuery' => $reunionIdQuery,
            'prestamoIdQuery' => $prestamoIdQuery
        ]);
    }

    public function guardar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        $socioId = (int)($_POST['socio_id'] ?? 0);
        $prestamoId = !empty($_POST['prestamo_id']) ? (int)$_POST['prestamo_id'] : null;

        // 3.8: tipo de beneficio solo de la lista blanca
        $tipoBeneficio = trim($_POST['tipo_beneficio'] ?? 'PRESTAMO');
        if (!in_array($tipoBeneficio, EntregaBeneficio::tiposValidos(), true)) {
            $_SESSION['error'] = "Tipo de entrega no válido.";
            $this->redirect('/admin/entregas');
        }

        $montoStr = trim((string)($_POST['monto_entregado'] ?? ''));
        $monto = Money::parse($montoStr, 0.0);
        if ($monto === null) {
            $_SESSION['error'] = "El monto entregado no es válido. Escribe un valor como 300.000.";
            $this->redirect('/admin/entregas');
        }
        if ($monto <= 0) {
            $monto = $this->montoPorDefecto($tipoBeneficio);   // 2.7: valores por defecto desde config
        }
        $montoMax = (float)Money::config('controladores.monto_maximo', 100000000);
        if ($monto > $montoMax) {
            $_SESSION['error'] = "El monto supera el máximo permitido.";
            $this->redirect('/admin/entregas');
        }

        if ($reunionId <= 0 || $socioId <= 0) {
            $_SESSION['error'] = "Selecciona la reunión y el socio beneficiario.";
            $this->redirect('/admin/entregas');
        }

        // 3.9: el usuario que entrega es el de la sesión; si no se encuentra, se aborta (no se usa otro)
        $usuarioModel = new Usuario();
        $entregadoPor = (int)($_SESSION['usuario']['id'] ?? 0);
        if ($entregadoPor <= 0 || !$usuarioModel->getSocioById($entregadoPor)) {
            $_SESSION['error'] = "No se pudo identificar al usuario de la sesión. Inicia sesión de nuevo.";
            $this->redirect('/admin/entregas');
        }

        $reunionModel = new Reunion();
        $reunion = $reunionModel->getReunionById($reunionId);
        if (!$reunion) {
            $_SESSION['error'] = "La reunión seleccionada no existe.";
            $this->redirect('/admin/entregas');
        }
        if ($reunion['estado'] === 'CERRADA') {
            $_SESSION['error'] = "La reunión R{$reunion['numero_quincena']} está CERRADA: no se pueden registrar entregas en ella.";
            $this->redirect('/admin/entregas');
        }

        $socioBeneficiario = $usuarioModel->getSocioById($socioId);
        if (!$socioBeneficiario) {
            $_SESSION['error'] = "El socio beneficiario seleccionado (ID {$socioId}) no existe en la base de datos.";
            $this->redirect('/admin/entregas');
        }

        $entregaModel = new EntregaBeneficio();
        if ($tipoBeneficio !== 'PRESTAMO') {
            $pendientes = array_map(function ($s) { return (int)$s['id']; }, $entregaModel->getSociosPendientes($tipoBeneficio));
            if (!in_array($socioId, $pendientes, true)) {
                $_SESSION['error'] = "Ese socio ya recibió su " . strtolower($tipoBeneficio) . " (o no está activo).";
                $this->redirect('/admin/entregas');
            }
        }

        // ---- Evidencias: se validan TODAS antes de escribir cualquier archivo (core/Evidencia.php) ----
        try {
            $firmaBytes = Evidencia::validarFirma();
            $foto = Evidencia::validarFoto();
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/entregas');
        }

        $firmaPath = null; $fotoPath = null; $escritos = [];
        try {
            if ($firmaBytes !== null) {
                $firmaPath = Evidencia::guardarFirma($firmaBytes, $socioId, $tipoBeneficio, $escritos);
            }
            if ($foto !== null) {
                $fotoPath = Evidencia::guardarFoto($foto, $socioId, $tipoBeneficio, $escritos);
            }

            $ok = $entregaModel->registrarEntrega([
                'reunion_id' => $reunionId,
                'socio_id' => $socioId,
                'prestamo_id' => $prestamoId,
                'tipo_beneficio' => $tipoBeneficio,
                'monto_entregado' => $monto,
                'firma_digital_path' => $firmaPath,
                'foto_evidencia_path' => $fotoPath,
                'entregado_por_usuario_id' => $entregadoPor
            ]);

            if ($ok) {
                $_SESSION['success'] = "Entrega de {$tipoBeneficio} por $" . number_format($monto, 0, ',', '.') . " registrada exitosamente" . ($firmaPath || $fotoPath ? " con evidencia." : ".");
            } else {
                foreach ($escritos as $a) { @unlink($a); }
                $_SESSION['error'] = "No se pudo registrar la entrega de beneficio.";
            }
        } catch (Throwable $e) {
            foreach ($escritos as $a) { @unlink($a); }
            $_SESSION['error'] = "Error al registrar la entrega: " . $e->getMessage();
        }

        $this->redirect('/admin/entregas');
    }

    public function eliminar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $id = (int)($_POST['entrega_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = "ID de constancia de entrega inválido.";
            $this->redirect('/admin/entregas');
        }

        try {
            $entregaModel = new EntregaBeneficio();
            $ok = $entregaModel->eliminarEntrega($id);
            if ($ok) {
                $_SESSION['success'] = "Constancia de entrega N° {$id} eliminada exitosamente.";
            } else {
                $_SESSION['error'] = "No se pudo eliminar la constancia de entrega.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error al eliminar constancia: " . $e->getMessage();
        }

        $this->redirect('/admin/entregas');
    }

    public function sociosPendientesJson(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);
        $tipo = trim($_GET['tipo'] ?? 'RONDA');
        if (!in_array($tipo, EntregaBeneficio::tiposValidos(), true)) {
            $this->json(['success' => false, 'error' => 'Tipo no válido', 'socios' => []], 400);
        }

        $entregaModel = new EntregaBeneficio();
        $socios = $entregaModel->getSociosPendientes($tipo);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'socios' => $socios,
            'siguiente_turno' => ($tipo === 'RONDA' && $socios) ? $socios[0] : null,
            'monto_defecto' => $this->montoPorDefecto($tipo)
        ]);
    }
}
