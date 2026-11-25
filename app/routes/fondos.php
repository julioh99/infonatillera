<?php
// app/routes/fondos.php - Rutas del modulo FONDOS (ronda/rifa por reunion). Recibe $router.

// Simulador: sugiere en que reuniones poner las cuotas extra (JSON, no guarda nada)
$router->get('/admin/reuniones/simular-fondos', [ReunionController::class, 'simularFondos']);
// Aplica la distribucion sugerida a las reuniones NO cerradas del ciclo
$router->post('/admin/reuniones/aplicar-distribucion', [ReunionController::class, 'aplicarDistribucion']);
