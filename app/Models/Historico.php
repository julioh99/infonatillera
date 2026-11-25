<?php
// app/Models/Historico.php
//
// Historico de anos (como ramas de Git). Cada ano (diciembre a noviembre) es una fila de natillera_historico
// con una foto comprimida (gzcompress) de las tablas operativas, huella SHA-256 y los socios activos con su orden.
//
//   guardarActual()   = "commit": copia lo que hay en las tablas al ano cargado.
//   cargar($id)       = "checkout": guarda el ano cargado, vacia las tablas y carga el otro.
//   crearAnioNuevo()  = guarda el ano cargado, vacia las tablas y empieza en blanco (con socios y orden elegidos).
//
// Seguridad: respaldo RESPALDO_AUTOMATICO inmutable (+ copia en archivo) antes de cargar o crear, todo en una
// transaccion, verificacion de huella antes de restaurar y de contenido despues. Usuarios, roles y suscripciones
// push NUNCA se borran. Las contrasenas nunca entran en la foto.

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/Prestamo.php';

class Historico extends Model {

    const TIPO_ANIO = 'ANIO';
    const TIPO_RESPALDO = 'RESPALDO_AUTOMATICO';
    const VERSION = 1;

    /**
     * Tablas que forman la foto, en orden de INSERCION (padres primero). Se vacian en orden inverso.
     * natillera_notificaciones_leidas acompana a natillera_notificaciones (depende de ella por llave foranea).
     */
    public static $TABLAS = array(
        'natillera_reuniones',
        'natillera_prestamos',
        'natillera_ahorros_cuotas',
        'natillera_abonos_prestamos',
        'natillera_actividades',
        'natillera_actividad_participantes',
        'natillera_abonos_actividades',
        'natillera_fondo_beneficios_cronograma',
        'natillera_entregas_beneficios',
        'natillera_inyecciones_capital',
        'natillera_cierres_reunion',
        'natillera_transferencias_cajas',
        'natillera_pagos_notificaciones',
        'natillera_notificaciones',
        'natillera_notificaciones_leidas',
    );

    /** Carpeta de copias en archivo (las pruebas la cambian). null = storage/historico. */
    public static $dirRespaldos = null;
    /** Solo para pruebas: nombre del punto donde se lanza una excepcion para comprobar el rollback. */
    public static $simularFallo = null;

    private static $columnaOrdenOk = false;
    private $archivosCreados = array();
    private $tablasExistentes = null;
    private $columnasCache = array();

    public function __construct() {
        parent::__construct();
        self::asegurarEsquema($this->db);
    }

    // ------------------------------------------------------------------ esquema

    /** true si NT_NO_AUTOMIGRATE=1: el esquema solo cambia con scripts/migrate.php (nunca al abrir paginas). */
    public static function sinAutoMigracion() {
        $v = getenv('NT_NO_AUTOMIGRATE');
        return $v === '1' || $v === 'true';
    }

    public static function asegurarEsquema(PDO $db) {
        if (self::sinAutoMigracion()) {
            return;
        }
        self::crearTablaHistorico($db);
        self::asegurarColumnaOrden($db);
    }

