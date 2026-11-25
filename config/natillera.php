<?php
// config/natillera.php
// Reglas de negocio configurables. Nada de esto debe estar escrito a mano en controladores ni modelos.
// Uso: $cfg = require __DIR__ . '/../config/natillera.php';  o  Config::natillera('ronda.premio');
// Cada módulo agrega sus claves dentro de su propia sección (no editar las ajenas).

return [
    // ---- Cupos y calendario ----
    'cupos'            => 50,      // socios por ciclo; puede ampliarse o reducirse en años futuros
    'reuniones_por_ciclo' => 26,   // quincenas por ciclo (diciembre a noviembre)
    'ciclo_inicio_mes' => 12,      // mes en que empieza el ciclo

    // ---- Cuota ----
    'cuota' => [
        'base_total'   => 55000,   // cuota normal por reunión
        'ahorro'       => 40000,   // parte que es ahorro del socio
        'ronda_base'   => 10000,   // parte que va a la ronda
        'rifa_base'    => 5000,    // parte que va a la rifa
        'extra_ronda'  => 10000,   // sobrecuota para reuniones de "ronda extra" ($65.000)
        'extra_rifa'   => 5000,    // sobrecuota para reuniones de "rifa extra" ($60.000)
    ],

    // ---- Fondos que se entregan por turno o sorteo ----
    'ronda' => ['premio' => 300000],   // se entrega en orden de turno
    'rifa'  => ['premio' => 150000],   // sorteo entre los socios que no han ganado

    // ---- Préstamos ----
    'prestamo' => [
        'tasa_mensual_defecto' => 10.0,
        'monto_defecto'        => 500000,
        'tope_defecto'         => 2000000,
        'meta_intereses_anual' => 400000,
        'horas_anulacion_autoprestamo' => 24,
    ],

    // ---- Interés progresivo: SALDO_MES_INICIADO | SALDO_MES_COMPLETO | COMPUESTO ----
    'interes' => [
        'modo' => 'SALDO_MES_INICIADO',
    ],

    // ---- Seguridad ----
    'seguridad' => [
        'password_min'        => 8,
        'login_max_intentos'  => 5,
        'login_bloqueo_min'   => 15,
    ],

    // ---- Zona horaria de las reuniones ----
    'zona_horaria' => 'America/Bogota',
];
