<?php
// app/routes/historico.php - recibe $router (lo carga public/index.php)

// Historico de anos (solo Presidente en modo Presidente)
$router->get('/admin/historico', [HistoricoController::class, 'index']);
$router->post('/admin/historico/guardar', [HistoricoController::class, 'guardar']);
$router->post('/admin/historico/cargar', [HistoricoController::class, 'cargar']);
$router->post('/admin/historico/crear', [HistoricoController::class, 'crear']);
$router->post('/admin/historico/recuperar', [HistoricoController::class, 'recuperar']);
$router->get('/admin/historico/descargar', [HistoricoController::class, 'descargar']);

// Orden de socios (Gestion de Socios e Historico)
$router->post('/admin/socios/ordenar', [HistoricoController::class, 'ordenarSocios']);
