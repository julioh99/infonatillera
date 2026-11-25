<?php
// tests/prestamo_entrega_test.php - prestamo + constancia de entrega + validacion de abonos.
// (Antes: scratch/test_integracion_prestamo_entrega.php, test_prestamos_cierre.php)
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';
require_once __DIR__ . '/../app/Models/EntregaBeneficio.php';

$db = t_db();
$reunionId = t_crear_reunion('EN_PROCESO');
$socioId = t_crear_socio('Deudor de prueba');
$_SESSION['usuario'] = array('id' => $socioId);

$prestamos = new Prestamo();
$entregas = new EntregaBeneficio();

$pid = $prestamos->crearPrestamo(array(
    'socio_deudor_id' => $socioId, 'reunion_id' => $reunionId, 'nombre_referencia' => 'Prueba automatica',
    'monto_prestado' => 250000.00, 'tasa_interes_mensual' => 10.00, 'tipo_prestamo' => 'DIRECTO', 'es_autoprestamo' => 0,
));
t_ok((int)$pid > 0, 'crea el prestamo');

$ok = $entregas->registrarEntrega(array(
    'reunion_id' => $reunionId, 'socio_id' => $socioId, 'prestamo_id' => $pid, 'tipo_beneficio' => 'PRESTAMO',
    'monto_entregado' => 250000.00, 'firma_digital_path' => '/uploads/firmas/prueba.png', 'foto_evidencia_path' => null,
    'entregado_por_usuario_id' => $socioId,
));
t_ok($ok, 'registra la constancia de entrega');
$st = $db->prepare('SELECT id FROM natillera_entregas_beneficios WHERE prestamo_id = :p');
$st->execute(array(':p' => $pid));
$entregaId = $st->fetchColumn();
t_ok($entregaId !== false, 'la entrega queda ligada al prestamo');

// Abonos: no se acepta capital mayor al saldo ni montos negativos
t_lanza(function () use ($prestamos, $pid, $socioId, $reunionId) {
    $prestamos->registrarAbono($pid, 999999999.0, 0.0, $socioId, $reunionId);
}, 'rechaza un abono de capital mayor al saldo');
t_lanza(function () use ($prestamos, $pid, $socioId, $reunionId) {
    $prestamos->registrarAbono($pid, -1000.0, 0.0, $socioId, $reunionId);
}, 'rechaza un abono negativo');
t_ok($prestamos->registrarAbono($pid, 50000.0, 0.0, $socioId, $reunionId), 'acepta un abono valido de capital');

if ($entregaId !== false) {
    t_ok($entregas->eliminarEntrega((int)$entregaId), 'elimina la constancia de entrega');
}
