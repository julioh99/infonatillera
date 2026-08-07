<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmtRoles = $db->query("SELECT COUNT(*) as cant FROM natillera_roles");
    $rolesCount = $stmtRoles->fetch()['cant'];

    $stmtUsers = $db->query("SELECT COUNT(*) as cant FROM natillera_usuarios");
    $usersCount = $stmtUsers->fetch()['cant'];

    $stmtReuniones = $db->query("SELECT COUNT(*) as cant FROM natillera_reuniones");
    $reunionesCount = $stmtReuniones->fetch()['cant'];

    echo "CONN_OK: Roles={$rolesCount}, Usuarios={$usersCount}, Reuniones={$reunionesCount}\n";
} catch (Exception $e) {
    echo "CONN_ERROR: " . $e->getMessage() . "\n";
}
