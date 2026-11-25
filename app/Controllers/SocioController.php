<?php
// app/Controllers/SocioController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Prestamo.php';
require_once __DIR__ . '/../Models/Actividad.php';
require_once __DIR__ . '/../Models/PagoNotificacion.php';

class SocioController extends Controller {

    public function dashboard(): void {
        $user = $this->requireAuth();

        $usuarioModel = new Usuario();
        $reunionModel = new Reunion();
        $prestamoModel = new Prestamo();

        $resumen = $usuarioModel->getResumenFinancieroSocio((int)$user['id']);
        $reuniones = $reunionModel->getReuniones();

        // Obtener historial de cuotas pagadas por el socio
        $stmtHistory = Database::getConnection()->prepare("
            SELECT ac.*, r.numero_quincena, r.fecha_reunion, r.valor_cuota_base, r.tipo_evento_extra
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_reuniones r ON ac.reunion_id = r.id
            WHERE ac.socio_id = :id
            ORDER BY r.numero_quincena ASC
        ");
        $stmtHistory->execute([':id' => $user['id']]);
        $historialCuotas = $stmtHistory->fetchAll();

        // Préstamos del socio con sus cuotas/abonos e información de quién registró
        $stmtLoans = Database::getConnection()->prepare("
            SELECT p.*, IFNULL(SUM(ap.monto_capital_pagado), 0) as capital_pagado, IFNULL(SUM(ap.monto_interes_pagado), 0) as interes_pagado
            FROM natillera_prestamos p
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.socio_deudor_id = :id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        $stmtLoans->execute([':id' => $user['id']]);
        $misPrestamos = $stmtLoans->fetchAll();

        $stmtAbonos = Database::getConnection()->prepare("
            SELECT ap.*, u.nombre_completo as registrado_por_nombre
            FROM natillera_abonos_prestamos ap
            LEFT JOIN natillera_usuarios u ON ap.registrado_por_usuario_id = u.id
            WHERE ap.prestamo_id = :prestamo_id
            ORDER BY ap.fecha_abono DESC
        ");
        foreach ($misPrestamos as &$mp) {
            $stmtAbonos->execute([':prestamo_id' => $mp['id']]);
            $mp['cuotas'] = $stmtAbonos->fetchAll();
        }
        unset($mp);

        // Actividades comunitarias y deudas del socio
        $stmtActividades = Database::getConnection()->prepare("
            SELECT ap.*, a.nombre_actividad, a.descripcion, a.fecha_actividad
            FROM natillera_actividad_participantes ap
            JOIN natillera_actividades a ON ap.actividad_id = a.id
            WHERE ap.socio_id = :id
            ORDER BY a.fecha_actividad DESC
        ");
        $stmtActividades->execute([':id' => $user['id']]);
        $misActividades = $stmtActividades->fetchAll();

        $totalDeudaActividades = 0.0;
        $actividadModel = new Actividad();
        foreach ($misActividades as &$act) {
            $act['abonos'] = $actividadModel->getAbonosPorParticipante((int)$act['id']);
            $saldoAct = (float)$act['cuota_asignada'] - (float)$act['monto_pagado'];
            if ($saldoAct > 0) {
                $totalDeudaActividades += $saldoAct;
            }
        }
        unset($act);

        $this->render('socio/dashboard', [
            'user' => $user,
            'resumen' => $resumen,
            'reuniones' => $reuniones,
            'historialCuotas' => $historialCuotas,
            'misPrestamos' => $misPrestamos,
            'misActividades' => $misActividades,
            'totalDeudaActividades' => $totalDeudaActividades
        ]);
    }

    /**
     * Liquidación anual (simulación).
     *
     * Quién ve qué: un socio ve SOLO su propia fila; Presidente (en modo Presidente), Tesorera y
     * Secretaria General ven la de todos. Los totales generales (gran_total_ahorro y total de
     * intereses) son del fondo común y se muestran a todos porque el % de cada uno depende de ellos.
     *
     * Fórmula por socio:
     *   total_a_recibir = ahorro_socio
     *                   - deuda de capital pendiente de préstamos activos (incluye autopréstamos)
     *                   + ganancia por intereses (interés total pagado * ahorro_socio / ahorro de todos)
     *                   + utilidades de actividades (suma de ganancia_asignada del socio)
     *   ahorro_socio    = cuotas base pagadas (monto_cuota) + ahorro extra voluntario.
     *   Una cuota cubierta por autopréstamo NO cuenta como ahorro hasta que ese autopréstamo
     *   quede pagado (estado PAGADO o capital abonado >= monto prestado); mientras tanto su
     *   capital pendiente sí resta como deuda. El ahorro extra siempre cuenta.
     *   Montos: deuda desde Usuario::getResumenFinancieroSocio()['deuda_prestamos_capital'].
     */
    public function liquidacionAnual(): void {
        $user = $this->requireAuth();
        $db = Database::getConnection();

        $rol = isset($user['rol_nombre']) ? $user['rol_nombre'] : '';
        $modo = isset($_SESSION['active_mode']) ? $_SESSION['active_mode'] : $rol;
        if ($rol === 'Presidente' && $modo !== 'Presidente') {
            $rol = 'Socio';
        }
        $veTodos = in_array($rol, ['Presidente', 'Tesorera', 'Secretaria General'], true);

        // Total intereses generados en todo el sistema por préstamos
        $stmtTotalIntereses = $db->query("
            SELECT IFNULL(SUM(monto_interes_pagado), 0) as total_utilidades
            FROM natillera_abonos_prestamos ap
            JOIN natillera_prestamos p ON ap.prestamo_id = p.id
            WHERE p.anulado_sin_interes = 0
        ");
        $totalUtilidades = (float)($stmtTotalIntereses->fetch()['total_utilidades'] ?? 0);

        // Ahorro por socio: la cuota de un autopréstamo cuenta solo cuando ese préstamo ya se pagó
        $sociosAhorro = $db->query("
            SELECT u.id, u.nombre_completo, u.cedula,
                   IFNULL(SUM(
                       (CASE WHEN ac.cuota_pagada = 1 AND (
                                IFNULL(ac.autoprestamo_generado, 0) = 0
                                OR (p.id IS NOT NULL AND (p.estado = 'PAGADO' OR IFNULL(cp.capital_pagado, 0) >= p.monto_prestado))
                            ) THEN ac.monto_cuota ELSE 0 END)
                       + IFNULL(ac.monto_ahorro_extra, 0)
                   ), 0) as ahorro_socio
            FROM natillera_usuarios u
            LEFT JOIN natillera_ahorros_cuotas ac ON u.id = ac.socio_id
            LEFT JOIN natillera_prestamos p ON p.id = ac.prestamo_id_asociado
            LEFT JOIN (
                SELECT prestamo_id, SUM(monto_capital_pagado) as capital_pagado
                FROM natillera_abonos_prestamos GROUP BY prestamo_id
            ) cp ON cp.prestamo_id = p.id
            GROUP BY u.id, u.nombre_completo, u.cedula
            ORDER BY u.nombre_completo ASC
        ")->fetchAll();

        $granTotalAhorro = 0.0;
        foreach ($sociosAhorro as $s) {
            $granTotalAhorro += (float)$s['ahorro_socio'];
        }

        // Utilidades de actividades por socio
        $utilAct = [];
        foreach ($db->query("SELECT socio_id, IFNULL(SUM(ganancia_asignada), 0) as util FROM natillera_actividad_participantes GROUP BY socio_id")->fetchAll() as $r) {
            $utilAct[(int)$r['socio_id']] = (float)$r['util'];
        }

        $usuarioModel = new Usuario();
        $liquidaciones = [];
        foreach ($sociosAhorro as $s) {
            $socioId = (int)$s['id'];
            if (!$veTodos && $socioId !== (int)$user['id']) {
                continue;
            }

            $ahorroSocio = (float)$s['ahorro_socio'];
            $resumen = $usuarioModel->getResumenFinancieroSocio($socioId);
            $deudaCapital = (float)$resumen['deuda_prestamos_capital'];
            $porcentajeParticipacion = $granTotalAhorro > 0 ? ($ahorroSocio / $granTotalAhorro) : 0;
            $gananciaIntereses = round($totalUtilidades * $porcentajeParticipacion, 2);
            $utilidadesActividades = round(isset($utilAct[$socioId]) ? $utilAct[$socioId] : 0.0, 2);
            $totalARecibir = round($ahorroSocio - $deudaCapital + $gananciaIntereses + $utilidadesActividades, 2);

            $liquidaciones[] = [
                'id' => $socioId,
                'nombre_completo' => $s['nombre_completo'],
                'cedula' => $s['cedula'],
                'ahorro_socio' => $ahorroSocio,
                'porcentaje' => round($porcentajeParticipacion * 100, 2),
                'deuda_capital' => $deudaCapital,
                'ganancia_intereses' => $gananciaIntereses,
                'utilidades_actividades' => $utilidadesActividades,
                'ganancia_utilidad' => round($gananciaIntereses + $utilidadesActividades, 2),
                'total_a_recibir' => $totalARecibir
            ];
        }

        $this->json([
            'gran_total_ahorro' => $granTotalAhorro,
            'total_utilidades_intereses' => $totalUtilidades,
            've_todos' => $veTodos,
            'liquidaciones' => $liquidaciones
        ]);
    }
    /** Nombre del rol por id ('' si no existe). */
    private function rolNombrePorId(int $rolId): string {
        $stmt = Database::getConnection()->prepare("SELECT nombre FROM natillera_roles WHERE id = :id");
        $stmt->execute([':id' => $rolId]);
        $n = $stmt->fetchColumn();
        return $n ? (string)$n : '';
    }

    /** Clave temporal legible de 8 caracteres, sin caracteres ambiguos (0/O, 1/I/L). */
    private function generarClaveTemporal(int $largo = 8): string {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $clave = '';
        for ($i = 0; $i < $largo; $i++) {
            $clave .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $clave;
    }

    public function gestionarSocios(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $usuarioModel = new Usuario();
        $socios = $usuarioModel->getAllSocios();
        $cumpleanos = $usuarioModel->getProximosCumpleanos();

        // Obtener lista de roles para el select de edición
        $db = Database::getConnection();
        $roles = $db->query("SELECT * FROM natillera_roles ORDER BY id ASC")->fetchAll();

        $this->render('admin/socios', [
            'socios' => $socios,
            'cumpleanos' => $cumpleanos,
            'roles' => $roles
        ]);
    }

    public function crearSocio(): void {
        $actor = $this->requireRole(['Presidente', 'Secretaria General']);

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $rolId = (int)($_POST['rol_id'] ?? 5); // Default Socio
        // Nunca se acepta una clave escrita por el administrador: se genera una temporal aleatoria.
        $password = $this->generarClaveTemporal();

        if (empty($nombreCompleto) || empty($cedula)) {
            $_SESSION['error'] = "Nombre y cédula son obligatorios.";
            $this->redirect('/admin/socios');
        }

        // Solo el Presidente puede crear a alguien con rol Presidente
        if ($this->rolNombrePorId($rolId) === 'Presidente' && $actor['rol_nombre'] !== 'Presidente') {
            $_SESSION['error'] = "Solo el Presidente puede asignar el rol Presidente.";
            $this->redirect('/admin/socios');
        }

        $usuarioModel = new Usuario();
        try {
            $ok = $usuarioModel->crearSocio([
                'nombre_completo' => $nombreCompleto,
                'cedula' => $cedula,
                'telefono' => $telefono,
                'fecha_nacimiento' => $fechaNacimiento,
                'rol_id' => $rolId,
                'password' => $password
            ]);

            if ($ok) {
                // password_changed queda en 0 (valor por defecto): el socio debe cambiar la clave al entrar
                $_SESSION['success'] = "Nuevo socio '{$nombreCompleto}' registrado exitosamente. Clave temporal: {$password} (anótala ahora; no se volverá a mostrar. El socio deberá cambiarla al ingresar).";
            } else {
                $_SESSION['error'] = "No se pudo registrar el socio.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al crear socio (posible cédula duplicada).";
        }

        $this->redirect('/admin/socios');
    }

    public function actualizarSocio(): void {
        $actor = $this->requireRole(['Presidente', 'Secretaria General']);

        $socioId = (int)($_POST['socio_id'] ?? 0);
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $rolId = (int)($_POST['rol_id'] ?? 0);
        $password = trim($_POST['password'] ?? '');

        if ($socioId <= 0 || empty($nombreCompleto) || empty($cedula) || $rolId <= 0) {
            $_SESSION['error'] = "Por favor completa todos los campos requeridos.";
            $this->redirect('/admin/socios');
        }

        $usuarioModel = new Usuario();

        // Solo el Presidente puede crear, editar o asignar el rol Presidente
        if ($actor['rol_nombre'] !== 'Presidente') {
            $actual = $usuarioModel->getSocioById($socioId);
            if ($this->rolNombrePorId($rolId) === 'Presidente' || ($actual && $actual['rol_nombre'] === 'Presidente')) {
                $_SESSION['error'] = "Solo el Presidente puede editar o asignar el rol Presidente.";
                $this->redirect('/admin/socios');
            }
        }

        $minPass = (int)$this->natillera('seguridad.password_min', 8);
        if ($password !== '' && strlen($password) < $minPass) {
            $_SESSION['error'] = "La nueva contraseña debe tener al menos {$minPass} caracteres.";
            $this->redirect('/admin/socios');
        }

        $ok = $usuarioModel->actualizarSocio($socioId, [
            'nombre_completo' => $nombreCompleto,
            'cedula' => $cedula,
            'telefono' => $telefono,
            'fecha_nacimiento' => $fechaNacimiento,
            'rol_id' => $rolId,
            'password' => $password
        ]);

        if ($ok) {
            $_SESSION['success'] = "Información del socio '{$nombreCompleto}' actualizada correctamente.";
        } else {
            $_SESSION['error'] = "Ocurrió un error al actualizar los datos del socio.";
        }

        $this->redirect('/admin/socios');
    }

    public function actualizarMiPerfil(): void {
        $user = $this->requireAuth();
        $socioId = (int)$user['id'];

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nombreCompleto)) {
            $_SESSION['error'] = "El nombre completo es obligatorio.";
            $this->redirect('/socio/dashboard');
        }

        $minPass = (int)$this->natillera('seguridad.password_min', 8);
        if ($password !== '' && strlen($password) < $minPass) {
            $_SESSION['error'] = "La nueva contraseña debe tener al menos {$minPass} caracteres.";
            $this->redirect('/socio/dashboard');
        }

        $usuarioModel = new Usuario();
        $ok = $usuarioModel->actualizarMiPerfil($socioId, [
            'nombre_completo' => $nombreCompleto,
            'telefono' => $telefono,
            'fecha_nacimiento' => $fechaNacimiento,
            'password' => $password
        ]);

        if ($ok) {
            // Actualizar variables de sesión del usuario
            $_SESSION['usuario']['nombre_completo'] = $nombreCompleto;
            $_SESSION['usuario']['telefono'] = $telefono;
            $_SESSION['usuario']['fecha_nacimiento'] = $fechaNacimiento;
            $_SESSION['success'] = "Tus datos personales se han actualizado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudieron actualizar tus datos personales.";
        }

        $this->redirect('/socio/dashboard');
    }

    public function expedienteJson(): void {
        $this->requireRole(['Presidente', 'Secretaria General', 'Secretaria Actividades', 'Tesorera']);

        $socioId = (int)($_GET['socio_id'] ?? 0);
        header('Content-Type: application/json');

        if ($socioId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Socio no especificado.']);
            return;
        }

        $usuarioModel = new Usuario();
        $socio = $usuarioModel->getSocioById($socioId);
        if (!$socio) {
            echo json_encode(['success' => false, 'message' => 'Socio no encontrado.']);
            return;
        }

        $resumen = $usuarioModel->getResumenFinancieroSocio($socioId);

        $db = Database::getConnection();

        // 1. Historial de Cuotas de Reunión y Ahorro Extra
        $stmtCuotas = $db->prepare("
            SELECT ac.*, r.numero_quincena, r.fecha_reunion, r.valor_cuota_base, r.tipo_evento_extra
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_reuniones r ON ac.reunion_id = r.id
            WHERE ac.socio_id = :id
            ORDER BY r.numero_quincena ASC
        ");
        $stmtCuotas->execute([':id' => $socioId]);
        $cuotas = $stmtCuotas->fetchAll();

        // 2. Historial de Actividades y Abonos
        $stmtAct = $db->prepare("
            SELECT ap.*, a.nombre_actividad, a.descripcion, a.fecha_actividad
            FROM natillera_actividad_participantes ap
            JOIN natillera_actividades a ON ap.actividad_id = a.id
            WHERE ap.socio_id = :id
            ORDER BY a.fecha_actividad DESC
        ");
        $stmtAct->execute([':id' => $socioId]);
        $actividades = $stmtAct->fetchAll();

        $actividadModel = new Actividad();
        foreach ($actividades as &$act) {
            $act['abonos'] = $actividadModel->getAbonosPorParticipante((int)$act['id']);
        }
        unset($act);

        // 3. Historial de Préstamos y Abonos
        $stmtLoans = $db->prepare("
            SELECT p.*, 
                   IFNULL(SUM(ap.monto_capital_pagado), 0) as capital_pagado, 
                   IFNULL(SUM(ap.monto_interes_pagado), 0) as interes_pagado
            FROM natillera_prestamos p
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.socio_deudor_id = :id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        $stmtLoans->execute([':id' => $socioId]);
        $prestamos = $stmtLoans->fetchAll();

        $prestamoModel = new Prestamo();
        foreach ($prestamos as &$p) {
            $p['cuotas'] = $prestamoModel->getAbonosPorPrestamo((int)$p['id']);
        }
        unset($p);

        // 4. Historial de Entregas y Beneficios (Firma y Foto)
        $stmtEntregas = $db->prepare("
            SELECT e.*, r.numero_quincena, u.nombre_completo as registrado_por_nombre
            FROM natillera_entregas_beneficios e
            LEFT JOIN natillera_reuniones r ON e.reunion_id = r.id
            LEFT JOIN natillera_usuarios u ON e.entregado_por_usuario_id = u.id
            WHERE e.socio_id = :id
            ORDER BY e.fecha_entrega DESC
        ");
        $stmtEntregas->execute([':id' => $socioId]);
        $entregas = $stmtEntregas->fetchAll();

        // 5. Historial de Inyecciones de Capital del Socio
        $stmtIny = $db->prepare("
            SELECT ic.*, r.numero_quincena, r.fecha_reunion
            FROM natillera_inyecciones_capital ic
            LEFT JOIN natillera_reuniones r ON ic.reunion_id = r.id
            WHERE ic.socio_id = :id
            ORDER BY ic.fecha_inyeccion DESC
        ");
        $stmtIny->execute([':id' => $socioId]);
        $inyecciones = $stmtIny->fetchAll();

        echo json_encode([
            'success' => true,
            'socio' => $socio,
            'resumen' => $resumen,
            'cuotas' => $cuotas,
            'actividades' => $actividades,
            'prestamos' => $prestamos,
            'entregas' => $entregas,
            'inyecciones' => $inyecciones
        ]);
    }

    public function pagosPendientesModal(): void {
        $user = $this->requireAuth();
        $pagoNotifModel = new PagoNotificacion();

        $pagos = $pagoNotifModel->getPagosPendientesPorSocio((int)$user['id']);
        $totalAbonadoGeneral = 0.0;
        foreach ($pagos as $p) {
            $totalAbonadoGeneral += (float)$p['monto_total'];
        }

        $saldoAhorroActual = $pagoNotifModel->getSaldoAhorradoActualSocio((int)$user['id']);

        $this->json([
            'success' => true,
            'total_pendientes' => count($pagos),
            'total_abonado_general' => $totalAbonadoGeneral,
            'saldo_ahorro_actual' => $saldoAhorroActual,
            'socio_nombre' => $user['nombre_completo'],
            'socio_cedula' => $user['cedula'],
            'pagos' => $pagos
        ]);
    }

    public function marcarPagosLeidos(): void {
        $user = $this->requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? ($_POST['ids'] ?? []);

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            $this->json(['success' => false, 'message' => 'IDs de notificación no proporcionados.'], 400);
            return;
        }

        $pagoNotifModel = new PagoNotificacion();
        $ok = $pagoNotifModel->marcarPagosComoLeidos($ids, (int)$user['id']);

        $this->json(['success' => $ok]);
    }
}
