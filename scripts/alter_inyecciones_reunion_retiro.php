<?php
// scripts/alter_inyecciones_reunion_retiro.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Agregando columna reunion_id_retiro a natillera_inyecciones_capital en BD local...\n";
    try {
        $db->exec("ALTER TABLE natillera_inyecciones_capital ADD COLUMN reunion_id_retiro INT NULL AFTER reunion_id");
        $db->exec("ALTER TABLE natillera_inyecciones_capital ADD CONSTRAINT fk_inyecciones_reunion_retiro FOREIGN KEY (reunion_id_retiro) REFERENCES natillera_reuniones(id) ON DELETE SET NULL");
        echo "¡Columna reunion_id_retiro agregada en BD local!\n";
    } catch (Exception $e1) {
        echo "Nota local: " . $e1->getMessage() . "\n";
    }

    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "Agregando columna en servidor remoto {$host}...\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $remotePdo->exec("ALTER TABLE natillera_inyecciones_capital ADD COLUMN reunion_id_retiro INT NULL AFTER reunion_id");
        $remotePdo->exec("ALTER TABLE natillera_inyecciones_capital ADD CONSTRAINT fk_inyecciones_reunion_retiro FOREIGN KEY (reunion_id_retiro) REFERENCES natillera_reuniones(id) ON DELETE SET NULL");
        echo "¡Columna reunion_id_retiro agregada en BD remota skylined_pruebas!\n";
    } catch (Exception $e2) {
        echo "Nota remota: " . $e2->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
