<?php
// app/Models/FondoBeneficio.php
// Fondos de ronda y rifa: plan por reunion (cronograma) frente a lo real (aportes cobrados y premios entregados).
// Nada de numeros de negocio escritos aqui: cupos, cuotas y premios salen de config/natillera.php.
// Compatible con PHP 7.2.

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Money.php';

class FondoBeneficio extends Model {

    const TIPOS = ['RONDA', 'RIFA'];

    // ---------------------------------------------------------------
    // Configuracion
    // ---------------------------------------------------------------

    public static function cupos(): int {
        return max(1, (int)Money::config('cupos', 50));
    }

    public static function premio(string $tipo): float {
        return (float)Money::config(strtolower($tipo) . '.premio', $tipo === 'RONDA' ? 300000 : 150000);
    }

    /** Aporte base por socio y reunion de cada fondo (ronda_base / rifa_base). */
    public static function aporteBase(string $tipo): float {
        return (float)Money::config('cuota.' . strtolower($tipo) . '_base', $tipo === 'RONDA' ? 10000 : 5000);
    }

    /** Sobrecuota de las reuniones con extra de cada fondo. */
    public static function aporteExtra(string $tipo): float {
        return (float)Money::config('cuota.extra_' . strtolower($tipo), $tipo === 'RONDA' ? 10000 : 5000);
    }

    // ---------------------------------------------------------------
    // Calculos puros (sin base de datos)
    // ---------------------------------------------------------------

    /**
     * Un paso del fondo en una reunion. Ej. premio 300.000, cupos 50, aporte 10.000 (=500.000):
     * 500 -> 1 persona, queda 200; 700 -> 2, queda 100; 600 -> 2, queda 0.
     *
     * @param int|null $planificadas personas que se planea entregar (null = el maximo que alcanza)
     */
    public static function liberar(float $saldoAnterior, float $recaudo, float $premio, $planificadas = null): array {
        $disponible = $saldoAnterior + $recaudo;
        $maximo = $premio > 0 ? (int)floor(($disponible + 0.0001) / $premio) : 0;
        $personas = ($planificadas === null) ? $maximo : max(0, min((int)$planificadas, $maximo));
        $entregado = $personas * $premio;
        return [
            'disponible' => $disponible,
            'maximo'     => $maximo,
            'personas'   => $personas,
            'entregado'  => $entregado,
            'queda'      => $disponible - $entregado,
        ];
    }

    /**
     * Aportes por socio (ronda, rifa) que corresponden a una cuota cuando la reunion no los tiene guardados.
     * El total siempre cuadra: aporte_ronda + aporte_rifa = cuota - ahorro. Un extra >= extra_ronda va a la ronda,
     * uno menor a la rifa. Una cuota absurda (dato erroneo) se trata como cuota base.
     */
    public static function aportesPorDefecto(float $cuota): array {
        $ronda = self::aporteBase('RONDA');
        $rifa = self::aporteBase('RIFA');
        $extra = $cuota - (float)Money::config('cuota.base_total', 55000);
        $maxExtra = (self::aporteExtra('RONDA') + self::aporteExtra('RIFA')) * (float)Money::config('fondos.extra_max_factor', 2);
        if ($extra > 0 && $extra <= $maxExtra) {
            if ($extra >= self::aporteExtra('RONDA')) {
                $ronda += $extra;
            } else {
                $rifa += $extra;
            }
        }
        return ['ronda' => $ronda, 'rifa' => $rifa];
    }

    /** Aportes de una fila de natillera_reuniones: los guardados o, si faltan, los derivados de la cuota. */
    public static function aportesDeReunion(array $reunion): array {
        $def = self::aportesPorDefecto((float)($reunion['valor_cuota_base'] ?? Money::config('cuota.base_total', 55000)));
        return [
            'ronda' => isset($reunion['aporte_ronda']) && $reunion['aporte_ronda'] !== '' ? (float)$reunion['aporte_ronda'] : $def['ronda'],
            'rifa'  => isset($reunion['aporte_rifa']) && $reunion['aporte_rifa'] !== '' ? (float)$reunion['aporte_rifa'] : $def['rifa'],
        ];
    }

