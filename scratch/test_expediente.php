<?php
session_start();
$_SESSION['usuario'] = [
    'id' => 1,
    'nombre_completo' => 'Admin Test',
    'rol_nombre' => 'Presidente'
];

$_GET['socio_id'] = 1;
$_SERVER['REQUEST_URI'] = '/admin/socios/expediente-json?socio_id=1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../app/Controllers/SocioController.php';

$controller = new SocioController();
$controller->expedienteJson();
