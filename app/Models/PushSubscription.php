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

    /** Igual que registrarNotificacion pero devuelve el id creado (0 si falla). */
    public function registrarNotificacionId(string $titulo, string $mensaje, string $destinatarioTipo, ?int $socioId, int $enviadoPorId): int {
        if (!$this->registrarNotificacion($titulo, $mensaje, $destinatarioTipo, $socioId, $enviadoPorId)) {
            return 0;
        }
        return (int)$this->db->lastInsertId();
    }

    /** Suscripciones push: de un socio o de todos (socioId null). Solo id, socio_id y endpoint. */
    public function getSuscripciones(?int $socioId = null): array {
        if ($socioId === null) {
            return $this->db->query("SELECT id, socio_id, endpoint FROM natillera_push_subscriptions")->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT id, socio_id, endpoint FROM natillera_push_subscriptions WHERE socio_id = :s");
        $stmt->execute([':s' => $socioId]);
        return $stmt->fetchAll();
    }

    public function eliminarSuscripcion(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM natillera_push_subscriptions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Registra el resultado de un envio por suscripcion. No lanza error si la tabla aun no existe. */
    public function registrarEnvio(?int $notificacionId, ?int $suscripcionId, ?int $socioId, int $status, bool $ok, string $detalle): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO natillera_push_envios (notificacion_id, suscripcion_id, socio_id, http_status, exitoso, detalle)
                VALUES (:n, :s, :so, :h, :e, :d)
            ");
            $stmt->execute([':n' => $notificacionId, ':s' => $suscripcionId, ':so' => $socioId, ':h' => $status, ':e' => $ok ? 1 : 0, ':d' => mb_substr($detalle, 0, 250)]);
        } catch (Exception $e) {
            error_log('[push] no se pudo registrar el envio: ' . $e->getMessage());
        }
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
