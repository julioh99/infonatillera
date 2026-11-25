<?php
// tests/llamado_autoprestamo_test.php
// Llamado a lista -> notificacion de pago (sin duplicar), autoprestamo y regla de 24 horas,
// y reunion CERRADA no modificable. (Antes: scratch/test_llamado_lista_pagos.php, test_anular_24h_pago.php)
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/PagoNotificacion.php';

$db = t_db();
$reunionId = t_crear_reunion('EN_PROCESO', 55000);
$socioPaga = t_crear_socio('Socio que paga');
$socioAuto = t_crear_socio('Socio con autoprestamo');
$_SESSION['usuario'] = array('id' => $socioPaga, 'nombre_completo' => 'Tesorera de prueba');

$reunion = new Reunion();
$notif = new PagoNotificacion();

// 1. Pago con ahorro extra genera UNA notificacion y no se duplica al guardar de nuevo
$registros = array(array('socio_id' => $socioPaga, 'pagou_cuota' => 1, 'ahorro_extra' => 30000.00, 'generar_autoprestamo' => 0));
t_ok($reunion->guardarLlamadoListaBatch($reunionId, $registros), 'guarda el llamado a lista');
t_igual(count($notif->getPagosPendientesPorSocio($socioPaga)), 1, 'el socio recibe 1 notificacion de pago');
$reunion->guardarLlamadoListaBatch($reunionId, $registros);
t_igual(count($notif->getPagosPendientesPorSocio($socioPaga)), 1, 're-guardar sin cambios no duplica la notificacion');

// 2. Autoprestamo y anulacion dentro de 24 horas
$reunion->guardarLlamadoListaBatch($reunionId, array(
    array('socio_id' => $socioAuto, 'pagou_cuota' => 0, 'ahorro_extra' => 0, 'generar_autoprestamo' => 1),
));
$st = $db->prepare('SELECT id, prestamo_id_asociado, autoprestamo_generado FROM natillera_ahorros_cuotas WHERE reunion_id = :r AND socio_id = :s');
$st->execute(array(':r' => $reunionId, ':s' => $socioAuto));
$ahorro = $st->fetch();
t_ok($ahorro && (int)$ahorro['autoprestamo_generado'] === 1 && !empty($ahorro['prestamo_id_asociado']), 'se genera el autoprestamo asociado a la cuota');
t_igual(count($notif->getPagosPendientesPorSocio($socioAuto)), 0, 'con autoprestamo no hay notificacion de pago todavia');

if ($ahorro) {
    t_ok($reunion->anularAutoprestamo24Horas((int)$ahorro['id']), 'anular dentro de 24 horas funciona: ' . $reunion->ultimoError);
    t_ok(count($notif->getPagosPendientesPorSocio($socioAuto)) >= 1, 'tras anular se notifica el pago al socio');
    $p = $db->prepare('SELECT estado, anulado_sin_interes FROM natillera_prestamos WHERE id = :id');
    $p->execute(array(':id' => $ahorro['prestamo_id_asociado']));
    $pr = $p->fetch();
    t_ok(!$pr || (int)$pr['anulado_sin_interes'] === 1 || $pr['estado'] !== 'ACTIVO', 'el autoprestamo anulado ya no queda activo');
    t_ok(!$reunion->anularAutoprestamo24Horas((int)$ahorro['id']), 'no se puede anular dos veces');
}

// 3. Una reunion CERRADA no admite cambios en el llamado a lista
$db->prepare("UPDATE natillera_reuniones SET estado = 'CERRADA' WHERE id = :id")->execute(array(':id' => $reunionId));
t_lanza(function () use ($reunion, $reunionId, $socioPaga) {
    $reunion->guardarLlamadoListaBatch($reunionId, array(array('socio_id' => $socioPaga, 'pagou_cuota' => 1, 'ahorro_extra' => 0, 'generar_autoprestamo' => 0)));
}, 'reunion CERRADA rechaza el llamado a lista');
