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
    monto_cuota DECIMAL(10,2) NOT NULL, -- Siempre $40.000 COP acreditado al socio
    monto_aporte_ronda DECIMAL(10,2) DEFAULT 0.00,
    monto_aporte_rifa DECIMAL(10,2) DEFAULT 0.00,
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
    nombre_referencia VARCHAR(150), -- Nombre de referencia / Persona referente opcional
    monto_prestado DECIMAL(10,2) NOT NULL,
    tasa_interes_mensual DECIMAL(5,2) DEFAULT 10.00,
    tipo_prestamo VARCHAR(20) DEFAULT 'DIRECTO', -- 'DIRECTO', 'AUTOPRESTAMO'
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    es_autoprestamo BOOLEAN DEFAULT 0,
    anulado_sin_interes BOOLEAN DEFAULT 0,
    estado VARCHAR(20) DEFAULT 'ACTIVO', -- 'ACTIVO', 'PAGADO', 'ANULADO'
    FOREIGN KEY (socio_deudor_id) REFERENCES usuarios(id)
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
    cuota_por_socio DECIMAL(10,2) DEFAULT 0.00, -- Cuota a pagar asignada a cada socio
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

-- ABONOS A ACTIVIDADES COMUNITARIAS
CREATE TABLE abonos_actividades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actividad_participante_id INTEGER NOT NULL,
    monto_abono DECIMAL(10,2) NOT NULL,
    fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacion VARCHAR(255),
    registrado_por_usuario_id INTEGER NOT NULL,
    FOREIGN KEY (actividad_participante_id) REFERENCES actividad_participantes(id),
    FOREIGN KEY (registrado_por_usuario_id) REFERENCES usuarios(id)
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

-- TABLA DE PLANIFICACIÓN DE RONDAS Y RIFAS POR REUNIÓN
CREATE TABLE fondo_beneficios_cronograma (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reunion_id INTEGER NOT NULL,
    tipo_beneficio VARCHAR(10) NOT NULL, -- 'RONDA' ($300k) o 'RIFA' ($150k)
    aporte_por_socio DECIMAL(10,2) NOT NULL, -- $10.000 (o $20.000) para Ronda; $5.000 (o $10.000) para Rifa
    total_recaudado DECIMAL(10,2) NOT NULL, -- Ej: 50 * $10.000 = $500.000
    monto_beneficio_unidad DECIMAL(10,2) NOT NULL, -- $300.000 para Ronda, $150.000 para Rifa
    saldo_restante_reunion DECIMAL(10,2) NOT NULL,
    saldo_acumulado_fondo DECIMAL(10,2) NOT NULL, -- Saldo acumulado arrastrado a la sig. reunión
    personas_liberadas_planificadas INTEGER NOT NULL, -- Cantidad de ganadores en esta quincena (1, 2, 3 o 4)
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id)
);

-- REGISTRO Y EVIDENCIA DE ENTREGAS A BENEFICIARIOS
CREATE TABLE entregas_beneficios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reunion_id INTEGER NOT NULL,
    socio_id INTEGER NOT NULL,
    tipo_beneficio VARCHAR(10) NOT NULL, -- 'RONDA' o 'RIFA'
    monto_entregado DECIMAL(10,2) NOT NULL,
    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
    firma_digital_path VARCHAR(255), -- Ruta de la firma guardada en formato SVG/PNG
    foto_evidencia_path VARCHAR(255), -- Ruta de la foto del socio recibiendo el premio
    entregado_por_usuario_id INTEGER NOT NULL,
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id),
    FOREIGN KEY (socio_id) REFERENCES usuarios(id),
    FOREIGN KEY (entregado_por_usuario_id) REFERENCES usuarios(id)
);