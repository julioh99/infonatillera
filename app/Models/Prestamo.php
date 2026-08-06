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
                u_fiador.nombre_completo as fiador_nombre,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital_pagado,
                IFNULL(SUM(ap.monto_interes_pagado), 0) as total_interes_pagado
            FROM prestamos p
            JOIN usuarios u_deudor ON p.socio_deudor_id = u_deudor.id
            LEFT JOIN usuarios u_fiador ON p.socio_fiador_id = u_fiador.id
            LEFT JOIN abonos_prestamos ap ON p.id = ap.prestamo_id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        return $stmt->fetchAll();
    }

    public function crearPrestamo(array $datos): bool {
        $stmt = $this->db->prepare("
            INSERT INTO prestamos (
                socio_deudor_id, socio_fiador_id, tercero_nombre, monto_prestado, 
                tasa_interes_mensual, tipo_prestamo, es_autoprestamo, estado
            ) VALUES (
                :socio_deudor_id, :socio_fiador_id, :tercero_nombre, :monto_prestado, 
                :tasa_interes_mensual, :tipo_prestamo, :es_autoprestamo, 'ACTIVO'
            )
        ");

        return $stmt->execute([
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':socio_fiador_id' => $datos['socio_fiador_id'] ?: null,
            ':tercero_nombre' => $datos['tercero_nombre'] ?: null,
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'] ?? 10.00,
            ':tipo_prestamo' => $datos['tipo_prestamo'] ?? 'DIRECTO',
            ':es_autoprestamo' => $datos['es_autoprestamo'] ?? 0
        ]);
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

            // Verificar si con este abono el capital prestado queda totalmente cubierto
            $stmtCheck = $this->db->prepare("
                SELECT p.monto_prestado, IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital
                FROM prestamos p
                LEFT JOIN abonos_prestamos ap ON p.id = ap.prestamo_id
                WHERE p.id = :id
                GROUP BY p.id
            ");
            $stmtCheck->execute([':id' => $prestamoId]);
            $row = $stmtCheck->fetch();

            if ($row && (float)$row['total_capital'] >= (float)$row['monto_prestado']) {
                $stmtClose = $this->db->prepare("UPDATE prestamos SET estado = 'PAGADO' WHERE id = :id");
                $stmtClose->execute([':id' => $prestamoId]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
