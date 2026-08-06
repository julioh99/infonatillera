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
    }
}
