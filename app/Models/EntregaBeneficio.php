<?php
// app/Models/EntregaBeneficio.php

require_once __DIR__ . '/../../core/Model.php';

class EntregaBeneficio extends Model {

    public function registrarEntrega(array $datos): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO entregas_beneficios 
                (reunion_id, socio_id, tipo_beneficio, monto_entregado, firma_digital_path, foto_evidencia_path, entregado_por_usuario_id)
                VALUES (:reunion_id, :socio_id, :tipo_beneficio, :monto, :firma, :foto, :entregado_por)
            ");
            $stmt->execute([
                ':reunion_id' => $datos['reunion_id'],
                ':socio_id' => $datos['socio_id'],
                ':tipo_beneficio' => $datos['tipo_beneficio'],
                ':monto' => $datos['monto_entregado'],
                ':firma' => $datos['firma_digital_path'] ?? null,
                ':foto' => $datos['foto_evidencia_path'] ?? null,
                ':entregado_por' => $datos['entregado_por_usuario_id']
            ]);

            // Actualizar socio ganador en la reunión si aplica
            $stmtUpd = $this->db->prepare("
                UPDATE reuniones 
                SET ganador_socio_id = :socio_id 
                WHERE id = :reunion_id AND (ganador_socio_id IS NULL OR ganador_socio_id = 0)
            ");
            $stmtUpd->execute([
                ':socio_id' => $datos['socio_id'],
                ':reunion_id' => $datos['reunion_id']
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getTodasEntregas(): array {
        $stmt = $this->db->query("
            SELECT eb.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula,
                   r.numero_quincena, r.fecha_reunion,
                   u2.nombre_completo as entregado_por_nombre
            FROM entregas_beneficios eb
            JOIN usuarios u ON eb.socio_id = u.id
            JOIN reuniones r ON eb.reunion_id = r.id
            JOIN usuarios u2 ON eb.entregado_por_usuario_id = u2.id
            ORDER BY eb.fecha_entrega DESC
        ");
        return $stmt->fetchAll();
    }

    public function getSociosPendientes(string $tipoBeneficio): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.nombre_completo, u.cedula
            FROM usuarios u
            WHERE u.id NOT IN (
                SELECT socio_id FROM entregas_beneficios WHERE tipo_beneficio = :tipo
            )
            ORDER BY u.nombre_completo ASC
        ");
        $stmt->execute([':tipo' => $tipoBeneficio]);
        return $stmt->fetchAll();
    }
}
