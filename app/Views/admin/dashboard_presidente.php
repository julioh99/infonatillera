<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-7 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-crown text-warning"></i>
            Tablero Presidencia & Control Ejecutivo de Caja
        </h2>
        <p class="text-muted m-0 fs-7">Saldos reales de caja, cartera de préstamos en la calle, movimientos globales y métricas de participación de socios.</p>
    </div>
    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
        <a href="/admin/cierre-reunion" class="btn btn-outline-primary rounded-pill fw-semibold btn-sm shadow-sm">
            <i class="fa-solid fa-file-invoice-dollar me-1"></i>Ver Arqueo por Reunión
        </a>
        <a href="/admin/inyecciones" class="btn btn-success rounded-pill fw-bold btn-sm shadow-sm px-3">
            <i class="fa-solid fa-chart-line me-1"></i>Inyecciones Capital
        </a>
    </div>
</div>

<!-- Tarjeta Principal: Saldo Real Consolidado en Caja -->
<div class="card border-0 shadow-lg rounded-4 mb-4 bg-gradient-navy text-white p-4">
    <div class="row align-items-center g-4">
        <div class="col-12 col-lg-5 border-lg-end border-white border-opacity-10">
            <span class="badge bg-warning text-dark font-outfit fw-bold px-3 py-1 mb-2">
                <i class="fa-solid fa-vault me-1"></i>LIQUIDEZ REAL DISPONIBLE EN CAJA
            </span>
            <h1 class="display-5 font-outfit fw-bold text-warning m-0">
                $<?= number_format($resumen['saldo_real_caja'], 0, ',', '.') ?> <small class="fs-6 text-white-50">COP</small>
            </h1>
            <p class="text-white-50 fs-7 mt-2 mb-0">
                Fórmula de Arqueo Real: (Cuotas Base + Ahorro Extra + Intereses Cobrados + Inyecciones) - (Cartera Prestada Pendiente + Retiros + Entregas).
            </p>
        </div>
        <div class="col-12 col-lg-7">
            <div class="row g-3 text-center text-lg-start">
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-arrow-down text-success me-1"></i>Ingresos Totales Recaudados</small>
                    <strong class="font-outfit fs-6 text-success">$<?= number_format($resumen['ingresos_totales'], 0, ',', '.') ?></strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-hand-holding-dollar text-warning me-1"></i>Cartera Prestada en Calle</small>
                    <strong class="font-outfit fs-6 text-warning">$<?= number_format($resumen['cartera_activa_pendiente'], 0, ',', '.') ?></strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-piggy-bank text-info me-1"></i>Total Ahorro Obligatorio</small>
                    <strong class="font-outfit fs-6 text-info">$<?= number_format($resumen['total_cuotas'], 0, ',', '.') ?></strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-percent text-success me-1"></i>Intereses Cobrados</small>
                    <strong class="font-outfit fs-6 text-success">$<?= number_format($resumen['total_intereses_cobrados'], 0, ',', '.') ?></strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-chart-line text-primary me-1"></i>Inyecciones Capital Activas</small>
                    <strong class="font-outfit fs-6 text-primary">$<?= number_format($resumen['total_inyecciones_activas'], 0, ',', '.') ?></strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-white-50 fs-8 d-block"><i class="fa-solid fa-gift text-danger me-1"></i>Entregas Rifas/Rondas</small>
                    <strong class="font-outfit fs-6 text-danger">$<?= number_format($resumen['total_entregas'], 0, ',', '.') ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Métricas de Participación e Inactividad de Socios -->
