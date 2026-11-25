<?php
// tests/historico_test.php
// Prueba del historico de anos contra una base LOCAL de pruebas (nunca la remota).
//
//   DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nt_historico DB_USERNAME=root DB_PASSWORD= \
//     php tests/historico_test.php
//
// Guarda -> crea ano nuevo -> vuelve al anterior -> vuelve al nuevo y compara conteos y contenido por tabla.
// Tambien prueba fallos simulados (rollback), huella danada, respaldos inmutables y que no se guarden contrasenas.
// Al terminar deja la base como estaba y borra los registros de historico que creo.

$db = getenv('DB_DATABASE');
$host = getenv('DB_HOST');
if ($db === false || !preg_match('/^nt_[a-z0-9_]+$/', $db) || !in_array($host, array('127.0.0.1', 'localhost'), true)) {
    fwrite(STDERR, "Por seguridad esta prueba solo corre contra una base local 'nt_*' definida por variables de entorno (DB_HOST=127.0.0.1 DB_DATABASE=nt_historico ...).\n");
    exit(2);
}

require_once __DIR__ . '/../app/Models/Historico.php';
require_once __DIR__ . '/../app/Models/Usuario.php';

$fallos = 0;
$total = 0;
function chequear($nombre, $ok, $detalle = '') {
    global $fallos, $total;
    $total++;
    if ($ok) {
        echo "  OK    {$nombre}\n";
    } else {
        $fallos++;
        echo "  FALLA {$nombre} {$detalle}\n";
    }
}

/** Estado completo comparable: conteos y huella del contenido por tabla + socios activos y su orden. */
function estado(PDO $pdo) {
    $e = array('conteos' => array(), 'hash' => array());
    foreach (Historico::$TABLAS as $t) {
        $filas = $pdo->query("SELECT * FROM `{$t}` ORDER BY id")->fetchAll();
        $e['conteos'][$t] = count($filas);
        $e['hash'][$t] = md5(json_encode($filas));
    }
    $e['socios'] = $pdo->query("SELECT id, orden FROM natillera_usuarios WHERE estado = 1 ORDER BY orden, id")->fetchAll();
    $e['n_usuarios'] = (int)$pdo->query("SELECT COUNT(*) FROM natillera_usuarios")->fetchColumn();
    $e['n_push'] = (int)$pdo->query("SELECT COUNT(*) FROM natillera_push_subscriptions")->fetchColumn();
    return $e;
}
function conteoRespaldos(PDO $pdo) {
    return (int)$pdo->query("SELECT COUNT(*) FROM natillera_historico WHERE tipo = 'RESPALDO_AUTOMATICO'")->fetchColumn();
}
function archivosRespaldo() {
    return glob(Historico::directorioRespaldos() . '/*.json.gz') ?: array();
}

$dirTmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nt_historico_test_' . getmypid();
Historico::$dirRespaldos = $dirTmp;

$h = new Historico();
$pdo = Database::getConnection();
$uid = (int)$pdo->query("SELECT u.id FROM natillera_usuarios u JOIN natillera_roles r ON u.rol_id = r.id WHERE r.nombre = 'Presidente' ORDER BY u.id LIMIT 1")->fetchColumn();
if ($uid <= 0) {
    fwrite(STDERR, "La base de pruebas no tiene un usuario Presidente.\n");
    exit(2);
}
$maxHistIdAntes = (int)$pdo->query("SELECT IFNULL(MAX(id), 0) FROM natillera_historico")->fetchColumn();
$cargadoAntes = $pdo->query("SELECT id FROM natillera_historico WHERE tipo = 'ANIO' AND cargado = 1")->fetchAll(PDO::FETCH_COLUMN);
$sufijo = date('His');

echo "Base: {$db}. Operador (Presidente) id {$uid}.\n\n";

echo "1. Guardar el ano actual (A)\n";
$estadoOriginal = estado($pdo);
$nombreA = "Prueba A {$sufijo}";
if ($h->getActual()) {
    $pdo->exec("UPDATE natillera_historico SET cargado = 0 WHERE tipo = 'ANIO'");
}
$rA = $h->guardarActual($nombreA, $uid);
$estadoA = estado($pdo);
chequear('guardar no cambia las tablas', $estadoA['hash'] === $estadoOriginal['hash'] && $estadoA['conteos'] === $estadoOriginal['conteos']);
chequear('queda un ano cargado', $h->getActual() && (int)$h->getActual()['id'] === $rA['id']);
chequear('la foto lleva los conteos de las tablas', $rA['conteos'] === $estadoOriginal['conteos']);
chequear('verifica la huella SHA-256', $h->verificar($rA['id']));
list(, $jsonA) = $h->descargar($rA['id']);
chequear('la copia JSON no contiene contrasenas', stripos($jsonA, 'password') === false);
chequear('la copia JSON incluye socios activos con orden', count(json_decode($jsonA, true)['socios']) === count($estadoOriginal['socios']));
echo "     (reuniones {$estadoA['conteos']['natillera_reuniones']}, prestamos {$estadoA['conteos']['natillera_prestamos']}, cuotas {$estadoA['conteos']['natillera_ahorros_cuotas']}, abonos {$estadoA['conteos']['natillera_abonos_prestamos']})\n";

