<?php
// tests/webpush_test.php - Pruebas de core/WebPush.php (sin BD y sin servicios push reales).
// Uso: php tests/webpush_test.php
// Genera un par de llaves de prueba, firma un JWT y lo verifica con openssl_verify; levanta un
// servidor php -S local que registra las peticiones y responde 201/404/410/500.

require_once __DIR__ . '/../core/WebPush.php';

$fallos = 0;
function ok($cond, $nombre) {
    global $fallos;
    echo ($cond ? '[OK]    ' : '[FALLO] ') . $nombre . "\n";
    if (!$cond) { $fallos++; }
}

// --- llaves FIJAS de prueba (par desechable; no son las del proyecto; no dependen de openssl.cnf) ---
$pub = 'BCuochTeFlh3r-76WaKZ2L6HRg9_QBwiL2El0ik5UoxxCE88XXhR1b6BwwcgCUx0u8OrVHDybiE4XoMbRvJc0Zs';
$priv = 'nnAbSO0FsMHDy6wfXShI1ntOZpg4Cmwza-_AaSOjhu8';
$llaves = array('public' => $pub, 'private' => $priv, 'subject' => 'mailto:prueba@example.com');

// --- base64url ---
ok(WebPush::base64UrlEncode("\xfb\xff\xfe") === '-__-', 'base64url usa - y _');
ok(strpos(WebPush::base64UrlEncode('a'), '=') === false, 'base64url sin relleno');
ok(WebPush::base64UrlDecode(WebPush::base64UrlEncode("\x00\x01\xfa\xfb")) === "\x00\x01\xfa\xfb", 'base64url ida y vuelta');
ok(strlen(WebPush::base64UrlDecode($pub)) === 65, 'llave publica de 65 bytes');
ok(strpos($pub . $priv, '=') === false, 'llaves sin relleno');

// --- DER -> raw ---
$r = str_repeat("\x01", 31) . "\x02";
$s = "\x80" . str_repeat("\x03", 31);  // bit alto: DER agrega 0x00
$der = "\x30" . chr(2 + 32 + 2 + 33) . "\x02\x20" . $r . "\x02\x21\x00" . $s;
ok(WebPush::derARaw($der) === $r . $s, 'DER->raw con INTEGER de 33 bytes (cero inicial)');
$s0 = str_repeat("\x7f", 32);
$der2 = "\x30" . chr(2 + 1 + 2 + 32) . "\x02\x01\x05" . "\x02\x20" . $s0;
ok(WebPush::derARaw($der2) === str_repeat("\x00", 31) . "\x05" . $s0, 'DER->raw rellena r corto a 32 bytes');
ok(WebPush::rawADer($r . $s) === $der, 'raw->DER es el inverso');

// --- JWT ---
$wp = new WebPush($llaves, array('allow_insecure' => true));
$jwt = $wp->crearJwt('https://fcm.googleapis.com', time() + 3600);
$partes = explode('.', $jwt);
ok(count($partes) === 3, 'JWT con 3 partes');
ok(strpos($jwt, '=') === false && strpos($jwt, '+') === false && strpos($jwt, '/') === false, 'JWT es base64url sin relleno');
ok(json_decode(WebPush::base64UrlDecode($partes[0]), true) === array('typ' => 'JWT', 'alg' => 'ES256'), 'cabecera JWT ES256');
$claims = json_decode(WebPush::base64UrlDecode($partes[1]), true);
ok($claims['aud'] === 'https://fcm.googleapis.com' && $claims['sub'] === 'mailto:prueba@example.com' && $claims['exp'] > time(), 'claims aud/sub/exp');
ok(strlen(WebPush::base64UrlDecode($partes[2])) === 64, 'firma raw de 64 bytes');
$okVerif = openssl_verify($partes[0] . '.' . $partes[1], WebPush::rawADer(WebPush::base64UrlDecode($partes[2])), WebPush::pemPublico($pub), OPENSSL_ALGO_SHA256);
ok($okVerif === 1, 'openssl_verify valida el JWT con la clave publica');
ok(WebPush::verificarJwt($jwt, $pub) !== null, 'verificarJwt acepta el JWT');
ok(WebPush::verificarJwt($partes[0] . '.' . $partes[1] . 'x.' . $partes[2], $pub) === null, 'JWT alterado es rechazado');
$todasBien = true;
for ($i = 0; $i < 30; $i++) {
    $j = $wp->crearJwt('https://x.example');
    $p3 = explode('.', $j);
    if (strlen(WebPush::base64UrlDecode($p3[2])) !== 64 || WebPush::verificarJwt($j, $pub) === null) { $todasBien = false; }
}
ok($todasBien, '30 firmas consecutivas validas y de 64 bytes');

