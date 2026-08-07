<?php
// scripts/create_transferencias_table.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Creando tabla natillera_transferencias_cajas en BD local...\n";

    $sql = "
    CREATE TABLE IF NOT EXISTS natillera_transferencias_cajas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reunion_id INT NOT NULL,
        actividad_id INT NULL,
        tipo_movimiento VARCHAR(30) NOT NULL,
        monto DECIMAL(10,2) NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        fecha_transferencia DATETIME DEFAULT CURRENT_TIMESTAMP,
        registrado_por_usuario_id INT NOT NULL,
        FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
        FOREIGN KEY (actividad_id) REFERENCES natillera_actividades(id),
        FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($sql);
    echo "¡Tabla creada exitosamente en BD local!\n";

    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "Creando tabla en servidor remoto {$host}...\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $remotePdo->exec($sql);
    echo "¡Tabla creada exitosamente en servidor remoto skylined_pruebas!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