echo "\n2. Fallos simulados: no cambian nada (rollback)\n";
$resp0 = conteoRespaldos($pdo);
$arch0 = count(archivosRespaldo());
$prestamosActivos = $h->prestamosActivos();
$idsSocios = array_map(function ($s) { return (int)$s['id']; }, $estadoOriginal['socios']);
$idsDiez = array_slice($idsSocios, 0, 10);
foreach (array('antes_de_vaciar', 'despues_de_vaciar', 'despues_de_crear') as $punto) {
    Historico::$simularFallo = $punto;
    $lanzo = false;
    try {
        $h->crearAnioNuevo(array('nombre' => "Prueba fallo {$punto} {$sufijo}", 'fecha_inicio' => '2026-12-01', 'orden' => array_reverse($idsDiez), 'continuan' => $idsDiez, 'pasar_prestamos' => true), $uid);
    } catch (RuntimeException $e) {
        $lanzo = true;
    }
    Historico::$simularFallo = null;
    $e = estado($pdo);
    chequear("crear con fallo en '{$punto}' lanza excepcion", $lanzo);
    chequear("crear con fallo en '{$punto}' deja tablas, socios y push intactos", $e === $estadoA);
}
chequear('no se crearon respaldos ni archivos huerfanos', conteoRespaldos($pdo) === $resp0 && count(archivosRespaldo()) === $arch0);
chequear('el ano A sigue cargado', $h->getActual() && (int)$h->getActual()['id'] === $rA['id']);

echo "\n3. Crear ano nuevo (B) con 10 socios, orden invertido y prestamos activos como saldo inicial\n";
$nombreB = "Prueba B {$sufijo}";
$rB = $h->crearAnioNuevo(array('nombre' => $nombreB, 'fecha_inicio' => '2026-12-01', 'orden' => array_reverse($idsDiez), 'continuan' => $idsDiez, 'pasar_prestamos' => true), $uid);
$eB0 = estado($pdo);
$esperadosActivos = $idsDiez;
if (!in_array($uid, $esperadosActivos, true)) {
    $esperadosActivos[] = $uid;
}
chequear('tablas vaciadas (reuniones, cuotas, abonos...)', $eB0['conteos']['natillera_reuniones'] === 0 && $eB0['conteos']['natillera_ahorros_cuotas'] === 0 && $eB0['conteos']['natillera_abonos_prestamos'] === 0 && $eB0['conteos']['natillera_entregas_beneficios'] === 0);
chequear('prestamos activos pasaron como saldo inicial', $eB0['conteos']['natillera_prestamos'] === count($prestamosActivos) && $rB['prestamos_arrastrados'] === count($prestamosActivos), 'esperados ' . count($prestamosActivos));
$capitalA = round(array_sum(array_map(function ($p) { return $p['saldo_capital']; }, $prestamosActivos)), 2);
$capitalB = round((float)$pdo->query("SELECT IFNULL(SUM(monto_prestado),0) FROM natillera_prestamos")->fetchColumn(), 2);
chequear('el capital pasado es igual al saldo de los prestamos activos', abs($capitalA - $capitalB) < 0.01, "A={$capitalA} B={$capitalB}");
$activosB = array_map(function ($s) { return (int)$s['id']; }, $eB0['socios']);
chequear('solo los socios elegidos (y quien opera) quedan activos', count($activosB) === count($esperadosActivos) && !array_diff($activosB, $esperadosActivos));
chequear('el orden elegido se respeta', array_slice($activosB, 0, count(array_intersect(array_reverse($idsDiez), $esperadosActivos))) === array_values(array_intersect(array_reverse($idsDiez), $esperadosActivos)));
chequear('usuarios y suscripciones push no se borraron', $eB0['n_usuarios'] === $estadoA['n_usuarios'] && $eB0['n_push'] === $estadoA['n_push']);
chequear('quien opera sigue activo', in_array($uid, $activosB, true));
chequear('hay un respaldo automatico y su archivo', conteoRespaldos($pdo) === $resp0 + 1 && count(archivosRespaldo()) === $arch0 + 1);
chequear('el ano B quedo cargado y A guardado', $h->getActual() && (int)$h->getActual()['id'] === $rB['id']);

// Un poco de datos propios del ano B para comprobar que tambien se restaura
$pdo->exec("INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, valor_cuota_base, estado) VALUES (1, '2026-12-01', 55000, 'PROGRAMADA'), (2, '2026-12-15', 55000, 'PROGRAMADA')");
$h->guardarActual(null, $uid);
$estadoB = estado($pdo);
chequear('B tiene sus 2 reuniones', $estadoB['conteos']['natillera_reuniones'] === 2);

