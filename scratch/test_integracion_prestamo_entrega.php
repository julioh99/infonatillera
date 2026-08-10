<?php
// scratch/test_integracion_prestamo_entrega.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Prestamo.php';
require_once __DIR__ . '/../app/Models/EntregaBeneficio.php';

try {
    $db = Database::getConnection();
    $prestamoModel = new Prestamo();
    $entregaModel = new EntregaBeneficio();

    echo "1. Creando préstamo de prueba con prestamo_id y entrega asociada...\n";
    $prestamoId = $prestamoModel->crearPrestamo([
        'socio_deudor_id' => 1,
        'reunion_id' => 1,
        'nombre_referencia' => 'Prueba Integrada Firma',
        'monto_prestado' => 250000.00,
        'tasa_interes_mensual' => 10.00,
        'tipo_prestamo' => 'DIRECTO',
        'es_autoprestamo' => 0
    ]);

    echo "   Préstamo creado con ID: {$prestamoId}\n";

    echo "2. Registrando constancia de entrega con prestamo_id = {$prestamoId}...\n";
    $okEntrega = $entregaModel->registrarEntrega([
        'reunion_id' => 1,
        'socio_id' => 1,
        'prestamo_id' => $prestamoId,
        'tipo_beneficio' => 'PRESTAMO',
        'monto_entregado' => 250000.00,
        'firma_digital_path' => '/uploads/firmas/test_firma.png',
        'foto_evidencia_path' => '/uploads/evidencias/test_foto.jpg',
        'entregado_por_usuario_id' => 1
    ]);

    echo "   Resultado registro entrega: " . ($okEntrega ? 'EXITOSO' : 'FALLIDO') . "\n";

    // Verificar en BD
    $stmt = $db->prepare("SELECT * FROM natillera_entregas_beneficios WHERE prestamo_id = :pid");
    $stmt->execute([':pid' => $prestamoId]);
    $row = $stmt->fetch();

    if ($row) {
        echo "   [OK] Registro de entrega encontrado en BD (ID: {$row['id']}, prestamo_id: {$row['prestamo_id']}, firma: {$row['firma_digital_path']})\n";
        
        // Probar eliminación
        echo "3. Eliminando constancia de entrega ID: {$row['id']}...\n";
        $okDel = $entregaModel->eliminarEntrega($row['id']);
        echo "   Resultado eliminación: " . ($okDel ? 'EXITOSO' : 'FALLIDO') . "\n";
    } else {
        echo "   [ERROR] No se encontró el registro de entrega vinculado.\n";
    }

    // Limpieza de préstamo de prueba
    $db->exec("DELETE FROM natillera_prestamos WHERE id = {$prestamoId}");
    echo "4. Limpieza finalizada correctamente.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
