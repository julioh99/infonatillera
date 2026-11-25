<?php
// config/database.php
// Conexion PDO a MySQL, carga del .env, registro de errores y ejecutor de migraciones.
// Compatible con PHP 7.2.

class Database {
    private static $instance = null;

    /** Si es false, getConnection() no aplica migraciones pendientes (lo usa scripts/migrate.php). */
    public static $autoMigrar = true;

    /** Carga el .env. Las variables reales del entorno tienen prioridad sobre el archivo. */
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
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV) && getenv($name) === false) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    /** true si APP_DEBUG esta activo (1, true, on, yes). */
    public static function appDebug() {
        $v = getenv('APP_DEBUG');
        if ($v === false) return false;
        return in_array(strtolower(trim($v)), array('1', 'true', 'on', 'yes', 'si'), true);
    }

    /** Escribe una linea en logs/app.log (crea la carpeta si no existe). Nunca lanza excepciones. */
    public static function log($mensaje, $nivel = 'ERROR') {
        try {
            $dir = __DIR__ . '/../logs';
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $linea = '[' . date('Y-m-d H:i:s') . '] ' . $nivel . ' ' . str_replace(array("\r", "\n"), ' ', (string)$mensaje) . PHP_EOL;
            @file_put_contents($dir . '/app.log', $linea, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Sin registro disponible: no hay nada mas que hacer
        }
    }

    /**
     * Falla de conexion: guarda el detalle en el log y muestra un mensaje generico
     * (con APP_DEBUG=1 muestra el detalle). Termina la ejecucion.
     */
    public static function fallaConexion($e) {
        self::log('Conexion a MySQL fallida: ' . $e->getMessage());
        $detalle = self::appDebug() ? ' Detalle: ' . $e->getMessage() : '';
        $texto = 'No se pudo conectar con la base de datos. Intenta de nuevo en unos minutos; si el problema continua avisa al administrador.' . $detalle;
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $texto . PHP_EOL . 'El detalle quedo en logs/app.log' . PHP_EOL);
            exit(1);
        }
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: text/plain; charset=utf-8');
        }
        die($texto);
    }

    /** Crea la conexion PDO sin aplicar migraciones. */
    public static function crearConexion($crearBdSiNoExiste = false) {
        self::loadEnv(__DIR__ . '/../.env');

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_DATABASE') ?: 'infonatillera';
        $user = getenv('DB_USERNAME') ?: 'root';
        $pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        $opts = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
        );

        if ($crearBdSiNoExiste) {
            $tmp = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, $opts);
            if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
                throw new Exception('Nombre de base de datos no valido');
            }
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tmp = null;
        }

        return new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}", $user, $pass, $opts);
    }

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                $pdo = self::crearConexion();
            } catch (PDOException $e) {
                self::fallaConexion($e);
            }
            self::$instance = $pdo;

            // Migraciones pendientes: comprobacion barata (una consulta) una sola vez por peticion.
            if (self::$autoMigrar && self::autoMigracionActiva()) {
                try {
                    Migrator::autoAplicar(self::$instance);
                } catch (Exception $e) {
                    self::log('Migraciones automaticas: ' . $e->getMessage());
                }
            }
        }
        return self::$instance;
    }

    /**
     * La aplicacion automatica de migraciones por peticion esta DESACTIVADA por defecto.
     * Se activa solo con NT_AUTOMIGRATE=1 (o NT_NO_AUTOMIGRATE=0, para pruebas locales);
     * NT_NO_AUTOMIGRATE=1 la apaga siempre. En produccion se migra a mano: php scripts/migrate.php.
     */
    public static function autoMigracionActiva() {
        if (getenv('NT_NO_AUTOMIGRATE') === '1') return false;
        return getenv('NT_AUTOMIGRATE') === '1' || getenv('NT_NO_AUTOMIGRATE') === '0';
    }

    /** Siembra los roles base si la tabla esta vacia. Idempotente. */
    public static function sembrarRoles($db) {
        try {
            $total = (int)$db->query("SELECT COUNT(*) FROM natillera_roles")->fetchColumn();
            if ($total === 0) {
                $stmt = $db->prepare("INSERT INTO natillera_roles (nombre) VALUES (:nombre)");
                foreach (array('Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio') as $r) {
                    $stmt->execute(array(':nombre' => $r));
                }
            }
        } catch (Exception $e) {
            self::log('sembrarRoles: ' . $e->getMessage());
        }
    }

    /** Compatibilidad: antes creaba usuario y reuniones en cada peticion. Ahora solo garantiza roles. */
    public static function initDatabase() {
        self::sembrarRoles(self::getConnection());
    }

    /** Clave temporal aleatoria legible (sin 0/O/1/l/I). */
    public static function claveAleatoria($largo = 12) {
        $alfabeto = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alfabeto) - 1;
        $clave = '';
        for ($i = 0; $i < $largo; $i++) {
            $clave .= $alfabeto[random_int(0, $max)];
        }
        return $clave;
    }

    /**
     * Primer arranque (solo por consola: scripts/migrate.php y seed.php).
     * Si no hay usuarios crea el Presidente con clave temporal aleatoria y devuelve
     * array('cedula' => ..., 'clave' => ...) para imprimirla UNA vez; si ya hay usuarios devuelve null.
     * En esa base nueva tambien crea las reuniones del ciclo con la cuota base de config/natillera.php.
     */
    public static function sembrarInicial($db) {
        self::sembrarRoles($db);
        $cfg = file_exists(__DIR__ . '/natillera.php') ? require __DIR__ . '/natillera.php' : array();
        $cuota = isset($cfg['cuota']['base_total']) ? $cfg['cuota']['base_total'] : 55000;
        $porCiclo = isset($cfg['reuniones_por_ciclo']) ? (int)$cfg['reuniones_por_ciclo'] : 26;
        $resultado = null;

        $usuarios = (int)$db->query("SELECT COUNT(*) FROM natillera_usuarios")->fetchColumn();
        if ($usuarios === 0) {
            $rol = $db->query("SELECT id FROM natillera_roles WHERE nombre = 'Presidente'")->fetchColumn();
            $clave = self::claveAleatoria();
            $stmt = $db->prepare("
                INSERT INTO natillera_usuarios (cedula, nombre_completo, telefono, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta, password_changed)
                VALUES ('1010000001', 'Presidente Natillera', '3000000000', :hash, :rol, 3000000.00, 400000.00, 0)
            ");
            $stmt->execute(array(':hash' => password_hash($clave, PASSWORD_DEFAULT), ':rol' => $rol ? $rol : 1));
            $resultado = array('cedula' => '1010000001', 'clave' => $clave);
        }

        // Las reuniones solo se crean en una base realmente nueva (sin usuarios): en una base con datos
        // las reuniones vacias pueden ser un ano recien creado y no deben regenerarse.
        if ($resultado !== null && (int)$db->query("SELECT COUNT(*) FROM natillera_reuniones")->fetchColumn() === 0) {
            $stmt = $db->prepare("
                INSERT INTO natillera_reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
                VALUES (:num, :fecha, '14:00:00', :cuota, 'NINGUNO', 0, :estado)
            ");
            $fecha = new DateTime('next monday');
            for ($q = 1; $q <= $porCiclo; $q++) {
                $stmt->execute(array(
                    ':num' => $q,
                    ':fecha' => $fecha->format('Y-m-d'),
                    ':cuota' => $cuota,
                    ':estado' => ($q === 1) ? 'EN_PROCESO' : 'PROGRAMADA'
                ));
                $fecha->modify('+14 days');
            }
        }
        return $resultado;
    }
}

/**
 * Ejecutor de migraciones. Archivos en scripts/migrations/AAAAMMDD_HHMM_nombre.php con la forma
 *   <?php return array('descripcion' => '...', 'up' => function (PDO $db) { ... });
 * Se ejecutan en orden de nombre, cada una una sola vez (tabla natillera_migraciones) y deben ser idempotentes.
 */
class Migrator {
    const TABLA = 'natillera_migraciones';
    const LOCK = 'natillera_migraciones_lock';

    private $db;
    private $dir;

    public function __construct($db, $dir = null) {
        $this->db = $db;
        $this->dir = $dir ? $dir : realpath(__DIR__ . '/../scripts/migrations');
        if (!$this->dir) $this->dir = __DIR__ . '/../scripts/migrations';
    }

    public static function dirPorDefecto() {
        return __DIR__ . '/../scripts/migrations';
    }

    /** Comprobacion barata en cada peticion: si hay mas archivos que migraciones aplicadas, las aplica. */
    public static function autoAplicar($db) {
        $archivos = glob(self::dirPorDefecto() . '/*.php');
        if (!$archivos) return;
        try {
            $aplicadas = (int)$db->query('SELECT COUNT(*) FROM ' . self::TABLA)->fetchColumn();
        } catch (Exception $e) {
            $aplicadas = -1; // la tabla aun no existe
        }
        if ($aplicadas >= count($archivos)) return;

        $m = new Migrator($db);
        $res = $m->run(array('espera_lock' => 30));
        if (!empty($res['error'])) {
            Database::log('Migracion ' . $res['error']['nombre'] . ' fallo: ' . $res['error']['mensaje']);
        }
    }

    public function asegurarTabla() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS " . self::TABLA . " (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(190) NOT NULL,
                descripcion VARCHAR(255) NULL,
                huella CHAR(64) NOT NULL,
                aplicada_en DATETIME NOT NULL,
                duracion_ms INT NULL,
                UNIQUE KEY uq_migracion_nombre (nombre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /** Archivos de migracion ordenados: array nombre => ruta. */
    public function archivos() {
        $out = array();
        $lista = glob($this->dir . '/*.php');
        if (!$lista) return $out;
        sort($lista, SORT_STRING);
        foreach ($lista as $ruta) {
            $out[basename($ruta, '.php')] = $ruta;
        }
        return $out;
    }

    /** Migraciones ya aplicadas: array nombre => fila. */
    public function aplicadas() {
        $out = array();
        try {
            foreach ($this->db->query('SELECT nombre, descripcion, huella, aplicada_en FROM ' . self::TABLA . ' ORDER BY nombre') as $r) {
                $out[$r['nombre']] = $r;
            }
        } catch (Exception $e) {
            // la tabla aun no existe
        }
        return $out;
    }

    /** Estado: lista de array(nombre, estado APLICADA|PENDIENTE|MODIFICADA|HUERFANA, fecha, descripcion). */
    public function estado() {
        $archivos = $this->archivos();
        $aplicadas = $this->aplicadas();
        $out = array();
        foreach ($archivos as $nombre => $ruta) {
            $huella = hash_file('sha256', $ruta);
            if (isset($aplicadas[$nombre])) {
                $est = ($aplicadas[$nombre]['huella'] === $huella) ? 'APLICADA' : 'MODIFICADA';
                $out[] = array('nombre' => $nombre, 'estado' => $est, 'fecha' => $aplicadas[$nombre]['aplicada_en'], 'descripcion' => $aplicadas[$nombre]['descripcion']);
            } else {
                $out[] = array('nombre' => $nombre, 'estado' => 'PENDIENTE', 'fecha' => null, 'descripcion' => null);
            }
        }
        foreach ($aplicadas as $nombre => $fila) {
            if (!isset($archivos[$nombre])) {
                $out[] = array('nombre' => $nombre, 'estado' => 'HUERFANA', 'fecha' => $fila['aplicada_en'], 'descripcion' => $fila['descripcion']);
            }
        }
        return $out;
    }

    /**
     * Aplica las pendientes en orden. Opciones: dry_run (bool), espera_lock (segundos), salida (callable).
     * Devuelve array('aplicadas' => [...], 'pendientes' => [...], 'error' => null|array(nombre, mensaje), 'sin_lock' => bool).
     */
    public function run($opts = array()) {
        $dry = !empty($opts['dry_run']);
        $espera = isset($opts['espera_lock']) ? (int)$opts['espera_lock'] : 0;
        $say = isset($opts['salida']) && is_callable($opts['salida']) ? $opts['salida'] : function ($t) {};
        $res = array('aplicadas' => array(), 'pendientes' => array(), 'error' => null, 'sin_lock' => false);

        if ($dry) {
            foreach ($this->estado() as $e) {
                if ($e['estado'] === 'PENDIENTE') $res['pendientes'][] = $e['nombre'];
            }
            return $res;
        }

        $this->asegurarTabla();

        $st = $this->db->prepare('SELECT GET_LOCK(:n, :t)');
        $st->execute(array(':n' => self::LOCK, ':t' => $espera));
        if ((int)$st->fetchColumn() !== 1) {
            $res['sin_lock'] = true;
            return $res;
        }

        try {
            $archivos = $this->archivos();
            $aplicadas = $this->aplicadas(); // se lee con el lock tomado
            foreach ($archivos as $nombre => $ruta) {
                if (isset($aplicadas[$nombre])) continue;

                $def = require $ruta;
                if (!is_array($def) || !isset($def['up']) || !is_callable($def['up'])) {
                    $res['error'] = array('nombre' => $nombre, 'mensaje' => 'El archivo no devuelve array con la clave up (callable).');
                    break;
                }
                $desc = isset($def['descripcion']) ? (string)$def['descripcion'] : '';
                $say("Aplicando {$nombre}" . ($desc !== '' ? " - {$desc}" : '') . "\n");
                $t0 = microtime(true);
                try {
                    call_user_func($def['up'], $this->db);
                } catch (Exception $e) {
                    $res['error'] = array('nombre' => $nombre, 'mensaje' => $e->getMessage());
                    break;
                } catch (Throwable $e) {
                    $res['error'] = array('nombre' => $nombre, 'mensaje' => $e->getMessage());
                    break;
                }
                $ms = (int)round((microtime(true) - $t0) * 1000);
                $ins = $this->db->prepare('INSERT INTO ' . self::TABLA . ' (nombre, descripcion, huella, aplicada_en, duracion_ms) VALUES (:n, :d, :h, NOW(), :ms)');
                $ins->execute(array(':n' => $nombre, ':d' => mb_substr($desc, 0, 255), ':h' => hash_file('sha256', $ruta), ':ms' => $ms));
                $res['aplicadas'][] = $nombre;
            }
        } finally {
            try {
                $rel = $this->db->prepare('SELECT RELEASE_LOCK(:n)');
                $rel->execute(array(':n' => self::LOCK));
                $rel->fetchAll();
            } catch (Exception $e) {}
        }
        return $res;
    }

    /** Ejecuta un texto SQL con varias sentencias, una a una; lanza excepcion ante el primer error. */
    public static function ejecutarSql($db, $sql) {
        foreach (self::dividirSql($sql) as $sentencia) {
            $db->exec($sentencia);
        }
    }

    /** Divide un script SQL en sentencias respetando comillas y comentarios. */
    public static function dividirSql($sql) {
        $out = array();
        $buf = '';
        $n = strlen($sql);
        $quote = null;
        for ($i = 0; $i < $n; $i++) {
            $c = $sql[$i];
            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote !== '`' && $i + 1 < $n) {
                    $buf .= $sql[++$i];
                } elseif ($c === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') {
                $quote = $c;
                $buf .= $c;
            } elseif ($c === '-' && substr($sql, $i, 3) === '-- ') {
                $fin = strpos($sql, "\n", $i);
                if ($fin === false) break;
                $i = $fin; // descarta el comentario, conserva el salto
                $buf .= "\n";
            } elseif ($c === '/' && substr($sql, $i, 2) === '/*') {
                $fin = strpos($sql, '*/', $i + 2);
                if ($fin === false) { $buf .= substr($sql, $i); break; }
                $buf .= substr($sql, $i, $fin + 2 - $i);
                $i = $fin + 1;
            } elseif ($c === ';') {
                if (trim($buf) !== '') $out[] = trim($buf);
                $buf = '';
            } else {
                $buf .= $c;
            }
        }
        if (trim($buf) !== '') $out[] = trim($buf);
        return $out;
    }
}
