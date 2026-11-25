<?php
// scripts/generar_db_sql.php
// Regenera db.sql (esquema de referencia, SOLO estructura, sin datos) desde la base indicada por DB_*.
// Uso, con TODAS las migraciones ya aplicadas en esa base:
//   DB_DATABASE=nt_higiene php scripts/migrate.php
//   DB_DATABASE=nt_higiene php scripts/generar_db_sql.php            (escribe db.sql en la raiz)
// No necesita mysqldump. Quita DEFINER de las vistas y AUTO_INCREMENT de las tablas.

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config/database.php';
Database::$autoMigrar = false;
Database::loadEnv(__DIR__ . '/../.env');
try {
    $db = Database::crearConexion();
} catch (Exception $e) {
    Database::fallaConexion($e);
}

$destino = isset($argv[1]) ? $argv[1] : __DIR__ . '/../db.sql';
$bd = $db->query('SELECT DATABASE()')->fetchColumn();

$tablas = array();
$vistas = array();
foreach ($db->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME") as $r) {
    if ($r['TABLE_TYPE'] === 'VIEW') $vistas[] = $r['TABLE_NAME']; else $tablas[] = $r['TABLE_NAME'];
}

$sql = "-- InfoNatillera: esquema de referencia (SOLO estructura, sin datos).\n";
$sql .= "-- Generado con: php scripts/generar_db_sql.php  (despues de php scripts/migrate.php)\n";
$sql .= "-- No lo edites a mano. Lo carga la migracion 00000000_0000_baseline solo en una base vacia.\n\n";
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tablas as $t) {
    $row = $db->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM);
    $ddl = $row[1];
    $ddl = preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
    $ddl = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $ddl);
    $sql .= $ddl . ";\n\n";
}
foreach ($vistas as $v) {
    $row = $db->query("SHOW CREATE VIEW `{$v}`")->fetch(PDO::FETCH_NUM);
    $ddl = str_replace('`' . $bd . '`.', '', $row[1]);
    $ddl = preg_replace('/^CREATE\s+(ALGORITHM=\w+\s+)?(DEFINER=\S+\s+)?(SQL SECURITY \w+\s+)?VIEW/', 'CREATE OR REPLACE SQL SECURITY INVOKER VIEW', $ddl);
    $sql .= $ddl . ";\n\n";
}
$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents($destino, $sql);
echo "Escrito " . realpath($destino) . ": " . count($tablas) . " tablas, " . count($vistas) . " vistas.\n";
