<?php
// scratch/test_inyecciones_cierre.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/InyeccionCapital.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    echo "1. Probando creación de Inyección de Capital...\n";
    $inyModel = new InyeccionCapital();
    $okIny = $inyModel->crearInyeccion([
        'socio_id' => 1,
        'reunion_id' => 1,
        'monto_inyectado' => 1000000.00,
        'tasa_rendimiento_porcentaje' => 5.00,
        'observaciones' => 'Aporte inicial de prueba',
        'registrado_por_usuario_id' => 1
    ]);
    echo $okIny ? "¡Inyección creada con éxito!\n" : "Error al crear inyección.\n";

    echo "\n2. Consultando resumen de inyecciones:\n";
    $resumenIny = $inyModel->getResumenInyecciones();
    print_r($resumenIny);

    echo "\n3. Probando intento prematuro de retiro (debe ser bloqueado por la regla de 6 meses):\n";
    $inyecciones = $inyModel->getInyecciones();
    if (!empty($inyecciones)) {
        $firstId = (int)$inyecciones[0]['id'];
        try {
            $inyModel->retirarInyeccion($firstId, 1);
            echo "ALERTA: Se retiró sin haber pasado 6 meses (Inesperado).\n";
        } catch (Exception $e) {
            echo "Correcto - Regla de 6 meses activa: " . $e->getMessage() . "\n";
        }
    }

    echo "\n4. Probando Arqueo Financiero y Cierre de Reunión #1...\n";
    $cierreModel = new CierreReunion();
    $resumenCierre = $cierreModel->calcularResumenReunion(1);

    echo "INGRESOS TOTALES: $" . number_format($resumenCierre['ingresos']['total'], 0, ',', '.') . "\n";
    echo " - Cuotas base $40k: $" . number_format($resumenCierre['ingresos']['cuotas_base'], 0, ',', '.') . "\n";
    echo " - Inyecciones: $" . number_format($resumenCierre['ingresos']['inyecciones'], 0, ',', '.') . "\n";
    echo "EGRESOS TOTALES: $" . number_format($resumenCierre['egresos']['total'], 0, ',', '.') . "\n";
    echo "FLUJO NETO REUNIÓN: $" . number_format($resumenCierre['saldo_neto_reunion'], 0, ',', '.') . "\n";
    echo "SALDO ACUMULADO CAJA: $" . number_format($resumenCierre['saldo_acumulado_caja'], 0, ',', '.') . "\n";

    $okCierre = $cierreModel->guardarCierre(1, $resumenCierre, 1);
    echo $okCierre ? "¡Cierre de Reunión #1 registrado y guardado exitosamente!\n" : "Error en cierre.\n";

} catch (Exception $e) {
    echo "Excepción: " . $e->getMessage() . "\n";
}
