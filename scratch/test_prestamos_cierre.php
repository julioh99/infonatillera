<?php
// scratch/test_prestamos_cierre.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    echo "1. Creando o buscando un préstamo para prueba de abonos...\n";
    $prestamoModel = new Prestamo();
    $prestamos = $prestamoModel->getTodosPrestamos();
    if (empty($prestamos)) {
        $prestamoModel->crearPrestamo([
            'socio_deudor_id' => 1,
            'nombre_referencia' => 'Préstamo de prueba',
            'monto_prestado' => 1000000.00,
            'tasa_interes_mensual' => 10.00
        ]);
        $prestamos = $prestamoModel->getTodosPrestamos();
    }

    $pId = (int)$prestamos[0]['id'];

    echo "2. Registrando Abono #1 con reunion_id = 1 ($100.000 Capital / $20.000 Interés)...\n";
    $ok1 = $prestamoModel->registrarAbono($pId, 100000.00, 20000.00, 1, 1);
    echo $ok1 ? "Abono #1 guardado.\n" : "Error al guardar Abono #1.\n";

    echo "\n3. Registrando Abono #2 sin reunion_id en fecha de quincena ($150.000 Capital / $15.000 Interés)...\n";
    $ok2 = $prestamoModel->registrarAbono($pId, 150000.00, 15000.00, 1, null);
    // Asignar fecha en el rango de quincena
    $db = Database::getConnection();
    $db->exec("UPDATE natillera_abonos_prestamos SET fecha_abono = '2026-02-14 10:00:00' WHERE id = (SELECT MAX(id) FROM (SELECT id FROM natillera_abonos_prestamos) x)");
    echo $ok2 ? "Abono #2 guardado y actualizado con fecha quincenal.\n" : "Error al guardar Abono #2.\n";

    echo "\n4. Verificando cálculo en CierreReunion::calcularResumenReunion(1)...\n";
    $cierreModel = new CierreReunion();
    $resumen = $cierreModel->calcularResumenReunion(1);

    echo "INGRESOS TOTALES: $" . number_format($resumen['ingresos']['total'], 0, ',', '.') . "\n";
    echo " - Abonos Capital Préstamos: $" . number_format($resumen['ingresos']['abono_capital'], 0, ',', '.') . "\n";
    echo " - Intereses Cobrados Préstamos: $" . number_format($resumen['ingresos']['intereses_prestamos'], 0, ',', '.') . "\n";
    echo " - Rondas/Rifas incluidas: " . (array_key_exists('rondas_rifas', $resumen['ingresos']) ? 'SÍ (ERRÓNEO)' : 'NO (CORRECTO - EXCLUIDO)') . "\n";
    echo "EGRESOS TOTALES: $" . number_format($resumen['egresos']['total'], 0, ',', '.') . "\n";
    echo " - Premios Rondas/Rifas incluidos: " . (array_key_exists('premios_entregados', $resumen['egresos']) ? 'SÍ (ERRÓNEO)' : 'NO (CORRECTO - EXCLUIDO)') . "\n";
    echo "FLUJO NETO REUNIÓN #1: $" . number_format($resumen['saldo_neto_reunion'], 0, ',', '.') . "\n";

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}
