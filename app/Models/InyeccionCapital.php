<?php
// app/Models/InyeccionCapital.php

require_once __DIR__ . '/../../core/Model.php';

class InyeccionCapital extends Model {

    public function getInyecciones(): array {
        $stmt = $this->db->query("
            SELECT ic.*, u.nombre_completo as socio_nombre, u.cedula as socio_cedula,
                   r.numero_quincena, r.fecha_reunion,
                   u2.nombre_completo as registrado_por_nombre,
                   DATEDIFF(ic.fecha_retiro_permitido, CURDATE()) as dias_para_retiro
            FROM natillera_inyecciones_capital ic
            JOIN natillera_usuarios u ON ic.socio_id = u.id
            JOIN natillera_reuniones r ON ic.reunion_id = r.id
            JOIN natillera_usuarios u2 ON ic.registrado_por_usuario_id = u2.id
            ORDER BY ic.fecha_inyeccion DESC
        ");
        return $stmt->fetchAll();
    }

    public function getInyeccionesPorSocio(int $socioId): array {
        $stmt = $this->db->prepare("
            SELECT ic.*, r.numero_quincena, r.fecha_reunion,
                   DATEDIFF(ic.fecha_retiro_permitido, CURDATE()) as dias_para_retiro
            FROM natillera_inyecciones_capital ic
            JOIN natillera_reuniones r ON ic.reunion_id = r.id
            WHERE ic.socio_id = :socio_id
            ORDER BY ic.fecha_inyeccion DESC
        ");
        $stmt->execute([':socio_id' => $socioId]);
        return $stmt->fetchAll();
    }

    public function crearInyeccion(array $datos): bool {
        $monto = (float)$datos['monto_inyectado'];
        $tasa = (float)($datos['tasa_rendimiento_porcentaje'] ?? 5.00);
        $rendimiento = $monto * ($tasa / 100.0);

        // Fecha de retiro permitido: 6 meses en el futuro
        $fechaRetiroPermitido = date('Y-m-d', strtotime('+6 months'));

        $stmt = $this->db->prepare("
            INSERT INTO natillera_inyecciones_capital 
            (socio_id, reunion_id, monto_inyectado, tasa_rendimiento_porcentaje, monto_rendimiento_generado, fecha_retiro_permitido, estado, observaciones, registrado_por_usuario_id)
            VALUES (:socio_id, :reunion_id, :monto, :tasa, :rendimiento, :fecha_permitida, 'ACTIVA', :obs, :registrado_por)
        ");
        return $stmt->execute([
            ':socio_id' => $datos['socio_id'],
            ':reunion_id' => $datos['reunion_id'],
            ':monto' => $monto,
            ':tasa' => $tasa,
            ':rendimiento' => $rendimiento,
            ':fecha_permitida' => $fechaRetiroPermitido,
            ':obs' => $datos['observaciones'] ?? null,
            ':registrado_por' => $datos['registrado_por_usuario_id']
        ]);
    }

    public function retirarInyeccion(int $id, int $usuarioId): bool {
        $stmtCheck = $this->db->prepare("SELECT * FROM natillera_inyecciones_capital WHERE id = :id AND estado = 'ACTIVA'");
        $stmtCheck->execute([':id' => $id]);
        $iny = $stmtCheck->fetch();

        if (!$iny) {
            throw new Exception("Inyección no encontrada o ya se encuentra retirada.");
        }

        // Verificar si transcurrieron 6 meses
        $hoy = date('Y-m-d');
        if ($hoy < $iny['fecha_retiro_permitido']) {
            $diasRestantes = (strtotime($iny['fecha_retiro_permitido']) - strtotime($hoy)) / 86400;
            throw new Exception("Esta inyección aún está congelada. Faltan {$diasRestantes} días para cumplir los 6 meses de permanencia requeridos (Fecha retiro permitido: {$iny['fecha_retiro_permitido']}).");
        }

        $stmtUpd = $this->db->prepare("
            UPDATE natillera_inyecciones_capital 
            SET estado = 'RETIRADA', fecha_retiro = NOW() 
            WHERE id = :id
        ");
        return $stmtUpd->execute([':id' => $id]);
    }

    public function getResumenInyecciones(): array {
        $stmt = $this->db->query("
            SELECT 
                IFNULL(SUM(monto_inyectado), 0) as total_inyectado,
                IFNULL(SUM(monto_rendimiento_generado), 0) as total_rendimiento,
                IFNULL(SUM(CASE WHEN estado = 'ACTIVA' THEN monto_inyectado ELSE 0 END), 0) as activo_monto,
                IFNULL(SUM(CASE WHEN estado = 'RETIRADA' THEN monto_inyectado ELSE 0 END), 0) as retirado_monto,
                COUNT(*) as total_registros
            FROM natillera_inyecciones_capital
        ");
        return $stmt->fetch() ?: [
            'total_inyectado' => 0, 'total_rendimiento' => 0,
            'activo_monto' => 0, 'retirado_monto' => 0, 'total_registros' => 0
        ];
    }
}
