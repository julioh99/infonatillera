<?php
// tests/_bootstrap.php
// Base comun de las pruebas con base de datos. Reglas:
//  - La base sale SIEMPRE de DB_DATABASE (variables de entorno); no hay credenciales escritas aqui.
//  - Se rechaza cualquier host que no sea local y cualquier base que no parezca de pruebas
//    (nombre que empiece por "nt_" o "natillera_test", o termine en "_test"). Nunca la remota.
//  - Las pruebas crean sus propios socios y reuniones y los borran al terminar.
// Uso:  DB_DATABASE=nt_higiene DB_USERNAME=root DB_PASSWORD= php tests/mi_prueba.php

putenv('NT_NO_AUTOMIGRATE=1'); // las pruebas no migran la base sin que se pida

require_once __DIR__ . '/../config/database.php';
Database::$autoMigrar = false;
Database::loadEnv(__DIR__ . '/../.env'); // solo completa lo que falte; las variables reales mandan

$GLOBALS['__t'] = array('ok' => 0, 'fallos' => 0, 'socios' => array(), 'reuniones' => array());

function t_salir($msg, $code = 2) {
    fwrite(STDERR, $msg . "\n");
    exit($code);
}

$__host = getenv('DB_HOST') ?: '127.0.0.1';
$__db = getenv('DB_DATABASE');
if (!in_array($__host, array('127.0.0.1', 'localhost', '::1'), true)) {
    t_salir("Prueba cancelada: DB_HOST={$__host} no es local. Usa DB_HOST=127.0.0.1 y una base de pruebas.");
}
if (!$__db || !preg_match('/^(nt_|natillera_test)|_test$/', $__db)) {
    t_salir("Prueba cancelada: indica una base de pruebas en DB_DATABASE (nt_* o natillera_test). Recibido: " . ($__db ?: '(vacio)'));
}

function t_db() {
    return Database::getConnection();
}

function t_ok($condicion, $nombre) {
    if ($condicion) {
        $GLOBALS['__t']['ok']++;
        echo "  OK    {$nombre}\n";
    } else {
        $GLOBALS['__t']['fallos']++;
        echo "  FALLA {$nombre}\n";
    }
    return (bool)$condicion;
}

function t_igual($obtenido, $esperado, $nombre) {
    $ok = (is_numeric($obtenido) && is_numeric($esperado)) ? abs($obtenido - $esperado) < 0.005 : $obtenido === $esperado;
    if (!t_ok($ok, $nombre)) {
        echo "        esperado " . var_export($esperado, true) . ", obtenido " . var_export($obtenido, true) . "\n";
    }
    return $ok;
}

/** Ejecuta $fn y espera que lance una excepcion. */
function t_lanza($fn, $nombre) {
    try {
        $fn();
    } catch (Exception $e) {
        return t_ok(true, $nombre);
    }
    return t_ok(false, $nombre . ' (no lanzo excepcion)');
}

function t_omitir($motivo) {
    echo "  OMITIDA {$motivo}\n";
    exit(0);
}

/** Crea un socio de prueba (rol Socio) y lo registra para borrarlo al terminar. */
function t_crear_socio($nombre = 'Socio de prueba') {
    $db = t_db();
    $rol = $db->query("SELECT id FROM natillera_roles WHERE nombre = 'Socio'")->fetchColumn();
    $cedula = 'T' . substr(str_replace('.', '', uniqid('', true)), -12) . mt_rand(10, 99);
    $st = $db->prepare("INSERT INTO natillera_usuarios (cedula, nombre_completo, telefono, password_hash, rol_id, password_changed) VALUES (:c, :n, '3000000000', :h, :r, 1)");
    $st->execute(array(':c' => $cedula, ':n' => $nombre, ':h' => password_hash('no-se-usa', PASSWORD_DEFAULT), ':r' => $rol));
    $id = (int)$db->lastInsertId();
    $GLOBALS['__t']['socios'][] = $id;
    return $id;
}

/** Crea una reunion de prueba (numero 9000+) y la registra para borrarla al terminar. */
function t_crear_reunion($estado = 'EN_PROCESO', $cuota = 55000) {
    $db = t_db();
    $num = 9000 + mt_rand(1, 900);
    $st = $db->prepare("INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado) VALUES (:n, CURDATE(), '14:00:00', :c, 'NINGUNO', 0, :e)");
    $st->execute(array(':n' => $num, ':c' => $cuota, ':e' => $estado));
    $id = (int)$db->lastInsertId();
    $GLOBALS['__t']['reuniones'][] = $id;
    return $id;
}

function t_limpiar() {
    $t = $GLOBALS['__t'];
    if (!$t['socios'] && !$t['reuniones']) return;
    try {
        $db = t_db();
        if ($db->inTransaction()) $db->rollBack();
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $socios = $t['socios'] ? implode(',', array_map('intval', $t['socios'])) : '0';
        $reuniones = $t['reuniones'] ? implode(',', array_map('intval', $t['reuniones'])) : '0';
        $ids = $db->query("SELECT id FROM natillera_prestamos WHERE socio_deudor_id IN ({$socios}) OR reunion_id IN ({$reuniones})")->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            $lista = implode(',', array_map('intval', $ids));
            foreach (array('natillera_abonos_prestamos', 'natillera_entregas_beneficios') as $tabla) {
                $db->exec("DELETE FROM {$tabla} WHERE prestamo_id IN ({$lista})");
            }
        }
        $cols = $db->query("
            SELECT c.TABLE_NAME, c.COLUMN_NAME FROM information_schema.COLUMNS c
            JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_TYPE = 'BASE TABLE'
            WHERE c.TABLE_SCHEMA = DATABASE()
              AND c.COLUMN_NAME IN ('reunion_id','reunion_id_retiro','socio_id','socio_deudor_id','registrado_por_usuario_id','entregado_por_usuario_id','usuario_id')
              AND c.TABLE_NAME NOT IN ('natillera_usuarios','natillera_reuniones','natillera_historico')
        ")->fetchAll();
        foreach ($cols as $c) {
            $lista = (strpos($c['COLUMN_NAME'], 'reunion') === 0) ? $reuniones : $socios;
            $db->exec("DELETE FROM `{$c['TABLE_NAME']}` WHERE `{$c['COLUMN_NAME']}` IN ({$lista})");
        }
        $db->exec("DELETE FROM natillera_reuniones WHERE id IN ({$reuniones})");
        $db->exec("DELETE FROM natillera_usuarios WHERE id IN ({$socios})");
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $e) {
        fwrite(STDERR, "Aviso: no se pudo limpiar los datos de prueba: " . $e->getMessage() . "\n");
    }
}

register_shutdown_function(function () {
    t_limpiar();
    $t = $GLOBALS['__t'];
    echo "  -- {$t['ok']} correctas, {$t['fallos']} fallas\n";
    if ($t['fallos'] > 0) {
        exit(1); // un exit dentro de un shutdown handler fija el codigo de salida
    }
});

// Una excepcion no capturada cuenta como falla (codigo distinto de 0).
set_exception_handler(function ($e) {
    echo "  FALLA excepcion no capturada: " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $GLOBALS['__t']['fallos']++;
    exit(1);
});
