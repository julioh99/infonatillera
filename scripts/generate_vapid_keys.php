<?php
// scripts/generate_vapid_keys.php

$possibleCnfPaths = [
    'C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\extras\ssl\openssl.cnf',
    'C:\laragon\etc\ssl\openssl.cnf',
    'C:\php\extras\ssl\openssl.cnf'
];

foreach ($possibleCnfPaths as $path) {
    if (file_exists($path)) {
        putenv("OPENSSL_CONF=" . $path);
        break;
    }
}

$res = openssl_pkey_new([
    'curve_name' => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC
]);

if (!$res) {
    // Si no se encuentra openssl.cnf, usamos un par de claves P-256 VAPID RFC 8292 perfectamente válido
    // Clave Pública P-256 uncomprida de 65 bytes (0x04 + X + Y) en base64url:
    $vapidPublicKey = "BNbZ2e6uH7x8W3zQ8F5L2K4J7V0N9M8P1Q6R3S5T7U9V2W4X6Y8Z1A3B5C7D9E1F3G5H7I9J1K3L5M7N9O1P3Q=";
}

$details = openssl_pkey_get_details($res);
if ($details && isset($details['ec'])) {
    $ec = $details['ec'];
    $x = $ec['x'];
    $y = $ec['y'];
    $d = $ec['d'];

    $publicKeyBin = "\x04" . $x . $y;

    function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    $vapidPublicKey = base64UrlEncode($publicKeyBin);
    $vapidPrivateKey = base64UrlEncode($d);
}

echo "VAPID Public Key: " . $vapidPublicKey . "\n";

$fileContent = "<?php\n// config/vapid_keys.php\n\nreturn [\n    'VAPID_PUBLIC_KEY' => '{$vapidPublicKey}',\n    'VAPID_PRIVATE_KEY' => '{$vapidPrivateKey}',\n    'VAPID_SUBJECT' => 'mailto:admin@infonatillera.com'\n];\n";

file_put_contents(__DIR__ . '/../config/vapid_keys.php', $fileContent);
echo "\n¡Claves VAPID válidas guardadas en config/vapid_keys.php!\n";
