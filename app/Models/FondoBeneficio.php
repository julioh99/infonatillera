<?php
// app/Models/FondoBeneficio.php

require_once __DIR__ . '/../../core/Model.php';

class FondoBeneficio extends Model {

    public function generarCronogramaCompleto(): void {
        $stmtCheck = $this->db->query("SELECT COUNT(*) as cant FROM natillera_fondo_beneficios_cronograma");
        $cant = (int)($stmtCheck->fetch()['cant'] ?? 0);

        if ($cant > 0) {
            // Ya fue generado previamente
            return;
        }

        $stmtReuniones = $this->db->query("SELECT * FROM natillera_reuniones ORDER BY numero_quincena ASC");
        $reuniones = $stmtReuniones->fetchAll();

        if (empty($reuniones)) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmtInsert = $this->db->prepare("
                INSERT INTO natillera_fondo_beneficios_cronograma 
                (reunion_id, tipo_beneficio, aporte_por_socio, total_recaudado, monto_beneficio_unidad, saldo_restante_reunion, saldo_acumulado_fondo, personas_liberadas_planificadas)
                VALUES (:reunion_id, :tipo, :aporte_socio, :total_recaudado, :unidad, :saldo_restante, :saldo_acumulado, :personas)
            ");

            $acumuladoRonda = 0.00;
            $acumuladoRifa = 0.00;

            foreach ($reuniones as $r) {
                $vCuota = (float)($r['valor_cuota_base'] ?? 55000);
                $rId = (int)$r['id'];

                // 1. CÁLCULO RONDA ($300.000 por beneficiado)
                $aporteRonda = ($vCuota >= 65000) ? 20000.00 : 10000.00;
                $recaudoRonda = $aporteRonda * 50; // 50 socios
                $disponibleRonda = $acumuladoRonda + $recaudoRonda;
                $liberadosRonda = (int)floor($disponibleRonda / 300000.00);
                $entregadoRonda = $liberadosRonda * 300000.00;
                $restanteRonda = $disponibleRonda - $entregadoRonda;
                $acumuladoRonda = $restanteRonda;

                $stmtInsert->execute([
                    ':reunion_id' => $rId,
                    ':tipo' => 'RONDA',
                    ':aporte_socio' => $aporteRonda,
                    ':total_recaudado' => $recaudoRonda,
                    ':unidad' => 300000.00,
                    ':saldo_restante' => $restanteRonda,
                    ':saldo_acumulado' => $acumuladoRonda,
                    ':personas' => $liberadosRonda
                ]);

                // 2. CÁLCULO RIFA ($150.000 por beneficiado)
                $aporteRifa = ($vCuota >= 60000 && $vCuota < 65000) ? 10000.00 : 5000.00;
                $recaudoRifa = $aporteRifa * 50;
                $disponibleRifa = $acumuladoRifa + $recaudoRifa;
                $liberadosRifa = (int)floor($disponibleRifa / 150000.00);
                $entregadoRifa = $liberadosRifa * 150000.00;
                $restanteRifa = $disponibleRifa - $entregadoRifa;
                $acumuladoRifa = $restanteRifa;

                $stmtInsert->execute([
                    ':reunion_id' => $rId,
                    ':tipo' => 'RIFA',
                    ':aporte_socio' => $aporteRifa,
                    ':total_recaudado' => $recaudoRifa,
                    ':unidad' => 150000.00,
                    ':saldo_restante' => $restanteRifa,
                    ':saldo_acumulado' => $acumuladoRifa,
                    ':personas' => $liberadosRifa
                ]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
        }
    }

    public function getCronograma(string $tipoFilter = null): array {
        $this->generarCronogramaCompleto();

        $sql = "
            SELECT fbc.*, r.numero_quincena, r.fecha_reunion, r.valor_cuota_base, r.estado as reunion_estado,
                   (SELECT COUNT(*) FROM natillera_entregas_beneficios WHERE reunion_id = fbc.reunion_id AND tipo_beneficio = fbc.tipo_beneficio) as entregas_reales_count
            FROM natillera_fondo_beneficios_cronograma fbc
            JOIN natillera_reuniones r ON fbc.reunion_id = r.id
        ";
        if ($tipoFilter) {
            $sql .= " WHERE fbc.tipo_beneficio = :tipo ";
        }
        $sql .= " ORDER BY r.numero_quincena ASC, fbc.tipo_beneficio DESC ";

        $stmt = $this->db->prepare($sql);
        if ($tipoFilter) {
            $stmt->execute([':tipo' => $tipoFilter]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    public function getResumenFondos(): array {
        $this->generarCronogramaCompleto();

        // Resumen Ronda
        $stmtRonda = $this->db->query("
            SELECT IFNULL(SUM(total_recaudado), 0) as total_recaudado,
                   IFNULL(SUM(personas_liberadas_planificadas), 0) as total_liberados_planificados
            FROM natillera_fondo_beneficios_cronograma WHERE tipo_beneficio = 'RONDA'
        ");
        $rondaInfo = $stmtRonda->fetch();

        // Resumen Rifa
        $stmtRifa = $this->db->query("
            SELECT IFNULL(SUM(total_recaudado), 0) as total_recaudado,
                   IFNULL(SUM(personas_liberadas_planificadas), 0) as total_liberados_planificados
            FROM natillera_fondo_beneficios_cronograma WHERE tipo_beneficio = 'RIFA'
        ");
        $rifaInfo = $stmtRifa->fetch();

        // Entregas reales acumuladas
        $stmtEntregasRonda = $this->db->query("
            SELECT IFNULL(COUNT(*), 0) as ent_count, IFNULL(SUM(monto_entregado), 0) as ent_monto
            FROM natillera_entregas_beneficios WHERE tipo_beneficio = 'RONDA'
        ");
        $entRonda = $stmtEntregasRonda->fetch();

        $stmtEntregasRifa = $this->db->query("
            SELECT IFNULL(COUNT(*), 0) as ent_count, IFNULL(SUM(monto_entregado), 0) as ent_monto
            FROM natillera_entregas_beneficios WHERE tipo_beneficio = 'RIFA'
        ");
        $entRifa = $stmtEntregasRifa->fetch();

        $stmtEntregasPrestamo = $this->db->query("
            SELECT IFNULL(COUNT(*), 0) as ent_count, IFNULL(SUM(monto_entregado), 0) as ent_monto
            FROM natillera_entregas_beneficios WHERE tipo_beneficio = 'PRESTAMO'
        ");
        $entPrestamo = $stmtEntregasPrestamo->fetch();

        return [
            'ronda' => [
                'total_recaudado' => (float)$rondaInfo['total_recaudado'],
                'planificados' => (int)$rondaInfo['total_liberados_planificados'],
                'entregados_count' => (int)$entRonda['ent_count'],
                'entregados_monto' => (float)$entRonda['ent_monto']
            ],
            'rifa' => [
                'total_recaudado' => (float)$rifaInfo['total_recaudado'],
                'planificados' => (int)$rifaInfo['total_liberados_planificados'],
                'entregados_count' => (int)$entRifa['ent_count'],
                'entregados_monto' => (float)$entRifa['ent_monto']
            ],
            'prestamo' => [
                'entregados_count' => (int)$entPrestamo['ent_count'],
                'entregados_monto' => (float)$entPrestamo['ent_monto']
            ]
        ];
    }
}
