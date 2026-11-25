<?php
// Registro de envios Web Push por suscripcion y de recordatorios automaticos enviados (sin duplicados).
return [
    'descripcion' => 'Tablas natillera_push_envios y natillera_recordatorios_enviados (4.1 y 4.2)',
    'up' => function (PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_push_envios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                notificacion_id INT NULL,
                suscripcion_id INT NULL,
                socio_id INT NULL,
                http_status SMALLINT NOT NULL DEFAULT 0,
                exitoso TINYINT(1) NOT NULL DEFAULT 0,
                detalle VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_push_envios_notif (notificacion_id),
                INDEX idx_push_envios_fecha (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_recordatorios_enviados (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reunion_id INT NOT NULL,
                socio_id INT NOT NULL,
                franja VARCHAR(5) NOT NULL,
                notificacion_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_recordatorio (reunion_id, socio_id, franja),
                INDEX idx_recordatorio_reunion (reunion_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
];
