-- TABLA DE ROLES
CREATE TABLE IF NOT EXISTS natillera_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE -- 'Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLA DE USUARIOS / SOCIOS
CREATE TABLE IF NOT EXISTS natillera_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    tope_prestamo_personalizado DECIMAL(10,2) DEFAULT 2000000.00, -- Modificable por Sec. General
    interes_minimo_meta DECIMAL(10,2) DEFAULT 400000.00, -- Meta individual de $400k
    estado TINYINT(1) DEFAULT 1,
    password_changed TINYINT(1) DEFAULT 0,
    FOREIGN KEY (rol_id) REFERENCES natillera_roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- REUNIONES QUINCENALES
CREATE TABLE IF NOT EXISTS natillera_reuniones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_quincena INT NOT NULL, -- 1 a 26
    fecha_reunion DATE NOT NULL,
    hora_reunion TIME DEFAULT '14:00:00',
    valor_cuota_base DECIMAL(10,2) NOT NULL DEFAULT 55000.00, -- 55000, 60000, 65000
    tipo_evento_extra VARCHAR(50) DEFAULT 'NINGUNO', -- 'RIFA', 'RONDA', 'NINGUNO'
    monto_premio_extra DECIMAL(10,2) DEFAULT 0.00, -- 150000 o 300000
    ganador_socio_id INT, -- Socio beneficiado con la Rifa/Ronda
    estado VARCHAR(20) DEFAULT 'PROGRAMADA', -- 'PROGRAMADA', 'EN_PROCESO', 'CERRADA'
    FOREIGN KEY (ganador_socio_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- COBROS EN LLAMADO A LISTA
CREATE TABLE IF NOT EXISTS natillera_ahorros_cuotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    socio_id INT NOT NULL,
    cuota_pagada TINYINT(1) DEFAULT 0,
    monto_cuota DECIMAL(10,2) NOT NULL, -- Siempre $40.000 COP acreditado al socio
    monto_aporte_ronda DECIMAL(10,2) DEFAULT 0.00,
    monto_aporte_rifa DECIMAL(10,2) DEFAULT 0.00,
    monto_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
    autoprestamo_generado TINYINT(1) DEFAULT 0,
    prestamo_id_asociado INT, -- Si generó autopréstamo
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRÉSTAMOS E INTERESES
CREATE TABLE IF NOT EXISTS natillera_prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_deudor_id INT NOT NULL,
    reunion_id INT NULL,
    nombre_referencia VARCHAR(150), -- Nombre de referencia / Persona referente opcional
    monto_prestado DECIMAL(10,2) NOT NULL,
    tasa_interes_mensual DECIMAL(5,2) DEFAULT 10.00,
    tipo_prestamo VARCHAR(20) DEFAULT 'DIRECTO', -- 'DIRECTO', 'AUTOPRESTAMO'
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    es_autoprestamo TINYINT(1) DEFAULT 0,
    anulado_sin_interes TINYINT(1) DEFAULT 0,
    estado VARCHAR(20) DEFAULT 'ACTIVO', -- 'ACTIVO', 'PAGADO', 'ANULADO'
    FOREIGN KEY (socio_deudor_id) REFERENCES natillera_usuarios(id),
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ABONOS A PRÉSTAMOS
CREATE TABLE IF NOT EXISTS natillera_abonos_prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    reunion_id INT NULL,
    monto_interes_pagado DECIMAL(10,2) DEFAULT 0.00,
    monto_capital_pagado DECIMAL(10,2) DEFAULT 0.00,
    fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
    registrado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (prestamo_id) REFERENCES natillera_prestamos(id),
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ACTIVIDADES (TAMALES, RIFAS)
CREATE TABLE IF NOT EXISTS natillera_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_actividad VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_actividad DATE NOT NULL,
    ingresos_totales DECIMAL(10,2) DEFAULT 0.00,
    gastos_totales DECIMAL(10,2) DEFAULT 0.00,
    ganancia_neta DECIMAL(10,2) DEFAULT 0.00,
    cuota_por_socio DECIMAL(10,2) DEFAULT 0.00, -- Cuota a pagar asignada a cada socio
    estado VARCHAR(20) DEFAULT 'EN_PROCESO', -- 'EN_PROCESO', 'LIQUIDADA'
    creado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (creado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PARTICIPANTES Y DEUDAS EN ACTIVIDADES
CREATE TABLE IF NOT EXISTS natillera_actividad_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id INT NOT NULL,
    socio_id INT NOT NULL,
    cuota_asignada DECIMAL(10,2) DEFAULT 0.00, -- Deuda asignada o parte proporcional de gastos
    monto_pagado DECIMAL(10,2) DEFAULT 0.00,
    ganancia_asignada DECIMAL(10,2) DEFAULT 0.00, -- Utilidad que le corresponde de la actividad
    estado_pago VARCHAR(20) DEFAULT 'PENDIENTE', -- 'PENDIENTE', 'PAGADO'
    FOREIGN KEY (actividad_id) REFERENCES natillera_actividades(id),
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ABONOS A ACTIVIDADES COMUNITARIAS
CREATE TABLE IF NOT EXISTS natillera_abonos_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actividad_participante_id INT NOT NULL,
    monto_abono DECIMAL(10,2) NOT NULL,
    fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    registrado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (actividad_participante_id) REFERENCES natillera_actividad_participantes(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SUSCRIPCIONES WEB PUSH NOTIFICATIONS
CREATE TABLE IF NOT EXISTS natillera_push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- HISTORIAL DE NOTIFICACIONES
CREATE TABLE IF NOT EXISTS natillera_notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    destinatario_tipo VARCHAR(20) DEFAULT 'TODOS', -- 'TODOS', 'SOCIO_ESPECIFICO'
    socio_id INT,
    enviado_por_usuario_id INT NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
    FOREIGN KEY (enviado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- TABLA DE PLANIFICACIÓN DE RONDAS Y RIFAS POR REUNIÓN
CREATE TABLE IF NOT EXISTS natillera_fondo_beneficios_cronograma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    tipo_beneficio VARCHAR(10) NOT NULL, -- 'RONDA' ($300k) o 'RIFA' ($150k)
    aporte_por_socio DECIMAL(10,2) NOT NULL, -- $10.000 (o $20.000) para Ronda; $5.000 (o $10.000) para Rifa
    total_recaudado DECIMAL(10,2) NOT NULL, -- Ej: 50 * $10.000 = $500.000
    monto_beneficio_unidad DECIMAL(10,2) NOT NULL, -- $300.000 para Ronda, $150.000 para Rifa
    saldo_restante_reunion DECIMAL(10,2) NOT NULL,
    saldo_acumulado_fondo DECIMAL(10,2) NOT NULL, -- Saldo acumulado arrastrado a la sig. reunión
    personas_liberadas_planificadas INT NOT NULL, -- Cantidad de ganadores en esta quincena (1, 2, 3 o 4)
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- REGISTRO Y EVIDENCIA DE ENTREGAS A BENEFICIARIOS
CREATE TABLE IF NOT EXISTS natillera_entregas_beneficios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    socio_id INT NOT NULL,
    prestamo_id INT NULL,
    tipo_beneficio VARCHAR(10) NOT NULL, -- 'PRESTAMO', 'RONDA' o 'RIFA'
    monto_entregado DECIMAL(10,2) NOT NULL,
    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
    firma_digital_path VARCHAR(255), -- Ruta de la firma guardada en formato SVG/PNG
    foto_evidencia_path VARCHAR(255), -- Ruta de la foto del socio recibiendo el premio
    entregado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
    FOREIGN KEY (prestamo_id) REFERENCES natillera_prestamos(id) ON DELETE SET NULL,
    FOREIGN KEY (entregado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INYECCIONES DE CAPITAL DE SOCIOS
CREATE TABLE IF NOT EXISTS natillera_inyecciones_capital (
    id INT AUTO_INCREMENT PRIMARY KEY,
    socio_id INT NOT NULL,
    reunion_id INT NOT NULL,
    reunion_id_retiro INT NULL,
    monto_inyectado DECIMAL(10,2) NOT NULL,
    tasa_rendimiento_porcentaje DECIMAL(5,2) DEFAULT 5.00,
    monto_rendimiento_generado DECIMAL(10,2) DEFAULT 0.00,
    fecha_inyeccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_retiro_permitido DATE NOT NULL,
    estado VARCHAR(20) DEFAULT 'ACTIVA', -- 'ACTIVA', 'RETIRADA'
    fecha_retiro DATETIME NULL,
    observaciones VARCHAR(255),
    registrado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (reunion_id_retiro) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CIERRE DE CAJA Y LIQUIDACIÓN FINANCIERA POR REUNIÓN
CREATE TABLE IF NOT EXISTS natillera_cierres_reunion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL UNIQUE,
    total_ingresos_cuotas_base DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_rondas_rifas DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_abono_capital DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_intereses_prestamos DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_actividades DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_inyecciones DECIMAL(10,2) DEFAULT 0.00,
    total_ingresos_general DECIMAL(10,2) DEFAULT 0.00,
    total_egresos_prestamos_otorgados DECIMAL(10,2) DEFAULT 0.00,
    total_egresos_premios_entregados DECIMAL(10,2) DEFAULT 0.00,
    total_egresos_inyecciones_devueltas DECIMAL(10,2) DEFAULT 0.00,
    total_egresos_general DECIMAL(10,2) DEFAULT 0.00,
    saldo_neto_reunion DECIMAL(10,2) DEFAULT 0.00,
    saldo_acumulado_caja DECIMAL(10,2) DEFAULT 0.00,
    desglose_json LONGTEXT,
    fecha_cierre DATETIME DEFAULT CURRENT_TIMESTAMP,
    cerrado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (cerrado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PRÉSTAMOS Y TRANSFERENCIAS ENTRE CAJA MAYOR Y CAJA DE ACTIVIDADES
CREATE TABLE IF NOT EXISTS natillera_transferencias_cajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    actividad_id INT NULL,
    tipo_movimiento VARCHAR(30) NOT NULL, -- 'PRESTAMO_A_ACTIVIDAD', 'DEVOLUCION_A_CAJA_MAYOR'
    monto DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    fecha_transferencia DATETIME DEFAULT CURRENT_TIMESTAMP,
    registrado_por_usuario_id INT NOT NULL,
    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
    FOREIGN KEY (actividad_id) REFERENCES natillera_actividades(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- VISTAS SQL PARA AUDITORÍA, REVISIÓN Y REPORTES CONSOLIDADOS
-- ============================================================================

-- 1. VISTA RESUMEN CONSOLIDADO SOCIOS
CREATE OR REPLACE VIEW v_resumen_consolidado_socios AS
SELECT 
    u.id AS socio_id,
    u.cedula,
    u.nombre_completo,
    r.nombre AS rol,
    u.estado AS estado_socio_codigo,
    CASE WHEN u.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado_socio,
    IFNULL((SELECT SUM(CASE WHEN ac.cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END) FROM natillera_ahorros_cuotas ac WHERE ac.socio_id = u.id), 0) AS total_cuotas_base_pagadas,
    IFNULL((SELECT SUM(ac.monto_ahorro_extra) FROM natillera_ahorros_cuotas ac WHERE ac.socio_id = u.id), 0) AS total_ahorro_extra,
    (SELECT COUNT(*) FROM natillera_prestamos p WHERE p.socio_deudor_id = u.id AND p.estado != 'ANULADO') AS total_prestamos_tomados,
    (SELECT IFNULL(SUM(p.monto_prestado), 0) FROM natillera_prestamos p WHERE p.socio_deudor_id = u.id AND p.estado != 'ANULADO') AS monto_total_prestado,
    (SELECT IFNULL(SUM(p.monto_prestado - IFNULL((SELECT SUM(ab.monto_capital_pagado) FROM natillera_abonos_prestamos ab WHERE ab.prestamo_id = p.id), 0)), 0) 
     FROM natillera_prestamos p WHERE p.socio_deudor_id = u.id AND p.estado = 'ACTIVO') AS saldo_pendiente_prestamos,
    (SELECT IFNULL(SUM(ab.monto_interes_pagado), 0) FROM natillera_abonos_prestamos ab JOIN natillera_prestamos p ON ab.prestamo_id = p.id WHERE p.socio_deudor_id = u.id) AS total_intereses_pagados,
    (SELECT IFNULL(SUM(ic.monto_inyectado), 0) FROM natillera_inyecciones_capital ic WHERE ic.socio_id = u.id AND ic.estado = 'ACTIVA') AS total_inyecciones_activas,
    (SELECT IFNULL(SUM(ic.monto_rendimiento_generado), 0) FROM natillera_inyecciones_capital ic WHERE ic.socio_id = u.id AND ic.estado = 'ACTIVA') AS total_rendimiento_inyecciones
FROM natillera_usuarios u
JOIN natillera_roles r ON u.rol_id = r.id;

-- 2. VISTA ESTADO DETALLADO DE PRÉSTAMOS
CREATE OR REPLACE VIEW v_estado_prestamos_detalle AS
SELECT 
    p.id AS prestamo_id,
    p.socio_deudor_id,
    u.nombre_completo AS deudor_nombre,
    u.cedula AS deudor_cedula,
    p.nombre_referencia,
    p.reunion_id,
    r.numero_quincena AS numero_quincena_otorgamiento,
    p.fecha_inicio AS fecha_otorgamiento,
    p.monto_prestado,
    p.tasa_interes_mensual,
    IFNULL(SUM(ab.monto_capital_pagado), 0) AS total_capital_pagado,
    IFNULL(SUM(ab.monto_interes_pagado), 0) AS total_interes_pagado,
    (p.monto_prestado - IFNULL(SUM(ab.monto_capital_pagado), 0)) AS saldo_pendiente_capital,
    p.estado AS estado_prestamo,
    CASE WHEN p.es_autoprestamo = 1 THEN 'SÍ' ELSE 'NO' END AS es_autoprestamo,
    CASE WHEN eb.firma_digital_path IS NOT NULL AND eb.firma_digital_path != '' THEN 'SÍ' ELSE 'NO' END AS tiene_firma_digital,
    CASE WHEN eb.foto_evidencia_path IS NOT NULL AND eb.foto_evidencia_path != '' THEN 'SÍ' ELSE 'NO' END AS tiene_foto_evidencia
FROM natillera_prestamos p
JOIN natillera_usuarios u ON p.socio_deudor_id = u.id
LEFT JOIN natillera_reuniones r ON p.reunion_id = r.id
LEFT JOIN natillera_abonos_prestamos ab ON p.id = ab.prestamo_id
LEFT JOIN natillera_entregas_beneficios eb ON p.id = eb.prestamo_id
GROUP BY p.id, p.socio_deudor_id, u.nombre_completo, u.cedula, p.nombre_referencia, p.reunion_id, r.numero_quincena, p.fecha_inicio, p.monto_prestado, p.tasa_interes_mensual, p.estado, p.es_autoprestamo, eb.firma_digital_path, eb.foto_evidencia_path;

-- 3. VISTA ARQUEO DE CAJA Y FLUJO DE REUNIONES
CREATE OR REPLACE VIEW v_arqueo_caja_reuniones AS
SELECT 
    r.id AS reunion_id,
    r.numero_quincena,
    r.fecha_reunion,
    r.estado AS estado_reunion,
    IFNULL((SELECT SUM(CASE WHEN ac.cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) AS ingreso_cuotas_base,
    IFNULL((SELECT SUM(ac.monto_ahorro_extra) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) AS ingreso_ahorro_extra,
    IFNULL((SELECT SUM(ab.monto_capital_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) AS ingreso_abonos_capital,
    IFNULL((SELECT SUM(ab.monto_interes_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) AS ingreso_intereses_prestamos,
    IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR'), 0) AS ingreso_devolucion_actividades,
    IFNULL((SELECT SUM(ic.monto_inyectado) FROM natillera_inyecciones_capital ic WHERE ic.reunion_id = r.id), 0) AS ingreso_inyecciones_capital,
    (
        IFNULL((SELECT SUM(CASE WHEN ac.cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) +
        IFNULL((SELECT SUM(ac.monto_ahorro_extra) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) +
        IFNULL((SELECT SUM(ab.monto_capital_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) +
        IFNULL((SELECT SUM(ab.monto_interes_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) +
        IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR'), 0) +
        IFNULL((SELECT SUM(ic.monto_inyectado) FROM natillera_inyecciones_capital ic WHERE ic.reunion_id = r.id), 0)
    ) AS total_ingresos_caja,
    IFNULL((SELECT SUM(eb.monto_entregado) FROM natillera_entregas_beneficios eb WHERE eb.reunion_id = r.id AND eb.tipo_beneficio = 'PRESTAMO'), 0) AS egreso_prestamos_otorgados,
    IFNULL((SELECT SUM(ic.monto_inyectado + ic.monto_rendimiento_generado) FROM natillera_inyecciones_capital ic WHERE ic.estado = 'RETIRADA' AND (ic.reunion_id_retiro = r.id OR (ic.reunion_id_retiro IS NULL AND DATE(ic.fecha_retiro) = r.fecha_reunion))), 0) AS egreso_inyecciones_retiradas,
    IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD'), 0) AS egreso_prestamos_actividades,
    (
        IFNULL((SELECT SUM(eb.monto_entregado) FROM natillera_entregas_beneficios eb WHERE eb.reunion_id = r.id AND eb.tipo_beneficio = 'PRESTAMO'), 0) +
        IFNULL((SELECT SUM(ic.monto_inyectado + ic.monto_rendimiento_generado) FROM natillera_inyecciones_capital ic WHERE ic.estado = 'RETIRADA' AND (ic.reunion_id_retiro = r.id OR (ic.reunion_id_retiro IS NULL AND DATE(ic.fecha_retiro) = r.fecha_reunion))), 0) +
        IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD'), 0)
    ) AS total_egresos_caja,
    (
        (
            IFNULL((SELECT SUM(CASE WHEN ac.cuota_pagada = 1 THEN 40000.00 ELSE 0.00 END) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) +
            IFNULL((SELECT SUM(ac.monto_ahorro_extra) FROM natillera_ahorros_cuotas ac WHERE ac.reunion_id = r.id), 0) +
            IFNULL((SELECT SUM(ab.monto_capital_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) +
            IFNULL((SELECT SUM(ab.monto_interes_pagado) FROM natillera_abonos_prestamos ab WHERE ab.reunion_id = r.id), 0) +
            IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'DEVOLUCION_A_CAJA_MAYOR'), 0) +
            IFNULL((SELECT SUM(ic.monto_inyectado) FROM natillera_inyecciones_capital ic WHERE ic.reunion_id = r.id), 0)
        ) - (
            IFNULL((SELECT SUM(eb.monto_entregado) FROM natillera_entregas_beneficios eb WHERE eb.reunion_id = r.id AND eb.tipo_beneficio = 'PRESTAMO'), 0) +
            IFNULL((SELECT SUM(ic.monto_inyectado + ic.monto_rendimiento_generado) FROM natillera_inyecciones_capital ic WHERE ic.estado = 'RETIRADA' AND (ic.reunion_id_retiro = r.id OR (ic.reunion_id_retiro IS NULL AND DATE(ic.fecha_retiro) = r.fecha_reunion))), 0) +
            IFNULL((SELECT SUM(tc.monto) FROM natillera_transferencias_cajas tc WHERE tc.reunion_id = r.id AND tc.tipo_movimiento = 'PRESTAMO_A_ACTIVIDAD'), 0)
        )
    ) AS saldo_neto_quincena
FROM natillera_reuniones r
ORDER BY r.numero_quincena ASC;

-- 4. VISTA DE ACTIVIDADES Y APORTES DE SOCIOS
CREATE OR REPLACE VIEW v_actividades_estado_socios AS
SELECT 
    a.id AS actividad_id,
    a.nombre_actividad,
    a.fecha_actividad,
    a.estado AS estado_actividad,
    ap.socio_id,
    u.nombre_completo AS socio_nombre,
    u.cedula AS socio_cedula,
    ap.cuota_asignada,
    ap.monto_pagado,
    (ap.cuota_asignada - ap.monto_pagado) AS saldo_pendiente,
    ap.ganancia_asignada,
    ap.estado_pago
FROM natillera_actividades a
JOIN natillera_actividad_participantes ap ON a.id = ap.actividad_id
JOIN natillera_usuarios u ON ap.socio_id = u.id;

-- 5. VISTA DE INYECCIONES DE CAPITAL
CREATE OR REPLACE VIEW v_inyecciones_capital_detalle AS
SELECT 
    ic.id AS inyeccion_id,
    ic.socio_id,
    u.nombre_completo AS inversionista_nombre,
    u.cedula AS inversionista_cedula,
    ic.reunion_id AS reunion_apertura_id,
    r1.numero_quincena AS quincena_apertura,
    ic.monto_inyectado,
    ic.tasa_rendimiento_porcentaje,
    ic.monto_rendimiento_generado,
    (ic.monto_inyectado + ic.monto_rendimiento_generado) AS monto_total_devolver,
    ic.fecha_inyeccion,
    ic.fecha_retiro_permitido,
    DATEDIFF(ic.fecha_retiro_permitido, CURDATE()) AS dias_restantes_para_retiro,
    ic.estado AS estado_inyeccion,
    ic.reunion_id_retiro,
    r2.numero_quincena AS quincena_retiro,
    ic.fecha_retiro,
    u2.nombre_completo AS registrado_por_nombre
FROM natillera_inyecciones_capital ic
JOIN natillera_usuarios u ON ic.socio_id = u.id
JOIN natillera_reuniones r1 ON ic.reunion_id = r1.id
JOIN natillera_usuarios u2 ON ic.registrado_por_usuario_id = u2.id
LEFT JOIN natillera_reuniones r2 ON ic.reunion_id_retiro = r2.id;

-- 6. VISTA DE ENTREGAS Y CONSTANCIAS DE EVIDENCIA
CREATE OR REPLACE VIEW v_constancias_entregas_evidencia AS
SELECT 
    eb.id AS entrega_id,
    eb.reunion_id,
    r.numero_quincena,
    eb.socio_id,
    u.nombre_completo AS beneficiario_nombre,
    u.cedula AS beneficiario_cedula,
    eb.tipo_beneficio,
    eb.monto_entregado,
    eb.prestamo_id,
    eb.fecha_entrega,
    CASE WHEN eb.firma_digital_path IS NOT NULL AND eb.firma_digital_path != '' THEN 'SÍ' ELSE 'NO' END AS tiene_firma,
    CASE WHEN eb.foto_evidencia_path IS NOT NULL AND eb.foto_evidencia_path != '' THEN 'SÍ' ELSE 'NO' END AS tiene_foto,
    u2.nombre_completo AS entregado_por_nombre
FROM natillera_entregas_beneficios eb
JOIN natillera_usuarios u ON eb.socio_id = u.id
JOIN natillera_reuniones r ON eb.reunion_id = r.id
JOIN natillera_usuarios u2 ON eb.entregado_por_usuario_id = u2.id;