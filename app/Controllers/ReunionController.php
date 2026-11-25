<?php
// app/Controllers/ReunionController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Money.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/FondoBeneficio.php';
require_once __DIR__ . '/../Models/CajaGlobal.php';

class ReunionController extends Controller {

    public function index(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $reunionModel = new Reunion();
        $usuarioModel = new Usuario();
        $fondoModel = new FondoBeneficio();

        $reuniones = $reunionModel->getReuniones();
        $socios = $usuarioModel->getAllSocios();

        // Plan vs real por reunion y fondo: $planReal[reunion_id]['RONDA'|'RIFA']
        $planReal = [];
        foreach ($fondoModel->getCronograma() as $f) {
            $planReal[(int)$f['reunion_id']][$f['tipo_beneficio']] = $f;
        }

        $this->render('admin/reuniones', [
            'reuniones' => $reuniones,
            'socios' => $socios,
            'planReal' => $planReal,
            'cfgFondos' => $this->cfgFondos()
        ]);
    }

    /** Valores de config que necesita la pantalla (nada de numeros de negocio escritos en la vista). */
    private function cfgFondos(): array {
        return [
            'cupos' => FondoBeneficio::cupos(),
            'reuniones_por_ciclo' => (int)CajaGlobal::cfg('reuniones_por_ciclo', 26),
            'cuota_base' => (float)CajaGlobal::cfg('cuota.base_total', 55000),
            'ahorro' => CajaGlobal::ahorroPorCuota(),
            'ronda_base' => FondoBeneficio::aporteBase('RONDA'),
            'rifa_base' => FondoBeneficio::aporteBase('RIFA'),
            'extra_ronda' => FondoBeneficio::aporteExtra('RONDA'),
            'extra_rifa' => FondoBeneficio::aporteExtra('RIFA'),
            'premio_ronda' => FondoBeneficio::premio('RONDA'),
            'premio_rifa' => FondoBeneficio::premio('RIFA'),
        ];
    }

    public function crear(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $fecha = trim($_POST['fecha_reunion'] ?? '');
        $hora = trim($_POST['hora_reunion'] ?? CajaGlobal::cfg('fondos.hora_reunion', '14:00:00'));
        $cuota = Money::parse($_POST['valor_cuota_base'] ?? CajaGlobal::cfg('cuota.base_total', 55000));
        $evento = trim($_POST['tipo_evento_extra'] ?? 'NINGUNO');
        $premio = Money::parse($_POST['monto_premio_extra'] ?? 0, 0.0);

        if (empty($fecha) || $cuota === null || $cuota <= 0 || $premio === null) {
            $_SESSION['error'] = "Ingresa una fecha y valor de cuota válidos.";
            $this->redirect('/admin/reuniones');
        }
        if (!in_array($evento, ['NINGUNO', 'RIFA', 'RONDA'], true)) {
            $evento = 'NINGUNO';
        }

        $reunionModel = new Reunion();
        $ok = $reunionModel->crearReunion([
            'fecha_reunion' => $fecha,
            'hora_reunion' => $hora,
            'valor_cuota_base' => $cuota,
            'tipo_evento_extra' => $evento,
            'monto_premio_extra' => $premio
        ]);

        if ($ok) {
            $_SESSION['success'] = "Nueva reunión programada exitosamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al crear la reunión.";
        }

        $this->redirect('/admin/reuniones');
    }

