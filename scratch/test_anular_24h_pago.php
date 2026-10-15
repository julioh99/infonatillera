<?php
// scratch/test_anular_24h_pago.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/PagoNotificacion.php';

try {
    $db = Database::getConnection();
    echo "=== TEST: ANULAR AUTOPRESTAMO 24H -> NOTIFICACION MODAL ===\n";

    $reunionModel = new Reunion();
    $pagoNotifModel = new PagoNotificacion();

    $reunion = $reunionModel->getReunionActual();
    if (!$reunion) {
        $reuniones = $reunionModel->getReuniones();
        $reunion = $reuniones[0];
    }
    $reunionId = (int)$reunion['id'];

    // Socio de prueba
    $stmtS = $db->query("SELECT id, nombre_completo FROM natillera_usuarios LIMIT 1");
    $socio = $stmtS->fetch();
    $socioId = (int)$socio['id'];

    $_SESSION['usuario'] = ['id' => 1, 'nombre_completo' => 'Secretaria Prueba'];

    // 1. Simular registro con autopréstamo (no pagó en la reunión)
    echo "1. Registrando autopréstamo para socio {$socioId} en reunión {$reunionId}...\n";
    $registros = [
        [
            'socio_id' => $socioId,
            'pagou_cuota' => 0,
            'ahorro_extra' => 0,
            'generar_autoprestamo' => 1
        ]
    ];
    $reunionModel->guardarLlamadoListaBatch($reunionId, $registros);

    // Obtener ahorro_id
    $stmtA = $db->prepare("SELECT id, prestamo_id_asociado FROM natillera_ahorros_cuotas WHERE reunion_id = :r_id AND socio_id = :s_id");
    $stmtA->execute([':r_id' => $reunionId, ':s_id' => $socioId]);
    $ahorro = $stmtA->fetch();
    if (!$ahorro) throw new Exception("No se encontró el ahorro registrado.");

    // Debe tener 0 pagos pendientes porque aún no ha pagado
    $pendientesAntes = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Pagos pendientes mientras tiene autopréstamo: " . count($pendientesAntes) . " (Correcto: 0)\n";

    // 2. Ejecutar Anular 24 Horas
    echo "\n2. Secretaria ejecuta 'Anular 24h' tras recibir el pago del socio...\n";
    $ok24 = $reunionModel->anularAutoprestamo24Horas((int)$ahorro['id']);
    echo " Anular 24h ejecutado: " . ($ok24 ? "OK" : "ERROR") . "\n";

    // 3. Verificar que ahora SI tiene notificación de pago para el modal
    $pendientesDespues = $pagoNotifModel->getPagosPendientesPorSocio($socioId);
    echo " Pagos pendientes tras anulación 24h: " . count($pendientesDespues) . "\n";
    if (empty($pendientesDespues)) {
        throw new Exception("No se generó la notificación de pago por anulación 24h!");
    }
    $p = $pendientesDespues[0];
    echo "   -> Titulo: {$p['titulo']}\n";
    echo "   -> Concepto: {$p['concepto']}\n";
    echo "   -> Monto Total: $" . number_format($p['monto_total'], 0, ',', '.') . "\n";

    // 4. Limpieza
    $pagoNotifModel->marcarPagosComoLeidos([$p['id']], $socioId);
    $db->exec("DELETE FROM natillera_pagos_notificaciones WHERE id = {$p['id']}");
    echo "\n[OK] Test anular 24h finalizado con éxito.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
