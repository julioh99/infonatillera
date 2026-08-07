<?php
// scratch/test_intercajas.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/TransferenciaCaja.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    echo "1. Probando registro de préstamo sin interés a la Caja de Actividades ($500.000 COP)...\n";
    $transfModel = new TransferenciaCaja();
    $ok1 = $transfModel->registrarTransferencia([
        'reunion_id' => 1,
        'actividad_id' => null,
        'tipo_movimiento' => 'PRESTAMO_A_ACTIVIDAD',
        'monto' => 500000.00,
        'concepto' => 'Préstamo inicial para compra de insumos tamales',
        'registrado_por_usuario_id' => 1
    ]);
    echo $ok1 ? "¡Préstamo a Actividades registrado!\n" : "Error en registro.\n";

    echo "\n2. Probando reembolso/devolución a la Caja Mayor ($200.000 COP)...\n";
    $ok2 = $transfModel->registrarTransferencia([
        'reunion_id' => 1,
        'actividad_id' => null,
        'tipo_movimiento' => 'DEVOLUCION_A_CAJA_MAYOR',
        'monto' => 200000.00,
        'concepto' => 'Devolución parcial de utilidades de tamales',
        'registrado_por_usuario_id' => 1
    ]);
    echo $ok2 ? "¡Devolución a Caja Mayor registrada!\n" : "Error en registro.\n";

    echo "\n3. Consultando saldo adeudado entre cajas:\n";
    $resumenInter = $transfModel->getResumenIntercajas();
    print_r($resumenInter);

    echo "\n4. Verificando cálculo del Cierre de Caja Mayor de Reunión #1...\n";
    $cierreModel = new CierreReunion();
    $resumenCierre = $cierreModel->calcularResumenReunion(1);

    echo "INGRESOS TOTALES: $" . number_format($resumenCierre['ingresos']['total'], 0, ',', '.') . "\n";
    echo " - Devoluciones Caja Actividades: $" . number_format($resumenCierre['ingresos']['devoluciones_actividades'], 0, ',', '.') . "\n";
    echo "EGRESOS TOTALES: $" . number_format($resumenCierre['egresos']['total'], 0, ',', '.') . "\n";
    echo " - Préstamos a Caja Actividades: $" . number_format($resumenCierre['egresos']['prestamos_actividades'], 0, ',', '.') . "\n";
    echo "FLUJO NETO REUNIÓN: $" . number_format($resumenCierre['saldo_neto_reunion'], 0, ',', '.') . "\n";

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}
