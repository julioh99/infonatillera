<?php
// tests/run.php - mini ejecutor de pruebas (sin Composer ni PHPUnit).
// Descubre tests/*_test.php y tests/test_*.php, ejecuta cada archivo como un proceso aparte y resume.
//   DB_DATABASE=nt_higiene DB_USERNAME=root DB_PASSWORD= php tests/run.php [filtro]
// Codigo de salida 0 = todo bien; 1 = alguna prueba fallo. Un archivo pasa si termina con codigo 0.
// Las pruebas con base de datos exigen DB_DATABASE de pruebas (ver tests/_bootstrap.php).

$dir = __DIR__;
$a = glob($dir . '/*_test.php');
$b = glob($dir . '/test_*.php');
$archivos = array_values(array_unique(array_merge($a ? $a : array(), $b ? $b : array())));
sort($archivos, SORT_STRING);

$filtro = isset($argv[1]) ? $argv[1] : null;
if ($filtro !== null) {
    $archivos = array_values(array_filter($archivos, function ($f) use ($filtro) {
        return strpos(basename($f), $filtro) !== false;
    }));
}
if (!$archivos) {
    echo "No hay pruebas" . ($filtro ? " que coincidan con '{$filtro}'" : '') . ".\n";
    exit(0);
}

$php = PHP_BINARY;
$resumen = array();
$fallos = 0;
foreach ($archivos as $f) {
    $nombre = basename($f);
    echo "== {$nombre}\n";
    $t0 = microtime(true);
    $salida = array();
    exec(escapeshellarg($php) . ' ' . escapeshellarg($f) . ' 2>&1', $salida, $codigo);
    foreach ($salida as $linea) echo $linea . "\n";
    $seg = round(microtime(true) - $t0, 1);
    $estado = ($codigo === 0) ? 'OK' : 'FALLO';
    if ($codigo !== 0) $fallos++;
    echo "   -> {$estado} ({$seg}s)\n\n";
    $resumen[] = array($nombre, $estado);
}

echo "==== RESUMEN ====\n";
foreach ($resumen as $r) printf("  %-8s %s\n", $r[1], $r[0]);
echo ($fallos === 0 ? "Todas las pruebas pasaron" : "{$fallos} prueba(s) con fallos") . " (" . count($archivos) . " archivos).\n";
exit($fallos === 0 ? 0 : 1);
