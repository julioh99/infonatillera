<?php
// tests/migraciones_test.php - el ejecutor de migraciones sobre una base vacia temporal y sobre la base de pruebas.
// Necesita permiso CREATE DATABASE (en local con root); si no lo tiene, se omite.
require_once __DIR__ . '/_bootstrap.php';

// 1. La base de pruebas debe estar al dia (sin pendientes)
$m = new Migrator(t_db());
$pend = array();
foreach ($m->estado() as $e) {
    if ($e['estado'] === 'PENDIENTE') $pend[] = $e['nombre'];
}
t_ok(!$pend, 'la base de pruebas no tiene migraciones pendientes' . ($pend ? ' (' . implode(', ', $pend) . ')' : ''));

// 2. Base vacia temporal: migra desde cero y es idempotente
$temporal = getenv('DB_DATABASE') . '_mig_tmp';
if (!preg_match('/^[A-Za-z0-9_]+$/', $temporal)) t_omitir('nombre de base temporal no valido');
$admin = t_db();
try {
    $admin->exec("DROP DATABASE IF EXISTS `{$temporal}`");
    $admin->exec("CREATE DATABASE `{$temporal}` CHARACTER SET utf8mb4");
} catch (Exception $e) {
    t_omitir('sin permiso para crear la base temporal: ' . $e->getMessage());
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
try {
    $tmp = new PDO("mysql:host={$host};port={$port};dbname={$temporal};charset=utf8mb4", $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    $mt = new Migrator($tmp);
    $total = count($mt->archivos());
    $r1 = $mt->run();
    t_ok($r1['error'] === null, 'migra una base vacia sin errores' . ($r1['error'] ? ': ' . $r1['error']['mensaje'] : ''));
    t_igual(count($r1['aplicadas']), $total, 'aplica todas las migraciones');
    $r2 = $mt->run();
    t_igual(count($r2['aplicadas']), 0, 'segunda ejecucion no aplica nada');
    $roles = (int)$tmp->query('SELECT COUNT(*) FROM natillera_roles')->fetchColumn();
    t_igual($roles, 5, 'quedan los 5 roles base');
    foreach (array('natillera_usuarios', 'natillera_reuniones', 'natillera_prestamos', 'natillera_migraciones') as $t) {
        $ok = (int)$tmp->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$t}'")->fetchColumn();
        t_igual($ok, 1, "existe {$t}");
    }
    $tmp = null;
} finally {
    $admin->exec("DROP DATABASE IF EXISTS `{$temporal}`");
}

// 3. dividirSql respeta comillas y comentarios
$partes = Migrator::dividirSql("SELECT 'a;b'; -- comentario; con punto y coma\nSELECT 2 /* ; */;\nSELECT 3");
t_igual(count($partes), 3, 'dividirSql separa 3 sentencias');
t_ok(strpos($partes[0], "'a;b'") !== false, 'dividirSql no corta dentro de comillas');
