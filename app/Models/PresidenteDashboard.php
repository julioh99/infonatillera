<?php
// app/Models/PresidenteDashboard.php

require_once __DIR__ . '/../../core/Model.php';

class PresidenteDashboard extends Model {

    public function getResumenFinancieroGlobal(): array {
        // 1. Total ahorrado por cuotas base ($40.000)
        $stmtCuotas = $this->db->query("SELECT COALESCE(SUM(CASE WHEN cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END), 0) FROM natillera_ahorros_cuotas");
        $totalCuotas = (float)$stmtCuotas->fetchColumn();

        // 2. Total ahorro extra/voluntario
        $stmtExtra = $this->db->query("SELECT COALESCE(SUM(monto_ahorro_extra), 0) FROM natillera_ahorros_cuotas");
        $totalExtra = (float)$stmtExtra->fetchColumn();

        // 3. Intereses cobrados acumulados por abonos a préstamos
        $stmtInt = $this->db->query("SELECT COALESCE(SUM(monto_interes_pagado), 0) FROM natillera_abonos_prestamos");
        $totalInteresesCobrados = (float)$stmtInt->fetchColumn();

        // 4. Inyecciones de capital activas
        $stmtIny = $this->db->query("SELECT COALESCE(SUM(monto_inyectado), 0) FROM natillera_inyecciones_capital WHERE estado = 'ACTIVA'");
        $totalInyeccionesActivas = (float)$stmtIny->fetchColumn();

        // 5. Cartera de Préstamos (Capital prestado histórico y saldo capital actualmente en la calle)
        $stmtPrest = $this->db->query("
            SELECT 
                COALESCE(SUM(p.monto_prestado), 0) as total_prestado,
                COALESCE(SUM(CASE WHEN p.estado = 'ACTIVO' THEN (p.monto_prestado - COALESCE(ab.total_pagado, 0)) ELSE 0 END), 0) as cartera_activa_pendiente
            FROM natillera_prestamos p
            LEFT JOIN (
                SELECT prestamo_id, SUM(monto_capital_pagado) as total_pagado
                FROM natillera_abonos_prestamos
                GROUP BY prestamo_id
            ) ab ON p.id = ab.prestamo_id
            WHERE p.estado != 'ANULADO'
        ");
        $prestamosInfo = $stmtPrest->fetch();

        // 6. Egresos por retiros de inyecciones de capital procesados
        $stmtEgrIny = $this->db->query("SELECT COALESCE(SUM(monto_inyectado), 0) FROM natillera_inyecciones_capital WHERE estado = 'RETIRADA'");
        $egresosRetiros = (float)$stmtEgrIny->fetchColumn();

        // 7. Entregas de rifas/rondas pagadas
        $stmtEntregas = $this->db->query("SELECT COALESCE(SUM(monto_entregado), 0) FROM natillera_entregas_beneficios");
        $totalEntregas = (float)$stmtEntregas->fetchColumn();

        // Saldo Real Estimado Disponible en Caja
        $ingresosTotales = $totalCuotas + $totalExtra + $totalInteresesCobrados + $totalInyeccionesActivas;
        $egresosTotales = $prestamosInfo['cartera_activa_pendiente'] + $egresosRetiros + $totalEntregas;
        $saldoRealCaja = $ingresosTotales - $egresosTotales;

        return [
            'total_cuotas' => $totalCuotas,
            'total_extra' => $totalExtra,
            'total_intereses_cobrados' => $totalInteresesCobrados,
            'total_inyecciones_activas' => $totalInyeccionesActivas,
            'total_prestado_historico' => (float)$prestamosInfo['total_prestado'],
            'cartera_activa_pendiente' => (float)$prestamosInfo['cartera_activa_pendiente'],
            'egresos_retiros' => $egresosRetiros,
            'total_entregas' => $totalEntregas,
            'ingresos_totales' => $ingresosTotales,
            'saldo_real_caja' => $saldoRealCaja
        ];
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
