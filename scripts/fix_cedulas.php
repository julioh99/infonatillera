<?php
// scripts/fix_cedulas.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Paso 1: Asignando cédulas temporales para evitar colisiones de UNIQUE KEY...\n";
    $db->exec("UPDATE natillera_usuarios SET cedula = CONCAT('TEMP_', id)");

    echo "Paso 2: Generando formato '1040' + LPAD(id, 2, '0') (Ej: 1 -> 104001, 10 -> 104010)...\n";
    $db->exec("UPDATE natillera_usuarios SET cedula = CONCAT('1040', LPAD(id, 2, '0'))");

    echo "¡Proceso completado exitosamente en la base de datos!\n\n";

    echo "Demostración de las primeras 15 cédulas actualizadas:\n";
    $stmt = $db->query("SELECT id, nombre_completo, cedula FROM natillera_usuarios ORDER BY id ASC LIMIT 15");
    $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($socios as $s) {
        echo sprintf("ID #%-2d | Cédula: %-10s | Nombre: %s\n", $s['id'], $s['cedula'], $s['nombre_completo']);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
