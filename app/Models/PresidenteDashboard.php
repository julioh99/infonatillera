<?php
// app/Models/PresidenteDashboard.php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/CajaGlobal.php';

class PresidenteDashboard extends Model {

    /**
     * Resumen financiero del tablero. La caja sale de CajaGlobal (misma fuente que el cierre de reunion).
     * Se conservan las claves antiguas por compatibilidad: total_cuotas, total_extra, total_intereses_cobrados,
     * total_inyecciones_activas, cartera_activa_pendiente, ingresos_totales, saldo_real_caja.
     */
    public function getResumenFinancieroGlobal(): array {
        $caja = new CajaGlobal();
        $r = $caja->resumen();
        $cierres = $caja->cajaPorCierres();

        return array_merge($r, [
            // claves antiguas
            'total_cuotas' => $r['ahorro_obligatorio'],
            'total_extra' => $r['ahorro_extra'],
            'total_intereses_cobrados' => $r['intereses'],
            'total_inyecciones_activas' => $r['inyecciones_activas'],
            'total_prestado_historico' => $r['prestamos_total_historico'],
            'cartera_activa_pendiente' => $r['cartera_pendiente'],
            'egresos_retiros' => $r['inyecciones_devueltas'],
            'total_entregas' => $r['premios_entregados'],
            'saldo_real_caja' => $r['caja_total'],
            // cuadre con la cadena de cierres
            'caja_cierres' => $cierres['caja_cierres'],
            'diferencia_cierres' => round($r['caja_total'] - $cierres['caja_cierres'], 2),
        ]);
    }

    public function getSociosSinPrestamos(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono, u.tope_prestamo_personalizado
            FROM natillera_usuarios u
            WHERE u.estado = 1
              AND u.id NOT IN (
                  SELECT DISTINCT socio_deudor_id 
                  FROM natillera_prestamos 
                  WHERE estado = 'ACTIVO'
              )
            ORDER BY u.nombre_completo ASC
        ");
        return $stmt->fetchAll();
    }

    public function getSociosSinActividades(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.cedula, u.nombre_completo, u.telefono
            FROM natillera_usuarios u
            WHERE u.estado = 1
              AND u.id NOT IN (
                  SELECT DISTINCT socio_id 
                  FROM natillera_actividad_participantes
              )
            ORDER BY u.nombre_completo ASC
        ");
        return $stmt->fetchAll();
    }

    public function getSociosConCuotasPendientes(): array {
        $stmt = $this->db->query("
            SELECT u.id, u.nombre_completo, u.telefono,
                   COUNT(ac.id) as total_cuotas_registradas,
                   SUM(CASE WHEN ac.cuota_pagada = 0 THEN 1 ELSE 0 END) as cuotas_debe
            FROM natillera_usuarios u
            JOIN natillera_ahorros_cuotas ac ON u.id = ac.socio_id
            WHERE u.estado = 1
            GROUP BY u.id, u.nombre_completo, u.telefono
            HAVING cuotas_debe > 0
            ORDER BY cuotas_debe DESC
        ");
        return $stmt->fetchAll();
    }
}
