<?php
// scratch/test_entrega.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/EntregaBeneficio.php';

try {
    $db = Database::getConnection();
    echo "Conexión a MySQL exitosa.\n";

    // Verificar si existen reuniones, socios y usuario entregador
    $rStmt = $db->query("SELECT id FROM natillera_reuniones LIMIT 1");
    $reunion = $rStmt->fetch();
    $sStmt = $db->query("SELECT id FROM natillera_usuarios LIMIT 1");
    $socio = $sStmt->fetch();

    if (!$reunion || !$socio) {
        echo "No hay reuniones o usuarios en la base de datos para probar.\n";
        exit;
    }

    echo "Probando inserción con reunión ID: {$reunion['id']}, socio ID: {$socio['id']}...\n";

    $entregaModel = new EntregaBeneficio();
    $ok = $entregaModel->registrarEntrega([
        'reunion_id' => $reunion['id'],
        'socio_id' => $socio['id'],
        'tipo_beneficio' => 'PRESTAMO',
        'monto_entregado' => 500000.00,
        'firma_digital_path' => '/uploads/firmas/test.png',
        'foto_evidencia_path' => null,
        'entregado_por_usuario_id' => $socio['id']
    ]);

    if ($ok) {
        echo "¡Inserción exitosa!\n";
    } else {
        echo "Fallo en registrarEntrega.\n";
    }

} catch (Exception $e) {
    echo "Excepción PDO / MySQL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
