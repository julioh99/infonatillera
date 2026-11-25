<?php
// tests/fondos_test.php - Fondos de ronda/rifa, caja unica (CajaGlobal) y cierre.
// Uso: DB_HOST=127.0.0.1 DB_DATABASE=nt_fondos DB_USERNAME=root DB_PASSWORD= php tests/fondos_test.php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/Models/CajaGlobal.php';
require_once __DIR__ . '/../app/Models/FondoBeneficio.php';
require_once __DIR__ . '/../app/Models/CierreReunion.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/EntregaBeneficio.php';

$db = t_db();
$cupos = FondoBeneficio::cupos();

echo "== Fondo: ejemplos 500 / 700 / 600\n";
$p = FondoBeneficio::liberar(0, 500000, 300000);
t_igual($p['personas'], 1, '500.000 -> 1 persona');
t_igual($p['queda'], 200000, '500.000 -> queda 200.000');
$p = FondoBeneficio::liberar(200000, 500000, 300000);
t_igual($p['personas'], 2, '700.000 -> 2 personas');
t_igual($p['queda'], 100000, '700.000 -> queda 100.000');
$p = FondoBeneficio::liberar(100000, 500000, 300000);
t_igual($p['personas'], 2, '600.000 -> 2 personas');
t_igual($p['queda'], 0, '600.000 -> queda 0');
$p = FondoBeneficio::liberar(0, 500000, 300000, 0);
t_igual($p['personas'], 0, 'plan de 0 personas conserva todo el saldo');
t_igual($p['queda'], 500000, 'plan de 0 personas -> queda 500.000');
$p = FondoBeneficio::liberar(0, 250000, 150000);
t_igual($p['queda'], 100000, 'rifa 250.000 -> 1 premio, queda 100.000');

echo "== Aportes por defecto (reunion de 55.000, 60.000 y 65.000)\n";
$a = FondoBeneficio::aportesPorDefecto(55000);
t_igual($a['ronda'] + $a['rifa'], 15000, 'cuota 55.000: ronda+rifa = 15.000');
$a = FondoBeneficio::aportesPorDefecto(60000);
t_igual($a['rifa'], 10000, 'cuota 60.000: rifa 10.000');
t_igual($a['ronda'], 10000, 'cuota 60.000: ronda 10.000');
$a = FondoBeneficio::aportesPorDefecto(65000);
t_igual($a['ronda'], 20000, 'cuota 65.000: ronda 20.000');
$a = FondoBeneficio::aportesPorDefecto(70000);
t_igual($a['ronda'] + $a['rifa'] + CajaGlobal::ahorroPorCuota(), 70000, 'cuota 70.000: el total cuadra');
$a = FondoBeneficio::aportesPorDefecto(5500000);
t_igual($a['ronda'] + $a['rifa'], 15000, 'cuota absurda (5.500.000) se trata como cuota base');

echo "== Simulador: 26 reuniones, cupos de config\n";
$s = FondoBeneficio::sugerirDistribucion(array_fill(0, 26, array('bloqueada' => false)));
t_igual($s['extras_ronda'], 4, '4 reuniones con extra de ronda');
t_igual($s['extras_rifa'], 4, '4 reuniones con extra de rifa');
t_ok($s['ok'], 'la simulacion cierra bien');
t_igual($s['saldo_final_ronda'], 0, 'ronda llega a $0');
t_igual($s['saldo_final_rifa'], 0, 'rifa llega a $0');
t_igual($s['servidos_ronda'], $cupos, 'ronda sirve a todos los cupos');
t_igual($s['servidos_rifa'], $cupos, 'rifa sirve a todos los cupos');
$coinciden = 0; $pos = array();
foreach ($s['filas'] as $i => $f) {
    if ($f['extra'] !== 'NINGUNO') $pos[] = $i;
}
t_igual(count($pos), 8, 'ninguna reunion tiene dos extras (8 posiciones distintas)');
$minSep = 99;
for ($i = 1; $i < count($pos); $i++) $minSep = min($minSep, $pos[$i] - $pos[$i - 1]);
t_ok($minSep >= 2, "extras separadas entre si (minimo {$minSep} reuniones)");
$s2 = FondoBeneficio::sugerirDistribucion(array_fill(0, 26, array('bloqueada' => false)), 40);
t_ok($s2['ok'] && $s2['servidos_ronda'] === 40, 'con 40 cupos tambien cierra en $0 con 40 servidos');
$s3 = FondoBeneficio::sugerirDistribucion(array_fill(0, 20, array('bloqueada' => false)), 60);
t_ok($s3['extras_ronda'] > 4 && $s3['ok'], 'con menos reuniones hacen falta mas cuotas extra y sigue cerrando en 0');
$bloq = array_fill(0, 26, array('bloqueada' => false));
$bloq[0] = array('bloqueada' => true, 'aporte_ronda' => 10000, 'aporte_rifa' => 5000, 'personas_ronda' => 1, 'personas_rifa' => 1);
$s4 = FondoBeneficio::sugerirDistribucion($bloq);
t_igual($s4['filas'][0]['extra'], 'NINGUNO', 'una reunion bloqueada no recibe extra');
t_ok($s4['ok'], 'con una reunion bloqueada tambien cierra');

