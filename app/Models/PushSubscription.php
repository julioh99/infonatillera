<?php
// app/Models/PushSubscription.php

require_once __DIR__ . '/../../core/Model.php';

class PushSubscription extends Model {

    public function guardarSuscripcion(int $socioId, string $endpoint, string $p256dh, string $auth): bool {
        $stmtDel = $this->db->prepare("DELETE FROM natillera_push_subscriptions WHERE endpoint = :endpoint");
        $stmtDel->execute([':endpoint' => $endpoint]);

        $stmt = $this->db->prepare("
            INSERT INTO natillera_push_subscriptions (socio_id, endpoint, p256dh, auth)
            VALUES (:socio_id, :endpoint, :p256dh, :auth)
        ");
        return $stmt->execute([
            ':socio_id' => $socioId,
            ':endpoint' => $endpoint,
            ':p256dh' => $p256dh,
            ':auth' => $auth
        ]);
    }

    public function registrarNotificacion(string $titulo, string $mensaje, string $destinatarioTipo, ?int $socioId, int $enviadoPorId): bool {
        $stmt = $this->db->prepare("
            INSERT INTO natillera_notificaciones (titulo, mensaje, destinatario_tipo, socio_id, enviado_por_usuario_id)
            VALUES (:titulo, :mensaje, :destinatario_tipo, :socio_id, :enviado_por)
        ");
        return $stmt->execute([
            ':titulo' => $titulo,
            ':mensaje' => $mensaje,
            ':destinatario_tipo' => $destinatarioTipo,
            ':socio_id' => $socioId,
            ':enviado_por' => $enviadoPorId
        ]);
    }

    public function getHistorialNotificaciones(): array {
        $stmt = $this->db->query("
            SELECT n.*, u_rem.nombre_completo as remitente_nombre, u_dest.nombre_completo as destinatario_nombre
            FROM natillera_notificaciones n
            JOIN natillera_usuarios u_rem ON n.enviado_por_usuario_id = u_rem.id
            LEFT JOIN natillera_usuarios u_dest ON n.socio_id = u_dest.id
            ORDER BY n.fecha_envio DESC
        ");
        return $stmt->fetchAll();
    }

    public function getNotificacionesPorSocio(int $socioId): array {
        $stmt = $this->db->prepare("
            SELECT n.*, u_rem.nombre_completo as remitente_nombre
            FROM natillera_notificaciones n
            JOIN natillera_usuarios u_rem ON n.enviado_por_usuario_id = u_rem.id
            WHERE n.destinatario_tipo = 'TODOS' OR n.socio_id = :socio_id
            ORDER BY n.fecha_envio DESC
            LIMIT 20
        ");
        $stmt->execute([':socio_id' => $socioId]);
        return $stmt->fetchAll();
    }

    public function getNotificacionesPendientesPorSocio(int $socioId): array {
        $stmt = $this->db->prepare("
            SELECT n.*, u_rem.nombre_completo as remitente_nombre
            FROM natillera_notificaciones n
            JOIN natillera_usuarios u_rem ON n.enviado_por_usuario_id = u_rem.id
            LEFT JOIN natillera_notificaciones_leidas nl ON (n.id = nl.notificacion_id AND nl.socio_id = :socio_id)
            WHERE (n.destinatario_tipo = 'TODOS' OR n.socio_id = :socio_id)
              AND nl.id IS NULL
            ORDER BY n.fecha_envio ASC
        ");
        $stmt->execute([':socio_id' => $socioId]);
        return $stmt->fetchAll();
    }

    public function marcarNotificacionComoLeida(int $notificacionId, int $socioId): bool {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO natillera_notificaciones_leidas (notificacion_id, socio_id)
            VALUES (:notificacion_id, :socio_id)
        ");
        return $stmt->execute([
            ':notificacion_id' => $notificacionId,
            ':socio_id' => $socioId
        ]);
    }
}
