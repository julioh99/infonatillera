<link rel="stylesheet" href="/css/pages/dashboards.css">
<div class="nt-d-head">
    <div>
        <h2 class="nt-page-title m-0">
            <i class="fa-solid fa-crown nt-d-title-ico"></i>Tablero Presidencia & Control Ejecutivo de Caja
        </h2>
        <p class="nt-page-sub m-0 mt-1">Saldos reales de caja, cartera de préstamos en la calle, movimientos globales y métricas de participación de socios.</p>
    </div>
    <div class="nt-d-head__actions">
        <a href="/admin/cierre-reunion" class="btn btn-outline-primary rounded-pill fw-semibold btn-sm px-3">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i>Ver Arqueo por Reunión
        </a>
        <a href="/admin/inyecciones" class="btn btn-primary rounded-pill fw-bold btn-sm px-3">
            <i class="fa-solid fa-chart-line me-1"></i>Inyecciones Capital
        </a>
    </div>
</div>

<!-- Caja y fondos reservados (fuente única: CajaGlobal, la misma del cierre de reunión) -->
<?php
$fmt = function ($v) { return '$' . number_format((float)$v, 0, ',', '.'); };
$revisar = !empty($resumen['revisar_datos']);
?>
<link rel="stylesheet" href="/css/pages/fondos.css">
<div class="fd-cards mb-3">
    <div class="fd-card fd-card--main<?= $resumen['caja_total'] < 0 ? ' is-neg' : '' ?>">
        <span class="fd-label">Caja total</span>
        <strong class="fd-value"><?= $fmt($resumen['caja_total']) ?></strong>
        <?php if ($revisar): ?><span class="fd-warn" title="Algún saldo salió negativo: hay datos inconsistentes">Revisar datos</span><?php endif; ?>
    </div>
    <div class="fd-card<?= $resumen['reservado_ronda'] < 0 ? ' is-neg' : '' ?>">
        <span class="fd-label">Reservado ronda</span>
        <strong class="fd-value"><?= $fmt($resumen['reservado_ronda']) ?></strong>
    </div>
    <div class="fd-card<?= $resumen['reservado_rifa'] < 0 ? ' is-neg' : '' ?>">
        <span class="fd-label">Reservado rifa</span>
        <strong class="fd-value"><?= $fmt($resumen['reservado_rifa']) ?></strong>
    </div>
    <div class="fd-card fd-card--main<?= $resumen['disponible_prestar'] < 0 ? ' is-neg' : '' ?>">
        <span class="fd-label">Disponible para prestar</span>
        <strong class="fd-value"><?= $fmt($resumen['disponible_prestar']) ?></strong>
    </div>
