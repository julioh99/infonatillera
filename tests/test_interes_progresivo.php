<?php
// tests/test_interes_progresivo.php
// Prueba de InteresPrestamo (sin base de datos):  php tests/test_interes_progresivo.php
require_once __DIR__ . '/../app/Models/InteresPrestamo.php';

$fallos = 0;
$total = 0;

function chequear($nombre, $obtenido, $esperado) {
    global $fallos, $total;
    $total++;
    $ok = (is_float($esperado) || is_float($obtenido))
        ? abs((float)$obtenido - (float)$esperado) < 0.005
        : $obtenido === $esperado;
    if ($ok) {
        echo "  OK    {$nombre}\n";
    } else {
        $fallos++;
        echo "  FALLA {$nombre}: esperado " . var_export($esperado, true) . ", obtenido " . var_export($obtenido, true) . "\n";
    }
}

function prestamo($monto = 1000000, $fecha = '2026-01-15', $tasa = 10, $anulado = 0) {
    return array('monto_prestado' => $monto, 'tasa_interes_mensual' => $tasa, 'fecha_inicio' => $fecha, 'anulado_sin_interes' => $anulado);
}
function abono($fecha, $cap, $int) {
    return array('fecha_abono' => $fecha . ' 10:00:00', 'monto_capital_pagado' => $cap, 'monto_interes_pagado' => $int);
}
$INI = InteresPrestamo::MODO_INICIADO;
$COM = InteresPrestamo::MODO_COMPLETO;
$CMP = InteresPrestamo::MODO_COMPUESTO;

echo "Caso 1: el mes que empieza se cobra completo (SALDO_MES_INICIADO)\n";
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-01-15', $INI);
chequear('el mismo dia ya se debe 1 mes', $r['meses_cobrados'], 1);
chequear('interes 10% de 1.000.000', $r['interes_pendiente'], 100000.0);

echo "Caso 2: segundo mes empieza en el aniversario\n";
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-02-14', $INI);
chequear('un dia antes: 1 mes', $r['meses_cobrados'], 1);
chequear('proximo mes', $r['proximo_mes'], '2026-02-15');
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-02-15', $INI);
chequear('en el aniversario: 2 meses', $r['meses_cobrados'], 2);
chequear('interes 200.000', $r['interes_pendiente'], 200000.0);

echo "Caso 3: SALDO_MES_COMPLETO solo cobra meses cumplidos\n";
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-02-14', $COM);
chequear('antes del mes: 0 meses', $r['meses_cobrados'], 0);
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-02-15', $COM);
chequear('al cumplir el mes: 1 mes', $r['meses_cobrados'], 1);
chequear('interes 100.000', $r['interes_pendiente'], 100000.0);

echo "Caso 4: abono a capital reduce el mes siguiente\n";
$r = InteresPrestamo::calcular(prestamo(), array(abono('2026-02-01', 400000, 100000)), '2026-02-20', $INI);
chequear('base del mes 2 = 600.000', $r['meses'][1]['base_capital'], 600000.0);
chequear('causado 100.000 + 60.000', $r['interes_causado'], 160000.0);
chequear('pendiente 60.000 tras pagar 100.000', $r['interes_pendiente'], 60000.0);
chequear('saldo capital 600.000', $r['saldo_capital'], 600000.0);

echo "Caso 5: si se paga todo el capital el interes deja de correr ese dia\n";
$ab = array(abono('2026-03-15', 1000000, 0));
$r = InteresPrestamo::calcular(prestamo(), $ab, '2026-03-15', $INI);
chequear('mes 3 (inicia el dia del pago total) no se cobra', $r['meses_cobrados'], 2);
$r = InteresPrestamo::calcular(prestamo(), $ab, '2026-12-31', $INI);
chequear('meses despues sigue en 2', $r['meses_cobrados'], 2);
chequear('sin proximo mes', $r['proximo_mes'], null);
chequear('interes causado 200.000', $r['interes_causado'], 200000.0);

