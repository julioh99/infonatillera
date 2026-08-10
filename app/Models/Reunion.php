<?php
// app/Models/Reunion.php

require_once __DIR__ . '/../../core/Model.php';

class Reunion extends Model {

    public function getReuniones(): array {
        $stmt = $this->db->query("
            SELECT r.*, u.nombre_completo as ganador_nombre
            FROM natillera_reuniones r
            LEFT JOIN natillera_usuarios u ON r.ganador_socio_id = u.id
            ORDER BY 
                CASE 
                    WHEN r.estado = 'LLAMADO_CERRADO' THEN 1 
                    WHEN r.estado = 'EN_PROCESO' THEN 2
                    WHEN r.estado = 'PROGRAMADA' THEN 3
                    ELSE 4 
                END,
                r.numero_quincena ASC
        ");
        return $stmt->fetchAll();
    }

    public function getReunionActual() {
        // Prioridad 1: Reunión que tenga el llamado a lista cerrado (LLAMADO_CERRADO)
        $stmtLlamado = $this->db->query("
            SELECT r.* 
            FROM natillera_reuniones r
            WHERE r.estado = 'LLAMADO_CERRADO'
            ORDER BY r.numero_quincena DESC 
            LIMIT 1
        ");
        $reunion = $stmtLlamado->fetch();

        if (!$reunion) {
            // Prioridad 2: Primera quincena sin cerrar y sin llamado
            $stmt = $this->db->query("
                SELECT r.* 
                FROM natillera_reuniones r
                WHERE r.estado != 'CERRADA'
                  AND r.id NOT IN (
                      SELECT DISTINCT reunion_id FROM natillera_ahorros_cuotas
                  )
                ORDER BY r.numero_quincena ASC 
                LIMIT 1
            ");
            $reunion = $stmt->fetch();
        }

        if (!$reunion) {
            $stmtPending = $this->db->query("
                SELECT r.* FROM natillera_reuniones r
                WHERE r.estado IN ('PROGRAMADA', 'EN_PROCESO')
                ORDER BY r.numero_quincena ASC LIMIT 1
            ");
            $reunion = $stmtPending->fetch();
        }

        if (!$reunion) {
            $stmtLast = $this->db->query("SELECT * FROM natillera_reuniones ORDER BY numero_quincena DESC LIMIT 1");
            $reunion = $stmtLast->fetch();
        }

        return $reunion ?: null;
    }

    public function getReunionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM natillera_reuniones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function crearReunion(array $datos): bool {
        // Calcular número de quincena automáticamente
        $stmtMax = $this->db->query("SELECT IFNULL(MAX(numero_quincena), 0) + 1 as siguiente FROM natillera_reuniones");
        $siguienteNum = (int)$stmtMax->fetch()['siguiente'];

        $stmt = $this->db->prepare("
            INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
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
            UPDATE natillera_reuniones SET 
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
            SELECT ac.*, u.nombre_completo, u.cedula, p.anulado_sin_interes, u.id
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_usuarios u ON ac.socio_id = u.id
            LEFT JOIN natillera_prestamos p ON ac.prestamo_id_asociado = p.id
            WHERE ac.reunion_id = :reunion_id
            ORDER BY u.id ASC
        ");
        $stmt->execute([':reunion_id' => $reunionId]);
        return $stmt->fetchAll();
    }

    public function guardarLlamadoListaBatch(int $reunionId, array $registros): bool {
        $this->db->beginTransaction();

        try {
            $stmtReunion = $this->db->prepare("SELECT valor_cuota_base FROM natillera_reuniones WHERE id = :id");
            $stmtReunion->execute([':id' => $reunionId]);
            $reunion = $stmtReunion->fetch();
            $valorCuotaBase = (float)($reunion['valor_cuota_base'] ?? 55000);

            if ($valorCuotaBase >= 65000) {
                $aporteRondaBase = 20000.00;
                $aporteRifaBase = 5000.00;
            } else if ($valorCuotaBase >= 60000) {
                $aporteRondaBase = 10000.00;
                $aporteRifaBase = 10000.00;
            } else {
                $aporteRondaBase = 10000.00;
                $aporteRifaBase = 5000.00;
            }
            $ahorroNetoConstante = 40000.00; // Ahorro neto acreditado al socio es SIEMPRE $40.000 COP

            // Eliminar registros anteriores de esta reunión si se está re-procesando
            $stmtDelAhorros = $this->db->prepare("DELETE FROM natillera_ahorros_cuotas WHERE reunion_id = :reunion_id");
            $stmtDelAhorros->execute([':reunion_id' => $reunionId]);

            $stmtInsertAhorro = $this->db->prepare("
                INSERT INTO natillera_ahorros_cuotas (reunion_id, socio_id, cuota_pagada, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra, autoprestamo_generado, prestamo_id_asociado)
                VALUES (:reunion_id, :socio_id, :cuota_pagada, :monto_cuota, :monto_aporte_ronda, :monto_aporte_rifa, :monto_ahorro_extra, :autoprestamo_generado, :prestamo_id_asociado)
            ");

            $stmtInsertPrestamo = $this->db->prepare("
                INSERT INTO natillera_prestamos (socio_deudor_id, reunion_id, monto_prestado, tasa_interes_mensual, tipo_prestamo, es_autoprestamo)
                VALUES (:socio_deudor_id, :reunion_id, :monto_prestado, 10.00, 'AUTOPRESTAMO', 1)
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
                        ':reunion_id' => $reunionId,
                        ':monto_prestado' => $valorCuotaBase
                    ]);
                    $prestamoId = (int)$this->db->lastInsertId();
                }

                // El dinero de la cuotas base ($40k, $10k ronda, $5k rifa) ingresa a la natillera por pago directo o por desembolso de autopréstamo
                $cuotaIngresada = ($pagouCuota || !empty($prestamoId));

                $stmtInsertAhorro->execute([
                    ':reunion_id' => $reunionId,
                    ':socio_id' => $socioId,
                    ':cuota_pagada' => $cuotaIngresada ? 1 : 0,
                    ':monto_cuota' => $cuotaIngresada ? $ahorroNetoConstante : 0.00,
                    ':monto_aporte_ronda' => $cuotaIngresada ? $aporteRondaBase : 0.00,
                    ':monto_aporte_rifa' => $cuotaIngresada ? $aporteRifaBase : 0.00,
                    ':monto_ahorro_extra' => $ahorroExtra,
                    ':autoprestamo_generado' => $prestamoId ? 1 : 0,
                    ':prestamo_id_asociado' => $prestamoId
                ]);
            }

            // Marcar el llamado a lista de la reunión como realizado (LLAMADO_CERRADO)
            $stmtState = $this->db->prepare("UPDATE natillera_reuniones SET estado = 'LLAMADO_CERRADO' WHERE id = :id AND estado != 'CERRADA'");
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
            $stmtAhorro = $this->db->prepare("SELECT * FROM natillera_ahorros_cuotas WHERE id = :id");
            $stmtAhorro->execute([':id' => $ahorroId]);
            $ahorro = $stmtAhorro->fetch();

            if ($ahorro && $ahorro['prestamo_id_asociado']) {
                $prestamoId = (int)$ahorro['prestamo_id_asociado'];

                // Consultar cuota base de la reunión para calcular desgloses
                $stmtReunion = $this->db->prepare("SELECT valor_cuota_base FROM natillera_reuniones WHERE id = :id");
                $stmtReunion->execute([':id' => $ahorro['reunion_id']]);
                $rInfo = $stmtReunion->fetch();
                $vBase = (float)($rInfo['valor_cuota_base'] ?? 55000);

                if ($vBase >= 65000) {
                    $apRonda = 20000.00;
                    $apRifa = 5000.00;
                } else if ($vBase >= 60000) {
                    $apRonda = 10000.00;
                    $apRifa = 10000.00;
                } else {
                    $apRonda = 10000.00;
                    $apRifa = 5000.00;
                }

                // Marcar préstamo como anulado sin interés y pagado
                $stmtP = $this->db->prepare("
                    UPDATE natillera_prestamos 
                    SET anulado_sin_interes = 1, estado = 'PAGADO' 
                    WHERE id = :p_id
                ");
                $stmtP->execute([':p_id' => $prestamoId]);

                // Actualizar ahorro a cuota pagada ($40.000 COP netos)
                $stmtA = $this->db->prepare("
                    UPDATE natillera_ahorros_cuotas 
                    SET cuota_pagada = 1, 
                        monto_cuota = 40000.00,
                        monto_aporte_ronda = :ap_ronda,
                        monto_aporte_rifa = :ap_rifa,
                        autoprestamo_generado = 0 
                    WHERE id = :a_id
                ");
                $stmtA->execute([
                    ':a_id' => $ahorroId,
                    ':ap_ronda' => $apRonda,
                    ':ap_rifa' => $apRifa
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
