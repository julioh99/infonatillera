<?php
// app/Controllers/EntregaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/FondoBeneficio.php';
require_once __DIR__ . '/../Models/EntregaBeneficio.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';

class EntregaController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $fondoModel = new FondoBeneficio();
        $entregaModel = new EntregaBeneficio();
        $reunionModel = new Reunion();
        $usuarioModel = new Usuario();

        $resumenFondos = $fondoModel->getResumenFondos();
        $cronograma = $fondoModel->getCronograma();
        $entregas = $entregaModel->getTodasEntregas();
        $reuniones = $reunionModel->getReuniones();
        $sociosPrestamo = $entregaModel->getSociosPendientes('PRESTAMO');
        $sociosRonda = $entregaModel->getSociosPendientes('RONDA');
        $sociosRifa = $entregaModel->getSociosPendientes('RIFA');

        $tipoQuery = trim($_GET['tipo'] ?? 'PRESTAMO');
        $socioIdQuery = (int)($_GET['socio_id'] ?? 0);
        $montoQuery = (float)($_GET['monto'] ?? 0);

        $this->render('admin/entregas_beneficios', [
            'resumenFondos' => $resumenFondos,
            'cronograma' => $cronograma,
            'entregas' => $entregas,
            'reuniones' => $reuniones,
            'sociosPrestamo' => $sociosPrestamo,
            'sociosRonda' => $sociosRonda,
            'sociosRifa' => $sociosRifa,
            'tipoQuery' => $tipoQuery,
            'socioIdQuery' => $socioIdQuery,
            'montoQuery' => $montoQuery
        ]);
    }

    public function guardar(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);

        $reunionId = (int)($_POST['reunion_id'] ?? 0);
        $socioId = (int)($_POST['socio_id'] ?? 0);
        $tipoBeneficio = trim($_POST['tipo_beneficio'] ?? 'PRESTAMO');
        $montoStr = trim($_POST['monto_entregado'] ?? '0');
        $monto = (float)str_replace('.', '', $montoStr);

        if ($monto <= 0) {
            $monto = ($tipoBeneficio === 'RONDA') ? 300000.00 : (($tipoBeneficio === 'RIFA') ? 150000.00 : 500000.00);
        }

        if ($reunionId <= 0 || $socioId <= 0) {
            $_SESSION['error'] = "Selecciona la reunión y el socio beneficiario.";
            $this->redirect('/admin/entregas');
        }

        // Crear directorios de evidencias si no existen
        $firmasDir = __DIR__ . '/../../public/uploads/firmas';
        $evidenciasDir = __DIR__ . '/../../public/uploads/evidencias';

        if (!is_dir($firmasDir)) {
            @mkdir($firmasDir, 0777, true);
        }
        if (!is_dir($evidenciasDir)) {
            @mkdir($evidenciasDir, 0777, true);
        }

        // 1. Guardar Firma Digital Canvas (Base64)
        $firmaPath = null;
        if (!empty($_POST['firma_base64'])) {
            $firmaData = $_POST['firma_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $firmaData, $type)) {
                $data = substr($firmaData, strpos($firmaData, ',') + 1);
                $data = base64_decode($data);
                if ($data !== false) {
                    $fileNameFirma = "firma_{$socioId}_{$tipoBeneficio}_" . time() . ".png";
                    @file_put_contents("{$firmasDir}/{$fileNameFirma}", $data);
                    $firmaPath = "/uploads/firmas/{$fileNameFirma}";
                }
            }
        }

        // 2. Guardar Foto Evidencia (Subida de Archivo o Base64 Cámara)
        $fotoPath = null;
        if (!empty($_FILES['foto_evidencia']['tmp_name']) && is_uploaded_file($_FILES['foto_evidencia']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto_evidencia']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $fileNameFoto = "foto_{$socioId}_{$tipoBeneficio}_" . time() . ".{$ext}";
                if (@move_uploaded_file($_FILES['foto_evidencia']['tmp_name'], "{$evidenciasDir}/{$fileNameFoto}")) {
                    $fotoPath = "/uploads/evidencias/{$fileNameFoto}";
                }
            }
        } elseif (!empty($_POST['foto_base64'])) {
            $fotoData = $_POST['foto_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
                $data = substr($fotoData, strpos($fotoData, ',') + 1);
                $data = base64_decode($data);
                if ($data !== false) {
                    $fileNameFoto = "foto_{$socioId}_{$tipoBeneficio}_" . time() . ".jpg";
                    @file_put_contents("{$evidenciasDir}/{$fileNameFoto}", $data);
                    $fotoPath = "/uploads/evidencias/{$fileNameFoto}";
                }
            }
        }

        try {
            $usuarioModel = new Usuario();

            $entregadoPor = (int)($_SESSION['usuario']['id'] ?? 1);
            $userExist = $usuarioModel->getSocioById($entregadoPor);

            if (!$userExist) {
                // Si el ID guardado en sesión previa no existe en la BD actual, asignar ID 1 o la primera persona
                $todosSocios = $usuarioModel->getAllSocios();
                $entregadoPor = !empty($todosSocios) ? (int)$todosSocios[0]['id'] : 1;
                // Actualizar la sesión para sincronizarla
                if (isset($_SESSION['usuario']) && !empty($todosSocios)) {
                    $_SESSION['usuario']['id'] = $entregadoPor;
                }
            }

            // Verificar que el socio beneficiario exista
            $socioBeneficiario = $usuarioModel->getSocioById($socioId);
            if (!$socioBeneficiario) {
                $_SESSION['error'] = "El socio beneficiario seleccionado (ID {$socioId}) no existe en la base de datos.";
                $this->redirect('/admin/entregas');
            }

            $entregaModel = new EntregaBeneficio();
            $ok = $entregaModel->registrarEntrega([
                'reunion_id' => $reunionId,
                'socio_id' => $socioId,
                'tipo_beneficio' => $tipoBeneficio,
                'monto_entregado' => $monto,
                'firma_digital_path' => $firmaPath,
                'foto_evidencia_path' => $fotoPath,
                'entregado_por_usuario_id' => $entregadoPor
            ]);

            if ($ok) {
                $_SESSION['success'] = "Entrega de {$tipoBeneficio} por $" . number_format($monto, 0, ',', '.') . " registrada exitosamente con evidencia.";
            } else {
                $_SESSION['error'] = "No se pudo registrar la entrega de beneficio.";
            }
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error al registrar la entrega: " . $e->getMessage();
        }

        $this->redirect('/admin/entregas');
    }

    public function sociosPendientesJson(): void {
        $this->requireRole(['Presidente', 'Tesorera', 'Secretaria General']);
        $tipo = trim($_GET['tipo'] ?? 'RONDA');

        $entregaModel = new EntregaBeneficio();
        $socios = $entregaModel->getSociosPendientes($tipo);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'socios' => $socios]);
    }
}
