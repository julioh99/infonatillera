<?php
// tests/esquema_test.php - tablas y columnas que el sistema necesita (antes eran comprobaciones sueltas en scratch/).
require_once __DIR__ . '/_bootstrap.php';
$db = t_db();

$esperado = array(
    'natillera_usuarios' => array('cedula', 'password_hash', 'password_changed', 'rol_id'),
    'natillera_reuniones' => array('numero_quincena', 'fecha_reunion', 'valor_cuota_base', 'estado'),
    'natillera_ahorros_cuotas' => array('reunion_id', 'socio_id', 'cuota_pagada', 'monto_aporte_ronda', 'monto_aporte_rifa', 'prestamo_id_asociado', 'autoprestamo_generado'),
    'natillera_prestamos' => array('socio_deudor_id', 'reunion_id', 'fecha_prestamo', 'nombre_referencia', 'es_autoprestamo', 'anulado_sin_interes'),
    'natillera_abonos_prestamos' => array('prestamo_id', 'monto_capital_pagado', 'monto_interes_pagado', 'reunion_id'),
    'natillera_entregas_beneficios' => array('reunion_id', 'socio_id', 'prestamo_id', 'tipo_beneficio', 'firma_digital_path'),
    'natillera_pagos_notificaciones' => array('socio_id', 'monto_total', 'leido'),
    'natillera_notificaciones_leidas' => array(),
    'natillera_cierres_reunion' => array(),
    'natillera_fondo_beneficios_cronograma' => array(),
    'natillera_migraciones' => array('nombre', 'huella', 'aplicada_en'),
);
foreach ($esperado as $tabla => $columnas) {
    $st = $db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
    $st->execute(array(':t' => $tabla));
    $reales = $st->fetchAll(PDO::FETCH_COLUMN);
    if (!t_ok(count($reales) > 0, "existe la tabla {$tabla}")) continue;
    $faltan = array_diff($columnas, $reales);
    t_ok(!$faltan, "{$tabla} tiene sus columnas" . ($faltan ? ' (faltan: ' . implode(', ', $faltan) . ')' : ''));
}

$roles = $db->query('SELECT nombre FROM natillera_roles')->fetchAll(PDO::FETCH_COLUMN);
foreach (array('Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio') as $r) {
    t_ok(in_array($r, $roles, true), "existe el rol {$r}");
}
