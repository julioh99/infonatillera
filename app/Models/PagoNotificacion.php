<?php
// app/Models/PagoNotificacion.php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/Usuario.php';

class PagoNotificacion extends Model {

    /**
     * Registrar o actualizar notificación de pago de cuota de reunión (Llamado a lista).
     */
    public function registrarNotificacionCuotaReunion(
        int $socioId,
        int $reunionId,
        string $titulo,
        string $concepto,
        float $montoCuota,
        float $montoRonda,
        float $montoRifa,
        float $montoExtra,
        float $montoTotal,
        array $detalles,
        int $registradoPorId
    ): bool {
        // Verificar si ya existe registro previo para este socio y reunión
        $stmtCheck = $this->db->prepare("
            SELECT id, monto_total, leido 
            FROM natillera_pagos_notificaciones 
            WHERE socio_id = :socio_id AND reunion_id = :reunion_id AND tipo_pago = 'CUOTA_REUNION'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCheck->execute([
            ':socio_id' => $socioId,
            ':reunion_id' => $reunionId
        ]);
        $existente = $stmtCheck->fetch();

        $detallesJson = json_encode($detalles, JSON_UNESCAPED_UNICODE);

        if (!$existente) {
            // Nuevo registro de pago no notificado
            $stmtInsert = $this->db->prepare("
                INSERT INTO natillera_pagos_notificaciones 
                (socio_id, reunion_id, tipo_pago, referencia_id, titulo, concepto, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra, monto_total, detalles_json, registrado_por_usuario_id, leido)
                VALUES 
                (:socio_id, :reunion_id, 'CUOTA_REUNION', :reunion_id, :titulo, :concepto, :monto_cuota, :monto_ronda, :monto_rifa, :monto_extra, :monto_total, :detalles_json, :registrado_por, 0)
            ");
            return $stmtInsert->execute([
                ':socio_id' => $socioId,
                ':reunion_id' => $reunionId,
                ':titulo' => $titulo,
                ':concepto' => $concepto,
                ':monto_cuota' => $montoCuota,
                ':monto_ronda' => $montoRonda,
                ':monto_rifa' => $montoRifa,
                ':monto_extra' => $montoExtra,
                ':monto_total' => $montoTotal,
                ':detalles_json' => $detallesJson,
                ':registrado_por' => $registradoPorId
            ]);
        } else {
            // Si ya existe y no ha sido leído, actualizamos los datos y montos
            if ((int)$existente['leido'] === 0) {
                $stmtUpdate = $this->db->prepare("
                    UPDATE natillera_pagos_notificaciones 
                    SET titulo = :titulo,
                        concepto = :concepto,
                        monto_cuota = :monto_cuota,
                        monto_aporte_ronda = :monto_ronda,
                        monto_aporte_rifa = :monto_rifa,
                        monto_ahorro_extra = :monto_extra,
                        monto_total = :monto_total,
                        detalles_json = :detalles_json,
                        registrado_por_usuario_id = :registrado_por,
                        fecha_pago = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
                return $stmtUpdate->execute([
                    ':titulo' => $titulo,
                    ':concepto' => $concepto,
                    ':monto_cuota' => $montoCuota,
                    ':monto_ronda' => $montoRonda,
                    ':monto_rifa' => $montoRifa,
                    ':monto_extra' => $montoExtra,
                    ':monto_total' => $montoTotal,
                    ':detalles_json' => $detallesJson,
                    ':registrado_por' => $registradoPorId,
                    ':id' => $existente['id']
                ]);
            } else {
                // Ya fue leído previamente.
                // Si el monto total cambió sustancialmente (se añadió más ahorro extra), reactivamos la notificación
                if (abs((float)$existente['monto_total'] - $montoTotal) >= 1.0) {
                    $stmtUpdate = $this->db->prepare("
                        UPDATE natillera_pagos_notificaciones 
                        SET monto_cuota = :monto_cuota,
                            monto_aporte_ronda = :monto_ronda,
                            monto_aporte_rifa = :monto_rifa,
                            monto_ahorro_extra = :monto_extra,
                            monto_total = :monto_total,
                            detalles_json = :detalles_json,
                            registrado_por_usuario_id = :registrado_por,
                            leido = 0,
                            fecha_leido = NULL,
                            fecha_pago = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    return $stmtUpdate->execute([
                        ':monto_cuota' => $montoCuota,
                        ':monto_ronda' => $montoRonda,
                        ':monto_rifa' => $montoRifa,
                        ':monto_extra' => $montoExtra,
                        ':monto_total' => $montoTotal,
                        ':detalles_json' => $detallesJson,
                        ':registrado_por' => $registradoPorId,
                        ':id' => $existente['id']
                    ]);
                }
                return true; // No cambió nada, se mantiene como ya leído
            }
        }
    }

    /**
     * Eliminar notificación pendiente si la secretaria desmarcó el pago antes de que el socio lo viera.
     */
    public function eliminarNotificacionCuotaReunionSiPendiente(int $socioId, int $reunionId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM natillera_pagos_notificaciones 
            WHERE socio_id = :socio_id AND reunion_id = :reunion_id AND tipo_pago = 'CUOTA_REUNION' AND leido = 0
        ");
        return $stmt->execute([
            ':socio_id' => $socioId,
            ':reunion_id' => $reunionId
        ]);
    }

    /**
     * Registrar notificación de pago por anulación de autopréstamo dentro de las 24 horas.
     */
    public function registrarNotificacionAnulacion24h(
        int $socioId,
        int $reunionId,
        string $titulo,
        string $concepto,
        float $montoCuota,
        float $montoRonda,
        float $montoRifa,
        float $montoTotal,
        array $detalles,
        int $registradoPorId
    ): bool {
        $detallesJson = json_encode($detalles, JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare("
            INSERT INTO natillera_pagos_notificaciones 
            (socio_id, reunion_id, tipo_pago, referencia_id, titulo, concepto, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra, monto_total, detalles_json, registrado_por_usuario_id, leido)
            VALUES 
            (:socio_id, :reunion_id, 'ANULACION_24H', :reunion_id, :titulo, :concepto, :monto_cuota, :monto_ronda, :monto_rifa, 0.00, :monto_total, :detalles_json, :registrado_por, 0)
        ");
        return $stmt->execute([
            ':socio_id' => $socioId,
            ':reunion_id' => $reunionId,
            ':titulo' => $titulo,
            ':concepto' => $concepto,
            ':monto_cuota' => $montoCuota,
            ':monto_ronda' => $montoRonda,
            ':monto_rifa' => $montoRifa,
            ':monto_total' => $montoTotal,
            ':detalles_json' => $detallesJson,
            ':registrado_por' => $registradoPorId
        ]);
    }

    /**
     * Registrar abono a préstamo.
     */
    public function registrarNotificacionAbonoPrestamo(
        int $socioId,
        int $prestamoId,
        ?int $reunionId,
        float $montoCapital,
        float $montoInteres,
        int $registradoPorId,
        array $detallesExtra = []
    ): bool {
        $montoTotal = $montoCapital + $montoInteres;
        $detalles = array_merge([
            'prestamo_id' => $prestamoId,
            'capital_pagado' => $montoCapital,
            'interes_pagado' => $montoInteres,
            'reunion_id' => $reunionId
        ], $detallesExtra);

        $concepto = 'Abono a crédito personal (Capital: $' . number_format($montoCapital, 0, ',', '.') . ' | Interés: $' . number_format($montoInteres, 0, ',', '.') . ')';

        $stmt = $this->db->prepare("
            INSERT INTO natillera_pagos_notificaciones 
            (socio_id, reunion_id, tipo_pago, referencia_id, titulo, concepto, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra, monto_total, detalles_json, registrado_por_usuario_id, leido)
            VALUES 
            (:socio_id, :reunion_id, 'ABONO_PRESTAMO', :prestamo_id, 'Abono a Préstamo Recibido', :concepto, :monto_capital, :monto_interes, 0.00, 0.00, :monto_total, :detalles_json, :registrado_por, 0)
        ");
        return $stmt->execute([
            ':socio_id' => $socioId,
            ':reunion_id' => $reunionId,
            ':prestamo_id' => $prestamoId,
            ':concepto' => $concepto,
            ':monto_capital' => $montoCapital,
            ':monto_interes' => $montoInteres,
            ':monto_total' => $montoTotal,
            ':detalles_json' => json_encode($detalles, JSON_UNESCAPED_UNICODE),
            ':registrado_por' => $registradoPorId
        ]);
    }

    /**
     * Registrar abono a actividad comunitaria.
     */
    public function registrarNotificacionAbonoActividad(
        int $socioId,
        int $actividadId,
        float $montoAbono,
        string $nombreActividad,
        int $registradoPorId
    ): bool {
        $detalles = [
            'actividad_id' => $actividadId,
            'nombre_actividad' => $nombreActividad,
            'monto_abono' => $montoAbono
        ];

        $stmt = $this->db->prepare("
            INSERT INTO natillera_pagos_notificaciones 
            (socio_id, reunion_id, tipo_pago, referencia_id, titulo, concepto, monto_cuota, monto_total, detalles_json, registrado_por_usuario_id, leido)
            VALUES 
            (:socio_id, NULL, 'ABONO_ACTIVIDAD', :actividad_id, 'Pago de Actividad Recibido', :concepto, :monto, :monto, :detalles_json, :registrado_por, 0)
        ");
        return $stmt->execute([
            ':socio_id' => $socioId,
            ':actividad_id' => $actividadId,
            ':concepto' => "Abono para la actividad: {$nombreActividad}",
            ':monto' => $montoAbono,
            ':detalles_json' => json_encode($detalles, JSON_UNESCAPED_UNICODE),
            ':registrado_por' => $registradoPorId
        ]);
    }

    /**
     * Obtener todos los pagos pendientes de notificar al socio (leido = 0).
     */
    public function getPagosPendientesPorSocio(int $socioId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   u.nombre_completo as registrado_por_nombre,
                   r.numero_quincena,
                   r.fecha_reunion
            FROM natillera_pagos_notificaciones p
            JOIN natillera_usuarios u ON p.registrado_por_usuario_id = u.id
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            WHERE p.socio_id = :socio_id AND p.leido = 0
            ORDER BY p.fecha_pago ASC, p.id ASC
        ");
        $stmt->execute([':socio_id' => $socioId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['detalles'] = !empty($r['detalles_json']) ? json_decode($r['detalles_json'], true) : [];
        }
        unset($r);

        return $rows;
    }

    /**
     * Marcar lista de notificaciones de pago como leídas / vistas por el socio.
     */
    public function marcarPagosComoLeidos(array $pagoIds, int $socioId): bool {
        if (empty($pagoIds)) {
            return false;
        }

        $sanitizedIds = array_map('intval', $pagoIds);
        $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));

        $sql = "
            UPDATE natillera_pagos_notificaciones 
            SET leido = 1, fecha_leido = CURRENT_TIMESTAMP 
            WHERE socio_id = ? AND id IN ($placeholders)
        ";

        $stmt = $this->db->prepare($sql);
        $params = array_merge([$socioId], $sanitizedIds);
        return $stmt->execute($params);
    }

    /**
     * Obtener saldo actual acumulado de ahorro del socio.
     */
    public function getSaldoAhorradoActualSocio(int $socioId): float {
        $usuarioModel = new Usuario();
        $resumen = $usuarioModel->getResumenFinancieroSocio($socioId);
        return (float)($resumen['total_ahorrado'] ?? 0.0);
    }
}
