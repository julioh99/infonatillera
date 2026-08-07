<?php
// scripts/create_new_tables.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "Creando tablas natillera_inyecciones_capital y natillera_cierres_reunion en BD local...\n";

    $sql1 = "
    CREATE TABLE IF NOT EXISTS natillera_inyecciones_capital (
        id INT AUTO_INCREMENT PRIMARY KEY,
        socio_id INT NOT NULL,
        reunion_id INT NOT NULL,
        monto_inyectado DECIMAL(10,2) NOT NULL,
        tasa_rendimiento_porcentaje DECIMAL(5,2) DEFAULT 5.00,
        monto_rendimiento_generado DECIMAL(10,2) DEFAULT 0.00,
        fecha_inyeccion DATETIME DEFAULT CURRENT_TIMESTAMP,
        fecha_retiro_permitido DATE NOT NULL,
        estado VARCHAR(20) DEFAULT 'ACTIVA',
        fecha_retiro DATETIME NULL,
        observaciones VARCHAR(255),
        registrado_por_usuario_id INT NOT NULL,
        FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
        FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
        FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($sql1);

    $sql2 = "
    CREATE TABLE IF NOT EXISTS natillera_cierres_reunion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reunion_id INT NOT NULL UNIQUE,
        total_ingresos_cuotas_base DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_rondas_rifas DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_abono_capital DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_intereses_prestamos DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_actividades DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_inyecciones DECIMAL(10,2) DEFAULT 0.00,
        total_ingresos_general DECIMAL(10,2) DEFAULT 0.00,
        total_egresos_prestamos_otorgados DECIMAL(10,2) DEFAULT 0.00,
        total_egresos_premios_entregados DECIMAL(10,2) DEFAULT 0.00,
        total_egresos_inyecciones_devueltas DECIMAL(10,2) DEFAULT 0.00,
        total_egresos_general DECIMAL(10,2) DEFAULT 0.00,
        saldo_neto_reunion DECIMAL(10,2) DEFAULT 0.00,
        saldo_acumulado_caja DECIMAL(10,2) DEFAULT 0.00,
        desglose_json LONGTEXT,
        fecha_cierre DATETIME DEFAULT CURRENT_TIMESTAMP,
        cerrado_por_usuario_id INT NOT NULL,
        FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
        FOREIGN KEY (cerrado_por_usuario_id) REFERENCES natillera_usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->exec($sql2);

    echo "¡Tablas creadas exitosamente en BD local!\n";

    // Probar creación en servidor remoto skylined_pruebas
    $host = '184.107.184.74';
    $dbName = 'skylined_pruebas';
    $user = 'skylined_natillera';
    $pass = 'mImwIZY)W)%YOYl+';

    echo "Creando tablas en servidor remoto {$host}...\n";
    $remotePdo = new PDO("mysql:host={$host};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $remotePdo->exec($sql1);
    $remotePdo->exec($sql2);
    echo "¡Tablas creadas exitosamente en servidor remoto skylined_pruebas!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