</div>
<div class="fd-cards fd-cards--small mb-2">
    <div class="fd-card"><span class="fd-label">Ingresos totales</span><strong class="fd-value"><?= $fmt($resumen['ingresos_totales']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Cartera en la calle</span><strong class="fd-value"><?= $fmt($resumen['cartera_pendiente']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Ahorro obligatorio</span><strong class="fd-value"><?= $fmt($resumen['ahorro_obligatorio']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Intereses cobrados</span><strong class="fd-value"><?= $fmt($resumen['intereses']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Inyecciones activas</span><strong class="fd-value"><?= $fmt($resumen['inyecciones_activas']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Premios entregados</span><strong class="fd-value"><?= $fmt($resumen['premios_entregados']) ?></strong></div>
    <div class="fd-card"><span class="fd-label">Préstamos entregados</span><strong class="fd-value"><?= $fmt($resumen['prestamos_entregados']) ?></strong></div>
</div>
<p class="fd-note mb-4">
    Caja = ahorro + ahorro extra + aportes de ronda y rifa + abonos a capital + intereses + inyecciones y devoluciones
    − préstamos entregados − premios − inyecciones devueltas − préstamos a actividades.
    Cuadre con los cierres: <?= $fmt($resumen['caja_cierres']) ?>
    <?php if (abs($resumen['diferencia_cierres']) > 0.5): ?>
        (diferencia <?= $fmt($resumen['diferencia_cierres']) ?>: movimientos registrados después de cerrar alguna reunión)
    <?php else: ?>(sin diferencia)<?php endif; ?>
</p>

<!-- Métricas de Participación e Inactividad de Socios -->
<div class="row g-4 mb-4 nt-stagger">
    <!-- Columna 1: Socios SIN Préstamos Activos -->
    <div class="col-12 col-lg-4">
        <div class="card nt-panel nt-list-card h-100">
            <div class="nt-panel__head">
                <h6 class="nt-panel__title">
                    <i class="fa-solid fa-handshake-slash"></i>
                    Socios Sin Préstamos Activos
                </h6>
                <span class="badge rounded-pill font-outfit nt-count"><?= count($sociosSinPrestamos) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="nt-fineprint mb-3">Socios que no tienen saldo de crédito pendiente en la actualidad (Capacidad disponible de otorgamiento de préstamo).</p>
                <div class="input-group mb-3 nt-search">
                    <span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioSinPrestamo" class="form-control border-start-0" placeholder="Buscar socio sin préstamo...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosSinPrestamos)): ?>
                        <div class="text-center py-4 text-muted fs-7">Todos los socios tienen préstamos activos.</div>
                    <?php else: ?>
                        <?php foreach ($sociosSinPrestamos as $s): ?>
                            <div class="list-group-item nt-person row-socio-sin-prestamo" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="nt-person__name"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="nt-person__sub">C.C. <?= htmlspecialchars($s['cedula']) ?></small>
                                    </div>
                                    <span class="nt-tag">
                                        Tope: $<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna 2: Socios SIN Participación en Actividades (Tamales/Rifas) -->
    <div class="col-12 col-lg-4">
        <div class="card nt-panel nt-list-card nt-list-card--gold h-100">
            <div class="nt-panel__head">
                <h6 class="nt-panel__title">
                    <i class="fa-solid fa-utensils"></i>
                    Sin Participación en Actividades
                </h6>
                <span class="badge rounded-pill font-outfit nt-count"><?= count($sociosSinActividades) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="nt-fineprint mb-3">Socios que no han comprado ni vendido en los eventos especiales o tamaladas de la natillera.</p>
                <div class="input-group mb-3 nt-search">
                    <span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioSinActividad" class="form-control border-start-0" placeholder="Buscar socio sin actividad...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosSinActividades)): ?>
                        <div class="text-center py-4 text-muted fs-7">¡Excelente! Todos los socios han participado en actividades.</div>
                    <?php else: ?>
                        <?php foreach ($sociosSinActividades as $s): ?>
                            <div class="list-group-item nt-person row-socio-sin-actividad" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="nt-person__name"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="nt-person__sub"><i class="fa-solid fa-phone me-1 nt-pos"></i><?= htmlspecialchars($s['telefono'] ?: 'Sin tel') ?></small>
                                    </div>
                                    <span class="nt-tag nt-tag--muted">Sin Registro</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna 3: Socios con Cuotas de Ahorro Pendientes / Morosos -->
    <div class="col-12 col-lg-4">
        <div class="card nt-panel nt-list-card nt-list-card--red h-100">
            <div class="nt-panel__head">
                <h6 class="nt-panel__title">
                    <i class="fa-solid fa-user-clock"></i>
                    Socios con Cuotas Pendientes
                </h6>
                <span class="badge rounded-pill font-outfit nt-count"><?= count($sociosCuotasPendientes) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="nt-fineprint mb-3">Socios con registros incompletos o llamadas a lista sin abono de cuota base ($40k).</p>
                <div class="input-group mb-3 nt-search">
                    <span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioMora" class="form-control border-start-0" placeholder="Buscar socio en mora...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosCuotasPendientes)): ?>
                        <div class="text-center py-4 text-muted fs-7">¡Perfecto! Todos los socios están al día con sus cuotas.</div>
                    <?php else: ?>
                        <?php foreach ($sociosCuotasPendientes as $s): ?>
                            <div class="list-group-item nt-person row-socio-mora" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="nt-person__name"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="nt-person__sub"><i class="fa-solid fa-phone me-1 nt-pos"></i><?= htmlspecialchars($s['telefono'] ?: 'Sin tel') ?></small>
                                    </div>
                                    <span class="nt-tag nt-tag--red">
                                        <?= $s['cuotas_debe'] ?> cuota(s) pendiente(s)
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Filtro 1: Socios sin préstamo
    document.getElementById('buscarSocioSinPrestamo')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.row-socio-sin-prestamo').forEach(row => {
            const text = row.getAttribute('data-search') || row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    // Filtro 2: Socios sin actividad
    document.getElementById('buscarSocioSinActividad')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.row-socio-sin-actividad').forEach(row => {
            const text = row.getAttribute('data-search') || row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    // Filtro 3: Socios en mora
    document.getElementById('buscarSocioMora')?.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.row-socio-mora').forEach(row => {
            const text = row.getAttribute('data-search') || row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
});
</script>
