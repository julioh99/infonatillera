<?php
// scripts/migrate_sqlite_to_mysql.php

require_once __DIR__ . '/../config/database.php';

Database::loadEnv(__DIR__ . '/../.env');

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'infonatillera';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$sqliteFile = __DIR__ . '/../natillera.sqlite';

echo "=== MIGRACIÓN DE DATOS: SQLITE A MYSQL (Prefijo natillera_) ===\n";

if (!file_exists($sqliteFile)) {
    echo "⚠️ ADVERTENCIA: No se encontró el archivo natillera.sqlite en la raíz.\n";
    echo "Se creará e inicializará la base de datos MySQL vacía.\n";
    $sqliteDb = null;
} else {
    echo "✅ Archivo SQLite localizado: {$sqliteFile}\n";
    $sqliteDb = new PDO("sqlite:" . $sqliteFile);
    $sqliteDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqliteDb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}

// 1. Conexión a Servidor MySQL sin especificar base de datos inicial para crearla si no existe
try {
    $serverPdo = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✅ Base de datos MySQL '{$dbName}' verificada/creada exitosamente.\n";
} catch (PDOException $e) {
    die("❌ Error al conectar con el servidor MySQL o crear la base de datos: " . $e->getMessage() . "\n");
}

// 2. Conexión a la base de datos MySQL objetivo
try {
    $mysqlDb = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
    ]);
} catch (PDOException $e) {
    die("❌ Error al conectar a la base de datos MySQL '{$dbName}': " . $e->getMessage() . "\n");
}

// 3. Crear Tablas en MySQL desde db.sql
$sqlSchemaPath = __DIR__ . '/../db.sql';
if (file_exists($sqlSchemaPath)) {
    $schemaSql = file_get_contents($sqlSchemaPath);
    try {
        $mysqlDb->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $mysqlDb->exec($schemaSql);
        $mysqlDb->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "✅ Esquema de tablas `natillera_*` aplicado correctamente en MySQL.\n";
    } catch (PDOException $e) {
        die("❌ Error al crear tablas en MySQL: " . $e->getMessage() . "\n");
    }
}

if (!$sqliteDb) {
    echo "✨ Proceso terminado. La estructura MySQL fue inicializada vacía.\n";
    exit(0);
}

// 4. Mapeo de tablas de SQLite a tablas MySQL con prefijo natillera_
$tablesMap = [
    'roles' => 'natillera_roles',
    'usuarios' => 'natillera_usuarios',
    'reuniones' => 'natillera_reuniones',
    'ahorros_cuotas' => 'natillera_ahorros_cuotas',
    'prestamos' => 'natillera_prestamos',
    'abonos_prestamos' => 'natillera_abonos_prestamos',
    'actividades' => 'natillera_actividades',
    'actividad_participantes' => 'natillera_actividad_participantes',
    'abonos_actividades' => 'natillera_abonos_actividades',
    'push_subscriptions' => 'natillera_push_subscriptions',
    'notificaciones' => 'natillera_notificaciones',
    'fondo_beneficios_cronograma' => 'natillera_fondo_beneficios_cronograma',
    'entregas_beneficios' => 'natillera_entregas_beneficios'
];

echo "\n--- Transfiriendo registros desde SQLite hacia MySQL ---\n";
$mysqlDb->exec("SET FOREIGN_KEY_CHECKS = 0;");

foreach ($tablesMap as $sqliteTable => $mysqlTable) {
    try {
        // Verificar si la tabla existe en SQLite
        $stmtCheck = $sqliteDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$sqliteTable}'");
        if (!$stmtCheck->fetch()) {
            echo "ℹ️ Tabla '{$sqliteTable}' no existe en SQLite. Omitiendo.\n";
            continue;
        }

        // Obtener filas de SQLite
        $rowsStmt = $sqliteDb->query("SELECT * FROM `{$sqliteTable}`");
        $rows = $rowsStmt->fetchAll();

        if (empty($rows)) {
            echo "ℹ️ Tabla '{$sqliteTable}' (-> {$mysqlTable}) vacía en SQLite.\n";
            continue;
        }

        // Obtener columnas existentes en la tabla MySQL de destino
        $targetColsStmt = $mysqlDb->query("SHOW COLUMNS FROM `{$mysqlTable}`");
        $targetColumns = $targetColsStmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Vaciar la tabla MySQL de destino antes de insertar
        $mysqlDb->exec("TRUNCATE TABLE `{$mysqlTable}`");

        // Filtrar cada fila en SQLite para conservar solo las columnas presentes en MySQL
        $validColumns = array_intersect(array_keys($rows[0]), $targetColumns);
        $colNames = implode('`, `', $validColumns);
        $placeholders = implode(', ', array_fill(0, count($validColumns), '?'));

        $insertStmt = $mysqlDb->prepare("INSERT INTO `{$mysqlTable}` (`{$colNames}`) VALUES ({$placeholders})");

        $count = 0;
        foreach ($rows as $row) {
            $filteredRow = array_intersect_key($row, array_flip($validColumns));
            $insertStmt->execute(array_values($filteredRow));
            $count++;
        }

        echo "✅ {$count} registros migrados a `{$mysqlTable}`.\n";
    } catch (Exception $e) {
        echo "❌ Error al migrar la tabla {$sqliteTable}: " . $e->getMessage() . "\n";
    }
}

$mysqlDb->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "\n🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE DE SQLITE A MYSQL 🎉\n";
