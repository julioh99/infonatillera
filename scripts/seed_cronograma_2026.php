<?php
// scripts/seed_cronograma_2026.php
// Carga lo PLANIFICADO del ciclo 2026 (Google Sheets "Cronograma natillera 2026") en natillera_reuniones:
// aporte_ronda, aporte_rifa (tomados de las cuotas extra de valor_cuota_base ya guardado) y las personas que
// reciben ronda y rifa en cada reunion (1 a 26). Luego regenera el cronograma de fondos, incluidas las cerradas.
//
// SOLO base local: se niega a correr si DB_HOST no es 127.0.0.1/localhost.
// Uso: DB_HOST=127.0.0.1 DB_DATABASE=nt_fondos DB_USERNAME=root DB_PASSWORD= php scripts/seed_cronograma_2026.php --confirmar
// Es idempotente (volver a correrlo deja lo mismo). No cambia valor_cuota_base ni reuniones cerradas en lo financiero.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/FondoBeneficio.php';

const SEED_RONDA = [1, 2, 2, 3, 2, 1, 2, 3, 2, 2, 1, 2, 2, 1, 2, 3, 2, 2, 1, 2, 3, 2, 2, 1, 2, 2];
const SEED_RIFA  = [1, 4, 1, 2, 2, 1, 3, 2, 2, 2, 1, 2, 3, 1, 3, 1, 2, 2, 1, 2, 2, 1, 2, 3, 1, 3];

$host = getenv('DB_HOST') ?: '127.0.0.1';
if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "Rechazado: este script solo corre contra una base local (DB_HOST={$host}).\n");
    exit(1);
}
if (!in_array('--confirmar', $argv ?? [], true)) {
    fwrite(STDERR, "Base: " . (getenv('DB_DATABASE') ?: '(por defecto)') . ". Agrega --confirmar para ejecutar.\n");
    exit(1);
}

$cupos = FondoBeneficio::cupos();
if (array_sum(SEED_RONDA) !== $cupos || array_sum(SEED_RIFA) !== $cupos) {
    fwrite(STDERR, "Las personas por reunion no suman los cupos de config ({$cupos}).\n");
    exit(1);
}

$db = Database::getConnection();
$mig = require __DIR__ . '/migrations/20261125_1000_fondos_por_reunion.php';
$mig['up']($db);

$reuniones = $db->query("SELECT id, numero_quincena, valor_cuota_base FROM natillera_reuniones ORDER BY numero_quincena ASC")->fetchAll();
if (count($reuniones) !== count(SEED_RONDA)) {
    fwrite(STDERR, "Se esperaban " . count(SEED_RONDA) . " reuniones y hay " . count($reuniones) . ". Nada se modifico.\n");
    exit(1);
}

$base = (float)Money::config('cuota.base_total', 55000);
$maxExtra = (FondoBeneficio::aporteExtra('RONDA') + FondoBeneficio::aporteExtra('RIFA')) * (float)Money::config('fondos.extra_max_factor', 2);
// Personas por reunion: se persigue el acumulado de la hoja; si una reunion no alcanza (por ejemplo la cuota extra
// quedo en otra reunion que en la hoja) el faltante se entrega apenas haya fondos (el saldo se arrastra).
$planes = ['RONDA' => [], 'RIFA' => []];
foreach (['RONDA' => SEED_RONDA, 'RIFA' => SEED_RIFA] as $tipo => $hoja) {
    $k = strtolower($tipo);
    $recaudado = 0.0; $objetivo = 0; $entregadas = 0;
    foreach ($reuniones as $i => $r) {
        $ap = FondoBeneficio::aportesPorDefecto((float)$r['valor_cuota_base']);
        $recaudado += $ap[$k] * $cupos;
        $objetivo += $hoja[$i];
        $posibles = (int)floor(($recaudado + 0.0001) / FondoBeneficio::premio($tipo));
        $meta = min($objetivo, $posibles, $cupos);
        $planes[$tipo][$i] = max(0, $meta - $entregadas);
        if ($planes[$tipo][$i] !== $hoja[$i]) {
            echo "AVISO: {$tipo} R{$r['numero_quincena']}: hoja {$hoja[$i]}, plan {$planes[$tipo][$i]} (por fondos disponibles)\n";
        }
        $entregadas += $planes[$tipo][$i];
    }
}

$upd = $db->prepare("UPDATE natillera_reuniones SET aporte_ronda = :ar, aporte_rifa = :af,
                         premios_ronda_planificados = :pr, premios_rifa_planificados = :pf WHERE id = :id");
$db->beginTransaction();
try {
    foreach ($reuniones as $i => $r) {
        $cuota = (float)$r['valor_cuota_base'];
        if ($cuota - $base > $maxExtra) {
            echo "AVISO: R{$r['numero_quincena']} tiene cuota " . number_format($cuota, 0, ',', '.') . " (dato erroneo); se planifica con la cuota base.\n";
        }
        $ap = FondoBeneficio::aportesPorDefecto($cuota);
        $upd->execute([':ar' => $ap['ronda'], ':af' => $ap['rifa'], ':pr' => $planes['RONDA'][$i], ':pf' => $planes['RIFA'][$i], ':id' => $r['id']]);
    }
    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

(new FondoBeneficio())->regenerarCronograma(true);

// Verificacion: cada fondo debe cerrar en 0 con todos servidos
foreach (FondoBeneficio::TIPOS as $tipo) {
    $st = $db->prepare("SELECT SUM(personas_liberadas_planificadas) p, SUBSTRING_INDEX(GROUP_CONCAT(saldo_acumulado_fondo ORDER BY reunion_id DESC), ',', 1) ult
                        FROM natillera_fondo_beneficios_cronograma WHERE tipo_beneficio = :t");
    $st->execute([':t' => $tipo]);
    $v = $st->fetch();
    echo "{$tipo}: personas planificadas {$v['p']} de {$cupos}, saldo final {$v['ult']}\n";
}
echo "Listo.\n";
