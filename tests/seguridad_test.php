<?php
// tests/seguridad_test.php
// Pruebas del modulo de seguridad nucleo (CSRF, login, roles, liquidacion, errores).
// Solo corre contra la base local nt_seguridad. Uso:
//   DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nt_seguridad DB_USERNAME=root DB_PASSWORD= \
//     /c/laragon/bin/php/php-8.1.10-Win32-vs16-x64/php.exe tests/seguridad_test.php
// Requiere el servidor de pruebas ya levantado en 127.0.0.1:8101 (ver docs/PRUEBAS_LOCALES.md; DB_PASSWORD= vacio debe ir definido).

if (getenv('DB_DATABASE') !== 'nt_seguridad') {
    fwrite(STDERR, "Se niega a correr: DB_DATABASE debe ser nt_seguridad (base local de pruebas).\n");
    exit(2);
}
if ((getenv('DB_HOST') ?: '') !== '127.0.0.1') {
    fwrite(STDERR, "Se niega a correr: DB_HOST debe ser 127.0.0.1.\n");
    exit(2);
}

$raiz = realpath(__DIR__ . '/..');
define('BASE', 'http://127.0.0.1:8101');
$php = PHP_BINARY;

require_once $raiz . '/core/Csrf.php';
require_once $raiz . '/core/Controller.php';
require_once $raiz . '/core/Router.php';
require_once $raiz . '/config/database.php';
require_once $raiz . '/app/Models/LoginIntento.php';
require_once $raiz . '/app/Controllers/SocioController.php';

$pdo = Database::getConnection();
$ok = 0; $fallos = 0;
function t($nombre, $cond, $detalle = '') {
    global $ok, $fallos;
    if ($cond) { $ok++; echo "OK     $nombre\n"; }
    else { $fallos++; echo "FALLO  $nombre" . ($detalle !== '' ? "  -> $detalle" : '') . "\n"; }
}

// ---------- preparacion ----------
$migracion = require $raiz . '/scripts/migrations/20261125_1000_login_intentos.php';
$migracion['up']($pdo);
$migracion['up']($pdo); // idempotente
t('migracion login_intentos es idempotente', true);

$pass = 'Prueba123';
$usuarios = [];   // id => hash original
$tocar = [50, 9, 17]; // Presidente, Tesorera, Secretaria General
$sAuto = (int)$pdo->query("SELECT p.socio_deudor_id FROM natillera_prestamos p WHERE p.es_autoprestamo = 1 AND p.estado = 'ACTIVO' ORDER BY p.id LIMIT 1")->fetchColumn();
$sPlano = (int)$pdo->query("SELECT id FROM natillera_usuarios WHERE rol_id = 5 AND id NOT IN ($sAuto, 1) ORDER BY id LIMIT 1")->fetchColumn();
$tocar[] = $sAuto; $tocar[] = $sPlano;
foreach ($tocar as $id) {
    $usuarios[$id] = $pdo->query("SELECT password_hash, password_changed FROM natillera_usuarios WHERE id = $id")->fetch();
    $st = $pdo->prepare("UPDATE natillera_usuarios SET password_hash = :h, password_changed = 1, estado = 1 WHERE id = :id");
    $st->execute([':h' => password_hash($pass, PASSWORD_DEFAULT), ':id' => $id]);
}
$ced = function ($id) use ($pdo) { return $pdo->query("SELECT cedula FROM natillera_usuarios WHERE id = " . (int)$id)->fetchColumn(); };
$pdo->exec("DELETE FROM natillera_login_intentos");

