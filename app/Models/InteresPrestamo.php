<?php
// app/Models/InteresPrestamo.php
//
// Interes mensual progresivo de un prestamo. Clase PURA: no usa base de datos, se prueba sola.
//
// Regla (config/natillera.php -> interes.modo):
//   SALDO_MES_INICIADO (por defecto): la tasa del prestamo (ej. 10%) se cobra sobre el capital pendiente
//       al inicio de cada mes; el mes que empieza se cobra COMPLETO (el dia del prestamo ya se debe 1 mes).
//   SALDO_MES_COMPLETO: igual, pero solo se cobran los meses cumplidos (al mes exacto se debe 1 mes).
//   COMPUESTO: como SALDO_MES_INICIADO, pero la base incluye el interes causado y no pagado (interes sobre interes).
// En todos los modos:
//   - Los meses se cuentan por aniversario de la fecha del prestamo (31 ene + 1 mes = 28/29 feb).
//   - Los abonos a capital con fecha <= inicio del mes reducen la base de ese mes (un abono el mismo dia
//     del aniversario se aplica antes de cobrar el mes nuevo). Los abonos posteriores reducen el mes siguiente.
//   - Si el capital llega a 0 el interes deja de correr ese dia: no se cobran meses nuevos.
//   - Prestamos anulados por la regla de 24 horas (anulado_sin_interes = 1): interes 0.

class InteresPrestamo {

    const MODO_INICIADO = 'SALDO_MES_INICIADO';
    const MODO_COMPLETO = 'SALDO_MES_COMPLETO';
    const MODO_COMPUESTO = 'COMPUESTO';

    const MAX_MESES = 600;

    /** Modo configurado en config/natillera.php (sin depender de BD). */
    public static function modoConfigurado() {
        static $modo = null;
        if ($modo === null) {
            $modo = self::MODO_INICIADO;
            $archivo = __DIR__ . '/../../config/natillera.php';
            if (is_file($archivo)) {
                $cfg = include $archivo;
                if (is_array($cfg) && isset($cfg['interes']['modo'])) {
                    $modo = self::normalizarModo($cfg['interes']['modo']);
                }
            }
        }
        return $modo;
    }

    public static function normalizarModo($modo) {
        $modo = strtoupper(trim((string)$modo));
        if ($modo === self::MODO_COMPLETO || $modo === self::MODO_COMPUESTO) {
            return $modo;
        }
        return self::MODO_INICIADO;
    }

