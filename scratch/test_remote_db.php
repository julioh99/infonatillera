<?php
require_once __DIR__ . '/../scripts/_bootstrap.php';
// scratch/test_remote_db.php

$remote = remote_db_config();
$host = $remote['host'];
$dbName = $remote['dbName'];
$user = $remote['user'];
$pass = $remote['pass'];

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Conectado a remote DB.\n";

    // 1. Obtener primera reunión y socio
    $rStmt = $pdo->query("SELECT id FROM natillera_reuniones ORDER BY id ASC LIMIT 1");
    $reunion = $rStmt->fetch();

    $sStmt = $pdo->query("SELECT id FROM natillera_usuarios ORDER BY id ASC LIMIT 1");
    $socio = $sStmt->fetch();

    if (!$reunion || !$socio) {
        echo "No hay reuniones o usuarios en la BD remota.\n";
        exit;
    }

    echo "Reunión ID: {$reunion['id']}, Socio ID: {$socio['id']}\n";

    // Probar INSERT directo
    $stmtIns = $pdo->prepare("
        INSERT INTO natillera_entregas_beneficios 
        (reunion_id, socio_id, tipo_beneficio, monto_entregado, firma_digital_path, foto_evidencia_path, entregado_por_usuario_id)
        VALUES (:reunion_id, :socio_id, :tipo_beneficio, :monto, :firma, :foto, :entregado_por)
    ");

    $stmtIns->execute([
        ':reunion_id' => $reunion['id'],
        ':socio_id' => $socio['id'],
        ':tipo_beneficio' => 'PRESTAMO',
        ':monto' => 500000.00,
        ':firma' => '/uploads/firmas/test.png',
        ':foto' => null,
        ':entregado_por' => $socio['id']
    ]);

    echo "¡INSERT en BD remota exitoso! ID generado: " . $pdo->lastInsertId() . "\n";

} catch (Throwable $e) {
    echo "ERROR en BD remota: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
