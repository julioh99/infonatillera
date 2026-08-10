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