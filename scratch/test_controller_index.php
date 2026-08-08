<?php
// scratch/test_controller_index.php

$_SESSION['usuario'] = ['id' => 1, 'rol_nombre' => 'Presidente'];
$_SESSION['active_mode'] = 'Presidente';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../app/Models/TransferenciaCaja.php';
require_once __DIR__ . '/../app/Models/Reunion.php';
require_once __DIR__ . '/../app/Models/Actividad.php';
require_once __DIR__ . '/../app/Models/Usuario.php';
require_once __DIR__ . '/../app/Controllers/TransferenciaCajaController.php';

try {
    $c = new TransferenciaCajaController();
    // Probar el modelo
    $transfModel = new TransferenciaCaja();
    $reunionModel = new Reunion();
    $actividadModel = new Actividad();

    $transferencias = $transfModel->getTransferencias();
    $resumen = $transfModel->getResumenIntercajas();
    $reuniones = $reunionModel->getReuniones();
    $actividades = $actividadModel->getTodasActividades();

    echo "¡Llamados ejecutados exitosamente!\n";
    echo "Actividades encontradas: " . count($actividades) . "\n";
    echo "Reuniones encontradas: " . count($reuniones) . "\n";
    echo "Transferencias encontradas: " . count($transferencias) . "\n";

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
