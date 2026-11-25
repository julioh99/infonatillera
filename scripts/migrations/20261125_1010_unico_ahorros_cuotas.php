<?php
// Indice unico (reunion_id, socio_id) en natillera_ahorros_cuotas (2.10). Idempotente.
// Si hay duplicados NO crea el indice: escribe scripts/reporte_duplicados_cuotas.txt y lanza una excepcion
// para que la migracion quede pendiente hasta que se depuren a mano.
return [
    'descripcion' => 'Indice unico uq_ahorros_reunion_socio en natillera_ahorros_cuotas (si no hay duplicados)',
    'up' => function (PDO $db) {
        $existe = $db->prepare("SELECT COUNT(*) FROM information_schema.statistics
            WHERE table_schema = DATABASE() AND table_name = 'natillera_ahorros_cuotas' AND index_name = 'uq_ahorros_reunion_socio'");
        $existe->execute();
        if ((int)$existe->fetchColumn() > 0) return;

        $dups = $db->query("SELECT reunion_id, socio_id, COUNT(*) AS veces, GROUP_CONCAT(id ORDER BY id) AS ids
            FROM natillera_ahorros_cuotas GROUP BY reunion_id, socio_id HAVING COUNT(*) > 1
            ORDER BY reunion_id, socio_id")->fetchAll(PDO::FETCH_ASSOC);

        $reporte = __DIR__ . '/../reporte_duplicados_cuotas.txt';
        if ($dups) {
            $txt = "REPORTE DE CUOTAS DUPLICADAS (" . date('Y-m-d H:i:s') . ")\n"
                 . "No se creo el indice unico uq_ahorros_reunion_socio. Revisa estas filas de natillera_ahorros_cuotas,\n"
                 . "conserva una por (reunion, socio), elimina las demas y vuelve a correr la migracion.\n\n"
                 . "reunion_id\tsocio_id\tveces\tids\n";
            foreach ($dups as $d) {
                $txt .= $d['reunion_id'] . "\t" . $d['socio_id'] . "\t" . $d['veces'] . "\t" . $d['ids'] . "\n";
            }
            file_put_contents($reporte, $txt);
            throw new RuntimeException(count($dups) . " pares (reunion, socio) duplicados en natillera_ahorros_cuotas; indice NO creado. Ver scripts/reporte_duplicados_cuotas.txt");
        }

        if (is_file($reporte)) @unlink($reporte);
        $db->exec("ALTER TABLE natillera_ahorros_cuotas ADD UNIQUE INDEX uq_ahorros_reunion_socio (reunion_id, socio_id)");
    },
];
