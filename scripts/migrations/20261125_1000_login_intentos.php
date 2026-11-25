<?php
// Tabla para limitar intentos fallidos de login por cedula + IP (seguridad 3.7).
return [
    'descripcion' => 'Crea natillera_login_intentos (intentos de login por cedula e IP)',
    'up' => function (PDO $db) {
        $existe = $db->query("SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'natillera_login_intentos'")->fetchColumn();
        if ((int)$existe === 0) {
            $db->exec("CREATE TABLE natillera_login_intentos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cedula VARCHAR(30) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                exitoso TINYINT(1) NOT NULL DEFAULT 0,
                fecha_intento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_cedula_ip_fecha (cedula, ip, fecha_intento),
                INDEX idx_login_fecha (fecha_intento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    },
];
