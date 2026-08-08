<?php
// scratch/test_signed_loans.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';

try {
    $prestamoModel = new Prestamo();
    $prestamos = $prestamoModel->getTodosPrestamos();

    echo "Préstamos consultados: " . count($prestamos) . "\n";
    foreach ($prestamos as $p) {
        echo "ID: {$p['id']} - Socio: {$p['deudor_nombre']} - Monto: {$p['monto_prestado']} - Tiene Firma/Foto: " . ($p['tiene_firma_foto'] ?? 0) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
