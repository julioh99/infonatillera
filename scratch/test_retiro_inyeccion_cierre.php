<?php
// scratch/test_retiro_inyeccion_cierre.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/InyeccionCapital.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    $db = Database::getConnection();
    $inyModel = new InyeccionCapital();
    $cierreModel = new CierreReunion();

    echo "1. Creando inyección de prueba de $1.000.000 COP...\n";
    $inyModel->crearInyeccion([
        'socio_id' => 1,
        'reunion_id' => 1,
        'monto_inyectado' => 1000000.00,
        'tasa_rendimiento_porcentaje' => 5.00,
        'observaciones' => 'Prueba retiro asignado a reunión',
        'registrado_por_usuario_id' => 1
    ]);

    $lastId = (int)$db->lastInsertId();
    echo "   Inyección creada con ID: {$lastId}\n";

    // Forzar fecha_retiro_permitido a ayer para simular cumplimiento de 6 meses
    $db->exec("UPDATE natillera_inyecciones_capital SET fecha_retiro_permitido = '2025-01-01' WHERE id = {$lastId}");

    echo "2. Procesando retiro asignando a la reunión ID = 13 (LLAMADO_CERRADO)...\n";
    $okRet = $inyModel->retirarInyeccion($lastId, 1, 13);
    echo "   Resultado retiro: " . ($okRet ? 'EXITOSO' : 'FALLIDO') . "\n";

    echo "3. Consultando CierreFinanciero para reunión R13 (ID = 13)...\n";
    $resumenR13 = $cierreModel->calcularResumenReunion(13);
    $egrInyDev = $resumenR13['egresos']['inyecciones_devueltas'];

    echo "   Monto registrado en Egresos por Inyecciones Devueltas en R13: $" . number_format($egrInyDev, 0, ',', '.') . " COP\n";

    if ($egrInyDev >= 1050000) {
        echo "   [OK] El valor del retiro ($1.050.000 COP) se registró correctamente como egreso de la reunión seleccionada.\n";
    } else {
        echo "   [ERROR] El retiro no se computó en el egreso de la reunión R13.\n";
    }

    // Limpieza
    $db->exec("DELETE FROM natillera_inyecciones_capital WHERE id = {$lastId}");
    echo "4. Limpieza finalizada correctamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
