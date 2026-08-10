<?php
// scratch/test_entrega_reunion_link.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';

try {
    $prestamoModel = new Prestamo();
    $prestamos = $prestamoModel->getTodosPrestamos();

    echo "ENLACES DE FIRMA/FOTO GENERADOS PARA PRÉSTAMOS:\n";
    foreach (array_slice($prestamos, 0, 5) as $p) {
        $reunionId = $p['reunion_id'] ?? '';
        $url = "/admin/entregas?tipo=PRESTAMO&socio_id={$p['socio_deudor_id']}&monto={$p['monto_prestado']}&reunion_id={$reunionId}";
        $numR = !empty($p['numero_quincena']) ? "R{$p['numero_quincena']}" : "Sin R";
        echo "Préstamo #{$p['id']} ({$p['deudor_nombre']}) | Reunión: {$numR} (ID: {$reunionId}) => URL: {$url}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
