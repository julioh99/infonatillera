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
        return $stmt->fetchAll();
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

    public function actualizarPagoParticipante(int $participanteId, float $montoPagado): bool {
        $stmtCheck = $this->db->prepare("SELECT cuota_asignada FROM actividad_participantes WHERE id = :id");
        $stmtCheck->execute([':id' => $participanteId]);
        $row = $stmtCheck->fetch();
        if (!$row) return false;

        $cuotaAsignada = (float)$row['cuota_asignada'];
        $estado = ($montoPagado >= $cuotaAsignada) ? 'PAGADO' : 'PENDIENTE';

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
