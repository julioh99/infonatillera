<?php
require_once __DIR__ . '/../scripts/_bootstrap.php';
// scratch/check_remote_users.php

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

    echo "Usuarios en natillera_usuarios en BD remota skylined_pruebas:\n";
    $stmt = $pdo->query("SELECT id, cedula, nombre_completo, rol_id FROM natillera_usuarios ORDER BY id ASC");
    $users = $stmt->fetchAll();

    foreach ($users as $u) {
        echo sprintf("ID: %-3d | Cédula: %-10s | Nombre: %-30s | Rol ID: %d\n", $u['id'], $u['cedula'], $u['nombre_completo'], $u['rol_id']);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
