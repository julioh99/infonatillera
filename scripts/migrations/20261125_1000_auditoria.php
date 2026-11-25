<?php
// Crea la tabla de auditoria (2.9). Idempotente.
return [
    'descripcion' => 'Crea natillera_auditoria (bitacora e instantaneas JSON para restaurar borrados)',
    'up' => function (PDO $db) {
        $db->exec("CREATE TABLE IF NOT EXISTS natillera_auditoria (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usuario_id INT NULL,
            rol VARCHAR(50) NULL,
            accion VARCHAR(80) NOT NULL,
            entidad VARCHAR(80) NOT NULL,
            entidad_id INT NULL,
            datos_antes JSON NULL,
            datos_despues JSON NULL,
            ip VARCHAR(45) NULL,
            PRIMARY KEY (id),
            KEY idx_aud_entidad (entidad, entidad_id),
            KEY idx_aud_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    },
];