// ---------- 1. Unitarias ----------
$_SESSION = [];
$tok = Csrf::token();
t('Csrf::token genera 64 hex', (bool)preg_match('/^[0-9a-f]{64}$/', $tok));
t('Csrf::token estable en la sesion', Csrf::token() === $tok);
$_POST = ['csrf_token' => $tok]; t('Csrf::valido con token correcto', Csrf::valido());
$_POST = ['csrf_token' => 'x'];  t('Csrf::valido rechaza token malo', !Csrf::valido());
$_POST = [];                     t('Csrf::valido rechaza ausencia', !Csrf::valido());
$_SERVER['HTTP_X_CSRF_TOKEN'] = $tok; t('Csrf::valido acepta cabecera X-CSRF-Token', Csrf::valido());
unset($_SERVER['HTTP_X_CSRF_TOKEN']);

$html = '<form method="POST" action="/a"><input name="x"></form>'
      . '<form action="/b" method=\'post\' onsubmit="return 1>0"><input></form>'
      . '<form method="GET" action="/c"></form>'
      . '<form method="POST"><input type="hidden" name="csrf_token" value="ya"></form>';
$out = Csrf::inyectar($html);
t('inyectar: 2 formularios POST sin token reciben campo', substr_count($out, 'name="csrf_token"') === 3, 'cuenta=' . substr_count($out, 'name="csrf_token"'));
t('inyectar: no toca GET ni duplica', strpos($out, '<form method="GET" action="/c"></form>') !== false && substr_count($out, 'value="ya"') === 1);

$m = new ReflectionMethod('SocioController', 'generarClaveTemporal');
$m->setAccessible(true);
$sc = new SocioController();
$claves = []; $bien = true;
for ($i = 0; $i < 200; $i++) {
    $c = $m->invoke($sc); $claves[$c] = 1;
    if (!preg_match('/^[A-HJKMNP-Z2-9]{8}$/', $c)) $bien = false;
}
t('clave temporal: 8 caracteres sin ambiguos (0 O 1 I L)', $bien);
t('clave temporal: aleatoria (200 distintas aprox)', count($claves) > 190);

$li = new LoginIntento();
$cx = 'T-' . mt_rand(1000, 9999); $ipx = '10.0.0.9';
for ($i = 0; $i < 4; $i++) $li->registrarFallo($cx, $ipx);
t('LoginIntento: 4 fallos no bloquea', $li->minutosBloqueo($cx, $ipx) === 0);
$li->registrarFallo($cx, $ipx);
$esp = $li->minutosBloqueo($cx, $ipx);
t('LoginIntento: 5 fallos bloquea ~15 min', $esp >= 14 && $esp <= 15, "espera=$esp");
t('LoginIntento: otra IP no se bloquea', $li->minutosBloqueo($cx, '10.0.0.10') === 0);
$li->limpiar($cx, $ipx);
t('LoginIntento: limpiar libera', $li->minutosBloqueo($cx, $ipx) === 0);

// Router: excepcion -> pagina generica + log, detalle solo con APP_DEBUG=1
class ThrowCtrl extends Controller { public function boom() { throw new RuntimeException('SECRETO-PDO-123'); } }
$logFile = $raiz . '/logs/app.log';
@unlink($logFile);
$r = new Router(); $r->get('/boom', ['ThrowCtrl', 'boom']);
putenv('APP_DEBUG'); unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);
ob_start(); $r->dispatch('/boom', 'GET'); $salida = ob_get_clean();
t('Router: error muestra pagina generica sin detalle', strpos($salida, 'SECRETO') === false && strpos($salida, 'Algo salio mal') !== false);
t('Router: error registrado en logs/app.log', is_file($logFile) && strpos(file_get_contents($logFile), 'SECRETO-PDO-123') !== false);
putenv('APP_DEBUG=1');
ob_start(); $r->dispatch('/boom', 'GET'); $salida = ob_get_clean();
t('Router: APP_DEBUG=1 muestra el detalle', strpos($salida, 'SECRETO-PDO-123') !== false);
putenv('APP_DEBUG');
$_SERVER['HTTP_ACCEPT'] = 'application/json';
error_reporting(0);
ob_start(); $r->dispatch('/boom', 'GET'); $salida = ob_get_clean();
error_reporting(E_ALL);
$j = json_decode($salida, true);
t('Router: error AJAX responde JSON generico', is_array($j) && $j['success'] === false && strpos($salida, 'SECRETO') === false);
unset($_SERVER['HTTP_ACCEPT']);
http_response_code(200);

