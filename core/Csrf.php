<?php
// core/Csrf.php
// Proteccion CSRF central: un token por sesion, validado por Router::dispatch en toda peticion POST.

class Csrf {
    const CAMPO = 'csrf_token';

    private static function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
            session_start();
        }
    }

    /** Devuelve el token de la sesion; lo crea si no existe. */
    public static function token() {
        self::iniciarSesion();
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Token enviado por la peticion (campo POST o cabecera X-CSRF-Token). */
    public static function tokenEnviado() {
        if (isset($_POST[self::CAMPO]) && is_string($_POST[self::CAMPO])) {
            return $_POST[self::CAMPO];
        }
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        return '';
    }

    public static function valido($enviado = null) {
        self::iniciarSesion();
        $esperado = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
        if ($enviado === null) {
            $enviado = self::tokenEnviado();
        }
        return is_string($esperado) && $esperado !== '' && is_string($enviado) && $enviado !== ''
            && hash_equals($esperado, $enviado);
    }

    public static function campo() {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /** La peticion espera JSON (AJAX / fetch). */
    public static function esAjax() {
        $xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
        $accept = isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']) : '';
        $ctype = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
        return $xhr === 'xmlhttprequest'
            || strpos($accept, 'application/json') !== false
            || strpos($ctype, 'application/json') !== false
            || isset($_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    /** Responde 419 y termina. */
    public static function rechazar() {
        http_response_code(419);
        if (self::esAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'success' => false,
                'message' => 'Tu sesion expiro o la peticion no es valida. Recarga la pagina e intentalo de nuevo.',
                'csrf' => false
            ), JSON_UNESCAPED_UNICODE);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
               . '<title>Sesion expirada</title></head>'
               . '<body style="font-family:system-ui,sans-serif;background:#f4f7f4;color:#23332b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0">'
               . '<div style="max-width:420px;text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">'
               . '<h2 style="margin-top:0">Tu sesion expiro</h2>'
               . '<p>Por seguridad no pudimos procesar la accion. No se guardo ningun cambio. Vuelve a la pagina anterior, recargala e intentalo de nuevo.</p>'
               . '<p><a href="/login" style="color:#1f6b4a;font-weight:600">Ir al inicio</a></p>'
               . '</div></body></html>';
        }
        exit;
    }

    /**
     * Inserta el campo oculto despues de cada <form ... method="POST"> del HTML que no lo tenga.
     */
    public static function inyectar($html) {
        if (stripos($html, '<form') === false) {
            return $html;
        }
        $campo = self::campo();
        $patron = '/<form\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
        if (!preg_match_all($patron, $html, $m, PREG_OFFSET_CAPTURE)) {
            return $html;
        }
        $salida = '';
        $cursor = 0;
        foreach ($m[0] as $par) {
            $tag = $par[0];
            $pos = $par[1];
            if (!preg_match('/\bmethod\s*=\s*(?:"post"|\'post\'|post\b)/i', $tag)) {
                continue;
            }
            $fin = $pos + strlen($tag);
            $cierre = stripos($html, '</form', $fin);
            $cuerpo = $cierre === false ? substr($html, $fin) : substr($html, $fin, $cierre - $fin);
            if (strpos($cuerpo, 'name="' . self::CAMPO . '"') !== false || strpos($cuerpo, "name='" . self::CAMPO . "'") !== false) {
                continue;
            }
            $salida .= substr($html, $cursor, $fin - $cursor) . $campo;
            $cursor = $fin;
        }
        return $salida . substr($html, $cursor);
    }
}
