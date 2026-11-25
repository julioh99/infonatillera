<?php
// tests/_runner_controlador.php
// Ejecuta UN metodo de controlador en CLI con $_POST/$_SESSION simulados y imprime el resultado como JSON.
// Uso (lo invoca tests/endurecimiento_test.php):  php tests/_runner_controlador.php <Clase> <metodo> <archivo_json>
// El JSON trae {"post":{}, "session":{}}. La BD sale de las variables DB_* (usar siempre una BD local de pruebas).

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

$clase = $argv[1];
$metodo = $argv[2];
$cfg = json_decode(file_get_contents($argv[3]), true);

chdir(__DIR__ . '/..');
session_save_path(sys_get_temp_dir());
@session_start();
$_SESSION = isset($cfg['session']) ? $cfg['session'] : [];
$_POST = isset($cfg['post']) ? $cfg['post'] : [];
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';

register_shutdown_function(function () {
    echo "\n@@RESULT@@" . json_encode([
        'error' => isset($_SESSION['error']) ? $_SESSION['error'] : null,
        'success' => isset($_SESSION['success']) ? $_SESSION['success'] : null,
    ], JSON_UNESCAPED_UNICODE) . "\n";
});

require_once __DIR__ . '/../app/Controllers/' . $clase . '.php';
$c = new $clase();
$c->$metodo();
