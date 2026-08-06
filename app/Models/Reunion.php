<?php
// app/Models/Reunion.php

require_once __DIR__ . '/../../core/Model.php';

class Reunion extends Model {

    public function getReuniones(): array {
        $stmt = $this->db->query("
            SELECT r.*, u.nombre_completo as ganador_nombre
            FROM reuniones r
            LEFT JOIN usuarios u ON r.ganador_socio_id = u.id
            ORDER BY r.numero_quincena ASC
        ");
        return $stmt->fetchAll();
    }

    public function getReunionActual() {
        // Busca la primera reunión programada o en proceso
        $stmt = $this->db->query("
            SELECT * FROM reuniones 
            WHERE estado IN ('PROGRAMADA', 'EN_PROCESO') 
            ORDER BY numero_quincena ASC LIMIT 1
        ");
        $reunion = $stmt->fetch();
        if (!$reunion) {
            // Si todas se cerraron, retorna la última
            $stmtLast = $this->db->query("SELECT * FROM reuniones ORDER BY numero_quincena DESC LIMIT 1");
            $reunion = $stmtLast->fetch();
        }
        return $reunion ?: null;
    }

    public function getReunionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM reuniones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crearReunion(array $datos): bool {
        // Calcular número de quincena automáticamente
        $stmtMax = $this->db->query("SELECT IFNULL(MAX(numero_quincena), 0) + 1 as siguiente FROM reuniones");
        $siguienteNum = (int)$stmtMax->fetch()['siguiente'];

        $stmt = $this->db->prepare("
            INSERT INTO reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
            VALUES (:num, :fecha, :hora, :cuota, :evento, :premio, 'PROGRAMADA')
        ");
        return $stmt->execute([
            ':num' => $siguienteNum,
            ':fecha' => $datos['fecha_reunion'],
            ':hora' => $datos['hora_reunion'] ?? '14:00:00',
            ':cuota' => $datos['valor_cuota_base'] ?? 55000.00,
            ':evento' => $datos['tipo_evento_extra'] ?? 'NINGUNO',
            ':premio' => $datos['monto_premio_extra'] ?? 0.00
        ]);
    }

    public function actualizarReunion(int $id, array $datos): bool {
        $stmt = $this->db->prepare("
            UPDATE reuniones SET 
                fecha_reunion = :fecha,
                hora_reunion = :hora,
                valor_cuota_base = :cuota,
                tipo_evento_extra = :evento,
                monto_premio_extra = :premio,
                ganador_socio_id = :ganador,
                estado = :estado
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':fecha' => $datos['fecha_reunion'],
            ':hora' => $datos['hora_reunion'] ?? '14:00:00',
            ':cuota' => $datos['valor_cuota_base'],
            ':evento' => $datos['tipo_evento_extra'] ?? 'NINGUNO',
            ':premio' => $datos['monto_premio_extra'] ?? 0.00,
            ':ganador' => !empty($datos['ganador_socio_id']) ? $datos['ganador_socio_id'] : null,
            ':estado' => $datos['estado'] ?? 'PROGRAMADA'
        ]);
    }

    public function getAhorrosPorReunion(int $reunionId): array {
        $stmt = $this->db->prepare("
            SELECT ac.*, u.nombre_completo, u.cedula, p.anulado_sin_interes
            FROM ahorros_cuotas ac
            JOIN usuarios u ON ac.socio_id = u.id
            LEFT JOIN prestamos p ON ac.prestamo_id_asociado = p.id
            WHERE ac.reunion_id = :reunion_id
            ORDER BY u.nombre_completo ASC
        ");
        $stmt->execute([':reunion_id' => $reunionId]);
        return $stmt->fetchAll();
    }

    public function guardarLlamadoListaBatch(int $reunionId, array $registros): bool {
        $this->db->beginTransaction();

        try {
            $stmtReunion = $this->db->prepare("SELECT valor_cuota_base FROM reuniones WHERE id = :id");
            $stmtReunion->execute([':id' => $reunionId]);
            $reunion = $stmtReunion->fetch();
            $valorCuotaBase = (float)($reunion['valor_cuota_base'] ?? 55000);

            // Eliminar registros anteriores de esta reunión si se está re-procesando
            $stmtDelAhorros = $this->db->prepare("DELETE FROM ahorros_cuotas WHERE reunion_id = :reunion_id");
            $stmtDelAhorros->execute([':reunion_id' => $reunionId]);

            $stmtInsertAhorro = $this->db->prepare("
                INSERT INTO ahorros_cuotas (reunion_id, socio_id, cuota_pagada, monto_cuota, monto_ahorro_extra, autoprestamo_generado, prestamo_id_asociado)
                VALUES (:reunion_id, :socio_id, :cuota_pagada, :monto_cuota, :monto_ahorro_extra, :autoprestamo_generado, :prestamo_id_asociado)
            ");

            $stmtInsertPrestamo = $this->db->prepare("
                INSERT INTO prestamos (socio_deudor_id, monto_prestado, tasa_interes_mensual, tipo_prestamo, es_autoprestamo)
                VALUES (:socio_deudor_id, :monto_prestado, 10.00, 'AUTOPRESTAMO', 1)
            ");

            foreach ($registros as $reg) {
                $socioId = (int)$reg['socio_id'];
                $pagouCuota = isset($reg['pagou_cuota']) && ($reg['pagou_cuota'] == 1 || $reg['pagou_cuota'] === 'true');
                $ahorroExtra = isset($reg['ahorro_extra']) ? (float)$reg['ahorro_extra'] : 0.0;
                $generarAutoprestamo = isset($reg['generar_autoprestamo']) && ($reg['generar_autoprestamo'] == 1 || $reg['generar_autoprestamo'] === 'true');

                $prestamoId = null;

                if (!$pagouCuota && $generarAutoprestamo) {
                    $stmtInsertPrestamo->execute([
                        ':socio_deudor_id' => $socioId,
                        ':monto_prestado' => $valorCuotaBase
                    ]);
                    $prestamoId = (int)$this->db->lastInsertId();
                }

                $stmtInsertAhorro->execute([
                    ':reunion_id' => $reunionId,
                    ':socio_id' => $socioId,
                    ':cuota_pagada' => $pagouCuota ? 1 : 0,
                    ':monto_cuota' => $valorCuotaBase,
                    ':monto_ahorro_extra' => $ahorroExtra,
                    ':autoprestamo_generado' => $prestamoId ? 1 : 0,
                    ':prestamo_id_asociado' => $prestamoId
                ]);
            }

            // Marcar reunión como EN_PROCESO
            $stmtState = $this->db->prepare("UPDATE reuniones SET estado = 'EN_PROCESO' WHERE id = :id");
            $stmtState->execute([':id' => $reunionId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function anularAutoprestamo24Horas(int $ahorroId): bool {
        $this->db->beginTransaction();
        try {
            $stmtAhorro = $this->db->prepare("SELECT * FROM ahorros_cuotas WHERE id = :id");
            $stmtAhorro->execute([':id' => $ahorroId]);
            $ahorro = $stmtAhorro->fetch();

            if ($ahorro && $ahorro['prestamo_id_asociado']) {
                $prestamoId = (int)$ahorro['prestamo_id_asociado'];

                // Marcar préstamo como anulado sin interés y pagado
                $stmtP = $this->db->prepare("
                    UPDATE prestamos 
                    SET anulado_sin_interes = 1, estado = 'PAGADO' 
                    WHERE id = :p_id
                ");
                $stmtP->execute([':p_id' => $prestamoId]);

                // Actualizar ahorro a cuota pagada
                $stmtA = $this->db->prepare("
                    UPDATE ahorros_cuotas 
                    SET cuota_pagada = 1, autoprestamo_generado = 0 
                    WHERE id = :a_id
                ");
                $stmtA->execute([':a_id' => $ahorroId]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
