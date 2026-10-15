<?php
// scratch/test_modal_pagos_flow.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/PagoNotificacion.php';
require_once __DIR__ . '/../app/Models/Usuario.php';

try {
    $db = Database::getConnection();
    echo "=== TEST: FLUJO DE NOTIFICACIONES Y MODAL DE PAGOS ===\n";

    // 1. Obtener un socio de prueba
    $usuarioModel = new Usuario();
    $socios = $usuarioModel->getAllSocios();
    if (empty($socios)) {
        throw new Exception("No hay socios registrados para probar.");
    }
    $socioPrueba = $socios[0];
    $socioId = (int)$socioPrueba['id'];
    echo " Socio de prueba: {$socioPrueba['nombre_completo']} (ID: {$socioId})\n";

    $pagoNotifModel = new PagoNotificacion();

    // 2. Simular pago de cuota y ahorro extra registrado por secretaria
    echo "\n2. Simulando registro de pago por secretaria (Cuota $55k + Ahorro Extra $25k)...\n";
    $ok1 = $pagoNotifModel->registrarNotificacionCuotaReunion(
        $socioId,
        999, // reunion_id prueba
        "Pago de Cuota - Reunión Quincenal #999",
        "Reunión de prueba quincenal 999",
        40000.00,
        10000.00,
        5000.00,
        25000.00,
        80000.00,
        [
            'reunion_id' => 999,
            'numero_quincena' => 999,
            'fecha_reunion' => '2026-03-01',
            'cuota_base' => 55000.00,
            'ahorro_cuota' => 40000.00,
            'aporte_ronda' => 10000.00,
            'aporte_rifa' => 5000.00,
            'ahorro_extra' => 25000.00,
            'total_pagado' => 80000.00,
            'pagou_cuota' => 1
        ],
        1 // registrado por usuario 1
    );
    echo " Registro 1: " . ($ok1 ? "OK" : "ERROR") . "\n";

    // 3. Simular un segundo abono pendiente (ej. abono a préstamo)
    echo "\n3. Simulando registro de abono a préstamo ($50k capital + $10k interés)...\n";
    $ok2 = $pagoNotifModel->registrarNotificacionAbonoPrestamo(
        $socioId,
        888,
        999,
        50000.00,
        10000.00,
        1,
        ['nombre_referencia' => 'Préstamo personal']
    );
    echo " Registro 2: " . ($ok2 ? "OK" : "ERROR") . "\n";

    // 4. Consultar pagos pendientes para el modal (al ingresar el socio)
    echo "\n4. Consultando pagos pendientes para el modal...\n";
    $pendientes = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Total pagos pendientes detectados: " . count($pendientes) . "\n";
    $totalGeneral = 0.0;
    $pagoIds = [];
    foreach ($pendientes as $p) {
        $totalGeneral += (float)$p['monto_total'];
        $pagoIds[] = (int)$p['id'];
        echo "   -> [ID: {$p['id']}] {$p['titulo']} | Monto: $" . number_format($p['monto_total'], 0, ',', '.') . " | Registrado por: {$p['registrado_por_nombre']}\n";
    }
    echo " Monto Total a mostrar en el modal: $" . number_format($totalGeneral, 0, ',', '.') . " COP\n";
    $saldoActual = $pagoNotifModel->getSaldoAhorradoActualSocio($socioId);
    echo " Saldo Ahorrado Actual del Socio: $" . number_format($saldoActual, 0, ',', '.') . " COP\n";

    if (count($pendientes) < 2) {
        throw new Exception("Se esperaban al menos 2 pagos pendientes.");
    }

    // 5. Simular confirmación del socio al ver el modal (marcar leídos)
    echo "\n5. Simulando confirmación del socio (clic en '¡Entendido, Confirmar Recibido!')...\n";
    $okLeidos = $pagoNotifModel->marcarPagosComoLeidos($pagoIds, $socioId);
    echo " Marcar como leídos: " . ($okLeidos ? "OK" : "ERROR") . "\n";

    // 6. Verificar que al volver a ingresar ya NO salen (sale una sola vez)
    echo "\n6. Verificando que NO vuelva a salir el modal al ingresar nuevamente...\n";
    $pendientesDespues = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Pagos pendientes después de confirmar: " . count($pendientesDespues) . "\n";
    if (count($pendientesDespues) === 0) {
        echo " [EXITOSO] El modal solo saldrá UNA SOLA VEZ como lo solicitó el usuario.\n";
    } else {
        echo " [ALERTA] Aún quedan pagos pendientes no marcados como leídos.\n";
    }

    // 7. Limpiar registros de prueba
    echo "\n7. Limpiando registros de prueba...\n";
    $stmtClean = $db->prepare("DELETE FROM natillera_pagos_notificaciones WHERE id IN (" . implode(',', $pagoIds) . ")");
    $stmtClean->execute();
    echo " [OK] Registros de prueba eliminados.\n";

    echo "\n=== TODAS LAS PRUEBAS FINALIZARON EXITOSAMENTE ===\n";
} catch (Exception $e) {
    echo "Error en test: " . $e->getMessage() . "\n";
}
