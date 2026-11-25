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

<!-- Héroe: Saldo Real Consolidado en Caja -->
<div class="nt-hero nt-hero--dash mb-4">
    <div class="nt-hero-grid nt-hero-grid--split">
        <div>
            <span class="nt-eyebrow"><i class="fa-solid fa-vault"></i>LIQUIDEZ REAL DISPONIBLE EN CAJA</span>
            <h1 class="nt-hero__value nt-money mt-2<?= $resumen['saldo_real_caja'] < 0 ? ' is-negative' : '' ?>">
                $<?= number_format($resumen['saldo_real_caja'], 0, ',', '.') ?> <small>COP</small>
            </h1>
            <p class="nt-hero__note">
                Fórmula de Arqueo Real: (Cuotas Base + Ahorro Extra + Intereses Cobrados + Inyecciones) - (Cartera Prestada Pendiente + Retiros + Entregas).
            </p>
        </div>
        <div>
            <div class="nt-tiles nt-tiles--3">
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-arrow-down"></i>Ingresos Totales Recaudados</span>
                        <span class="nt-stat__value nt-money">$<?= number_format($resumen['ingresos_totales'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-hand-holding-dollar"></i>Cartera Prestada en Calle</span>
                        <span class="nt-stat__value nt-money is-gold">$<?= number_format($resumen['cartera_activa_pendiente'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-piggy-bank"></i>Total Ahorro Obligatorio</span>
                        <span class="nt-stat__value nt-money">$<?= number_format($resumen['total_cuotas'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-percent"></i>Intereses Cobrados</span>
                        <span class="nt-stat__value nt-money">$<?= number_format($resumen['total_intereses_cobrados'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-chart-line"></i>Inyecciones Capital Activas</span>
                        <span class="nt-stat__value nt-money">$<?= number_format($resumen['total_inyecciones_activas'], 0, ',', '.') ?></span>
                    </div>
                </div>
                <div>
                    <div class="nt-stat nt-stat--on-dark">
                        <span class="nt-stat__label"><i class="fa-solid fa-gift"></i>Entregas Rifas/Rondas</span>
                        <span class="nt-stat__value nt-money">$<?= number_format($resumen['total_entregas'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
