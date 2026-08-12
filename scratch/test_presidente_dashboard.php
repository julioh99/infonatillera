<?php
// scratch/test_presidente_dashboard.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/PresidenteDashboard.php';

try {
    $model = new PresidenteDashboard();

    $resumen = $model->getResumenFinancieroGlobal();
    echo "=== RESUMEN FINANCIERO GLOBAL ===\n";
    echo " - Total Cuotas Ahorradas: $" . number_format($resumen['total_cuotas']) . " COP\n";
    echo " - Intereses Cobrados: $" . number_format($resumen['total_interes_cobrados'] ?? $resumen['total_intereses_cobrados']) . " COP\n";
    echo " - Inyecciones Activas: $" . number_format($resumen['total_inyecciones_activas']) . " COP\n";
    echo " - Cartera Prestada Pendiente: $" . number_format($resumen['cartera_activa_pendiente']) . " COP\n";
    echo " - LIQUIDEZ REAL EN CAJA: $" . number_format($resumen['saldo_real_caja']) . " COP\n";

    $sinPrestamos = $model->getSociosSinPrestamos();
    echo "\nSocios sin préstamos activos: " . count($sinPrestamos) . "\n";

    $sinActividades = $model->getSociosSinActividades();
    echo "Socios sin participación en actividades: " . count($sinActividades) . "\n";

    $conCuotasPend = $model->getSociosConCuotasPendientes();
    echo "Socios con cuotas pendientes: " . count($conCuotasPend) . "\n";

    echo "\n[OK] El Tablero de la Presidencia fue probado con éxito.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