// ---------- 2. HTTP ----------
function vivo() { $s = @fsockopen('127.0.0.1', 8101, $e, $es, 0.5); if ($s) { fclose($s); return true; } return false; }
if (!vivo()) {
    fwrite(STDERR, "Falta el servidor. Levantalo en otra consola (DB_PASSWORD vacio debe quedar definido):
  DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=nt_seguridad DB_USERNAME=root DB_PASSWORD= php -S 127.0.0.1:8101 -t public public/index.php
");
}
t('servidor 127.0.0.1:8101 responde', vivo());

class Cliente {
    public $jar;
    function __construct() { $this->jar = tempnam(sys_get_temp_dir(), 'ntj'); }
    function req($metodo, $ruta, $post = null, $headers = []) {
        $ch = curl_init(BASE . $ruta);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_HTTPHEADER => $headers]);
        if ($post !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
        $raw = curl_exec($ch);
        $hs = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'head' => substr($raw, 0, $hs), 'body' => substr($raw, $hs)];
    }
    function token() {
        $r = $this->req('GET', '/login');
        return preg_match('/name="csrf-token" content="([0-9a-f]+)"/', $r['body'], $m) ? $m[1] : '';
    }
    function sid() { return preg_match('/PHPSESSID\s+(\S+)/', file_get_contents($this->jar), $m) ? $m[1] : ''; }
    function login($cedula, $pass, $tok = null) {
        if ($tok === null) $tok = $this->token();
        return $this->req('POST', '/login', ['cedula' => $cedula, 'password' => $pass, 'csrf_token' => $tok]);
    }
}

// 2.1 login
$c = new Cliente();
$r = $c->req('POST', '/login', ['cedula' => $ced(50), 'password' => $pass]);
t('POST /login sin token = 419', $r['code'] === 419, 'codigo=' . $r['code']);
$r = $c->req('GET', '/login');
t('GET /login incluye meta csrf-token y campo oculto en el formulario',
    (bool)preg_match('/name="csrf-token" content="[0-9a-f]{64}"/', $r['body']) && (bool)preg_match('/<form[^>]*action="\/login"[^>]*>\s*<input type="hidden" name="csrf_token"/', $r['body']));
$sid0 = $c->sid();
$r = $c->login($ced(50), $pass);
$sid1 = $c->sid();
t('login correcto con token = 302 al panel presidente', $r['code'] === 302 && strpos($r['head'], '/admin/dashboard-presidente') !== false, 'codigo=' . $r['code']);
t('login regenera el id de sesion', $sid0 !== '' && $sid1 !== '' && $sid0 !== $sid1);

