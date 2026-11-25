<?php
// app/Controllers/EntregaController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
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

    /**
     * Valida y decodifica una imagen en base64 (data URI). Devuelve [bytes, extension] o lanza RuntimeException.
     * @param string[] $mimesPermitidos
     */
    private function decodificarImagenBase64(string $dataUri, array $mimesPermitidos, int $maxBytes, string $nombre): array {
        if (!preg_match('#^data:(image/[a-z0-9.+-]+);base64,#i', $dataUri, $m)) {
            throw new RuntimeException("La {$nombre} no es una imagen válida.");
        }
        $bytes = base64_decode(substr($dataUri, strlen($m[0])), true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException("La {$nombre} no se pudo decodificar.");
        }
        if (strlen($bytes) > $maxBytes) {
            throw new RuntimeException("La {$nombre} supera el tamaño permitido (" . round($maxBytes / 1048576, 1) . " MB).");
        }
        $info = @getimagesizefromstring($bytes);
        if ($info === false || empty($info[0]) || empty($info[1]) || !in_array($info['mime'], $mimesPermitidos, true)) {
            throw new RuntimeException("La {$nombre} no es una imagen real de un tipo permitido.");
        }
        return [$bytes, $this->extensionDeMime($info['mime'])];
    }

    private function extensionDeMime(string $mime): string {
        $mapa = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        return $mapa[$mime] ?? 'bin';
    }

    private function nombreSeguro(string $prefijo, int $socioId, string $tipo, string $ext): string {
        // $tipo ya viene de la lista blanca; el sufijo aleatorio evita colisiones y nombres adivinables
        return $prefijo . '_' . $socioId . '_' . strtolower($tipo) . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
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

        // ---- Evidencias: se validan TODAS antes de escribir cualquier archivo ----
        $maxFirma = (int)Money::config('fondos.firma_max_bytes', 1048576);
        $maxFoto = (int)Money::config('fondos.foto_max_bytes', 5242880);
        $mimesFoto = (array)Money::config('fondos.foto_tipos', ['image/jpeg', 'image/png', 'image/webp']);

        $firmaBytes = null;
        $fotoBytes = null; $fotoExt = null; $fotoTmp = null;
        try {
            if (!empty($_POST['firma_base64'])) {
                list($firmaBytes, ) = $this->decodificarImagenBase64((string)$_POST['firma_base64'], ['image/png'], $maxFirma, 'firma digital');
            }

            if (!empty($_FILES['foto_evidencia']['tmp_name'])) {
                $f = $_FILES['foto_evidencia'];
                if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                    throw new RuntimeException("No se pudo recibir la foto de evidencia.");
                }
                if ((int)$f['size'] > $maxFoto) {
                    throw new RuntimeException("La foto de evidencia supera el tamaño permitido (" . round($maxFoto / 1048576, 1) . " MB).");
                }
                $info = @getimagesize($f['tmp_name']);
                if ($info === false || !in_array($info['mime'], $mimesFoto, true)) {
                    throw new RuntimeException("La foto de evidencia no es una imagen válida (JPG, PNG o WEBP).");
                }
                $fotoTmp = $f['tmp_name'];
                $fotoExt = $this->extensionDeMime($info['mime']);
            } elseif (!empty($_POST['foto_base64'])) {
                list($fotoBytes, $fotoExt) = $this->decodificarImagenBase64((string)$_POST['foto_base64'], $mimesFoto, $maxFoto, 'foto de evidencia');
            }
        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/admin/entregas');
        }

        $firmasDir = __DIR__ . '/../../public/uploads/firmas';
        $evidenciasDir = __DIR__ . '/../../public/uploads/evidencias';
        foreach ([$firmasDir, $evidenciasDir] as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
        }

        $firmaPath = null; $fotoPath = null; $escritos = [];
        try {
            if ($firmaBytes !== null) {
                $nombre = $this->nombreSeguro('firma', $socioId, $tipoBeneficio, 'png');
                if (@file_put_contents("{$firmasDir}/{$nombre}", $firmaBytes) === false) {
                    throw new RuntimeException("No se pudo guardar la firma digital.");
                }
                $escritos[] = "{$firmasDir}/{$nombre}";
                $firmaPath = "/uploads/firmas/{$nombre}";
            }
            if ($fotoTmp !== null || $fotoBytes !== null) {
                $nombre = $this->nombreSeguro('foto', $socioId, $tipoBeneficio, $fotoExt);
                $okFoto = ($fotoTmp !== null)
                    ? @move_uploaded_file($fotoTmp, "{$evidenciasDir}/{$nombre}")
                    : (@file_put_contents("{$evidenciasDir}/{$nombre}", $fotoBytes) !== false);
                if (!$okFoto) {
                    throw new RuntimeException("No se pudo guardar la foto de evidencia.");
                }
                $escritos[] = "{$evidenciasDir}/{$nombre}";
                $fotoPath = "/uploads/evidencias/{$nombre}";
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
