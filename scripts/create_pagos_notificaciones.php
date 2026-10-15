<?php
// scripts/create_pagos_notificaciones.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    echo "=== CREANDO TABLA natillera_pagos_notificaciones ===\n";

    $sql = "
    CREATE TABLE IF NOT EXISTS natillera_pagos_notificaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        socio_id INT NOT NULL,
        reunion_id INT NULL,
        tipo_pago VARCHAR(50) NOT NULL,
        referencia_id INT NULL,
        titulo VARCHAR(150) NOT NULL,
        concepto VARCHAR(255) NOT NULL,
        monto_cuota DECIMAL(10,2) DEFAULT 0.00,
        monto_aporte_ronda DECIMAL(10,2) DEFAULT 0.00,
        monto_aporte_rifa DECIMAL(10,2) DEFAULT 0.00,
        monto_ahorro_extra DECIMAL(10,2) DEFAULT 0.00,
        monto_total DECIMAL(10,2) NOT NULL,
        detalles_json LONGTEXT NULL,
        registrado_por_usuario_id INT NOT NULL,
        fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
        leido TINYINT(1) DEFAULT 0,
        fecha_leido DATETIME NULL,
        INDEX idx_pago_socio_leido (socio_id, leido),
        INDEX idx_pago_socio_reunion (socio_id, reunion_id),
        FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->exec($sql);
    echo " [OK] Tabla natillera_pagos_notificaciones verificada/creada exitosamente.\n";

    // Verificar estructura
    $stmt = $db->query("SHOW COLUMNS FROM natillera_pagos_notificaciones");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo " Columnas encontradas: " . implode(', ', $cols) . "\n";

    echo "\n¡Migración finalizada con éxito!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