    /**
     * Sugiere en que reuniones poner la cuota extra de ronda y de rifa para que cada fondo termine en $0 en la
     * ultima reunion con todos los cupos servidos. Las filas "bloqueadas" (reuniones cerradas) conservan sus valores.
     *
     * @param array $filas cada fila: ['bloqueada'=>bool, 'aporte_ronda'=>?float, 'aporte_rifa'=>?float,
     *                     'personas_ronda'=>?int, 'personas_rifa'=>?int]  (los valores solo importan si bloqueada)
     * @param int|null $cupos socios del ciclo (por defecto config('cupos'))
     */
    public static function sugerirDistribucion(array $filas, $cupos = null): array {
        $cupos = $cupos === null ? self::cupos() : max(1, (int)$cupos);
        $n = count($filas);
        $baseCuota = (float)Money::config('cuota.base_total', 55000);
        $ahorro = (float)Money::config('cuota.ahorro', 40000);
        $sep = max(1, (int)Money::config('fondos.separacion_min_extras', 2));
        $mensajes = [];

        $res = [];
        $libres = [];
        foreach ($filas as $i => $f) {
            $bloq = !empty($f['bloqueada']);
            $res[$i] = [
                'bloqueada' => $bloq,
                'extra' => 'NINGUNO',
                'aporte_ronda' => $bloq && isset($f['aporte_ronda']) ? (float)$f['aporte_ronda'] : self::aporteBase('RONDA'),
                'aporte_rifa'  => $bloq && isset($f['aporte_rifa']) ? (float)$f['aporte_rifa'] : self::aporteBase('RIFA'),
                'personas_ronda_fijas' => $bloq && isset($f['personas_ronda']) ? (int)$f['personas_ronda'] : null,
                'personas_rifa_fijas'  => $bloq && isset($f['personas_rifa']) ? (int)$f['personas_rifa'] : null,
            ];
            if (!$bloq) { $libres[] = $i; }
        }
        $m = count($libres);

        $extrasNecesarios = [];
        foreach (self::TIPOS as $tipo) {
            $k = strtolower($tipo);
            $recaudoBase = 0.0;
            foreach ($res as $r) { $recaudoBase += $r['aporte_' . $k] * $cupos; }
            $faltante = $cupos * self::premio($tipo) - $recaudoBase;
            $porExtra = self::aporteExtra($tipo) * $cupos;
            $cant = ($faltante > 0.0001 && $porExtra > 0) ? (int)ceil(($faltante - 0.0001) / $porExtra) : 0;
            if ($cant > $m) {
                $mensajes[] = "No alcanzan las reuniones libres para las cuotas extra de " . strtolower($tipo) . " (se necesitan {$cant}, hay {$m}).";
                $cant = $m;
            }
            $extrasNecesarios[$tipo] = $cant;
        }

        // Colocacion: posiciones espaciadas parejo, la rifa desfasada respecto de la ronda, sin coincidir.
        $usadas = [];       // posicion (indice dentro de $libres) => tipo
        $colocar = function ($tipo, $cant, $desfase) use (&$usadas, $m, $sep) {
            for ($j = 0; $j < $cant; $j++) {
                $objetivo = ($j + $desfase) * $m / max(1, $cant);
                $mejor = null; $mejorClave = null;
                for ($p = 0; $p < $m; $p++) {
                    if (isset($usadas[$p])) { continue; }
                    $minDist = $m;
                    foreach ($usadas as $q => $_) { $minDist = min($minDist, abs($p - $q)); }
                    $clave = [min($minDist, $sep) * -1, abs($p - $objetivo), $p];
                    if ($mejorClave === null || $clave < $mejorClave) { $mejorClave = $clave; $mejor = $p; }
                }
                if ($mejor !== null) { $usadas[$mejor] = $tipo; }
            }
        };
        $colocar('RONDA', $extrasNecesarios['RONDA'], 0.25);
        $colocar('RIFA', $extrasNecesarios['RIFA'], 0.75);

        foreach ($usadas as $p => $tipo) {
            $i = $libres[$p];
            $res[$i]['extra'] = $tipo;
            if ($tipo === 'RONDA') { $res[$i]['aporte_ronda'] += self::aporteExtra('RONDA'); }
            else { $res[$i]['aporte_rifa'] += self::aporteExtra('RIFA'); }
        }

        // Personas por reunion: el maximo que alcanza sin pasar de los cupos
        $saldo = ['RONDA' => 0.0, 'RIFA' => 0.0];
        $servidos = ['RONDA' => 0, 'RIFA' => 0];
        foreach ($res as $i => &$r) {
            foreach (self::TIPOS as $tipo) {
                $k = strtolower($tipo);
                $fijas = $r['personas_' . $k . '_fijas'];
                $restantes = max(0, $cupos - $servidos[$tipo]);
                $paso = self::liberar($saldo[$tipo], $r['aporte_' . $k] * $cupos, self::premio($tipo), $fijas === null ? $restantes : min($fijas, $restantes));
                $r['personas_' . $k] = $paso['personas'];
                $r['saldo_' . $k] = $paso['queda'];
                $saldo[$tipo] = $paso['queda'];
                $servidos[$tipo] += $paso['personas'];
            }
            $r['cuota'] = $ahorro + $r['aporte_ronda'] + $r['aporte_rifa'];
            unset($r['personas_ronda_fijas'], $r['personas_rifa_fijas']);
        }
        unset($r);

        $ok = true;
        foreach (self::TIPOS as $tipo) {
            if (abs($saldo[$tipo]) > 0.01 || $servidos[$tipo] !== $cupos) {
                $ok = false;
                $mensajes[] = ucfirst(strtolower($tipo)) . ": termina con saldo $" . number_format($saldo[$tipo], 0, ',', '.')
                            . " y " . $servidos[$tipo] . " de " . $cupos . " servidos.";
            }
        }

        // Separacion minima real entre cualquier par de reuniones con extra
        $pos = array_keys($usadas);
        sort($pos);
        $minSep = null;
        for ($a = 1; $a < count($pos); $a++) {
            $d = $pos[$a] - $pos[$a - 1];
            $minSep = $minSep === null ? $d : min($minSep, $d);
        }

        return [
            'filas' => array_values($res),
            'cupos' => $cupos,
            'reuniones' => $n,
            'extras_ronda' => $extrasNecesarios['RONDA'],
            'extras_rifa' => $extrasNecesarios['RIFA'],
            'separacion_minima' => $minSep,
            'servidos_ronda' => $servidos['RONDA'],
            'servidos_rifa' => $servidos['RIFA'],
            'saldo_final_ronda' => $saldo['RONDA'],
            'saldo_final_rifa' => $saldo['RIFA'],
            'ok' => $ok,
            'mensajes' => $mensajes,
        ];
    }

