<?php
// app/Models/Prestamo.php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/InteresPrestamo.php';

class Prestamo extends Model {

    public function getTodosPrestamos(): array {
        $stmt = $this->db->query("
            SELECT 
                p.*,
                u_deudor.nombre_completo as deudor_nombre,
                u_deudor.cedula as deudor_cedula,
                r.numero_quincena,
                r.fecha_reunion,
                IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital_pagado,
                IFNULL(SUM(ap.monto_interes_pagado), 0) as total_interes_pagado,
                (SELECT COUNT(*) 
                 FROM natillera_entregas_beneficios eb 
                 WHERE (eb.prestamo_id = p.id OR (eb.socio_id = p.socio_deudor_id AND eb.tipo_beneficio = 'PRESTAMO'))
                   AND ((eb.firma_digital_path IS NOT NULL AND eb.firma_digital_path != '') OR (eb.foto_evidencia_path IS NOT NULL AND eb.foto_evidencia_path != ''))
                ) as tiene_firma_foto
            FROM natillera_prestamos p
            JOIN natillera_usuarios u_deudor ON p.socio_deudor_id = u_deudor.id
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            GROUP BY p.id
            ORDER BY p.fecha_inicio DESC
        ");
        return $this->adjuntarIntereses($stmt->fetchAll());
    }

    /** Fecha desde la que corre el interes: fecha del prestamo, o de su reunion, o de creacion. */
    public static function fechaBaseInteres(array $p): string {
        foreach (['fecha_prestamo', 'fecha_reunion', 'fecha_inicio'] as $campo) {
            if (!empty($p[$campo])) {
                $f = InteresPrestamo::soloFecha($p[$campo]);
                if ($f !== '') return $f;
            }
        }
        return date('Y-m-d');
    }

    /** Abonos agrupados por prestamo (para calcular el interes de muchos prestamos con una sola consulta). */
    private function abonosAgrupados(?array $prestamoIds = null): array {
        $sql = "SELECT prestamo_id, fecha_abono, monto_capital_pagado, monto_interes_pagado FROM natillera_abonos_prestamos";
        if ($prestamoIds !== null) {
            if (empty($prestamoIds)) return [];
            $sql .= " WHERE prestamo_id IN (" . implode(',', array_map('intval', $prestamoIds)) . ")";
        }
        $porPrestamo = [];
        foreach ($this->db->query($sql)->fetchAll() as $a) {
            $porPrestamo[(int)$a['prestamo_id']][] = $a;
        }
        return $porPrestamo;
    }

    /**
     * Agrega a cada prestamo: interes_causado, interes_pendiente, interes_a_favor, meses_cobrados y
     * interes_proximo_mes (InteresPrestamo, segun config interes.modo).
     */
    public function adjuntarIntereses(array $prestamos, ?string $hoy = null): array {
        if (empty($prestamos)) return $prestamos;
        $abonos = $this->abonosAgrupados();
        foreach ($prestamos as $i => $p) {
            $calc = $this->calcularInteresDe($p, $abonos[(int)$p['id']] ?? [], $hoy);
            $prestamos[$i]['interes_causado'] = $calc['interes_causado'];
            $prestamos[$i]['interes_pendiente'] = $calc['interes_pendiente'];
            $prestamos[$i]['interes_a_favor'] = $calc['interes_a_favor'];
            $prestamos[$i]['meses_cobrados'] = $calc['meses_cobrados'];
            $prestamos[$i]['interes_proximo_mes'] = $calc['proximo_mes'];
        }
        return $prestamos;
    }

    private function calcularInteresDe(array $p, array $abonos, ?string $hoy = null): array {
        return InteresPrestamo::calcular([
            'monto_prestado' => $p['monto_prestado'],
            'tasa_interes_mensual' => $p['tasa_interes_mensual'] ?? 0,
            'fecha_inicio' => self::fechaBaseInteres($p),
            'anulado_sin_interes' => !empty($p['anulado_sin_interes'])
        ], $abonos, $hoy);
    }

    /** Detalle completo del interes de un prestamo (incluye `meses` mes a mes). null si no existe. */
    public function calcularInteresPrestamo(int $prestamoId, ?string $hoy = null): ?array {
        $p = $this->getPrestamoById($prestamoId);
        if (!$p) return null;
        $abonos = $this->abonosAgrupados([$prestamoId]);
        return $this->calcularInteresDe($p, $abonos[$prestamoId] ?? [], $hoy);
    }

    /** Interes pendiente y capital pendiente de los prestamos ACTIVOS de un socio. */
    public function resumenInteresSocio(int $socioId): array {
        $stmt = $this->db->prepare("
            SELECT p.*, r.fecha_reunion FROM natillera_prestamos p
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            WHERE p.socio_deudor_id = :id AND p.estado = 'ACTIVO'
        ");
        $stmt->execute([':id' => $socioId]);
        $prestamos = $stmt->fetchAll();
        $abonos = $this->abonosAgrupados(array_map(function ($p) { return (int)$p['id']; }, $prestamos));
        $interes = 0.0;
        $capital = 0.0;
        $meses = 0;
        foreach ($prestamos as $p) {
            $calc = $this->calcularInteresDe($p, $abonos[(int)$p['id']] ?? []);
            $interes += $calc['interes_pendiente'];
            $capital += $calc['saldo_capital'];
            $meses += $calc['meses_cobrados'];
        }
        return ['interes_pendiente' => round($interes, 2), 'capital_pendiente' => round($capital, 2), 'meses_cobrados' => $meses, 'prestamos_activos' => count($prestamos)];
    }

    public function getPrestamoById(int $id) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.nombre_completo as deudor_nombre, u.cedula as deudor_cedula, r.numero_quincena, r.fecha_reunion
            FROM natillera_prestamos p
            JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
            LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Indica si la reunion (por id) esta CERRADA. Sin id => false. */
    public function reunionEstaCerrada(?int $reunionId): bool {
        if (empty($reunionId)) return false;
        $stmt = $this->db->prepare("SELECT estado FROM natillera_reuniones WHERE id = :id");
        $stmt->execute([':id' => $reunionId]);
        return $stmt->fetchColumn() === 'CERRADA';
    }

    private function exigirReunionAbierta(?int $reunionId): void {
        if ($this->reunionEstaCerrada($reunionId)) {
            throw new InvalidArgumentException("La reunión asociada está CERRADA; no se pueden crear, editar ni eliminar préstamos o abonos de una reunión cerrada.");
        }
    }

    /**
     * Crea o actualiza (sin duplicar) la entrega/egreso PRESTAMO ligada a un prestamo_id.
     * Firma y foto son opcionales; si la entrega ya existia no se pisan con valores vacios.
     * Los autoprestamos generan su propia constancia en Reunion (sin prestamo_id) y no pasan por aqui.
     */
    public function asegurarEntregaPrestamo(int $prestamoId, int $reunionId, int $socioId, float $monto, ?string $firmaPath, ?string $fotoPath, int $entregadoPor): void {
        $stmt = $this->db->prepare("SELECT id FROM natillera_entregas_beneficios WHERE prestamo_id = :pid AND tipo_beneficio = 'PRESTAMO' LIMIT 1");
        $stmt->execute([':pid' => $prestamoId]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $stmtU = $this->db->prepare("
                UPDATE natillera_entregas_beneficios
                SET monto_entregado = :monto,
                    firma_digital_path = COALESCE(:firma, firma_digital_path),
                    foto_evidencia_path = COALESCE(:foto, foto_evidencia_path)
                WHERE id = :id
            ");
            $stmtU->execute([':monto' => $monto, ':firma' => $firmaPath, ':foto' => $fotoPath, ':id' => $id]);
            return;
        }

        $stmtI = $this->db->prepare("
            INSERT INTO natillera_entregas_beneficios
            (reunion_id, socio_id, prestamo_id, tipo_beneficio, monto_entregado, firma_digital_path, foto_evidencia_path, entregado_por_usuario_id)
            VALUES (:reunion_id, :socio_id, :prestamo_id, 'PRESTAMO', :monto, :firma, :foto, :por)
        ");
        $stmtI->execute([
            ':reunion_id' => $reunionId, ':socio_id' => $socioId, ':prestamo_id' => $prestamoId,
            ':monto' => $monto, ':firma' => $firmaPath, ':foto' => $fotoPath, ':por' => $entregadoPor
        ]);
    }

    public function crearPrestamo(array $datos) {
        $this->exigirReunionAbierta(!empty($datos['reunion_id']) ? (int)$datos['reunion_id'] : null);
        $fechaPrestamo = !empty($datos['fecha_prestamo']) ? $datos['fecha_prestamo'] : date('Y-m-d');

        $stmt = $this->db->prepare("
            INSERT INTO natillera_prestamos (
                socio_deudor_id, reunion_id, fecha_prestamo, nombre_referencia, monto_prestado, 
                tasa_interes_mensual, tipo_prestamo, es_autoprestamo, estado
            ) VALUES (
                :socio_deudor_id, :reunion_id, :fecha_prestamo, :nombre_referencia, :monto_prestado, 
                :tasa_interes_mensual, :tipo_prestamo, :es_autoprestamo, 'ACTIVO'
            )
        ");

        $ok = $stmt->execute([
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':reunion_id' => !empty($datos['reunion_id']) ? $datos['reunion_id'] : null,
            ':fecha_prestamo' => $fechaPrestamo,
            ':nombre_referencia' => !empty($datos['nombre_referencia']) ? $datos['nombre_referencia'] : null,
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'] ?? 10.00,
            ':tipo_prestamo' => $datos['tipo_prestamo'] ?? 'DIRECTO',
            ':es_autoprestamo' => $datos['es_autoprestamo'] ?? 0
        ]);

        if ($ok) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    public function actualizarPrestamo(int $id, array $datos): bool {
        $actual = $this->getPrestamoById($id);
        if (!$actual) {
            throw new InvalidArgumentException("El préstamo no existe.");
        }
        $this->exigirReunionAbierta(!empty($actual['reunion_id']) ? (int)$actual['reunion_id'] : null);
        $this->exigirReunionAbierta(!empty($datos['reunion_id']) ? (int)$datos['reunion_id'] : null);
        $fechaPrestamo = !empty($datos['fecha_prestamo']) ? $datos['fecha_prestamo'] : date('Y-m-d');

        $stmt = $this->db->prepare("
            UPDATE natillera_prestamos SET
                socio_deudor_id = :socio_deudor_id,
                reunion_id = :reunion_id,
                fecha_prestamo = :fecha_prestamo,
                monto_prestado = :monto_prestado,
                tasa_interes_mensual = :tasa_interes_mensual,
                nombre_referencia = :nombre_referencia,
                estado = :estado
            WHERE id = :id
        ");

        $ok = $stmt->execute([
            ':id' => $id,
            ':socio_deudor_id' => $datos['socio_deudor_id'],
            ':reunion_id' => !empty($datos['reunion_id']) ? $datos['reunion_id'] : null,
            ':fecha_prestamo' => $fechaPrestamo,
            ':monto_prestado' => $datos['monto_prestado'],
            ':tasa_interes_mensual' => $datos['tasa_interes_mensual'],
            ':nombre_referencia' => !empty($datos['nombre_referencia']) ? $datos['nombre_referencia'] : null,
            ':estado' => $datos['estado']
        ]);

        if ($ok) {
            // Mantener el egreso del cierre coherente con el monto prestado
            $stmtEnt = $this->db->prepare("UPDATE natillera_entregas_beneficios SET monto_entregado = :monto WHERE prestamo_id = :id AND tipo_beneficio = 'PRESTAMO'");
            $stmtEnt->execute([':monto' => $datos['monto_prestado'], ':id' => $id]);
            $this->recalcularEstadoPrestamo($id);
        }

        return $ok;
    }

    /**
     * Valida montos y saldo de un abono. $abonoReemplazadoId excluye ese abono del saldo (edicion).
     * Lanza InvalidArgumentException con mensaje amigable.
     */
    private function validarAbono(array $prestamo, float $montoCapital, float $montoInteres, ?int $abonoReemplazadoId = null): void {
        if ($montoCapital < 0 || $montoInteres < 0) {
            throw new InvalidArgumentException("Los montos del abono no pueden ser negativos.");
        }
        if ($montoCapital <= 0 && $montoInteres <= 0) {
            throw new InvalidArgumentException("Ingresa al menos un valor a abonar en capital o interés.");
        }
        $sql = "SELECT IFNULL(SUM(monto_capital_pagado), 0) FROM natillera_abonos_prestamos WHERE prestamo_id = :pid";
        $params = [':pid' => (int)$prestamo['id']];
        if ($abonoReemplazadoId !== null) {
            $sql .= " AND id != :aid";
            $params[':aid'] = $abonoReemplazadoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $saldo = (float)$prestamo['monto_prestado'] - (float)$stmt->fetchColumn();
        if (round($montoCapital, 2) > round($saldo, 2)) {
            throw new InvalidArgumentException("El abono a capital ($" . number_format($montoCapital, 0, ',', '.') . ") supera el saldo pendiente del préstamo ($" . number_format(max($saldo, 0), 0, ',', '.') . ").");
        }
    }

    public function getAbonosPorPrestamo(int $prestamoId): array {
        $stmt = $this->db->prepare("
            SELECT ap.*, u.nombre_completo as registrado_por_nombre
            FROM natillera_abonos_prestamos ap
            LEFT JOIN natillera_usuarios u ON ap.registrado_por_usuario_id = u.id
            WHERE ap.prestamo_id = :prestamo_id
            ORDER BY ap.fecha_abono DESC
        ");
        $stmt->execute([':prestamo_id' => $prestamoId]);
        return $stmt->fetchAll();
    }

    public function registrarAbono(int $prestamoId, float $montoCapital, float $montoInteres, int $usuarioId, ?int $reunionId = null): bool {
        $this->db->beginTransaction();
        try {
            $stmtLock = $this->db->prepare("SELECT id, monto_prestado, estado, reunion_id FROM natillera_prestamos WHERE id = :id FOR UPDATE");
            $stmtLock->execute([':id' => $prestamoId]);
            $prestamoRow = $stmtLock->fetch();
            if (!$prestamoRow) {
                throw new InvalidArgumentException("El préstamo no existe.");
            }
            if ($prestamoRow['estado'] !== 'ACTIVO') {
                throw new InvalidArgumentException("Solo se pueden registrar abonos en préstamos activos.");
            }
            $this->exigirReunionAbierta($reunionId);
            $this->exigirReunionAbierta(!empty($prestamoRow['reunion_id']) ? (int)$prestamoRow['reunion_id'] : null);
            $this->validarAbono($prestamoRow, $montoCapital, $montoInteres);

            $stmt = $this->db->prepare("
                INSERT INTO natillera_abonos_prestamos (prestamo_id, reunion_id, monto_capital_pagado, monto_interes_pagado, registrado_por_usuario_id)
                VALUES (:prestamo_id, :reunion_id, :monto_capital, :monto_interes, :registrado_por)
            ");
            $stmt->execute([
                ':prestamo_id' => $prestamoId,
                ':reunion_id' => $reunionId,
                ':monto_capital' => $montoCapital,
                ':monto_interes' => $montoInteres,
                ':registrado_por' => $usuarioId
            ]);

            // Registrar notificación de pago para el socio deudor
            $stmtP = $this->db->prepare("SELECT socio_deudor_id, nombre_referencia, monto_prestado FROM natillera_prestamos WHERE id = :id");
            $stmtP->execute([':id' => $prestamoId]);
            $prestamo = $stmtP->fetch();
            if ($prestamo) {
                require_once __DIR__ . '/PagoNotificacion.php';
                $pagoNotif = new PagoNotificacion();
                $pagoNotif->registrarNotificacionAbonoPrestamo(
                    (int)$prestamo['socio_deudor_id'],
                    $prestamoId,
                    $reunionId,
                    $montoCapital,
                    $montoInteres,
                    $usuarioId,
                    [
                        'nombre_referencia' => $prestamo['nombre_referencia'] ?? '',
                        'monto_prestado' => (float)$prestamo['monto_prestado']
                    ]
                );
            }

            $this->db->commit();
            $this->recalcularEstadoPrestamo($prestamoId);
            return true;
        } catch (InvalidArgumentException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function actualizarAbono(int $abonoId, float $montoCapital, float $montoInteres, ?string $fechaAbono = null, ?int $reunionId = null): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id, reunion_id FROM natillera_abonos_prestamos WHERE id = :id");
        $stmtAbono->execute([':id' => $abonoId]);
        $abono = $stmtAbono->fetch();
        if (!$abono) {
            throw new InvalidArgumentException("El abono no existe.");
        }

        $prestamoId = (int)$abono['prestamo_id'];
        $prestamo = $this->getPrestamoById($prestamoId);
        if (!$prestamo || $prestamo['estado'] === 'ANULADO') {
            throw new InvalidArgumentException("El préstamo del abono no existe o está anulado.");
        }
        $this->exigirReunionAbierta(!empty($abono['reunion_id']) ? (int)$abono['reunion_id'] : null);
        $this->exigirReunionAbierta($reunionId);
        $this->exigirReunionAbierta(!empty($prestamo['reunion_id']) ? (int)$prestamo['reunion_id'] : null);
        $this->validarAbono($prestamo, $montoCapital, $montoInteres, $abonoId);

        $fields = ["monto_capital_pagado = :capital", "monto_interes_pagado = :interes"];
        $params = [
            ':id' => $abonoId,
            ':capital' => $montoCapital,
            ':interes' => $montoInteres
        ];

        if (!empty($fechaAbono)) {
            $fields[] = "fecha_abono = :fecha";
            $params[':fecha'] = $fechaAbono;
        }

        if ($reunionId !== null) {
            $fields[] = "reunion_id = :reunion_id";
            $params[':reunion_id'] = $reunionId;
        }

        $sql = "UPDATE natillera_abonos_prestamos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function eliminarAbono(int $abonoId): bool {
        $stmtAbono = $this->db->prepare("SELECT prestamo_id, reunion_id FROM natillera_abonos_prestamos WHERE id = :id");
        $stmtAbono->execute([':id' => $abonoId]);
        $abono = $stmtAbono->fetch();
        if (!$abono) return false;

        $prestamoId = (int)$abono['prestamo_id'];
        $this->exigirReunionAbierta(!empty($abono['reunion_id']) ? (int)$abono['reunion_id'] : null);

        $stmtDel = $this->db->prepare("DELETE FROM natillera_abonos_prestamos WHERE id = :id");
        $ok = $stmtDel->execute([':id' => $abonoId]);

        if ($ok) {
            $this->recalcularEstadoPrestamo($prestamoId);
        }

        return $ok;
    }

    public function recalcularEstadoPrestamo(int $prestamoId): void {
        $stmtCheck = $this->db->prepare("
            SELECT p.monto_prestado, p.anulado_sin_interes, IFNULL(SUM(ap.monto_capital_pagado), 0) as total_capital
            FROM natillera_prestamos p
            LEFT JOIN natillera_abonos_prestamos ap ON p.id = ap.prestamo_id
            WHERE p.id = :id
            GROUP BY p.id
        ");
        $stmtCheck->execute([':id' => $prestamoId]);
        $row = $stmtCheck->fetch();

        if ($row && !$row['anulado_sin_interes']) {
            $montoPrestado = (float)$row['monto_prestado'];
            $totalCapital = (float)$row['total_capital'];
            $nuevoEstado = ($totalCapital >= $montoPrestado) ? 'PAGADO' : 'ACTIVO';

            $stmtUpdate = $this->db->prepare("UPDATE natillera_prestamos SET estado = :estado WHERE id = :id");
            $stmtUpdate->execute([':estado' => $nuevoEstado, ':id' => $prestamoId]);
        }
    }

    public function eliminarPrestamo(int $id): bool {
        $prestamo = $this->getPrestamoById($id);
        if (!$prestamo) {
            throw new InvalidArgumentException("El préstamo no existe.");
        }
        $this->exigirReunionAbierta(!empty($prestamo['reunion_id']) ? (int)$prestamo['reunion_id'] : null);
        $stmtCerr = $this->db->prepare("
            SELECT COUNT(*) FROM natillera_reuniones r
            WHERE r.estado = 'CERRADA' AND (
                r.id IN (SELECT reunion_id FROM natillera_abonos_prestamos WHERE prestamo_id = :id1 AND reunion_id IS NOT NULL)
                OR r.id IN (SELECT reunion_id FROM natillera_entregas_beneficios WHERE prestamo_id = :id2)
            )
        ");
        $stmtCerr->execute([':id1' => $id, ':id2' => $id]);
        if ((int)$stmtCerr->fetchColumn() > 0) {
            throw new InvalidArgumentException("El préstamo tiene abonos o entregas en una reunión CERRADA; no se puede eliminar.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Eliminar abonos a capital/intereses asociados
            $stmtDelAbonos = $this->db->prepare("DELETE FROM natillera_abonos_prestamos WHERE prestamo_id = :id");
            $stmtDelAbonos->execute([':id' => $id]);

            // 2. Eliminar evidencias/firmas asociadas en entregas de beneficios
            $stmtDelEntregas = $this->db->prepare("DELETE FROM natillera_entregas_beneficios WHERE prestamo_id = :id");
            $stmtDelEntregas->execute([':id' => $id]);

            // 3. Desvincular del registro de ahorro si fue autopréstamo
            $stmtUnlinkAhorro = $this->db->prepare("
                UPDATE natillera_ahorros_cuotas 
                SET autoprestamo_generado = 0, prestamo_id_asociado = NULL 
                WHERE prestamo_id_asociado = :id
            ");
            $stmtUnlinkAhorro->execute([':id' => $id]);

            // 4. Eliminar el registro del préstamo
            $stmtDelP = $this->db->prepare("DELETE FROM natillera_prestamos WHERE id = :id");
            $stmtDelP->execute([':id' => $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
