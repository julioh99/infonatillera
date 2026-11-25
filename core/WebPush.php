<?php
// core/WebPush.php
// Envio de Web Push SIN dependencias: JWT VAPID (ES256) con openssl y envio por cURL.
// Se usa push SIN carga util (cuerpo vacio): el service worker consulta
// /socio/notificaciones-pendientes-json y muestra el aviso. Asi no hace falta cifrar (RFC 8291).
// Compatible con PHP 7.2.

class WebPush {

    /** Servicios push permitidos (evita que un usuario haga que el servidor llame a cualquier URL). */
    private static $hostsPermitidos = array(
        'fcm.googleapis.com',
        'android.googleapis.com',
        'updates.push.services.mozilla.com',
        '.push.services.mozilla.com',
        '.push.apple.com',
        '.notify.windows.com',
    );

    private $publicKey;
    private $privateKey;
    private $subject;
    private $permitirInseguro;
    private $timeout;
    private $pem = null;
    private $jwtCache = array();

    /**
     * @param array $llaves ['public'=>..., 'private'=>..., 'subject'=>...] en base64url
     * @param array $opciones allow_insecure (solo pruebas locales), timeout (segundos)
     */
    public function __construct(array $llaves, array $opciones = array()) {
        $this->publicKey = (string)(isset($llaves['public']) ? $llaves['public'] : '');
        $this->privateKey = (string)(isset($llaves['private']) ? $llaves['private'] : '');
        $this->subject = (string)(isset($llaves['subject']) ? $llaves['subject'] : 'mailto:admin@example.com');
        $this->permitirInseguro = !empty($opciones['allow_insecure']);
        $this->timeout = isset($opciones['timeout']) ? (int)$opciones['timeout'] : 10;
    }

    // ------------------------------------------------------------------ llaves

    /** Llaves desde el entorno (VAPID_*) y, como respaldo, config/vapid_keys.php. */
    public static function llaves() {
        $raiz = __DIR__ . '/..';
        if (class_exists('Database') && method_exists('Database', 'loadEnv')) {
            Database::loadEnv($raiz . '/.env');
        }
        $pub = getenv('VAPID_PUBLIC_KEY');
        $priv = getenv('VAPID_PRIVATE_KEY');
        $sub = getenv('VAPID_SUBJECT');
        if ($pub !== false && $pub !== '' && $priv !== false && $priv !== '') {
            return array('public' => $pub, 'private' => $priv, 'subject' => ($sub !== false && $sub !== '') ? $sub : 'mailto:admin@infonatillera.com', 'origen' => 'entorno');
        }
        $archivo = $raiz . '/config/vapid_keys.php';
        if (is_file($archivo)) {
            $cfg = require $archivo;
            if (is_array($cfg) && !empty($cfg['VAPID_PUBLIC_KEY']) && !empty($cfg['VAPID_PRIVATE_KEY'])) {
                return array(
                    'public' => $cfg['VAPID_PUBLIC_KEY'],
                    'private' => $cfg['VAPID_PRIVATE_KEY'],
                    'subject' => !empty($cfg['VAPID_SUBJECT']) ? $cfg['VAPID_SUBJECT'] : 'mailto:admin@infonatillera.com',
                    'origen' => 'archivo',
                );
            }
        }
        return array('public' => '', 'private' => '', 'subject' => '', 'origen' => 'ninguno');
    }

    public static function desdeConfiguracion(array $opciones = array()) {
        return new self(self::llaves(), $opciones);
    }

    public function configurado() {
        return $this->publicKey !== '' && $this->privateKey !== '' && function_exists('openssl_sign') && function_exists('curl_init');
    }

    // ------------------------------------------------------------------ utilidades

