<?php
// seed.php - Seeder Inteligente de Datos de Prueba para la Natillera

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();

    echo "Inicializando Base de Datos...\n";
    Database::initDatabase();

    // 1. Verificar e Insertar Roles si están vacíos
    $stmtRolesCount = $db->query("SELECT COUNT(*) as total FROM roles");
    if ($stmtRolesCount->fetch()['total'] == 0) {
        echo "Creando Roles de Usuario...\n";
        $roles = ['Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio'];
        $stmtInsertRole = $db->prepare("INSERT INTO roles (nombre) VALUES (:nombre)");
        foreach ($roles as $r) {
            $stmtInsertRole->execute([':nombre' => $r]);
        }
    }

    // MAPA DE ROLES ID
    $rolesMap = [];
    foreach ($db->query("SELECT id, nombre FROM roles")->fetchAll() as $row) {
        $rolesMap[$row['nombre']] = $row['id'];
    }

    // 2. Crear 50 Socios con Nombres Reales
    $stmtUserCount = $db->query("SELECT COUNT(*) as total FROM usuarios");
    if ($stmtUserCount->fetch()['total'] == 0) {
        echo "Generando 50 Socios y Directiva...\n";

        $nombres = [
            "Carlos Eduardo Ramírez (Presidente)", "María Fernanda Gómez (Tesorera)", "Ana Milena Suárez (Sec. Actividades)",
            "Luz Stella Restrepo (Sec. General)", "Jorge Eliécer Hernández", "Martha Cecilia Valencia",
            "José Luis Rodríguez", "Claudia Patricia Morales", "Andrés Felipe Castro", "Diana Marcela Ospina",
            "Juan Esteban Jaramillo", "Gloria Inés Martínez", "Héctor Mario Patiño", "Sandra Milena Londoño",
            "Santiago Arango", "Paula Andrea Botero", "Alejandro Bedoya", "Esperanza Cardona",
            "Gonzalo de Jesús Montoya", "Olga Lucía Henao", "Mauricio Tobón", "Lina María Quintero",
            "Gabriel Jaime Rendón", "Adriana María Correa", "César Augusto Vargas", "Verónica Marín",
            "Diego León Gil", "Isabel Cristina Múnera", "Felipe Zuluaga", "Beatriz Elena Osorio",
            "Álvaro José Carvajal", "Patricia Eugenia Cano", "Víctor Manuel Ríos", "Yolanda Franco",
            "Camilo Andrés Giraldo", "Mónica Maria Agudelo", "Daniel Fernando Castaño", "Liliana María Tamayo",
            "Oscar Iván Duque", "Silvia Rosa Bustamante", "Jaime Alberto Villa", "Rosa Amelia Echavarría",
            "Julián Esteban Marulanda", "Dora Luz Hoyos", "Hernán Darío Velásquez", "Angela María Saldarriaga",
            "Mateo Arboleda", "Carolina Galeano", "Esteban Roldán", "Valeria Pineda"
        ];

        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);

        $stmtUser = $db->prepare("
            INSERT INTO usuarios (cedula, nombre_completo, telefono, fecha_nacimiento, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta)
            VALUES (:cedula, :nombre, :telefono, :fecha_nacimiento, :password, :rol_id, :tope, 400000.00)
        ");

        for ($i = 0; $i < 50; $i++) {
            $cedula = (string)(1010000001 + $i);
            $nombre = $nombres[$i];
            $telefono = "300" . str_pad($i + 1, 7, "0", STR_PAD_LEFT);
            // Generar fecha de nacimiento realista (ej: entre 1970 y 2000, repartidos en los meses)
            $mes = str_pad(($i % 12) + 1, 2, "0", STR_PAD_LEFT);
            $dia = str_pad(($i % 28) + 1, 2, "0", STR_PAD_LEFT);
            $anio = 1975 + ($i % 25);
            $fechaNacimiento = "$anio-$mes-$dia";

            // Asignar rol correspondiente
            if ($i === 0) $rolId = $rolesMap['Presidente'];
            elseif ($i === 1) $rolId = $rolesMap['Tesorera'];
            elseif ($i === 2) $rolId = $rolesMap['Secretaria Actividades'];
            elseif ($i === 3) $rolId = $rolesMap['Secretaria General'];
            else $rolId = $rolesMap['Socio'];

            $tope = ($i % 5 === 0) ? 3000000.00 : 2000000.00;

            $stmtUser->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':telefono' => $telefono,
                ':fecha_nacimiento' => $fechaNacimiento,
                ':password' => $passwordHash,
                ':rol_id' => $rolId,
                ':tope' => $tope
            ]);
        }
    }

    // 3. Crear las 26 Quincenas del Año
    $stmtReunionesCount = $db->query("SELECT COUNT(*) as total FROM reuniones");
    if ($stmtReunionesCount->fetch()['total'] == 0) {
        echo "Programando las 26 Quincenas del Año...\n";

        $stmtReunion = $db->prepare("
            INSERT INTO reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
            VALUES (:num, :fecha, '14:00:00', :cuota, :evento, :premio, :estado)
        ");

        $startDate = new DateTime('2026-01-15');

        for ($q = 1; $q <= 26; $q++) {
            $cuotaBase = 55000.00;
            $evento = 'NINGUNO';
            $premio = 0.00;

            // 4 quincenas con Rifa ($150k) -> cuota $60k
            if (in_array($q, [4, 10, 16, 22])) {
                $cuotaBase = 60000.00;
                $evento = 'RIFA';
                $premio = 150000.00;
            }
            // 4 quincenas con Ronda ($300k) -> cuota $65k
            elseif (in_array($q, [7, 13, 19, 25])) {
                $cuotaBase = 65000.00;
                $evento = 'RONDA';
                $premio = 300000.00;
            }

            $estado = ($q === 1) ? 'EN_PROCESO' : 'PROGRAMADA';

            $stmtReunion->execute([
                ':num' => $q,
                ':fecha' => $startDate->format('Y-m-d'),
                ':cuota' => $cuotaBase,
                ':evento' => $evento,
                ':premio' => $premio,
                ':estado' => $estado
            ]);

            // Avanzar 15 días aproximadamente (15 y 30 de cada mes)
            if ($q % 2 !== 0) {
                $startDate->modify('+15 days');
            } else {
                $startDate->modify('first day of next month');
                $startDate->setDate((int)$startDate->format('Y'), (int)$startDate->format('m'), 15);
            }
        }
    }

    echo "¡Base de datos sembrada correctamente!\n";
    echo "Usuarios de Prueba Creados:\n";
    echo "- Presidente: Cédula 1010000001 / Clave 123456\n";
    echo "- Tesorera: Cédula 1010000002 / Clave 123456\n";
    echo "- Sec. Actividades: Cédula 1010000003 / Clave 123456\n";
    echo "- Sec. General: Cédula 1010000004 / Clave 123456\n";
    echo "- Socios: Cédula 1010000005 hasta 1010000050 / Clave 123456\n";

} catch (Exception $e) {
    echo "Error en Seeder: " . $e->getMessage() . "\n";
}
