<?php
// Tabla del historico de anos (fotos comprimidas con huella SHA-256). Idempotente.
return [
    'descripcion' => 'Crea natillera_historico (fotos de cada ano y respaldos automaticos)',
    'up' => function (PDO $db) {
        require_once __DIR__ . '/../../app/Models/Historico.php';
        Historico::crearTablaHistorico($db);
    },
];
