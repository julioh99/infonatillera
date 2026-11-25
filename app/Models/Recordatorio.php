<?php
// app/Models/Recordatorio.php
// Datos para los recordatorios automaticos de reunion (scripts/enviar_recordatorios.php).

require_once __DIR__ . '/../../core/Model.php';

class Recordatorio extends Model {

    /** Reuniones aun abiertas (PROGRAMADA / EN_PROCESO) cuya fecha es $fecha (Y-m-d). */
    public function reunionesDelDia(string $fecha): array {
        $stmt = $this->db->prepare("
            SELECT id, numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, estado
            FROM natillera_reuniones
            WHERE fecha_reunion = :f AND estado IN ('PROGRAMADA', 'EN_PROCESO')
            ORDER BY hora_reunion
        ");
        $stmt->execute([':f' => $fecha]);
        return $stmt->fetchAll();
    }

    /**
     * Socios activos que aun no tienen la cuota pagada en la reunion, o todos si $soloPendientes es false.
     * Excluye los que ya recibieron esta franja.
     */
    public function destinatarios(int $reunionId, string $franja, bool $soloPendientes): array {
        $sql = "
            SELECT u.id, u.nombre_completo
            FROM natillera_usuarios u
            WHERE u.estado = 1
              AND NOT EXISTS (SELECT 1 FROM natillera_recordatorios_enviados re
                              WHERE re.reunion_id = :r1 AND re.socio_id = u.id AND re.franja = :fr)
        ";
        if ($soloPendientes) {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM natillera_ahorros_cuotas ac
                                      WHERE ac.reunion_id = :r2 AND ac.socio_id = u.id AND ac.cuota_pagada = 1)";
        }
        $sql .= " ORDER BY u.id";
        $stmt = $this->db->prepare($sql);
        $params = [':r1' => $reunionId, ':fr' => $franja];
        if ($soloPendientes) {
            $params[':r2'] = $reunionId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Marca el recordatorio como enviado. Devuelve false si ya existia (otro proceso lo envio). */
    public function marcarEnviado(int $reunionId, int $socioId, string $franja, ?int $notificacionId): bool {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO natillera_recordatorios_enviados (reunion_id, socio_id, franja, notificacion_id)
            VALUES (:r, :s, :f, :n)
        ");
        $stmt->execute([':r' => $reunionId, ':s' => $socioId, ':f' => $franja, ':n' => $notificacionId]);
        return $stmt->rowCount() > 0;
    }

    public function actualizarNotificacion(int $reunionId, int $socioId, string $franja, int $notificacionId): void {
        $stmt = $this->db->prepare("
            UPDATE natillera_recordatorios_enviados SET notificacion_id = :n
            WHERE reunion_id = :r AND socio_id = :s AND franja = :f
        ");
        $stmt->execute([':n' => $notificacionId, ':r' => $reunionId, ':s' => $socioId, ':f' => $franja]);
    }

    /** Usuario que firma las notificaciones automaticas: el primer Presidente. */
    public function remitenteSistema(): int {
        $id = $this->db->query("
            SELECT u.id FROM natillera_usuarios u JOIN natillera_roles r ON r.id = u.rol_id
            WHERE r.nombre = 'Presidente' ORDER BY u.id LIMIT 1
        ")->fetchColumn();
        return $id ? (int)$id : 0;
    }

    public function tablaExiste(): bool {
        return $this->db->query("SHOW TABLES LIKE 'natillera_recordatorios_enviados'")->rowCount() > 0;
    }
}
