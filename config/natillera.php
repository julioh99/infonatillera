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

    // ---- Controladores financieros (endurecimiento) ----
    'controladores' => [
        'monto_maximo'            => 100000000, // tope absoluto por monto digitado (COP)
        'tasa_prestamo_min'       => 0.0,       // % mensual minimo valido en un prestamo
        'tasa_prestamo_max'       => 20.0,      // % mensual maximo valido en un prestamo
        'inyeccion_tasa_defecto'  => 5.0,       // % de rendimiento por defecto de una inyeccion de capital
        'inyeccion_tasa_max'      => 20.0,      // % maximo de rendimiento de una inyeccion
        'tope_socio_defecto'      => 2000000,   // tope de prestamo si el socio no tiene uno personalizado
        'tope_nuevo_defecto'      => 2000000,   // tope sugerido al actualizar el tope de un socio sin valor
    ],

    // ---- Fondos de ronda/rifa y entregas (modulo FONDOS) ----
    'fondos' => [
        'separacion_min_extras' => 2,        // reuniones minimas entre una reunion con cuota extra y otra, si caben
        'dias_entre_reuniones'  => 14,       // separacion entre reuniones al generar un ciclo
        'hora_reunion'          => '14:00:00',
        'firma_max_bytes'       => 1048576,  // 1 MB: firma digital (PNG del canvas)
        'foto_max_bytes'        => 5242880,  // 5 MB: foto de evidencia
        'foto_tipos'            => ['image/jpeg', 'image/png', 'image/webp'],
        'extra_max_factor'      => 2,        // una cuota que excede la base en mas de (extra_ronda+extra_rifa)*factor se trata como dato erroneo
    ],

    // ---- Zona horaria de las reuniones ----
    'zona_horaria' => 'America/Bogota',
];