    public function actualizar(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $id = (int)($_POST['reunion_id'] ?? 0);
        $fecha = trim($_POST['fecha_reunion'] ?? '');
        $hora = trim($_POST['hora_reunion'] ?? CajaGlobal::cfg('fondos.hora_reunion', '14:00:00'));
        $cuota = Money::parse($_POST['valor_cuota_base'] ?? CajaGlobal::cfg('cuota.base_total', 55000));
        $evento = trim($_POST['tipo_evento_extra'] ?? 'NINGUNO');
        $premio = Money::parse($_POST['monto_premio_extra'] ?? 0, 0.0);
        $ganadorId = !empty($_POST['ganador_socio_id']) ? (int)$_POST['ganador_socio_id'] : null;
        $estado = trim($_POST['estado'] ?? 'PROGRAMADA');

        if ($id <= 0 || empty($fecha) || $cuota === null || $cuota <= 0 || $premio === null) {
            $_SESSION['error'] = "Fecha y valor de cuota son obligatorios.";
            $this->redirect('/admin/reuniones');
        }
        if (!in_array($evento, ['NINGUNO', 'RIFA', 'RONDA'], true)) {
            $evento = 'NINGUNO';
        }
        if (!in_array($estado, ['PROGRAMADA', 'EN_PROCESO', 'LLAMADO_CERRADO', 'CERRADA'], true)) {
            $estado = 'PROGRAMADA';
        }

        $reunionModel = new Reunion();
        $actual = $reunionModel->getReunionById($id);
        if (!$actual) {
            $_SESSION['error'] = "La reunión no existe.";
            $this->redirect('/admin/reuniones');
        }

        $datos = [
            'fecha_reunion' => $fecha,
            'hora_reunion' => $hora,
            'valor_cuota_base' => $cuota,
            'tipo_evento_extra' => $evento,
            'monto_premio_extra' => $premio,
            'ganador_socio_id' => $ganadorId,
            'estado' => $estado
        ];

        // Fondos de la reunión (aporte de ronda/rifa y premios planificados)
        if (isset($_POST['aporte_ronda']) || isset($_POST['aporte_rifa'])) {
            $r = $this->resolverFondos($actual, $cuota);
            if (isset($r['error'])) {
                $_SESSION['error'] = $r['error'];
                $this->redirect('/admin/reuniones');
            }
            $datos['valor_cuota_base'] = $r['cuota'];
            $datos['aporte_ronda'] = $r['aporte_ronda'];
            $datos['aporte_rifa'] = $r['aporte_rifa'];
            $datos['premios_ronda_planificados'] = $r['premios_ronda'];
            $datos['premios_rifa_planificados'] = $r['premios_rifa'];
        }

        $ok = $reunionModel->actualizarReunion($id, $datos);

        if ($ok) {
            $_SESSION['success'] = ($actual['estado'] === 'CERRADA')
                ? "Reunión actualizada. Está CERRADA: su cuota y fondos no se modifican."
                : "Reunión actualizada exitosamente.";
        } else {
            $_SESSION['error'] = "No se pudo actualizar la reunión.";
        }

        $this->redirect('/admin/reuniones');
    }

    /**
     * Regla: si cambian los aportes, la cuota pasa a ser ahorro + ronda + rifa. Si solo cambia la cuota,
     * los aportes se derivan de la nueva cuota (se guardan en NULL). Si no cambia nada, se conservan.
     */
    private function resolverFondos(array $actual, float $cuotaEnviada): array {
        $ahorro = CajaGlobal::ahorroPorCuota();
        $antes = FondoBeneficio::aportesDeReunion($actual);
        $apR = Money::parse($_POST['aporte_ronda'] ?? '', $antes['ronda']);
        $apF = Money::parse($_POST['aporte_rifa'] ?? '', $antes['rifa']);
        if ($apR === null || $apF === null) {
            return ['error' => 'El aporte de ronda o de rifa no es válido.'];
        }
        $prR = ($_POST['premios_ronda_planificados'] ?? '') === '' ? null : max(0, (int)$_POST['premios_ronda_planificados']);
        $prF = ($_POST['premios_rifa_planificados'] ?? '') === '' ? null : max(0, (int)$_POST['premios_rifa_planificados']);
        $cupos = FondoBeneficio::cupos();
        if (($prR !== null && $prR > $cupos) || ($prF !== null && $prF > $cupos)) {
            return ['error' => "Los premios planificados por reunión no pueden superar los cupos ({$cupos})."];
        }

        $aportesCambiaron = abs($apR - $antes['ronda']) > 0.001 || abs($apF - $antes['rifa']) > 0.001;
        $cuotaCambio = abs($cuotaEnviada - (float)$actual['valor_cuota_base']) > 0.001;

        if ($aportesCambiaron) {
            return ['cuota' => $ahorro + $apR + $apF, 'aporte_ronda' => $apR, 'aporte_rifa' => $apF, 'premios_ronda' => $prR, 'premios_rifa' => $prF];
        }
        if ($cuotaCambio) {
            return ['cuota' => $cuotaEnviada, 'aporte_ronda' => null, 'aporte_rifa' => null, 'premios_ronda' => $prR, 'premios_rifa' => $prF];
        }
        return [
            'cuota' => $cuotaEnviada,
            'aporte_ronda' => ($actual['aporte_ronda'] === null ? null : $apR),
            'aporte_rifa' => ($actual['aporte_rifa'] === null ? null : $apF),
            'premios_ronda' => $prR, 'premios_rifa' => $prF
        ];
    }

