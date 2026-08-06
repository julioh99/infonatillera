-- TABLA DE ROLES
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE -- 'Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio'
);

-- TABLA DE USUARIOS / SOCIOS
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    password_hash VARCHAR(255) NOT NULL,
    rol_id INTEGER NOT NULL,
    tope_prestamo_personalizado DECIMAL(10,2) DEFAULT 2000000.00, -- Modificable por Sec. General
    interes_minimo_meta DECIMAL(10,2) DEFAULT 400000.00, -- Meta individual de $400k
    estado BOOLEAN DEFAULT 1,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- REUNIONES QUINCENALES
CREATE TABLE reuniones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_quincena INTEGER NOT NULL, -- 1 a 26
    fecha_reunion DATE NOT NULL,
    hora_reunion TIME DEFAULT '14:00:00',
    valor_cuota_base DECIMAL(10,2) NOT NULL DEFAULT 55000.00, -- 55000, 60000, 65000
    tipo_evento_extra VARCHAR(50) DEFAULT 'NINGUNO', -- 'RIFA', 'RONDA', 'NINGUNO'
    monto_premio_extra DECIMAL(10,2) DEFAULT 0.00, -- 150000 o 300000
    ganador_socio_id INTEGER, -- Socio beneficiado con la Rifa/Ronda
    estado VARCHAR(20) DEFAULT 'PROGRAMADA', -- 'PROGRAMADA', 'EN_PROCESO', 'CERRADA'
    FOREIGN KEY (ganador_socio_id) REFERENCES usuarios(id)
);

-- COBROS EN LLAMADO A LISTA
CREATE TABLE ahorros_cuotas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reunion_id INTEGER NOT NULL,
    socio_id INTEGER NOT NULL,
    cuota_pagada BOOLEAN DEFAULT 0,
    monto_cuota DECIMAL(10,2) NOT NULL,
    monto_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
    autoprestamo_generado BOOLEAN DEFAULT 0,
    prestamo_id_asociado INTEGER, -- Si generó autopréstamo
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id),
    FOREIGN KEY (socio_id) REFERENCES usuarios(id)
);

-- PRÉSTAMOS E INTERESES
CREATE TABLE prestamos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    socio_deudor_id INTEGER NOT NULL,
    socio_fiador_id INTEGER, -- NULL si es directo, ID socio si es para un tercero
    tercero_nombre VARCHAR(100),
    monto_prestado DECIMAL(10,2) NOT NULL,
    tasa_interes_mensual DECIMAL(5,2) DEFAULT 10.00, -- Ajustable por Sec. General
    tipo_prestamo VARCHAR(20) DEFAULT 'DIRECTO', -- 'DIRECTO', 'TERCERO', 'AUTOPRESTAMO'
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    es_autoprestamo BOOLEAN DEFAULT 0,
    anulado_sin_interes BOOLEAN DEFAULT 0, -- Si se pagó dentro de las 24 hrs
    estado VARCHAR(20) DEFAULT 'ACTIVO', -- 'ACTIVO', 'PAGADO', 'ANULADO'
    FOREIGN KEY (socio_deudor_id) REFERENCES usuarios(id),
    FOREIGN KEY (socio_fiador_id) REFERENCES usuarios(id)
);

-- ABONOS A PRÉSTAMOS
CREATE TABLE abonos_prestamos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    prestamo_id INTEGER NOT NULL,
    monto_interes_pagado DECIMAL(10,2) DEFAULT 0.00,
    monto_capital_pagado DECIMAL(10,2) DEFAULT 0.00,
    fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
    registrado_por_usuario_id INTEGER NOT NULL,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES usuarios(id)
);

-- ACTIVIDADES (TAMALES, EVENTOS)
CREATE TABLE actividades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_actividad VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha_actividad DATE NOT NULL,
    ingresos_totales DECIMAL(10,2) DEFAULT 0.00,
    gastos_totales DECIMAL(10,2) DEFAULT 0.00,
    ganancia_neta DECIMAL(10,2) DEFAULT 0.00,
    estado VARCHAR(20) DEFAULT 'EN_PROCESO', -- 'EN_PROCESO', 'LIQUIDADA'
    creado_por_usuario_id INTEGER NOT NULL,
    FOREIGN KEY (creado_por_usuario_id) REFERENCES usuarios(id)
);

-- PARTICIPANTES Y DEUDAS EN ACTIVIDADES
CREATE TABLE actividad_participantes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actividad_id INTEGER NOT NULL,
    socio_id INTEGER NOT NULL,
    cuota_asignada DECIMAL(10,2) DEFAULT 0.00, -- Deuda asignada o parte proporcional de gastos
    monto_pagado DECIMAL(10,2) DEFAULT 0.00,
    ganancia_asignada DECIMAL(10,2) DEFAULT 0.00, -- Utilidad que le corresponde de la actividad
    estado_pago VARCHAR(20) DEFAULT 'PENDIENTE', -- 'PENDIENTE', 'PAGADO'
    FOREIGN KEY (actividad_id) REFERENCES actividades(id),
    FOREIGN KEY (socio_id) REFERENCES usuarios(id)
);

-- SUSCRIPCIONES WEB PUSH NOTIFICATIONS
CREATE TABLE push_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    socio_id INTEGER NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES usuarios(id)
);

-- HISTORIAL DE NOTIFICACIONES
CREATE TABLE notificaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    destinatario_tipo VARCHAR(20) DEFAULT 'TODOS', -- 'TODOS', 'SOCIO_ESPECIFICO'
    socio_id INTEGER,
    enviado_por_usuario_id INTEGER NOT NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (socio_id) REFERENCES usuarios(id),
    FOREIGN KEY (enviado_por_usuario_id) REFERENCES usuarios(id)
);