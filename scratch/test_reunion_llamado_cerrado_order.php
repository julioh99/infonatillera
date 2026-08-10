<?php
// scratch/test_reunion_llamado_cerrado_order.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Reunion.php';

try {
    $reunionModel = new Reunion();
    $reuniones = $reunionModel->getReuniones();
    $actual = $reunionModel->getReunionActual();

    echo "REUNIÓN ACTUAL (getReunionActual):\n";
    if ($actual) {
        echo "  R{$actual['numero_quincena']} (ID: {$actual['id']}) | Estado: {$actual['estado']}\n\n";
    } else {
        echo "  Ninguna.\n\n";
    }

    echo "PRIMERAS 5 REUNIONES EN LISTADOS (getReuniones):\n";
    foreach (array_slice($reuniones, 0, 5) as $idx => $r) {
        $num = $idx + 1;
        echo "  {$num}. R{$r['numero_quincena']} (ID: {$r['id']}) | Estado: {$r['estado']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
