<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-7 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
            Arqueo de Caja y Cierre Financiero por Reunión
        </h2>
        <p class="text-muted m-0 fs-7">Liquidación detallada de ingresos (+), egresos (-) y saldo de caja para cada quincena.</p>
    </div>
    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
        <form method="GET" action="/admin/cierre-reunion" class="d-flex align-items-center gap-2">
            <label for="selectReunionCierre" class="form-label fw-semibold fs-7 mb-0 text-nowrap">Reunión:</label>
            <select name="reunion_id" id="selectReunionCierre" class="form-select form-select-sm fw-bold shadow-sm" onchange="this.form.submit()">
                <?php foreach ($reuniones as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($r['id'] == $reunionId) ? 'selected' : '' ?>>
                        Quincena Q<?= $r['numero_quincena'] ?> (<?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?>) - <?= $r['estado'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($resumen): 
    $rInfo = $resumen['reunion'];
    $ing = $resumen['ingresos'];
    $egr = $resumen['egresos'];
    $isCerrada = ($rInfo['estado'] === 'CERRADA');
?>
    <!-- Encabezado de la Reunión Seleccionada -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-navy text-white p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-dark font-outfit fw-bold fs-7 px-3 py-1 mb-2">
                    <i class="fa-solid fa-calendar-day text-primary me-1"></i>Quincena Q<?= $rInfo['numero_quincena'] ?>
                </span>
                <h3 class="font-outfit fw-bold text-white m-0">
                    Fecha de Reunión: <?= date('d/m/Y', strtotime($rInfo['fecha_reunion'])) ?>
                </h3>
                <small class="text-white-50 fs-8">Valor Cuota Base: $<?= number_format($rInfo['valor_cuota_base'], 0, ',', '.') ?> COP | Evento: <?= htmlspecialchars($rInfo['tipo_evento_extra']) ?></small>
            </div>
            <div class="text-end">
                <?php if ($isCerrada): ?>
                    <span class="badge bg-success fs-6 px-4 py-2 rounded-pill"><i class="fa-solid fa-lock me-2"></i>REUNIÓN CERRADA</span>
                    <?php if ($cierreExistente): ?>
                        <small class="d-block text-white-50 fs-8 mt-1">Cerrado el <?= date('d/m/Y g:i a', strtotime($cierreExistente['fecha_cierre'])) ?> por <?= htmlspecialchars($cierreExistente['cerrado_por_nombre']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6 px-4 py-2 rounded-pill"><i class="fa-solid fa-unlock me-2"></i>EN PROCESO / PENDIENTE CIERRE</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Desglose Ingresos vs Egresos -->
    <div class="row g-4 mb-4">
        <!-- Columna 1: INGRESOS (+) -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden border-top border-4 border-success">
                <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                    <h5 class="font-outfit fw-bold text-success m-0 d-flex align-items-center justify-content-between">
                        <span><i class="fa-solid fa-arrow-down-long me-2"></i>INGRESOS A CAJA (+)</span>
                        <span>$<?= number_format($ing['total'], 0, ',', '.') ?> COP</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-7">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Cuotas Base Ahorradas ($40.000)</strong>
                                <small class="text-muted">Aporte directo de socios para liquidación</small>
                            </div>
                            <span class="fw-bold text-dark font-outfit fs-6">$<?= number_format($ing['cuotas_base'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Ahorro Voluntario Extra</strong>
                                <small class="text-muted">Ahorro adicional elegido por socios</small>
                            </div>
                            <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['ahorro_extra'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Abonos a Capital de Préstamos</strong>
                                <small class="text-muted">Recuperación de principal prestado</small>
                            </div>
                            <span class="fw-bold text-primary font-outfit fs-6">$<?= number_format($ing['abono_capital'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Intereses Cobrados de Préstamos</strong>
                                <small class="text-muted">Ganancia bruta por financiamiento</small>
                            </div>
                            <span class="fw-bold text-warning font-outfit fs-6">$<?= number_format($ing['intereses_prestamos'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Devoluciones Recibidas de Caja de Actividades</strong>
                                <small class="text-muted">Reembolso de capital o aporte de utilidades de eventos</small>
                            </div>
                            <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['devoluciones_actividades'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Inyecciones de Capital Ingresadas</strong>
                                <small class="text-muted">Aportes de inversión inicial</small>
                            </div>
                            <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['inyecciones'], 0, ',', '.') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Columna 2: EGRESOS (-) -->
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden border-top border-4 border-danger">
                <div class="card-header bg-danger bg-opacity-10 border-0 py-3">
                    <h5 class="font-outfit fw-bold text-danger m-0 d-flex align-items-center justify-content-between">
                        <span><i class="fa-solid fa-arrow-up-long me-2"></i>EGRESOS DE CAJA (-)</span>
                        <span>$<?= number_format($egr['total'], 0, ',', '.') ?> COP</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush fs-7">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Préstamos Otorgados / Desembolsados</strong>
                                <small class="text-muted">Capital girado a socios en la reunión</small>
                            </div>
                            <span class="fw-bold text-danger font-outfit fs-6">$<?= number_format($egr['prestamos_otorgados'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Inyecciones Devueltas / Retiradas</strong>
                                <small class="text-muted">Retiro de inyecciones tras 6 meses</small>
                            </div>
                            <span class="fw-bold text-danger font-outfit fs-6">$<?= number_format($egr['inyecciones_devueltas'], 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Préstamos Sin Interés a Caja de Actividades</strong>
                                <small class="text-muted">Capital girado para financiamiento inicial de eventos</small>
                            </div>
                            <span class="fw-bold text-warning font-outfit fs-6">$<?= number_format($egr['prestamos_actividades'], 0, ',', '.') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Final y Acción de Cierre -->
    <div class="card border-0 shadow-lg rounded-4 mb-5 p-4 bg-white">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-4 border-end-md">
                <span class="fs-8 text-muted fw-semibold text-uppercase d-block">Flujo Neto de la Reunión (Q<?= $rInfo['numero_quincena'] ?>)</span>
                <h2 class="font-outfit fw-bold <?= ($resumen['saldo_neto_reunion'] >= 0) ? 'text-success' : 'text-danger' ?> m-0 mt-1">
                    $<?= number_format($resumen['saldo_neto_reunion'], 0, ',', '.') ?> COP
                </h2>
                <small class="text-muted fs-8">Calculado como: Total Ingresos - Total Egresos</small>
            </div>
            <div class="col-12 col-md-4 border-end-md">
                <span class="fs-8 text-muted fw-semibold text-uppercase d-block">Saldo Acumulado Global en Caja</span>
                <h2 class="font-outfit fw-bold text-primary m-0 mt-1">
                    $<?= number_format($resumen['saldo_acumulado_caja'], 0, ',', '.') ?> COP
                </h2>
                <small class="text-muted fs-8">Efectivo total disponible en la Natillera</small>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <?php if ($isCerrada): ?>
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4 py-2" disabled>
                        <i class="fa-solid fa-lock me-1"></i>Reunión ya Cerrada
                    </button>
                <?php else: ?>
                    <form action="/admin/cierre-reunion/cerrar" method="POST" onsubmit="return confirm('¿Confirmas realizar el CIERRE FINANCIERO de la Quincena Q<?= $rInfo['numero_quincena'] ?>? Esta acción guardará el arqueo e inmovilizará los registros.');">
                        <input type="hidden" name="reunion_id" value="<?= $rInfo['id'] ?>">
                        <button type="submit" class="btn btn-warning text-dark rounded-pill fw-bold px-4 py-2 shadow">
                            <i class="fa-solid fa-lock me-2"></i>Realizar Cierre Financiero
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Historial de Cierres de Reunión Realizados -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white font-outfit fw-bold py-3">
        <i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Historial de Cierres Financieros de Reunión
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 fs-7">
            <thead class="bg-light font-outfit text-uppercase fs-8">
                <tr>
                    <th class="ps-4">Reunión / Quincena</th>
                    <th>Fecha Cierre</th>
                    <th>Total Ingresos</th>
                    <th>Total Egresos</th>
                    <th>Flujo Neto Reunión</th>
                    <th>Saldo Acumulado Caja</th>
                    <th>Cerrado Por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todosCierres)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se ha realizado ningún cierre financiero de reunión aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todosCierres as $c): ?>
                        <tr>
                            <td class="ps-4 fw-bold font-outfit">
                                Quincena Q<?= $c['numero_quincena'] ?>
                                <small class="d-block text-muted fw-normal"><?= date('d/m/Y', strtotime($c['fecha_reunion'])) ?></small>
                            </td>
                            <td><?= date('d/m/Y g:i a', strtotime($c['fecha_cierre'])) ?></td>
                            <td class="fw-bold text-success">$<?= number_format($c['total_ingresos_general'], 0, ',', '.') ?></td>
                            <td class="fw-bold text-danger">$<?= number_format($c['total_egresos_general'], 0, ',', '.') ?></td>
                            <td class="fw-bold font-outfit <?= ($c['saldo_neto_reunion'] >= 0) ? 'text-success' : 'text-danger' ?>">$<?= number_format($c['saldo_neto_reunion'], 0, ',', '.') ?></td>
                            <td class="fw-bold font-outfit text-primary">$<?= number_format($c['saldo_acumulado_caja'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($c['cerrado_por_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