// --- endpoints permitidos ---
$prod = new WebPush($llaves);
ok($prod->endpointPermitido('https://fcm.googleapis.com/fcm/send/abc'), 'permite FCM');
ok($prod->endpointPermitido('https://updates.push.services.mozilla.com/wpush/v2/abc'), 'permite Mozilla');
ok($prod->endpointPermitido('https://web.push.apple.com/abc'), 'permite Apple');
ok(!$prod->endpointPermitido('http://fcm.googleapis.com/x'), 'rechaza http');
ok(!$prod->endpointPermitido('https://127.0.0.1/x') && !$prod->endpointPermitido('https://evil.example/x') && !$prod->endpointPermitido('https://fcm.googleapis.com.evil.com/x'), 'rechaza hosts ajenos (SSRF)');

// --- servidor falso ---
$puerto = 8115;
$tmp = sys_get_temp_dir();
$log = $tmp . DIRECTORY_SEPARATOR . 'webpush_test_' . getmypid() . '.log';
@unlink($log);
putenv('WEBPUSH_LOG=' . $log);
$q = function ($p) { return strpos($p, " ") === false ? $p : "\"" . $p . "\""; };
$cmd = $q(PHP_BINARY) . " -S 127.0.0.1:" . $puerto . " " . $q(__DIR__ . "/webpush_servidor_falso.php");
$pipes = array();
$proc = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('file', $tmp . '/webpush_srv_out.txt', 'w'), 2 => array('file', $tmp . '/webpush_srv_err.txt', 'w')), $pipes);
$listo = false;
for ($i = 0; $i < 50; $i++) {
    $f = @fsockopen('127.0.0.1', $puerto, $en, $es, 0.2);
    if ($f) { fclose($f); $listo = true; break; }
    usleep(100000);
}
ok($listo, 'servidor falso levantado en 127.0.0.1:' . $puerto);
if ($listo) {
    $base = 'http://127.0.0.1:' . $puerto;
    $r = $wp->enviarVarios(array(
        array('endpoint' => $base . '/ok/abc'),
        array('endpoint' => $base . '/gone/abc'),
        array('endpoint' => $base . '/missing/abc'),
        array('endpoint' => $base . '/err/abc'),
        array('endpoint' => 'http://127.0.0.1:1/cerrado'),
    ), 600);
    ok($r[0]['ok'] && $r[0]['status'] === 201 && !$r[0]['caducada'], '201 = enviado');
    ok(!$r[1]['ok'] && $r[1]['status'] === 410 && $r[1]['caducada'], '410 = suscripcion caducada');
    ok(!$r[2]['ok'] && $r[2]['status'] === 404 && $r[2]['caducada'], '404 = suscripcion caducada');
    ok(!$r[3]['ok'] && $r[3]['status'] === 500 && !$r[3]['caducada'], '500 = fallo temporal, NO caduca');
    ok(!$r[4]['ok'] && $r[4]['status'] === 0 && !$r[4]['caducada'], 'red caida = fallo temporal');

    $reqs = array();
    foreach (array_filter(explode("\n", (string)@file_get_contents($log))) as $l) { $reqs[] = json_decode($l, true); }
    ok(count($reqs) === 4, 'el servidor recibio 4 peticiones (la 5a no conecta)');
    $q = $reqs[0];
    $h = array_change_key_case($q['cabeceras'], CASE_LOWER);
    $m = array();
    ok($q['metodo'] === 'POST', 'metodo POST');
    ok($q['cuerpo'] === '', 'cuerpo vacio (push sin carga util)');
    ok(isset($h['ttl']) && $h['ttl'] === '600', 'cabecera TTL');
    ok(isset($h['authorization']) && preg_match('/^vapid t=([\w-]+\.[\w-]+\.[\w-]+), k=([\w-]+)$/', $h['authorization'], $m) === 1, 'Authorization: vapid t=<jwt>, k=<clave>');
    ok(isset($m[2]) && $m[2] === $pub, 'k es la clave publica en base64url');
    $cl = isset($m[1]) ? WebPush::verificarJwt($m[1], $pub) : null;
    ok($cl !== null && $cl['aud'] === $base, 'el JWT recibido verifica y su aud es el origen del endpoint');
}
if (is_resource($proc)) {
    $st = proc_get_status($proc);
    if (stripos(PHP_OS, 'WIN') === 0) {
        // En Windows proc_open lanza cmd.exe: hay que matar el arbol para no dejar el servidor vivo.
        exec('taskkill /F /T /PID ' . (int)$st['pid'] . ' 2>&1');
    } else {
        proc_terminate($proc);
    }
    proc_close($proc);
}
@unlink($log);

echo $fallos === 0 ? "\nTODO OK\n" : "\n$fallos prueba(s) fallaron\n";
exit($fallos === 0 ? 0 : 1);
