<?php
// scripts/enviar_recordatorios.php
// Recordatorios automaticos del dia de reunion (8:00, 12:00 y 13:00, zona horaria de config/natillera.php).
//   08:00  a TODOS los socios activos: "hoy hay reunion".
//   12:00  y 13:00 solo a quienes AUN NO han pagado la cuota de esa reunion.
// Sin duplicados: natillera_recordatorios_enviados (reunion, socio, franja) es unica.
//
// Uso:  php scripts/enviar_recordatorios.php [--dry-run] [--ahora="YYYY-MM-DD HH:MM"] [--sin-push]
//   --dry-run   no escribe nada ni envia; solo muestra a quien se le enviaria
//   --ahora     simula la fecha y hora (zona horaria de la natillera)
//   --sin-push  registra los avisos (campanita) pero no llama a ningun servicio push
// Programar con cron o el Programador de tareas (ver docs/06_notificaciones_push.md).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Solo por consola.\n");
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/WebPush.php';
require_once __DIR__ . '/../app/Models/PushSubscription.php';
require_once __DIR__ . '/../app/Models/Recordatorio.php';

$cfg = require __DIR__ . '/../config/natillera.php';
$zona = isset($cfg['zona_horaria']) ? $cfg['zona_horaria'] : 'America/Bogota';
date_default_timezone_set($zona);

$dry = false;
$sinPush = false;
$ahoraTxt = null;
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--dry-run') {
        $dry = true;
    } elseif ($a === '--sin-push') {
        $sinPush = true;
    } elseif (strpos($a, '--ahora=') === 0) {
        $ahoraTxt = trim(substr($a, 8), " \"'");
    } else {
        fwrite(STDERR, "Opcion desconocida: $a\n");
        exit(2);
    }
}

try {
    $ahora = $ahoraTxt !== null ? new DateTime($ahoraTxt, new DateTimeZone($zona)) : new DateTime('now', new DateTimeZone($zona));
} catch (Exception $e) {
    fwrite(STDERR, "Valor de --ahora invalido (use \"YYYY-MM-DD HH:MM\").\n");
    exit(2);
}

$hora = (int)$ahora->format('G');
$franjas = array(8 => '08:00', 12 => '12:00', 13 => '13:00');
$salida = function ($t) { echo '[' . date('Y-m-d H:i:s') . '] ' . $t . "\n"; };

$salida('Ahora: ' . $ahora->format('Y-m-d H:i') . " ($zona)" . ($dry ? ' [dry-run]' : '') . ($sinPush ? ' [sin-push]' : ''));
if (!isset($franjas[$hora])) {
    $salida('Esta hora (' . $hora . ':xx) no es de recordatorio (8, 12 o 13). Nada que hacer.');
    exit(0);
}
$franja = $franjas[$hora];
$soloPendientes = ($franja !== '08:00');

$rec = new Recordatorio();
if (!$rec->tablaExiste()) {
    fwrite(STDERR, "Falta la tabla natillera_recordatorios_enviados: aplique las migraciones (php scripts/migrate.php).\n");
    exit(1);
}

$reuniones = $rec->reunionesDelDia($ahora->format('Y-m-d'));
if (!$reuniones) {
    $salida('No hay reunion PROGRAMADA o EN_PROCESO para hoy. Nada que hacer.');
    exit(0);
}

$push = new PushSubscription();
$remitente = $rec->remitenteSistema();
if (!$dry && $remitente <= 0) {
    fwrite(STDERR, "No hay un usuario Presidente para firmar los avisos.\n");
    exit(1);
}

$pesos = function ($v) { return '$' . number_format((float)$v, 0, ',', '.'); };
$totalAvisos = 0;
$sociosAvisados = array();

foreach ($reuniones as $r) {
    $horaTxt = date('g:i A', strtotime($r['hora_reunion'] ?: '14:00:00'));
    $cuota = $pesos($r['valor_cuota_base']);
    if ($franja === '08:00') {
        $titulo = 'Hoy hay reunión de la natillera';
        $mensaje = 'Hoy a las ' . $horaTxt . ' es la reunión de la quincena ' . $r['numero_quincena'] . '. Cuota: ' . $cuota . '. Te esperamos.';
    } elseif ($franja === '12:00') {
        $titulo = 'Recuerda tu cuota de hoy';
        $mensaje = 'Aún no aparece pagada tu cuota de la reunión de hoy a las ' . $horaTxt . ' (' . $cuota . '). Puedes dejarla lista antes de la reunión.';
    } else {
        $titulo = 'Último recordatorio: tu cuota de hoy';
        $mensaje = 'Tu cuota de la reunión de hoy a las ' . $horaTxt . ' (' . $cuota . ') sigue sin registrarse. Acércate para ponerte al día.';
    }

    $dest = $rec->destinatarios((int)$r['id'], $franja, $soloPendientes);
    $salida('Reunion #' . $r['id'] . ' (quincena ' . $r['numero_quincena'] . ', ' . $r['estado'] . ', ' . $horaTxt . '), franja ' . $franja
        . ($soloPendientes ? ' (solo sin cuota)' : ' (todos)') . ': ' . count($dest) . ' socio(s)');

    foreach ($dest as $s) {
        if ($dry) {
            $salida('  [dry-run] ' . $s['nombre_completo'] . ' <- "' . $titulo . '"');
            continue;
        }
        // Se reserva primero (UNIQUE): si otro proceso ya lo envio, se omite.
        if (!$rec->marcarEnviado((int)$r['id'], (int)$s['id'], $franja, null)) {
            continue;
        }
        $nid = $push->registrarNotificacionId($titulo, $mensaje, 'SOCIO_ESPECIFICO', (int)$s['id'], $remitente);
        if ($nid > 0) {
            $rec->actualizarNotificacion((int)$r['id'], (int)$s['id'], $franja, $nid);
        }
        $totalAvisos++;
        $sociosAvisados[(int)$s['id']] = $nid;
    }
}

if ($dry) {
    $salida('Dry-run: no se escribio ni envio nada.');
    exit(0);
}

$salida('Avisos registrados: ' . $totalAvisos);

if ($sinPush || !$sociosAvisados) {
    exit(0);
}

$wp = WebPush::desdeConfiguracion();
if (!$wp->configurado()) {
    $salida('AVISO: llaves VAPID o extensiones openssl/curl no disponibles; no se envio push (los avisos quedan en la campanita).');
    exit(0);
}

$subs = array();
foreach ($push->getSuscripciones() as $sub) {
    if (isset($sociosAvisados[(int)$sub['socio_id']])) {
        $subs[] = $sub;
    }
}
$salida('Suscripciones push a notificar: ' . count($subs));
$res = $wp->enviarVarios($subs, 3 * 3600);
$ok = 0;
$caducadas = 0;
foreach ($subs as $i => $sub) {
    $x = $res[$i];
    $push->registrarEnvio($sociosAvisados[(int)$sub['socio_id']] ?: null, (int)$sub['id'], (int)$sub['socio_id'], (int)$x['status'], (bool)$x['ok'], $x['detalle']);
    if ($x['ok']) {
        $ok++;
    }
    if ($x['caducada']) {
        $push->eliminarSuscripcion((int)$sub['id']);
        $caducadas++;
    }
}
$salida("Push enviados: $ok de " . count($subs) . "; suscripciones caducadas eliminadas: $caducadas");
exit(0);
