<?php
// Router del servidor php -S de prueba: registra cada peticion recibida y responde segun la ruta.
$log = getenv('WEBPUSH_LOG');
$headers = function_exists('getallheaders') ? getallheaders() : array();
if ($log) {
    file_put_contents($log, json_encode(array(
        'metodo' => $_SERVER['REQUEST_METHOD'],
        'ruta' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        'cabeceras' => $headers,
        'cuerpo' => file_get_contents('php://input'),
    )) . "\n", FILE_APPEND);
}
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$codigos = array('/ok' => 201, '/gone' => 410, '/missing' => 404, '/err' => 500);
$partes = explode('/', ltrim($ruta, '/'));
$prefijo = '/' . $partes[0];
http_response_code(isset($codigos[$prefijo]) ? $codigos[$prefijo] : 201);
echo 'fake';
