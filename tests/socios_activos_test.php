<?php
// tests/socios_activos_test.php
// Las listas para operar usan SOLO socios activos (estado = 1), ordenados por `orden` y luego id;
// los inactivos conservan su historia. El llamado a lista tolera socios repetidos.
//   DB_HOST=127.0.0.1 DB_DATABASE=nt_a1 DB_USERNAME=root DB_PASSWORD= php tests/socios_activos_test.php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/Actividad.php';
require_once __DIR__ . '/../app/Models/EntregaBeneficio.php';

$db = t_db();
$usuario = new Usuario();

// Tres activos con orden invertido respecto al id, y dos inactivos
$a1 = t_crear_socio('Activo uno');
$a2 = t_crear_socio('Activo dos');
$a3 = t_crear_socio('Activo tres');
$i1 = t_crear_socio('Inactivo uno');
$i2 = t_crear_socio('Inactivo dos');
$base = (int)$db->query('SELECT IFNULL(MAX(orden),0) FROM natillera_usuarios')->fetchColumn() + 100;
$upd = $db->prepare('UPDATE natillera_usuarios SET estado = :e, orden = :o WHERE id = :id');
foreach (array(array($a1, 1, $base + 3), array($a2, 1, $base + 1), array($a3, 1, $base + 2), array($i1, 0, $base + 0), array($i2, 0, $base + 4)) as $f) {
    $upd->execute(array(':e' => $f[1], ':o' => $f[2], ':id' => $f[0]));
}
$ids = function ($lista) { return array_map(function ($s) { return (int)$s['id']; }, $lista); };
$mios = array($a1, $a2, $a3, $i1, $i2);
$soloMios = function ($lista) use ($ids, $mios) { return array_values(array_intersect($ids($lista), $mios)); };

// 1. Usuario: por defecto solo activos y en orden
t_igual($soloMios($usuario->getAllSocios()), array($a2, $a3, $a1), 'getAllSocios() por defecto: solo activos, por orden');
t_igual($soloMios($usuario->getSociosActivos()), array($a2, $a3, $a1), 'getSociosActivos(): solo activos, por orden');
t_igual($soloMios($usuario->getAllSocios(true)), array($a2, $a3, $a1, $i1, $i2), 'getAllSocios(true): activos primero y luego inactivos');
t_igual($soloMios($usuario->getTodosSocios()), array($a2, $a3, $a1, $i1, $i2), 'getTodosSocios(): incluye inactivos');
t_ok($usuario->getSocioById($i1) !== null, 'un socio inactivo se sigue pudiendo consultar por id');

// 2. Entregas: pendientes de ronda/rifa/prestamo solo activos y en orden
$entrega = new EntregaBeneficio();
foreach (array('PRESTAMO', 'RONDA', 'RIFA') as $tipo) {
    $p = $soloMios($entrega->getSociosPendientes($tipo));
    t_igual($p, array($a2, $a3, $a1), "pendientes de {$tipo}: solo activos, por orden");
}

// 3. Meta de intereses desde la configuracion
$meta = Usuario::metaIntereses();
t_ok($meta > 0 && $meta === (float)Money::config('prestamo.meta_intereses_anual'), 'la meta de intereses sale de config/natillera.php');
t_igual($usuario->getResumenFinancieroSocio($a1)['meta_interes'], $meta, 'el resumen usa la meta configurada');

// 4. Actividad: un participante inactivo no entra
$actividad = new Actividad();
$_SESSION['usuario'] = array('id' => $a1);
$ok = $actividad->crearActividad(array(
    'nombre_actividad' => 'Prueba socios activos', 'descripcion' => '', 'fecha_actividad' => date('Y-m-d'),
    'ingresos_totales' => 0, 'gastos_totales' => 0, 'cuota_por_socio' => 1000, 'creado_por_usuario_id' => $a1,
), array($a1 => 1000, $i1 => 1000));
t_ok($ok, 'crea la actividad');
$actId = (int)$db->query("SELECT MAX(id) FROM natillera_actividades WHERE nombre_actividad = 'Prueba socios activos'")->fetchColumn();
$parts = $db->query("SELECT socio_id FROM natillera_actividad_participantes WHERE actividad_id = {$actId}")->fetchAll(PDO::FETCH_COLUMN);
t_igual(array_map('intval', $parts), array($a1), 'la actividad solo tiene participantes activos');

// 5. Llamado a lista con socio repetido no falla y queda el ultimo registro
$reunionId = t_crear_reunion('EN_PROCESO', 55000);
$reunion = new Reunion();
$ok = $reunion->guardarLlamadoListaBatch($reunionId, array(
    array('socio_id' => $a1, 'pagou_cuota' => 0, 'ahorro_extra' => 0, 'generar_autoprestamo' => 0),
    array('socio_id' => $a2, 'pagou_cuota' => 1, 'ahorro_extra' => 0, 'generar_autoprestamo' => 0),
    array('socio_id' => $a1, 'pagou_cuota' => 1, 'ahorro_extra' => 5000, 'generar_autoprestamo' => 0),
));
t_ok($ok, 'llamado a lista con socio repetido no falla');
$filas = $db->query("SELECT socio_id, cuota_pagada, monto_ahorro_extra FROM natillera_ahorros_cuotas WHERE reunion_id = {$reunionId} ORDER BY socio_id")->fetchAll();
t_igual(count($filas), 2, 'una sola fila por socio');
$f1 = null;
foreach ($filas as $f) if ((int)$f['socio_id'] === $a1) $f1 = $f;
t_ok($f1 && (int)$f1['cuota_pagada'] === 1 && (float)$f1['monto_ahorro_extra'] === 5000.0, 'del socio repetido queda el ultimo registro');
t_ok($reunion->guardarLlamadoListaBatch($reunionId, array(array('socio_id' => $a2, 'pagou_cuota' => 0, 'ahorro_extra' => 0, 'generar_autoprestamo' => 0))), 're-guardar el llamado a lista funciona');

$db->exec("DELETE FROM natillera_actividad_participantes WHERE actividad_id = {$actId}");
$db->exec("DELETE FROM natillera_actividades WHERE id = {$actId}");
