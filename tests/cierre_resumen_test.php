<?php
// tests/cierre_resumen_test.php - el resumen financiero de cada reunion es coherente (solo lectura, mas una reunion de prueba).
// (Antes: scratch/test_cierre_financiero.php, test_inyecciones_cierre.php, test_intercajas.php)
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';

$db = t_db();
$cierre = new CierreReunion();

$ids = $db->query('SELECT id FROM natillera_reuniones ORDER BY numero_quincena LIMIT 30')->fetchAll(PDO::FETCH_COLUMN);
$ids[] = t_crear_reunion('EN_PROCESO');
$malos = 0;
foreach ($ids as $id) {
    $r = $cierre->calcularResumenReunion((int)$id);
    $coherente = isset($r['ingresos']['total'], $r['egresos']['total'], $r['saldo_neto_reunion'])
        && abs(($r['ingresos']['total'] - $r['egresos']['total']) - $r['saldo_neto_reunion']) < 0.01;
    if (!$coherente) {
        $malos++;
        echo "        reunion {$id}: neto distinto de ingresos - egresos\n";
    }
}
t_igual($malos, 0, 'saldo neto = ingresos - egresos en ' . count($ids) . ' reuniones');

$vacia = $cierre->calcularResumenReunion((int)end($ids));
t_igual($vacia['ingresos']['total'], 0, 'una reunion sin movimientos tiene ingresos 0');
t_igual($vacia['egresos']['total'], 0, 'una reunion sin movimientos tiene egresos 0');
