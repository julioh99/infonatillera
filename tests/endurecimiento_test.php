<?php
// tests/endurecimiento_test.php
// Pruebas del endurecimiento de controladores financieros (Money, Auditoria, tasa, usuario de sesion, migraciones).
// SOLO contra una BD local de pruebas:
//   DB_HOST=127.0.0.1 DB_DATABASE=nt_endurecimiento DB_USERNAME=root DB_PASSWORD= php tests/endurecimiento_test.php
// Se niega a correr si la BD no es local (nt_* o natillera_test en 127.0.0.1/localhost).

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Money.php';
require_once __DIR__ . '/../app/Models/Auditoria.php';

$host = getenv('DB_HOST') ?: '';
$dbn = getenv('DB_DATABASE') ?: '';
if (!in_array($host, ['127.0.0.1', 'localhost'], true) || !preg_match('/^(nt_[a-z_]+|natillera_test)$/', $dbn)) {
    fwrite(STDERR, "Rechazado: define DB_HOST=127.0.0.1 y DB_DATABASE=nt_* (BD local de pruebas). Recibido host='{$host}' db='{$dbn}'\n");
    exit(2);
}

$pass = 0; $fail = 0;
function ok($cond, $msg) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  [ok]   $msg\n"; }
    else { $fail++; echo "  [FALLO] $msg\n"; }
}
function eq($actual, $esperado, $msg) {
    $c = ($actual === $esperado);
    ok($c, $msg . ($c ? '' : '  (esperado ' . var_export($esperado, true) . ', obtenido ' . var_export($actual, true) . ')'));
}
function seccion($t) { echo "\n== $t ==\n"; }

$db = Database::getConnection();
$q = function ($sql, $p = []) use ($db) { $st = $db->prepare($sql); $st->execute($p); return $st; };

// ---------------------------------------------------------------- Money
seccion('Money::parse / format');
eq(Money::parse('1.500.000'), 1500000.0, '1.500.000');
eq(Money::parse('1.500.000,50'), 1500000.5, '1.500.000,50');
eq(Money::parse('1500000'), 1500000.0, '1500000');
eq(Money::parse('1500000,5'), 1500000.5, 'coma decimal');
eq(Money::parse('150000.5'), 150000.5, '150000.5 NO es 1.500.005');
eq(Money::parse('$ 1.500.000 COP'), 1500000.0, 'simbolo $ y COP');
eq(Money::parse('1.500'), 1500.0, '1.500 = mil quinientos');
eq(Money::parse('0'), 0.0, 'cero');
eq(Money::parse(''), null, 'vacio => null');
eq(Money::parse('', 0.0), 0.0, 'vacio con valor por defecto');
eq(Money::parse(null, 0.0), 0.0, 'null con valor por defecto');
eq(Money::parse('-5'), null, 'negativo');
eq(Money::parse('-1.000'), null, 'negativo con miles');
eq(Money::parse('abc'), null, 'texto');
eq(Money::parse('1e5'), null, 'notacion cientifica');
eq(Money::parse('1.5.0'), null, 'puntos mal puestos');
eq(Money::parse('1,2,3'), null, 'comas multiples');
eq(Money::parse('12 34x'), null, 'ruido');
eq(Money::parse('1.500.000,567'), null, 'tres decimales');
eq(Money::parse('9999999999999999'), null, 'demasiado grande');
eq(Money::parse(array('1')), null, 'arreglo');
eq(Money::parse(-3), null, 'int negativo');
eq(Money::parse(2500), 2500.0, 'int');
eq(Money::format(1500000), '1.500.000', 'format');
eq(Money::format(1500000.5, 2), '1.500.000,50', 'format decimales');
list($m, $e) = Money::validarPositivo('0'); ok($m === null && $e !== null, 'validarPositivo rechaza 0');
list($m, $e) = Money::validarPositivo('999.999.999.999'); ok($m === null && $e !== null, 'validarPositivo rechaza sobre el tope');
list($m, $e) = Money::validarPositivo('150.000'); ok($m === 150000.0 && $e === null, 'validarPositivo acepta 150.000');
eq(Money::parsePorcentaje('10,5', 0, 20), 10.5, 'porcentaje con coma');
eq(Money::parsePorcentaje('25', 0, 20), null, 'porcentaje fuera de rango');
eq(Money::parsePorcentaje('x', 0, 20), null, 'porcentaje invalido');
eq(Money::config('prestamo.tasa_mensual_defecto'), 10.0, 'config prestamo.tasa_mensual_defecto');
eq(Money::config('controladores.monto_maximo') > 0, true, 'config controladores.monto_maximo');

