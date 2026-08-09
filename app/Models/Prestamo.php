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
                r.numero_quincena,
                r.fecha_reunion,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital_pagado,
                IFNULL(SUM(ap.monto_interes_pagado), 0) as total_interes_pagado,
                (SELECT COUNT(*) 
                 FROM natillera_entregas_beneficios eb 
                 WHERE eb.socio_id = p.socio_deudor_id 
                   AND eb.tipo_beneficio = 'PRESTAMO'
                   AND ((eb.firma_digital_path IS NOT NULL AND eb.firma_digital_path != '') OR (eb.foto_evidencia_path IS NOT NULL AND eb.foto_evidencia_path != ''))
                ) as tiene_firma_foto
            FROM natillera_prestamos p
            JOIN natillera_usuarios u_deudor ON p.socio_deudor_id = u_deudor.id
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        return $stmt->fetchAll();
    }

    public function getPrestamoById(int $id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nombre_completo as deudor_nombre, u.cedula as deudor_cedula, r.numero_quincena, r.fecha_reunion
            FROM natillera_prestamos p
            JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crearPrestamo(array $datos): bool {
        $stmt = $this->db->prepare("
            INSERT INTO natillera_prestamos (
                socio_deudor_id, reunion_id, nombre_referencia, monto_prestado, 
                tasa_interes_mensual, tipo_prestamo, es_autoprestamo, estado
            ) VALUES (
                :socio_deudor_id, :reunion_id, :nombre_referencia, :monto_prestado, 
                :tasa_interes_mensual, :tipo_prestamo, :es_autoprestamo, 'ACTIVO'
            )
        ");

        return $stmt->execute([
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':reunion_id' => !empty($datos['reunion_id']) ? $datos['reunion_id'] : null,
            ':nombre_referencia' => !empty($datos['nombre_referencia']) ? $datos['nombre_referencia'] : null,
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'] ?? 10.00,
            ':tipo_prestamo' => $datos['tipo_prestamo'] ?? 'DIRECTO',
            ':es_autoprestamo' => $datos['es_autoprestamo'] ?? 0
        ]);
    }

    public function actualizarPrestamo(int $id, array $datos): bool {
        $stmt = $this->db->prepare("
            UPDATE natillera_prestamos SET
                socio_deudor_id = :socio_deudor_id,
                reunion_id = :reunion_id,
                monto_prestado = :monto_prestado,
                tasa_interes_mensual = :tasa_interes_mensual,
                nombre_referencia = :nombre_referencia,
                estado = :estado
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            ':id' => $id,
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':reunion_id' => !empty($datos['reunion_id']) ? $datos['reunion_id'] : null,
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
            FROM natillera_abonos_prestamos ap
            LEFT JOIN natillera_usuarios u ON ap.registrado_por_usuario_id = u.id
            WHERE ap.prestamo_id = :prestamo_id
            ORDER BY ap.fecha_abono DESC
        ");
        $stmt->execute([':prestamo_id' => $prestamoId]);
        return $stmt->fetchAll();
    }

    public function registrarAbono(int $prestamoId, float $montoCapital, float $montoInteres, int $usuarioId, ?int $reunionId = null): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO natillera_abonos_prestamos (prestamo_id, reunion_id, monto_capital_pagado, monto_interes_pagado, registrado_por_usuario_id)
                VALUES (:prestamo_id, :reunion_id, :monto_capital, :monto_interes, :registrado_por)
            ");
            $stmt->execute([
                ':prestamo_id' => $prestamoId,
                ':reunion_id' => $reunionId,
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

    public function actualizarAbono(int $abonoId, float $montoCapital, float $montoInteres, ?string $fechaAbono = null, ?int $reunionId = null): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id FROM natillera_abonos_prestamos WHERE id = :id");
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

        if ($reunionId !== null) {
            $fields[] = "reunion_id = :reunion_id";
            $params[':reunion_id'] = $reunionId;
        }

        $sql = "UPDATE natillera_abonos_prestamos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function eliminarAbono(int $abonoId): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id FROM natillera_abonos_prestamos WHERE id = :id");
        $stmtAbono->execute([':id' => $abonoId]);
        $abono = $stmtAbono->fetch();
        if (!$abono) return false;

        $prestamoId = (int)$abono['prestamo_id'];

        $stmtDel = $this->db->prepare("DELETE FROM natillera_abonos_prestamos WHERE id = :id");
        $ok = $stmtDel->execute([':id' => $abonoId]);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function recalcularEstadoPrestamo(int $prestamoId): void {
        $stmtCheck = $this->db->prepare("
            SELECT p.monto_prestado, p.anulado_sin_interes, IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital
            FROM natillera_prestamos p
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtCheck->execute([':id' => $prestamoId]);
        $row = $stmtCheck->fetch();

        if ($row && !$row['anulado_sin_interes']) {
            $montoPrestado = (float)$row['monto_prestado'];
            $totalCapital = (float)$row['total_capital'];
            $nuevoEstado = ($totalCapital >= $montoPrestado) ? 'PAGADO' : 'ACTIVO';

            $stmtUpdate = $this->db->prepare("UPDATE natillera_prestamos SET estado = :estado WHERE id = :id");
            $stmtUpdate->execute([':estado' => $nuevoEstado, ':id' => $prestamoId]);
        }
    }
}
