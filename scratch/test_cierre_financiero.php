<?php
// scratch/test_cierre_financiero.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

try {
    $cierreModel = new CierreReunion();
    $reunionModel = new Reunion();

    $reuniones = $reunionModel->getReuniones();

    echo "RESUMEN FINANCIERO Y ACUMULADOS DE CAJA POR REUNIÓN:\n";
    foreach ($reuniones as $r) {
        if ($r['numero_quincena'] > 5) break;

        $resumen = $cierreModel->calcularResumenReunion($r['id']);
        $cierreExistente = $cierreModel->getCierrePorReunion($r['id']);

        $isCierreDone = !empty($cierreExistente) ? "CERRADO 🔒" : "PENDIENTE ⚠️";
        $hasLlamado = $resumen['tiene_llamado_lista'] ? "Llamado ✔" : "Sin Llamado ❌";

        echo "\nReunión R{$r['numero_quincena']} ({$r['fecha_reunion']}) | Estado BD: {$r['estado']} | {$hasLlamado} | Cierre Financiero: {$isCierreDone}\n";
        echo "  - Saldo Inicial Caja (Heredado): $" . number_format($resumen['saldo_inicial_caja'], 0, ',', '.') . "\n";
        echo "  - Ingresos Totales: $" . number_format($resumen['ingresos']['total'], 0, ',', '.') . "\n";
        echo "  - Egresos Totales: $" . number_format($resumen['egresos']['total'], 0, ',', '.') . "\n";
        echo "  - Flujo Neto Reunión: $" . number_format($resumen['saldo_neto_reunion'], 0, ',', '.') . "\n";
        echo "  - Saldo Acumulado Final: $" . number_format($resumen['saldo_acumulado_caja'], 0, ',', '.') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
