<?php
// core/Money.php
// Montos en formato colombiano y acceso a la configuracion de negocio (config/natillera.php).
// Compatible con PHP 7.2.

class Money {

    private static $cfg = null;

    /**
     * Lee una clave de config/natillera.php con notacion de puntos: Money::config('prestamo.tasa_mensual_defecto', 10.0)
     */
    public static function config($clave, $porDefecto = null) {
        if (self::$cfg === null) {
            $archivo = __DIR__ . '/../config/natillera.php';
            self::$cfg = is_file($archivo) ? (array)(require $archivo) : [];
        }
        $nodo = self::$cfg;
        foreach (explode('.', (string)$clave) as $parte) {
            if (is_array($nodo) && array_key_exists($parte, $nodo)) {
                $nodo = $nodo[$parte];
            } else {
                return $porDefecto;
            }
        }
        return $nodo;
    }

    /**
     * Convierte un monto escrito por una persona a float. Formatos aceptados:
     *   "1.500.000"  "1.500.000,50"  "1500000"  "1500000,5"  "150000.5"  "$ 1.500.000"  "1.500.000 COP"
     * Devuelve null si el texto es invalido, negativo, tiene ruido o es absurdamente grande.
     * Vacio/null => $vacioComo (por defecto null, es decir "invalido").
     *
     * Nota: "1.500" se lee como 1500 (punto = miles, regla colombiana); "1.50" se lee como 1,5.
     */
    public static function parse($valor, $vacioComo = null) {
        if (is_int($valor) || is_float($valor)) {
            if (is_float($valor) && (is_nan($valor) || is_infinite($valor))) return null;
            return $valor < 0 ? null : round((float)$valor, 2);
        }
        if ($valor === null || is_bool($valor) || is_array($valor) || is_object($valor)) {
            return ($valor === null) ? $vacioComo : null;
        }
        $v = trim((string)$valor);
        $v = preg_replace('/^\$\s*|\s*(COP|cop)\s*$/', '', $v);
        $v = str_replace([' ', "\xC2\xA0"], '', $v);
        $v = ltrim($v, '$');
        if ($v === '') return $vacioComo;

        $n = null;
        if (preg_match('/^\d+$/', $v)) {
            $n = $v;
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $v)) {
            // miles con punto, decimales opcionales con coma
            $n = str_replace(',', '.', str_replace('.', '', $v));
        } elseif (preg_match('/^\d+,\d{1,2}$/', $v)) {
            $n = str_replace(',', '.', $v);
        } elseif (preg_match('/^\d+\.\d{1,2}$/', $v)) {
            $n = $v; // decimal con punto (150000.5)
        } else {
            return null;
        }

        // Demasiados digitos: descarta (evita desbordes en DECIMAL y flotantes imprecisos)
        $enteros = explode('.', $n)[0];
        if (strlen(ltrim($enteros, '0')) > 12) return null;

        return round((float)$n, 2);
    }

    /**
     * Valida que el monto sea > 0 y no supere el tope de config (controladores.monto_maximo).
     * Devuelve [float|null, mensajeError|null].
     */
    public static function validarPositivo($valor, $nombre = 'El monto', $maximo = null) {
        $m = self::parse($valor);
        if ($m === null || $m <= 0) {
            return [null, "{$nombre} no es valido. Escribe un valor mayor a cero, por ejemplo 150.000."];
        }
        $tope = ($maximo !== null) ? (float)$maximo : (float)self::config('controladores.monto_maximo', 100000000);
        if ($m > $tope) {
            return [null, "{$nombre} supera el maximo permitido ($" . self::format($tope) . ")."];
        }
        return [$m, null];
    }

    /**
     * Porcentaje ("5", "5,5", "5.25") dentro de [$min, $max]. Devuelve float o null si es invalido/fuera de rango.
     */
    public static function parsePorcentaje($valor, $min, $max) {
        if (is_int($valor) || is_float($valor)) {
            $t = (float)$valor;
        } else {
            if (!is_string($valor)) return null;
            $v = str_replace(',', '.', trim($valor));
            if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $v)) return null;
            $t = (float)$v;
        }
        if (is_nan($t) || $t < $min || $t > $max) return null;
        return $t;
    }

    /** Formato colombiano: 1500000 => "1.500.000"; con decimales "1.500.000,50". */
    public static function format($n, $decimales = 0) {
        return number_format((float)$n, (int)$decimales, ',', '.');
    }
}
