-- InfoNatillera: esquema de referencia (SOLO estructura, sin datos).
-- Generado con: php scripts/generar_db_sql.php  (despues de php scripts/migrate.php)
-- No lo edites a mano. Lo carga la migracion 00000000_0000_baseline solo en una base vacia.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `natillera_abonos_actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_participante_id` int NOT NULL,
  `monto_abono` decimal(10,2) NOT NULL,
  `fecha_abono` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacion` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registrado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `actividad_participante_id` (`actividad_participante_id`),
  KEY `registrado_por_usuario_id` (`registrado_por_usuario_id`),
  CONSTRAINT `natillera_abonos_actividades_ibfk_1` FOREIGN KEY (`actividad_participante_id`) REFERENCES `natillera_actividad_participantes` (`id`),
  CONSTRAINT `natillera_abonos_actividades_ibfk_2` FOREIGN KEY (`registrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_abonos_prestamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prestamo_id` int NOT NULL,
  `reunion_id` int DEFAULT NULL,
  `monto_interes_pagado` decimal(10,2) DEFAULT '0.00',
  `monto_capital_pagado` decimal(10,2) DEFAULT '0.00',
  `fecha_abono` datetime DEFAULT CURRENT_TIMESTAMP,
  `registrado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `prestamo_id` (`prestamo_id`),
  KEY `registrado_por_usuario_id` (`registrado_por_usuario_id`),
  KEY `fk_abonos_reunion` (`reunion_id`),
  CONSTRAINT `fk_abonos_reunion` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_abonos_prestamos_ibfk_1` FOREIGN KEY (`prestamo_id`) REFERENCES `natillera_prestamos` (`id`),
  CONSTRAINT `natillera_abonos_prestamos_ibfk_2` FOREIGN KEY (`registrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_actividad_participantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `socio_id` int NOT NULL,
  `cuota_asignada` decimal(10,2) DEFAULT '0.00',
  `monto_pagado` decimal(10,2) DEFAULT '0.00',
  `ganancia_asignada` decimal(10,2) DEFAULT '0.00',
  `estado_pago` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'PENDIENTE',
  PRIMARY KEY (`id`),
  KEY `actividad_id` (`actividad_id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `natillera_actividad_participantes_ibfk_1` FOREIGN KEY (`actividad_id`) REFERENCES `natillera_actividades` (`id`),
  CONSTRAINT `natillera_actividad_participantes_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_actividad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `fecha_actividad` date NOT NULL,
  `ingresos_totales` decimal(10,2) DEFAULT '0.00',
  `gastos_totales` decimal(10,2) DEFAULT '0.00',
  `ganancia_neta` decimal(10,2) DEFAULT '0.00',
  `cuota_por_socio` decimal(10,2) DEFAULT '0.00',
  `estado` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'EN_PROCESO',
  `creado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creado_por_usuario_id` (`creado_por_usuario_id`),
  CONSTRAINT `natillera_actividades_ibfk_1` FOREIGN KEY (`creado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_ahorros_cuotas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `socio_id` int NOT NULL,
  `cuota_pagada` tinyint(1) DEFAULT '0',
  `monto_cuota` decimal(10,2) NOT NULL,
  `monto_aporte_ronda` decimal(10,2) DEFAULT '0.00',
  `monto_aporte_rifa` decimal(10,2) DEFAULT '0.00',
  `monto_ahorro_extra` decimal(10,2) DEFAULT '0.00',
  `autoprestamo_generado` tinyint(1) DEFAULT '0',
  `prestamo_id_asociado` int DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ahorros_reunion_socio` (`reunion_id`,`socio_id`),
  KEY `reunion_id` (`reunion_id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `natillera_ahorros_cuotas_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_ahorros_cuotas_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_auditoria` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `accion` varchar(80) NOT NULL,
  `entidad` varchar(80) NOT NULL,
  `entidad_id` int DEFAULT NULL,
  `datos_antes` json DEFAULT NULL,
  `datos_despues` json DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aud_entidad` (`entidad`,`entidad_id`),
  KEY `idx_aud_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_cierres_reunion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `total_ingresos_cuotas_base` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_ahorro_extra` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_rondas_rifas` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_abono_capital` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_intereses_prestamos` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_actividades` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_inyecciones` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_general` decimal(10,2) DEFAULT '0.00',
  `total_egresos_prestamos_otorgados` decimal(10,2) DEFAULT '0.00',
  `total_egresos_premios_entregados` decimal(10,2) DEFAULT '0.00',
  `total_egresos_inyecciones_devueltas` decimal(10,2) DEFAULT '0.00',
  `total_egresos_general` decimal(10,2) DEFAULT '0.00',
  `saldo_neto_reunion` decimal(10,2) DEFAULT '0.00',
  `saldo_acumulado_caja` decimal(10,2) DEFAULT '0.00',
  `desglose_json` longtext COLLATE utf8mb4_general_ci,
  `fecha_cierre` datetime DEFAULT CURRENT_TIMESTAMP,
  `cerrado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reunion_id` (`reunion_id`),
  KEY `cerrado_por_usuario_id` (`cerrado_por_usuario_id`),
  CONSTRAINT `natillera_cierres_reunion_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_cierres_reunion_ibfk_2` FOREIGN KEY (`cerrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_entregas_beneficios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `socio_id` int NOT NULL,
  `prestamo_id` int DEFAULT NULL,
  `tipo_beneficio` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `monto_entregado` decimal(10,2) NOT NULL,
  `fecha_entrega` datetime DEFAULT CURRENT_TIMESTAMP,
  `firma_digital_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto_evidencia_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entregado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reunion_id` (`reunion_id`),
  KEY `socio_id` (`socio_id`),
  KEY `entregado_por_usuario_id` (`entregado_por_usuario_id`),
  KEY `fk_entregas_prestamo` (`prestamo_id`),
  CONSTRAINT `fk_entregas_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `natillera_prestamos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `natillera_entregas_beneficios_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_entregas_beneficios_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`),
  CONSTRAINT `natillera_entregas_beneficios_ibfk_3` FOREIGN KEY (`entregado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_fondo_beneficios_cronograma` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `tipo_beneficio` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `aporte_por_socio` decimal(10,2) NOT NULL,
  `total_recaudado` decimal(10,2) NOT NULL,
  `monto_beneficio_unidad` decimal(10,2) NOT NULL,
  `saldo_restante_reunion` decimal(10,2) NOT NULL,
  `saldo_acumulado_fondo` decimal(10,2) NOT NULL,
  `personas_liberadas_planificadas` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reunion_id` (`reunion_id`),
  CONSTRAINT `natillera_fondo_beneficios_cronograma_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_historico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'ANIO',
  `cargado` tinyint(1) NOT NULL DEFAULT '0',
  `snapshot` longblob NOT NULL,
  `huella` char(64) NOT NULL,
  `conteos_json` text,
  `archivo` varchar(255) DEFAULT NULL,
  `origen_id` int DEFAULT NULL,
  `creado_por_usuario_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_historico_tipo` (`tipo`,`cargado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_inyecciones_capital` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `reunion_id` int NOT NULL,
  `reunion_id_retiro` int DEFAULT NULL,
  `monto_inyectado` decimal(10,2) NOT NULL,
  `tasa_rendimiento_porcentaje` decimal(5,2) DEFAULT '5.00',
  `monto_rendimiento_generado` decimal(10,2) DEFAULT '0.00',
  `fecha_inyeccion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_retiro_permitido` date NOT NULL,
  `estado` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'ACTIVA',
  `fecha_retiro` datetime DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registrado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  KEY `reunion_id` (`reunion_id`),
  KEY `registrado_por_usuario_id` (`registrado_por_usuario_id`),
  KEY `fk_inyecciones_reunion_retiro` (`reunion_id_retiro`),
  CONSTRAINT `fk_inyecciones_reunion_retiro` FOREIGN KEY (`reunion_id_retiro`) REFERENCES `natillera_reuniones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `natillera_inyecciones_capital_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`),
  CONSTRAINT `natillera_inyecciones_capital_ibfk_2` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_inyecciones_capital_ibfk_3` FOREIGN KEY (`registrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_login_intentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(30) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_intento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_cedula_ip_fecha` (`cedula`,`ip`,`fecha_intento`),
  KEY `idx_login_fecha` (`fecha_intento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_migraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(190) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `huella` char(64) NOT NULL,
  `aplicada_en` datetime NOT NULL,
  `duracion_ms` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migracion_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_general_ci NOT NULL,
  `destinatario_tipo` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'TODOS',
  `socio_id` int DEFAULT NULL,
  `enviado_por_usuario_id` int NOT NULL,
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  KEY `enviado_por_usuario_id` (`enviado_por_usuario_id`),
  CONSTRAINT `natillera_notificaciones_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`),
  CONSTRAINT `natillera_notificaciones_ibfk_2` FOREIGN KEY (`enviado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_notificaciones_leidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacion_id` int NOT NULL,
  `socio_id` int NOT NULL,
  `fecha_lectura` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_notif_socio` (`notificacion_id`,`socio_id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `natillera_notificaciones_leidas_ibfk_1` FOREIGN KEY (`notificacion_id`) REFERENCES `natillera_notificaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `natillera_notificaciones_leidas_ibfk_2` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_pagos_notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `reunion_id` int DEFAULT NULL,
  `tipo_pago` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `referencia_id` int DEFAULT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `monto_cuota` decimal(10,2) DEFAULT '0.00',
  `monto_aporte_ronda` decimal(10,2) DEFAULT '0.00',
  `monto_aporte_rifa` decimal(10,2) DEFAULT '0.00',
  `monto_ahorro_extra` decimal(10,2) DEFAULT '0.00',
  `monto_total` decimal(10,2) NOT NULL,
  `detalles_json` longtext COLLATE utf8mb4_general_ci,
  `registrado_por_usuario_id` int NOT NULL,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  `leido` tinyint(1) DEFAULT '0',
  `fecha_leido` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pago_socio_leido` (`socio_id`,`leido`),
  KEY `idx_pago_socio_reunion` (`socio_id`,`reunion_id`),
  KEY `registrado_por_usuario_id` (`registrado_por_usuario_id`),
  CONSTRAINT `natillera_pagos_notificaciones_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `natillera_pagos_notificaciones_ibfk_2` FOREIGN KEY (`registrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_prestamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_deudor_id` int NOT NULL,
  `reunion_id` int DEFAULT NULL,
  `fecha_prestamo` date DEFAULT NULL,
  `nombre_referencia` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `monto_prestado` decimal(10,2) NOT NULL,
  `tasa_interes_mensual` decimal(5,2) DEFAULT '10.00',
  `tipo_prestamo` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'DIRECTO',
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `es_autoprestamo` tinyint(1) DEFAULT '0',
  `anulado_sin_interes` tinyint(1) DEFAULT '0',
  `estado` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'ACTIVO',
  PRIMARY KEY (`id`),
  KEY `socio_deudor_id` (`socio_deudor_id`),
  KEY `fk_prestamos_reunion` (`reunion_id`),
  CONSTRAINT `fk_prestamos_reunion` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_prestamos_ibfk_1` FOREIGN KEY (`socio_deudor_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_push_envios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notificacion_id` int DEFAULT NULL,
  `suscripcion_id` int DEFAULT NULL,
  `socio_id` int DEFAULT NULL,
  `http_status` smallint NOT NULL DEFAULT '0',
  `exitoso` tinyint(1) NOT NULL DEFAULT '0',
  `detalle` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_push_envios_notif` (`notificacion_id`),
  KEY `idx_push_envios_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `socio_id` int NOT NULL,
  `endpoint` text COLLATE utf8mb4_general_ci NOT NULL,
  `p256dh` text COLLATE utf8mb4_general_ci NOT NULL,
  `auth` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `socio_id` (`socio_id`),
  CONSTRAINT `natillera_push_subscriptions_ibfk_1` FOREIGN KEY (`socio_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_recordatorios_enviados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `socio_id` int NOT NULL,
  `franja` varchar(5) NOT NULL,
  `notificacion_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recordatorio` (`reunion_id`,`socio_id`,`franja`),
  KEY `idx_recordatorio_reunion` (`reunion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `natillera_reuniones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_quincena` int NOT NULL,
  `fecha_reunion` date NOT NULL,
  `hora_reunion` time DEFAULT '14:00:00',
  `valor_cuota_base` decimal(10,2) NOT NULL DEFAULT '55000.00',
  `tipo_evento_extra` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'NINGUNO',
  `monto_premio_extra` decimal(10,2) DEFAULT '0.00',
  `ganador_socio_id` int DEFAULT NULL,
  `estado` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'PROGRAMADA',
  `aporte_ronda` decimal(10,2) DEFAULT NULL,
  `aporte_rifa` decimal(10,2) DEFAULT NULL,
  `premios_ronda_planificados` int DEFAULT NULL,
  `premios_rifa_planificados` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ganador_socio_id` (`ganador_socio_id`),
  CONSTRAINT `natillera_reuniones_ibfk_1` FOREIGN KEY (`ganador_socio_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_transferencias_cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reunion_id` int NOT NULL,
  `actividad_id` int DEFAULT NULL,
  `tipo_movimiento` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_transferencia` datetime DEFAULT CURRENT_TIMESTAMP,
  `registrado_por_usuario_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `reunion_id` (`reunion_id`),
  KEY `actividad_id` (`actividad_id`),
  KEY `registrado_por_usuario_id` (`registrado_por_usuario_id`),
  CONSTRAINT `natillera_transferencias_cajas_ibfk_1` FOREIGN KEY (`reunion_id`) REFERENCES `natillera_reuniones` (`id`),
  CONSTRAINT `natillera_transferencias_cajas_ibfk_2` FOREIGN KEY (`actividad_id`) REFERENCES `natillera_actividades` (`id`),
  CONSTRAINT `natillera_transferencias_cajas_ibfk_3` FOREIGN KEY (`registrado_por_usuario_id`) REFERENCES `natillera_usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `natillera_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rol_id` int NOT NULL,
  `tope_prestamo_personalizado` decimal(10,2) DEFAULT '2000000.00',
  `interes_minimo_meta` decimal(10,2) DEFAULT '400000.00',
  `estado` tinyint(1) DEFAULT '1',
  `password_changed` tinyint(1) DEFAULT '0',
  `orden` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `natillera_usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `natillera_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_actividades_estado_socios` AS select `a`.`id` AS `actividad_id`,`a`.`nombre_actividad` AS `nombre_actividad`,`a`.`fecha_actividad` AS `fecha_actividad`,`a`.`estado` AS `estado_actividad`,`ap`.`socio_id` AS `socio_id`,`u`.`nombre_completo` AS `socio_nombre`,`u`.`cedula` AS `socio_cedula`,`ap`.`cuota_asignada` AS `cuota_asignada`,`ap`.`monto_pagado` AS `monto_pagado`,(`ap`.`cuota_asignada` - `ap`.`monto_pagado`) AS `saldo_pendiente`,`ap`.`ganancia_asignada` AS `ganancia_asignada`,`ap`.`estado_pago` AS `estado_pago` from ((`natillera_actividades` `a` join `natillera_actividad_participantes` `ap` on((`a`.`id` = `ap`.`actividad_id`))) join `natillera_usuarios` `u` on((`ap`.`socio_id` = `u`.`id`)));

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_arqueo_caja_reuniones` AS select `r`.`id` AS `reunion_id`,`r`.`numero_quincena` AS `numero_quincena`,`r`.`fecha_reunion` AS `fecha_reunion`,`r`.`estado` AS `estado_reunion`,ifnull((select sum((case when (`ac`.`cuota_pagada` = 1) then 40000.00 else 0.00 end)) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0) AS `ingreso_cuotas_base`,ifnull((select sum(`ac`.`monto_ahorro_extra`) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0) AS `ingreso_ahorro_extra`,ifnull((select sum(`ab`.`monto_capital_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0) AS `ingreso_abonos_capital`,ifnull((select sum(`ab`.`monto_interes_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0) AS `ingreso_intereses_prestamos`,ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'DEVOLUCION_A_CAJA_MAYOR'))),0) AS `ingreso_devolucion_actividades`,ifnull((select sum(`ic`.`monto_inyectado`) from `natillera_inyecciones_capital` `ic` where (`ic`.`reunion_id` = `r`.`id`)),0) AS `ingreso_inyecciones_capital`,(((((ifnull((select sum((case when (`ac`.`cuota_pagada` = 1) then 40000.00 else 0.00 end)) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0) + ifnull((select sum(`ac`.`monto_ahorro_extra`) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`ab`.`monto_capital_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`ab`.`monto_interes_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'DEVOLUCION_A_CAJA_MAYOR'))),0)) + ifnull((select sum(`ic`.`monto_inyectado`) from `natillera_inyecciones_capital` `ic` where (`ic`.`reunion_id` = `r`.`id`)),0)) AS `total_ingresos_caja`,ifnull((select sum(`eb`.`monto_entregado`) from `natillera_entregas_beneficios` `eb` where ((`eb`.`reunion_id` = `r`.`id`) and (`eb`.`tipo_beneficio` = 'PRESTAMO'))),0) AS `egreso_prestamos_otorgados`,ifnull((select sum((`ic`.`monto_inyectado` + `ic`.`monto_rendimiento_generado`)) from `natillera_inyecciones_capital` `ic` where ((`ic`.`estado` = 'RETIRADA') and ((`ic`.`reunion_id_retiro` = `r`.`id`) or ((`ic`.`reunion_id_retiro` is null) and (cast(`ic`.`fecha_retiro` as date) = `r`.`fecha_reunion`))))),0) AS `egreso_inyecciones_retiradas`,ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'PRESTAMO_A_ACTIVIDAD'))),0) AS `egreso_prestamos_actividades`,((ifnull((select sum(`eb`.`monto_entregado`) from `natillera_entregas_beneficios` `eb` where ((`eb`.`reunion_id` = `r`.`id`) and (`eb`.`tipo_beneficio` = 'PRESTAMO'))),0) + ifnull((select sum((`ic`.`monto_inyectado` + `ic`.`monto_rendimiento_generado`)) from `natillera_inyecciones_capital` `ic` where ((`ic`.`estado` = 'RETIRADA') and ((`ic`.`reunion_id_retiro` = `r`.`id`) or ((`ic`.`reunion_id_retiro` is null) and (cast(`ic`.`fecha_retiro` as date) = `r`.`fecha_reunion`))))),0)) + ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'PRESTAMO_A_ACTIVIDAD'))),0)) AS `total_egresos_caja`,((((((ifnull((select sum((case when (`ac`.`cuota_pagada` = 1) then 40000.00 else 0.00 end)) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0) + ifnull((select sum(`ac`.`monto_ahorro_extra`) from `natillera_ahorros_cuotas` `ac` where (`ac`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`ab`.`monto_capital_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`ab`.`monto_interes_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`reunion_id` = `r`.`id`)),0)) + ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'DEVOLUCION_A_CAJA_MAYOR'))),0)) + ifnull((select sum(`ic`.`monto_inyectado`) from `natillera_inyecciones_capital` `ic` where (`ic`.`reunion_id` = `r`.`id`)),0)) - ((ifnull((select sum(`eb`.`monto_entregado`) from `natillera_entregas_beneficios` `eb` where ((`eb`.`reunion_id` = `r`.`id`) and (`eb`.`tipo_beneficio` = 'PRESTAMO'))),0) + ifnull((select sum((`ic`.`monto_inyectado` + `ic`.`monto_rendimiento_generado`)) from `natillera_inyecciones_capital` `ic` where ((`ic`.`estado` = 'RETIRADA') and ((`ic`.`reunion_id_retiro` = `r`.`id`) or ((`ic`.`reunion_id_retiro` is null) and (cast(`ic`.`fecha_retiro` as date) = `r`.`fecha_reunion`))))),0)) + ifnull((select sum(`tc`.`monto`) from `natillera_transferencias_cajas` `tc` where ((`tc`.`reunion_id` = `r`.`id`) and (`tc`.`tipo_movimiento` = 'PRESTAMO_A_ACTIVIDAD'))),0))) AS `saldo_neto_quincena` from `natillera_reuniones` `r` order by `r`.`numero_quincena`;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_constancias_entregas_evidencia` AS select `eb`.`id` AS `entrega_id`,`eb`.`reunion_id` AS `reunion_id`,`r`.`numero_quincena` AS `numero_quincena`,`eb`.`socio_id` AS `socio_id`,`u`.`nombre_completo` AS `beneficiario_nombre`,`u`.`cedula` AS `beneficiario_cedula`,`eb`.`tipo_beneficio` AS `tipo_beneficio`,`eb`.`monto_entregado` AS `monto_entregado`,`eb`.`prestamo_id` AS `prestamo_id`,`eb`.`fecha_entrega` AS `fecha_entrega`,(case when ((`eb`.`firma_digital_path` is not null) and (`eb`.`firma_digital_path` <> '')) then 'SÍ' else 'NO' end) AS `tiene_firma`,(case when ((`eb`.`foto_evidencia_path` is not null) and (`eb`.`foto_evidencia_path` <> '')) then 'SÍ' else 'NO' end) AS `tiene_foto`,`u2`.`nombre_completo` AS `entregado_por_nombre` from (((`natillera_entregas_beneficios` `eb` join `natillera_usuarios` `u` on((`eb`.`socio_id` = `u`.`id`))) join `natillera_reuniones` `r` on((`eb`.`reunion_id` = `r`.`id`))) join `natillera_usuarios` `u2` on((`eb`.`entregado_por_usuario_id` = `u2`.`id`)));

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_estado_prestamos_detalle` AS select `p`.`id` AS `prestamo_id`,`p`.`socio_deudor_id` AS `socio_deudor_id`,`u`.`nombre_completo` AS `deudor_nombre`,`u`.`cedula` AS `deudor_cedula`,`p`.`nombre_referencia` AS `nombre_referencia`,`p`.`reunion_id` AS `reunion_id`,`r`.`numero_quincena` AS `numero_quincena_otorgamiento`,`p`.`fecha_inicio` AS `fecha_otorgamiento`,`p`.`monto_prestado` AS `monto_prestado`,`p`.`tasa_interes_mensual` AS `tasa_interes_mensual`,ifnull(sum(`ab`.`monto_capital_pagado`),0) AS `total_capital_pagado`,ifnull(sum(`ab`.`monto_interes_pagado`),0) AS `total_interes_pagado`,(`p`.`monto_prestado` - ifnull(sum(`ab`.`monto_capital_pagado`),0)) AS `saldo_pendiente_capital`,`p`.`estado` AS `estado_prestamo`,(case when (`p`.`es_autoprestamo` = 1) then 'SÍ' else 'NO' end) AS `es_autoprestamo`,(case when ((`eb`.`firma_digital_path` is not null) and (`eb`.`firma_digital_path` <> '')) then 'SÍ' else 'NO' end) AS `tiene_firma_digital`,(case when ((`eb`.`foto_evidencia_path` is not null) and (`eb`.`foto_evidencia_path` <> '')) then 'SÍ' else 'NO' end) AS `tiene_foto_evidencia` from ((((`natillera_prestamos` `p` join `natillera_usuarios` `u` on((`p`.`socio_deudor_id` = `u`.`id`))) left join `natillera_reuniones` `r` on((`p`.`reunion_id` = `r`.`id`))) left join `natillera_abonos_prestamos` `ab` on((`p`.`id` = `ab`.`prestamo_id`))) left join `natillera_entregas_beneficios` `eb` on((`p`.`id` = `eb`.`prestamo_id`))) group by `p`.`id`,`p`.`socio_deudor_id`,`u`.`nombre_completo`,`u`.`cedula`,`p`.`nombre_referencia`,`p`.`reunion_id`,`r`.`numero_quincena`,`p`.`fecha_inicio`,`p`.`monto_prestado`,`p`.`tasa_interes_mensual`,`p`.`estado`,`p`.`es_autoprestamo`,`eb`.`firma_digital_path`,`eb`.`foto_evidencia_path`;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_inyecciones_capital_detalle` AS select `ic`.`id` AS `inyeccion_id`,`ic`.`socio_id` AS `socio_id`,`u`.`nombre_completo` AS `inversionista_nombre`,`u`.`cedula` AS `inversionista_cedula`,`ic`.`reunion_id` AS `reunion_apertura_id`,`r1`.`numero_quincena` AS `quincena_apertura`,`ic`.`monto_inyectado` AS `monto_inyectado`,`ic`.`tasa_rendimiento_porcentaje` AS `tasa_rendimiento_porcentaje`,`ic`.`monto_rendimiento_generado` AS `monto_rendimiento_generado`,(`ic`.`monto_inyectado` + `ic`.`monto_rendimiento_generado`) AS `monto_total_devolver`,`ic`.`fecha_inyeccion` AS `fecha_inyeccion`,`ic`.`fecha_retiro_permitido` AS `fecha_retiro_permitido`,(to_days(`ic`.`fecha_retiro_permitido`) - to_days(curdate())) AS `dias_restantes_para_retiro`,`ic`.`estado` AS `estado_inyeccion`,`ic`.`reunion_id_retiro` AS `reunion_id_retiro`,`r2`.`numero_quincena` AS `quincena_retiro`,`ic`.`fecha_retiro` AS `fecha_retiro`,`u2`.`nombre_completo` AS `registrado_por_nombre` from ((((`natillera_inyecciones_capital` `ic` join `natillera_usuarios` `u` on((`ic`.`socio_id` = `u`.`id`))) join `natillera_reuniones` `r1` on((`ic`.`reunion_id` = `r1`.`id`))) join `natillera_usuarios` `u2` on((`ic`.`registrado_por_usuario_id` = `u2`.`id`))) left join `natillera_reuniones` `r2` on((`ic`.`reunion_id_retiro` = `r2`.`id`)));

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `v_resumen_consolidado_socios` AS select `u`.`id` AS `socio_id`,`u`.`cedula` AS `cedula`,`u`.`nombre_completo` AS `nombre_completo`,`r`.`nombre` AS `rol`,`u`.`estado` AS `estado_socio_codigo`,(case when (`u`.`estado` = 1) then 'ACTIVO' else 'INACTIVO' end) AS `estado_socio`,ifnull((select sum((case when (`ac`.`cuota_pagada` = 1) then 40000.00 else 0.00 end)) from `natillera_ahorros_cuotas` `ac` where (`ac`.`socio_id` = `u`.`id`)),0) AS `total_cuotas_base_pagadas`,ifnull((select sum(`ac`.`monto_ahorro_extra`) from `natillera_ahorros_cuotas` `ac` where (`ac`.`socio_id` = `u`.`id`)),0) AS `total_ahorro_extra`,(select count(0) from `natillera_prestamos` `p` where ((`p`.`socio_deudor_id` = `u`.`id`) and (`p`.`estado` <> 'ANULADO'))) AS `total_prestamos_tomados`,(select ifnull(sum(`p`.`monto_prestado`),0) from `natillera_prestamos` `p` where ((`p`.`socio_deudor_id` = `u`.`id`) and (`p`.`estado` <> 'ANULADO'))) AS `monto_total_prestado`,(select ifnull(sum((`p`.`monto_prestado` - ifnull((select sum(`ab`.`monto_capital_pagado`) from `natillera_abonos_prestamos` `ab` where (`ab`.`prestamo_id` = `p`.`id`)),0))),0) from `natillera_prestamos` `p` where ((`p`.`socio_deudor_id` = `u`.`id`) and (`p`.`estado` = 'ACTIVO'))) AS `saldo_pendiente_prestamos`,(select ifnull(sum(`ab`.`monto_interes_pagado`),0) from (`natillera_abonos_prestamos` `ab` join `natillera_prestamos` `p` on((`ab`.`prestamo_id` = `p`.`id`))) where (`p`.`socio_deudor_id` = `u`.`id`)) AS `total_intereses_pagados`,(select ifnull(sum(`ic`.`monto_inyectado`),0) from `natillera_inyecciones_capital` `ic` where ((`ic`.`socio_id` = `u`.`id`) and (`ic`.`estado` = 'ACTIVA'))) AS `total_inyecciones_activas`,(select ifnull(sum(`ic`.`monto_rendimiento_generado`),0) from `natillera_inyecciones_capital` `ic` where ((`ic`.`socio_id` = `u`.`id`) and (`ic`.`estado` = 'ACTIVA'))) AS `total_rendimiento_inyecciones` from (`natillera_usuarios` `u` join `natillera_roles` `r` on((`u`.`rol_id` = `r`.`id`)));

SET FOREIGN_KEY_CHECKS = 1;
