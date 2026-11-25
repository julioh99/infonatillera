<?php
// scripts/create_historico_table.php
// Crea natillera_historico y la columna natillera_usuarios.orden. No borra nada. Se puede repetir.
// Uso:  php scripts/create_historico_table.php      (usa la BD del .env o de las variables DB_*)

require_once __DIR__ . '/../app/Models/Historico.php';

try {
    $db = Database::getConnection();
    Historico::crearTablaHistorico($db);
    Historico::aplicarColumnaOrden($db);
    $tabla = $db->query("SHOW TABLES LIKE 'natillera_historico'")->rowCount() > 0;
    $col = $db->query("SHOW COLUMNS FROM natillera_usuarios LIKE 'orden'")->rowCount() > 0;
    echo "natillera_historico: " . ($tabla ? 'OK' : 'NO SE CREO') . "\n";
    echo "natillera_usuarios.orden: " . ($col ? 'OK' : 'NO SE CREO') . "\n";
    exit($tabla && $col ? 0 : 1);
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
