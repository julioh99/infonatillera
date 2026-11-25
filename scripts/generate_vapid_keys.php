<?php
// scripts/generate_vapid_keys.php
// Genera un par de llaves VAPID (P-256) nuevo y lo IMPRIME como lineas para el .env.
// No escribe ningun archivo del repositorio (las llaves son secretos).
// Uso: php scripts/generate_vapid_keys.php [mailto:contacto@dominio.com]
// En Windows, si openssl falla, defina OPENSSL_CONF antes de ejecutar, por ejemplo:
//   set OPENSSL_CONF=C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\extras\ssl\openssl.cnf

if (PHP_SAPI !== 'cli') {
    exit('Solo por consola.');
}
require_once __DIR__ . '/../core/WebPush.php';

$subject = isset($argv[1]) ? $argv[1] : 'mailto:admin@infonatillera.com';

$res = openssl_pkey_new(array('curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC));
if (!$res) {
    fwrite(STDERR, "No se pudo generar la llave: " . openssl_error_string() . "\n"
        . "En Windows defina OPENSSL_CONF apuntando a un openssl.cnf (ver comentario al inicio del script).\n");
    exit(1);
}
$d = openssl_pkey_get_details($res);
if (!$d || !isset($d['ec'])) {
    fwrite(STDERR, "OpenSSL no devolvio los datos de la llave EC.\n");
    exit(1);
}
$pad = function ($v) { return str_pad($v, 32, "\x00", STR_PAD_LEFT); };
$publica = WebPush::base64UrlEncode("\x04" . $pad($d['ec']['x']) . $pad($d['ec']['y']));
$privada = WebPush::base64UrlEncode($pad($d['ec']['d']));

echo "Copie estas lineas al .env del servidor (NO al repositorio):\n\n";
echo "VAPID_PUBLIC_KEY={$publica}\n";
echo "VAPID_PRIVATE_KEY={$privada}\n";
echo "VAPID_SUBJECT={$subject}\n\n";
echo "Despues los socios deben pulsar de nuevo \"Activar en mi Navegador\" (las suscripciones hechas con la llave anterior dejan de funcionar).\n";
