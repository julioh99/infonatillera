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
            SELECT IFNULL(SUM(monto_inyectado), 0) as total_devueltas
            FROM natillera_inyecciones_capital
            WHERE estado = 'RETIRADA' AND DATE(fecha_retiro) = :fecha
        ");
        $stmtEgrInyDev->execute([':fecha' => $fechaReunion]);
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

        // 4. Saldo Neto de la Reunión
        $saldoNetoReunion = $totalIngresos - $totalEgresos;

        // 5. Saldo Acumulado Global en Caja (Sumatoria de todas las reuniones hasta la actual)
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