    public static function crearTablaHistorico(PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS natillera_historico (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                tipo VARCHAR(30) NOT NULL DEFAULT 'ANIO',
                cargado TINYINT(1) NOT NULL DEFAULT 0,
                snapshot LONGBLOB NOT NULL,
                huella CHAR(64) NOT NULL,
                conteos_json TEXT NULL,
                archivo VARCHAR(255) NULL,
                origen_id INT NULL,
                creado_por_usuario_id INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                KEY idx_historico_tipo (tipo, cargado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /** Agrega natillera_usuarios.orden (idempotente). Al crearla, el orden inicial es el id. */
    public static function asegurarColumnaOrden(PDO $db) {
        if (self::$columnaOrdenOk || self::sinAutoMigracion()) {
            return;
        }
        self::aplicarColumnaOrden($db);
    }

    /** Igual que asegurarColumnaOrden pero siempre (lo usan las migraciones). */
    public static function aplicarColumnaOrden(PDO $db) {
        try {
            $existe = $db->query("SHOW COLUMNS FROM natillera_usuarios LIKE 'orden'")->rowCount() > 0;
            if (!$existe) {
                $db->exec("ALTER TABLE natillera_usuarios ADD COLUMN orden INT NOT NULL DEFAULT 0");
                $db->exec("UPDATE natillera_usuarios SET orden = id WHERE orden = 0");
            }
            self::$columnaOrdenOk = true;
        } catch (Exception $e) {
            // la tabla aun no existe: se reintenta en la proxima instancia
        }
    }

    // ------------------------------------------------------------------ consultas

    public function listar() {
        return $this->db->query("
            SELECT id, nombre, tipo, cargado, huella, conteos_json, archivo, origen_id,
                   creado_por_usuario_id, created_at, updated_at, OCTET_LENGTH(snapshot) AS bytes
            FROM natillera_historico
            ORDER BY (tipo = 'ANIO') DESC, cargado DESC, created_at DESC, id DESC
        ")->fetchAll();
    }

    public function getActual() {
        $stmt = $this->db->query("SELECT id, nombre FROM natillera_historico WHERE tipo = 'ANIO' AND cargado = 1 ORDER BY id DESC LIMIT 1");
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /** Conteos actuales por tabla (para mostrar en pantalla). */
    public function conteosActuales() {
        return $this->contar();
    }

    /** Todos los socios (activos primero) con su orden actual. */
    public function getSociosParaOrden() {
        return $this->db->query("
            SELECT u.id, u.cedula, u.nombre_completo, u.estado, u.orden, r.nombre AS rol_nombre
            FROM natillera_usuarios u
            JOIN natillera_roles r ON u.rol_id = r.id
            ORDER BY u.estado DESC, u.orden ASC, u.id ASC
        ")->fetchAll();
    }

    /** Prestamos activos (sin los anulados por 24 h) con su saldo de capital e interes pendiente. */
    public function prestamosActivos() {
        $lista = array();
        foreach ((new Prestamo())->getTodosPrestamos() as $p) {
            if ($p['estado'] !== 'ACTIVO' || !empty($p['anulado_sin_interes'])) {
                continue;
            }
            $saldo = round((float)$p['monto_prestado'] - (float)$p['total_capital_pagado'], 2);
            if ($saldo <= 0) {
                continue;
            }
            $lista[] = array(
                'id' => (int)$p['id'],
                'socio_deudor_id' => (int)$p['socio_deudor_id'],
                'deudor_nombre' => $p['deudor_nombre'],
                'nombre_referencia' => $p['nombre_referencia'],
                'tasa_interes_mensual' => (float)$p['tasa_interes_mensual'],
                'saldo_capital' => $saldo,
                'interes_pendiente' => (float)$p['interes_pendiente'],
            );
        }
        return $lista;
    }

    // ------------------------------------------------------------------ acciones

    /**
     * Guarda el estado actual de las tablas en el ano cargado. Si no hay ano cargado, crea uno con $nombre.
     * @return array {id, nombre, creado, conteos}
     */
    public function guardarActual($nombre, $usuarioId) {
        return $this->enTransaccion(function () use ($nombre, $usuarioId) {
            return $this->guardarActualInterno($nombre, $usuarioId);
        });
    }

    /** Guarda el ano cargado, vacia las tablas y carga el ano $id. */
    public function cargar($id, $usuarioId) {
        $fila = $this->getFila((int)$id);
        if (!$fila || $fila['tipo'] !== self::TIPO_ANIO) {
            throw new InvalidArgumentException('El ano que intentas cargar no existe.');
        }
        if ((int)$fila['cargado'] === 1) {
            throw new InvalidArgumentException('Ese ano ya es el que esta cargado.');
        }
        $payload = $this->leerPayload($fila); // verifica la huella antes de tocar nada

        return $this->enTransaccion(function () use ($fila, $payload, $usuarioId) {
            $respaldo = $this->crearRespaldo('cargar "' . $fila['nombre'] . '"', $usuarioId);
            $this->guardarActualInterno(null, $usuarioId);
            $this->punto('antes_de_vaciar');
            $this->vaciarTablas();
            $this->punto('despues_de_vaciar');
            $this->restaurarTablas($payload['tablas']);
            $this->punto('despues_de_restaurar');
            $this->restaurarSocios($payload['socios'], $usuarioId);
            $this->verificarRestauracion($payload);
            $this->db->exec("UPDATE natillera_historico SET cargado = 0 WHERE tipo = 'ANIO'");
            $upd = $this->db->prepare("UPDATE natillera_historico SET cargado = 1 WHERE id = :id AND tipo = 'ANIO'");
            $upd->execute(array(':id' => (int)$fila['id']));
            return array('id' => (int)$fila['id'], 'nombre' => $fila['nombre'], 'respaldo_id' => $respaldo, 'conteos' => $payload['conteos']);
        });
    }

    /**
     * Guarda el ano cargado, vacia las tablas y empieza un ano en blanco.
     * $opts: nombre, fecha_inicio (Y-m-d), orden (ids en el orden elegido), continuan (ids que siguen activos),
     *        pasar_prestamos (bool: los prestamos activos pasan como saldo inicial).
     * @return array {id, nombre, fecha_inicio, socios, prestamos_arrastrados, respaldo_id}
     */
    public function crearAnioNuevo(array $opts, $usuarioId) {
        $nombre = trim(isset($opts['nombre']) ? $opts['nombre'] : '');
        if ($nombre === '' || self::largo($nombre) > 120) {
            throw new InvalidArgumentException('Escribe el nombre del ano nuevo (maximo 120 caracteres).');
        }
        $fecha = isset($opts['fecha_inicio']) ? $opts['fecha_inicio'] : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int)substr($fecha, 5, 2), (int)substr($fecha, 8, 2), (int)substr($fecha, 0, 4))) {
            throw new InvalidArgumentException('La fecha de inicio del ano nuevo no es valida.');
        }
        $continuan = array();
        foreach ((array)(isset($opts['continuan']) ? $opts['continuan'] : array()) as $id) {
            $continuan[(int)$id] = true;
        }
        $continuan[(int)$usuarioId] = true; // quien opera siempre queda activo
        $orden = array();
        foreach ((array)(isset($opts['orden']) ? $opts['orden'] : array()) as $id) {
            $id = (int)$id;
            if (isset($continuan[$id]) && !in_array($id, $orden, true)) {
                $orden[] = $id;
            }
        }
        foreach (array_keys($continuan) as $id) {
            if (!in_array($id, $orden, true)) {
                $orden[] = $id;
            }
        }
        $pasar = !empty($opts['pasar_prestamos']);

        return $this->enTransaccion(function () use ($nombre, $fecha, $orden, $continuan, $pasar, $usuarioId) {
            if (!$this->nombreDisponible($nombre, 0)) {
                throw new InvalidArgumentException('Ya existe un ano con ese nombre.');
            }
            $respaldo = $this->crearRespaldo('crear el ano "' . $nombre . '"', $usuarioId);
            $this->guardarActualInterno(null, $usuarioId);

            // Prestamos que pasan como saldo inicial: se calculan ANTES de vaciar las tablas
            $arrastrar = $pasar ? $this->prestamosActivos() : array();

            $this->punto('antes_de_vaciar');
            $this->vaciarTablas();
            $this->punto('despues_de_vaciar');

            // Socios: los elegidos quedan activos en el orden dado; el resto se desactiva (nunca se borran)
            $this->aplicarSocios($orden, (int)$usuarioId);

            if (!empty($arrastrar)) {
                $ins = $this->db->prepare("
                    INSERT INTO natillera_prestamos
                        (socio_deudor_id, reunion_id, fecha_prestamo, nombre_referencia, monto_prestado,
                         tasa_interes_mensual, tipo_prestamo, es_autoprestamo, anulado_sin_interes, estado)
                    VALUES (:socio, NULL, :fecha, :ref, :monto, :tasa, 'DIRECTO', 0, 0, 'ACTIVO')
                ");
                foreach ($arrastrar as $p) {
                    $ref = 'Saldo inicial (prestamo ' . $p['id'] . ')' . ($p['nombre_referencia'] ? ' - ' . $p['nombre_referencia'] : '');
                    $ins->execute(array(
                        ':socio' => $p['socio_deudor_id'],
                        ':fecha' => $fecha,
                        ':ref' => self::cortar($ref, 0, 150),
                        ':monto' => $p['saldo_capital'],
                        ':tasa' => $p['tasa_interes_mensual'],
                    ));
                }
            }
            $this->punto('despues_de_crear');

            $cap = $this->capturar();
            $this->db->exec("UPDATE natillera_historico SET cargado = 0 WHERE tipo = 'ANIO'");
            $nuevoId = $this->insertarFila($nombre, self::TIPO_ANIO, 1, $cap, (int)$usuarioId, null, null);
            return array(
                'id' => $nuevoId,
                'nombre' => $nombre,
                'fecha_inicio' => $fecha,
                'socios' => count($orden),
                'prestamos_arrastrados' => count($arrastrar),
                'respaldo_id' => $respaldo,
            );
        });
    }

    /** Copia un RESPALDO_AUTOMATICO como un ano normal (sin cargarlo) para poder cargarlo con "Cargar otro ano". */
    public function recuperarRespaldo($id, $usuarioId) {
        $fila = $this->getFila((int)$id);
        if (!$fila || $fila['tipo'] !== self::TIPO_RESPALDO) {
            throw new InvalidArgumentException('El respaldo no existe.');
        }
        $this->leerPayload($fila);
        return $this->enTransaccion(function () use ($fila, $usuarioId) {
            $base = 'Recuperado: ' . $fila['nombre'];
            $nombre = self::cortar($base, 0, 110);
            $n = 1;
            while (!$this->nombreDisponible($nombre, 0)) {
                $n++;
                $nombre = self::cortar($base, 0, 105) . ' (' . $n . ')';
            }
            $stmt = $this->db->prepare("
                INSERT INTO natillera_historico (nombre, tipo, cargado, snapshot, huella, conteos_json, origen_id, creado_por_usuario_id)
                VALUES (:n, 'ANIO', 0, :s, :h, :c, :o, :u)
            ");
            $stmt->bindValue(':n', $nombre);
            $stmt->bindValue(':s', $fila['snapshot'], PDO::PARAM_LOB);
            $stmt->bindValue(':h', $fila['huella']);
            $stmt->bindValue(':c', $fila['conteos_json']);
            $stmt->bindValue(':o', (int)$fila['id'], PDO::PARAM_INT);
            $stmt->bindValue(':u', (int)$usuarioId, PDO::PARAM_INT);
            $stmt->execute();
            return array('id' => (int)$this->db->lastInsertId(), 'nombre' => $nombre);
        });
    }

    /** JSON de un ano o respaldo para descargar: [nombre, json]. Verifica la huella. */
    public function descargar($id) {
        $fila = $this->getFila((int)$id);
        if (!$fila) {
            throw new InvalidArgumentException('La copia no existe.');
        }
        $this->leerPayload($fila);
        return array($fila['nombre'], gzuncompress($fila['snapshot']));
    }

    /** Verifica la huella de una copia (true/false) sin restaurar. */
    public function verificar($id) {
        $fila = $this->getFila((int)$id);
        if (!$fila) {
            return false;
        }
        try {
            $this->leerPayload($fila);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // ------------------------------------------------------------------ nucleo

    private function enTransaccion($fn) {
        $this->archivosCreados = array();
        $this->db->beginTransaction();
        try {
            $r = call_user_func($fn);
            $this->db->commit();
            return $r;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($this->archivosCreados as $f) {
                @unlink($f);
            }
            throw $e;
        }
    }

    private function punto($nombre) {
        if (self::$simularFallo === $nombre) {
            throw new RuntimeException('Fallo simulado en: ' . $nombre);
        }
    }

    private function guardarActualInterno($nombre, $usuarioId) {
        $cap = $this->capturar();
        $actual = $this->getActual();
        $nombre = $nombre === null ? '' : trim($nombre);
        if ($nombre !== '' && self::largo($nombre) > 120) {
            throw new InvalidArgumentException('El nombre del ano es demasiado largo (maximo 120 caracteres).');
        }

        if ($actual) {
            $nombreFinal = $nombre !== '' ? $nombre : $actual['nombre'];
            if (!$this->nombreDisponible($nombreFinal, (int)$actual['id'])) {
                throw new InvalidArgumentException('Ya existe otro ano con ese nombre.');
            }
            $stmt = $this->db->prepare("
                UPDATE natillera_historico
                SET nombre = :n, snapshot = :s, huella = :h, conteos_json = :c, updated_at = NOW()
                WHERE id = :id AND tipo = 'ANIO'
            ");
            $stmt->bindValue(':n', $nombreFinal);
            $stmt->bindValue(':s', $cap['blob'], PDO::PARAM_LOB);
            $stmt->bindValue(':h', $cap['huella']);
            $stmt->bindValue(':c', json_encode($cap['conteos']));
            $stmt->bindValue(':id', (int)$actual['id'], PDO::PARAM_INT);
            $stmt->execute();
            return array('id' => (int)$actual['id'], 'nombre' => $nombreFinal, 'creado' => false, 'conteos' => $cap['conteos']);
        }

        if ($nombre === '') {
            $nombre = 'Ano en curso ' . date('Y-m-d H:i');
        }
        if (!$this->nombreDisponible($nombre, 0)) {
            throw new InvalidArgumentException('Ya existe un ano con ese nombre.');
        }
        $id = $this->insertarFila($nombre, self::TIPO_ANIO, 1, $cap, (int)$usuarioId, null, null);
        return array('id' => $id, 'nombre' => $nombre, 'creado' => true, 'conteos' => $cap['conteos']);
    }

    private function nombreDisponible($nombre, $excluirId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM natillera_historico WHERE tipo = 'ANIO' AND LOWER(nombre) = LOWER(:n) AND id <> :id");
        $stmt->execute(array(':n' => $nombre, ':id' => (int)$excluirId));
        return (int)$stmt->fetchColumn() === 0;
    }

    private function insertarFila($nombre, $tipo, $cargado, array $cap, $usuarioId, $archivo, $origenId) {
        $stmt = $this->db->prepare("
            INSERT INTO natillera_historico (nombre, tipo, cargado, snapshot, huella, conteos_json, archivo, origen_id, creado_por_usuario_id)
            VALUES (:n, :t, :c, :s, :h, :cj, :a, :o, :u)
        ");
        $stmt->bindValue(':n', $nombre);
        $stmt->bindValue(':t', $tipo);
        $stmt->bindValue(':c', (int)$cargado, PDO::PARAM_INT);
        $stmt->bindValue(':s', $cap['blob'], PDO::PARAM_LOB);
        $stmt->bindValue(':h', $cap['huella']);
        $stmt->bindValue(':cj', json_encode($cap['conteos']));
        $stmt->bindValue(':a', $archivo);
        $stmt->bindValue(':o', $origenId, $origenId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':u', $usuarioId > 0 ? $usuarioId : null, $usuarioId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    /** RESPALDO_AUTOMATICO: fila inmutable + copia en archivo. Si no se puede escribir el archivo, se aborta todo. */
    private function crearRespaldo($motivo, $usuarioId) {
        $cap = $this->capturar();
        $archivo = $this->escribirArchivo($cap['json']);
        $nombre = self::cortar('Respaldo antes de ' . $motivo . ' - ' . date('Y-m-d H:i:s'), 0, 120);
        return $this->insertarFila($nombre, self::TIPO_RESPALDO, 0, $cap, (int)$usuarioId, basename($archivo), null);
    }

    public static function directorioRespaldos() {
        $dir = self::$dirRespaldos !== null ? self::$dirRespaldos : __DIR__ . '/../../storage/historico';
        return rtrim($dir, '/\\');
    }

    private function escribirArchivo($json) {
        $dir = self::directorioRespaldos();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta de respaldos (' . $dir . '). No se hizo ningun cambio.');
        }
        $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "# Respaldos del historico: nunca se sirven por web\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
        }
        $ruta = $dir . DIRECTORY_SEPARATOR . 'respaldo_' . date('Ymd_His') . '_' . substr(sha1(uniqid('', true)), 0, 8) . '.json.gz';
        if (@file_put_contents($ruta, gzencode($json, 9)) === false) {
            throw new RuntimeException('No se pudo escribir la copia en archivo (' . $ruta . '). No se hizo ningun cambio.');
        }
        $this->archivosCreados[] = $ruta;
        return $ruta;
    }

    private function getFila($id) {
        $stmt = $this->db->prepare("SELECT * FROM natillera_historico WHERE id = :id");
        $stmt->execute(array(':id' => (int)$id));
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // ------------------------------------------------------------------ foto

    private function existeTabla($t) {
        if ($this->tablasExistentes === null) {
            $this->tablasExistentes = array();
            foreach ($this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $n) {
                $this->tablasExistentes[strtolower($n)] = true;
            }
        }
        return isset($this->tablasExistentes[strtolower($t)]);
    }

    private function columnas($t) {
        if (!isset($this->columnasCache[$t])) {
            $this->columnasCache[$t] = $this->db->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN);
        }
        return $this->columnasCache[$t];
    }

    private function contar() {
        $c = array();
        foreach (self::$TABLAS as $t) {
            $c[$t] = $this->existeTabla($t) ? (int)$this->db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn() : 0;
        }
        return $c;
    }

    private function leerTablas() {
        $tablas = array();
        foreach (self::$TABLAS as $t) {
            $filas = array();
            if ($this->existeTabla($t)) {
                foreach ($this->db->query("SELECT * FROM `{$t}` ORDER BY id")->fetchAll() as $f) {
                    foreach ($f as $k => $v) {
                        $f[$k] = $v === null ? null : (string)$v; // misma foto en cualquier version de PHP
                    }
                    $filas[] = $f;
                }
            }
            $tablas[$t] = $filas;
        }
        return $tablas;
    }

    private function leerSociosActivos() {
        $socios = array();
        foreach ($this->db->query("SELECT id, cedula, nombre_completo, orden FROM natillera_usuarios WHERE estado = 1 ORDER BY orden ASC, id ASC")->fetchAll() as $s) {
            $socios[] = array('id' => (int)$s['id'], 'cedula' => $s['cedula'], 'nombre_completo' => $s['nombre_completo'], 'orden' => (int)$s['orden']);
        }
        return $socios;
    }

    private static function largo($t) {
        return function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
    }

    private static function cortar($t, $ini, $n) {
        return function_exists('mb_substr') ? mb_substr($t, $ini, $n, 'UTF-8') : substr($t, $ini, $n);
    }

    private static function json($datos) {
        $j = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($j === false) {
            throw new RuntimeException('No se pudo serializar la foto (' . json_last_error_msg() . ').');
        }
        return $j;
    }

    private static function huellaTablas(array $tablas) {
        return hash('sha256', self::json($tablas));
    }

    /** @return array {json, blob, huella, conteos, tablas, socios} */
    private function capturar() {
        $tablas = $this->leerTablas();
        $socios = $this->leerSociosActivos();
        $conteos = array();
        foreach ($tablas as $t => $filas) {
            $conteos[$t] = count($filas);
        }
        $payload = array(
            'version' => self::VERSION,
            'generado' => date('Y-m-d H:i:s'),
            'huella_tablas' => self::huellaTablas($tablas),
            'conteos' => $conteos,
            'socios' => $socios,
            'tablas' => $tablas,
        );
        $json = self::json($payload);
        return array(
            'json' => $json,
            'blob' => gzcompress($json, 9),
            'huella' => hash('sha256', $json),
            'conteos' => $conteos,
            'tablas' => $tablas,
            'socios' => $socios,
        );
    }

    /** Descomprime, verifica la huella SHA-256 y devuelve el contenido. Lanza excepcion si no coincide. */
    private function leerPayload(array $fila) {
        $json = @gzuncompress($fila['snapshot']);
        if ($json === false || $json === '') {
            throw new RuntimeException('La copia "' . $fila['nombre'] . '" no se pudo descomprimir; no se restaura.');
        }
        if (!hash_equals($fila['huella'], hash('sha256', $json))) {
            throw new RuntimeException('La huella SHA-256 de "' . $fila['nombre'] . '" no coincide: la copia esta danada y no se restaura.');
        }
        $payload = json_decode($json, true);
        if (!is_array($payload) || !isset($payload['tablas']) || !is_array($payload['tablas'])) {
            throw new RuntimeException('La copia "' . $fila['nombre'] . '" no tiene un formato valido.');
        }
        if (!isset($payload['socios']) || !is_array($payload['socios'])) {
            $payload['socios'] = array();
        }
        if (!isset($payload['conteos']) || !is_array($payload['conteos'])) {
            $payload['conteos'] = array();
            foreach ($payload['tablas'] as $t => $filas) {
                $payload['conteos'][$t] = count($filas);
            }
        }
        if (isset($payload['huella_tablas']) && !hash_equals($payload['huella_tablas'], self::huellaTablas($payload['tablas']))) {
            throw new RuntimeException('El contenido de "' . $fila['nombre'] . '" no coincide con su huella interna; no se restaura.');
        }
        return $payload;
    }

    // ------------------------------------------------------------------ restauracion

    private function vaciarTablas() {
        foreach (array_reverse(self::$TABLAS) as $t) {
            if ($this->existeTabla($t)) {
                $this->db->exec("DELETE FROM `{$t}`");
            }
        }
    }

    private function restaurarTablas(array $tablas) {
        foreach (self::$TABLAS as $t) {
            $filas = isset($tablas[$t]) ? $tablas[$t] : array();
            if (empty($filas)) {
                continue;
            }
            if (!$this->existeTabla($t)) {
                throw new RuntimeException("La tabla {$t} no existe en esta base de datos.");
            }
            $existentes = $this->columnas($t);
            $cols = array();
            foreach (array_keys($filas[0]) as $c) {
                if (in_array($c, $existentes, true)) {
                    $cols[] = $c;
                }
            }
            $sql = 'INSERT INTO `' . $t . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
            $stmt = $this->db->prepare($sql);
            foreach ($filas as $f) {
                $vals = array();
                foreach ($cols as $c) {
                    $vals[] = array_key_exists($c, $f) ? $f[$c] : null;
                }
                $stmt->execute($vals);
            }
        }
    }

    /** Aplica los socios de una foto: activos y en su orden; quien opera nunca queda inactivo. */
    private function restaurarSocios(array $socios, $usuarioId) {
        if (empty($socios)) {
            return; // foto sin lista de socios: no se toca a nadie
        }
        usort($socios, function ($a, $b) {
            return ((int)$a['orden'] <=> (int)$b['orden']) ?: ((int)$a['id'] <=> (int)$b['id']);
        });
        $ids = array();
        foreach ($socios as $s) {
            $ids[] = (int)$s['id'];
        }
        $this->aplicarSocios($ids, (int)$usuarioId);
    }

    /** Deja activos (estado 1) a $idsEnOrden con orden 1..n; el resto queda inactivo y despues, salvo quien opera. */
    private function aplicarSocios(array $idsEnOrden, $usuarioId) {
        $existentes = array();
        foreach ($this->db->query("SELECT id FROM natillera_usuarios ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $existentes[(int)$id] = true;
        }
        $activos = array();
        foreach ($idsEnOrden as $id) {
            if (isset($existentes[$id]) && !in_array($id, $activos, true)) {
                $activos[] = $id;
            }
        }
        if ($usuarioId > 0 && isset($existentes[$usuarioId]) && !in_array($usuarioId, $activos, true)) {
            $activos[] = $usuarioId;
        }
        $upd = $this->db->prepare('UPDATE natillera_usuarios SET estado = :e, orden = :o WHERE id = :id');
        $n = 0;
        foreach ($activos as $id) {
            $n++;
            $upd->execute(array(':e' => 1, ':o' => $n, ':id' => $id));
        }
        foreach (array_keys($existentes) as $id) {
            if (!in_array($id, $activos, true)) {
                $n++;
                $upd->execute(array(':e' => 0, ':o' => $n, ':id' => $id));
            }
        }
    }

    private function verificarRestauracion(array $payload) {
        $tablas = $this->leerTablas();
        foreach ($tablas as $t => $filas) {
            $esperado = isset($payload['conteos'][$t]) ? (int)$payload['conteos'][$t] : 0;
            if (count($filas) !== $esperado) {
                throw new RuntimeException("Verificacion fallida: {$t} tiene " . count($filas) . " filas y se esperaban {$esperado}.");
            }
        }
        if (isset($payload['huella_tablas']) && !hash_equals($payload['huella_tablas'], self::huellaTablas($tablas))) {
            throw new RuntimeException('Verificacion fallida: el contenido restaurado no coincide con la huella de la copia.');
        }
    }
}
