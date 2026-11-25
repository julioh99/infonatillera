<?php
// scripts/nuevo_anio_socios.php
//
// Cambia al ano nuevo con la lista de socios que continuan y los nuevos, en el orden dado.
// Usa EXACTAMENTE el mismo camino que la pantalla "Historico de Anos" (Historico::crearAnioNuevo):
// guarda el ano cargado, hace un RESPALDO_AUTOMATICO y empieza en blanco. Luego genera las 26 reuniones
// con la distribucion separada de cuotas extra de ronda y rifa (Reunion::generarCiclo, sugerir_extras).
//
// Por defecto es un ENSAYO EN SECO: no cambia nada, solo muestra que haria.
//
// Uso:
//   php scripts/nuevo_anio_socios.php --lista=socios.tsv --nombre="Dic 2026 - Nov 2027" --primera-reunion=2026-12-14
//   (agrega --aplicar --confirmo-base=NOMBRE_DE_LA_BASE --claves-salida=claves.csv para ejecutar de verdad)
//
// Opciones:
//   --lista=ARCHIVO         Una linea por socio, en el orden nuevo:  Nombre completo<TAB>id_existente
//                           (id vacio = socio nuevo). Los existentes que no aparezcan quedan inactivos.
//   --nombre=TEXTO          Nombre del ano nuevo (obligatorio).
//   --primera-reunion=FECHA Fecha AAAA-MM-DD de la primera reunion (obligatoria; se generan 26 cada 14 dias).
//   --usuario=ID            Quien opera (por defecto el Presidente activo). Siempre continua.
//   --pasar-prestamos       Los prestamos activos pasan como saldo inicial (por defecto NO).
//   --aplicar               Ejecuta de verdad (sin esto solo ensaya).
//   --confirmo-base=NOMBRE  Con --aplicar: debe ser igual al nombre de la base a la que se conecta (seguro anti-error).
//   --claves-salida=ARCHIVO Con --aplicar: CSV donde se guardan las claves temporales de los socios nuevos.
//
// La conexion sale de .env (DB_*), pero las variables de entorno mandan: para ensayar en una copia local
//   DB_HOST=127.0.0.1 DB_DATABASE=nt_ensayo DB_USERNAME=root DB_PASSWORD= php scripts/nuevo_anio_socios.php ...

if (PHP_SAPI !== 'cli') {
    exit("Solo por linea de comandos.\n");
}

putenv('NT_NO_AUTOMIGRATE=1');
require_once __DIR__ . '/../config/database.php';
Database::$autoMigrar = false;
Database::loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/Historico.php';
require_once __DIR__ . '/../app/Models/Reunion.php';

// ------------------------------------------------------------------ utilidades
function ns_salir($msg, $code = 1) {
    fwrite(STDERR, $msg . "\n");
    exit($code);
}

function ns_norm($s) {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'));
    $s = preg_replace('/[^a-z0-9 ]+/', '', $s);
    return preg_replace('/\s+/', ' ', $s);
}

function ns_clave() {
    $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $c = '';
    for ($i = 0; $i < 8; $i++) {
        $c .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $c;
}

$opt = getopt('', array('lista:', 'nombre:', 'primera-reunion:', 'usuario:', 'pasar-prestamos', 'aplicar', 'confirmo-base:', 'claves-salida:'));
foreach (array('lista', 'nombre', 'primera-reunion') as $req) {
    if (empty($opt[$req])) {
        ns_salir("Falta --{$req}. Mira el encabezado del script para ver el uso.");
    }
}
$aplicar = isset($opt['aplicar']);
$pasar = isset($opt['pasar-prestamos']);
$nombreAnio = trim($opt['nombre']);
$primera = $opt['primera-reunion'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $primera) || !checkdate((int)substr($primera, 5, 2), (int)substr($primera, 8, 2), (int)substr($primera, 0, 4))) {
    ns_salir('--primera-reunion no es una fecha valida (AAAA-MM-DD).');
}

// ------------------------------------------------------------------ lista
if (!is_readable($opt['lista'])) {
    ns_salir('No se puede leer el archivo de la lista: ' . $opt['lista']);
}
$filas = array();
foreach (preg_split('/\r\n|\n|\r/', file_get_contents($opt['lista'])) as $linea) {
    if (trim($linea) === '' || $linea[0] === '#') {
        continue;
    }
    $p = explode("\t", $linea);
    $nombre = trim($p[0]);
    $id = isset($p[1]) ? trim($p[1]) : '';
    if ($nombre === '') {
        ns_salir('Hay una linea sin nombre en la lista.');
    }
    if ($id !== '' && !ctype_digit($id)) {
        ns_salir("El id '{$id}' de '{$nombre}' no es un numero.");
    }
    $filas[] = array('nombre' => $nombre, 'id' => $id === '' ? null : (int)$id);
}
if (!$filas) {
    ns_salir('La lista esta vacia.');
}

