<?php
// scratch/test_password_and_notifs.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Models/PushSubscription.php';

try {
    $db = Database::getConnection();

    // 1. Verificar columna password_changed
    $stmt = $db->query("SHOW COLUMNS FROM natillera_usuarios LIKE 'password_changed'");
    $col = $stmt->fetch();
    echo "1. Columna 'password_changed': " . ($col ? "OK (Tipo: {$col['Type']}, Default: {$col['Default']})" : "NO EXISTE") . "\n";

    // 2. Verificar tabla natillera_notificaciones_leidas
    $stmt2 = $db->query("SHOW TABLES LIKE 'natillera_notificaciones_leidas'");
    $tbl = $stmt2->fetch();
    echo "2. Tabla 'natillera_notificaciones_leidas': " . ($tbl ? "OK" : "NO EXISTE") . "\n";

    // 3. Probar getNotificacionesPendientesPorSocio
    $pushModel = new PushSubscription();
    $pendientes = $pushModel->getNotificacionesPendientesPorSocio(1);
    echo "3. Notificaciones pendientes para socio ID=1: " . count($pendientes) . "\n";

    echo "\n[OK] Todas las comprobaciones de backend finalizaron exitosamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