// 2.2 formulario del panel, con y sin token
$cedNueva = '9' . mt_rand(10000000, 99999999);
$antes = (int)$pdo->query("SELECT COUNT(*) FROM natillera_usuarios")->fetchColumn();
$r = $c->req('POST', '/admin/socios/crear', ['nombre_completo' => 'Prueba CSRF', 'cedula' => $cedNueva, 'rol_id' => 5, 'password' => '123456']);
$despues = (int)$pdo->query("SELECT COUNT(*) FROM natillera_usuarios")->fetchColumn();
t('POST /admin/socios/crear sin token = 419 y sin efectos', $r['code'] === 419 && $antes === $despues);
$r = $c->req('GET', '/admin/socios');
$nForms = preg_match_all('/<form\b[^>]*method="POST"/i', $r['body']);
$nConTok = preg_match_all('/<form\b[^>]*method="POST"[^>]*>\s*<input type="hidden" name="csrf_token"/i', $r['body']);
t('/admin/socios: todos los formularios POST llevan csrf_token (inyeccion servidor)', $nForms > 0 && $nForms === $nConTok, "forms=$nForms con_token=$nConTok");
preg_match('/name="csrf-token" content="([0-9a-f]+)"/', $r['body'], $mm); $tk = $mm[1];
$r = $c->req('POST', '/admin/socios/crear', ['nombre_completo' => 'Prueba CSRF', 'cedula' => $cedNueva, 'telefono' => '3000000000', 'rol_id' => 5, 'password' => '123456', 'csrf_token' => $tk]);
$fila = $pdo->query("SELECT id, password_hash, password_changed FROM natillera_usuarios WHERE cedula = '$cedNueva'")->fetch();
t('crear socio con token funciona', $r['code'] === 302 && $fila);
t('crear socio: password_changed = 0', $fila && (int)$fila['password_changed'] === 0);
t('crear socio: la clave 123456 enviada se ignora', $fila && !password_verify('123456', $fila['password_hash']));
$r = $c->req('GET', '/admin/socios');
$clv = preg_match('/Clave temporal: ([A-Z0-9]{8})/', $r['body'], $mm) ? $mm[1] : '';
t('flash muestra la clave temporal UNA vez', $clv !== '');
t('la clave temporal mostrada es la guardada', $clv !== '' && $fila && password_verify($clv, $fila['password_hash']));
$r = $c->req('GET', '/admin/socios');
t('la clave temporal no se vuelve a mostrar', strpos($r['body'], 'Clave temporal:') === false);

// 2.3 fetch / AJAX
$r = $c->req('POST', '/socio/marcar-pagos-leidos', '{"ids":[999999]}', ['Content-Type: application/json']);
$jj = json_decode($r['body'], true);
t('fetch POST sin token = 419 JSON', $r['code'] === 419 && is_array($jj) && $jj['success'] === false, 'codigo=' . $r['code']);
$r = $c->req('POST', '/socio/marcar-pagos-leidos', '{"ids":[999999]}', ['Content-Type: application/json', 'X-CSRF-Token: ' . $tk]);
t('fetch POST con X-CSRF-Token = 200 JSON', $r['code'] === 200 && is_array(json_decode($r['body'], true)), 'codigo=' . $r['code']);
$r = $c->req('POST', '/toggle-mode', []);
t('POST /toggle-mode sin token = 419', $r['code'] === 419);

// 2.4 3.12 Presidente en modo Socio
$r = $c->req('GET', '/admin/prestamos');
t('Presidente en modo Presidente accede a /admin/prestamos', $r['code'] === 200, 'codigo=' . $r['code']);
$r = $c->req('POST', '/toggle-mode', ['csrf_token' => $tk]);
t('toggle-mode con token cambia a modo Socio (302)', $r['code'] === 302 && strpos($r['head'], '/socio/dashboard') !== false);
$r = $c->req('GET', '/admin/prestamos');
t('Presidente en modo Socio NO accede a /admin/prestamos', $r['code'] === 302 && strpos($r['head'], '/socio/dashboard') !== false);
$r = $c->req('GET', '/socio/dashboard');
t('Presidente en modo Socio si ve rutas de socio', $r['code'] === 200);
$r = $c->req('GET', '/socio/liquidacion-anual');
$j = json_decode($r['body'], true);
t('liquidacion: Presidente en modo Socio ve solo lo suyo', is_array($j) && count($j['liquidaciones']) === 1 && $j['liquidaciones'][0]['cedula'] === $ced(50));
$c->req('POST', '/toggle-mode', ['csrf_token' => $tk]);

// 2.5 limite de intentos
$cb = new Cliente();
$cedB = $ced($sPlano);
for ($i = 0; $i < 5; $i++) { $cb->login($cedB, 'mala-' . $i); }
$r = $cb->login($cedB, $pass); // clave correcta pero ya bloqueado
$p = $cb->req('GET', '/login');
t('5 fallos bloquean aunque la clave sea correcta', $r['code'] === 302 && strpos($r['head'], '/login') !== false && strpos($p['body'], 'Demasiados intentos') !== false && preg_match('/Espera 1[45] minutos/', $p['body']));
$pdo->exec("DELETE FROM natillera_login_intentos");
$r = $cb->login($cedB, $pass);
t('tras liberar el bloqueo entra de nuevo', $r['code'] === 302 && strpos($r['head'], '/socio/dashboard') !== false);

