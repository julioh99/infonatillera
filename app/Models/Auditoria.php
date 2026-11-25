<?php
// app/Models/Auditoria.php
// Bitacora de acciones sensibles e instantaneas JSON para poder restaurar borrados/ediciones.
// Compatible con PHP 7.2.

require_once __DIR__ . '/../../core/Model.php';

class Auditoria extends Model {

    private static $tablaLista = false;

    public function __construct() {
        parent::__construct();
        self::asegurarTabla($this->db);
    }

    /** Crea la tabla si no existe (la migracion hace lo mismo; esto evita fallar si aun no se aplico). */
    public static function asegurarTabla($db) {
        if (self::$tablaLista) return;
        $db->exec("CREATE TABLE IF NOT EXISTS natillera_auditoria (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usuario_id INT NULL,
            rol VARCHAR(50) NULL,
            accion VARCHAR(80) NOT NULL,
            entidad VARCHAR(80) NOT NULL,
            entidad_id INT NULL,
            datos_antes JSON NULL,
            datos_despues JSON NULL,
            ip VARCHAR(45) NULL,
            PRIMARY KEY (id),
            KEY idx_aud_entidad (entidad, entidad_id),
            KEY idx_aud_fecha (fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::$tablaLista = true;
    }

    /**
     * Usuario de la sesion verificado contra la BD. Devuelve null si no hay sesion o el usuario ya no existe/esta inactivo.
     * Los controladores deben ABORTAR cuando devuelve null (nunca atribuir la accion a otro usuario).
     */
    public static function usuarioSesion() {
        $id = (int)(isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0);
        if ($id <= 0) return null;
        $db = Database::getConnection();
        $st = $db->prepare("SELECT u.id, u.nombre_completo, u.estado, r.nombre AS rol_nombre
                            FROM natillera_usuarios u JOIN natillera_roles r ON r.id = u.rol_id
                            WHERE u.id = :id");
        $st->execute([':id' => $id]);
        $u = $st->fetch();
        if (!$u || ($u['estado'] !== null && (int)$u['estado'] === 0)) return null;
        return $u;
    }

    /** Registra una accion. Devuelve el id de la entrada. */
    public function registrar($accion, $entidad, $entidadId = null, $antes = null, $despues = null) {
        $uid = (int)(isset($_SESSION['usuario']['id']) ? $_SESSION['usuario']['id'] : 0);
        $flags = JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR;
        $stmt = $this->db->prepare("INSERT INTO natillera_auditoria
            (usuario_id, rol, accion, entidad, entidad_id, datos_antes, datos_despues, ip)
            VALUES (:u, :r, :a, :e, :eid, :antes, :despues, :ip)");
        $stmt->execute([
            ':u' => $uid > 0 ? $uid : null,
            ':r' => isset($_SESSION['usuario']['rol_nombre']) ? $_SESSION['usuario']['rol_nombre'] : null,
            ':a' => $accion,
            ':e' => $entidad,
            ':eid' => $entidadId !== null ? (int)$entidadId : null,
            ':antes' => $antes === null ? null : json_encode($antes, $flags),
            ':despues' => $despues === null ? null : json_encode($despues, $flags),
            ':ip' => isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Guarda una instantanea de la fila $tabla.id=$id y de sus dependientes ANTES de borrarla/modificarla.
     * $dependientes: mapa tabla => columna_fk (ej. ['natillera_abonos_prestamos' => 'prestamo_id']).
     * Lanza excepcion si la fila no existe o no se pudo guardar (el llamador debe abortar el borrado).
     * Devuelve el id de auditoria.
     */
    public function archivarAntesDeBorrar($tabla, $id, array $dependientes = [], $accion = null) {
        $tabla = self::ident($tabla);
        $id = (int)$id;
        $fila = $this->fila($tabla, $id);
        if (!$fila) {
            throw new RuntimeException("No existe el registro a archivar ({$tabla} #{$id}).");
        }
        $dep = [];
        foreach ($dependientes as $tablaDep => $columna) {
            $tablaDep = self::ident($tablaDep);
            $columna = self::ident($columna);
            $st = $this->db->prepare("SELECT * FROM `{$tablaDep}` WHERE `{$columna}` = :id");
            $st->execute([':id' => $id]);
            $dep[$tablaDep] = ['columna' => $columna, 'filas' => $st->fetchAll()];
        }
        $snap = ['tabla' => $tabla, 'id' => $id, 'fila' => $fila, 'dependientes' => $dep];
        $acc = $accion !== null ? $accion : ('ELIMINAR_' . strtoupper($tabla));
        return $this->registrar($acc, $tabla, $id, $snap, null);
    }

    /** Completa datos_despues de una entrada (p. ej. la fila ya modificada). */
    public function completar($auditId, $despues) {
        $st = $this->db->prepare("UPDATE natillera_auditoria SET datos_despues = :d WHERE id = :id");
        $st->execute([':d' => json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR), ':id' => (int)$auditId]);
    }

    /** Marca una entrada como no ejecutada (la operacion fallo despues de archivar). No se puede restaurar. */
    public function descartar($auditId) {
        $st = $this->db->prepare("UPDATE natillera_auditoria SET accion = CONCAT(LEFT(accion, 60), '_NO_EJECUTADA') WHERE id = :id AND accion NOT LIKE '%_NO_EJECUTADA'");
        $st->execute([':id' => (int)$auditId]);
    }

    /** Fila actual (o false). */
    public function fila($tabla, $id) {
        $tabla = self::ident($tabla);
        $st = $this->db->prepare("SELECT * FROM `{$tabla}` WHERE id = :id");
        $st->execute([':id' => (int)$id]);
        return $st->fetch();
    }

    /**
     * Restaura una entrada archivada (fila principal + dependientes) dentro de una transaccion.
     * Si la fila ya existe se actualiza con los valores archivados; si no, se inserta.
     * Devuelve un resumen ['filas' => n, 'tabla' => ..., 'id' => ...].
     */
    public function restaurar($auditId) {
        $st = $this->db->prepare("SELECT * FROM natillera_auditoria WHERE id = :id");
        $st->execute([':id' => (int)$auditId]);
        $e = $st->fetch();
        if (!$e) throw new RuntimeException("La entrada de auditoria #{$auditId} no existe.");
        if (substr($e['accion'], -12) === '_NO_EJECUTADA') {
            throw new RuntimeException("La entrada #{$auditId} corresponde a una operacion que no se ejecuto.");
        }
        $snap = json_decode((string)$e['datos_antes'], true);
        if (!is_array($snap) || empty($snap['tabla']) || empty($snap['fila'])) {
            throw new RuntimeException("La entrada #{$auditId} no contiene una instantanea restaurable.");
        }

        $total = 0;
        $this->db->beginTransaction();
        try {
            $total += $this->upsertFila($snap['tabla'], $snap['fila']);
            $deps = isset($snap['dependientes']) ? $snap['dependientes'] : [];
            foreach ($deps as $tablaDep => $info) {
                $filas = isset($info['filas']) ? $info['filas'] : [];
                foreach ($filas as $f) {
                    $total += $this->upsertFila($tablaDep, $f);
                }
            }
            $this->registrar('RESTAURAR', $snap['tabla'], (int)$snap['id'], null, ['auditoria_id' => (int)$auditId, 'filas' => $total]);
            $this->db->commit();
        } catch (Throwable $ex) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $ex;
        }
        return ['filas' => $total, 'tabla' => $snap['tabla'], 'id' => (int)$snap['id']];
    }

    private function upsertFila($tabla, array $fila) {
        $tabla = self::ident($tabla);
        $cols = array_keys($fila);
        $ph = [];
        $params = [];
        $sets = [];
        foreach ($cols as $i => $c) {
            self::ident($c);
            $ph[] = ':v' . $i;
            $params[':v' . $i] = $fila[$c];
            if ($c !== 'id') $sets[] = "`{$c}` = VALUES(`{$c}`)";
        }
        $sql = "INSERT INTO `{$tabla}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $ph) . ")";
        if ($sets) $sql .= " ON DUPLICATE KEY UPDATE " . implode(', ', $sets);
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return 1;
    }

    /** Solo identificadores simples (tabla/columna) para armar SQL seguro. */
    private static function ident($nombre) {
        if (!is_string($nombre) || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $nombre)) {
            throw new InvalidArgumentException('Identificador no valido.');
        }
        return $nombre;
    }
}
