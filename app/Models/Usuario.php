<?php
// app/Models/Usuario.php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/Historico.php';
require_once __DIR__ . '/Prestamo.php';

class Usuario extends Model {

    public function __construct() {
        parent::__construct();
        // La columna natillera_usuarios.orden debe existir antes de ordenar por ella
        Historico::asegurarColumnaOrden($this->db);
    }

    public function login($cedula, $password) {
        $stmt = $this->db->prepare("SELECT u.*, r.nombre as rol_nombre 
            FROM natillera_usuarios u 
            JOIN natillera_roles r ON u.rol_id = r.id 
            WHERE u.cedula = :cedula AND u.estado = 1
        ");
        $stmt->execute([':cedula' => $cedula]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }

        return null;
    }

    /**
     * Socios en el orden definido por natillera_usuarios.orden (los activos primero, los inactivos al final).
     * $soloActivos = true devuelve solo los socios del ano en curso (estado = 1).
     */
    public function getAllSocios($soloActivos = false) {
        $where = $soloActivos ? 'WHERE u.estado = 1' : '';
        $stmt = $this->db->query("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono, u.fecha_nacimiento, u.rol_id, u.tope_prestamo_personalizado, u.interes_minimo_meta, u.estado, u.orden, r.nombre as rol_nombre
            FROM natillera_usuarios u
            JOIN natillera_roles r ON u.rol_id = r.id
            {$where}
            ORDER BY u.estado DESC, u.orden ASC, u.id ASC
        ");
        return $stmt->fetchAll();
    }

    public function getSociosActivos() {
        return $this->getAllSocios(true);
    }

    /** Guarda el orden: $idsEnOrden[0] queda de primero. Los socios no incluidos quedan al final (por id). */
    public function guardarOrden(array $idsEnOrden): int {
        $ids = array();
        foreach ($idsEnOrden as $id) {
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $ids, true)) $ids[] = $id;
        }
        $propia = !$this->db->inTransaction();
        if ($propia) $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare('UPDATE natillera_usuarios SET orden = :o WHERE id = :id');
            $n = 0;
            foreach ($ids as $id) {
                $n++;
                $upd->execute(array(':o' => $n, ':id' => $id));
            }
            $resto = $this->db->query('SELECT id FROM natillera_usuarios ORDER BY orden ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($resto as $id) {
                if (!in_array((int)$id, $ids, true)) {
                    $n++;
                    $upd->execute(array(':o' => $n, ':id' => (int)$id));
                }
            }
            if ($propia) $this->db->commit();
            return count($ids);
        } catch (Exception $e) {
            if ($propia && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function getSocioById($id) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono, u.fecha_nacimiento, u.rol_id, u.tope_prestamo_personalizado, u.interes_minimo_meta, u.estado, u.orden, r.nombre as rol_nombre
            FROM natillera_usuarios u
            JOIN natillera_roles r ON u.rol_id = r.id
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function crearSocio(array $datos): bool {
        // El socio nuevo queda de ultimo en el orden
        $siguienteOrden = (int)$this->db->query("SELECT IFNULL(MAX(orden), 0) + 1 FROM natillera_usuarios")->fetchColumn();
        $stmt = $this->db->prepare("
            INSERT INTO natillera_usuarios (cedula, nombre_completo, telefono, fecha_nacimiento, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta, estado, orden)
            VALUES (:cedula, :nombre_completo, :telefono, :fecha_nacimiento, :password_hash, :rol_id, :tope, 400000.00, 1, :orden)
        ");
        return $stmt->execute([
            ':orden' => $siguienteOrden,
            ':cedula' => $datos['cedula'],
            ':nombre_completo' => $datos['nombre_completo'],
            ':telefono' => $datos['telefono'] ?? null,
            ':fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
            ':password_hash' => password_hash((!empty($datos['password']) ? $datos['password'] : bin2hex(random_bytes(6))), PASSWORD_DEFAULT),
            ':rol_id' => $datos['rol_id'],
            ':tope' => $datos['tope_prestamo_personalizado'] ?? 2000000.00
        ]);
    }

    public function actualizarSocio(int $id, array $datos): bool {
        $fields = [
            'nombre_completo = :nombre_completo',
            'cedula = :cedula',
            'telefono = :telefono',
            'fecha_nacimiento = :fecha_nacimiento',
            'rol_id = :rol_id'
        ];
        $params = [
            ':id' => $id,
            ':nombre_completo' => $datos['nombre_completo'],
            ':cedula' => $datos['cedula'],
            ':telefono' => $datos['telefono'] ?? null,
            ':fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
            ':rol_id' => $datos['rol_id']
        ];

        if (!empty($datos['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $params[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE natillera_usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function actualizarMiPerfil(int $id, array $datos): bool {
        $fields = [
            'nombre_completo = :nombre_completo',
            'telefono = :telefono',
            'fecha_nacimiento = :fecha_nacimiento'
        ];
        $params = [
            ':id' => $id,
            ':nombre_completo' => $datos['nombre_completo'],
            ':telefono' => $datos['telefono'] ?? null,
            ':fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null
        ];

        if (!empty($datos['password'])) {
            $fields[] = 'password_hash = :password_hash';
            $fields[] = 'password_changed = 1';
            $params[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE natillera_usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function cambiarPasswordInicial(int $id, string $newPassword): bool {
        $stmt = $this->db->prepare("
            UPDATE natillera_usuarios 
            SET password_hash = :hash, password_changed = 1 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }

    public function getProximosCumpleanos(): array {
        // Retorna los socios agrupados por mes/día de nacimiento próximos en los siguientes 60 días
        $stmt = $this->db->query("
            SELECT id, nombre_completo, cedula, telefono, fecha_nacimiento,
                   DATE_FORMAT(fecha_nacimiento, '%m-%d') as dia_cumple
            FROM natillera_usuarios
            WHERE fecha_nacimiento IS NOT NULL AND estado = 1
            ORDER BY DATE_FORMAT(fecha_nacimiento, '%m-%d') ASC
        ");
        return $stmt->fetchAll();
    }

    public function getResumenFinancieroSocio(int $socioId): array {
        // Total ahorrado en cuotas y voluntario
        $stmtAhorro = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN cuota_pagada = 1 THEN monto_cuota ELSE 0 END) as total_cuotas,
                SUM(monto_ahorro_extra) as total_ahorro_extra
            FROM natillera_ahorros_cuotas
            WHERE socio_id = :id
        ");
        $stmtAhorro->execute([':id' => $socioId]);
        $ahorros = $stmtAhorro->fetch() ?: ['total_cuotas' => 0, 'total_ahorro_extra' => 0];

        $totalCuotas = (float)($ahorros['total_cuotas'] ?? 0);
        $totalExtra = (float)($ahorros['total_ahorro_extra'] ?? 0);
        $totalAhorrado = $totalCuotas + $totalExtra;

        // Total intereses pagados por el socio en préstamos (para la meta de $400.000)
        $stmtInteres = $this->db->prepare("
            SELECT SUM(ap.monto_interes_pagado) as total_interes
            FROM natillera_abonos_prestamos ap
            JOIN natillera_prestamos p ON ap.prestamo_id = p.id
            WHERE p.socio_deudor_id = :id AND p.anulado_sin_interes = 0
        ");
        $stmtInteres->execute([':id' => $socioId]);
        $interesRow = $stmtInteres->fetch();
        $totalInteresPagado = (float)($interesRow['total_interes'] ?? 0);

        // Deuda activa en préstamos
        $stmtDeuda = $this->db->prepare("
            SELECT 
                p.id, p.monto_prestado, p.tasa_interes_mensual, p.es_autoprestamo, p.nombre_referencia,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as capital_pagado
            FROM natillera_prestamos p
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.socio_deudor_id = :id AND p.estado = 'ACTIVO'
            GROUP BY p.id
        ");
        $stmtDeuda->execute([':id' => $socioId]);
        $prestamosActivos = $stmtDeuda->fetchAll();

        $saldoPendienteCapital = 0;
        foreach ($prestamosActivos as $p) {
            $saldoPendienteCapital += ((float)$p['monto_prestado'] - (float)$p['capital_pagado']);
        }

        // Deudas en actividades
        $stmtAct = $this->db->prepare("
            SELECT IFNULL(SUM(cuota_asignada - monto_pagado), 0) as deuda_actividades
            FROM natillera_actividad_participantes
            WHERE socio_id = :id AND estado_pago = 'PENDIENTE'
        ");
        $stmtAct->execute([':id' => $socioId]);
        $deudaActividades = (float)($stmtAct->fetch()['deuda_actividades'] ?? 0);

        // Interes mensual progresivo pendiente de los prestamos activos (InteresPrestamo)
        $interesSocio = (new Prestamo())->resumenInteresSocio($socioId);

        return [
            'interes_pendiente_prestamos' => $interesSocio['interes_pendiente'],
            'meses_interes_cobrados' => $interesSocio['meses_cobrados'],
            'deuda_prestamos_con_interes' => $saldoPendienteCapital + $interesSocio['interes_pendiente'],
            'total_cuotas' => $totalCuotas,
            'total_ahorro_extra' => $totalExtra,
            'total_ahorrado' => $totalAhorrado,
            'total_interes_generado' => $totalInteresPagado,
            'meta_interes' => 400000.00,
            'porcentaje_meta' => min(100, round(($totalInteresPagado / 400000.00) * 100, 1)),
            'deuda_prestamos_capital' => $saldoPendienteCapital,
            'deuda_actividades' => $deudaActividades
        ];
    }

    public function updateTopePrestamo(int $socioId, float $nuevoTope): bool {
        $stmt = $this->db->prepare("UPDATE natillera_usuarios SET tope_prestamo_personalizado = :tope WHERE id = :id");
        return $stmt->execute([':tope' => $nuevoTope, ':id' => $socioId]);
    }
}
