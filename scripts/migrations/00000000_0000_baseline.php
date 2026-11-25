<?php
// scripts/migrations/00000000_0000_baseline.php
// Linea base: lo que antes hacia Database::ensureSchemaUpdates() en CADA peticion.
// Idempotente: en una base ya migrada no cambia nada; en una base vacia carga db.sql primero.

return array(
    'descripcion' => 'Linea base del esquema (antes Database::ensureSchemaUpdates)',
    'up' => function (PDO $db) {
        $existeTabla = function ($tabla) use ($db) {
            $st = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t');
            $st->execute(array(':t' => $tabla));
            return (int)$st->fetchColumn() > 0;
        };
        $existeColumna = function ($tabla, $col) use ($db) {
            $st = $db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
            $st->execute(array(':t' => $tabla, ':c' => $col));
            return (int)$st->fetchColumn() > 0;
        };
        $agregarColumna = function ($tabla, $col, $def) use ($db, $existeTabla, $existeColumna) {
            if ($existeTabla($tabla) && !$existeColumna($tabla, $col)) {
                $db->exec("ALTER TABLE {$tabla} ADD COLUMN {$col} {$def}");
            }
        };

        // 1. Base vacia: cargar el esquema de referencia (db.sql)
        if (!$existeTabla('natillera_roles')) {
            $ruta = __DIR__ . '/../../db.sql';
            if (!file_exists($ruta)) {
                throw new Exception('Base vacia y no se encuentra db.sql para crear el esquema.');
            }
            Migrator::ejecutarSql($db, file_get_contents($ruta));
        }

        // 2. Roles base
        Database::sembrarRoles($db);

        // 3. Columnas que se agregaban en cada peticion
        $agregarColumna('natillera_prestamos', 'nombre_referencia', 'VARCHAR(150)');
        $agregarColumna('natillera_prestamos', 'fecha_prestamo', 'DATE NULL');
        $agregarColumna('natillera_actividades', 'cuota_por_socio', 'DECIMAL(10,2) DEFAULT 0.00');
        $agregarColumna('natillera_ahorros_cuotas', 'monto_aporte_ronda', 'DECIMAL(10,2) DEFAULT 0.00');
        $agregarColumna('natillera_ahorros_cuotas', 'monto_aporte_rifa', 'DECIMAL(10,2) DEFAULT 0.00');

        if ($existeColumna('natillera_prestamos', 'fecha_prestamo') && $existeColumna('natillera_prestamos', 'reunion_id')) {
            $db->exec("
                UPDATE natillera_prestamos p
                LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
                SET p.fecha_prestamo = IFNULL(r.fecha_reunion, DATE(p.fecha_inicio))
                WHERE p.fecha_prestamo IS NULL
            ");
        }

        // 4. Tablas que se creaban en cada peticion (CREATE TABLE IF NOT EXISTS)
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_fondo_beneficios_cronograma (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reunion_id INT NOT NULL,
                tipo_beneficio VARCHAR(10) NOT NULL,
                aporte_por_socio DECIMAL(10,2) NOT NULL,
                total_recaudado DECIMAL(10,2) NOT NULL,
                monto_beneficio_unidad DECIMAL(10,2) NOT NULL,
                saldo_restante_reunion DECIMAL(10,2) NOT NULL,
                saldo_acumulado_fondo DECIMAL(10,2) NOT NULL,
                personas_liberadas_planificadas INT NOT NULL,
                FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_entregas_beneficios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                reunion_id INT NOT NULL,
                socio_id INT NOT NULL,
                tipo_beneficio VARCHAR(10) NOT NULL,
                monto_entregado DECIMAL(10,2) NOT NULL,
                fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                firma_digital_path VARCHAR(255),
                foto_evidencia_path VARCHAR(255),
                entregado_por_usuario_id INT NOT NULL,
                FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
                FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
                FOREIGN KEY (entregado_por_usuario_id) REFERENCES natillera_usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_abonos_actividades (
                id INT AUTO_INCREMENT PRIMARY KEY,
                actividad_participante_id INT NOT NULL,
                monto_abono DECIMAL(10,2) NOT NULL,
                fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
                observacion VARCHAR(255),
                registrado_por_usuario_id INT NOT NULL,
                FOREIGN KEY (actividad_participante_id) REFERENCES natillera_actividad_participantes(id),
                FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_pagos_notificaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                socio_id INT NOT NULL,
                reunion_id INT NULL,
                tipo_pago VARCHAR(50) NOT NULL,
                referencia_id INT NULL,
                titulo VARCHAR(150) NOT NULL,
                concepto VARCHAR(255) NOT NULL,
                monto_cuota DECIMAL(10,2) DEFAULT 0.00,
                monto_aporte_ronda DECIMAL(10,2) DEFAULT 0.00,
                monto_aporte_rifa DECIMAL(10,2) DEFAULT 0.00,
                monto_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
                monto_total DECIMAL(10,2) NOT NULL,
                detalles_json LONGTEXT NULL,
                registrado_por_usuario_id INT NOT NULL,
                fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
                leido TINYINT(1) DEFAULT 0,
                fecha_leido DATETIME NULL,
                INDEX idx_pago_socio_leido (socio_id, leido),
                INDEX idx_pago_socio_reunion (socio_id, reunion_id),
                FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id) ON DELETE CASCADE,
                FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
);
