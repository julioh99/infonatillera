<?php
require_once __DIR__ . '/_bootstrap.php';
// scripts/alter_entregas_prestamo_id.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Agregando columna prestamo_id a natillera_entregas_beneficios en BD local...\n";
    try {
        $db->exec("ALTER TABLE natillera_entregas_beneficios ADD COLUMN prestamo_id INT NULL AFTER socio_id");
        $db->exec("ALTER TABLE natillera_entregas_beneficios ADD CONSTRAINT fk_entregas_prestamo FOREIGN KEY (prestamo_id) REFERENCES natillera_prestamos(id) ON DELETE SET NULL");
        echo "¡Columna prestamo_id agregada en BD local!\n";
    } catch (Exception $e1) {
        echo "Nota local: " . $e1->getMessage() . "\n";
    }

    $remote = remote_db_config();
    $host = $remote['host'];
    $dbName = $remote['dbName'];
    $user = $remote['user'];
    $pass = $remote['pass'];

    echo "Agregando columna en servidor remoto {$host}...\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $remotePdo->exec("ALTER TABLE natillera_entregas_beneficios ADD COLUMN prestamo_id INT NULL AFTER socio_id");
        $remotePdo->exec("ALTER TABLE natillera_entregas_beneficios ADD CONSTRAINT fk_entregas_prestamo FOREIGN KEY (prestamo_id) REFERENCES natillera_prestamos(id) ON DELETE SET NULL");
        echo "¡Columna prestamo_id agregada en BD remota skylined_pruebas!\n";
    } catch (Exception $e2) {
        echo "Nota remota: " . $e2->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
