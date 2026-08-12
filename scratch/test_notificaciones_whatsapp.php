<?php
// scratch/test_notificaciones_whatsapp.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/PushSubscription.php';

try {
    $pushModel = new PushSubscription();
    $notifs = $pushModel->getNotificacionesPorSocio(1);

    echo "Notificaciones obtenidas para socio ID = 1: " . count($notifs) . "\n";
    foreach (array_slice($notifs, 0, 3) as $n) {
        echo " - Título: {$n['titulo']} | Remitente: {$n['remitente_nombre']} | Fecha: {$n['fecha_envio']}\n";
    }

    echo "\n[OK] Método de consulta de notificaciones para In-App y WhatsApp listo.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
