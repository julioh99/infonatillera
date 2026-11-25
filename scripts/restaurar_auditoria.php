<?php
// scripts/restaurar_auditoria.php
// Restaura una entrada de natillera_auditoria (fila archivada + dependientes) en una transaccion.
// Uso:  php scripts/restaurar_auditoria.php <id_auditoria> [--ver]
//   --ver  solo muestra lo que se restauraria, sin escribir.
// La BD se elige con DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD (o el .env).

if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Auditoria.php';

$id = isset($argv[1]) ? (int)$argv[1] : 0;
$soloVer = in_array('--ver', $argv, true);
if ($id <= 0) {
    fwrite(STDERR, "Uso: php scripts/restaurar_auditoria.php <id_auditoria> [--ver]\n");
    exit(1);
}

try {
    $aud = new Auditoria();
    if ($soloVer) {
        $db = Database::getConnection();
        $st = $db->prepare("SELECT id, fecha, usuario_id, rol, accion, entidad, entidad_id, datos_antes FROM natillera_auditoria WHERE id = :id");
        $st->execute([':id' => $id]);
        $e = $st->fetch();
        if (!$e) { fwrite(STDERR, "No existe la entrada #{$id}\n"); exit(1); }
        $snap = json_decode((string)$e['datos_antes'], true);
        echo "Entrada #{$e['id']} {$e['fecha']} {$e['accion']} {$e['entidad']} #{$e['entidad_id']} (usuario {$e['usuario_id']} / {$e['rol']})\n";
        if (is_array($snap) && isset($snap['dependientes'])) {
            foreach ($snap['dependientes'] as $t => $info) {
                echo "  dependientes {$t}: " . count($info['filas']) . " fila(s)\n";
            }
        }
        exit(0);
    }
    $r = $aud->restaurar($id);
    echo "Restaurado {$r['tabla']} #{$r['id']} ({$r['filas']} fila(s)). Revisa saldos y estados del prestamo o actividad afectado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo restaurar: " . $e->getMessage() . "\n");
    exit(1);
}
