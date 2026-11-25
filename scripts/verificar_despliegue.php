<?php
// scripts/verificar_despliegue.php
// Revision SOLO LECTURA del despliegue. Uso (desde la raiz del proyecto):
//   php scripts/verificar_despliegue.php
// Muestra una tabla OK / ADVERTENCIA / FALLO. Nunca imprime contrasenas ni valores del .env.
// Codigo de salida: 0 si no hay FALLO, 1 si hay al menos uno.
// Las variables reales del entorno (DB_HOST=... php ...) tienen prioridad sobre el .env.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = realpath(__DIR__ . '/..');
require_once $raiz . '/config/database.php';
Database::$autoMigrar = false; // jamas aplicar nada
Database::loadEnv($raiz . '/.env');

$filas = array();
function nt_v($estado, $tema, $detalle) {
    global $filas;
    $filas[] = array($estado, $tema, $detalle);
}

// ---------------------------------------------------------------- PHP
if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
    nt_v('OK', 'Version de PHP', PHP_VERSION);
} else {
    nt_v('FALLO', 'Version de PHP', PHP_VERSION . ' (se requiere 7.2 o superior)');
}
$requeridas = array(
    'pdo_mysql' => 'conexion a MySQL',
    'openssl'   => 'firma de Web Push',
    'mbstring'  => 'textos con tildes',
    'json'      => 'JSON',
    'curl'      => 'envio de Web Push',
);
foreach ($requeridas as $ext => $para) {
    if (extension_loaded($ext)) {
        nt_v('OK', 'Extension ' . $ext, 'cargada');
    } else {
        nt_v('FALLO', 'Extension ' . $ext, 'falta (' . $para . ')');
    }
}
// El codigo no usa gd ni zip; solo se avisa si faltan por si se agregan imagenes o exportes.
foreach (array('gd', 'zip') as $ext) {
    if (!extension_loaded($ext)) nt_v('ADVERTENCIA', 'Extension ' . $ext, 'no cargada (el codigo actual no la usa)');
}

// ---------------------------------------------------------------- BD
$db = null;
if (extension_loaded('pdo_mysql')) {
    try {
        $db = Database::crearConexion(false);
        $bd = $db->query('SELECT DATABASE()')->fetchColumn();
        $ver = $db->query('SELECT VERSION()')->fetchColumn();
        nt_v('OK', 'Conexion a la base', 'conectado a "' . $bd . '" (servidor ' . $ver . ')');
    } catch (Exception $e) {
        $db = null;
        Database::log('verificar_despliegue: conexion fallida: ' . $e->getMessage());
        nt_v('FALLO', 'Conexion a la base', 'no se pudo conectar (revise DB_* en .env; detalle en logs/app.log)');
    }
} else {
    nt_v('FALLO', 'Conexion a la base', 'omitida: falta pdo_mysql');
}

