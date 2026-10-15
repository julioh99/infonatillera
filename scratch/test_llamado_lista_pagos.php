<?php
// scratch/test_llamado_lista_pagos.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/PagoNotificacion.php';

try {
    $db = Database::getConnection();
    echo "=== TEST: INTEGRACION LLAMADO A LISTA -> NOTIFICACION MODAL ===\n";

    $reunionModel = new Reunion();
    $reuniones = $reunionModel->getReuniones();
    if (empty($reuniones)) {
        throw new Exception("No hay reuniones.");
    }
    $reunion = $reuniones[0];
    $reunionId = (int)$reunion['id'];

    $pagoNotifModel = new PagoNotificacion();

    // Consultar un socio
    $stmtS = $db->query("SELECT id, nombre_completo FROM natillera_usuarios WHERE rol_id = 5 LIMIT 1");
    $socio = $stmtS->fetch();
    if (!$socio) {
        $stmtS = $db->query("SELECT id, nombre_completo FROM natillera_usuarios LIMIT 1");
        $socio = $stmtS->fetch();
    }
    $socioId = (int)$socio['id'];

    echo "Reunión ID: {$reunionId} (Quincena #{$reunion['numero_quincena']})\n";
    echo "Socio: {$socio['nombre_completo']} (ID: {$socioId})\n";

    // 1. Simular guardar llamado a lista con pago y ahorro extra
    echo "\n1. Guardando llamado a lista con cuota pagada y ahorro extra de $30.000 COP...\n";
    $_SESSION['usuario'] = ['id' => 1, 'nombre_completo' => 'Secretaria Prueba'];

    $registros = [
        [
            'socio_id' => $socioId,
            'pagou_cuota' => 1,
            'ahorro_extra' => 30000.00,
            'generar_autoprestamo' => 0
        ]
    ];

    $ok = $reunionModel->guardarLlamadoListaBatch($reunionId, $registros);
    echo " Batch guardado: " . ($ok ? "OK" : "ERROR") . "\n";

    // 2. Verificar que el socio tiene notificación de pago pendiente
    $pendientes = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Notificaciones pendientes para socio: " . count($pendientes) . "\n";
    if (empty($pendientes)) {
        throw new Exception("No se generó la notificación de pago esperada.");
    }

    $notif = $pendientes[0];
    echo "   -> Titulo: {$notif['titulo']}\n";
    echo "   -> Concepto: {$notif['concepto']}\n";
    echo "   -> Monto Cuota: $" . number_format($notif['monto_cuota'], 0, ',', '.') . "\n";
    echo "   -> Monto Extra: $" . number_format($notif['monto_ahorro_extra'], 0, ',', '.') . "\n";
    echo "   -> Monto Total: $" . number_format($notif['monto_total'], 0, ',', '.') . "\n";

    // 3. Simular que la secretaria vuelve a guardar pero ahora no cambia nada
    echo "\n3. Re-guardando la misma reunión sin cambios (no debe duplicar notificación)...\n";
    $reunionModel->guardarLlamadoListaBatch($reunionId, $registros);
    $pendientes2 = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Notificaciones pendientes tras re-guardar: " . count($pendientes2) . " (Debe ser exactamente 1)\n";
    if (count($pendientes2) !== 1) {
        throw new Exception("Se duplicó la notificación!");
    }

    // 4. Marcar como leído
    echo "\n4. Socio abre modal y confirma recibido...\n";
    $pagoNotifModel->marcarPagosComoLeidos([$notif['id']], $socioId);
    $pendientes3 = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Notificaciones pendientes tras confirmar: " . count($pendientes3) . "\n";

    // 5. Limpieza de prueba
    $db->exec("DELETE FROM natillera_pagos_notificaciones WHERE socio_id = {$socioId} AND reunion_id = {$reunionId}");
    echo "\n[OK] Test de integración completado exitosamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