echo "Caso 6: pago total a mitad de mes no borra el mes ya iniciado\n";
$r = InteresPrestamo::calcular(prestamo(), array(abono('2026-03-01', 1000000, 0)), '2026-06-01', $INI);
chequear('se cobran los 2 meses iniciados (ene15 y feb15)', $r['meses_cobrados'], 2);

echo "Caso 7: prestamo anulado por regla de 24 h => interes 0\n";
$r = InteresPrestamo::calcular(prestamo(1000000, '2026-01-15', 10, 1), array(), '2026-11-25', $INI);
chequear('meses 0', $r['meses_cobrados'], 0);
chequear('pendiente 0', $r['interes_pendiente'], 0.0);
chequear('marcado anulado', $r['anulado'], true);

echo "Caso 8: COMPUESTO cobra interes sobre el interes no pagado\n";
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-03-15', $CMP);
chequear('3 meses', $r['meses_cobrados'], 3);
chequear('100.000 + 110.000 + 121.000', $r['interes_causado'], 331000.0);
$r = InteresPrestamo::calcular(prestamo(), array(abono('2026-02-10', 0, 100000)), '2026-03-15', $CMP);
chequear('si se paga el interes del mes 1, el mes 2 no compone', $r['meses'][1]['base_interes'], 0.0);

echo "Caso 9: fin de mes (31 de enero)\n";
$r = InteresPrestamo::calcular(prestamo(1000000, '2026-01-31'), array(), '2026-02-27', $INI);
chequear('27 feb: 1 mes', $r['meses_cobrados'], 1);
$r = InteresPrestamo::calcular(prestamo(1000000, '2026-01-31'), array(), '2026-02-28', $INI);
chequear('28 feb (ultimo dia): 2 meses', $r['meses_cobrados'], 2);
$r = InteresPrestamo::calcular(prestamo(1000000, '2026-01-31'), array(), '2026-03-31', $INI);
chequear('31 mar: 3 meses', $r['meses_cobrados'], 3);

echo "Caso 10: abono a capital el dia del aniversario ya reduce ese mes\n";
$r = InteresPrestamo::calcular(prestamo(), array(abono('2026-02-15', 500000, 0)), '2026-02-15', $INI);
chequear('base mes 2 = 500.000', $r['meses'][1]['base_capital'], 500000.0);

echo "Caso 11: prestamo futuro y tasa distinta\n";
$r = InteresPrestamo::calcular(prestamo(1000000, '2026-12-01'), array(), '2026-11-25', $INI);
chequear('prestamo futuro: 0 meses', $r['meses_cobrados'], 0);
$r = InteresPrestamo::calcular(prestamo(2000000, '2026-01-15', 5), array(), '2026-01-20', $INI);
chequear('5% de 2.000.000 = 100.000', $r['interes_pendiente'], 100000.0);

echo "Caso 12: interes pagado de mas queda a favor\n";
$r = InteresPrestamo::calcular(prestamo(), array(abono('2026-01-20', 0, 150000)), '2026-01-25', $INI);
chequear('pendiente 0', $r['interes_pendiente'], 0.0);
chequear('a favor 50.000', $r['interes_a_favor'], 50000.0);

echo "Caso 13: detalle mes a mes\n";
$r = InteresPrestamo::calcular(prestamo(), array(), '2026-03-20', $INI);
chequear('mes 1 desde', $r['meses'][0]['desde'], '2026-01-15');
chequear('mes 1 hasta', $r['meses'][0]['hasta'], '2026-02-14');
chequear('mes 3 numero', $r['meses'][2]['numero'], 3);

echo "Caso 14: modo configurado valido\n";
chequear('modo normalizado', InteresPrestamo::normalizarModo('algo raro'), 'SALDO_MES_INICIADO');
chequear('modo de config es uno de los tres', in_array(InteresPrestamo::modoConfigurado(), array($INI, $COM, $CMP), true), true);

echo "\n" . ($total - $fallos) . " de {$total} comprobaciones correctas.\n";
exit($fallos === 0 ? 0 : 1);
