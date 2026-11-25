<?php
// app/Views/admin/historico.php - Histórico de años (solo Presidente en modo Presidente)
$fmtMoney = function ($v) { return '$' . number_format((float)$v, 0, ',', '.'); };
$fmtBytes = function ($b) { return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format(max(1, $b) / 1024, 0, ',', '.') . ' KB'; };
$aniosNormales = array_values(array_filter($anios, function ($a) { return $a['tipo'] === 'ANIO'; }));
$respaldos = array_values(array_filter($anios, function ($a) { return $a['tipo'] !== 'ANIO'; }));
$capitalActivo = 0; $interesActivo = 0;
foreach ($prestamosActivos as $pa) { $capitalActivo += $pa['saldo_capital']; $interesActivo += $pa['interes_pendiente']; }
$activos = array_filter($socios, function ($s) { return (int)$s['estado'] === 1; });
?>
<style>
    .hx-grid { display: grid; gap: 1rem; }
    @media (min-width: 992px) { .hx-grid--2 { grid-template-columns: 1fr 1fr; align-items: start; } }
    .hx-card { background: var(--nt-surface); border-radius: var(--nt-radius-lg); box-shadow: var(--nt-shadow-2); padding: 1.1rem; min-width: 0; }
    .hx-card h3 { font-family: var(--font-heading); font-weight: 700; font-size: 1.05rem; margin: 0 0 .25rem; display: flex; align-items: center; gap: .5rem; }
    .hx-card p.hx-hint, .hx-hint { color: var(--nt-muted); font-size: .82rem; margin: 0 0 .85rem; }
    .hx-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; margin-top: 1rem; }
    @media (min-width: 768px) { .hx-stats { grid-template-columns: repeat(4, 1fr); } }
    .hx-year { display: flex; flex-direction: column; gap: .6rem; padding: .85rem; border-radius: var(--nt-radius); box-shadow: var(--nt-shadow-1); background: var(--nt-surface); }
    .hx-year.is-current { background: var(--nt-pine-50); box-shadow: 0 0 0 2px var(--nt-pine-500); }
    .hx-year__top { display: flex; justify-content: space-between; gap: .5rem; flex-wrap: wrap; align-items: flex-start; }
    .hx-year__name { font-weight: 700; overflow-wrap: anywhere; }
    .hx-year__meta { color: var(--nt-muted); font-size: .78rem; }
    .hx-year__actions { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .hx-year__actions form { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
    .hx-confirm { max-width: 11rem; }
    .hx-list { display: grid; gap: .6rem; }
    .hx-orden-box { max-height: 26rem; overflow-y: auto; padding: .25rem; }
    .hx-check { margin: 0; }
    details.hx-det > summary { cursor: pointer; font-weight: 600; padding: .35rem 0; }
</style>

<div class="mb-3">
    <h2 class="nt-page-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Histórico de Años</h2>
    <p class="nt-page-sub m-0">Cada año (diciembre a noviembre) se guarda como una foto completa. Puedes guardar, cargar otro año o crear el año nuevo sin perder nada.</p>
</div>

<!-- Año cargado -->
<div class="nt-hero mb-3">
    <span class="nt-eyebrow"><i class="fa-solid fa-code-branch"></i>Año cargado</span>
    <h3 class="mt-2 mb-0" style="font-family: var(--font-heading); font-weight: 800; color:#fff; overflow-wrap:anywhere;">
        <?= $actual ? htmlspecialchars($actual['nombre']) : 'Sin guardar todavía' ?>
    </h3>
    <p class="m-0 mt-1" style="color: var(--nt-pine-200); font-size: .85rem;">
        <?= $actual ? 'Lo que ves en la aplicación pertenece a este año.' : 'Guarda el año actual con un nombre (por ejemplo ' . htmlspecialchars($nombreSugerido) . ') antes de cargar otro o crear uno nuevo.' ?>
    </p>
    <div class="hx-stats">
        <div class="nt-stat nt-stat--on-dark"><span class="nt-stat__label">Reuniones</span><span class="nt-stat__value"><?= (int)$conteos['natillera_reuniones'] ?></span></div>
        <div class="nt-stat nt-stat--on-dark"><span class="nt-stat__label">Préstamos</span><span class="nt-stat__value"><?= (int)$conteos['natillera_prestamos'] ?></span></div>
        <div class="nt-stat nt-stat--on-dark"><span class="nt-stat__label">Cuotas</span><span class="nt-stat__value"><?= (int)$conteos['natillera_ahorros_cuotas'] ?></span></div>
        <div class="nt-stat nt-stat--on-dark"><span class="nt-stat__label">Socios activos</span><span class="nt-stat__value"><?= count($activos) ?></span></div>
    </div>
</div>

<div class="hx-grid hx-grid--2 mb-3">
    <!-- Guardar -->
    <section class="hx-card" aria-labelledby="hxGuardar">
        <h3 id="hxGuardar"><i class="fa-solid fa-floppy-disk text-success"></i>Guardar este año</h3>
        <p class="hx-hint">Copia lo que hay hoy en la aplicación al año cargado. No borra nada. Las contraseñas nunca se guardan.</p>
        <form action="/admin/historico/guardar" method="POST" class="d-grid gap-2">
            <label for="hxNombreGuardar" class="form-label m-0 small fw-semibold">Nombre del año</label>
            <input type="text" id="hxNombreGuardar" name="nombre" class="form-control" maxlength="120" value="<?= htmlspecialchars($actual ? $actual['nombre'] : $nombreSugerido) ?>" required>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar este año</button>
        </form>
    </section>

    <!-- Orden de socios -->
    <section class="hx-card" aria-labelledby="hxOrden">
        <h3 id="hxOrden"><i class="fa-solid fa-arrow-down-1-9 text-warning"></i>Orden de socios</h3>
        <p class="hx-hint">Se usa en el llamado a lista, préstamos, actividades y entregas. Arrastra con el ícono o usa las flechas.</p>
        <details class="hx-det">
            <summary>Ver y cambiar el orden (<?= count($activos) ?> activos)</summary>
            <form action="/admin/socios/ordenar" method="POST" class="mt-2">
                <input type="hidden" name="volver" value="historico">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-nt-orden-az="#hxListaOrden"><i class="fa-solid fa-arrow-down-a-z me-1"></i>A-Z</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-nt-orden-reset="#hxListaOrden"><i class="fa-solid fa-rotate-left me-1"></i>Volver al orden actual</button>
                </div>
                <div class="hx-orden-box">
                    <ul class="nt-orden" id="hxListaOrden" data-nt-orden>
                        <?php foreach ($socios as $s): ?>
                            <li class="nt-orden__item<?= (int)$s['estado'] === 0 ? ' is-off' : '' ?>" data-id="<?= (int)$s['id'] ?>" data-nombre="<?= htmlspecialchars($s['nombre_completo']) ?>">
                                <button type="button" class="nt-orden__handle" aria-label="Arrastrar a <?= htmlspecialchars($s['nombre_completo']) ?>"><i class="fa-solid fa-grip-vertical"></i></button>
                                <span class="nt-orden__pos"></span>
                                <span class="nt-orden__name"><?= htmlspecialchars($s['nombre_completo']) ?><span class="nt-orden__sub"><?= htmlspecialchars($s['rol_nombre']) ?><?= (int)$s['estado'] === 0 ? ' · inactivo' : '' ?></span></span>
                                <span class="nt-orden__btns">
                                    <button type="button" class="nt-orden__up" aria-label="Subir"><i class="fa-solid fa-chevron-up"></i></button>
                                    <button type="button" class="nt-orden__down" aria-label="Bajar"><i class="fa-solid fa-chevron-down"></i></button>
                                </span>
                                <input type="hidden" name="orden[]" value="<?= (int)$s['id'] ?>">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button type="submit" class="btn btn-outline-primary mt-2 w-100">Guardar solo el orden</button>
            </form>
        </details>
    </section>
</div>

<!-- Años guardados -->
<section class="hx-card mb-3" aria-labelledby="hxAnios">
    <h3 id="hxAnios"><i class="fa-solid fa-layer-group text-primary"></i>Años guardados</h3>
    <p class="hx-hint">Para cargar otro año escribe CONFIRMAR. Antes de cambiar se guarda el año cargado y se hace un respaldo automático que no se modifica.</p>
    <div class="hx-list">
        <?php if (empty($aniosNormales)): ?>
            <div class="text-muted small">Todavía no hay años guardados.</div>
        <?php endif; ?>
        <?php foreach ($aniosNormales as $a):
            $c = json_decode($a['conteos_json'] ?: '{}', true) ?: [];
            $esActual = (int)$a['cargado'] === 1;
        ?>
            <article class="hx-year<?= $esActual ? ' is-current' : '' ?>">
                <div class="hx-year__top">
                    <div class="min-w-0">
                        <div class="hx-year__name"><?= htmlspecialchars($a['nombre']) ?></div>
                        <div class="hx-year__meta">
                            Guardado <?= date('d/m/Y H:i', strtotime($a['updated_at'] ?: $a['created_at'])) ?> ·
                            <?= (int)($c['natillera_reuniones'] ?? 0) ?> reuniones · <?= (int)($c['natillera_prestamos'] ?? 0) ?> préstamos · <?= (int)($c['natillera_ahorros_cuotas'] ?? 0) ?> cuotas · <?= $fmtBytes((int)$a['bytes']) ?>
                        </div>
                    </div>
                    <?php if ($esActual): ?><span class="nt-chip"><i class="fa-solid fa-circle-check"></i>Cargado</span><?php endif; ?>
                </div>
                <div class="hx-year__actions">
                    <a class="btn btn-sm btn-outline-secondary" href="/admin/historico/descargar?id=<?= (int)$a['id'] ?>"><i class="fa-solid fa-download me-1"></i>Descargar JSON</a>
                    <?php if (!$esActual): ?>
                        <form action="/admin/historico/cargar" method="POST">
                            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                            <input type="text" name="confirmar" class="form-control form-control-sm hx-confirm" placeholder="Escribe CONFIRMAR" autocomplete="off" aria-label="Escribe CONFIRMAR para cargar <?= htmlspecialchars($a['nombre']) ?>" required>
                            <button type="submit" class="btn btn-sm btn-warning"><i class="fa-solid fa-code-branch me-1"></i>Cargar este año</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($respaldos)): ?>
        <details class="hx-det mt-3">
            <summary>Respaldos automáticos (<?= count($respaldos) ?>)</summary>
            <p class="hx-hint mt-2">No se pueden modificar. "Recuperar" los copia como un año normal para poder cargarlos.</p>
            <div class="hx-list">
                <?php foreach ($respaldos as $r): ?>
                    <article class="hx-year">
                        <div class="hx-year__name"><?= htmlspecialchars($r['nombre']) ?></div>
                        <div class="hx-year__meta"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> · <?= $fmtBytes((int)$r['bytes']) ?><?= $r['archivo'] ? ' · archivo ' . htmlspecialchars($r['archivo']) : '' ?></div>
                        <div class="hx-year__actions">
                            <a class="btn btn-sm btn-outline-secondary" href="/admin/historico/descargar?id=<?= (int)$r['id'] ?>"><i class="fa-solid fa-download me-1"></i>Descargar JSON</a>
                            <form action="/admin/historico/recuperar" method="POST">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-box-open me-1"></i>Recuperar como año</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endif; ?>
</section>

<!-- Crear año nuevo -->
<section class="hx-card mb-4" aria-labelledby="hxNuevo">
    <h3 id="hxNuevo"><i class="fa-solid fa-circle-plus text-success"></i>Crear año nuevo</h3>
    <p class="hx-hint">Guarda el año cargado, vacía reuniones, cuotas, préstamos, abonos, actividades, entregas, inyecciones y cierres, y empieza en blanco. Los socios, roles y suscripciones no se borran.</p>
    <form action="/admin/historico/crear" method="POST" id="hxFormNuevo">
        <div class="row g-2 mb-3">
            <div class="col-12 col-md-7">
                <label for="hxNombreNuevo" class="form-label small fw-semibold m-0">Nombre del año nuevo</label>
                <input type="text" id="hxNombreNuevo" name="nombre" class="form-control" maxlength="120" value="<?= htmlspecialchars('Dic ' . (substr($nombreSugerido, 4, 4) + 1) . ' - Nov ' . (substr($nombreSugerido, 4, 4) + 2)) ?>" required>
            </div>
            <div class="col-12 col-md-5">
                <label for="hxFechaNueva" class="form-label small fw-semibold m-0">Fecha de la primera reunión</label>
                <input type="date" id="hxFechaNueva" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fechaSugerida) ?>" required>
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="pasar_prestamos" value="1" id="hxPasar">
            <label class="form-check-label" for="hxPasar">
                Pasar al año nuevo los préstamos que siguen activos
                <span class="d-block hx-hint m-0"><?= count($prestamosActivos) ?> préstamos con <?= $fmtMoney($capitalActivo) ?> de capital. Entran como saldo inicial (sin firma ni egreso) y el interés corre desde la fecha de inicio. El interés pendiente (<?= $fmtMoney($interesActivo) ?>) no se traslada: cóbralo antes.</span>
            </label>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <strong class="small">Socios que continúan y su orden</strong>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="hxTodos">Marcar todos</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-nt-orden-az="#hxListaNuevo"><i class="fa-solid fa-arrow-down-a-z me-1"></i>A-Z</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-nt-orden-reset="#hxListaNuevo"><i class="fa-solid fa-rotate-left me-1"></i>Volver al orden actual</button>
            </div>
        </div>
        <div class="hx-orden-box mb-3">
            <ul class="nt-orden" id="hxListaNuevo" data-nt-orden>
                <?php foreach ($socios as $s): $on = (int)$s['estado'] === 1; ?>
                    <li class="nt-orden__item<?= $on ? '' : ' is-off' ?>" data-id="<?= (int)$s['id'] ?>" data-nombre="<?= htmlspecialchars($s['nombre_completo']) ?>">
                        <button type="button" class="nt-orden__handle" aria-label="Arrastrar a <?= htmlspecialchars($s['nombre_completo']) ?>"><i class="fa-solid fa-grip-vertical"></i></button>
                        <span class="nt-orden__pos"></span>
                        <label class="nt-orden__name hx-check" style="cursor:pointer;">
                            <input type="checkbox" class="form-check-input me-1 hx-continua" name="continuan[]" value="<?= (int)$s['id'] ?>" <?= $on ? 'checked' : '' ?>>
                            <?= htmlspecialchars($s['nombre_completo']) ?><span class="nt-orden__sub"><?= htmlspecialchars($s['rol_nombre']) ?><?= $on ? '' : ' · inactivo' ?></span>
                        </label>
                        <span class="nt-orden__btns">
                            <button type="button" class="nt-orden__up" aria-label="Subir"><i class="fa-solid fa-chevron-up"></i></button>
                            <button type="button" class="nt-orden__down" aria-label="Bajar"><i class="fa-solid fa-chevron-down"></i></button>
                        </span>
                        <input type="hidden" name="orden[]" value="<?= (int)$s['id'] ?>">
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p class="hx-hint"><span id="hxContador"><?= count($activos) ?></span> socios continúan. Quien hace la operación (tú) siempre queda activo.</p>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="confirmar" class="form-control hx-confirm" placeholder="Escribe CONFIRMAR" autocomplete="off" aria-label="Escribe CONFIRMAR para crear el año nuevo" required>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Crear año nuevo</button>
        </div>
    </form>
</section>

<script src="/js/orden_socios.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var lista = document.getElementById('hxListaNuevo');
    if (!lista) return;
    function refrescar() {
        var n = 0;
        lista.querySelectorAll('.hx-continua').forEach(function (cb) {
            cb.closest('.nt-orden__item').classList.toggle('is-off', !cb.checked);
            if (cb.checked) n++;
        });
        document.getElementById('hxContador').textContent = n;
    }
    lista.addEventListener('change', refrescar);
    document.getElementById('hxTodos').addEventListener('click', function () {
        lista.querySelectorAll('.hx-continua').forEach(function (cb) { cb.checked = true; });
        refrescar();
    });
    refrescar();
});
</script>