echo "\n4. Volver al ano anterior (A): mismos conteos y contenido\n";
$rCargaA = $h->cargar($rA['id'], $uid);
$e = estado($pdo);
chequear('conteos por tabla identicos a A', $e['conteos'] === $estadoA['conteos']);
chequear('contenido por tabla identico a A', $e['hash'] === $estadoA['hash']);
chequear('socios activos y orden identicos a A', $e['socios'] === $estadoA['socios']);
chequear('usuarios y push intactos', $e['n_usuarios'] === $estadoA['n_usuarios'] && $e['n_push'] === $estadoA['n_push']);
chequear('A queda cargado', (int)$h->getActual()['id'] === $rA['id']);

echo "\n5. Volver al ano nuevo (B)\n";
$h->cargar($rB['id'], $uid);
$e = estado($pdo);
chequear('conteos por tabla identicos a B', $e['conteos'] === $estadoB['conteos']);
chequear('contenido por tabla identico a B', $e['hash'] === $estadoB['hash']);
chequear('socios activos y orden identicos a B', $e['socios'] === $estadoB['socios']);

echo "\n6. Fallos simulados al cargar: rollback completo\n";
$resp1 = conteoRespaldos($pdo);
$arch1 = count(archivosRespaldo());
foreach (array('antes_de_vaciar', 'despues_de_vaciar', 'despues_de_restaurar') as $punto) {
    Historico::$simularFallo = $punto;
    $lanzo = false;
    try {
        $h->cargar($rA['id'], $uid);
    } catch (RuntimeException $ex) {
        $lanzo = true;
    }
    Historico::$simularFallo = null;
    $e = estado($pdo);
    chequear("cargar con fallo en '{$punto}': excepcion y estado intacto", $lanzo && $e === $estadoB);
}
chequear('sin respaldos ni archivos nuevos tras los fallos', conteoRespaldos($pdo) === $resp1 && count(archivosRespaldo()) === $arch1);
chequear('B sigue cargado tras los fallos', (int)$h->getActual()['id'] === $rB['id']);

echo "\n7. Huella danada: no se restaura\n";
$stmt = $pdo->prepare("UPDATE natillera_historico SET huella = :h WHERE id = :id");
$stmt->execute(array(':h' => str_repeat('0', 64), ':id' => $rA['id']));
$lanzo = false;
try {
    $h->cargar($rA['id'], $uid);
} catch (Exception $ex) {
    $lanzo = true;
}
chequear('huella distinta => se rechaza', $lanzo);
chequear('y las tablas no cambian', estado($pdo) === $estadoB);
$stmt->execute(array(':h' => hash('sha256', gzuncompress($pdo->query("SELECT snapshot FROM natillera_historico WHERE id = " . (int)$rA['id'])->fetchColumn())), ':id' => $rA['id']));
chequear('con la huella correcta vuelve a verificar', $h->verificar($rA['id']));

echo "\n8. Respaldos inmutables\n";
$idResp = (int)$pdo->query("SELECT id FROM natillera_historico WHERE tipo = 'RESPALDO_AUTOMATICO' ORDER BY id LIMIT 1")->fetchColumn();
$antes = $pdo->query("SELECT huella FROM natillera_historico WHERE id = {$idResp}")->fetchColumn();
$h->guardarActual(null, $uid);
$h->guardarActual(null, $uid);
chequear('guardar no modifica los respaldos', $pdo->query("SELECT huella FROM natillera_historico WHERE id = {$idResp}")->fetchColumn() === $antes);
$lanzo = false;
try {
    $h->cargar($idResp, $uid);
} catch (InvalidArgumentException $ex) {
    $lanzo = true;
}
chequear('un respaldo no se carga directamente', $lanzo);
$rec = $h->recuperarRespaldo($idResp, $uid);
chequear('un respaldo se puede recuperar como ano nuevo', $rec['id'] > 0 && $h->verificar($rec['id']));
chequear('hay copias en archivo y el .htaccess de denegacion', count(archivosRespaldo()) >= 3 && is_file($dirTmp . '/.htaccess') && strpos(file_get_contents($dirTmp . '/.htaccess'), 'denied') !== false);

echo "\n9. Limpieza: dejar la base como estaba\n";
$h->cargar($rA['id'], $uid);
$e = estado($pdo);
chequear('la base vuelve al estado original', $e['hash'] === $estadoOriginal['hash'] && $e['conteos'] === $estadoOriginal['conteos'] && $e['socios'] === $estadoOriginal['socios']);
$pdo->exec("DELETE FROM natillera_historico WHERE id > {$maxHistIdAntes}");
$pdo->exec("UPDATE natillera_historico SET cargado = 0 WHERE tipo = 'ANIO'");
foreach ($cargadoAntes as $id) {
    $pdo->exec("UPDATE natillera_historico SET cargado = 1 WHERE id = " . (int)$id);
}
foreach (glob($dirTmp . '/{,.}*', GLOB_BRACE) ?: array() as $f) {
    if (is_file($f)) {
        @unlink($f);
    }
}
@rmdir($dirTmp);

echo "\n" . ($total - $fallos) . " de {$total} comprobaciones correctas.\n";
exit($fallos === 0 ? 0 : 1);
