<?php
// scratch/check_reunion_states.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    $db = Database::getConnection();

    $stmt = $db->query("
        SELECT r.id, r.numero_quincena, r.fecha_reunion, r.estado,
               (SELECT COUNT(*) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id) as total_ahorros,
               (SELECT COUNT(*) FROM natillera_cierres_reunion cr WHERE cr.reunion_id = r.id) as tiene_cierre_financiero
        FROM natillera_reuniones r
        ORDER BY r.numero_quincena ASC
    ");

    $reuniones = $stmt->fetchAll();

    echo "ESTADO DE REUNIONES:\n";
    foreach ($reuniones as $r) {
        echo "ID: {$r['id']} | R{$r['numero_quincena']} ({$r['fecha_reunion']}) | Estado BD: {$r['estado']} | Llamado Registros: {$r['total_ahorros']} | Cierre Financiero: {$r['tiene_cierre_financiero']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