// 2.6 rol Presidente solo por Presidente (Secretaria General id 17)
$cs = new Cliente();
$cs->login($ced(17), $pass);
$r = $cs->req('GET', '/admin/socios'); preg_match('/name="csrf-token" content="([0-9a-f]+)"/', $r['body'], $mm); $tkS = $mm[1];
t('Secretaria General accede a /admin/socios', $r['code'] === 200);
$ced2 = '8' . mt_rand(10000000, 99999999);
$cs->req('POST', '/admin/socios/crear', ['nombre_completo' => 'Intruso', 'cedula' => $ced2, 'rol_id' => 1, 'csrf_token' => $tkS]);
t('Secretaria General NO puede crear un Presidente', (int)$pdo->query("SELECT COUNT(*) FROM natillera_usuarios WHERE cedula = '$ced2'")->fetchColumn() === 0);
$cs->req('POST', '/admin/socios/actualizar', ['socio_id' => $sPlano, 'nombre_completo' => 'X', 'cedula' => $cedB, 'rol_id' => 1, 'csrf_token' => $tkS]);
t('Secretaria General NO puede ascender a Presidente', (int)$pdo->query("SELECT rol_id FROM natillera_usuarios WHERE id = $sPlano")->fetchColumn() === 5);
$nomPres = $pdo->query("SELECT nombre_completo FROM natillera_usuarios WHERE id = 50")->fetchColumn();
$cs->req('POST', '/admin/socios/actualizar', ['socio_id' => 50, 'nombre_completo' => 'Hackeado', 'cedula' => $ced(50), 'rol_id' => 5, 'csrf_token' => $tkS]);
t('Secretaria General NO puede editar al Presidente', $pdo->query("SELECT nombre_completo FROM natillera_usuarios WHERE id = 50")->fetchColumn() === $nomPres);
$cs->req('POST', '/admin/socios/actualizar', ['socio_id' => $sPlano, 'nombre_completo' => 'X', 'cedula' => $cedB, 'rol_id' => 5, 'password' => 'corta', 'csrf_token' => $tkS]);
t('clave nueva de menos de 8 caracteres se rechaza', !password_verify('corta', $pdo->query("SELECT password_hash FROM natillera_usuarios WHERE id = $sPlano")->fetchColumn()));
$r = $cs->req('GET', '/admin/socios/expediente-json?socio_id=' . $sPlano);
$jj = json_decode($r['body'], true);
t('expedienteJson: Secretaria General accede', $r['code'] === 200 && is_array($jj) && $jj['success'] === true, 'codigo=' . $r['code']);
$ct = new Cliente(); $ct->login($ced(9), $pass);
$r = $ct->req('GET', '/admin/socios/expediente-json?socio_id=' . $sPlano);
$jj = json_decode($r['body'], true);
t('expedienteJson: rol Tesorera ya no queda excluido', $r['code'] === 200 && is_array($jj) && $jj['success'] === true, 'codigo=' . $r['code'] . ' ' . substr($r['body'], 0, 80));

// 2.7 liquidacion anual
$r = $ct->req('GET', '/socio/liquidacion-anual');
$jt = json_decode($r['body'], true);
t('liquidacion: Tesorera ve a todos', is_array($jt) && count($jt['liquidaciones']) >= 50);
$sumPct = 0; foreach ($jt['liquidaciones'] as $l) $sumPct += $l['porcentaje'];
t('liquidacion: porcentajes suman ~100', abs($sumPct - 100) < 0.5, "suma=$sumPct");
$ca = new Cliente(); $ca->login($ced($sAuto), $pass);
$r = $ca->req('GET', '/socio/liquidacion-anual');
$ja = json_decode($r['body'], true);
t('liquidacion: un socio ve SOLO su fila', is_array($ja) && count($ja['liquidaciones']) === 1 && $ja['liquidaciones'][0]['cedula'] === $ced($sAuto));
t('liquidacion: socio no recibe cedulas ajenas', strpos($r['body'], '"' . $ced($sPlano) . '"') === false);