    /**
     * @param array       $prestamo monto_prestado, tasa_interes_mensual, fecha_inicio (Y-m-d), anulado_sin_interes
     * @param array       $abonos   filas con fecha_abono, monto_capital_pagado, monto_interes_pagado
     * @param string|null $hoy      Y-m-d (por defecto hoy)
     * @param string|null $modo     por defecto el configurado
     * @return array {
     *   modo, tasa, capital_inicial, capital_pagado, saldo_capital, meses_cobrados, interes_causado,
     *   interes_pagado, interes_pendiente, interes_a_favor, proximo_mes (Y-m-d|null), anulado, meses: [
     *     {numero, desde, hasta, base_capital, base_interes, tasa, interes}
     *   ]
     * }
     */
    public static function calcular(array $prestamo, array $abonos, $hoy = null, $modo = null) {
        $modo = $modo === null ? self::modoConfigurado() : self::normalizarModo($modo);
        $hoy = self::soloFecha($hoy === null ? date('Y-m-d') : $hoy);
        $monto = (float)(isset($prestamo['monto_prestado']) ? $prestamo['monto_prestado'] : 0);
        $tasa = (float)(isset($prestamo['tasa_interes_mensual']) ? $prestamo['tasa_interes_mensual'] : 0);
        $anulado = !empty($prestamo['anulado_sin_interes']);
        $inicio = self::soloFecha(isset($prestamo['fecha_inicio']) ? $prestamo['fecha_inicio'] : $hoy);
        if ($inicio === '') {
            $inicio = $hoy;
        }

        // Abonos normalizados y totales
        $capitales = array(); // [fecha, monto]
        $capitalPagado = 0.0;
        $interesPagado = 0.0;
        foreach ($abonos as $a) {
            $f = self::soloFecha(isset($a['fecha_abono']) ? $a['fecha_abono'] : '');
            if ($f === '') {
                $f = $inicio;
            }
            $c = (float)(isset($a['monto_capital_pagado']) ? $a['monto_capital_pagado'] : 0);
            $i = (float)(isset($a['monto_interes_pagado']) ? $a['monto_interes_pagado'] : 0);
            if ($c > 0) {
                $capitales[] = array($f, $c);
                $capitalPagado += $c;
            }
            $interesPagado += $i;
        }
        $saldoActual = max(0.0, round($monto - $capitalPagado, 2));

        $res = array(
            'modo' => $modo,
            'tasa' => $tasa,
            'fecha_inicio' => $inicio,
            'capital_inicial' => round($monto, 2),
            'capital_pagado' => round($capitalPagado, 2),
            'saldo_capital' => $saldoActual,
            'meses_cobrados' => 0,
            'interes_causado' => 0.0,
            'interes_pagado' => round($interesPagado, 2),
            'interes_pendiente' => 0.0,
            'interes_a_favor' => round($interesPagado, 2),
            'proximo_mes' => null,
            'anulado' => $anulado,
            'meses' => array(),
        );

        if ($anulado || $monto <= 0 || $tasa <= 0) {
            return $res;
        }

        $causado = 0.0;
        for ($k = 0; $k < self::MAX_MESES; $k++) {
            $mesInicio = self::sumarMeses($inicio, $k);
            $mesSiguiente = self::sumarMeses($inicio, $k + 1);

            // Un mes se cobra segun el modo: iniciado (ya empezo) o completo (ya termino)
            $limite = ($modo === self::MODO_COMPLETO) ? $mesSiguiente : $mesInicio;
            if ($limite > $hoy) {
                // aun no se cobra: es el proximo cobro (si queda capital)
                if ($saldoActual > 0) {
                    $res['proximo_mes'] = $limite;
                }
                break;
            }

            // Capital pendiente al inicio de este mes
            $pagadoAntes = 0.0;
            foreach ($capitales as $cp) {
                if ($cp[0] <= $mesInicio) {
                    $pagadoAntes += $cp[1];
                }
            }
            $saldoMes = round($monto - $pagadoAntes, 2);
            if ($saldoMes <= 0) {
                break; // capital pagado: el interes deja de correr
            }

            $baseInteres = 0.0;
            if ($modo === self::MODO_COMPUESTO) {
                // interes causado y no pagado hasta el inicio del mes
                $baseInteres = max(0.0, round($causado - self::interesPagadoHasta($abonos, $mesInicio, $inicio), 2));
            }
            $interes = round(($saldoMes + $baseInteres) * $tasa / 100.0, 2);
            $causado += $interes;

            $res['meses'][] = array(
                'numero' => $k + 1,
                'desde' => $mesInicio,
                'hasta' => self::sumarDias($mesSiguiente, -1),
                'base_capital' => $saldoMes,
                'base_interes' => $baseInteres,
                'tasa' => $tasa,
                'interes' => $interes,
            );
        }

        $res['meses_cobrados'] = count($res['meses']);
        $res['interes_causado'] = round($causado, 2);
        $res['interes_pendiente'] = round(max(0.0, $causado - $interesPagado), 2);
        $res['interes_a_favor'] = round(max(0.0, $interesPagado - $causado), 2);
        return $res;
    }

    private static function interesPagadoHasta(array $abonos, $fecha, $inicio) {
        $t = 0.0;
        foreach ($abonos as $a) {
            $f = self::soloFecha(isset($a['fecha_abono']) ? $a['fecha_abono'] : '');
            if ($f === '') {
                $f = $inicio;
            }
            if ($f <= $fecha) {
                $t += (float)(isset($a['monto_interes_pagado']) ? $a['monto_interes_pagado'] : 0);
            }
        }
        return $t;
    }

    /** Devuelve Y-m-d a partir de 'Y-m-d' o 'Y-m-d H:i:s'; '' si no es valida. */
    public static function soloFecha($valor) {
        $valor = trim((string)$valor);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m)) {
            return '';
        }
        if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            return '';
        }
        return $m[1] . '-' . $m[2] . '-' . $m[3];
    }

    /** Suma meses por aniversario con recorte al ultimo dia del mes destino. */
    public static function sumarMeses($fecha, $meses) {
        $y = (int)substr($fecha, 0, 4);
        $m = (int)substr($fecha, 5, 2);
        $d = (int)substr($fecha, 8, 2);
        $total = $y * 12 + ($m - 1) + (int)$meses;
        $ny = intdiv($total, 12);
        $nm = ($total % 12) + 1;
        $ultimo = (int)date('t', mktime(0, 0, 0, $nm, 1, $ny));
        return sprintf('%04d-%02d-%02d', $ny, $nm, min($d, $ultimo));
    }

    public static function sumarDias($fecha, $dias) {
        $dt = new DateTime($fecha . ' 00:00:00');
        $dt->modify(($dias >= 0 ? '+' : '') . (int)$dias . ' day');
        return $dt->format('Y-m-d');
    }
}
