<?php
// scripts/_bootstrap.php
// Helper para scripts CLI y de scratch: carga el .env y expone las credenciales de la BD remota.

require_once __DIR__ . '/../config/database.php';
Database::loadEnv(__DIR__ . '/../.env');

/**
 * Devuelve la configuración de la BD remota leída desde .env
 * (REMOTE_DB_HOST, REMOTE_DB_DATABASE, REMOTE_DB_USERNAME, REMOTE_DB_PASSWORD).
 */
function remote_db_config() {
    $cfg = array(
        'host'   => getenv('REMOTE_DB_HOST'),
        'dbName' => getenv('REMOTE_DB_DATABASE'),
        'user'   => getenv('REMOTE_DB_USERNAME'),
        'pass'   => getenv('REMOTE_DB_PASSWORD'),
    );
    if ($cfg['host'] === false || $cfg['host'] === '' || $cfg['dbName'] === false || $cfg['user'] === false || $cfg['pass'] === false) {
        fwrite(STDERR, "Faltan variables REMOTE_DB_* en .env\n");
        exit(1);
    }
    return $cfg;
}
