<?php
// app/Models/Prestamo.php

require_once __DIR__ . '/../../core/Model.php';

class Prestamo extends Model {

    public function getTodosPrestamos(): array {
        $stmt = $this->db->query("
            SELECT 
                p.*,
                u_deudor.nombre_completo as deudor_nombre,
                u_deudor.cedula as deudor_cedula,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital_pagado,
                IFNULL(SUM(ap.monto_interes_pagado), 0) as total_interes_pagado
            FROM prestamos p
            JOIN usuarios u_deudor ON p.socio_deudor_id = u_deudor.id
            LEFT JOIN abonos_prestamos ap ON p.id = ap.prestamo_id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        return $stmt->fetchAll();
    }

    public function getPrestamoById(int $id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nombre_completo as deudor_nombre, u.cedula as deudor_cedula
            FROM prestamos p
            JOIN usuarios u ON p.socio_deudor_id = u.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crearPrestamo(array $datos): bool {
        $stmt = $this->db->prepare("
            INSERT INTO prestamos (
                socio_deudor_id, nombre_referencia, monto_prestado, 
                tasa_interes_mensual, tipo_prestamo, es_autoprestamo, estado
            ) VALUES (
                :socio_deudor_id, :nombre_referencia, :monto_prestado, 
                :tasa_interes_mensual, :tipo_prestamo, :es_autoprestamo, 'ACTIVO'
            )
        ");

        return $stmt->execute([
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':nombre_referencia' => !empty($datos['nombre_referencia']) ? $datos['nombre_referencia'] : null,
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'] ?? 10.00,
            ':tipo_prestamo' => $datos['tipo_prestamo'] ?? 'DIRECTO',
            ':es_autoprestamo' => $datos['es_autoprestamo'] ?? 0
        ]);
    }

    public function actualizarPrestamo(int $id, array $datos): bool {
        $stmt = $this->db->prepare("
            UPDATE prestamos SET
                socio_deudor_id = :socio_deudor_id,
                monto_prestado = :monto_prestado,
                tasa_interes_mensual = :tasa_interes_mensual,
                nombre_referencia = :nombre_referencia,
                estado = :estado
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            ':id' => $id,
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'],
            ':nombre_referencia' => !empty($datos['nombre_referencia']) ? $datos['nombre_referencia'] : null,
            ':estado' => $datos['estado']
        ]);

        if ($ok) {
            $this->recalcularEstadoPrestamo($id);
        }

        return $ok;
    }

    public function getAbonosPorPrestamo(int $prestamoId): array {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nombre_completo as registrado_por_nombre
            FROM abonos_prestamos ap
            LEFT JOIN usuarios u ON ap.registrado_por_usuario_id = u.id
            WHERE ap.prestamo_id = :prestamo_id
            ORDER BY ap.fecha_abono DESC
        ");
        $stmt->execute([':prestamo_id' => $prestamoId]);
        return $stmt->fetchAll();
    }

    public function registrarAbono(int $prestamoId, float $montoCapital, float $montoInteres, int $usuarioId): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO abonos_prestamos (prestamo_id, monto_capital_pagado, monto_interes_pagado, registrado_por_usuario_id)
                VALUES (:prestamo_id, :monto_capital, :monto_interes, :registrado_por)
            ");
            $stmt->execute([
                ':prestamo_id' => $prestamoId,
                ':monto_capital' => $montoCapital,
                ':monto_interes' => $montoInteres,
                ':registrado_por' => $usuarioId
            ]);

            $this->db->commit();
            $this->recalcularEstadoPrestamo($prestamoId);
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function actualizarAbono(int $abonoId, float $montoCapital, float $montoInteres, ?string $fechaAbono = null): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id FROM abonos_prestamos WHERE id = :id");
        $stmtAbono->execute([':id' => $abonoId]);
        $abono = $stmtAbono->fetch();
        if (!$abono) return false;

        $prestamoId = (int)$abono['prestamo_id'];

        $fields = ["monto_capital_pagado = :capital", "monto_interes_pagado = :interes"];
        $params = [
            ':id' => $abonoId,
            ':capital' => $montoCapital,
            ':interes' => $montoInteres
        ];

        if (!empty($fechaAbono)) {
            $fields[] = "fecha_abono = :fecha";
            $params[':fecha'] = $fechaAbono;
        }

        $sql = "UPDATE abonos_prestamos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function eliminarAbono(int $abonoId): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id FROM abonos_prestamos WHERE id = :id");
        $stmtAbono->execute([':id' => $abonoId]);
        $abono = $stmtAbono->fetch();
        if (!$abono) return false;

        $prestamoId = (int)$abono['prestamo_id'];

        $stmtDel = $this->db->prepare("DELETE FROM abonos_prestamos WHERE id = :id");
        $ok = $stmtDel->execute([':id' => $abonoId]);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function recalcularEstadoPrestamo(int $prestamoId): void {
        $stmtCheck = $this->db->prepare("
            SELECT p.monto_prestado, p.anulado_sin_interes, IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital
            FROM prestamos p
            LEFT JOIN abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtCheck->execute([':id' => $prestamoId]);
        $row = $stmtCheck->fetch();

        if ($row && !$row['anulado_sin_interes']) {
            $montoPrestado = (float)$row['monto_prestado'];
            $totalCapital = (float)$row['total_capital'];
            $nuevoEstado = ($totalCapital >= $montoPrestado) ? 'PAGADO' : 'ACTIVO';

            $stmtUpdate = $this->db->prepare("UPDATE prestamos SET estado = :estado WHERE id = :id");
            $stmtUpdate->execute([':estado' => $nuevoEstado, ':id' => $prestamoId]);
        }
    }
}
