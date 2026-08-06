<?php
// config/database.php

class Database {
    private static $instance = null;
    private static $dbFile;

    public static function getConnection() {
        if (self::$dbFile === null) {
            self::$dbFile = __DIR__ . '/../natillera.sqlite';
        }

        if (self::$instance === null) {
            try {
                $isNewDb = !file_exists(self::$dbFile);
                self::$instance = new PDO('sqlite:' . self::$dbFile);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Habilitar llaves foráneas en SQLite
                self::$instance->exec('PRAGMA foreign_keys = ON;');
                
                if ($isNewDb) {
                    self::initDatabase();
                }
            } catch (PDOException $e) {
                die("Error de Conexión a la Base de Datos: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function initDatabase() {
        $db = self::getConnection();
        $sqlPath = __DIR__ . '/../db.sql';
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            $sql = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql);
            $db->exec($sql);
        }

        // 1. Insertar roles si la tabla está vacía
        $stmtRolesCount = $db->query("SELECT COUNT(*) as total FROM roles");
        if ($stmtRolesCount->fetch()['total'] == 0) {
            $roles = array('Presidente', 'Tesorera', 'Secretaria Actividades', 'Secretaria General', 'Socio');
            $stmtRole = $db->prepare("INSERT INTO roles (nombre) VALUES (:nombre)");
            foreach ($roles as $r) {
                $stmtRole->execute(array(':nombre' => $r));
            }
        }

        // 2. Insertar Usuario Presidente por defecto guardado en código
        $stmtUserCount = $db->query("SELECT COUNT(*) as total FROM usuarios");
        if ($stmtUserCount->fetch()['total'] == 0) {
            $stmtPresidenteRole = $db->query("SELECT id FROM roles WHERE nombre = 'Presidente'");
            $presiRole = $stmtPresidenteRole->fetch();
            $presiRoleId = $presiRole ? $presiRole['id'] : 1;

            $stmtInsertUser = $db->prepare("
                INSERT INTO usuarios (cedula, nombre_completo, telefono, password_hash, rol_id, tope_prestamo_personalizado, interes_minimo_meta)
                VALUES ('1010000001', 'Presidente Natillera', '3000000000', :hash, :rol_id, 3000000.00, 400000.00)
            ");
            $stmtInsertUser->execute(array(
                ':hash' => password_hash('123456', PASSWORD_DEFAULT),
                ':rol_id' => $presiRoleId
            ));
        }

        // 3. Crear 26 quincenas iniciales si la tabla reuniones está vacía
        $stmtReunionesCount = $db->query("SELECT COUNT(*) as total FROM reuniones");
        if ($stmtReunionesCount->fetch()['total'] == 0) {
            $stmtReunion = $db->prepare("
                INSERT INTO reuniones (numero_quincena, fecha_reunion, hora_reunion, valor_cuota_base, tipo_evento_extra, monto_premio_extra, estado)
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
    }
}
