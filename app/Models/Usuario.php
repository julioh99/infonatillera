<?php
// app/Models/Usuario.php

require_once __DIR__ . '/../../core/Model.php';

class Usuario extends Model {

    public function login($cedula, $password) {
        $stmt = $this->db->prepare("SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
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

    public function getAllSocios() {
        $stmt = $this->db->query("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono, u.fecha_nacimiento, u.rol_id, u.tope_prestamo_personalizado, u.interes_minimo_meta, u.estado, r.nombre as rol_nombre
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            ORDER BY u.nombre_completo ASC
        ");
        return $stmt->fetchAll();
    }

    public function getSocioById($id) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono, u.fecha_nacimiento, u.rol_id, u.tope_prestamo_personalizado, u.interes_minimo_meta, u.estado, r.nombre as rol_nombre
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function crearSocio(array $datos): bool {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (cedula, nombre_completo, telefono, fecha_nacimiento, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta, estado)
            VALUES (:cedula, :nombre_completo, :telefono, :fecha_nacimiento, :password_hash, :rol_id, :tope, 400000.00, 1)
        ");
        return $stmt->execute([
            ':cedula' => $datos['cedula'],
            ':nombre_completo' => $datos['nombre_completo'],
            ':telefono' => $datos['telefono'] ?? null,
            ':fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
            ':password_hash' => password_hash($datos['password'] ?: '123456', PASSWORD_DEFAULT),
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

        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
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
            $params[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getProximosCumpleanos(): array {
        // Retorna los socios agrupados por mes/día de nacimiento próximos en los siguientes 60 días
        $stmt = $this->db->query("
            SELECT id, nombre_completo, cedula, telefono, fecha_nacimiento,
                   strftime('%m-%d', fecha_nacimiento) as dia_cumple
            FROM usuarios
            WHERE fecha_nacimiento IS NOT NULL AND estado = 1
            ORDER BY strftime('%m-%d', fecha_nacimiento) ASC
        ");
        return $stmt->fetchAll();
    }

    public function getResumenFinancieroSocio(int $socioId): array {
        // Total ahorrado en cuotas y voluntario
        $stmtAhorro = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN cuota_pagada = 1 THEN monto_cuota ELSE 0 END) as total_cuotas,
                SUM(monto_ahorro_extra) as total_ahorro_extra
            FROM ahorros_cuotas
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
            FROM abonos_prestamos ap
            JOIN prestamos p ON ap.prestamo_id = p.id
            WHERE p.socio_deudor_id = :id AND p.anulado_sin_interes = 0
        ");
        $stmtInteres->execute([':id' => $socioId]);
        $interesRow = $stmtInteres->fetch();
        $totalInteresPagado = (float)($interesRow['total_interes'] ?? 0);

        // Deuda activa en préstamos
        $stmtDeuda = $this->db->prepare("
            SELECT 
                p.id, p.monto_prestado, p.tasa_interes_mensual, p.es_autoprestamo, p.tercero_nombre,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as capital_pagado
            FROM prestamos p
            LEFT JOIN abonos_prestamos ap ON p.id = ap.prestamo_id
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
            FROM actividad_participantes
            WHERE socio_id = :id AND estado_pago = 'PENDIENTE'
        ");
        $stmtAct->execute([':id' => $socioId]);
        $deudaActividades = (float)($stmtAct->fetch()['deuda_actividades'] ?? 0);

        return [
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
        $stmt = $this->db->prepare("UPDATE usuarios SET tope_prestamo_personalizado = :tope WHERE id = :id");
        return $stmt->execute([':tope' => $nuevoTope, ':id' => $socioId]);
    }
}
