<?php
// app/Models/TransferenciaCaja.php

require_once __DIR__ . '/../../core/Model.php';

class TransferenciaCaja extends Model {

    public function getTransferencias(): array {
        $stmt = $this->db->query("
            SELECT tc.*, r.numero_quincena, r.fecha_reunion,
                   act.nombre_actividad,
                   u.nombre_completo as registrado_por_nombre
            FROM natillera_transferencias_cajas tc
            JOIN natillera_reuniones r ON tc.reunion_id = r.id
            LEFT JOIN natillera_actividades act ON tc.actividad_id = act.id
            JOIN natillera_usuarios u ON tc.registrado_por_usuario_id = u.id
            ORDER BY tc.fecha_transferencia DESC
        ");
        return $stmt->fetchAll();
    }

    public function registrarTransferencia(array $datos): bool {
        $stmt = $this->db->prepare("
            INSERT INTO natillera_transferencias_cajas 
            (reunion_id, actividad_id, tipo_movimiento, monto, concepto, registrado_por_usuario_id)
            VALUES (:reunion_id, :actividad_id, :tipo_movimiento, :monto, :concepto, :registrado_por)
        ");
        return $stmt->execute([
            ':reunion_id' => $datos['reunion_id'],
            ':actividad_id' => !empty($datos['actividad_id']) ? $datos['actividad_id'] : null,
            ':tipo_movimiento' => $datos['tipo_movimiento'], // 'PRESTAMO_A_ACTIVIDAD', 'DEVOLUCION_A_CAJA_MAYOR'
            ':monto' => (float)$datos['monto'],
            ':concepto' => $datos['concepto'],
            ':registrado_por' => $datos['registrado_por_usuario_id']
        ]);
    }

    public function getResumenIntercajas(): array {
        $stmt = $this->db->query("
            SELECT 
                IFNULL(SUM(CASE WHEN tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD' THEN monto ELSE 0 END), 0) as total_prestado_actividades,
                IFNULL(SUM(CASE WHEN tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR' THEN monto ELSE 0 END), 0) as total_devuelto_caja_mayor
            FROM natillera_transferencias_cajas
        ");
        $res = $stmt->fetch() ?: ['total_prestado_actividades' => 0, 'total_devuelto_caja_mayor' => 0];
        
        $totalPrestado = (float)$res['total_prestado_actividades'];
        $totalDevuelto = (float)$res['total_devuelto_caja_mayor'];
        $saldoPendiente = $totalPrestado - $totalDevuelto;

        return [
            'total_prestado_actividades' => $totalPrestado,
            'total_devuelto_caja_mayor' => $totalDevuelto,
            'saldo_pendiente_actividades' => $saldoPendiente
        ];
    }
}
