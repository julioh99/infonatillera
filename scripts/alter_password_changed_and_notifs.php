<?php
// scripts/alter_password_changed_and_notifs.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "=== ACTUALIZANDO BD LOCAL ===\n";
    // 1. Agregar columna password_changed a natillera_usuarios
    try {
        $db->exec("ALTER TABLE natillera_usuarios ADD COLUMN password_changed TINYINT(1) DEFAULT 0 AFTER estado");
        echo " [OK] Columna password_changed agregada en BD local.\n";
    } catch (Exception $e1) {
        echo " [NOTA] Local: " . $e1->getMessage() . "\n";
    }

    // 2. Crear tabla natillera_notificaciones_leidas
    $sqlNotif = "
    CREATE TABLE IF NOT EXISTS natillera_notificaciones_leidas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notificacion_id INT NOT NULL,
        socio_id INT NOT NULL,
        fecha_lectura DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unq_notif_socio (notificacion_id, socio_id),
        FOREIGN KEY (notificacion_id) REFERENCES natillera_notificaciones(id) ON DELETE CASCADE,
        FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($sqlNotif);
    echo " [OK] Tabla natillera_notificaciones_leidas creada en BD local.\n";

    // 3. ACTUALIZAR SERVIDOR REMOTO PRODUCCIÓN
    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "\n=== ACTUALIZANDO SERVIDOR REMOTO ({$host} - {$dbName}) ===\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $remotePdo->exec("ALTER TABLE natillera_usuarios ADD COLUMN password_changed TINYINT(1) DEFAULT 0 AFTER estado");
        echo " [OK] Columna password_changed agregada en BD remota.\n";
    } catch (Exception $e2) {
        echo " [NOTA] Remoto: " . $e2->getMessage() . "\n";
    }

    $remotePdo->exec($sqlNotif);
    echo " [OK] Tabla natillera_notificaciones_leidas creada en BD remota.\n";

    echo "\n¡Migración de BD completada con éxito!\n";

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