// ---------------------------------------------------------------- preparacion
seccion('Preparacion de datos de prueba (BD ' . $dbn . ')');
$base = (int)$q("SELECT IFNULL(MAX(id),0) FROM natillera_auditoria")->fetchColumn();
$socio = (int)$q("SELECT id FROM natillera_usuarios WHERE rol_id = 5 AND estado = 1 ORDER BY id LIMIT 1")->fetchColumn();
$socio2 = (int)$q("SELECT id FROM natillera_usuarios WHERE rol_id = 5 AND estado = 1 ORDER BY id LIMIT 1 OFFSET 1")->fetchColumn();
$presId = (int)$q("SELECT id FROM natillera_usuarios WHERE rol_id = 1 AND estado = 1 LIMIT 1")->fetchColumn();
$tesId = (int)$q("SELECT id FROM natillera_usuarios WHERE rol_id = 2 AND estado = 1 LIMIT 1")->fetchColumn();
$actId = (int)$q("SELECT id FROM natillera_usuarios WHERE rol_id = 3 AND estado = 1 LIMIT 1")->fetchColumn();
ok($socio > 0 && $socio2 > 0 && $presId > 0 && $tesId > 0 && $actId > 0, "usuarios de prueba hallados (pres=$presId tes=$tesId act=$actId socio=$socio)");

$q("INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, estado) VALUES (999, CURDATE(), 'PROGRAMADA')");
$reunion = (int)$db->lastInsertId();
$ghost = 99999999; // usuario de sesion que no existe

function sesion($id, $rol) { return ['id' => $id, 'rol_nombre' => $rol, 'password_changed' => 1, 'nombre_completo' => 'Prueba']; }
function ctrl($clase, $metodo, array $post, array $session) {
    $tmp = tempnam(sys_get_temp_dir(), 'ntr');
    file_put_contents($tmp, json_encode(['post' => $post, 'session' => ['usuario' => $session, 'active_mode' => $session['rol_nombre']]]));
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/_runner_controlador.php') . ' ' . $clase . ' ' . $metodo . ' ' . escapeshellarg($tmp) . ' 2>&1';
    $out = shell_exec($cmd);
    @unlink($tmp);
    if (preg_match('/@@RESULT@@(.*)$/s', (string)$out, $m)) {
        $r = json_decode(trim($m[1]), true);
        $r['raw'] = $out;
        return $r;
    }
    return ['error' => 'SIN RESULTADO', 'success' => null, 'raw' => $out];
}
$contar = function ($sql, $p = []) use ($q) { return (int)$q($sql, $p)->fetchColumn(); };

// ---------------------------------------------------------------- Prestamos: monto, tasa, usuario
seccion('PrestamoController::guardar (montos, tasa 3.6, usuario de sesion 2.11)');
$P = ['socio_deudor_id' => $socio, 'reunion_id' => $reunion, 'nombre_referencia' => 'TEST_END_a', 'monto_prestado' => '150.000', 'tasa_interes_mensual' => '15'];

