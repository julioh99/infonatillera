<?php
// app/Models/CierreReunion.php

require_once __DIR__ . '/../../core/Model.php';

class CierreReunion extends Model {

    public function getCierrePorReunion(int $reunionId) {
        $stmt = $this->db->prepare("
            SELECT cr.*, r.numero_quincena, r.fecha_reunion, u.nombre_completo as cerrado_por_nombre
            FROM natillera_cierres_reunion cr
            JOIN natillera_reuniones r ON cr.reunion_id = r.id
            JOIN natillera_usuarios u ON cr.cerrado_por_usuario_id = u.id
            WHERE cr.reunion_id = :reunion_id
        ");
        $stmt->execute([':reunion_id' => $reunionId]);
        return $stmt->fetch() ?: null;
    }

    public function calcularResumenReunion(int $reunionId): array {
        // 1. Obtener datos de la reunión
        $stmtR = $this->db->prepare("SELECT * FROM natillera_reuniones WHERE id = :id");
        $stmtR->execute([':id' => $reunionId]);
        $reunion = $stmtR->fetch();

        if (!$reunion) {
            throw new Exception("Reunión no encontrada.");
        }

        $fechaReunion = $reunion['fecha_reunion'];

        // 2. INGRESOS (+)
        // a) Cuotas base ($40.000), Ahorros Voluntarios Extras y Aportes a Rondas/Rifas
        $stmtCuotas = $this->db->prepare("
            SELECT 
                IFNULL(SUM(CASE WHEN cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END), 0) as total_cuotas_base,
                IFNULL(SUM(monto_ahorro_extra), 0) as total_ahorro_extra,
                IFNULL(SUM(monto_aporte_ronda + monto_aporte_rifa), 0) as total_rondas_rifas,
                COUNT(*) as cantidad_registros
            FROM natillera_ahorros_cuotas
            WHERE reunion_id = :reunion_id
        ");
        $stmtCuotas->execute([':reunion_id' => $reunionId]);
        $resCuotas = $stmtCuotas->fetch();

        $cuotasBase = (float)$resCuotas['total_cuotas_base'];
        $ahorroExtra = (float)$resCuotas['total_ahorro_extra'];
        $rondasRifas = (float)$resCuotas['total_rondas_rifas'];
        $tieneLlamado = ((int)$resCuotas['cantidad_registros'] > 0);

        // b) Abonos a préstamos (Capital e Intereses) asociados a esta quincena o realizados en el rango de fechas
        $quincenaNum = (int)$reunion['numero_quincena'];
        $quincenaPrevNum = $quincenaNum - 1;

        $stmtPrestamos = $this->db->prepare("
            SELECT 
                IFNULL(SUM(monto_capital_pagado), 0) as total_capital,
                IFNULL(SUM(monto_interes_pagado), 0) as total_interes
            FROM natillera_abonos_prestamos ap
            WHERE 
                ap.reunion_id = :reunion_id
                OR (
                    ap.reunion_id IS NULL AND (
                        DATE(ap.fecha_abono) = :fecha
                        OR (
                            DATE(ap.fecha_abono) <= :fecha
                            AND DATE(ap.fecha_abono) > IFNULL((SELECT fecha_reunion FROM natillera_reuniones WHERE numero_quincena = :quincena_prev LIMIT 1), '2000-01-01')
                        )
                    )
                )
        ");
        $stmtPrestamos->execute([
            ':reunion_id' => $reunionId,
            ':fecha' => $fechaReunion,
            ':quincena_prev' => $quincenaPrevNum
        ]);
        $resPrestamos = $stmtPrestamos->fetch();

        $abonoCapital = (float)$resPrestamos['total_capital'];
        $interesesPrestamos = (float)$resPrestamos['total_interes'];

        // c) Transferencias/Devoluciones recibidas de la Caja de Actividades hacia la Caja Mayor
        $stmtDevAct = $this->db->prepare("
            SELECT IFNULL(SUM(monto), 0) as total_devoluciones
            FROM natillera_transferencias_cajas
            WHERE reunion_id = :reunion_id AND tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR'
        ");
        $stmtDevAct->execute([':reunion_id' => $reunionId]);
        $devolucionesActividades = (float)$stmtDevAct->fetch()['total_devoluciones'];

        // d) Inyecciones de capital ingresadas en esta reunión
        $stmtInyecciones = $this->db->prepare("
            SELECT IFNULL(SUM(monto_inyectado), 0) as total_inyecciones
            FROM natillera_inyecciones_capital
            WHERE reunion_id = :reunion_id
        ");
        $stmtInyecciones->execute([':reunion_id' => $reunionId]);
        $inyecciones = (float)$stmtInyecciones->fetch()['total_inyecciones'];

        $totalIngresos = $cuotasBase + $ahorroExtra + $abonoCapital + $interesesPrestamos + $devolucionesActividades + $inyecciones;

        // 3. EGRESOS (-)
        // a) Préstamos otorgados / desembolsados a socios en esta reunión
        $stmtEgrPrestamos = $this->db->prepare("
            SELECT IFNULL(SUM(monto_entregado), 0) as total_prestamos_otorgados
            FROM natillera_entregas_beneficios
            WHERE reunion_id = :reunion_id AND tipo_beneficio = 'PRESTAMO'
        ");
        $stmtEgrPrestamos->execute([':reunion_id' => $reunionId]);
        $prestamosOtorgados = (float)$stmtEgrPrestamos->fetch()['total_prestamos_otorgados'];

        // b) Inyecciones de capital devueltas/retiradas en esta reunión
        $stmtEgrInyDev = $this->db->prepare("
            SELECT IFNULL(SUM(monto_inyectado + monto_rendimiento_generado), 0) as total_devueltas
            FROM natillera_inyecciones_capital
            WHERE estado = 'RETIRADA' 
              AND (reunion_id_retiro = :reunion_id OR (reunion_id_retiro IS NULL AND DATE(fecha_retiro) = :fecha))
        ");
        $stmtEgrInyDev->execute([
            ':reunion_id' => $reunionId,
            ':fecha' => $fechaReunion
        ]);
        $inyeccionesDevueltas = (float)$stmtEgrInyDev->fetch()['total_devueltas'];

        // c) Préstamos sin interés otorgados desde la Caja Mayor hacia la Caja de Actividades
        $stmtPrestAct = $this->db->prepare("
            SELECT IFNULL(SUM(monto), 0) as total_prestamos_actividades
            FROM natillera_transferencias_cajas
            WHERE reunion_id = :reunion_id AND tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD'
        ");
        $stmtPrestAct->execute([':reunion_id' => $reunionId]);
        $prestamosAActividades = (float)$stmtPrestAct->fetch()['total_prestamos_actividades'];

        $totalEgresos = $prestamosOtorgados + $inyeccionesDevueltas + $prestamosAActividades;

        // 4. DESGLOSE DETALLADO POR RUBRO (Detalle por socio / movimiento)
        // a) Cuotas Base ($40.000)
        $stmtDetCuotas = $this->db->prepare("
            SELECT ac.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_usuarios u ON ac.socio_id = u.id
            WHERE ac.reunion_id = :reunion_id AND ac.cuota_pagada = 1
            ORDER BY u.nombre_completo ASC
        ");
        $stmtDetCuotas->execute([':reunion_id' => $reunionId]);
        $detCuotasBase = $stmtDetCuotas->fetchAll();

        // b) Ahorro Voluntario Extra
        $stmtDetExtra = $this->db->prepare("
            SELECT ac.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula
            FROM natillera_ahorros_cuotas ac
            JOIN natillera_usuarios u ON ac.socio_id = u.id
            WHERE ac.reunion_id = :reunion_id AND ac.monto_ahorro_extra > 0
            ORDER BY u.nombre_completo ASC
        ");
        $stmtDetExtra->execute([':reunion_id' => $reunionId]);
        $detAhorroExtra = $stmtDetExtra->fetchAll();

        // c) Abonos a Capital de Préstamos
        $stmtDetCap = $this->db->prepare("
            SELECT ap.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula,
                   p.nombre_referencia, p.monto_prestado, p.tasa_interes_mensual, p.tipo_prestamo,
                   u_reg.nombre_completo as registrado_por_nombre
            FROM natillera_abonos_prestamos ap
            JOIN natillera_prestamos p ON ap.prestamo_id = p.id
            JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
            LEFT JOIN natillera_usuarios u_reg ON ap.registrado_por_usuario_id = u_reg.id
            WHERE ap.monto_capital_pagado > 0
              AND (
                  ap.reunion_id = :reunion_id
                  OR (
                      ap.reunion_id IS NULL AND (
                          DATE(ap.fecha_abono) = :fecha
                          OR (
                              DATE(ap.fecha_abono) <= :fecha
                              AND DATE(ap.fecha_abono) > IFNULL((SELECT fecha_reunion FROM natillera_reuniones WHERE numero_quincena = :quincena_prev LIMIT 1), '2000-01-01')
                          )
                      )
                  )
              )
            ORDER BY ap.fecha_abono DESC, u.nombre_completo ASC
        ");
        $stmtDetCap->execute([
            ':reunion_id' => $reunionId,
            ':fecha' => $fechaReunion,
            ':quincena_prev' => $quincenaPrevNum
        ]);
        $detAbonoCapital = $stmtDetCap->fetchAll();

        // d) Intereses Cobrados de Préstamos
        $stmtDetInt = $this->db->prepare("
            SELECT ap.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula,
                   p.nombre_referencia, p.monto_prestado, p.tasa_interes_mensual, p.tipo_prestamo,
                   u_reg.nombre_completo as registrado_por_nombre
            FROM natillera_abonos_prestamos ap
            JOIN natillera_prestamos p ON ap.prestamo_id = p.id
            JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
            LEFT JOIN natillera_usuarios u_reg ON ap.registrado_por_usuario_id = u_reg.id
            WHERE ap.monto_interes_pagado > 0
              AND (
                  ap.reunion_id = :reunion_id
                  OR (
                      ap.reunion_id IS NULL AND (
                          DATE(ap.fecha_abono) = :fecha
                          OR (
                              DATE(ap.fecha_abono) <= :fecha
                              AND DATE(ap.fecha_abono) > IFNULL((SELECT fecha_reunion FROM natillera_reuniones WHERE numero_quincena = :quincena_prev LIMIT 1), '2000-01-01')
                          )
                      )
                  )
              )
            ORDER BY ap.fecha_abono DESC, u.nombre_completo ASC
        ");
        $stmtDetInt->execute([
            ':reunion_id' => $reunionId,
            ':fecha' => $fechaReunion,
            ':quincena_prev' => $quincenaPrevNum
        ]);
        $detInteresesPrestamos = $stmtDetInt->fetchAll();

        // e) Devoluciones de Actividades
        $stmtDetDev = $this->db->prepare("
            SELECT tc.*, u.nombre_completo as registrado_por_nombre
            FROM natillera_transferencias_cajas tc
            LEFT JOIN natillera_usuarios u ON tc.registrado_por_usuario_id = u.id
            WHERE tc.reunion_id = :reunion_id AND tc.tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR'
            ORDER BY tc.fecha_transferencia DESC
        ");
        $stmtDetDev->execute([':reunion_id' => $reunionId]);
        $detDevolucionesActividades = $stmtDetDev->fetchAll();

        // f) Inyecciones Ingresadas
        $stmtDetIny = $this->db->prepare("
            SELECT ic.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula
            FROM natillera_inyecciones_capital ic
            JOIN natillera_usuarios u ON ic.socio_id = u.id
            WHERE ic.reunion_id = :reunion_id
            ORDER BY ic.fecha_inyeccion DESC
        ");
        $stmtDetIny->execute([':reunion_id' => $reunionId]);
        $detInyecciones = $stmtDetIny->fetchAll();

        // g) Préstamos Otorgados / Desembolsados (Egresos)
        $stmtDetEgrP = $this->db->prepare("
            SELECT eb.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula,
                   p.monto_prestado, p.tasa_interes_mensual, p.nombre_referencia, p.tipo_prestamo
            FROM natillera_entregas_beneficios eb
            JOIN natillera_usuarios u ON eb.socio_id = u.id
            LEFT JOIN natillera_prestamos p ON eb.prestamo_id = p.id
            WHERE eb.reunion_id = :reunion_id AND eb.tipo_beneficio = 'PRESTAMO'
            ORDER BY eb.fecha_entrega DESC
        ");
        $stmtDetEgrP->execute([':reunion_id' => $reunionId]);
        $detPrestamosOtorgados = $stmtDetEgrP->fetchAll();

        if (empty($detPrestamosOtorgados)) {
            $stmtAltP = $this->db->prepare("
                SELECT p.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula, p.monto_prestado as monto_entregado, p.created_at as fecha_entrega
                FROM natillera_prestamos p
                JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
                WHERE p.reunion_id = :reunion_id
                ORDER BY p.id DESC
            ");
            $stmtAltP->execute([':reunion_id' => $reunionId]);
            $detPrestamosOtorgados = $stmtAltP->fetchAll();
        }

        // h) Inyecciones Devueltas (Egresos)
        $stmtDetInyDev = $this->db->prepare("
            SELECT ic.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula
            FROM natillera_inyecciones_capital ic
            JOIN natillera_usuarios u ON ic.socio_id = u.id
            WHERE ic.estado = 'RETIRADA'
              AND (ic.reunion_id_retiro = :reunion_id OR (ic.reunion_id_retiro IS NULL AND DATE(ic.fecha_retiro) = :fecha))
            ORDER BY ic.fecha_retiro DESC
        ");
        $stmtDetInyDev->execute([
            ':reunion_id' => $reunionId,
            ':fecha' => $fechaReunion
        ]);
        $detInyeccionesDevueltas = $stmtDetInyDev->fetchAll();

        // i) Préstamos a Caja de Actividades (Egresos)
        $stmtDetPrestAct = $this->db->prepare("
            SELECT tc.*, u.nombre_completo as registrado_por_nombre
            FROM natillera_transferencias_cajas tc
            LEFT JOIN natillera_usuarios u ON tc.registrado_por_usuario_id = u.id
            WHERE tc.reunion_id = :reunion_id AND tc.tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD'
            ORDER BY tc.fecha_transferencia DESC
        ");
        $stmtDetPrestAct->execute([':reunion_id' => $reunionId]);
        $detPrestamosActividades = $stmtDetPrestAct->fetchAll();

        // 5. Saldo Neto de la Reunión
        $saldoNetoReunion = $totalIngresos - $totalEgresos;

        // 6. Saldo Acumulado Global en Caja (Sumatoria de todas las reuniones hasta la actual)
        $stmtAcum = $this->db->prepare("
            SELECT IFNULL(SUM(saldo_neto_reunion), 0) as acumulado_previo
            FROM natillera_cierres_reunion cr
            JOIN natillera_reuniones r ON cr.reunion_id = r.id
            WHERE r.numero_quincena < :num_quincena
        ");
        $stmtAcum->execute([':num_quincena' => $reunion['numero_quincena']]);
        $acumuladoPrevio = (float)$stmtAcum->fetch()['acumulado_previo'];

        $saldoAcumuladoCaja = $acumuladoPrevio + $saldoNetoReunion;

        return [
            'reunion' => $reunion,
            'ingresos' => [
                'cuotas_base' => $cuotasBase,
                'ahorro_extra' => $ahorroExtra,
                'rondas_rifas' => 0.00,
                'abono_capital' => $abonoCapital,
                'intereses_prestamos' => $interesesPrestamos,
                'devoluciones_actividades' => $devolucionesActividades,
                'inyecciones' => $inyecciones,
                'total' => $totalIngresos
            ],
            'egresos' => [
                'prestamos_otorgados' => $prestamosOtorgados,
                'inyecciones_devueltas' => $inyeccionesDevueltas,
                'prestamos_actividades' => $prestamosAActividades,
                'premios_entregados' => 0.00,
                'total' => $totalEgresos
            ],
            'detalles' => [
                'cuotas_base' => $detCuotasBase,
                'ahorro_extra' => $detAhorroExtra,
                'abono_capital' => $detAbonoCapital,
                'intereses_prestamos' => $detInteresesPrestamos,
                'devoluciones_actividades' => $detDevolucionesActividades,
                'inyecciones' => $detInyecciones,
                'prestamos_otorgados' => $detPrestamosOtorgados,
                'inyecciones_devueltas' => $detInyeccionesDevueltas,
                'prestamos_actividades' => $detPrestamosActividades
            ],
            'saldo_inicial_caja' => $acumuladoPrevio,
            'saldo_neto_reunion' => $saldoNetoReunion,
            'saldo_acumulado_caja' => $saldoAcumuladoCaja,
            'tiene_llamado_lista' => $tieneLlamado
        ];
    }

    public function guardarCierre(int $reunionId, array $resumen, int $usuarioId): bool {
        $this->db->beginTransaction();

        try {
            $ing = $resumen['ingresos'];
            $egr = $resumen['egresos'];
            $desgloseJson = json_encode($resumen, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("
                INSERT INTO natillera_cierres_reunion 
                (reunion_id, total_ingresos_cuotas_base, total_ingresos_ahorro_extra, total_ingresos_rondas_rifas,
                 total_ingresos_abono_capital, total_ingresos_intereses_prestamos, total_ingresos_actividades,
                 total_ingresos_inyecciones, total_ingresos_general, total_egresos_prestamos_otorgados,
                 total_egresos_premios_entregados, total_egresos_inyecciones_devueltas, total_egresos_general,
                 saldo_neto_reunion, saldo_acumulado_caja, desglose_json, cerrado_por_usuario_id)
                VALUES 
                (:reunion_id, :ing_cuotas, :ing_extra, :ing_rr, :ing_cap, :ing_int, :ing_act, :ing_iny, :ing_tot,
                 :egr_prest, :egr_prem, :egr_iny, :egr_tot, :saldo_neto, :saldo_acum, :json, :usuario_id)
                ON DUPLICATE KEY UPDATE 
                    total_ingresos_cuotas_base = VALUES(total_ingresos_cuotas_base),
                    total_ingresos_ahorro_extra = VALUES(total_ingresos_ahorro_extra),
                    total_ingresos_rondas_rifas = VALUES(total_ingresos_rondas_rifas),
                    total_ingresos_abono_capital = VALUES(total_ingresos_abono_capital),
                    total_ingresos_intereses_prestamos = VALUES(total_ingresos_intereses_prestamos),
                    total_ingresos_actividades = VALUES(total_ingresos_actividades),
                    total_ingresos_inyecciones = VALUES(total_ingresos_inyecciones),
                    total_ingresos_general = VALUES(total_ingresos_general),
                    total_egresos_prestamos_otorgados = VALUES(total_egresos_prestamos_otorgados),
                    total_egresos_premios_entregados = VALUES(total_egresos_premios_entregados),
                    total_egresos_inyecciones_devueltas = VALUES(total_egresos_inyecciones_devueltas),
                    total_egresos_general = VALUES(total_egresos_general),
                    saldo_neto_reunion = VALUES(saldo_neto_reunion),
                    saldo_acumulado_caja = VALUES(saldo_acumulado_caja),
                    desglose_json = VALUES(desglose_json),
                    fecha_cierre = NOW(),
                    cerrado_por_usuario_id = VALUES(cerrado_por_usuario_id)
            ");

            $stmt->execute([
                ':reunion_id' => $reunionId,
                ':ing_cuotas' => $ing['cuotas_base'],
                ':ing_extra' => $ing['ahorro_extra'],
                ':ing_rr' => $ing['rondas_rifas'],
                ':ing_cap' => $ing['abono_capital'],
                ':ing_int' => $ing['intereses_prestamos'],
                ':ing_act' => $ing['devoluciones_actividades'],
                ':ing_iny' => $ing['inyecciones'],
                ':ing_tot' => $ing['total'],
                ':egr_prest' => $egr['prestamos_otorgados'],
                ':egr_prem' => $egr['premios_entregados'],
                ':egr_iny' => $egr['inyecciones_devueltas'],
                ':egr_tot' => $egr['total'],
                ':saldo_neto' => $resumen['saldo_neto_reunion'],
                ':saldo_acum' => $resumen['saldo_acumulado_caja'],
                ':json' => $desgloseJson,
                ':usuario_id' => $usuarioId
            ]);

            // Marcar reunión como CERRADA
            $stmtUpdR = $this->db->prepare("UPDATE natillera_reuniones SET estado = 'CERRADA' WHERE id = :id");
            $stmtUpdR->execute([':id' => $reunionId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getTodosCierres(): array {
        $stmt = $this->db->query("
            SELECT cr.*, r.numero_quincena, r.fecha_reunion, u.nombre_completo as cerrado_por_nombre
            FROM natillera_cierres_reunion cr
            JOIN natillera_reuniones r ON cr.reunion_id = r.id
            JOIN natillera_usuarios u ON cr.cerrado_por_usuario_id = u.id
            ORDER BY r.numero_quincena DESC
        ");
        return $stmt->fetchAll();
    }
}
