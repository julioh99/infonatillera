<?php
// scratch/test_autoprestamo_reunion.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';
require_once __DIR__ . '/../app/Models/Reunion.php';

try {
    $prestamoModel = new Prestamo();
    $reunionesModel = new Reunion();

    $prestamos = $prestamoModel->getTodosPrestamos();
    echo "Préstamos totales: " . count($prestamos) . "\n";

    foreach ($prestamos as $p) {
        $rNum = !empty($p['numero_quincena']) ? "R{$p['numero_quincena']}" : "Sin R";
        echo "ID: {$p['id']} - Socio: {$p['deudor_nombre']} - Reunión: {$rNum} - Tipo: {$p['tipo_prestamo']} - Autopréstamo: {$p['es_autoprestamo']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
