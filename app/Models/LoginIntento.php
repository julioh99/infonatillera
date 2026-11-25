<?php
// app/Models/LoginIntento.php
// Limite de intentos fallidos de login por cedula + IP (config/natillera.php, clave 'seguridad').

require_once __DIR__ . '/../../core/Model.php';

class LoginIntento extends Model {

    private $maxIntentos;
    private $bloqueoMin;

    public function __construct() {
        parent::__construct();
        $cfg = require __DIR__ . '/../../config/natillera.php';
        $seg = isset($cfg['seguridad']) ? $cfg['seguridad'] : array();
        $this->maxIntentos = isset($seg['login_max_intentos']) ? (int)$seg['login_max_intentos'] : 5;
        $this->bloqueoMin = isset($seg['login_bloqueo_min']) ? (int)$seg['login_bloqueo_min'] : 15;
    }

    public static function ipCliente() {
        return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '0.0.0.0';
    }

    /**
     * Minutos que faltan para poder reintentar (0 = no esta bloqueado).
     * Si la tabla no existe (migracion pendiente) no bloquea.
     */
    public function minutosBloqueo($cedula, $ip) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) AS n,
                       TIMESTAMPDIFF(SECOND, NOW(), MAX(fecha_intento) + INTERVAL :bloqueo MINUTE) AS segundos
                FROM natillera_login_intentos
                WHERE cedula = :cedula AND ip = :ip AND exitoso = 0
                  AND fecha_intento > (NOW() - INTERVAL :bloqueo2 MINUTE)
            ");
            $stmt->execute(array(':bloqueo' => $this->bloqueoMin, ':bloqueo2' => $this->bloqueoMin, ':cedula' => $cedula, ':ip' => $ip));
            $row = $stmt->fetch();
            if ($row && (int)$row['n'] >= $this->maxIntentos && (int)$row['segundos'] > 0) {
                return (int)ceil(((int)$row['segundos']) / 60);
            }
        } catch (PDOException $e) {
            // tabla ausente: sin limite hasta correr la migracion
        }
        return 0;
    }

    public function registrarFallo($cedula, $ip) {
        try {
            $stmt = $this->db->prepare("INSERT INTO natillera_login_intentos (cedula, ip, exitoso) VALUES (:c, :ip, 0)");
            $stmt->execute(array(':c' => $cedula, ':ip' => $ip));
            // limpieza ocasional de registros viejos
            if (mt_rand(1, 50) === 1) {
                $this->db->exec("DELETE FROM natillera_login_intentos WHERE fecha_intento < (NOW() - INTERVAL 7 DAY)");
            }
        } catch (PDOException $e) {
        }
    }

    /** Al autenticar bien se borran los fallos de esa cedula + IP. */
    public function limpiar($cedula, $ip) {
        try {
            $stmt = $this->db->prepare("DELETE FROM natillera_login_intentos WHERE cedula = :c AND ip = :ip");
            $stmt->execute(array(':c' => $cedula, ':ip' => $ip));
        } catch (PDOException $e) {
        }
    }

    public function getBloqueoMin() { return $this->bloqueoMin; }
    public function getMaxIntentos() { return $this->maxIntentos; }
}
