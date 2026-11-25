<?php
// Orden de los socios en todas las listas. Idempotente: si la columna ya existe no hace nada.
return [
    'descripcion' => 'Agrega natillera_usuarios.orden (orden de los socios; inicia igual al id)',
    'up' => function (PDO $db) {
        require_once __DIR__ . '/../../app/Models/Historico.php';
        Historico::aplicarColumnaOrden($db);
    },
];
