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

    public function crearActividad(array $datos, array $socioIds): bool {
        $this->db->beginTransaction();
        try {
            $ingresos = (float)($datos['ingresos_totales'] ?? 0);
            $gastos = (float)($datos['gastos_totales'] ?? 0);
            $gananciaNeta = $ingresos - $gastos;

            $stmt = $this->db->prepare("
                INSERT INTO actividades (nombre_actividad, descripcion, fecha_actividad, ingresos_totales, gastos_totales, ganancia_neta, creado_por_usuario_id)
                VALUES (:nombre, :descripcion, :fecha, :ingresos, :gastos, :ganancia, :creado_por)
            ");
            $stmt->execute([
                ':nombre' => $datos['nombre_actividad'],
                ':descripcion' => $datos['descripcion'] ?? '',
                ':fecha' => $datos['fecha_actividad'],
                ':ingresos' => $ingresos,
                ':gastos' => $gastos,
                ':ganancia' => $gananciaNeta,
                ':creado_por' => $datos['creado_por_usuario_id']
            ]);

            $actividadId = (int)$this->db->lastInsertId();
            $numParticipantes = count($socioIds);

            if ($numParticipantes > 0) {
                $gananciaPorSocio = $gananciaNeta > 0 ? round($gananciaNeta / $numParticipantes, 2) : 0.00;
                $stmtPart = $this->db->prepare("
                    INSERT INTO actividad_participantes (actividad_id, socio_id, cuota_asignada, ganancia_asignada, estado_pago)
                    VALUES (:actividad_id, :socio_id, 0.00, :ganancia, 'PAGADO')
                ");
                foreach ($socioIds as $sId) {
                    $stmtPart->execute([
                        ':actividad_id' => $actividadId,
                        ':socio_id' => (int)$sId,
                        ':ganancia' => $gananciaPorSocio
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
}
