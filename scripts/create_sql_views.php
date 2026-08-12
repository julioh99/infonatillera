<?php
// scripts/create_sql_views.php

require_once __DIR__ . '/../config/database.php';

$views = [];

// 1. VISTA RESUMEN CONSOLIDADO SOCIOS
$views['v_resumen_consolidado_socios'] = "
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
";

// 2. VISTA ESTADO DETALLADO DE PRÉSTAMOS
$views['v_estado_prestamos_detalle'] = "
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
";

// 3. VISTA ARQUEO DE CAJA Y FLUJO DE REUNIONES
$views['v_arqueo_caja_reuniones'] = "
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
";

// 4. VISTA DE ACTIVIDADES Y APORTES DE SOCIOS
$views['v_actividades_estado_socios'] = "
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
";

// 5. VISTA DE INYECCIONES DE CAPITAL
$views['v_inyecciones_capital_detalle'] = "
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
";

// 6. VISTA DE ENTREGAS Y CONSTANCIAS DE EVIDENCIA
$views['v_constancias_entregas_evidencia'] = "
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
";

try {
    $db = Database::getConnection();

    echo "=== CREANDO VISTAS EN BASE DE DATOS LOCAL ===\n";
    foreach ($views as $name => $sql) {
        $db->exec($sql);
        echo " [OK] Vista creada: {$name}\n";
    }

    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "\n=== CREANDO VISTAS EN SERVIDOR REMOTO ({$host} - {$dbName}) ===\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    foreach ($views as $name => $sql) {
        $remotePdo->exec($sql);
        echo " [OK] Vista remota creada: {$name}\n";
    }

    echo "\n¡Todas las vistas SQL se crearon e instalaron exitosamente en local y producción!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
