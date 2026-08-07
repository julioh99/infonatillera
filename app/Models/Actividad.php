<?php
// app/Models/Actividad.php

require_once __DIR__ . '/../../core/Model.php';

class Actividad extends Model {

    public function getTodasActividades(): array {
        $stmt = $this->db->query("
            SELECT a.*, u.nombre_completo as creador_nombre,
                   (SELECT COUNT(*) FROM actividad_participantes WHERE actividad_id = a.id) as total_participantes
            FROM actividades a
            JOIN usuarios u ON a.creado_por_usuario_id = u.id
            ORDER BY a.fecha_actividad DESC
        ");
        return $stmt->fetchAll();
    }

    public function getActividadById($id) {
        $stmt = $this->db->prepare("SELECT * FROM actividades WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getParticipantes(int $actividadId): array {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nombre_completo, u.cedula
            FROM actividad_participantes ap
            JOIN usuarios u ON ap.socio_id = u.id
            WHERE ap.actividad_id = :actividad_id
            ORDER BY u.nombre_completo ASC
        ");
        $stmt->execute([':actividad_id' => $actividadId]);
        $participantes = $stmt->fetchAll();

        foreach ($participantes as &$p) {
            $p['abonos'] = $this->getAbonosPorParticipante((int)$p['id']);
        }
        unset($p);

        return $participantes;
    }

    public function getAbonosPorParticipante(int $participanteId): array {
        $stmt = $this->db->prepare("
            SELECT aa.*, u.nombre_completo as registrado_por_nombre
            FROM abonos_actividades aa
            JOIN usuarios u ON aa.registrado_por_usuario_id = u.id
            WHERE aa.actividad_participante_id = :id
            ORDER BY aa.fecha_abono ASC, aa.id ASC
        ");
        $stmt->execute([':id' => $participanteId]);
        return $stmt->fetchAll();
    }

    public function recalcularMontoPagado(int $participanteId): bool {
        $stmtSum = $this->db->prepare("SELECT IFNULL(SUM(monto_abono), 0) as total FROM abonos_actividades WHERE actividad_participante_id = :id");
        $stmtSum->execute([':id' => $participanteId]);
        $totalPagado = (float)$stmtSum->fetch()['total'];

        $stmtCheck = $this->db->prepare("SELECT cuota_asignada FROM actividad_participantes WHERE id = :id");
        $stmtCheck->execute([':id' => $participanteId]);
        $row = $stmtCheck->fetch();
        if (!$row) return false;

        $cuotaAsignada = (float)$row['cuota_asignada'];
        $estado = ($totalPagado >= $cuotaAsignada && $cuotaAsignada > 0) ? 'PAGADO' : (($cuotaAsignada <= 0 && $totalPagado >= 0) ? 'PAGADO' : 'PENDIENTE');

        $stmtUpd = $this->db->prepare("
            UPDATE actividad_participantes 
            SET monto_pagado = :monto_pagado, estado_pago = :estado
            WHERE id = :id
        ");
        return $stmtUpd->execute([
            ':id' => $participanteId,
            ':monto_pagado' => $totalPagado,
            ':estado' => $estado
        ]);
    }

    public function registrarAbono(int $participanteId, float $montoAbono, int $registradoPorId, string $observacion = ''): bool {
        if ($montoAbono <= 0) return false;

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO abonos_actividades (actividad_participante_id, monto_abono, observacion, registrado_por_usuario_id)
                VALUES (:participante_id, :monto, :obs, :registrado_por)
            ");
            $stmt->execute([
                ':participante_id' => $participanteId,
                ':monto' => $montoAbono,
                ':obs' => $observacion,
                ':registrado_por' => $registradoPorId
            ]);

            $this->recalcularMontoPagado($participanteId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function eliminarAbono(int $abonoId): bool {
        $stmt = $this->db->prepare("SELECT actividad_participante_id FROM abonos_actividades WHERE id = :id");
        $stmt->execute([':id' => $abonoId]);
        $row = $stmt->fetch();
        if (!$row) return false;

        $participanteId = (int)$row['actividad_participante_id'];

        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM abonos_actividades WHERE id = :id");
            $stmtDel->execute([':id' => $abonoId]);

            $this->recalcularMontoPagado($participanteId);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function crearActividad(array $datos, array $participantesCuotas): bool {
        $this->db->beginTransaction();
        try {
            $ingresos = (float)($datos['ingresos_totales'] ?? 0);
            $gastos = (float)($datos['gastos_totales'] ?? 0);
            $cuotaPorSocio = (float)($datos['cuota_por_socio'] ?? 0);
            $gananciaNeta = $ingresos - $gastos;

            $stmt = $this->db->prepare("
                INSERT INTO actividades (nombre_actividad, descripcion, fecha_actividad, ingresos_totales, gastos_totales, ganancia_neta, cuota_por_socio, creado_por_usuario_id)
                VALUES (:nombre, :descripcion, :fecha, :ingresos, :gastos, :ganancia, :cuota_socio, :creado_por)
            ");
            $stmt->execute([
                ':nombre' => $datos['nombre_actividad'],
                ':descripcion' => $datos['descripcion'] ?? '',
                ':fecha' => $datos['fecha_actividad'],
                ':ingresos' => $ingresos,
                ':gastos' => $gastos,
                ':ganancia' => $gananciaNeta,
                ':cuota_socio' => $cuotaPorSocio,
                ':creado_por' => $datos['creado_por_usuario_id']
            ]);

            $actividadId = (int)$this->db->lastInsertId();
            $numParticipantes = count($participantesCuotas);

            if ($numParticipantes > 0) {
                $gananciaPorSocio = $gananciaNeta > 0 ? round($gananciaNeta / $numParticipantes, 2) : 0.00;

                $stmtPart = $this->db->prepare("
                    INSERT INTO actividad_participantes (actividad_id, socio_id, cuota_asignada, monto_pagado, ganancia_asignada, estado_pago)
                    VALUES (:actividad_id, :socio_id, :cuota_asignada, 0.00, :ganancia, :estado_pago)
                ");
                foreach ($participantesCuotas as $sId => $cuotaIndiv) {
                    $cMonto = (float)$cuotaIndiv;
                    $estadoInicial = $cMonto > 0 ? 'PENDIENTE' : 'PAGADO';

                    $stmtPart->execute([
                        ':actividad_id' => $actividadId,
                        ':socio_id' => (int)$sId,
                        ':cuota_asignada' => $cMonto,
                        ':ganancia' => $gananciaPorSocio,
                        ':estado_pago' => $estadoInicial
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function actualizarPagoParticipante(int $participanteId, float $montoPagado, int $registradoPorId = 1, string $observacion = 'Abono directo'): bool {
        $stmtCheck = $this->db->prepare("SELECT cuota_asignada, monto_pagado FROM actividad_participantes WHERE id = :id");
        $stmtCheck->execute([':id' => $participanteId]);
        $row = $stmtCheck->fetch();
        if (!$row) return false;

        $montoActual = (float)$row['monto_pagado'];
        $diferencia = $montoPagado - $montoActual;

        if ($diferencia > 0) {
            return $this->registrarAbono($participanteId, $diferencia, $registradoPorId, $observacion);
        }

        // Si es igual o menor y se desea fijar directamente:
        $cuotaAsignada = (float)$row['cuota_asignada'];
        $estado = ($montoPagado >= $cuotaAsignada && $cuotaAsignada > 0) ? 'PAGADO' : 'PENDIENTE';

        $stmt = $this->db->prepare("
            UPDATE actividad_participantes 
            SET monto_pagado = :monto_pagado, estado_pago = :estado
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $participanteId,
            ':monto_pagado' => $montoPagado,
            ':estado' => $estado
        ]);
    }
}
