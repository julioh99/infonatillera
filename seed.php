<?php
// seed.php - Datos de DEMOSTRACION para una base LOCAL de pruebas (MySQL, tablas natillera_*).
//   DB_DATABASE=nt_demo php scripts/migrate.php --sin-seed     (crea el esquema sin Presidente)
//   DB_DATABASE=nt_demo php seed.php
// - Solo por consola. Se niega a correr si el host no es local o si ya hay usuarios.
// - No hay claves fijas: se genera UNA clave temporal aleatoria para toda la demostracion,
//   se imprime una sola vez por consola y cada usuario debe cambiarla al entrar.
// - NUNCA lo ejecutes contra la base de produccion.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/config/database.php';
Database::$autoMigrar = false;
Database::loadEnv(__DIR__ . '/.env');

$host = getenv('DB_HOST') ?: '127.0.0.1';
if (!in_array($host, array('127.0.0.1', 'localhost', '::1'), true)) {
    fwrite(STDERR, "seed.php solo corre contra una base local (DB_HOST={$host} no es local).\n");
    exit(2);
}

try {
    $db = Database::crearConexion();
} catch (Exception $e) {
    Database::fallaConexion($e);
}

try {
    $existe = $db->query("SHOW TABLES LIKE 'natillera_usuarios'")->fetchColumn();
    if (!$existe) {
        fwrite(STDERR, "No hay esquema. Primero: php scripts/migrate.php --sin-seed\n");
        exit(1);
    }
    Database::sembrarRoles($db);

    if ((int)$db->query('SELECT COUNT(*) FROM natillera_usuarios')->fetchColumn() > 0) {
        fwrite(STDERR, "La base ya tiene usuarios; el seeder solo llena bases vacias.\n");
        exit(1);
    }

    $roles = array();
    foreach ($db->query('SELECT id, nombre FROM natillera_roles') as $r) {
        $roles[$r['nombre']] = (int)$r['id'];
    }

    $cfg = require __DIR__ . '/config/natillera.php';
    $cupos = (int)$cfg['cupos'];
    $porCiclo = (int)$cfg['reuniones_por_ciclo'];
    $cuota = $cfg['cuota']['base_total'];

    $nombres = array(
        'Carlos Eduardo Ramirez', 'Maria Fernanda Gomez', 'Ana Milena Suarez', 'Luz Stella Restrepo',
        'Jorge Eliecer Hernandez', 'Martha Cecilia Valencia', 'Jose Luis Rodriguez', 'Claudia Patricia Morales',
        'Andres Felipe Castro', 'Diana Marcela Ospina', 'Juan Esteban Jaramillo', 'Gloria Ines Martinez',
        'Hector Mario Patino', 'Sandra Milena Londono', 'Santiago Arango', 'Paula Andrea Botero',
        'Alejandro Bedoya', 'Esperanza Cardona', 'Gonzalo de Jesus Montoya', 'Olga Lucia Henao',
        'Mauricio Tobon', 'Lina Maria Quintero', 'Gabriel Jaime Rendon', 'Adriana Maria Correa',
        'Cesar Augusto Vargas', 'Veronica Marin', 'Diego Leon Gil', 'Isabel Cristina Munera',
        'Felipe Zuluaga', 'Beatriz Elena Osorio', 'Alvaro Jose Carvajal', 'Patricia Eugenia Cano',
        'Victor Manuel Rios', 'Yolanda Franco', 'Camilo Andres Giraldo', 'Monica Maria Agudelo',
        'Daniel Fernando Castano', 'Liliana Maria Tamayo', 'Oscar Ivan Duque', 'Silvia Rosa Bustamante',
        'Jaime Alberto Villa', 'Rosa Amelia Echavarria', 'Julian Esteban Marulanda', 'Dora Luz Hoyos',
        'Hernan Dario Velasquez', 'Angela Maria Saldarriaga', 'Mateo Arboleda', 'Carolina Galeano',
        'Esteban Roldan', 'Valeria Pineda',
    );

    $clave = Database::claveAleatoria();
    $hash = password_hash($clave, PASSWORD_DEFAULT);

    $db->beginTransaction();

    $stmtUser = $db->prepare("
        INSERT INTO natillera_usuarios (cedula, nombre_completo, telefono, fecha_nacimiento, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta, password_changed)
        VALUES (:cedula, :nombre, :telefono, :nacimiento, :hash, :rol, :tope, 400000.00, 0)
    ");
    for ($i = 0; $i < $cupos; $i++) {
        if ($i === 0) $rol = $roles['Presidente'];
        elseif ($i === 1) $rol = $roles['Tesorera'];
        elseif ($i === 2) $rol = $roles['Secretaria Actividades'];
        elseif ($i === 3) $rol = $roles['Secretaria General'];
        else $rol = $roles['Socio'];

        $stmtUser->execute(array(
            ':cedula' => (string)(1010000001 + $i),
            ':nombre' => isset($nombres[$i]) ? $nombres[$i] : 'Socio de demostracion ' . ($i + 1),
            ':telefono' => '300' . str_pad((string)($i + 1), 7, '0', STR_PAD_LEFT),
            ':nacimiento' => (1975 + ($i % 25)) . '-' . str_pad((string)(($i % 12) + 1), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)(($i % 28) + 1), 2, '0', STR_PAD_LEFT),
            ':hash' => $hash,
            ':rol' => $rol,
            ':tope' => ($i % 5 === 0) ? 3000000.00 : 2000000.00,
        ));
    }

    // Reuniones: una cada 14 dias desde el proximo lunes, todas con la cuota normal.
    // Las cuotas extra ($60.000 / $65.000) se planifican despues desde el modulo Reuniones.
    $stmtReunion = $db->prepare("
        INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
        VALUES (:num, :fecha, '14:00:00', :cuota, 'NINGUNO', 0, :estado)
    ");
    $fecha = new DateTime('next monday');
    for ($q = 1; $q <= $porCiclo; $q++) {
        $stmtReunion->execute(array(
            ':num' => $q,
            ':fecha' => $fecha->format('Y-m-d'),
            ':cuota' => $cuota,
            ':estado' => ($q === 1) ? 'EN_PROCESO' : 'PROGRAMADA',
        ));
        $fecha->modify('+14 days');
    }

    $db->commit();

    echo "Base de demostracion creada: {$cupos} usuarios y {$porCiclo} reuniones.\n";
    echo "Cedulas 1010000001 (Presidente), 1010000002 (Tesorera), 1010000003 (Sec. Actividades),\n";
    echo "1010000004 (Sec. General) y 1010000005 en adelante (Socios).\n";
    echo "Clave temporal de TODOS (se muestra solo ahora; deben cambiarla al entrar): {$clave}\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    Database::log('seed.php: ' . $e->getMessage());
    fwrite(STDERR, "El seeder fallo (detalle en logs/app.log).\n");
    exit(1);
}
