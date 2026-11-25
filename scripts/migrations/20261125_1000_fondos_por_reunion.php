<?php
// Fondos de ronda y rifa por reunion (task 2.14): aporte por socio y premios planificados.
// NULL = "derivar de valor_cuota_base y config cuota.*" (comportamiento anterior), asi que no cambia datos existentes.
return [
    'descripcion' => 'natillera_reuniones: aporte_ronda, aporte_rifa, premios_ronda_planificados, premios_rifa_planificados',
    'up' => function (PDO $db) {
        $columnas = [
            'aporte_ronda'               => 'DECIMAL(10,2) NULL DEFAULT NULL',
            'aporte_rifa'                => 'DECIMAL(10,2) NULL DEFAULT NULL',
            'premios_ronda_planificados' => 'INT NULL DEFAULT NULL',
            'premios_rifa_planificados'  => 'INT NULL DEFAULT NULL',
        ];
        $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'natillera_reuniones' AND COLUMN_NAME = :c");
        foreach ($columnas as $nombre => $definicion) {
            $stmt->execute([':c' => $nombre]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->exec("ALTER TABLE natillera_reuniones ADD COLUMN `{$nombre}` {$definicion}");
            }
        }
    },
];
