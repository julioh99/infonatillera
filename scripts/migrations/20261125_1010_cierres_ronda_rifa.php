<?php
// Corrige los cierres ya hechos con la formula vieja (rondas_rifas = 0 y premios = 0): suma el recaudo real de
// ronda/rifa como ingreso y los premios entregados como egreso, y recalcula la cadena de saldo_acumulado_caja.
// Idempotente: solo toca un cierre si su campo sigue en 0 y la reunion tiene aportes/premios reales.
return [
    'descripcion' => 'Cierres cerrados: sumar ronda/rifa como ingreso y premios como egreso; recalcular saldo acumulado',
    'up' => function (PDO $db) {
        $tabla = $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'natillera_cierres_reunion'")->fetchColumn();
        if ((int)$tabla === 0) {
            return;
        }
        $cierres = $db->query("SELECT cr.id, cr.reunion_id, cr.total_ingresos_rondas_rifas, cr.total_egresos_premios_entregados,
                                      cr.total_ingresos_general, cr.total_egresos_general, cr.saldo_neto_reunion, cr.desglose_json
                               FROM natillera_cierres_reunion cr")->fetchAll(PDO::FETCH_ASSOC);

        $stmtAp = $db->prepare("SELECT IFNULL(SUM(monto_aporte_ronda + monto_aporte_rifa), 0) FROM natillera_ahorros_cuotas WHERE reunion_id = :r");
        $stmtPr = $db->prepare("SELECT IFNULL(SUM(monto_entregado), 0) FROM natillera_entregas_beneficios WHERE reunion_id = :r AND tipo_beneficio IN ('RONDA','RIFA')");
        $stmtUp = $db->prepare("UPDATE natillera_cierres_reunion SET total_ingresos_rondas_rifas = :rr, total_egresos_premios_entregados = :pr,
                                    total_ingresos_general = :ti, total_egresos_general = :te, saldo_neto_reunion = :neto, desglose_json = :json
                                WHERE id = :id");
        $db->beginTransaction();
        try {
            foreach ($cierres as $c) {
                $rr = (float)$c['total_ingresos_rondas_rifas'];
                $pr = (float)$c['total_egresos_premios_entregados'];
                $cambio = false;
                if ($rr == 0.0) {
                    $stmtAp->execute([':r' => $c['reunion_id']]);
                    $nuevo = (float)$stmtAp->fetchColumn();
                    if ($nuevo > 0) { $rr = $nuevo; $cambio = true; }
                }
                if ($pr == 0.0) {
                    $stmtPr->execute([':r' => $c['reunion_id']]);
                    $nuevo = (float)$stmtPr->fetchColumn();
                    if ($nuevo > 0) { $pr = $nuevo; $cambio = true; }
                }
                if (!$cambio) {
                    continue;
                }
                $ti = (float)$c['total_ingresos_general'] + ($rr - (float)$c['total_ingresos_rondas_rifas']);
                $te = (float)$c['total_egresos_general'] + ($pr - (float)$c['total_egresos_premios_entregados']);
                $json = $c['desglose_json'];
                $dec = $json ? json_decode($json, true) : null;
                if (is_array($dec)) {
                    $dec['ingresos']['rondas_rifas'] = $rr;
                    $dec['ingresos']['total'] = $ti;
                    $dec['egresos']['premios_entregados'] = $pr;
                    $dec['egresos']['total'] = $te;
                    $dec['saldo_neto_reunion'] = $ti - $te;
                    $json = json_encode($dec, JSON_UNESCAPED_UNICODE);
                }
                $stmtUp->execute([':rr' => $rr, ':pr' => $pr, ':ti' => $ti, ':te' => $te, ':neto' => $ti - $te, ':json' => $json, ':id' => $c['id']]);
            }
            // Cadena de saldo acumulado (misma regla que CierreReunion::recalcularSaldosAcumulados)
            $orden = $db->query("SELECT cr.id, cr.saldo_neto_reunion FROM natillera_cierres_reunion cr
                                 JOIN natillera_reuniones r ON cr.reunion_id = r.id ORDER BY r.numero_quincena ASC")->fetchAll(PDO::FETCH_ASSOC);
            $stmtAc = $db->prepare("UPDATE natillera_cierres_reunion SET saldo_acumulado_caja = :s WHERE id = :id");
            $acum = 0.0;
            foreach ($orden as $o) {
                $acum += (float)$o['saldo_neto_reunion'];
                $stmtAc->execute([':s' => $acum, ':id' => $o['id']]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            throw $e;
        }
    },
];
