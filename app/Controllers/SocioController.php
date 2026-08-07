<?php
// app/Controllers/SocioController.php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Reunion.php';
require_once __DIR__ . '/../Models/Prestamo.php';

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

    public function liquidacionAnual(): void {
        $this->requireAuth();
        $db = Database::getConnection();

        // Total intereses generados en todo el sistema por préstamos
        $stmtTotalIntereses = $db->query("
            SELECT IFNULL(SUM(monto_interes_pagado), 0) as total_utilidades
            FROM natillera_abonos_prestamos ap
            JOIN natillera_prestamos p ON ap.prestamo_id = p.id
            WHERE p.anulado_sin_interes = 0
        ");
        $totalUtilidades = (float)($stmtTotalIntereses->fetch()['total_utilidades'] ?? 0);

        // Suma total ahorrada por TODOS los socios (cuotas + ahorros extras)
        $stmtTotalAhorroGeneral = $db->query("
            SELECT IFNULL(SUM(CASE WHEN cuota_pagada = 1 THEN monto_cuota ELSE 0 END + monto_ahorro_extra), 0) as gran_total_ahorro
            FROM natillera_ahorros_cuotas
        ");
        $granTotalAhorro = (float)($stmtTotalAhorroGeneral->fetch()['gran_total_ahorro'] ?? 0);

        // Ahorros por socio para calcular reparto proporcional
        $stmtSociosAhorro = $db->query("
            SELECT u.id, u.nombre_completo, u.cedula,
                   IFNULL(SUM(CASE WHEN ac.cuota_pagada = 1 THEN ac.monto_cuota ELSE 0 END + ac.monto_ahorro_extra), 0) as ahorro_socio
            FROM natillera_usuarios u
            LEFT JOIN natillera_ahorros_cuotas ac ON u.id = ac.socio_id
            GROUP BY u.id
            ORDER BY u.nombre_completo ASC
        ");
        $sociosAhorro = $stmtSociosAhorro->fetchAll();

        $liquidaciones = [];
        foreach ($sociosAhorro as $s) {
            $ahorroSocio = (float)$s['ahorro_socio'];
            $porcentajeParticipacion = $granTotalAhorro > 0 ? ($ahorroSocio / $granTotalAhorro) : 0;
            $gananciaEquivalente = round($totalUtilidades * $porcentajeParticipacion, 2);
            $totalARecibir = $ahorroSocio + $gananciaEquivalente;

            $liquidaciones[] = [
                'id' => $s['id'],
                'nombre_completo' => $s['nombre_completo'],
                'cedula' => $s['cedula'],
                'ahorro_socio' => $ahorroSocio,
                'porcentaje' => round($porcentajeParticipacion * 100, 2),
                'ganancia_utilidad' => $gananciaEquivalente,
                'total_a_recibir' => $totalARecibir
            ];
        }

        $this->json([
            'gran_total_ahorro' => $granTotalAhorro,
            'total_utilidades_intereses' => $totalUtilidades,
            'liquidaciones' => $liquidaciones
        ]);
    }

    public function gestionarSocios(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $usuarioModel = new Usuario();
        $socios = $usuarioModel->getAllSocios();
        $cumpleanos = $usuarioModel->getProximosCumpleanos();

        // Obtener lista de roles para el select de edición
        $db = Database::getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        $this->render('admin/socios', [
            'socios' => $socios,
            'cumpleanos' => $cumpleanos,
            'roles' => $roles
        ]);
    }

    public function crearSocio(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $cedula = trim($_POST['cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
        $rolId = (int)($_POST['rol_id'] ?? 5); // Default Socio
        $password = trim($_POST['password'] ?? '123456');

        if (empty($nombreCompleto) || empty($cedula)) {
            $_SESSION['error'] = "Nombre y cédula son obligatorios.";
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
                $_SESSION['success'] = "Nuevo socio '{$nombreCompleto}' registrado exitosamente.";
            } else {
                $_SESSION['error'] = "No se pudo registrar el socio.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al crear socio (posible cédula duplicada).";
        }

        $this->redirect('/admin/socios');
    }

    public function actualizarSocio(): void {
        $this->requireRole(['Presidente', 'Secretaria General']);

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
}
