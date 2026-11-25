<?php
// scripts/migrate.php
// Ejecutor de migraciones.   Uso (desde la raiz del proyecto):
//   php scripts/migrate.php              aplica las migraciones pendientes
//   php scripts/migrate.php --status     lista el estado de cada migracion
//   php scripts/migrate.php --dry-run    muestra que se aplicaria, sin tocar nada
//   php scripts/migrate.php --crear-bd   crea la base si no existe (necesita permiso CREATE)
//   php scripts/migrate.php --sin-seed   no crea el Presidente inicial en una base nueva
// La base sale del .env o de las variables de entorno DB_* (ver docs/PRUEBAS_LOCALES.md).

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';
Database::$autoMigrar = false; // este script es quien migra

$args = array_slice($argv, 1);
$status = in_array('--status', $args, true);
$dry = in_array('--dry-run', $args, true);
$crearBd = in_array('--crear-bd', $args, true);
$sinSeed = in_array('--sin-seed', $args, true);
if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
    echo "Uso: php scripts/migrate.php [--status] [--dry-run] [--crear-bd] [--sin-seed]\n";
    exit(0);
}

Database::loadEnv(__DIR__ . '/../.env');
try {
    $db = Database::crearConexion($crearBd);
} catch (Exception $e) {
    Database::fallaConexion($e); // registra el detalle en logs/app.log y sale con codigo 1
}

$m = new Migrator($db);
$dbName = $db->query('SELECT DATABASE()')->fetchColumn();
echo "Base de datos: {$dbName}\n";

if ($status) {
    $filas = $m->estado();
    if (!$filas) { echo "No hay migraciones.\n"; exit(0); }
    $pend = 0;
    foreach ($filas as $f) {
        if ($f['estado'] === 'PENDIENTE') $pend++;
        printf("  %-10s %-45s %s\n", $f['estado'], $f['nombre'], $f['fecha'] ? $f['fecha'] : '');
        if ($f['estado'] === 'MODIFICADA') echo "             (el archivo cambio despues de aplicarse; no se vuelve a ejecutar)\n";
    }
    echo "Pendientes: {$pend}\n";
    exit(0);
}

if ($dry) {
    $res = $m->run(array('dry_run' => true));
    if (!$res['pendientes']) { echo "Nada pendiente.\n"; exit(0); }
    echo "Se aplicarian " . count($res['pendientes']) . " migraciones:\n";
    foreach ($res['pendientes'] as $n) echo "  - {$n}\n";
    exit(0);
}

$res = $m->run(array('espera_lock' => 60, 'salida' => function ($t) { echo $t; }));
if ($res['sin_lock']) {
    fwrite(STDERR, "Otra migracion esta en curso (bloqueo ocupado). Intenta de nuevo en un momento.\n");
    exit(2);
}
if ($res['error']) {
    Database::log('Migracion ' . $res['error']['nombre'] . ' fallo: ' . $res['error']['mensaje']);
    fwrite(STDERR, "ERROR en la migracion {$res['error']['nombre']}. Se detiene; las siguientes no se ejecutaron.\n");
    fwrite(STDERR, Database::appDebug() || in_array('--debug', $args, true)
        ? "Detalle: {$res['error']['mensaje']}\n"
        : "El detalle quedo en logs/app.log (o repite con --debug).\n");
    exit(1);
}
echo $res['aplicadas'] ? "Migraciones aplicadas: " . count($res['aplicadas']) . "\n" : "Nada pendiente: la base ya esta al dia.\n";

if (!$sinSeed) {
    try {
        $inicial = Database::sembrarInicial($db);
        if ($inicial) {
            echo "\nBase nueva: se creo el usuario Presidente.\n";
            echo "  Cedula: {$inicial['cedula']}\n";
            echo "  Clave temporal: {$inicial['clave']}\n";
            echo "Se muestra SOLO ahora; el sistema pedira cambiarla al primer ingreso.\n";
        }
    } catch (Exception $e) {
        Database::log('Siembra inicial: ' . $e->getMessage());
        fwrite(STDERR, "No se pudo crear los datos iniciales (ver logs/app.log).\n");
        exit(1);
    }
}
exit(0);