$db = Database::getConnection();
$baseActual = $db->query('SELECT DATABASE()')->fetchColumn();
$hostActual = getenv('DB_HOST') ?: '(.env)';

$existentes = array();
foreach ($db->query('SELECT id, cedula, nombre_completo, estado, orden, rol_id FROM natillera_usuarios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) as $u) {
    $existentes[(int)$u['id']] = $u;
}

// Validaciones
$vistos = array();
$nombresVistos = array();
foreach ($filas as $f) {
    if ($f['id'] !== null) {
        if (!isset($existentes[$f['id']])) {
            ns_salir("El id {$f['id']} ('{$f['nombre']}') no existe en natillera_usuarios.");
        }
        if (isset($vistos[$f['id']])) {
            ns_salir("El id {$f['id']} aparece dos veces en la lista.");
        }
        $vistos[$f['id']] = true;
    }
    $n = ns_norm($f['nombre']);
    if (isset($nombresVistos[$n])) {
        ns_salir("El nombre '{$f['nombre']}' esta repetido en la lista.");
    }
    $nombresVistos[$n] = true;
}
$operador = isset($opt['usuario']) ? (int)$opt['usuario'] : 0;
if ($operador === 0) {
    $operador = (int)$db->query("SELECT u.id FROM natillera_usuarios u JOIN natillera_roles r ON r.id = u.rol_id WHERE r.nombre = 'Presidente' AND u.estado = 1 ORDER BY u.id LIMIT 1")->fetchColumn();
}
if (!isset($existentes[$operador]) || !isset($vistos[$operador])) {
    ns_salir("El usuario que opera (id {$operador}) debe existir y estar en la lista.");
}

// ------------------------------------------------------------------ plan
echo "Base: {$baseActual}  (host: {$hostActual})\n";
echo 'Modo: ' . ($aplicar ? 'APLICAR (cambia datos)' : 'ENSAYO EN SECO (no cambia nada)') . "\n";
echo "Ano nuevo: \"{$nombreAnio}\"  ·  primera reunion: {$primera}  ·  opera: id {$operador}\n\n";

$renombres = array();
$nuevos = array();
printf("%-3s %-28s %-8s %-30s %s\n", '#', 'NOMBRE NUEVO', 'ID', 'NOMBRE ACTUAL', 'ACCION');
foreach ($filas as $i => $f) {
    $accion = 'NUEVO (cedula nueva y clave temporal)';
    $actual = '';
    $idTxt = '-';
    if ($f['id'] !== null) {
        $u = $existentes[$f['id']];
        $actual = $u['nombre_completo'];
        $idTxt = (string)$f['id'];
        if (ns_norm($actual) === ns_norm($f['nombre'])) {
            $accion = 'igual (se conserva)';
        } else {
            $accion = 'RENOMBRAR';
            $renombres[$f['id']] = array('antes' => $actual, 'despues' => $f['nombre']);
        }
        if ((int)$u['estado'] !== 1) {
            $accion .= ' + REACTIVAR';
        }
    } else {
        $nuevos[] = $i;
    }
    printf("%-3d %-28s %-8s %-30s %s\n", $i + 1, $f['nombre'], $idTxt, $actual, $accion);
}
$salen = array();
foreach ($existentes as $id => $u) {
    if (!isset($vistos[$id]) && (int)$u['estado'] === 1) {
        $salen[] = $u;
    }
}
echo "\nContinuan: " . count($vistos) . '  ·  Nuevos: ' . count($nuevos) . '  ·  Dejan de estar activos: ' . count($salen) . "\n";
foreach ($salen as $u) {
    echo "  - sale: id {$u['id']}  {$u['nombre_completo']}  (cedula {$u['cedula']}; no se borra, solo queda inactivo)\n";
}

$historico = new Historico();
$prestamos = $historico->prestamosActivos();
$saldo = 0;
foreach ($prestamos as $p) {
    $saldo += $p['saldo_capital'];
}
echo "\nPrestamos activos hoy: " . count($prestamos) . ' (capital pendiente ' . number_format($saldo, 0, ',', '.') . ').  ' . ($pasar ? 'PASAN al ano nuevo como saldo inicial.' : 'NO pasan: quedan en el ano que se guarda.') . "\n";
$pend = $db->query("SELECT COUNT(*) FROM natillera_reuniones WHERE estado <> 'CERRADA'")->fetchColumn();
echo "Reuniones sin cerrar en el ano actual: {$pend}\n";
echo 'Ano cargado ahora: ' . (($ac = $historico->getActual()) ? $ac['nombre'] : '(ninguno: se creara al guardar)') . "\n";

if (!$aplicar) {
    echo "\nEnsayo terminado. No se cambio nada. Para ejecutar: agrega --aplicar --confirmo-base={$baseActual} --claves-salida=ARCHIVO.csv\n";
    exit(0);
}

// ------------------------------------------------------------------ aplicar
if (!isset($opt['confirmo-base']) || $opt['confirmo-base'] !== $baseActual) {
    ns_salir("Con --aplicar debes escribir --confirmo-base={$baseActual} (el nombre exacto de la base a la que estas conectado).");
}
if (empty($opt['claves-salida'])) {
    ns_salir('Con --aplicar indica --claves-salida=ARCHIVO.csv (aqui se guardan las claves temporales de los socios nuevos).');
}
if (file_exists($opt['claves-salida'])) {
    ns_salir('El archivo de claves ya existe; elige otro nombre para no pisarlo.');
}

$usuarioModel = new Usuario();
$rolSocio = (int)$db->query("SELECT id FROM natillera_roles WHERE nombre = 'Socio' LIMIT 1")->fetchColumn();
if (!$rolSocio) {
    ns_salir("No encontre el rol 'Socio'.");
}

$creados = array();   // id => clave
$renombrados = array();
$reactivados = array();
try {
    // 1) Renombrar los que continuan con otra ortografia
    $updNombre = $db->prepare('UPDATE natillera_usuarios SET nombre_completo = :n WHERE id = :id');
    foreach ($renombres as $id => $r) {
        $updNombre->execute(array(':n' => $r['despues'], ':id' => $id));
        $renombrados[$id] = $r['antes'];
    }

    // 2) Crear los socios nuevos (cedula con el mismo esquema 1040 + id) y guardar su clave temporal
    $ids = array();
    foreach ($filas as $f) {
        if ($f['id'] !== null) {
            $ids[] = $f['id'];
            continue;
        }
        $clave = ns_clave();
        $tmp = 'NT' . substr(bin2hex(random_bytes(6)), 0, 12);
        $usuarioModel->crearSocio(array('cedula' => $tmp, 'nombre_completo' => $f['nombre'], 'rol_id' => $rolSocio, 'password' => $clave));
        $nuevoId = (int)$db->query("SELECT id FROM natillera_usuarios WHERE cedula = " . $db->quote($tmp))->fetchColumn();
        $cedula = '1040' . str_pad((string)$nuevoId, 2, '0', STR_PAD_LEFT);
        $db->prepare('UPDATE natillera_usuarios SET cedula = :c, password_changed = 0 WHERE id = :id')->execute(array(':c' => $cedula, ':id' => $nuevoId));
        $creados[$nuevoId] = array('nombre' => $f['nombre'], 'cedula' => $cedula, 'clave' => $clave);
        $ids[] = $nuevoId;
    }

    // 3) Crear el ano nuevo (guarda el actual, respaldo automatico, vacia, deja activos solo estos, en este orden)
    $r = $historico->crearAnioNuevo(array(
        'nombre' => $nombreAnio,
        'fecha_inicio' => $primera,
        'orden' => $ids,
        'continuan' => $ids,
        'pasar_prestamos' => $pasar,
    ), $operador);
    echo "\nAno creado: \"{$r['nombre']}\" con {$r['socios']} socios. Respaldo automatico id {$r['respaldo_id']}.\n";
} catch (Throwable $e) {
    // No quedo ano nuevo: se deshace lo que se hizo antes (socios nuevos y nombres)
    foreach (array_keys($creados) as $id) {
        $db->prepare('DELETE FROM natillera_usuarios WHERE id = :id')->execute(array(':id' => $id));
    }
    foreach ($renombrados as $id => $antes) {
        $updNombre->execute(array(':n' => $antes, ':id' => $id));
    }
    ns_salir('NO SE CREO EL ANO. Se deshicieron los nombres y socios nuevos. Detalle: ' . $e->getMessage());
}

// 4) Reuniones del ano nuevo con la distribucion separada de cuotas extra
try {
    $rm = new Reunion();
    $ciclo = $rm->generarCiclo($primera, null, array('sugerir_extras' => true));
    $historico->guardarActual(null, $operador); // la foto del ano nuevo incluye sus reuniones
    echo "Reuniones generadas: {$ciclo['creadas']} (omitidas por fecha repetida: {$ciclo['omitidas']}).\n";
} catch (Throwable $e) {
    echo 'ATENCION: el ano se creo pero no se pudieron generar las reuniones: ' . $e->getMessage() . "\nCrealas desde Reuniones.\n";
}

// 5) Claves temporales de los socios nuevos
$fh = fopen($opt['claves-salida'], 'w');
fputcsv($fh, array('nombre', 'cedula_usuario', 'clave_temporal'));
foreach ($creados as $c) {
    fputcsv($fh, array($c['nombre'], $c['cedula'], $c['clave']));
}
fclose($fh);
@chmod($opt['claves-salida'], 0600);
echo 'Claves temporales de ' . count($creados) . ' socios nuevos guardadas en: ' . $opt['claves-salida'] . "\n";
echo "Listo.\n";