if ($db) {
    // Migraciones (solo lectura: Migrator::estado() no escribe)
    try {
        $m = new Migrator($db);
        $pend = array();
        $mod = array();
        $huer = array();
        $total = 0;
        foreach ($m->estado() as $e) {
            $total++;
            if ($e['estado'] === 'PENDIENTE') $pend[] = $e['nombre'];
            if ($e['estado'] === 'MODIFICADA') $mod[] = $e['nombre'];
            if ($e['estado'] === 'HUERFANA') $huer[] = $e['nombre'];
        }
        if ($pend) {
            nt_v('FALLO', 'Migraciones', count($pend) . ' pendiente(s): ' . implode(', ', $pend) . ' -> php scripts/migrate.php');
        } else {
            nt_v('OK', 'Migraciones', 'al dia (' . $total . ' registradas)');
        }
        if ($mod) nt_v('ADVERTENCIA', 'Migraciones modificadas', 'el archivo cambio tras aplicarse: ' . implode(', ', $mod));
        if ($huer) nt_v('ADVERTENCIA', 'Migraciones huerfanas', 'aplicadas pero sin archivo: ' . implode(', ', $huer));
    } catch (Exception $e) {
        nt_v('FALLO', 'Migraciones', 'no se pudo leer el estado (' . get_class($e) . ')');
    }

    // Tablas y columnas clave
    $existeTabla = function ($t) use ($db) {
        $s = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
        $s->execute(array(':t' => $t));
        return (int)$s->fetchColumn() > 0;
    };
    $existeCol = function ($t, $c) use ($db) {
        $s = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
        $s->execute(array(':t' => $t, ':c' => $c));
        return (int)$s->fetchColumn() > 0;
    };
    try {
        foreach (array(
            array('natillera_usuarios', 'orden'),
            array('natillera_usuarios', 'password_changed'),
        ) as $tc) {
            if ($existeCol($tc[0], $tc[1])) nt_v('OK', 'Columna ' . $tc[0] . '.' . $tc[1], 'existe');
            else nt_v('FALLO', 'Columna ' . $tc[0] . '.' . $tc[1], 'no existe (falta migrar)');
        }
        foreach (array('natillera_historico', 'natillera_migraciones', 'natillera_push_envios', 'natillera_recordatorios_enviados', 'natillera_cierres_reunion') as $t) {
            if ($existeTabla($t)) nt_v('OK', 'Tabla ' . $t, 'existe');
            else nt_v('FALLO', 'Tabla ' . $t, 'no existe (falta migrar)');
        }
        $s = $db->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'natillera_ahorros_cuotas' AND INDEX_NAME = 'uq_ahorros_reunion_socio' AND NON_UNIQUE = 0");
        $s->execute();
        if ((int)$s->fetchColumn() > 0) {
            nt_v('OK', 'Indice unico ahorros_cuotas', 'uq_ahorros_reunion_socio existe');
        } else {
            nt_v('FALLO', 'Indice unico ahorros_cuotas', 'uq_ahorros_reunion_socio no existe (migracion unico_ahorros_cuotas: puede haber duplicados, ver su mensaje)');
        }
    } catch (Exception $e) {
        nt_v('FALLO', 'Esquema', 'no se pudo consultar information_schema (' . get_class($e) . ')');
    }

    // max_allowed_packet
    try {
        $mp = (int)$db->query('SELECT @@max_allowed_packet')->fetchColumn();
        $mb = round($mp / 1048576, 1);
        if ($mp >= 16 * 1048576) nt_v('OK', 'max_allowed_packet', $mb . ' MB');
        else nt_v('ADVERTENCIA', 'max_allowed_packet', $mb . ' MB (se recomiendan 16 MB o mas: firmas, fotos y respaldos del historico)');
    } catch (Exception $e) {
        nt_v('ADVERTENCIA', 'max_allowed_packet', 'no se pudo leer');
    }
}

// ---------------------------------------------------------------- .env
$claves = array(
    'DB_HOST' => true, 'DB_DATABASE' => true, 'DB_USERNAME' => true, 'DB_PASSWORD' => false,
    'DB_PORT' => false, 'DB_CHARSET' => false, 'DB_PREFIX' => false,
    'APP_DEBUG' => false, 'NT_NO_AUTOMIGRATE' => false, 'APP_URL' => false,
);
$faltan = array();
$opc = array();
foreach ($claves as $k => $obligatoria) {
    $v = getenv($k);
    if ($v === false || ($v === '' && $k !== 'DB_PASSWORD')) {
        if ($obligatoria) $faltan[] = $k; else $opc[] = $k;
    }
}
if ($faltan) nt_v('FALLO', 'Claves del .env', 'sin definir: ' . implode(', ', $faltan));
else nt_v('OK', 'Claves del .env', 'DB_HOST, DB_DATABASE y DB_USERNAME definidas');
if ($opc) nt_v('ADVERTENCIA', 'Claves opcionales', 'sin definir (se usan valores por defecto): ' . implode(', ', $opc));
if (Database::appDebug()) nt_v('ADVERTENCIA', 'APP_DEBUG', 'activo; en produccion debe ser 0');
else nt_v('OK', 'APP_DEBUG', 'apagado');
$nm = getenv('NT_NO_AUTOMIGRATE');
if ($nm === '1' || (getenv('NT_AUTOMIGRATE') !== '1' && $nm !== '0')) nt_v('OK', 'Migraciones automaticas', 'desactivadas (se migra con scripts/migrate.php)');
else nt_v('ADVERTENCIA', 'Migraciones automaticas', 'activas; en produccion ponga NT_NO_AUTOMIGRATE=1');

// ---------------------------------------------------------------- VAPID
require_once $raiz . '/core/WebPush.php';
$ll = WebPush::llaves();
if ($ll['public'] !== '' && $ll['private'] !== '') {
    if ($ll['origen'] === 'entorno') {
        nt_v('OK', 'VAPID', 'configurado desde el entorno / .env');
    } else {
        nt_v('ADVERTENCIA', 'VAPID', 'se usan las llaves de config/vapid_keys.php (comprometidas en git): genere nuevas y pongalas en .env');
    }
    if (strpos($ll['subject'], 'mailto:') !== 0 && strpos($ll['subject'], 'https://') !== 0) {
        nt_v('ADVERTENCIA', 'VAPID_SUBJECT', 'debe empezar por mailto: o https://');
    }
} else {
    nt_v('ADVERTENCIA', 'VAPID', 'sin llaves: las notificaciones push no se enviaran (php scripts/generate_vapid_keys.php)');
}