echo "== CajaGlobal: ingreso de ronda/rifa, premios y autoprestamo en una reunion nueva\n";
$caja = new CajaGlobal();
$cierre = new CierreReunion();
$antes = $caja->resumen();
$socio = t_crear_socio('Socio fondos A');
$socio2 = t_crear_socio('Socio fondos B');
$rid = t_crear_reunion('LLAMADO_CERRADO');
$ahorro = CajaGlobal::ahorroPorCuota();
$ins = $db->prepare("INSERT INTO natillera_ahorros_cuotas (reunion_id, socio_id, cuota_pagada, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra) VALUES (:r, :s, 1, :m, 10000, 5000, 0)");
$ins->execute(array(':r' => $rid, ':s' => $socio, ':m' => $ahorro));
$ins->execute(array(':r' => $rid, ':s' => $socio2, ':m' => $ahorro));
// socio 2 se hizo autoprestamo por su cuota completa (55.000)
$insE = $db->prepare("INSERT INTO natillera_entregas_beneficios (reunion_id, socio_id, tipo_beneficio, monto_entregado, entregado_por_usuario_id) VALUES (:r, :s, :t, :m, :u)");
$insE->execute(array(':r' => $rid, ':s' => $socio2, ':t' => 'PRESTAMO', ':m' => 55000, ':u' => $socio));
$res = $cierre->calcularResumenReunion($rid);
t_igual($res['ingresos']['rondas_rifas'], 30000, 'el cierre cuenta ronda+rifa como ingreso (2 x 15.000)');
t_igual($res['ingresos']['cuotas_base'], 2 * $ahorro, 'el cierre cuenta el ahorro de las 2 cuotas');
t_igual($res['egresos']['prestamos_otorgados'], 55000, 'el autoprestamo es egreso de 55.000');
t_igual($res['saldo_neto_reunion'], 2 * 55000 - 55000, 'neto = 2 cuotas de 55.000 - autoprestamo 55.000 (el autoprestamo se anula contra su cuota)');
$insE->execute(array(':r' => $rid, ':s' => $socio, ':t' => 'RONDA', ':m' => 300000, ':u' => $socio));
$res = $cierre->calcularResumenReunion($rid);
t_igual($res['egresos']['premios_entregados'], 300000, 'el premio de ronda entregado es egreso');
t_igual($res['saldo_neto_reunion'], 110000 - 55000 - 300000, 'neto incluye el premio entregado');
$despues = $caja->resumen();
t_igual($despues['caja_total'] - $antes['caja_total'], $res['saldo_neto_reunion'], 'la caja global cambia exactamente lo que dice el cierre de la reunion');
t_igual($despues['reservado_ronda'] - $antes['reservado_ronda'], 20000 - 300000, 'reservado ronda = aportes - premios');
t_igual($despues['reservado_rifa'] - $antes['reservado_rifa'], 10000, 'reservado rifa = aportes de rifa');
t_igual($despues['disponible_prestar'], $despues['caja_total'] - $despues['reservado_ronda'] - $despues['reservado_rifa'], 'disponible para prestar = caja - reservados');
t_igual($res['fondos']['reservado_ronda'], $despues['reservado_ronda'], 'reservado de ronda del cierre = el del tablero');

echo "== Caja del tablero vs cadena de cierres\n";
$cc = $caja->cajaPorCierres();
$dif = $caja->diferenciaConCierres();
$deriva = $cierre->reconciliarCierres(false);   // solo lista: cierres cuyo neto guardado ya no coincide
$sumaDeriva = 0.0;
foreach ($deriva as $d) $sumaDeriva += $d['diferencia'];
t_igual($dif, $sumaDeriva, 'caja del tablero - caja de cierres = movimientos posteriores al cierre (' . number_format($sumaDeriva, 0, ',', '.') . ')');
t_ok($cc['reuniones_abiertas'] >= 1, 'la reunion abierta con movimientos entra en la caja de cierres');

