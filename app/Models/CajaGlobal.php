<?php
// app/Models/CajaGlobal.php
// UNICA fuente de verdad de la caja y de los fondos reservados (ronda/rifa).
// La usan el tablero del presidente y el cierre de reunion (CierreReunion::calcularResumenReunion), de modo
// que ambos coinciden. Compatible con PHP 7.2.
//
// Caja = cuotas base pagadas (ahorro) + ahorro extra + aportes de ronda + aportes de rifa + abonos a capital
//        + intereses + devoluciones de actividades + inyecciones recibidas
//        - prestamos entregados (no anulados) - premios de ronda/rifa entregados
//        - inyecciones devueltas (capital + rendimiento) - prestamos a actividades.
// Un autoprestamo es cuota recibida (ahorro + ronda + rifa) y prestamo entregado por el mismo valor: neto 0.

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Money.php';

class CajaGlobal extends Model {

    /** Lee config/natillera.php con notacion de puntos (ver core/Money.php). */
    public static function cfg($clave, $porDefecto = null) {
        return Money::config($clave, $porDefecto);
    }

    /** Ahorro neto por cuota pagada (antes 40.000 escrito en el codigo). */
    public static function ahorroPorCuota(): float {
        return (float)self::cfg('cuota.ahorro', 40000);
    }

    // ---------------------------------------------------------------
    // Piezas por reunion (o globales si $reunionId es null)
    // ---------------------------------------------------------------

    /** Cuotas base, ahorro extra, aportes de ronda y de rifa cobrados. */
    public function cuotas(int $reunionId = null): array {
        $sql = "SELECT IFNULL(SUM(CASE WHEN cuota_pagada = 1 THEN :ahorro ELSE 0 END), 0) AS cuotas_base,
                       IFNULL(SUM(monto_ahorro_extra), 0) AS ahorro_extra,
                       IFNULL(SUM(monto_aporte_ronda), 0) AS aporte_ronda,
                       IFNULL(SUM(monto_aporte_rifa), 0) AS aporte_rifa,
                       COUNT(*) AS registros
                FROM natillera_ahorros_cuotas" . ($reunionId !== null ? " WHERE reunion_id = :r" : "");
        $p = [':ahorro' => self::ahorroPorCuota()];
        if ($reunionId !== null) { $p[':r'] = $reunionId; }
        $st = $this->db->prepare($sql);
        $st->execute($p);
        $f = $st->fetch();
        return [
            'cuotas_base'  => (float)$f['cuotas_base'],
            'ahorro_extra' => (float)$f['ahorro_extra'],
            'aporte_ronda' => (float)$f['aporte_ronda'],
            'aporte_rifa'  => (float)$f['aporte_rifa'],
            'registros'    => (int)$f['registros'],
        ];
    }

    /** Premios de ronda y de rifa realmente entregados (monto y cantidad). */
    public function premios(int $reunionId = null): array {
        $sql = "SELECT tipo_beneficio, IFNULL(SUM(monto_entregado), 0) AS monto, COUNT(*) AS cant
                FROM natillera_entregas_beneficios
                WHERE tipo_beneficio IN ('RONDA','RIFA')" . ($reunionId !== null ? " AND reunion_id = :r" : "") . "
                GROUP BY tipo_beneficio";
        $st = $this->db->prepare($sql);
        $st->execute($reunionId !== null ? [':r' => $reunionId] : []);
        $out = ['ronda' => 0.0, 'rifa' => 0.0, 'ronda_cant' => 0, 'rifa_cant' => 0];
        foreach ($st->fetchAll() as $f) {
            $k = strtolower($f['tipo_beneficio']);
            $out[$k] = (float)$f['monto'];
            $out[$k . '_cant'] = (int)$f['cant'];
        }
        return $out;
    }

    /** Prestamos desembolsados (entregas tipo PRESTAMO), sin los de prestamos ANULADOS. */
    public function prestamosEntregados(int $reunionId = null): float {
        $sql = "SELECT IFNULL(SUM(eb.monto_entregado), 0)
                FROM natillera_entregas_beneficios eb
                LEFT JOIN natillera_prestamos p ON eb.prestamo_id = p.id
                WHERE eb.tipo_beneficio = 'PRESTAMO' AND (p.id IS NULL OR p.estado <> 'ANULADO')"
              . ($reunionId !== null ? " AND eb.reunion_id = :r" : "");
        $st = $this->db->prepare($sql);
        $st->execute($reunionId !== null ? [':r' => $reunionId] : []);
        return (float)$st->fetchColumn();
    }

