<?php
// app/Models/Reunion.php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/PagoNotificacion.php';
require_once __DIR__ . '/CajaGlobal.php';
require_once __DIR__ . '/FondoBeneficio.php';

class Reunion extends Model {

    /** Motivo del último rechazo/fallo de anularAutoprestamo24Horas. */
    public $ultimoError = '';

    public function getReuniones(): array {
        $stmt = $this->db->query("
            SELECT r.*, u.nombre_completo as ganador_nombre
            FROM natillera_reuniones r
            LEFT JOIN natillera_usuarios u ON r.ganador_socio_id = u.id
            ORDER BY 
                CASE 
                    WHEN r.estado = 'LLAMADO_CERRADO' THEN 1 
                    WHEN r.estado = 'EN_PROCESO' THEN 2
                    WHEN r.estado = 'PROGRAMADA' THEN 3
                    ELSE 4 
                END,
                r.numero_quincena ASC
        ");
        return $stmt->fetchAll();
    }

    public function getReunionActual() {
        // Prioridad 1: Reunión que tenga el llamado a lista cerrado (LLAMADO_CERRADO)
        $stmtLlamado = $this->db->query("
            SELECT r.* 
            FROM natillera_reuniones r
            WHERE r.estado = 'LLAMADO_CERRADO'
            ORDER BY r.numero_quincena DESC 
            LIMIT 1
        ");
        $reunion = $stmtLlamado->fetch();

        if (!$reunion) {
            // Prioridad 2: Primera quincena sin cerrar y sin llamado
            $stmt = $this->db->query("
                SELECT r.* 
                FROM natillera_reuniones r
                WHERE r.estado != 'CERRADA'
                  AND r.id NOT IN (
                      SELECT DISTINCT reunion_id FROM natillera_ahorros_cuotas
                  )
                ORDER BY r.numero_quincena ASC 
                LIMIT 1
            ");
            $reunion = $stmt->fetch();
        }

        if (!$reunion) {
            $stmtPending = $this->db->query("
                SELECT r.* FROM natillera_reuniones r
                WHERE r.estado IN ('PROGRAMADA', 'EN_PROCESO')
                ORDER BY r.numero_quincena ASC LIMIT 1
            ");
            $reunion = $stmtPending->fetch();
        }

        if (!$reunion) {
            $stmtLast = $this->db->query("SELECT * FROM natillera_reuniones ORDER BY numero_quincena DESC LIMIT 1");
            $reunion = $stmtLast->fetch();
        }

        return $reunion ?: null;
    }

    public function getReunionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM natillera_reuniones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Id de la ultima reunion creada con crearReunion(). */
    public $ultimoId = 0;

    /** Convierte '' / null en NULL y lo demas en float (campos de fondos opcionales). */
    private static function nullableFloat($v) {
        return ($v === null || $v === '') ? null : (float)$v;
    }

    private static function nullableInt($v) {
        return ($v === null || $v === '') ? null : max(0, (int)$v);
    }

    public function crearReunion(array $datos): bool {
        // Calcular número de quincena automáticamente
        $stmtMax = $this->db->query("SELECT IFNULL(MAX(numero_quincena), 0) + 1 as siguiente FROM natillera_reuniones");
        $siguienteNum = (int)$stmtMax->fetch()['siguiente'];

        $stmt = $this->db->prepare("
            INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado,
                                             aporte_ronda, aporte_rifa, premios_ronda_planificados, premios_rifa_planificados)
            VALUES (:num, :fecha, :hora, :cuota, :evento, :premio, 'PROGRAMADA', :ap_ronda, :ap_rifa, :pr_ronda, :pr_rifa)
        ");
        $ok = $stmt->execute([
            ':num' => $siguienteNum,
            ':fecha' => $datos['fecha_reunion'],
            ':hora' => $datos['hora_reunion'] ?? '14:00:00',
            ':cuota' => $datos['valor_cuota_base'] ?? CajaGlobal::cfg('cuota.base_total', 55000),
            ':evento' => $datos['tipo_evento_extra'] ?? 'NINGUNO',
            ':premio' => $datos['monto_premio_extra'] ?? 0.00,
            ':ap_ronda' => self::nullableFloat($datos['aporte_ronda'] ?? null),
            ':ap_rifa' => self::nullableFloat($datos['aporte_rifa'] ?? null),
            ':pr_ronda' => self::nullableInt($datos['premios_ronda_planificados'] ?? null),
            ':pr_rifa' => self::nullableInt($datos['premios_rifa_planificados'] ?? null)
        ]);
        $this->ultimoId = $ok ? (int)$this->db->lastInsertId() : 0;
        if ($ok) {
            $this->refrescarCronograma();
        }
        return $ok;
    }

    public function actualizarReunion(int $id, array $datos): bool {
        $actual = $this->getReunionById($id);
        if (!$actual) {
            return false;
        }
        $cerrada = ($actual['estado'] === 'CERRADA');

        $sets = "fecha_reunion = :fecha, hora_reunion = :hora, valor_cuota_base = :cuota, tipo_evento_extra = :evento,
                 monto_premio_extra = :premio, ganador_socio_id = :ganador, estado = :estado";
        $params = [
            ':id' => $id,
            ':fecha' => $datos['fecha_reunion'],
            ':hora' => $datos['hora_reunion'] ?? '14:00:00',
            ':cuota' => $datos['valor_cuota_base'],
            ':evento' => $datos['tipo_evento_extra'] ?? 'NINGUNO',
            ':premio' => $datos['monto_premio_extra'] ?? 0.00,
            ':ganador' => !empty($datos['ganador_socio_id']) ? $datos['ganador_socio_id'] : null,
            ':estado' => $datos['estado'] ?? 'PROGRAMADA'
        ];
        // Una reunión CERRADA no cambia sus fondos planificados ni su cuota (el cierre ya consolidó la caja)
        if ($cerrada) {
            $sets = "fecha_reunion = :fecha, hora_reunion = :hora, tipo_evento_extra = :evento, monto_premio_extra = :premio,
                     ganador_socio_id = :ganador, estado = :estado";
            unset($params[':cuota']);
        } elseif (array_key_exists('aporte_ronda', $datos) || array_key_exists('premios_ronda_planificados', $datos)) {
            $sets .= ", aporte_ronda = :ap_ronda, aporte_rifa = :ap_rifa, premios_ronda_planificados = :pr_ronda, premios_rifa_planificados = :pr_rifa";
            $params[':ap_ronda'] = self::nullableFloat($datos['aporte_ronda'] ?? null);
            $params[':ap_rifa'] = self::nullableFloat($datos['aporte_rifa'] ?? null);
            $params[':pr_ronda'] = self::nullableInt($datos['premios_ronda_planificados'] ?? null);
            $params[':pr_rifa'] = self::nullableInt($datos['premios_rifa_planificados'] ?? null);
        }
        $stmt = $this->db->prepare("UPDATE natillera_reuniones SET {$sets} WHERE id = :id");
        $ok = $stmt->execute($params);
        if ($ok) {
            $this->refrescarCronograma();
        }
        return $ok;
    }

    /** Recalcula el cronograma de fondos (las reuniones cerradas conservan su fila). */
    public function refrescarCronograma(): void {
        try {
            (new FondoBeneficio())->regenerarCronograma();
        } catch (Throwable $e) {
            // el cronograma es un plan derivado: no debe impedir guardar la reunión
            error_log('No se pudo regenerar el cronograma de fondos: ' . $e->getMessage());
        }
    }

    /**
     * Crea las reuniones de un ciclo: una cada N dias (config fondos.dias_entre_reuniones, 14) desde la fecha dada.
     * No duplica: si ya existe una reunion en esa fecha la omite. numero_quincena sigue consecutivo (MAX+1).
     *
     * @param string   $fechaPrimeraReunion  YYYY-MM-DD
     * @param int|null $cantidad             por defecto config('reuniones_por_ciclo')
     * @param array    $opciones             valor_cuota_base, hora_reunion, dias_entre, cupos,
     *                                       extras_ronda / extras_rifa (listas de numeros de reunion 1..N con cuota extra),
     *                                       sugerir_extras (true = reparte las extras con FondoBeneficio::sugerirDistribucion
     *                                       y deja los premios planificados)
     * @return array ['creadas'=>int, 'omitidas'=>int, 'ids'=>int[], 'fechas'=>string[], 'distribucion'=>?array]
     */
    public function generarCiclo(string $fechaPrimeraReunion, int $cantidad = null, array $opciones = []): array {
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaPrimeraReunion);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaPrimeraReunion) {
            throw new InvalidArgumentException('Fecha de la primera reunión inválida (use AAAA-MM-DD).');
        }
        $cantidad = ($cantidad === null) ? (int)CajaGlobal::cfg('reuniones_por_ciclo', 26) : $cantidad;
        if ($cantidad < 1 || $cantidad > 60) {
            throw new InvalidArgumentException('La cantidad de reuniones debe estar entre 1 y 60.');
        }
        $dias = (int)($opciones['dias_entre'] ?? CajaGlobal::cfg('fondos.dias_entre_reuniones', 14));
        if ($dias < 1) { $dias = 14; }
        $hora = $opciones['hora_reunion'] ?? CajaGlobal::cfg('fondos.hora_reunion', '14:00:00');
        $cuotaBase = (float)($opciones['valor_cuota_base'] ?? CajaGlobal::cfg('cuota.base_total', 55000));
        $ahorro = CajaGlobal::ahorroPorCuota();

        // Aportes por reunión del ciclo
        $baseRonda = FondoBeneficio::aporteBase('RONDA');
        $baseRifa = FondoBeneficio::aporteBase('RIFA');
        $extrasRonda = array_map('intval', (array)($opciones['extras_ronda'] ?? []));
        $extrasRifa = array_map('intval', (array)($opciones['extras_rifa'] ?? []));
        $personas = [];
        $distribucion = null;
        if (!empty($opciones['sugerir_extras'])) {
            $distribucion = FondoBeneficio::sugerirDistribucion(array_fill(0, $cantidad, ['bloqueada' => false]), $opciones['cupos'] ?? null);
            foreach ($distribucion['filas'] as $i => $f) {
                if ($f['extra'] === 'RONDA') { $extrasRonda[] = $i + 1; }
                if ($f['extra'] === 'RIFA') { $extrasRifa[] = $i + 1; }
                $personas[$i + 1] = [$f['personas_ronda'], $f['personas_rifa']];
            }
        }
        $conFondos = !empty($extrasRonda) || !empty($extrasRifa) || !empty($personas);

        $resultado = ['creadas' => 0, 'omitidas' => 0, 'ids' => [], 'fechas' => [], 'distribucion' => $distribucion];
        $propia = !$this->db->inTransaction();
        if ($propia) { $this->db->beginTransaction(); }
        try {
            $stmtExiste = $this->db->prepare("SELECT id FROM natillera_reuniones WHERE fecha_reunion = :f LIMIT 1");
            for ($i = 1; $i <= $cantidad; $i++) {
                $f = clone $fecha;
                $f->modify('+' . (($i - 1) * $dias) . ' days');
                $fechaStr = $f->format('Y-m-d');

                $stmtExiste->execute([':f' => $fechaStr]);
                if ($stmtExiste->fetchColumn()) {
                    $resultado['omitidas']++;
                    continue;
                }

                $apRonda = null; $apRifa = null; $cuota = $cuotaBase; $evento = 'NINGUNO';
                if ($conFondos) {
                    $apRonda = $baseRonda + (in_array($i, $extrasRonda, true) ? FondoBeneficio::aporteExtra('RONDA') : 0);
                    $apRifa = $baseRifa + (in_array($i, $extrasRifa, true) ? FondoBeneficio::aporteExtra('RIFA') : 0);
                    $cuota = $ahorro + $apRonda + $apRifa;
                    $evento = in_array($i, $extrasRonda, true) ? 'RONDA' : (in_array($i, $extrasRifa, true) ? 'RIFA' : 'NINGUNO');
                }
                $this->crearReunionSinRefrescar([
                    'fecha_reunion' => $fechaStr,
                    'hora_reunion' => $hora,
                    'valor_cuota_base' => $cuota,
                    'tipo_evento_extra' => $evento,
                    'monto_premio_extra' => 0,
                    'aporte_ronda' => $apRonda,
                    'aporte_rifa' => $apRifa,
                    'premios_ronda_planificados' => isset($personas[$i]) ? $personas[$i][0] : null,
                    'premios_rifa_planificados' => isset($personas[$i]) ? $personas[$i][1] : null,
                ]);
                $resultado['creadas']++;
                $resultado['ids'][] = $this->ultimoId;
                $resultado['fechas'][] = $fechaStr;
            }
            if ($propia) { $this->db->commit(); }
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) { $this->db->rollBack(); }
            throw $e;
        }
        if ($resultado['creadas'] > 0) {
            $this->refrescarCronograma();
        }
        return $resultado;
    }

    private function crearReunionSinRefrescar(array $datos): void {
        $stmtMax = $this->db->query("SELECT IFNULL(MAX(numero_quincena), 0) + 1 AS siguiente FROM natillera_reuniones");
        $num = (int)$stmtMax->fetch()['siguiente'];
        $stmt = $this->db->prepare("
            INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado,
                                             aporte_ronda, aporte_rifa, premios_ronda_planificados, premios_rifa_planificados)
            VALUES (:num, :fecha, :hora, :cuota, :evento, :premio, 'PROGRAMADA', :ap_ronda, :ap_rifa, :pr_ronda, :pr_rifa)");
        $stmt->execute([
            ':num' => $num, ':fecha' => $datos['fecha_reunion'], ':hora' => $datos['hora_reunion'],
            ':cuota' => $datos['valor_cuota_base'], ':evento' => $datos['tipo_evento_extra'], ':premio' => $datos['monto_premio_extra'],
            ':ap_ronda' => self::nullableFloat($datos['aporte_ronda']), ':ap_rifa' => self::nullableFloat($datos['aporte_rifa']),
            ':pr_ronda' => self::nullableInt($datos['premios_ronda_planificados']), ':pr_rifa' => self::nullableInt($datos['premios_rifa_planificados']),
        ]);
        $this->ultimoId = (int)$this->db->lastInsertId();
    }

    public function getAhorrosPorReunion(int $reunionId): array {
        $stmt = $this->db->prepare("
            SELECT ac.*, u.nombre_completo, u.cedula, p.anulado_sin_interes, u.id
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_usuarios u ON ac.socio_id = u.id
            LEFT JOIN natillera_prestamos p ON ac.prestamo_id_asociado = p.id
            WHERE ac.reunion_id = :reunion_id
            ORDER BY u.orden ASC, u.id ASC
        ");
        $stmt->execute([':reunion_id' => $reunionId]);
        return $stmt->fetchAll();
    }

    public function guardarLlamadoListaBatch(int $reunionId, array $registros): bool {
        $this->db->beginTransaction();

        try {
            $stmtReunion = $this->db->prepare("SELECT valor_cuota_base, aporte_ronda, aporte_rifa, numero_quincena, fecha_reunion, estado FROM natillera_reuniones WHERE id = :id FOR UPDATE");
            $stmtReunion->execute([':id' => $reunionId]);
            $reunion = $stmtReunion->fetch();
            if (!$reunion) {
                throw new Exception('La reunión no existe.');
            }
            if (($reunion['estado'] ?? '') === 'CERRADA') {
                throw new Exception('La reunión está CERRADA; no se puede modificar el llamado a lista.');
            }
            $valorCuotaBase = (float)($reunion['valor_cuota_base'] ?? 55000);

            // Aportes de ronda y rifa de ESTA reunion (guardados en la reunion o derivados de la cuota; ver FondoBeneficio)
            $aportesReunion = FondoBeneficio::aportesDeReunion($reunion);
            $aporteRondaBase = $aportesReunion['ronda'];
            $aporteRifaBase = $aportesReunion['rifa'];
            $ahorroNetoConstante = CajaGlobal::ahorroPorCuota(); // Ahorro neto acreditado al socio por cuota (config cuota.ahorro)

            // Consultar autopréstamos previamente registrados en esta reunión para no duplicar
            $stmtPrevP = $this->db->prepare("
                SELECT id, socio_deudor_id 
                FROM natillera_prestamos 
                WHERE reunion_id = :reunion_id AND es_autoprestamo = 1 AND (anulado_sin_interes = 0 OR anulado_sin_interes IS NULL)
            ");
            $stmtPrevP->execute([':reunion_id' => $reunionId]);
            $existingP = $stmtPrevP->fetchAll();
            $existingPMap = [];
            foreach ($existingP as $ep) {
                $existingPMap[(int)$ep['socio_deudor_id']] = (int)$ep['id'];
            }

            // Eliminar registros anteriores de esta reunión si se está re-procesando
            $stmtDelAhorros = $this->db->prepare("DELETE FROM natillera_ahorros_cuotas WHERE reunion_id = :reunion_id");
            $stmtDelAhorros->execute([':reunion_id' => $reunionId]);

            $stmtInsertAhorro = $this->db->prepare("
                INSERT INTO natillera_ahorros_cuotas (reunion_id, socio_id, cuota_pagada, monto_cuota, monto_aporte_ronda, monto_aporte_rifa, monto_ahorro_extra, autoprestamo_generado, prestamo_id_asociado)
                VALUES (:reunion_id, :socio_id, :cuota_pagada, :monto_cuota, :monto_aporte_ronda, :monto_aporte_rifa, :monto_ahorro_extra, :autoprestamo_generado, :prestamo_id_asociado)
                ON DUPLICATE KEY UPDATE cuota_pagada = VALUES(cuota_pagada), monto_cuota = VALUES(monto_cuota),
                    monto_aporte_ronda = VALUES(monto_aporte_ronda), monto_aporte_rifa = VALUES(monto_aporte_rifa),
                    monto_ahorro_extra = VALUES(monto_ahorro_extra), autoprestamo_generado = VALUES(autoprestamo_generado),
                    prestamo_id_asociado = VALUES(prestamo_id_asociado)
            ");

            $stmtInsertPrestamo = $this->db->prepare("
                INSERT INTO natillera_prestamos (socio_deudor_id, reunion_id, monto_prestado, tasa_interes_mensual, tipo_prestamo, es_autoprestamo)
                VALUES (:socio_deudor_id, :reunion_id, :monto_prestado, 10.00, 'AUTOPRESTAMO', 1)
            ");

            $entregadoPor = (int)($_SESSION['usuario']['id'] ?? 1);

            $stmtCheckEntrega = $this->db->prepare("
                SELECT id FROM natillera_entregas_beneficios
                WHERE reunion_id = :reunion_id AND socio_id = :socio_id AND tipo_beneficio = 'PRESTAMO'
                  AND prestamo_id IS NULL
            ");
            $stmtInsertEntrega = $this->db->prepare("
                INSERT INTO natillera_entregas_beneficios (reunion_id, socio_id, tipo_beneficio, monto_entregado, entregado_por_usuario_id)
                VALUES (:reunion_id, :socio_id, 'PRESTAMO', :monto, :entregado_por)
            ");
            $stmtUpdateEntrega = $this->db->prepare("
                UPDATE natillera_entregas_beneficios 
                SET monto_entregado = :monto 
                WHERE id = :id
            ");
            $stmtDelEntrega = $this->db->prepare("
                DELETE FROM natillera_entregas_beneficios 
                WHERE reunion_id = :reunion_id AND socio_id = :socio_id AND tipo_beneficio = 'PRESTAMO'
                  AND prestamo_id IS NULL
                  AND (firma_digital_path IS NULL OR firma_digital_path = '')
                  AND (foto_evidencia_path IS NULL OR foto_evidencia_path = '')
            ");

            // Sin duplicados: si un socio llega repetido queda el ultimo registro (respeta el indice unico reunion+socio)
            $unicos = [];
            foreach ($registros as $reg) {
                if (!is_array($reg) || !isset($reg['socio_id'])) continue;
                $unicos[(int)$reg['socio_id']] = $reg;
            }
            $registros = array_values($unicos);

            foreach ($registros as $reg) {
                $socioId = (int)$reg['socio_id'];
                $pagouCuota = isset($reg['pagou_cuota']) && ($reg['pagou_cuota'] == 1 || $reg['pagou_cuota'] === 'true');
                $ahorroExtra = isset($reg['ahorro_extra']) ? (float)$reg['ahorro_extra'] : 0.0;
                $generarAutoprestamo = isset($reg['generar_autoprestamo']) && ($reg['generar_autoprestamo'] == 1 || $reg['generar_autoprestamo'] === 'true');

                $prestamoId = null;

                if (!$pagouCuota && $generarAutoprestamo) {
                    if (isset($existingPMap[$socioId])) {
                        // Reutilizar el autopréstamo existente para evitar duplicidad
                        $prestamoId = $existingPMap[$socioId];
                        $stmtUpdateP = $this->db->prepare("UPDATE natillera_prestamos SET monto_prestado = :monto WHERE id = :id");
                        $stmtUpdateP->execute([':monto' => $valorCuotaBase, ':id' => $prestamoId]);
                    } else {
                        // Crear nuevo autopréstamo si no existía
                        $stmtInsertPrestamo->execute([
                            ':socio_deudor_id' => $socioId,
                            ':reunion_id' => $reunionId,
                            ':monto_prestado' => $valorCuotaBase
                        ]);
                        $prestamoId = (int)$this->db->lastInsertId();
                    }

                    // Registrar o actualizar automáticamente la constancia de entrega para el autopréstamo
                    $stmtCheckEntrega->execute([':reunion_id' => $reunionId, ':socio_id' => $socioId]);
                    $entId = $stmtCheckEntrega->fetchColumn();
                    if ($entId) {
                        $stmtUpdateEntrega->execute([':monto' => $valorCuotaBase, ':id' => $entId]);
                    } else {
                        $stmtInsertEntrega->execute([
                            ':reunion_id' => $reunionId,
                            ':socio_id' => $socioId,
                            ':monto' => $valorCuotaBase,
                            ':entregado_por' => $entregadoPor
                        ]);
                    }
                } else {
                    // Si ya tenía un autopréstamo pero ahora no se generó (o se pagó la cuota directamente)
                    if (isset($existingPMap[$socioId])) {
                        $oldPId = $existingPMap[$socioId];
                        // Eliminarlo solo si no tiene abonos asociados
                        $stmtCheckAb = $this->db->prepare("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :pid");
                        $stmtCheckAb->execute([':pid' => $oldPId]);
                        if ((int)$stmtCheckAb->fetchColumn() === 0) {
                            $stmtDelP = $this->db->prepare("DELETE FROM natillera_prestamos WHERE id = :pid");
                            $stmtDelP->execute([':pid' => $oldPId]);
                            $stmtDelEntrega->execute([':reunion_id' => $reunionId, ':socio_id' => $socioId]);
                        }
                    }
                }

                // El dinero de la cuotas base ($40k, $10k ronda, $5k rifa) ingresa a la natillera por pago directo o por desembolso de autopréstamo
                $cuotaIngresada = ($pagouCuota || !empty($prestamoId));

                $stmtInsertAhorro->execute([
                    ':reunion_id' => $reunionId,
                    ':socio_id' => $socioId,
                    ':cuota_pagada' => $cuotaIngresada ? 1 : 0,
                    ':monto_cuota' => $cuotaIngresada ? $ahorroNetoConstante : 0.00,
                    ':monto_aporte_ronda' => $cuotaIngresada ? $aporteRondaBase : 0.00,
                    ':monto_aporte_rifa' => $cuotaIngresada ? $aporteRifaBase : 0.00,
                    ':monto_ahorro_extra' => $ahorroExtra,
                    ':autoprestamo_generado' => $prestamoId ? 1 : 0,
                    ':prestamo_id_asociado' => $prestamoId
                ]);

                // Registrar o actualizar notificación de pago para el socio si pagó cuota o aportó ahorro extra
                $pagoNotifModel = new PagoNotificacion();
                if ($pagouCuota || $ahorroExtra > 0) {
                    $montoTotalPagado = ($pagouCuota ? $valorCuotaBase : 0.00) + $ahorroExtra;
                    $pagoNotifModel->registrarNotificacionCuotaReunion(
                        $socioId,
                        $reunionId,
                        "Pago de Cuota - Reunión Quincenal #{$reunion['numero_quincena']}",
                        "Cuota Quincenal #{$reunion['numero_quincena']} (" . date('d/m/Y', strtotime($reunion['fecha_reunion'])) . ")",
                        $pagouCuota ? $ahorroNetoConstante : 0.00,
                        $pagouCuota ? $aporteRondaBase : 0.00,
                        $pagouCuota ? $aporteRifaBase : 0.00,
                        $ahorroExtra,
                        $montoTotalPagado,
                        [
                            'reunion_id' => $reunionId,
                            'numero_quincena' => $reunion['numero_quincena'],
                            'fecha_reunion' => $reunion['fecha_reunion'],
                            'cuota_base' => $valorCuotaBase,
                            'ahorro_cuota' => $pagouCuota ? $ahorroNetoConstante : 0.00,
                            'aporte_ronda' => $pagouCuota ? $aporteRondaBase : 0.00,
                            'aporte_rifa' => $pagouCuota ? $aporteRifaBase : 0.00,
                            'ahorro_extra' => $ahorroExtra,
                            'total_pagado' => $montoTotalPagado,
                            'pagou_cuota' => $pagouCuota ? 1 : 0
                        ],
                        $entregadoPor
                    );
                } else {
                    // Si no pagó cuota y no hay ahorro extra, anular notificación pendiente si existía
                    $pagoNotifModel->eliminarNotificacionCuotaReunionSiPendiente($socioId, $reunionId);
                }
            }

            // Marcar el llamado a lista de la reunión como realizado (LLAMADO_CERRADO)
            $stmtState = $this->db->prepare("UPDATE natillera_reuniones SET estado = 'LLAMADO_CERRADO' WHERE id = :id AND estado != 'CERRADA'");
            $stmtState->execute([':id' => $reunionId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function anularAutoprestamo24Horas(int $ahorroId): bool {
        $this->ultimoError = '';
        $this->db->beginTransaction();
        try {
            $stmtAhorro = $this->db->prepare("SELECT * FROM natillera_ahorros_cuotas WHERE id = :id FOR UPDATE");
            $stmtAhorro->execute([':id' => $ahorroId]);
            $ahorro = $stmtAhorro->fetch();

            if (!$ahorro || empty($ahorro['prestamo_id_asociado']) || empty($ahorro['autoprestamo_generado'])) {
                return $this->abortarAnulacion('No existe un autopréstamo vigente asociado a este registro.');
            }

            $prestamoId = (int)$ahorro['prestamo_id_asociado'];
            $socioIdAhorro = (int)$ahorro['socio_id'];
            $reunionIdAhorro = (int)$ahorro['reunion_id'];

            // Plazo de 24 horas contado desde la creación del autopréstamo (fecha_inicio DATETIME); se compara en SQL con NOW()
            $stmtPrest = $this->db->prepare("
                SELECT id, estado, anulado_sin_interes, es_autoprestamo,
                       TIMESTAMPDIFF(SECOND, fecha_inicio, NOW()) AS segundos
                FROM natillera_prestamos
                WHERE id = :pid AND socio_deudor_id = :sid AND reunion_id = :rid
                FOR UPDATE
            ");
            $stmtPrest->execute([':pid' => $prestamoId, ':sid' => $socioIdAhorro, ':rid' => $reunionIdAhorro]);
            $prest = $stmtPrest->fetch();

            if (!$prest || (int)$prest['es_autoprestamo'] !== 1) {
                return $this->abortarAnulacion('No se encontró el autopréstamo asociado.');
            }
            if ((int)$prest['anulado_sin_interes'] === 1 || $prest['estado'] === 'ANULADO') {
                return $this->abortarAnulacion('El autopréstamo ya fue anulado.');
            }
            if ($prest['segundos'] === null || (int)$prest['segundos'] > 24 * 3600) {
                return $this->abortarAnulacion('Ya transcurrieron más de 24 horas desde la creación del autopréstamo.');
            }

            $stmtEstReu = $this->db->prepare("SELECT estado FROM natillera_reuniones WHERE id = :id");
            $stmtEstReu->execute([':id' => $reunionIdAhorro]);
            if ($stmtEstReu->fetchColumn() === 'CERRADA') {
                return $this->abortarAnulacion('La reunión está CERRADA; no se puede anular el autopréstamo.');
            }

            $stmtAb = $this->db->prepare("SELECT COUNT(*) FROM natillera_abonos_prestamos WHERE prestamo_id = :pid");
            $stmtAb->execute([':pid' => $prestamoId]);
            if ((int)$stmtAb->fetchColumn() > 0) {
                return $this->abortarAnulacion('El autopréstamo ya tiene abonos registrados y no puede anularse.');
            }

            // Constancia de entrega generada automáticamente: se elimina si no está firmada ni tiene evidencia
            $stmtEnt = $this->db->prepare("
                SELECT id, firma_digital_path, foto_evidencia_path FROM natillera_entregas_beneficios
                WHERE reunion_id = :rid AND socio_id = :sid AND tipo_beneficio = 'PRESTAMO'
                  AND prestamo_id IS NULL
            ");
            $stmtEnt->execute([':rid' => $reunionIdAhorro, ':sid' => $socioIdAhorro]);
            $entregas = $stmtEnt->fetchAll();
            foreach ($entregas as $ent) {
                if (!empty($ent['firma_digital_path']) || !empty($ent['foto_evidencia_path'])) {
                    return $this->abortarAnulacion('La constancia de entrega del autopréstamo ya tiene firma o evidencia; no puede anularse.');
                }
            }
            if ($entregas) {
                $stmtDelEnt = $this->db->prepare("
                    DELETE FROM natillera_entregas_beneficios
                    WHERE reunion_id = :rid AND socio_id = :sid AND tipo_beneficio = 'PRESTAMO'
                      AND prestamo_id IS NULL
                ");
                $stmtDelEnt->execute([':rid' => $reunionIdAhorro, ':sid' => $socioIdAhorro]);
            }

            {
                // Consultar cuota base de la reunión para calcular desgloses
                $stmtReunion = $this->db->prepare("SELECT valor_cuota_base, aporte_ronda, aporte_rifa, numero_quincena, fecha_reunion FROM natillera_reuniones WHERE id = :id");
                $stmtReunion->execute([':id' => $ahorro['reunion_id']]);
                $rInfo = $stmtReunion->fetch();
                $vBase = (float)($rInfo['valor_cuota_base'] ?? 55000);

                $aportesR = FondoBeneficio::aportesDeReunion($rInfo ?: []);
                $apRonda = $aportesR['ronda'];
                $apRifa = $aportesR['rifa'];
                $ahorroCuota = CajaGlobal::ahorroPorCuota();

                // Marcar préstamo como anulado sin interés y pagado
                $stmtP = $this->db->prepare("
                    UPDATE natillera_prestamos 
                    SET anulado_sin_interes = 1, estado = 'PAGADO' 
                    WHERE id = :p_id
                ");
                $stmtP->execute([':p_id' => $prestamoId]);

                // Actualizar ahorro a cuota pagada (ahorro neto por config cuota.ahorro)
                $stmtA = $this->db->prepare("
                    UPDATE natillera_ahorros_cuotas 
                    SET cuota_pagada = 1, 
                        monto_cuota = :ahorro_cuota,
                        monto_aporte_ronda = :ap_ronda,
                        monto_aporte_rifa = :ap_rifa,
                        autoprestamo_generado = 0 
                    WHERE id = :a_id
                ");
                $stmtA->execute([
                    ':a_id' => $ahorroId,
                    ':ahorro_cuota' => $ahorroCuota,
                    ':ap_ronda' => $apRonda,
                    ':ap_rifa' => $apRifa
                ]);

                // Registrar notificación de pago acreditado por regla de 24 horas
                $socioId = (int)$ahorro['socio_id'];
                $reunionId = (int)$ahorro['reunion_id'];
                $numQuincena = $rInfo['numero_quincena'] ?? $reunionId;
                $ahorroExtraActual = (float)($ahorro['monto_ahorro_extra'] ?? 0.0);
                $totalPagado24h = $vBase + $ahorroExtraActual;

                $pagoNotifModel = new PagoNotificacion();
                $pagoNotifModel->registrarNotificacionAnulacion24h(
                    $socioId,
                    $reunionId,
                    "Pago de Cuota Acreditado (Plazo 24 Horas)",
                    "Cuota Reunión Nº {$numQuincena} pagada en plazo de 24 horas - Autopréstamo anulado",
                    $ahorroCuota,
                    $apRonda,
                    $apRifa,
                    $totalPagado24h,
                    [
                        'reunion_id' => $reunionId,
                        'numero_quincena' => $numQuincena,
                        'fecha_reunion' => $rInfo['fecha_reunion'] ?? '',
                        'cuota_base' => $vBase,
                        'ahorro_cuota' => $ahorroCuota,
                        'aporte_ronda' => $apRonda,
                        'aporte_rifa' => $apRifa,
                        'ahorro_extra' => $ahorroExtraActual,
                        'total_pagado' => $totalPagado24h,
                        'regla_24h' => true
                    ],
                    (int)($_SESSION['usuario']['id'] ?? 1)
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->ultimoError = 'Error al anular el autopréstamo.';
            return false;
        }
    }

    private function abortarAnulacion(string $mensaje): bool {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->ultimoError = $mensaje;
        return false;
    }
}