echo "== Reunion cerrada no cambia sus fondos; cronograma\n";
$reunionModel = new Reunion();
$cerr = t_crear_reunion('CERRADA', 55000);
$reunionModel->actualizarReunion($cerr, array('fecha_reunion' => date('Y-m-d'), 'valor_cuota_base' => 99000, 'estado' => 'CERRADA',
    'aporte_ronda' => 30000, 'aporte_rifa' => 30000, 'premios_ronda_planificados' => 5, 'premios_rifa_planificados' => 5));
$fila = $reunionModel->getReunionById($cerr);
t_igual($fila['valor_cuota_base'], 55000, 'una reunion CERRADA conserva su cuota');
t_ok($fila['aporte_ronda'] === null, 'una reunion CERRADA conserva sus aportes');
$abierta = t_crear_reunion('PROGRAMADA', 55000);
$reunionModel->actualizarReunion($abierta, array('fecha_reunion' => date('Y-m-d'), 'valor_cuota_base' => 65000, 'estado' => 'PROGRAMADA',
    'aporte_ronda' => 20000, 'aporte_rifa' => 5000, 'premios_ronda_planificados' => 1, 'premios_rifa_planificados' => null));
$cron = $db->prepare("SELECT * FROM natillera_fondo_beneficios_cronograma WHERE reunion_id = :r AND tipo_beneficio = 'RONDA'");
$cron->execute(array(':r' => $abierta));
$c = $cron->fetch();
t_ok($c !== false, 'al editar la reunion se regenera su fila del cronograma');
t_igual($c['aporte_por_socio'], 20000, 'cronograma usa el aporte guardado');
t_igual($c['total_recaudado'], 20000 * $cupos, 'recaudo planificado = aporte x cupos de config');

echo "== generarCiclo\n";
$r1 = $reunionModel->generarCiclo('2099-01-04', 4);
foreach ($r1['ids'] as $i) $GLOBALS['__t']['reuniones'][] = $i;
t_igual($r1['creadas'], 4, 'crea 4 reuniones');
$r2 = $reunionModel->generarCiclo('2099-01-04', 4);
t_igual($r2['creadas'], 0, 'no duplica al repetir');
t_igual($r2['omitidas'], 4, 'las omite');
t_igual($r1['fechas'][1], '2099-01-18', 'cada 14 dias');
$nums = $db->query("SELECT numero_quincena FROM natillera_reuniones WHERE id IN (" . implode(',', array_map('intval', $r1['ids'])) . ") ORDER BY numero_quincena")->fetchAll(PDO::FETCH_COLUMN);
t_igual((int)$nums[3] - (int)$nums[0], 3, 'numero_quincena consecutivo');
$r3 = $reunionModel->generarCiclo('2099-06-07', null, array('sugerir_extras' => true));
foreach ($r3['ids'] as $i) $GLOBALS['__t']['reuniones'][] = $i;
t_igual($r3['creadas'], (int)CajaGlobal::cfg('reuniones_por_ciclo', 26), 'cantidad por defecto = reuniones_por_ciclo de config');
t_ok($r3['distribucion']['ok'], 'el ciclo generado con extras sugeridas cierra en $0');

echo "== Entregas: validacion de imagenes\n";
require_once __DIR__ . '/../core/Evidencia.php'; // el validador seguro vive ahora en core/Evidencia.php (estatico)
$obj = null;
$m = new ReflectionMethod('Evidencia', 'decodificarImagenBase64');
$m->setAccessible(true);
$png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
$r = $m->invoke($obj, $png, array('image/png'), 1048576, 'firma');
t_igual($r[1], 'png', 'una firma PNG real se acepta');
foreach (array(
    'texto que no es imagen' => 'data:image/png;base64,' . base64_encode('esto no es una imagen'),
    'html disfrazado de png' => 'data:image/png;base64,' . base64_encode('<?php echo 1; ?>'),
    'sin prefijo data' => 'AAAA',
    'png demasiado grande' => $png,
) as $nombre => $dato) {
    $max = ($nombre === 'png demasiado grande') ? 10 : 1048576;
    $lanzo = false;
    try { $m->invoke($obj, $dato, array('image/png'), $max, 'firma'); } catch (RuntimeException $e) { $lanzo = true; }
    t_ok($lanzo, "se rechaza: {$nombre}");
}
t_igual(implode(',', EntregaBeneficio::tiposValidos()), 'RONDA,RIFA,PRESTAMO', 'lista blanca de tipos de entrega');
t_lanza(function () {
    (new EntregaBeneficio())->registrarEntrega(array('tipo_beneficio' => '../x', 'reunion_id' => 1, 'socio_id' => 1, 'monto_entregado' => 1, 'entregado_por_usuario_id' => 1));
}, 'el modelo rechaza un tipo de beneficio fuera de la lista');