    /** Saldo reservado de ronda y rifa (aportes cobrados - premios entregados) hasta una reunion inclusive. */
    public function reservadosHasta(int $numeroQuincena): array {
        $st = $this->db->prepare("SELECT IFNULL(SUM(ac.monto_aporte_ronda), 0) AS ar, IFNULL(SUM(ac.monto_aporte_rifa), 0) AS af
                                  FROM natillera_ahorros_cuotas ac JOIN natillera_reuniones r ON ac.reunion_id = r.id
                                  WHERE r.numero_quincena <= :q");
        $st->execute([':q' => $numeroQuincena]);
        $ap = $st->fetch();
        $st = $this->db->prepare("SELECT eb.tipo_beneficio, IFNULL(SUM(eb.monto_entregado), 0) AS m
                                  FROM natillera_entregas_beneficios eb JOIN natillera_reuniones r ON eb.reunion_id = r.id
                                  WHERE eb.tipo_beneficio IN ('RONDA','RIFA') AND r.numero_quincena <= :q GROUP BY eb.tipo_beneficio");
        $st->execute([':q' => $numeroQuincena]);
        $pr = ['RONDA' => 0.0, 'RIFA' => 0.0];
        foreach ($st->fetchAll() as $f) { $pr[$f['tipo_beneficio']] = (float)$f['m']; }
        return ['ronda' => (float)$ap['ar'] - $pr['RONDA'], 'rifa' => (float)$ap['af'] - $pr['RIFA']];
    }

    // ---------------------------------------------------------------
    // Resumen global
    // ---------------------------------------------------------------

    public function resumen(): array {
        $c = $this->cuotas();
        $premios = $this->premios();
        $prestamosEntregados = $this->prestamosEntregados();

        $ab = $this->db->query("SELECT IFNULL(SUM(monto_capital_pagado), 0) AS capital, IFNULL(SUM(monto_interes_pagado), 0) AS interes
                                FROM natillera_abonos_prestamos")->fetch();
        $abonoCapital = (float)$ab['capital'];
        $intereses = (float)$ab['interes'];

        $iny = $this->db->query("SELECT
                IFNULL(SUM(monto_inyectado), 0) AS recibidas,
                IFNULL(SUM(CASE WHEN estado = 'ACTIVA' THEN monto_inyectado ELSE 0 END), 0) AS activas,
                IFNULL(SUM(CASE WHEN estado = 'RETIRADA' THEN monto_inyectado + IFNULL(monto_rendimiento_generado, 0) ELSE 0 END), 0) AS devueltas
                FROM natillera_inyecciones_capital")->fetch();

        $tr = $this->db->query("SELECT
                IFNULL(SUM(CASE WHEN tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR' THEN monto ELSE 0 END), 0) AS devoluciones,
                IFNULL(SUM(CASE WHEN tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD' THEN monto ELSE 0 END), 0) AS prestamos_act
                FROM natillera_transferencias_cajas")->fetch();

        $cartera = $this->db->query("
            SELECT COALESCE(SUM(p.monto_prestado), 0) AS total_prestado,
                   COALESCE(SUM(CASE WHEN p.estado = 'ACTIVO' THEN (p.monto_prestado - COALESCE(ab.total_pagado, 0)) ELSE 0 END), 0) AS cartera_pendiente
            FROM natillera_prestamos p
            LEFT JOIN (SELECT prestamo_id, SUM(monto_capital_pagado) AS total_pagado FROM natillera_abonos_prestamos GROUP BY prestamo_id) ab
                   ON p.id = ab.prestamo_id
            WHERE p.estado <> 'ANULADO'")->fetch();

        $inyRecibidas = (float)$iny['recibidas'];
        $inyActivas = (float)$iny['activas'];
        $inyDevueltas = (float)$iny['devueltas'];
        $devolucionesAct = (float)$tr['devoluciones'];
        $prestamosAct = (float)$tr['prestamos_act'];

        $ingresos = $c['cuotas_base'] + $c['ahorro_extra'] + $c['aporte_ronda'] + $c['aporte_rifa']
                  + $abonoCapital + $intereses + $devolucionesAct + $inyRecibidas;
        $egresos = $prestamosEntregados + $premios['ronda'] + $premios['rifa'] + $inyDevueltas + $prestamosAct;
        $caja = $ingresos - $egresos;

        $reservadoRonda = $c['aporte_ronda'] - $premios['ronda'];
        $reservadoRifa = $c['aporte_rifa'] - $premios['rifa'];
        $disponible = $caja - $reservadoRonda - $reservadoRifa;

        return [
            'caja_total'           => $caja,
            'reservado_ronda'      => $reservadoRonda,
            'reservado_rifa'       => $reservadoRifa,
            'disponible_prestar'   => $disponible,
            'revisar_datos'        => ($caja < 0 || $reservadoRonda < 0 || $reservadoRifa < 0 || $disponible < 0),
            'ingresos_totales'     => $ingresos,
            'egresos_totales'      => $egresos,
            'ahorro_obligatorio'   => $c['cuotas_base'],
            'ahorro_extra'         => $c['ahorro_extra'],
            'aportes_ronda'        => $c['aporte_ronda'],
            'aportes_rifa'         => $c['aporte_rifa'],
            'abono_capital'        => $abonoCapital,
            'intereses'            => $intereses,
            'devoluciones_actividades' => $devolucionesAct,
            'inyecciones_recibidas' => $inyRecibidas,
            'inyecciones_activas'  => $inyActivas,
            'inyecciones_devueltas' => $inyDevueltas,
            'prestamos_actividades' => $prestamosAct,
            'premios_ronda'        => $premios['ronda'],
            'premios_rifa'         => $premios['rifa'],
            'premios_entregados'   => $premios['ronda'] + $premios['rifa'],
            'prestamos_entregados' => $prestamosEntregados,
            'prestamos_total_historico' => (float)$cartera['total_prestado'],
            'cartera_pendiente'    => (float)$cartera['cartera_pendiente'],
        ];
    }

    // ---------------------------------------------------------------
    // Caja segun la cadena de cierres
    // ---------------------------------------------------------------

    /**
     * Caja segun los cierres: ultimo saldo_acumulado_caja + flujo neto de las reuniones aun sin cierre que ya
     * tienen movimientos. Debe coincidir con resumen()['caja_total']; la diferencia se explica por movimientos
     * sin reunion asignada (abonos/inyecciones/transferencias con reunion_id NULL).
     */
    public function cajaPorCierres(): array {
        $ultimo = $this->db->query("SELECT cr.saldo_acumulado_caja, r.numero_quincena
                                    FROM natillera_cierres_reunion cr JOIN natillera_reuniones r ON cr.reunion_id = r.id
                                    ORDER BY r.numero_quincena DESC LIMIT 1")->fetch();
        $saldoCierres = $ultimo ? (float)$ultimo['saldo_acumulado_caja'] : 0.0;

        $abiertas = $this->db->query("
            SELECT r.id FROM natillera_reuniones r
            WHERE NOT EXISTS (SELECT 1 FROM natillera_cierres_reunion cr WHERE cr.reunion_id = r.id)
              AND (EXISTS (SELECT 1 FROM natillera_ahorros_cuotas x WHERE x.reunion_id = r.id)
                OR EXISTS (SELECT 1 FROM natillera_entregas_beneficios x WHERE x.reunion_id = r.id)
                OR EXISTS (SELECT 1 FROM natillera_abonos_prestamos x WHERE x.reunion_id = r.id)
                OR EXISTS (SELECT 1 FROM natillera_inyecciones_capital x WHERE x.reunion_id = r.id OR x.reunion_id_retiro = r.id)
                OR EXISTS (SELECT 1 FROM natillera_transferencias_cajas x WHERE x.reunion_id = r.id))
            ORDER BY r.numero_quincena ASC")->fetchAll();

        require_once __DIR__ . '/CierreReunion.php';
        $cierre = new CierreReunion();
        $flujoAbiertas = 0.0;
        foreach ($abiertas as $a) {
            $res = $cierre->calcularResumenReunion((int)$a['id']);
            $flujoAbiertas += (float)$res['saldo_neto_reunion'];
        }
        return [
            'ultimo_cierre'   => $saldoCierres,
            'flujo_abiertas'  => $flujoAbiertas,
            'caja_cierres'    => $saldoCierres + $flujoAbiertas,
            'reuniones_abiertas' => count($abiertas),
        ];
    }

    /** Diferencia caja global - caja por cierres (0 = cuadra). */
    public function diferenciaConCierres(): float {
        $r = $this->resumen();
        $c = $this->cajaPorCierres();
        return round($r['caja_total'] - $c['caja_cierres'], 2);
    }
}
