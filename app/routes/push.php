<?php
// app/routes/push.php - rutas del modulo Web Push ($router lo entrega public/index.php)
require_once __DIR__ . '/../Controllers/NotificacionController.php';

// Envia una notificacion de prueba solo a los dispositivos de quien la pide
$router->post('/admin/notificaciones/prueba', [NotificacionController::class, 'prueba']);