// calculo independiente del ahorro esperado
$rows = $pdo->query("SELECT ac.cuota_pagada, ac.monto_cuota, ac.monto_ahorro_extra, ac.autoprestamo_generado, p.estado, p.monto_prestado,
    (SELECT IFNULL(SUM(a.monto_capital_pagado),0) FROM natillera_abonos_prestamos a WHERE a.prestamo_id = p.id) AS cap
    FROM natillera_ahorros_cuotas ac LEFT JOIN natillera_prestamos p ON p.id = ac.prestamo_id_asociado WHERE ac.socio_id = $sAuto")->fetchAll();
$esp = 0.0; $ingenuo = 0.0; $excl = 0;
foreach ($rows as $f) {
    $ingenuo += ($f['cuota_pagada'] ? $f['monto_cuota'] : 0) + $f['monto_ahorro_extra'];
    $cuenta = $f['cuota_pagada'] && (!$f['autoprestamo_generado'] || ($f['estado'] === 'PAGADO' || $f['cap'] >= $f['monto_prestado']));
    if ($f['cuota_pagada'] && !$cuenta) $excl++;
    $esp += ($cuenta ? $f['monto_cuota'] : 0) + $f['monto_ahorro_extra'];
}
$la = $ja['liquidaciones'][0];
t('liquidacion: socio con autoprestamo activo tiene cuotas excluidas del ahorro', $excl > 0 && $esp < $ingenuo, "excluidas=$excl");
t('liquidacion: ahorro_socio = calculo independiente', abs($la['ahorro_socio'] - $esp) < 0.01, "json={$la['ahorro_socio']} esperado=$esp");
$deuda = 0.0;
foreach ($pdo->query("SELECT p.monto_prestado, IFNULL((SELECT SUM(monto_capital_pagado) FROM natillera_abonos_prestamos a WHERE a.prestamo_id = p.id),0) cap FROM natillera_prestamos p WHERE p.socio_deudor_id = $sAuto AND p.estado = 'ACTIVO'")->fetchAll() as $f) $deuda += $f['monto_prestado'] - $f['cap'];
t('liquidacion: deuda_capital = capital pendiente de prestamos activos', abs($la['deuda_capital'] - $deuda) < 0.01 && $deuda > 0, "json={$la['deuda_capital']} esperado=$deuda");
$util = (float)$pdo->query("SELECT IFNULL(SUM(ganancia_asignada),0) FROM natillera_actividad_participantes WHERE socio_id = $sAuto")->fetchColumn();
t('liquidacion: incluye utilidades de actividades', abs($la['utilidades_actividades'] - $util) < 0.01);
t('liquidacion: total = ahorro - deuda + intereses + actividades',
    abs($la['total_a_recibir'] - ($la['ahorro_socio'] - $la['deuda_capital'] + $la['ganancia_intereses'] + $la['utilidades_actividades'])) < 0.02);

// ---------- limpieza ----------
$pdo->exec("DELETE FROM natillera_usuarios WHERE cedula IN ('$cedNueva', '$ced2')");
$pdo->exec("DELETE FROM natillera_login_intentos");
foreach ($usuarios as $id => $o) {
    $st = $pdo->prepare("UPDATE natillera_usuarios SET password_hash = :h, password_changed = :pc WHERE id = :id");
    $st->execute([':h' => $o['password_hash'], ':pc' => $o['password_changed'], ':id' => $id]);
}
if (!$fallos) { @unlink($logFile); }

echo "\nResultado: $ok OK, $fallos FALLO\n";
exit($fallos ? 1 : 0);