<div class="row g-4 mb-4">
    <!-- Columna 1: Socios SIN Préstamos Activos -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden border-top border-4 border-info">
            <div class="card-header bg-info bg-opacity-10 border-0 p-3 d-flex align-items-center justify-content-between">
                <h6 class="font-outfit fw-bold text-info m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-handshake-slash fs-5"></i>
                    Socios Sin Préstamos Activos
                </h6>
                <span class="badge bg-info text-dark rounded-pill font-outfit"><?= count($sociosSinPrestamos) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="text-muted fs-8 mb-3">Socios que no tienen saldo de crédito pendiente en la actualidad (Capacidad disponible de otorgamiento de préstamo).</p>
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioSinPrestamo" class="form-control border-start-0" placeholder="Buscar socio sin préstamo...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosSinPrestamos)): ?>
                        <div class="text-center py-4 text-muted fs-7">Todos los socios tienen préstamos activos.</div>
                    <?php else: ?>
                        <?php foreach ($sociosSinPrestamos as $s): ?>
                            <div class="list-group-item p-2.5 border-0 bg-light rounded-3 mb-2 row-socio-sin-prestamo" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="d-block font-outfit fs-7 text-dark"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="text-muted fs-8">C.C. <?= htmlspecialchars($s['cedula']) ?></small>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success font-outfit fw-semibold fs-8">
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
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden border-top border-4 border-warning">
            <div class="card-header bg-warning bg-opacity-10 border-0 p-3 d-flex align-items-center justify-content-between">
                <h6 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-utensils fs-5 text-warning"></i>
                    Sin Participación en Actividades
                </h6>
                <span class="badge bg-warning text-dark rounded-pill font-outfit"><?= count($sociosSinActividades) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="text-muted fs-8 mb-3">Socios que no han comprado ni vendido en los eventos especiales o tamaladas de la natillera.</p>
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioSinActividad" class="form-control border-start-0" placeholder="Buscar socio sin actividad...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosSinActividades)): ?>
                        <div class="text-center py-4 text-muted fs-7">¡Excelente! Todos los socios han participado en actividades.</div>
                    <?php else: ?>
                        <?php foreach ($sociosSinActividades as $s): ?>
                            <div class="list-group-item p-2.5 border-0 bg-light rounded-3 mb-2 row-socio-sin-actividad" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'] . ' ' . $s['cedula'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="d-block font-outfit fs-7 text-dark"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="text-muted fs-8"><i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($s['telefono'] ?: 'Sin tel') ?></small>
                                    </div>
                                    <span class="badge bg-secondary text-white font-outfit fs-8">Sin Registro</span>
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
        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden border-top border-4 border-danger">
            <div class="card-header bg-danger bg-opacity-10 border-0 p-3 d-flex align-items-center justify-content-between">
                <h6 class="font-outfit fw-bold text-danger m-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-user-clock fs-5"></i>
                    Socios con Cuotas Pendientes
                </h6>
                <span class="badge bg-danger text-white rounded-pill font-outfit"><?= count($sociosCuotasPendientes) ?> socios</span>
            </div>
            <div class="card-body p-3">
                <p class="text-muted fs-8 mb-3">Socios con registros incompletos o llamadas a lista sin abono de cuota base ($40k).</p>
                <div class="input-group input-group-sm mb-3">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocioMora" class="form-control border-start-0" placeholder="Buscar socio en mora...">
                </div>
                <div class="list-group list-group-flush overflow-auto" style="max-height: 320px;">
                    <?php if (empty($sociosCuotasPendientes)): ?>
                        <div class="text-center py-4 text-muted fs-7">¡Perfecto! Todos los socios están al día con sus cuotas.</div>
                    <?php else: ?>
                        <?php foreach ($sociosCuotasPendientes as $s): ?>
                            <div class="list-group-item p-2.5 border-0 bg-light rounded-3 mb-2 row-socio-mora" data-search="<?= strtolower(htmlspecialchars($s['nombre_completo'])) ?>">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="d-block font-outfit fs-7 text-dark"><?= htmlspecialchars($s['nombre_completo']) ?></strong>
                                        <small class="text-muted fs-8"><i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($s['telefono'] ?: 'Sin tel') ?></small>
                                    </div>
                                    <span class="badge bg-danger text-white font-outfit fs-7 px-2.5 py-1">
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