    /** Filas del ciclo para el simulador: las reuniones CERRADAS quedan bloqueadas con lo que ya tienen. */
    private function filasSimulador(array $reuniones): array {
        $filas = [];
        $cron = [];
        foreach ((new FondoBeneficio())->getCronograma() as $c) {
            $cron[(int)$c['reunion_id']][$c['tipo_beneficio']] = $c;
        }
        foreach ($reuniones as $r) {
            $bloq = ($r['estado'] === 'CERRADA');
            $ap = FondoBeneficio::aportesDeReunion($r);
            $filas[] = [
                'bloqueada' => $bloq,
                'aporte_ronda' => $ap['ronda'],
                'aporte_rifa' => $ap['rifa'],
                'personas_ronda' => $cron[(int)$r['id']]['RONDA']['personas_liberadas_planificadas'] ?? null,
                'personas_rifa' => $cron[(int)$r['id']]['RIFA']['personas_liberadas_planificadas'] ?? null,
            ];
        }
        return $filas;
    }

    private function reunionesDelCiclo(): array {
        $todas = (new Reunion())->getReuniones();
        usort($todas, function ($a, $b) { return (int)$a['numero_quincena'] <=> (int)$b['numero_quincena']; });
        return $todas;
    }

    /** GET: simula la distribucion (no guarda). Parametros opcionales: cupos, reuniones (si aun no hay). */
    public function simularFondos(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);
        $reuniones = $this->reunionesDelCiclo();
        $cupos = isset($_GET['cupos']) && (int)$_GET['cupos'] > 0 ? (int)$_GET['cupos'] : null;

        if (empty($reuniones)) {
            $n = isset($_GET['reuniones']) && (int)$_GET['reuniones'] > 0 ? min(60, (int)$_GET['reuniones']) : (int)CajaGlobal::cfg('reuniones_por_ciclo', 26);
            $filas = array_fill(0, $n, ['bloqueada' => false]);
            $numeros = range(1, $n);
        } else {
            $filas = $this->filasSimulador($reuniones);
            $numeros = array_map(function ($r) { return (int)$r['numero_quincena']; }, $reuniones);
        }
        $sim = FondoBeneficio::sugerirDistribucion($filas, $cupos);
        foreach ($sim['filas'] as $i => &$f) {
            $f['numero'] = $numeros[$i];
        }
        unset($f);
        $this->json(['success' => true, 'simulacion' => $sim]);
    }

    /** POST: guarda en las reuniones no cerradas la distribucion sugerida (recalculada en el servidor). */
    public function aplicarDistribucion(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);
        $reuniones = $this->reunionesDelCiclo();
        if (empty($reuniones)) {
            $_SESSION['error'] = "No hay reuniones programadas para distribuir.";
            $this->redirect('/admin/reuniones');
        }
        $cupos = isset($_POST['cupos']) && (int)$_POST['cupos'] > 0 ? (int)$_POST['cupos'] : null;
        $sim = FondoBeneficio::sugerirDistribucion($this->filasSimulador($reuniones), $cupos);
        if (!$sim['ok']) {
            $_SESSION['error'] = "La simulación no cierra en $0 con todos servidos: " . implode(' ', $sim['mensajes']);
            $this->redirect('/admin/reuniones');
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        $aplicadas = 0;
        try {
            $upd = $db->prepare("UPDATE natillera_reuniones SET valor_cuota_base = :cuota, tipo_evento_extra = :evento,
                                     aporte_ronda = :ar, aporte_rifa = :af, premios_ronda_planificados = :pr, premios_rifa_planificados = :pf
                                 WHERE id = :id AND estado <> 'CERRADA'");
            foreach ($sim['filas'] as $i => $f) {
                if ($f['bloqueada']) { continue; }
                $upd->execute([
                    ':cuota' => $f['cuota'], ':evento' => $f['extra'], ':ar' => $f['aporte_ronda'], ':af' => $f['aporte_rifa'],
                    ':pr' => $f['personas_ronda'], ':pf' => $f['personas_rifa'], ':id' => $reuniones[$i]['id']
                ]);
                $aplicadas += $upd->rowCount();
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            $_SESSION['error'] = "No se pudo aplicar la distribución: " . $e->getMessage();
            $this->redirect('/admin/reuniones');
        }
        (new Reunion())->refrescarCronograma();
        $_SESSION['success'] = "Distribución aplicada a {$aplicadas} reuniones no cerradas ({$sim['extras_ronda']} cuotas extra de ronda y {$sim['extras_rifa']} de rifa).";
        $this->redirect('/admin/reuniones');
    }
}