// ---------------------------------------------------------------- directorios escribibles
foreach (array('public/uploads', 'public/uploads/firmas', 'public/uploads/evidencias', 'storage', 'logs') as $rel) {
    $ruta = $raiz . '/' . $rel;
    if (is_dir($ruta)) {
        if (is_writable($ruta)) nt_v('OK', 'Escritura ' . $rel, 'escribible');
        else nt_v('FALLO', 'Escritura ' . $rel, 'sin permiso de escritura para el usuario del servidor web');
    } else {
        $padre = dirname($ruta);
        if (is_dir($padre) && is_writable($padre)) {
            nt_v('ADVERTENCIA', 'Escritura ' . $rel, 'no existe; la aplicacion intentara crearla (el padre es escribible)');
        } else {
            nt_v('FALLO', 'Escritura ' . $rel, 'no existe y no se puede crear');
        }
    }
}
if (is_dir($raiz . '/public/uploads') && !is_file($raiz . '/public/uploads/.htaccess')) {
    nt_v('ADVERTENCIA', 'public/uploads', 'se sirve tal cual; solo debe contener imagenes (sin ejecutar PHP)');
}

// ---------------------------------------------------------------- DocumentRoot (por HTTP)
$url = getenv('APP_URL');
if ($url === false || trim($url) === '') {
    nt_v('ADVERTENCIA', 'DocumentRoot', 'APP_URL vacio: no se pudo probar /.env y /db.sql por HTTP');
} else {
    $url = rtrim(trim($url), '/');
    foreach (array('/.env', '/db.sql', '/scripts/migrate.php', '/config/database.php') as $ruta) {
        $codigo = nt_http_codigo($url . $ruta);
        if ($codigo === null) {
            nt_v('ADVERTENCIA', 'HTTP ' . $ruta, 'no se pudo consultar ' . preg_replace('#^(https?://[^/]+).*$#', '$1', $url) . ' (sin red o sin dominio)');
        } elseif ($codigo === 403 || $codigo === 404) {
            nt_v('OK', 'HTTP ' . $ruta, 'responde ' . $codigo);
        } else {
            nt_v('FALLO', 'HTTP ' . $ruta, 'responde ' . $codigo . ' (debe ser 403 o 404: revise DocumentRoot y .htaccess)');
        }
    }
}

/** Codigo HTTP de una URL con timeout corto, o null si no hay respuesta. No lee el cuerpo completo. */
function nt_http_codigo($u) {
    if (function_exists('curl_init')) {
        $ch = curl_init($u);
        curl_setopt_array($ch, array(
            CURLOPT_NOBODY => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_RANGE => '0-0',
            CURLOPT_SSL_VERIFYPEER => true,
        ));
        curl_exec($ch);
        $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($c === 206) $c = 200;
        return $c > 0 ? $c : null;
    }
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(array('http' => array('timeout' => 6, 'ignore_errors' => true, 'follow_location' => 0)));
        $r = @file_get_contents($u, false, $ctx, 0, 1);
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $mm)) {
            return (int)$mm[1];
        }
        return $r === false ? null : 200;
    }
    return null;
}

// ---------------------------------------------------------------- git
$salida = array();
$cod = 1;
if (is_dir($raiz . '/.git')) {
    @exec('git -C ' . escapeshellarg($raiz) . ' ls-files config/vapid_keys.php 2>&1', $salida, $cod);
    if ($cod === 0) {
        if (trim(implode('', $salida)) !== '') {
            nt_v('ADVERTENCIA', 'config/vapid_keys.php en git', 'sigue rastreado: git rm --cached config/vapid_keys.php (y rote las llaves)');
        } else {
            nt_v('OK', 'config/vapid_keys.php en git', 'no esta rastreado');
        }
    } else {
        nt_v('ADVERTENCIA', 'config/vapid_keys.php en git', 'omitido: git no disponible');
    }
} else {
    nt_v('OK', 'config/vapid_keys.php en git', 'no hay carpeta .git (copia sin historial)');
}

// ---------------------------------------------------------------- tabla
$w = 0;
foreach ($filas as $f) $w = max($w, strlen($f[1]));
$cont = array('OK' => 0, 'ADVERTENCIA' => 0, 'FALLO' => 0);
echo "\nVerificacion de despliegue - InfoNatillera (solo lectura)\n";
echo str_repeat('-', 78) . "\n";
foreach ($filas as $f) {
    $cont[$f[0]]++;
    printf("%-11s  %-" . $w . "s  %s\n", $f[0], $f[1], $f[2]);
}
echo str_repeat('-', 78) . "\n";
echo "OK: {$cont['OK']}   ADVERTENCIA: {$cont['ADVERTENCIA']}   FALLO: {$cont['FALLO']}\n";
exit($cont['FALLO'] > 0 ? 1 : 0);
