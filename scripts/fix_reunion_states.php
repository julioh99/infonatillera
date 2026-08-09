<?php
// scripts/fix_reunion_states.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Actualizando estado de reuniones en BD local...\n";
    $stmtLocal = $db->query("
        UPDATE natillera_reuniones 
        SET estado = 'LLAMADO_CERRADO' 
        WHERE estado = 'CERRADA' 
          AND id NOT IN (SELECT reunion_id FROM natillera_cierres_reunion)
    ");
    echo "¡Reuniones actualizadas en BD local! Filas afectadas: " . $stmtLocal->rowCount() . "\n";

    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "Actualizando estado en servidor remoto {$host}...\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmtRemote = $remotePdo->query("
        UPDATE natillera_reuniones 
        SET estado = 'LLAMADO_CERRADO' 
        WHERE estado = 'CERRADA' 
          AND id NOT IN (SELECT reunion_id FROM natillera_cierres_reunion)
    ");
    echo "¡Reuniones actualizadas en BD remota skylined_pruebas! Filas afectadas: " . $stmtRemote->rowCount() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
