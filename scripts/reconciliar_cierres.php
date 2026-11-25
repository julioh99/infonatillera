<?php
// scripts/reconciliar_cierres.php
// Muestra (y con --aplicar corrige) los cierres cuyo neto guardado ya no coincide con los datos de su reunion
// (movimientos registrados despues de cerrar). Por defecto solo LISTA; no cambia nada.
// Uso: DB_HOST=127.0.0.1 DB_DATABASE=nt_fondos DB_USERNAME=root DB_PASSWORD= php scripts/reconciliar_cierres.php [--aplicar]
// En produccion: hacer respaldo antes de usar --aplicar.

require_once __DIR__ . '/../app/Models/CierreReunion.php';

$aplicar = in_array('--aplicar', $argv ?? [], true);
$m = new CierreReunion();
$d = $m->reconciliarCierres($aplicar);
if (!$d) { echo "Los cierres coinciden con los datos.\n"; exit(0); }
$suma = 0;
foreach ($d as $r) {
    printf("R%-3d guardado %14s actual %14s diferencia %12s\n", $r['numero_quincena'], number_format($r['neto_guardado'], 0, ',', '.'),
        number_format($r['neto_actual'], 0, ',', '.'), number_format($r['diferencia'], 0, ',', '.'));
    $suma += $r['diferencia'];
}
echo "Diferencia total: " . number_format($suma, 0, ',', '.') . ($aplicar ? " (APLICADA)\n" : " (solo listado; use --aplicar)\n");