    // ---------------------------------------------------------------
    // Cronograma (plan)
    // ---------------------------------------------------------------

    private function totalReuniones(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM natillera_reuniones")->fetchColumn();
    }

    /** Genera el cronograma la primera vez, o si hay reuniones sin filas (por ejemplo un ciclo recien creado). */
    public function generarCronogramaCompleto(): void {
        $filas = (int)$this->db->query("SELECT COUNT(*) FROM natillera_fondo_beneficios_cronograma")->fetchColumn();
        if ($filas < $this->totalReuniones() * count(self::TIPOS)) {
            $this->regenerarCronograma();
        }
    }

    /**
     * Recalcula el plan con lo guardado en cada reunion (aporte_ronda, aporte_rifa, premios_*_planificados;
     * si faltan, se derivan de la cuota). Las reuniones CERRADAS conservan su fila ya calculada, salvo que
     * $incluirCerradas sea true (carga inicial del ciclo).
     */
    public function regenerarCronograma(bool $incluirCerradas = false): void {
        $reuniones = $this->db->query("SELECT * FROM natillera_reuniones ORDER BY numero_quincena ASC")->fetchAll();
        if (empty($reuniones)) {
            return;
        }
        $existentes = [];
        foreach ($this->db->query("SELECT * FROM natillera_fondo_beneficios_cronograma")->fetchAll() as $f) {
            $existentes[(int)$f['reunion_id']][$f['tipo_beneficio']] = $f;
        }

        $cupos = self::cupos();
        $propia = !$this->db->inTransaction();
        if ($propia) { $this->db->beginTransaction(); }
        try {
            $ins = $this->db->prepare("
                INSERT INTO natillera_fondo_beneficios_cronograma
                (reunion_id, tipo_beneficio, aporte_por_socio, total_recaudado, monto_beneficio_unidad, saldo_restante_reunion, saldo_acumulado_fondo, personas_liberadas_planificadas)
                VALUES (:reunion_id, :tipo, :aporte, :recaudo, :unidad, :restante, :acumulado, :personas)");
            $upd = $this->db->prepare("
                UPDATE natillera_fondo_beneficios_cronograma
                SET aporte_por_socio = :aporte, total_recaudado = :recaudo, monto_beneficio_unidad = :unidad,
                    saldo_restante_reunion = :restante, saldo_acumulado_fondo = :acumulado, personas_liberadas_planificadas = :personas
                WHERE id = :id");

            $acum = ['RONDA' => 0.0, 'RIFA' => 0.0];
            foreach ($reuniones as $r) {
                $rid = (int)$r['id'];
                $cerrada = ($r['estado'] === 'CERRADA');
                $aportes = self::aportesDeReunion($r);
                foreach (self::TIPOS as $tipo) {
                    $k = strtolower($tipo);
                    $previa = $existentes[$rid][$tipo] ?? null;
                    if ($cerrada && !$incluirCerradas && $previa) {
                        $acum[$tipo] = (float)$previa['saldo_acumulado_fondo'];
                        continue;
                    }
                    $plan = $r['premios_' . $k . '_planificados'] ?? null;
                    $paso = self::liberar($acum[$tipo], $aportes[$k] * $cupos, self::premio($tipo), ($plan === null || $plan === '') ? null : (int)$plan);
                    $acum[$tipo] = $paso['queda'];
                    $p = [
                        ':aporte' => $aportes[$k], ':recaudo' => $aportes[$k] * $cupos, ':unidad' => self::premio($tipo),
                        ':restante' => $paso['queda'], ':acumulado' => $paso['queda'], ':personas' => $paso['personas'],
                    ];
                    if ($previa) {
                        $p[':id'] = $previa['id'];
                        $upd->execute($p);
                    } else {
                        $p[':reunion_id'] = $rid;
                        $p[':tipo'] = $tipo;
                        $ins->execute($p);
                    }
                }
            }
            if ($propia) { $this->db->commit(); }
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
    }

    /**
     * Cronograma con lo real al lado: aporte cobrado, premios entregados y saldo reservado real acumulado.
     * Cada fila: plan (aporte_por_socio, total_recaudado, personas_liberadas_planificadas, saldo_acumulado_fondo)
     * y real (real_cobrado, entregas_reales_count, entregas_reales_monto, saldo_real_acumulado).
     */
    public function getCronograma(string $tipoFilter = null): array {
        $this->generarCronogramaCompleto();

        $sql = "
            SELECT fbc.*, r.numero_quincena, r.fecha_reunion, r.valor_cuota_base, r.estado AS reunion_estado,
                   (SELECT COUNT(*) FROM natillera_entregas_beneficios WHERE reunion_id = fbc.reunion_id AND tipo_beneficio = fbc.tipo_beneficio) AS entregas_reales_count,
                   (SELECT IFNULL(SUM(monto_entregado), 0) FROM natillera_entregas_beneficios WHERE reunion_id = fbc.reunion_id AND tipo_beneficio = fbc.tipo_beneficio) AS entregas_reales_monto,
                   (SELECT IFNULL(SUM(CASE WHEN fbc.tipo_beneficio = 'RONDA' THEN monto_aporte_ronda ELSE monto_aporte_rifa END), 0)
                      FROM natillera_ahorros_cuotas WHERE reunion_id = fbc.reunion_id) AS real_cobrado,
                   (SELECT COUNT(*) FROM natillera_ahorros_cuotas WHERE reunion_id = fbc.reunion_id) AS real_registros
            FROM natillera_fondo_beneficios_cronograma fbc
            JOIN natillera_reuniones r ON fbc.reunion_id = r.id
        ";
        if ($tipoFilter) {
            $sql .= " WHERE fbc.tipo_beneficio = :tipo ";
        }
        $sql .= " ORDER BY r.numero_quincena ASC, fbc.tipo_beneficio DESC ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($tipoFilter ? [':tipo' => $tipoFilter] : []);
        $filas = $stmt->fetchAll();

        $acum = ['RONDA' => 0.0, 'RIFA' => 0.0];
        foreach ($filas as &$f) {
            $t = $f['tipo_beneficio'];
            $acum[$t] += (float)$f['real_cobrado'] - (float)$f['entregas_reales_monto'];
            $f['saldo_real_acumulado'] = $acum[$t];
        }
        unset($f);
        return $filas;
    }

    /** Fondos reservados reales + totales de plan y de entregas, para tarjetas y vistas. */
    public function getResumenFondos(): array {
        $this->generarCronogramaCompleto();
        $cupos = self::cupos();

        $out = ['cupos' => $cupos];
        foreach (self::TIPOS as $tipo) {
            $k = strtolower($tipo);
            $plan = $this->db->prepare("SELECT IFNULL(SUM(total_recaudado), 0) AS rec, IFNULL(SUM(personas_liberadas_planificadas), 0) AS pers
                                         FROM natillera_fondo_beneficios_cronograma WHERE tipo_beneficio = :t");
            $plan->execute([':t' => $tipo]);
            $p = $plan->fetch();

            $ent = $this->db->prepare("SELECT COUNT(*) AS c, IFNULL(SUM(monto_entregado), 0) AS m FROM natillera_entregas_beneficios WHERE tipo_beneficio = :t");
            $ent->execute([':t' => $tipo]);
            $e = $ent->fetch();

            $col = $tipo === 'RONDA' ? 'monto_aporte_ronda' : 'monto_aporte_rifa';
            $cob = (float)$this->db->query("SELECT IFNULL(SUM({$col}), 0) FROM natillera_ahorros_cuotas")->fetchColumn();

            $out[$k] = [
                'total_recaudado'   => $cob,                       // cobrado real
                'plan_recaudado'    => (float)$p['rec'],
                'planificados'      => (int)$p['pers'],
                'entregados_count'  => (int)$e['c'],
                'entregados_monto'  => (float)$e['m'],
                'reservado'         => $cob - (float)$e['m'],      // saldo reservado real
                'premio'            => self::premio($tipo),
            ];
        }

        $stmtP = $this->db->query("SELECT IFNULL(COUNT(*), 0) AS c, IFNULL(SUM(monto_entregado), 0) AS m
                                   FROM natillera_entregas_beneficios WHERE tipo_beneficio = 'PRESTAMO'");
        $pr = $stmtP->fetch();
        $out['prestamo'] = ['entregados_count' => (int)$pr['c'], 'entregados_monto' => (float)$pr['m']];
        return $out;
    }
}