    public static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode($data) {
        $r = strlen($data) % 4;
        if ($r) {
            $data .= str_repeat('=', 4 - $r);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /** Firma ECDSA en DER (SEQUENCE{INTEGER r, INTEGER s}) -> r||s de 64 bytes. */
    public static function derARaw($der, $tam = 32) {
        $pos = 0;
        if (strlen($der) < 8 || ord($der[$pos++]) !== 0x30) {
            throw new RuntimeException('Firma DER invalida');
        }
        $len = ord($der[$pos++]);
        if ($len & 0x80) {
            $pos += ($len & 0x7f);
        }
        $partes = array();
        for ($i = 0; $i < 2; $i++) {
            if (!isset($der[$pos]) || ord($der[$pos++]) !== 0x02) {
                throw new RuntimeException('Firma DER invalida (INTEGER)');
            }
            $l = ord($der[$pos++]);
            $v = substr($der, $pos, $l);
            $pos += $l;
            $v = ltrim($v, "\x00");
            if (strlen($v) > $tam) {
                throw new RuntimeException('Componente de firma demasiado larga');
            }
            $partes[] = str_pad($v, $tam, "\x00", STR_PAD_LEFT);
        }
        return $partes[0] . $partes[1];
    }

    /** r||s (64 bytes) -> DER, para verificar con openssl_verify. */
    public static function rawADer($raw) {
        $enc = function ($v) {
            $v = ltrim($v, "\x00");
            if ($v === '' || (ord($v[0]) & 0x80)) {
                $v = "\x00" . $v;
            }
            return "\x02" . chr(strlen($v)) . $v;
        };
        $cuerpo = $enc(substr($raw, 0, 32)) . $enc(substr($raw, 32, 32));
        return "\x30" . chr(strlen($cuerpo)) . $cuerpo;
    }

    /** PEM de la llave privada EC P-256 a partir de d (32 bytes) y la publica (65 bytes). */
    public static function pemPrivado($privadaB64, $publicaB64) {
        $d = str_pad(self::base64UrlDecode($privadaB64), 32, "\x00", STR_PAD_LEFT);
        $pub = self::base64UrlDecode($publicaB64);
        if (strlen($d) !== 32 || strlen($pub) !== 65 || $pub[0] !== "\x04") {
            throw new RuntimeException('Llaves VAPID con formato invalido');
        }
        $der = "\x30\x77\x02\x01\x01\x04\x20" . $d
            . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
            . "\xa1\x44\x03\x42\x00" . $pub;
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /** PEM de la llave publica (SubjectPublicKeyInfo) a partir de los 65 bytes sin comprimir. */
    public static function pemPublico($publicaB64) {
        $pub = self::base64UrlDecode($publicaB64);
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $pub;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    // ------------------------------------------------------------------ JWT VAPID

    public function crearJwt($audiencia, $expira = null) {
        $expira = $expira ? $expira : (time() + 12 * 3600);
        $cabecera = self::base64UrlEncode(json_encode(array('typ' => 'JWT', 'alg' => 'ES256')));
        $claims = self::base64UrlEncode(json_encode(array('aud' => $audiencia, 'exp' => (int)$expira, 'sub' => $this->subject), JSON_UNESCAPED_SLASHES));
        $datos = $cabecera . '.' . $claims;
        if ($this->pem === null) {
            $this->pem = self::pemPrivado($this->privateKey, $this->publicKey);
        }
        $clave = openssl_pkey_get_private($this->pem);
        if (!$clave) {
            throw new RuntimeException('No se pudo cargar la llave privada VAPID');
        }
        $der = '';
        if (!openssl_sign($datos, $der, $clave, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el JWT VAPID');
        }
        return $datos . '.' . self::base64UrlEncode(self::derARaw($der));
    }

    /** Verifica un JWT con la llave publica; devuelve los claims o null. */
    public static function verificarJwt($jwt, $publicaB64) {
        $p = explode('.', $jwt);
        if (count($p) !== 3) {
            return null;
        }
        $raw = self::base64UrlDecode($p[2]);
        if (strlen($raw) !== 64) {
            return null;
        }
        $ok = openssl_verify($p[0] . '.' . $p[1], self::rawADer($raw), self::pemPublico($publicaB64), OPENSSL_ALGO_SHA256);
        return $ok === 1 ? json_decode(self::base64UrlDecode($p[1]), true) : null;
    }

    // ------------------------------------------------------------------ endpoints

    public function endpointPermitido($endpoint) {
        $u = parse_url((string)$endpoint);
        if (!$u || empty($u['host']) || empty($u['scheme'])) {
            return false;
        }
        if ($this->permitirInseguro) {
            return in_array($u['scheme'], array('http', 'https'), true);
        }
        if ($u['scheme'] !== 'https' || isset($u['user']) || (isset($u['port']) && (int)$u['port'] !== 443)) {
            return false;
        }
        return self::hostPermitido($u['host']);
    }

    public static function hostPermitido($host) {
        $host = strtolower($host);
        foreach (self::$hostsPermitidos as $h) {
            if ($h[0] === '.') {
                if (substr($host, -strlen($h)) === $h) {
                    return true;
                }
            } elseif ($host === $h) {
                return true;
            }
        }
        return false;
    }

    private function audiencia($endpoint) {
        $u = parse_url($endpoint);
        $a = $u['scheme'] . '://' . $u['host'];
        if (isset($u['port'])) {
            $a .= ':' . $u['port'];
        }
        return $a;
    }

    /** Cabeceras de la peticion para un endpoint. */
    public function cabeceras($endpoint, $ttl = 86400, $urgencia = 'normal') {
        $aud = $this->audiencia($endpoint);
        if (!isset($this->jwtCache[$aud]) || $this->jwtCache[$aud]['exp'] < time() + 600) {
            $exp = time() + 12 * 3600;
            $this->jwtCache[$aud] = array('exp' => $exp, 'jwt' => $this->crearJwt($aud, $exp));
        }
        return array(
            'Authorization: vapid t=' . $this->jwtCache[$aud]['jwt'] . ', k=' . $this->publicKey,
            'TTL: ' . (int)$ttl,
            'Urgency: ' . $urgencia,
            'Content-Length: 0',
        );
    }

    // ------------------------------------------------------------------ envio

    /**
     * Envia un push vacio a cada suscripcion (en paralelo con curl_multi).
     * @param array $suscripciones filas con al menos 'endpoint'
     * @return array mismo orden: ['ok'=>bool,'status'=>int,'caducada'=>bool,'detalle'=>string]
     */
    public function enviarVarios(array $suscripciones, $ttl = 86400, $urgencia = 'normal') {
        $resultados = array();
        $mh = curl_multi_init();
        $handles = array();
        foreach ($suscripciones as $i => $s) {
            $endpoint = (string)(isset($s['endpoint']) ? $s['endpoint'] : '');
            if (!$this->configurado()) {
                $resultados[$i] = self::res(false, 0, false, 'Llaves VAPID o extensiones no disponibles');
                continue;
            }
            if (!$this->endpointPermitido($endpoint)) {
                $resultados[$i] = self::res(false, 0, true, 'Endpoint no permitido (se descarta la suscripcion)');
                continue;
            }
            try {
                $cab = $this->cabeceras($endpoint, $ttl, $urgencia);
            } catch (Exception $e) {
                $resultados[$i] = self::res(false, 0, false, 'Error de firma: ' . $e->getMessage());
                continue;
            }
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '',
                CURLOPT_HTTPHEADER => $cab,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => $this->timeout,
            ));
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        $activos = 0;
        do {
            $estado = curl_multi_exec($mh, $activos);
            if ($activos > 0 && curl_multi_select($mh, 1.0) === -1) {
                usleep(10000);
            }
        } while ($activos > 0 && $estado === CURLM_OK);

        foreach ($handles as $i => $ch) {
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            $cuerpo = (string)curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($status === 0) {
                $resultados[$i] = self::res(false, 0, false, 'Sin respuesta: ' . ($err !== '' ? $err : 'error de red'));
            } elseif ($status >= 200 && $status < 300) {
                $resultados[$i] = self::res(true, $status, false, 'Enviado');
            } elseif ($status === 404 || $status === 410) {
                $resultados[$i] = self::res(false, $status, true, 'Suscripcion caducada (' . $status . ')');
            } else {
                $resultados[$i] = self::res(false, $status, false, 'Rechazado por el servicio push (' . $status . '): ' . substr(trim($cuerpo), 0, 120));
            }
        }
        curl_multi_close($mh);
        ksort($resultados);
        return $resultados;
    }

    public function enviar(array $suscripcion, $ttl = 86400, $urgencia = 'normal') {
        $r = $this->enviarVarios(array($suscripcion), $ttl, $urgencia);
        return $r[0];
    }

    private static function res($ok, $status, $caducada, $detalle) {
        return array('ok' => $ok, 'status' => $status, 'caducada' => $caducada, 'detalle' => $detalle);
    }
}
