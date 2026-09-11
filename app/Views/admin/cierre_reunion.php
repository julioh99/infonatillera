<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-7 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-file-invoice-dollar text-primary"></i>
            Arqueo de Caja y Cierre Financiero por Reunión
        </h2>
        <p class="text-muted m-0 fs-7">Liquidación detallada de ingresos (+), egresos (-) y saldo de caja para cada reunión.</p>
    </div>
    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
        <form method="GET" action="/admin/cierre-reunion" class="d-flex align-items-center gap-2">
            <label for="selectReunionCierre" class="form-label fw-semibold fs-7 mb-0 text-nowrap">Reunión:</label>
            <select name="reunion_id" id="selectReunionCierre" class="form-select form-select-sm fw-bold shadow-sm" onchange="this.form.submit()">
                <?php foreach ($reuniones as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($r['id'] == $reunionId) ? 'selected' : '' ?>>
                        Reunión R<?= $r['numero_quincena'] ?> (<?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?>) - <?= $r['estado'] ?>
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
    $isCierreRealizado = (!empty($cierreExistente) || $rInfo['estado'] === 'CERRADA');
    $hasLlamado = !empty($resumen['tiene_llamado_lista']);
?>
    <!-- Encabezado de la Reunión Seleccionada -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-navy text-white p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-dark font-outfit fw-bold fs-7 px-3 py-1 mb-2">
                    <i class="fa-solid fa-calendar-day text-primary me-1"></i>Reunión R<?= $rInfo['numero_quincena'] ?>
                </span>
                <h3 class="font-outfit fw-bold text-white m-0">
                    Fecha de Reunión: <?= date('d/m/Y', strtotime($rInfo['fecha_reunion'])) ?>
                </h3>
                <small class="text-white-50 fs-8">Valor Cuota Base: $<?= number_format($rInfo['valor_cuota_base'], 0, ',', '.') ?> COP | Evento: <?= htmlspecialchars($rInfo['tipo_evento_extra']) ?></small>
            </div>
            <div class="text-end">
                <?php if ($hasLlamado): ?>
                    <span class="badge bg-info text-dark fs-7 px-3 py-2 rounded-pill me-1">
                        <i class="fa-solid fa-clipboard-check me-1"></i>Llamado a Lista Registrado
                    </span>
                <?php endif; ?>

                <?php if ($isCierreRealizado): ?>
                    <span class="badge bg-success fs-6 px-4 py-2 rounded-pill"><i class="fa-solid fa-lock me-2"></i>CIERRE FINANCIERO REALIZADO</span>
                    <?php if ($cierreExistente): ?>
                        <small class="d-block text-white-50 fs-8 mt-1">Cerrado el <?= date('d/m/Y g:i a', strtotime($cierreExistente['fecha_cierre'])) ?> por <?= htmlspecialchars($cierreExistente['cerrado_por_nombre']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6 px-4 py-2 rounded-pill"><i class="fa-solid fa-unlock me-2"></i>PENDIENTE DE CIERRE FINANCIERO</span>
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
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark font-outfit fs-6">$<?= number_format($ing['cuotas_base'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleCuotasBase" title="Ver desglose por socios">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Ahorro Voluntario Extra</strong>
                                <small class="text-muted">Ahorro adicional elegido por socios</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['ahorro_extra'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleAhorroExtra" title="Ver desglose por socios">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Abonos a Capital de Préstamos</strong>
                                <small class="text-muted">Recuperación de principal prestado</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-primary font-outfit fs-6">$<?= number_format($ing['abono_capital'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleAbonoCapital" title="Ver de qué personas proviene el capital">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Intereses Cobrados de Préstamos</strong>
                                <small class="text-muted">Ganancia bruta por financiamiento</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-warning font-outfit fs-6">$<?= number_format($ing['intereses_prestamos'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleInteresesPrestamos" title="Ver a qué personas se le cobró interés">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Devoluciones Recibidas de Caja de Actividades</strong>
                                <small class="text-muted">Reembolso de capital o aporte de utilidades de eventos</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['devoluciones_actividades'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleDevolucionesActividades" title="Ver desglose de devoluciones">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Inyecciones de Capital Ingresadas</strong>
                                <small class="text-muted">Aportes de inversión inicial</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-success font-outfit fs-6">$<?= number_format($ing['inyecciones'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleInyecciones" title="Ver inversionistas">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
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
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-danger font-outfit fs-6">$<?= number_format($egr['prestamos_otorgados'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetallePrestamosOtorgados" title="Ver socios desembolsados">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Inyecciones Devueltas / Retiradas</strong>
                                <small class="text-muted">Retiro de inyecciones tras 6 meses</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-danger font-outfit fs-6">$<?= number_format($egr['inyecciones_devueltas'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetalleInyeccionesDevueltas" title="Ver inyecciones devueltas">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <strong class="d-block">Préstamos Sin Interés a Caja de Actividades</strong>
                                <small class="text-muted">Capital girado para financiamiento inicial de eventos</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-warning font-outfit fs-6">$<?= number_format($egr['prestamos_actividades'], 0, ',', '.') ?></span>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 rounded-pill fs-8 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDetallePrestamosActividades" title="Ver transferencias a actividades">
                                    <i class="fa-solid fa-eye me-1"></i>Ver
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Final y Acción de Cierre -->
    <div class="card border-0 shadow-lg rounded-4 mb-5 p-4 bg-white">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-3 border-end-md">
                <span class="fs-8 text-muted fw-semibold text-uppercase d-block">Saldo Inicial de Caja (Reunión Anterior)</span>
                <h4 class="font-outfit fw-bold text-secondary m-0 mt-1">
                    $<?= number_format($resumen['saldo_inicial_caja'], 0, ',', '.') ?> COP
                </h4>
                <small class="text-muted fs-8">Acumulado heredado de cierres previos</small>
            </div>
            <div class="col-12 col-md-3 border-end-md">
                <span class="fs-8 text-muted fw-semibold text-uppercase d-block">Flujo Neto de Reunión R<?= $rInfo['numero_quincena'] ?></span>
                <h3 class="font-outfit fw-bold <?= ($resumen['saldo_neto_reunion'] >= 0) ? 'text-success' : 'text-danger' ?> m-0 mt-1">
                    $<?= number_format($resumen['saldo_neto_reunion'], 0, ',', '.') ?> COP
                </h3>
                <small class="text-muted fs-8">Ingresos ($<?= number_format($ing['total'], 0, ',', '.') ?>) - Egresos ($<?= number_format($egr['total'], 0, ',', '.') ?>)</small>
            </div>
            <div class="col-12 col-md-3 border-end-md">
                <span class="fs-8 text-muted fw-semibold text-uppercase d-block">Saldo Acumulado Final en Caja</span>
                <h3 class="font-outfit fw-bold text-primary m-0 mt-1">
                    $<?= number_format($resumen['saldo_acumulado_caja'], 0, ',', '.') ?> COP
                </h3>
                <small class="text-muted fs-8">Saldo Inicial + Flujo Neto de esta reunión</small>
            </div>
            <div class="col-12 col-md-3 text-md-end">
                <?php if ($isCierreRealizado): ?>
                    <button type="button" class="btn btn-success rounded-pill fw-bold px-4 py-2" disabled>
                        <i class="fa-solid fa-lock me-1"></i>Cierre Financiero Realizado
                    </button>
                <?php else: ?>
                    <form action="/admin/cierre-reunion/cerrar" method="POST" onsubmit="return confirm('¿Confirmas realizar el CIERRE FINANCIERO de la Reunión R<?= $rInfo['numero_quincena'] ?>? Esta acción consolidará la caja e inmovilizará el arqueo.');">
                        <input type="hidden" name="reunion_id" value="<?= $rInfo['id'] ?>">
                        <button type="submit" class="btn btn-warning text-dark rounded-pill fw-bold px-4 py-2 shadow">
                            <i class="fa-solid fa-lock me-2"></i>Realizar Cierre Financiero
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODALES DE DESGLOSE DETALLADO POR SOCIO / CONCEPTO -->
    <?php $detalles = $resumen['detalles'] ?? []; ?>

    <!-- MODAL: DETALLE CUOTAS BASE -->
    <div class="modal fade" id="modalDetalleCuotasBase" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-coins text-warning"></i>
                        Desglose de Cuotas Base Ahorradas ($40.000) - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Ahorrador</th>
                                    <th>Cédula</th>
                                    <th>Monto Cuota Base</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['cuotas_base'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay pagos de cuota base registrados en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['cuotas_base'] as $idx => $item): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold text-success">$40.000</td>
                                            <td class="text-muted"><?= !empty($item['created_at']) ? date('d/m/Y g:i a', strtotime($item['created_at'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Total Socios Que Pagaron: <strong class="text-dark"><?= count($detalles['cuotas_base'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-success">Total Cuotas Base: $<?= number_format($ing['cuotas_base'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE AHORRO EXTRA -->
    <div class="modal fade" id="modalDetalleAhorroExtra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-piggy-bank text-success"></i>
                        Desglose Ahorro Voluntario Extra - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Ahorrador</th>
                                    <th>Cédula</th>
                                    <th>Ahorro Extra</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['ahorro_extra'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay aportes de ahorro extra en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['ahorro_extra'] as $idx => $item): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($item['monto_ahorro_extra'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= !empty($item['created_at']) ? date('d/m/Y g:i a', strtotime($item['created_at'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Registros: <strong class="text-dark"><?= count($detalles['ahorro_extra'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-success">Total Ahorro Extra: $<?= number_format($ing['ahorro_extra'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE ABONO CAPITAL -->
    <div class="modal fade" id="modalDetalleAbonoCapital" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-hand-holding-dollar me-1"></i>
                        Desglose Abonos a Capital de Préstamos - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Deudor</th>
                                    <th>Préstamo / Referencia</th>
                                    <th>Capital Pagado</th>
                                    <th>Fecha Abono</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['abono_capital'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se recibieron abonos a capital de préstamos en esta quincena.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['abono_capital'] as $idx => $item): 
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Directo');
                                    ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="text-muted">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-bookmark me-1 text-primary"></i><?= htmlspecialchars($ref) ?></span>
                                                <small class="d-block text-muted">Original: $<?= number_format($item['monto_prestado'], 0, ',', '.') ?></small>
                                            </td>
                                            <td class="fw-bold text-primary">$<?= number_format($item['monto_capital_pagado'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_abono'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Abonos Registrados: <strong class="text-dark"><?= count($detalles['abono_capital'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-primary">Total Capital Recuperado: $<?= number_format($ing['abono_capital'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INTERESES COBRADOS -->
    <div class="modal fade" id="modalDetalleInteresesPrestamos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-percent text-dark"></i>
                        Desglose Intereses Cobrados de Préstamos - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Deudor</th>
                                    <th>Préstamo / Tasa</th>
                                    <th>Interés Cobrado</th>
                                    <th>Fecha Abono</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['intereses_prestamos'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se cobraron intereses de préstamos en esta quincena.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['intereses_prestamos'] as $idx => $item): 
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Directo');
                                    ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="text-muted">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-dark me-1"><?= number_format($item['tasa_interes_mensual'], 1) ?>% mensual</span>
                                                <small class="d-block text-muted"><?= htmlspecialchars($ref) ?></small>
                                            </td>
                                            <td class="fw-bold text-dark font-outfit bg-warning bg-opacity-10 px-2 py-1 rounded">$<?= number_format($item['monto_interes_pagado'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_abono'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Abonos con Interés: <strong class="text-dark"><?= count($detalles['intereses_prestamos'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-dark">Total Intereses Cobrados: $<?= number_format($ing['intereses_prestamos'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE DEVOLUCIONES ACTIVIDADES -->
    <div class="modal fade" id="modalDetalleDevolucionesActividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        Desglose Devoluciones de Caja de Actividades - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Concepto / Descripción</th>
                                    <th>Monto Devuelto</th>
                                    <th>Fecha Transferencia</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['devoluciones_actividades'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No hay devoluciones registradas desde la caja de actividades en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['devoluciones_actividades'] as $idx => $item): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark font-outfit"><?= htmlspecialchars($item['concepto']) ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($item['monto'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_transferencia'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Registros: <strong class="text-dark"><?= count($detalles['devoluciones_actividades'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-success">Total Devoluciones: $<?= number_format($ing['devoluciones_actividades'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INYECCIONES INGRESADAS -->
    <div class="modal fade" id="modalDetalleInyecciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-vault me-1"></i>
                        Desglose Inyecciones de Capital Ingresadas - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Inversionista</th>
                                    <th>Cédula</th>
                                    <th>Monto Inyectado</th>
                                    <th>Tasa Pactada</th>
                                    <th>Fecha Inyección</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['inyecciones'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se ingresaron inyecciones de capital en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['inyecciones'] as $idx => $item): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($item['monto_inyectado'], 0, ',', '.') ?></td>
                                            <td><span class="badge bg-info text-dark"><?= number_format($item['tasa_interes_mensual_pactada'], 1) ?>% / mes</span></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_inyeccion'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Inversionistas: <strong class="text-dark"><?= count($detalles['inyecciones'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-success">Total Inyectado: $<?= number_format($ing['inyecciones'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE PRESTAMOS OTORGADOS (EGRESOS) -->
    <div class="modal fade" id="modalDetallePrestamosOtorgados" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                        Desglose Préstamos Otorgados / Desembolsados - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Deudor</th>
                                    <th>Monto Otorgado</th>
                                    <th>Tasa %</th>
                                    <th>Referencia / Alias</th>
                                    <th>Fecha Desembolso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['prestamos_otorgados'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se otorgaron ni desembolsaron préstamos en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['prestamos_otorgados'] as $idx => $item): 
                                        $montoP = $item['monto_entregado'] ?? $item['monto_prestado'];
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Préstamo Directo');
                                    ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="text-muted">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td class="fw-bold text-danger font-outfit">$<?= number_format($montoP, 0, ',', '.') ?></td>
                                            <td><span class="badge bg-info text-dark"><?= number_format($item['tasa_interes_mensual'] ?? 10, 1) ?>%</span></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($ref) ?></span></td>
                                            <td class="text-muted"><?= !empty($item['fecha_entrega']) ? date('d/m/Y g:i a', strtotime($item['fecha_entrega'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Préstamos Girados: <strong class="text-dark"><?= count($detalles['prestamos_otorgados'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-danger">Total Desembolsado: $<?= number_format($egr['prestamos_otorgados'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INYECCIONES DEVUELTAS (EGRESOS) -->
    <div class="modal fade" id="modalDetalleInyeccionesDevueltas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-danger text-white rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-arrow-up-from-ground-water"></i>
                        Desglose Inyecciones Devueltas / Retiradas - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Socio Inversionista</th>
                                    <th>Capital Devuelto</th>
                                    <th>Rendimientos</th>
                                    <th>Total Entregado</th>
                                    <th>Fecha Retiro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['inyecciones_devueltas'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No se devolvieron inyecciones de capital en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['inyecciones_devueltas'] as $idx => $item): 
                                        $totRet = (float)$item['monto_inyectado'] + (float)$item['monto_rendimiento_generado'];
                                    ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="text-muted">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td class="fw-bold text-dark">$<?= number_format($item['monto_inyectado'], 0, ',', '.') ?></td>
                                            <td class="fw-bold text-success">$<?= number_format($item['monto_rendimiento_generado'], 0, ',', '.') ?></td>
                                            <td class="fw-bold text-danger font-outfit">$<?= number_format($totRet, 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= !empty($item['fecha_retiro']) ? date('d/m/Y g:i a', strtotime($item['fecha_retiro'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Retiros: <strong class="text-dark"><?= count($detalles['inyecciones_devueltas'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-danger">Total Devuelto: $<?= number_format($egr['inyecciones_devueltas'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE PRESTAMOS A CAJA DE ACTIVIDADES (EGRESOS) -->
    <div class="modal fade" id="modalDetallePrestamosActividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title font-outfit fw-bold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bullhorn text-dark"></i>
                        Desglose Préstamos a Caja de Actividades - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0 fs-7">
                            <thead class="bg-light font-outfit text-uppercase fs-8 sticky-top">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Concepto / Destino</th>
                                    <th>Monto Girado</th>
                                    <th>Fecha Transferencia</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['prestamos_actividades'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No se giró capital a la caja de actividades en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['prestamos_actividades'] as $idx => $item): ?>
                                        <tr>
                                            <td class="ps-4 text-muted fw-bold"><?= $idx + 1 ?></td>
                                            <td class="fw-bold font-outfit text-dark"><?= htmlspecialchars($item['concepto']) ?></td>
                                            <td class="fw-bold text-danger">$<?= number_format($item['monto'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_transferencia'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4 d-flex justify-content-between">
                    <span class="fs-7 text-muted">Transferencias: <strong class="text-dark"><?= count($detalles['prestamos_actividades'] ?? []) ?></strong></span>
                    <span class="fs-6 font-outfit fw-bold text-dark">Total Girado a Actividades: $<?= number_format($egr['prestamos_actividades'], 0, ',', '.') ?> COP</span>
                </div>
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
                    <th class="ps-4">Reunión</th>
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
                                Reunión R<?= $c['numero_quincena'] ?>
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