$antes = $contar("SELECT COUNT(*) FROM natillera_prestamos");
$r = ctrl('PrestamoController', 'guardar', $P, sesion($ghost, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_prestamos"), $antes, 'usuario de sesion inexistente: no se crea prestamo');
ok($r['error'] !== null && strpos($r['error'], 'No se registró nada') !== false, 'usuario inexistente: mensaje claro');

$r = ctrl('PrestamoController', 'guardar', $P, sesion($tesId, 'Tesorera'));
$p1 = $q("SELECT * FROM natillera_prestamos WHERE nombre_referencia = 'TEST_END_a' ORDER BY id DESC LIMIT 1")->fetch();
ok($p1 && $r['success'] !== null, 'Tesorera crea prestamo (' . ($r['error'] ?: $r['success']) . ')');
eq((float)$p1['tasa_interes_mensual'], 10.0, 'Tesorera: la tasa enviada (15) se ignora y queda la de config (10)');
eq((float)$p1['monto_prestado'], 150000.0, 'monto 150.000 => 150000');
$ent = $q("SELECT * FROM natillera_entregas_beneficios WHERE prestamo_id = :p", [':p' => $p1['id']])->fetch();
eq((int)$ent['entregado_por_usuario_id'], $tesId, 'egreso a nombre del usuario real de la sesion');

$P2 = $P; $P2['nombre_referencia'] = 'TEST_END_b'; $P2['monto_prestado'] = '150000.5';
$r = ctrl('PrestamoController', 'guardar', $P2, sesion($presId, 'Presidente'));
$p2 = $q("SELECT * FROM natillera_prestamos WHERE nombre_referencia = 'TEST_END_b'")->fetch();
ok($p2 !== false, 'Presidente crea prestamo con 150000.5');
eq((float)$p2['monto_prestado'], 150000.5, '150000.5 se guarda como 150000.50 (no 1.500.005)');
eq((float)$p2['tasa_interes_mensual'], 15.0, 'Presidente SI puede fijar tasa 15');

foreach (['25' => 'tasa 25 (fuera de rango)', '-1' => 'tasa negativa', 'abc' => 'tasa texto'] as $t => $desc) {
    $PX = $P; $PX['nombre_referencia'] = 'TEST_END_t'; $PX['tasa_interes_mensual'] = (string)$t;
    $r = ctrl('PrestamoController', 'guardar', $PX, sesion($presId, 'Presidente'));
    eq($contar("SELECT COUNT(*) FROM natillera_prestamos WHERE nombre_referencia = 'TEST_END_t'"), 0, "Presidente: $desc rechazada");
}
foreach (['-5', 'abc', '0', '1e5', '999.999.999.999', ''] as $mm) {
    $PX = $P; $PX['nombre_referencia'] = 'TEST_END_m'; $PX['monto_prestado'] = $mm;
    $r = ctrl('PrestamoController', 'guardar', $PX, sesion($presId, 'Presidente'));
    eq($contar("SELECT COUNT(*) FROM natillera_prestamos WHERE nombre_referencia = 'TEST_END_m'"), 0, "monto '$mm' rechazado");
}

seccion('PrestamoController::actualizar (tasa solo Presidente)');
$U = ['prestamo_id' => $p2['id'], 'socio_deudor_id' => $socio, 'reunion_id' => $reunion, 'monto_prestado' => '200.000', 'tasa_interes_mensual' => '3', 'nombre_referencia' => 'TEST_END_b', 'fecha_prestamo' => date('Y-m-d'), 'estado' => 'ACTIVO'];
$r = ctrl('PrestamoController', 'actualizar', $U, sesion($presId, 'Presidente'));
$f = $q("SELECT * FROM natillera_prestamos WHERE id = :i", [':i' => $p2['id']])->fetch();
eq((float)$f['tasa_interes_mensual'], 3.0, 'Presidente cambia la tasa a 3');
eq((float)$f['monto_prestado'], 200000.0, 'monto actualizado');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'ACTUALIZAR_PRESTAMO' AND entidad_id = :i ORDER BY id DESC LIMIT 1", [':i' => $p2['id']])->fetch();
ok($au && json_decode($au['datos_antes'], true)['fila']['monto_prestado'] == '150000.50' && $au['datos_despues'] !== null, 'auditoria con datos_antes y datos_despues del prestamo');

$U['tasa_interes_mensual'] = '18'; $U['monto_prestado'] = '210.000';
$r = ctrl('PrestamoController', 'actualizar', $U, sesion($actId, 'Secretaria General'));
$f = $q("SELECT * FROM natillera_prestamos WHERE id = :i", [':i' => $p2['id']])->fetch();
eq((float)$f['tasa_interes_mensual'], 3.0, 'Secretaria General no cambia la tasa (se conserva 3)');

// ---------------------------------------------------------------- Abonos
seccion('Abonos: usuario de sesion, montos y auditoria');
$A = ['prestamo_id' => $p1['id'], 'reunion_id' => $reunion, 'monto_capital_pagado' => '10.000', 'monto_interes_pagado' => '1.500'];
$r = ctrl('PrestamoController', 'abono', $A, sesion($ghost, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :p", [':p' => $p1['id']]), 0, 'abono con usuario inexistente: no se registra');
$r = ctrl('PrestamoController', 'abono', $A, sesion($tesId, 'Tesorera'));
$ab = $q("SELECT * FROM natillera_abonos_prestamos WHERE prestamo_id = :p", [':p' => $p1['id']])->fetch();
ok($ab !== false, 'abono valido registrado (' . ($r['error'] ?: $r['success']) . ')');
eq((float)$ab['monto_capital_pagado'], 10000.0, 'capital 10.000');
eq((float)$ab['monto_interes_pagado'], 1500.0, 'interes 1.500');
eq((int)$ab['registrado_por_usuario_id'], $tesId, 'abono a nombre del usuario real');
$A2 = $A; $A2['monto_capital_pagado'] = '-100';
$r = ctrl('PrestamoController', 'abono', $A2, sesion($tesId, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :p", [':p' => $p1['id']]), 1, 'abono negativo rechazado');

$r = ctrl('PrestamoController', 'actualizarAbono', ['abono_id' => $ab['id'], 'monto_capital_pagado' => '20.000', 'monto_interes_pagado' => '1.500', 'fecha_abono' => date('Y-m-d')], sesion($presId, 'Presidente'));
$ab2 = $q("SELECT * FROM natillera_abonos_prestamos WHERE id = :i", [':i' => $ab['id']])->fetch();
eq((float)$ab2['monto_capital_pagado'], 20000.0, 'abono actualizado');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'ACTUALIZAR_ABONO' AND entidad_id = :i", [':i' => $ab['id']])->fetch();
ok($au && (float)json_decode($au['datos_antes'], true)['fila']['monto_capital_pagado'] === 10000.0, 'auditoria ACTUALIZAR_ABONO guarda el valor anterior (10.000)');

// restaurar la actualizacion de abono
$audit = new Auditoria();
$audit->restaurar((int)$au['id']);
$ab3 = $q("SELECT * FROM natillera_abonos_prestamos WHERE id = :i", [':i' => $ab['id']])->fetch();
eq((float)$ab3['monto_capital_pagado'], 10000.0, 'restaurar entrada ACTUALIZAR_ABONO devuelve 10.000');

$r = ctrl('PrestamoController', 'eliminarAbono', ['abono_id' => $ab['id']], sesion($presId, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE id = :i", [':i' => $ab['id']]), 0, 'abono eliminado');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'ELIMINAR_ABONO' AND entidad_id = :i", [':i' => $ab['id']])->fetch();
ok($au !== false, 'auditoria ELIMINAR_ABONO creada');
$audit->restaurar((int)$au['id']);
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE id = :i", [':i' => $ab['id']]), 1, 'abono restaurado desde la auditoria');

// ---------------------------------------------------------------- Eliminar prestamo + restaurar via CLI
seccion('Eliminar prestamo: instantanea y restauracion (2.9)');
$antesPrest = $q("SELECT * FROM natillera_prestamos WHERE id = :i", [':i' => $p1['id']])->fetch();
$r = ctrl('PrestamoController', 'eliminar', ['prestamo_id' => $p1['id']], sesion($ghost, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_prestamos WHERE id = :i", [':i' => $p1['id']]), 1, 'eliminar con usuario inexistente: no borra');
$r = ctrl('PrestamoController', 'eliminar', ['prestamo_id' => $p1['id']], sesion($presId, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_prestamos WHERE id = :i", [':i' => $p1['id']]), 0, 'prestamo eliminado (' . ($r['error'] ?: $r['success']) . ')');
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :i", [':i' => $p1['id']]), 0, 'sus abonos tambien');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'ELIMINAR_PRESTAMO' AND entidad_id = :i", [':i' => $p1['id']])->fetch();
ok($au !== false, 'entrada ELIMINAR_PRESTAMO existe');
$snap = json_decode($au['datos_antes'], true);
eq(count($snap['dependientes']['natillera_abonos_prestamos']['filas']), 1, 'instantanea incluye 1 abono');
eq(count($snap['dependientes']['natillera_entregas_beneficios']['filas']), 1, 'instantanea incluye 1 entrega');
eq((int)$au['usuario_id'], $presId, 'auditoria guarda el usuario');

$script = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../scripts/restaurar_auditoria.php') . ' ' . (int)$au['id'] . ' 2>&1';
$salida = shell_exec($script);
ok(strpos((string)$salida, 'Restaurado natillera_prestamos') !== false, 'scripts/restaurar_auditoria.php: ' . trim((string)$salida));
$despues = $q("SELECT * FROM natillera_prestamos WHERE id = :i", [':i' => $p1['id']])->fetch();
eq($despues, $antesPrest, 'prestamo restaurado idéntico al original');
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :i", [':i' => $p1['id']]), 1, 'abono restaurado');
eq($contar("SELECT COUNT(*) FROM natillera_entregas_beneficios WHERE prestamo_id = :i", [':i' => $p1['id']]), 1, 'entrega restaurada');
// restaurar dos veces no duplica
$audit->restaurar((int)$au['id']);
eq($contar("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :i", [':i' => $p1['id']]), 1, 'restaurar de nuevo es idempotente');
$salida = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../scripts/restaurar_auditoria.php') . ' 999999999 2>&1');
ok(strpos((string)$salida, 'No se pudo restaurar') !== false, 'restaurar entrada inexistente falla con mensaje');

// ---------------------------------------------------------------- Tope
seccion('actualizarTope');
$r = ctrl('PrestamoController', 'actualizarTope', ['socio_id' => $socio, 'nuevo_tope' => '-1'], sesion($presId, 'Presidente'));
ok($r['error'] !== null, 'tope negativo rechazado');

// ---------------------------------------------------------------- Inyecciones
seccion('InyeccionController');
$I = ['socio_id' => $socio, 'reunion_id' => $reunion, 'monto_inyectado' => '1.000.000', 'tasa_rendimiento_porcentaje' => '5', 'observaciones' => 'TEST_END'];
$antes = $contar("SELECT COUNT(*) FROM natillera_inyecciones_capital");
ctrl('InyeccionController', 'crear', $I, sesion($ghost, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_inyecciones_capital"), $antes, 'usuario inexistente: no se crea inyeccion');
foreach (['-5', 'abc', '0'] as $mm) { $IX = $I; $IX['monto_inyectado'] = $mm; ctrl('InyeccionController', 'crear', $IX, sesion($tesId, 'Tesorera')); }
$IX = $I; $IX['tasa_rendimiento_porcentaje'] = '80'; ctrl('InyeccionController', 'crear', $IX, sesion($tesId, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_inyecciones_capital"), $antes, 'montos invalidos y tasa 80 rechazados');
$r = ctrl('InyeccionController', 'crear', $I, sesion($tesId, 'Tesorera'));
$iny = $q("SELECT * FROM natillera_inyecciones_capital WHERE observaciones = 'TEST_END' ORDER BY id DESC LIMIT 1")->fetch();
ok($iny !== false, 'inyeccion valida creada (' . ($r['error'] ?: $r['success']) . ')');
eq((float)$iny['monto_inyectado'], 1000000.0, 'monto 1.000.000');
eq((int)$iny['registrado_por_usuario_id'], $tesId, 'registrada por el usuario real');
ok($contar("SELECT COUNT(*) FROM natillera_auditoria WHERE accion = 'CREAR_INYECCION'") >= 1, 'auditoria CREAR_INYECCION');
$q("UPDATE natillera_inyecciones_capital SET fecha_retiro_permitido = '2000-01-01' WHERE id = :i", [':i' => $iny['id']]);
ctrl('InyeccionController', 'retirar', ['inyeccion_id' => $iny['id'], 'reunion_id_retiro' => $reunion], sesion($ghost, 'Tesorera'));
eq($q("SELECT estado FROM natillera_inyecciones_capital WHERE id = :i", [':i' => $iny['id']])->fetchColumn(), 'ACTIVA', 'retirar con usuario inexistente: no cambia estado');
ctrl('InyeccionController', 'retirar', ['inyeccion_id' => $iny['id'], 'reunion_id_retiro' => $reunion], sesion($tesId, 'Tesorera'));
eq($q("SELECT estado FROM natillera_inyecciones_capital WHERE id = :i", [':i' => $iny['id']])->fetchColumn(), 'RETIRADA', 'retiro valido');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'RETIRAR_INYECCION' AND entidad_id = :i", [':i' => $iny['id']])->fetch();
ok($au !== false, 'auditoria RETIRAR_INYECCION');
$audit->restaurar((int)$au['id']);
eq($q("SELECT estado FROM natillera_inyecciones_capital WHERE id = :i", [':i' => $iny['id']])->fetchColumn(), 'ACTIVA', 'restaurar devuelve la inyeccion a ACTIVA');

// ---------------------------------------------------------------- Transferencias
seccion('TransferenciaCajaController');
$T = ['reunion_id' => $reunion, 'tipo_movimiento' => 'DEVOLUCION_A_CAJA_MAYOR', 'monto' => '250.000', 'concepto' => 'TEST_END'];
$antes = $contar("SELECT COUNT(*) FROM natillera_transferencias_cajas");
ctrl('TransferenciaCajaController', 'crear', $T, sesion($ghost, 'Tesorera'));
eq($contar("SELECT COUNT(*) FROM natillera_transferencias_cajas"), $antes, 'usuario inexistente: no se crea transferencia');
foreach (['-1', 'xx', '0'] as $mm) { $TX = $T; $TX['monto'] = $mm; ctrl('TransferenciaCajaController', 'crear', $TX, sesion($tesId, 'Tesorera')); }
eq($contar("SELECT COUNT(*) FROM natillera_transferencias_cajas"), $antes, 'montos invalidos rechazados');
$r = ctrl('TransferenciaCajaController', 'crear', $T, sesion($tesId, 'Tesorera'));
$tr = $q("SELECT * FROM natillera_transferencias_cajas WHERE concepto = 'TEST_END' ORDER BY id DESC LIMIT 1")->fetch();
ok($tr !== false, 'transferencia valida creada (' . ($r['error'] ?: $r['success']) . ')');
eq((float)$tr['monto'], 250000.0, 'monto 250.000');
eq((int)$tr['registrado_por_usuario_id'], $tesId, 'registrada por el usuario real');

// ---------------------------------------------------------------- Actividades
seccion('ActividadController');
$G = ['nombre_actividad' => 'TEST_END_act', 'fecha_actividad' => date('Y-m-d'), 'ingresos_totales' => '0', 'gastos_totales' => '0', 'cuota_por_socio' => '20.000', 'participantes' => [$socio, $socio2]];
$antes = $contar("SELECT COUNT(*) FROM natillera_actividades");
ctrl('ActividadController', 'guardar', $G, sesion($ghost, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_actividades"), $antes, 'usuario inexistente: no se crea actividad');
$GX = $G; $GX['cuota_por_socio'] = '-20'; ctrl('ActividadController', 'guardar', $GX, sesion($presId, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_actividades"), $antes, 'cuota negativa rechazada');
$r = ctrl('ActividadController', 'guardar', $G, sesion($presId, 'Presidente'));
$act = $q("SELECT * FROM natillera_actividades WHERE nombre_actividad = 'TEST_END_act'")->fetch();
ok($act !== false, 'actividad creada (' . ($r['error'] ?: $r['success']) . ')');
$part = $q("SELECT * FROM natillera_actividad_participantes WHERE actividad_id = :a ORDER BY id LIMIT 1", [':a' => $act['id']])->fetch();
eq((float)$part['cuota_asignada'], 20000.0, 'cuota 20.000 por participante');

$AB = ['participante_id' => $part['id'], 'monto_abono' => '5.000', 'observacion' => 'TEST_END'];
ctrl('ActividadController', 'registrarAbono', $AB, sesion($ghost, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_actividades WHERE actividad_participante_id = :p", [':p' => $part['id']]), 0, 'abono de actividad con usuario inexistente: no se registra');
$ABX = $AB; $ABX['monto_abono'] = '-5'; ctrl('ActividadController', 'registrarAbono', $ABX, sesion($presId, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_actividades WHERE actividad_participante_id = :p", [':p' => $part['id']]), 0, 'abono negativo rechazado');
ctrl('ActividadController', 'registrarAbono', $AB, sesion($presId, 'Presidente'));
$aba = $q("SELECT * FROM natillera_abonos_actividades WHERE actividad_participante_id = :p", [':p' => $part['id']])->fetch();
ok($aba !== false && (float)$aba['monto_abono'] === 5000.0, 'abono de actividad 5.000 registrado');
eq((int)$aba['registrado_por_usuario_id'], $presId, 'a nombre del usuario real');
ctrl('ActividadController', 'eliminarAbono', ['abono_id' => $aba['id']], sesion($presId, 'Presidente'));
eq($contar("SELECT COUNT(*) FROM natillera_abonos_actividades WHERE id = :i", [':i' => $aba['id']]), 0, 'abono de actividad eliminado');
$au = $q("SELECT * FROM natillera_auditoria WHERE accion = 'ELIMINAR_ABONO_ACTIVIDAD' AND entidad_id = :i", [':i' => $aba['id']])->fetch();
ok($au !== false, 'auditoria ELIMINAR_ABONO_ACTIVIDAD');
$audit->restaurar((int)$au['id']);
eq($contar("SELECT COUNT(*) FROM natillera_abonos_actividades WHERE id = :i", [':i' => $aba['id']]), 1, 'abono de actividad restaurado');

// ---------------------------------------------------------------- Migraciones
seccion('Migraciones (auditoria + indice unico 2.10)');
$mig = require __DIR__ . '/../scripts/migrations/20261125_1010_unico_ahorros_cuotas.php';
$idx = function () use ($q) { return (int)$q("SELECT COUNT(DISTINCT index_name) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'natillera_ahorros_cuotas' AND index_name = 'uq_ahorros_reunion_socio'")->fetchColumn(); };
$reporte = __DIR__ . '/../scripts/reporte_duplicados_cuotas.txt';
@unlink($reporte);
$db->exec("ALTER TABLE natillera_ahorros_cuotas DROP INDEX uq_ahorros_reunion_socio");
eq($idx(), 0, 'indice eliminado para la prueba');
$q("INSERT INTO natillera_ahorros_cuotas (reunion_id, socio_id, monto_cuota) VALUES (:r, :s, 55000), (:r, :s, 55000)", [':r' => $reunion, ':s' => $socio]);
$msg = null;
try { $mig['up']($db); } catch (Throwable $e) { $msg = $e->getMessage(); }
ok($msg !== null && strpos($msg, 'duplicados') !== false, 'con duplicados la migracion falla y NO crea el indice');
eq($idx(), 0, 'indice sigue sin existir');
ok(is_file($reporte) && strpos(file_get_contents($reporte), "{$reunion}\t{$socio}\t2") !== false, 'reporte de duplicados escrito con reunion/socio/veces');
$q("DELETE FROM natillera_ahorros_cuotas WHERE reunion_id = :r", [':r' => $reunion]);
$mig['up']($db);
eq($idx(), 1, 'sin duplicados la migracion crea el indice');
ok(!is_file($reporte), 'el reporte viejo se elimina');
$mig['up']($db);
eq($idx(), 1, 'la migracion es idempotente');
$ins = null;
try { $q("INSERT INTO natillera_ahorros_cuotas (reunion_id, socio_id, monto_cuota) VALUES (:r, :s, 1), (:r, :s, 1)", [':r' => $reunion, ':s' => $socio]); } catch (Throwable $e) { $ins = $e->getMessage(); }
ok($ins !== null, 'con el indice un duplicado es rechazado por la BD');
$q("DELETE FROM natillera_ahorros_cuotas WHERE reunion_id = :r", [':r' => $reunion]);

// ---------------------------------------------------------------- Limpieza
seccion('Limpieza');
$ids = array_column($q("SELECT id FROM natillera_prestamos WHERE nombre_referencia LIKE 'TEST_END%'")->fetchAll(), 'id');
foreach ($ids as $pid) {
    $q("DELETE FROM natillera_abonos_prestamos WHERE prestamo_id = :p", [':p' => $pid]);
    $q("DELETE FROM natillera_entregas_beneficios WHERE prestamo_id = :p", [':p' => $pid]);
    $q("DELETE FROM natillera_prestamos WHERE id = :p", [':p' => $pid]);
}
$q("DELETE FROM natillera_inyecciones_capital WHERE observaciones = 'TEST_END'");
$q("DELETE FROM natillera_transferencias_cajas WHERE concepto = 'TEST_END'");
if (!empty($act['id'])) {
    $q("DELETE FROM natillera_abonos_actividades WHERE actividad_participante_id IN (SELECT id FROM natillera_actividad_participantes WHERE actividad_id = :a)", [':a' => $act['id']]);
    $q("DELETE FROM natillera_actividad_participantes WHERE actividad_id = :a", [':a' => $act['id']]);
    $q("DELETE FROM natillera_actividades WHERE id = :a", [':a' => $act['id']]);
}
$q("DELETE FROM natillera_reuniones WHERE id = :r", [':r' => $reunion]);
$q("DELETE FROM natillera_auditoria WHERE id > :b", [':b' => $base]);
echo "  datos de prueba eliminados\n";

echo "\n==== RESULTADO: $pass correctas, $fail con fallo ====\n";
exit($fail > 0 ? 1 : 0);
