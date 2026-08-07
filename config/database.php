<?php
// config/database.php

class Database {
    private static $instance = null;

    public static function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    public static function getConnection() {
        if (self::$instance === null) {
            self::loadEnv(__DIR__ . '/../.env');

            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $dbName = getenv('DB_DATABASE') ?: 'infonatillera';
            $user = getenv('DB_USERNAME') ?: 'root';
            $pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
            $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
                ]);

                // Aplicar esquemas e inicialización si aplica
                self::ensureSchemaUpdates(self::$instance);
                self::initDatabase();
            } catch (PDOException $e) {
                die("Error de Conexión a MySQL: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function ensureSchemaUpdates($db) {
        self::addColumnIfNotExists($db, 'natillera_prestamos', 'nombre_referencia', 'VARCHAR(150)');
        self::addColumnIfNotExists($db, 'natillera_actividades', 'cuota_por_socio', 'DECIMAL(10,2) DEFAULT 0.00');
        self::addColumnIfNotExists($db, 'natillera_ahorros_cuotas', 'monto_aporte_ronda', 'DECIMAL(10,2) DEFAULT 0.00');
        self::addColumnIfNotExists($db, 'natillera_ahorros_cuotas', 'monto_aporte_rifa', 'DECIMAL(10,2) DEFAULT 0.00');

        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS natillera_fondo_beneficios_cronograma (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reunion_id INT NOT NULL,
                    tipo_beneficio VARCHAR(10) NOT NULL,
                    aporte_por_socio DECIMAL(10,2) NOT NULL,
                    total_recaudado DECIMAL(10,2) NOT NULL,
                    monto_beneficio_unidad DECIMAL(10,2) NOT NULL,
                    saldo_restante_reunion DECIMAL(10,2) NOT NULL,
                    saldo_acumulado_fondo DECIMAL(10,2) NOT NULL,
                    personas_liberadas_planificadas INT NOT NULL,
                    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS natillera_entregas_beneficios (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    reunion_id INT NOT NULL,
                    socio_id INT NOT NULL,
                    tipo_beneficio VARCHAR(10) NOT NULL,
                    monto_entregado DECIMAL(10,2) NOT NULL,
                    fecha_entrega DATETIME DEFAULT CURRENT_TIMESTAMP,
                    firma_digital_path VARCHAR(255),
                    foto_evidencia_path VARCHAR(255),
                    entregado_por_usuario_id INT NOT NULL,
                    FOREIGN KEY (reunion_id) REFERENCES natillera_reuniones(id),
                    FOREIGN KEY (socio_id) REFERENCES natillera_usuarios(id),
                    FOREIGN KEY (entregado_por_usuario_id) REFERENCES natillera_usuarios(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS natillera_abonos_actividades (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    actividad_participante_id INT NOT NULL,
                    monto_abono DECIMAL(10,2) NOT NULL,
                    fecha_abono DATETIME DEFAULT CURRENT_TIMESTAMP,
                    observacion VARCHAR(255),
                    registrado_por_usuario_id INT NOT NULL,
                    FOREIGN KEY (actividad_participante_id) REFERENCES natillera_actividad_participantes(id),
                    FOREIGN KEY (registrado_por_usuario_id) REFERENCES natillera_usuarios(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } catch (Exception $e) {
            // Silencioso si ya existen
        }
    }

    private static function addColumnIfNotExists($db, $table, $column, $typeDef) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
            $stmt->execute([':column' => $column]);
            if ($stmt->rowCount() === 0) {
                $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$typeDef}");
            }
        } catch (Exception $e) {
            // Silencioso si la tabla no existe aún o la columna ya existe
        }
    }

    public static function initDatabase() {
        $db = self::getConnection();

        // Verificar si existe la tabla natillera_roles
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'natillera_roles'");
            if ($stmt->rowCount() === 0) {
                $sqlPath = __DIR__ . '/../db.sql';
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    $db->exec($sql);
                }
            }
        } catch (Exception $e) {
            // Continuar
        }

        // 1. Insertar roles si la tabla está vacía
        try {
            $stmtRolesCount = $db->query("SELECT COUNT(*) as total FROM natillera_roles");
            if ($stmtRolesCount->fetch()['total'] == 0) {
                $roles = array('Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio');
                $stmtRole = $db->prepare("INSERT INTO natillera_roles (nombre) VALUES (:nombre)");
                foreach ($roles as $r) {
                    $stmtRole->execute(array(':nombre' => $r));
                }
            }
        } catch (Exception $e) {}

        // 2. Insertar Usuario Presidente por defecto guardado en código
        try {
            $stmtUserCount = $db->query("SELECT COUNT(*) as total FROM natillera_usuarios");
            if ($stmtUserCount->fetch()['total'] == 0) {
                $stmtPresidenteRole = $db->query("SELECT id FROM natillera_roles WHERE nombre = 'Presidente'");
                $presiRole = $stmtPresidenteRole->fetch();
                $presiRoleId = $presiRole ? $presiRole['id'] : 1;

                $stmtInsertUser = $db->prepare("
                    INSERT INTO natillera_usuarios (cedula, nombre_completo, telefono, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta)
                    VALUES ('1010000001', 'Presidente Natillera', '3000000000', :hash, :rol_id, 3000000.00, 400000.00)
                ");
                $stmtInsertUser->execute(array(
                    ':hash' => password_hash('123456', PASSWORD_DEFAULT),
                    ':rol_id' => $presiRoleId
                ));
            }
        } catch (Exception $e) {}

        // 3. Crear 26 quincenas iniciales si la tabla reuniones está vacía
        try {
            $stmtReunionesCount = $db->query("SELECT COUNT(*) as total FROM natillera_reuniones");
            if ($stmtReunionesCount->fetch()['total'] == 0) {
                $stmtReunion = $db->prepare("
                    INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
                    VALUES (:num, :fecha, '14:00:00', :cuota, :evento, :premio, :estado)
                ");

                $startDate = new DateTime('2026-01-15');
                for ($q = 1; $q <= 26; $q++) {
                    $cuotaBase = 55000.00;
                    $evento = 'NINGUNO';
                    $premio = 0.00;

                    if (in_array($q, array(4, 10, 16, 22))) {
                        $cuotaBase = 60000.00;
                        $evento = 'RIFA';
                        $premio = 150000.00;
                    } elseif (in_array($q, array(7, 13, 19, 25))) {
                        $cuotaBase = 65000.00;
                        $evento = 'RONDA';
                        $premio = 300000.00;
                    }

                    $estado = ($q === 1) ? 'EN_PROCESO' : 'PROGRAMADA';

                    $stmtReunion->execute(array(
                        ':num' => $q,
                        ':fecha' => $startDate->format('Y-m-d'),
                        ':cuota' => $cuotaBase,
                        ':evento' => $evento,
                        ':premio' => $premio,
                        ':estado' => $estado
                    ));

                    if ($q % 2 !== 0) {
                        $startDate->modify('+15 days');
                    } else {
                        $startDate->modify('first day of next month');
                        $startDate->setDate((int)$startDate->format('Y'), (int)$startDate->format('m'), 15);
                    }
                }
            }
        } catch (Exception $e) {}
    }
}
