<link rel="stylesheet" href="/css/pages/gestion-dinero.css">

<div class="gd-head">
    <div class="gd-head__main">
        <span class="gd-head__icon" aria-hidden="true"><i class="fa-solid fa-file-invoice-dollar"></i></span>
        <div class="gd-head__text">
            <h2 class="nt-page-title">Arqueo de Caja y Cierre Financiero por Reunión</h2>
            <p class="nt-page-sub">Liquidación detallada de ingresos (+), egresos (-) y saldo de caja para cada reunión.</p>
        </div>
    </div>
    <div class="gd-head__actions">
        <form method="GET" action="/admin/cierre-reunion" class="gd-select-inline">
            <label for="selectReunionCierre">Reunión:</label>
            <select name="reunion_id" id="selectReunionCierre" class="form-select form-select-sm" onchange="this.form.submit()">
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
    <div class="nt-hero gd-hero">
        <div class="min-w-0">
            <span class="gd-hero-chip gd-hero-chip--light">
                <i class="fa-solid fa-calendar-day"></i>Reunión R<?= $rInfo['numero_quincena'] ?>
            </span>
            <h3 class="gd-hero__date">
                Fecha de Reunión: <?= date('d/m/Y', strtotime($rInfo['fecha_reunion'])) ?>
            </h3>
            <small class="gd-hero__meta">Valor Cuota Base: $<?= number_format($rInfo['valor_cuota_base'], 0, ',', '.') ?> COP | Evento: <?= htmlspecialchars($rInfo['tipo_evento_extra']) ?></small>
        </div>
        <div class="gd-hero__status">
            <?php if ($hasLlamado): ?>
                <span class="gd-hero-chip">
                    <i class="fa-solid fa-clipboard-check"></i>Llamado a Lista Registrado
                </span>
            <?php endif; ?>

            <?php if ($isCierreRealizado): ?>
                <span class="gd-hero-chip gd-hero-chip--light"><i class="fa-solid fa-lock"></i>CIERRE FINANCIERO REALIZADO</span>
                <?php if ($cierreExistente): ?>
                    <small>Cerrado el <?= date('d/m/Y g:i a', strtotime($cierreExistente['fecha_cierre'])) ?> por <?= htmlspecialchars($cierreExistente['cerrado_por_nombre']) ?></small>
                <?php endif; ?>
            <?php else: ?>
                <span class="gd-hero-chip gd-hero-chip--gold"><i class="fa-solid fa-unlock"></i>PENDIENTE DE CIERRE FINANCIERO</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Desglose Ingresos vs Egresos -->
    <div class="row g-4 mb-4">
        <!-- Columna 1: INGRESOS (+) -->
        <div class="col-12 col-lg-6">
            <div class="gd-surface gd-ledger gd-ledger--in mb-0">
                <div class="gd-ledger__head">
                    <h5 class="gd-ledger__title"><i class="fa-solid fa-arrow-down-long"></i>INGRESOS A CAJA (+)</h5>
                    <span class="gd-ledger__sum">$<?= number_format($ing['total'], 0, ',', '.') ?><small>COP</small></span>
                </div>
                <ul class="gd-lines">
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Cuotas Base Ahorradas ($40.000)</strong>
                            <small class="gd-line__note">Aporte directo de socios para liquidación</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount">$<?= number_format($ing['cuotas_base'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleCuotasBase" title="Ver desglose por socios">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Ahorro Voluntario Extra</strong>
                            <small class="gd-line__note">Ahorro adicional elegido por socios</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-pos">$<?= number_format($ing['ahorro_extra'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleAhorroExtra" title="Ver desglose por socios">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Abonos a Capital de Préstamos</strong>
                            <small class="gd-line__note">Recuperación de principal prestado</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-pine">$<?= number_format($ing['abono_capital'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleAbonoCapital" title="Ver de qué personas proviene el capital">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Intereses Cobrados de Préstamos</strong>
                            <small class="gd-line__note">Ganancia bruta por financiamiento</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-gold">$<?= number_format($ing['intereses_prestamos'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleInteresesPrestamos" title="Ver a qué personas se le cobró interés">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Devoluciones Recibidas de Caja de Actividades</strong>
                            <small class="gd-line__note">Reembolso de capital o aporte de utilidades de eventos</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-pos">$<?= number_format($ing['devoluciones_actividades'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleDevolucionesActividades" title="Ver desglose de devoluciones">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Inyecciones de Capital Ingresadas</strong>
                            <small class="gd-line__note">Aportes de inversión inicial</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-pos">$<?= number_format($ing['inyecciones'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleInyecciones" title="Ver inversionistas">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Columna 2: EGRESOS (-) -->
        <div class="col-12 col-lg-6">
            <div class="gd-surface gd-ledger gd-ledger--out mb-0">
                <div class="gd-ledger__head">
                    <h5 class="gd-ledger__title"><i class="fa-solid fa-arrow-up-long"></i>EGRESOS DE CAJA (-)</h5>
                    <span class="gd-ledger__sum">$<?= number_format($egr['total'], 0, ',', '.') ?><small>COP</small></span>
                </div>
                <ul class="gd-lines">
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Préstamos Otorgados / Desembolsados</strong>
                            <small class="gd-line__note">Capital girado a socios en la reunión</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-neg">$<?= number_format($egr['prestamos_otorgados'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetallePrestamosOtorgados" title="Ver socios desembolsados">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Inyecciones Devueltas / Retiradas</strong>
                            <small class="gd-line__note">Retiro de inyecciones tras 6 meses</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-neg">$<?= number_format($egr['inyecciones_devueltas'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetalleInyeccionesDevueltas" title="Ver inyecciones devueltas">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                    <li class="gd-line">
                        <div class="gd-line__text">
                            <strong class="gd-line__title">Préstamos Sin Interés a Caja de Actividades</strong>
                            <small class="gd-line__note">Capital girado para financiamiento inicial de eventos</small>
                        </div>
                        <div class="gd-line__end">
                            <span class="gd-line__amount gd-gold">$<?= number_format($egr['prestamos_actividades'], 0, ',', '.') ?></span>
                            <button type="button" class="gd-see" data-bs-toggle="modal" data-bs-target="#modalDetallePrestamosActividades" title="Ver transferencias a actividades">
                                <i class="fa-solid fa-eye"></i>Ver
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Balance Final y Acción de Cierre -->
    <div class="gd-balance">
        <div class="gd-balance__grid">
            <div class="gd-bal">
                <span class="gd-bal__label">Saldo Inicial de Caja (Reunión Anterior)</span>
                <h4 class="gd-bal__value">
                    $<?= number_format($resumen['saldo_inicial_caja'], 0, ',', '.') ?> <small>COP</small>
                </h4>
                <small class="gd-bal__note">Acumulado heredado de cierres previos</small>
            </div>
            <div class="gd-bal">
                <span class="gd-bal__label">Flujo Neto de Reunión R<?= $rInfo['numero_quincena'] ?></span>
                <h3 class="gd-bal__value <?= ($resumen['saldo_neto_reunion'] >= 0) ? 'gd-pos' : 'gd-neg' ?>">
                    $<?= number_format($resumen['saldo_neto_reunion'], 0, ',', '.') ?> <small>COP</small>
                </h3>
                <small class="gd-bal__note">Ingresos ($<?= number_format($ing['total'], 0, ',', '.') ?>) - Egresos ($<?= number_format($egr['total'], 0, ',', '.') ?>)</small>
            </div>
            <div class="gd-bal gd-bal--final">
                <span class="gd-bal__label">Saldo Acumulado Final en Caja</span>
                <h3 class="gd-bal__value">
                    $<?= number_format($resumen['saldo_acumulado_caja'], 0, ',', '.') ?> <small>COP</small>
                </h3>
                <small class="gd-bal__note">Saldo Inicial + Flujo Neto de esta reunión</small>
            </div>
            <div class="gd-balance__cta">
                <?php if ($isCierreRealizado): ?>
                    <button type="button" class="btn btn-success fw-bold px-4" disabled>
                        <i class="fa-solid fa-lock me-2"></i>Cierre Financiero Realizado
                    </button>
                <?php else: ?>
                    <form action="/admin/cierre-reunion/cerrar" method="POST" onsubmit="return confirm('¿Confirmas realizar el CIERRE FINANCIERO de la Reunión R<?= $rInfo['numero_quincena'] ?>? Esta acción consolidará la caja e inmovilizará el arqueo.');">
                        <input type="hidden" name="reunion_id" value="<?= $rInfo['id'] ?>">
                        <button type="submit" class="btn btn-warning fw-bold px-4">
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
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-dark">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-coins"></i>
                        Desglose de Cuotas Base Ahorradas ($40.000) - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
                                    <th>Socio Ahorrador</th>
                                    <th>Cédula</th>
                                    <th>Monto Cuota Base</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['cuotas_base'])): ?>
                                    <tr>
                                        <td colspan="5" class="gd-empty">No hay pagos de cuota base registrados en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['cuotas_base'] as $idx => $item): ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold gd-pos">$40.000</td>
                                            <td class="text-muted"><?= !empty($item['created_at']) ? date('d/m/Y g:i a', strtotime($item['created_at'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Total Socios Que Pagaron: <strong><?= count($detalles['cuotas_base'] ?? []) ?></strong></span>
                    <span class="gd-total gd-pos">Total Cuotas Base: $<?= number_format($ing['cuotas_base'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE AHORRO EXTRA -->
    <div class="modal fade" id="modalDetalleAhorroExtra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-dark">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-piggy-bank"></i>
                        Desglose Ahorro Voluntario Extra - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
                                    <th>Socio Ahorrador</th>
                                    <th>Cédula</th>
                                    <th>Ahorro Extra</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['ahorro_extra'])): ?>
                                    <tr>
                                        <td colspan="5" class="gd-empty">No hay aportes de ahorro extra en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['ahorro_extra'] as $idx => $item): ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold gd-pos">$<?= number_format($item['monto_ahorro_extra'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= !empty($item['created_at']) ? date('d/m/Y g:i a', strtotime($item['created_at'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Registros: <strong><?= count($detalles['ahorro_extra'] ?? []) ?></strong></span>
                    <span class="gd-total gd-pos">Total Ahorro Extra: $<?= number_format($ing['ahorro_extra'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE ABONO CAPITAL -->
    <div class="modal fade" id="modalDetalleAbonoCapital" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-primary">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        Desglose Abonos a Capital de Préstamos - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
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
                                        <td colspan="6" class="gd-empty">No se recibieron abonos a capital de préstamos en esta quincena.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['abono_capital'] as $idx => $item): 
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Directo');
                                    ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="gd-sub">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td>
                                                <span class="gd-ref"><i class="fa-solid fa-bookmark"></i><?= htmlspecialchars($ref) ?></span>
                                                <small class="gd-sub">Original: $<?= number_format($item['monto_prestado'], 0, ',', '.') ?></small>
                                            </td>
                                            <td class="fw-bold gd-pine">$<?= number_format($item['monto_capital_pagado'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_abono'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Abonos Registrados: <strong><?= count($detalles['abono_capital'] ?? []) ?></strong></span>
                    <span class="gd-total gd-pine">Total Capital Recuperado: $<?= number_format($ing['abono_capital'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INTERESES COBRADOS -->
    <div class="modal fade" id="modalDetalleInteresesPrestamos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-warning">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-percent"></i>
                        Desglose Intereses Cobrados de Préstamos - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
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
                                        <td colspan="6" class="gd-empty">No se cobraron intereses de préstamos en esta quincena.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['intereses_prestamos'] as $idx => $item): 
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Directo');
                                    ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="gd-sub">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info me-1"><?= number_format($item['tasa_interes_mensual'], 1) ?>% mensual</span>
                                                <small class="gd-sub"><?= htmlspecialchars($ref) ?></small>
                                            </td>
                                            <td class="fw-bold font-outfit gd-gold gd-num">$<?= number_format($item['monto_interes_pagado'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_abono'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Abonos con Interés: <strong><?= count($detalles['intereses_prestamos'] ?? []) ?></strong></span>
                    <span class="gd-total ">Total Intereses Cobrados: $<?= number_format($ing['intereses_prestamos'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE DEVOLUCIONES ACTIVIDADES -->
    <div class="modal fade" id="modalDetalleDevolucionesActividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-success">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        Desglose Devoluciones de Caja de Actividades - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
                                    <th>Concepto / Descripción</th>
                                    <th>Monto Devuelto</th>
                                    <th>Fecha Transferencia</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['devoluciones_actividades'])): ?>
                                    <tr>
                                        <td colspan="5" class="gd-empty">No hay devoluciones registradas desde la caja de actividades en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['devoluciones_actividades'] as $idx => $item): ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td class="fw-bold text-dark font-outfit"><?= htmlspecialchars($item['concepto']) ?></td>
                                            <td class="fw-bold gd-pos">$<?= number_format($item['monto'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_transferencia'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Registros: <strong><?= count($detalles['devoluciones_actividades'] ?? []) ?></strong></span>
                    <span class="gd-total gd-pos">Total Devoluciones: $<?= number_format($ing['devoluciones_actividades'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INYECCIONES INGRESADAS -->
    <div class="modal fade" id="modalDetalleInyecciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-success">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-vault"></i>
                        Desglose Inyecciones de Capital Ingresadas - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
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
                                        <td colspan="6" class="gd-empty">No se ingresaron inyecciones de capital en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['inyecciones'] as $idx => $item): ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></td>
                                            <td><?= htmlspecialchars($item['socio_cedula']) ?></td>
                                            <td class="fw-bold gd-pos">$<?= number_format($item['monto_inyectado'], 0, ',', '.') ?></td>
                                            <td><span class="badge bg-info"><?= number_format($item['tasa_interes_mensual_pactada'], 1) ?>% / mes</span></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_inyeccion'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Inversionistas: <strong><?= count($detalles['inyecciones'] ?? []) ?></strong></span>
                    <span class="gd-total gd-pos">Total Inyectado: $<?= number_format($ing['inyecciones'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE PRESTAMOS OTORGADOS (EGRESOS) -->
    <div class="modal fade" id="modalDetallePrestamosOtorgados" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-danger">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                        Desglose Préstamos Otorgados / Desembolsados - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
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
                                        <td colspan="6" class="gd-empty">No se otorgaron ni desembolsaron préstamos en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['prestamos_otorgados'] as $idx => $item): 
                                        $montoP = $item['monto_entregado'] ?? $item['monto_prestado'];
                                        $ref = !empty($item['nombre_referencia']) ? $item['nombre_referencia'] : ($item['tipo_prestamo'] ?? 'Préstamo Directo');
                                    ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="gd-sub">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td class="fw-bold gd-neg font-outfit">$<?= number_format($montoP, 0, ',', '.') ?></td>
                                            <td><span class="badge bg-info"><?= number_format($item['tasa_interes_mensual'] ?? 10, 1) ?>%</span></td>
                                            <td><span class="gd-ref"><?= htmlspecialchars($ref) ?></span></td>
                                            <td class="text-muted"><?= !empty($item['fecha_entrega']) ? date('d/m/Y g:i a', strtotime($item['fecha_entrega'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Préstamos Girados: <strong><?= count($detalles['prestamos_otorgados'] ?? []) ?></strong></span>
                    <span class="gd-total gd-neg">Total Desembolsado: $<?= number_format($egr['prestamos_otorgados'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE INYECCIONES DEVUELTAS (EGRESOS) -->
    <div class="modal fade" id="modalDetalleInyeccionesDevueltas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-danger">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-arrow-up-from-ground-water"></i>
                        Desglose Inyecciones Devueltas / Retiradas - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
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
                                        <td colspan="6" class="gd-empty">No se devolvieron inyecciones de capital en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['inyecciones_devueltas'] as $idx => $item): 
                                        $totRet = (float)$item['monto_inyectado'] + (float)$item['monto_rendimiento_generado'];
                                    ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td>
                                                <div class="gd-name"><?= htmlspecialchars($item['socio_nombre']) ?></div>
                                                <small class="gd-sub">C.C. <?= htmlspecialchars($item['socio_cedula']) ?></small>
                                            </td>
                                            <td class="fw-bold text-dark">$<?= number_format($item['monto_inyectado'], 0, ',', '.') ?></td>
                                            <td class="fw-bold gd-pos">$<?= number_format($item['monto_rendimiento_generado'], 0, ',', '.') ?></td>
                                            <td class="fw-bold gd-neg font-outfit">$<?= number_format($totRet, 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= !empty($item['fecha_retiro']) ? date('d/m/Y g:i a', strtotime($item['fecha_retiro'])) : '---' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Retiros: <strong><?= count($detalles['inyecciones_devueltas'] ?? []) ?></strong></span>
                    <span class="gd-total gd-neg">Total Devuelto: $<?= number_format($egr['inyecciones_devueltas'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: DETALLE PRESTAMOS A CAJA DE ACTIVIDADES (EGRESOS) -->
    <div class="modal fade" id="modalDetallePrestamosActividades" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content gd-modal">
                <div class="modal-header gd-mh gd-tone-warning">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-bullhorn"></i>
                        Desglose Préstamos a Caja de Actividades - Reunión R<?= $rInfo['numero_quincena'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle gd-table gd-keycol">
                            <thead class="gd-thead">
                                <tr>
                                    <th>#</th>
                                    <th>Concepto / Destino</th>
                                    <th>Monto Girado</th>
                                    <th>Fecha Transferencia</th>
                                    <th>Registrado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detalles['prestamos_actividades'])): ?>
                                    <tr>
                                        <td colspan="5" class="gd-empty">No se giró capital a la caja de actividades en esta reunión.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detalles['prestamos_actividades'] as $idx => $item): ?>
                                        <tr>
                                            <td class="gd-idx"><?= $idx + 1 ?></td>
                                            <td class="gd-name"><?= htmlspecialchars($item['concepto']) ?></td>
                                            <td class="fw-bold gd-neg">$<?= number_format($item['monto'], 0, ',', '.') ?></td>
                                            <td class="text-muted"><?= date('d/m/Y g:i a', strtotime($item['fecha_transferencia'])) ?></td>
                                            <td class="text-muted fs-8"><?= htmlspecialchars($item['registrado_por_nombre'] ?? 'Sistema') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer gd-mf">
                    <span class="gd-count">Transferencias: <strong><?= count($detalles['prestamos_actividades'] ?? []) ?></strong></span>
                    <span class="gd-total ">Total Girado a Actividades: $<?= number_format($egr['prestamos_actividades'], 0, ',', '.') ?> COP</span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Historial de Cierres de Reunión Realizados -->
<div class="gd-surface">
    <div class="gd-surface__head">
        <i class="fa-solid fa-clock-rotate-left"></i>Historial de Cierres Financieros de Reunión
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle gd-table gd-cards">
            <thead>
                <tr>
                    <th>Reunión</th>
                    <th>Fecha Cierre</th>
                    <th class="text-end">Total Ingresos</th>
                    <th class="text-end">Total Egresos</th>
                    <th class="text-end">Flujo Neto Reunión</th>
                    <th class="text-end">Saldo Acumulado Caja</th>
                    <th>Cerrado Por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($todosCierres)): ?>
                    <tr>
                        <td colspan="7" class="gd-empty">No se ha realizado ningún cierre financiero de reunión aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($todosCierres as $c): ?>
                        <tr>
                            <td class="gd-cell-title">
                                <div class="gd-name">Reunión R<?= $c['numero_quincena'] ?></div>
                                <small class="gd-sub"><?= date('d/m/Y', strtotime($c['fecha_reunion'])) ?></small>
                            </td>
                            <td data-label="Fecha cierre"><?= date('d/m/Y g:i a', strtotime($c['fecha_cierre'])) ?></td>
                            <td class="gd-num fw-bold gd-pos" data-label="Ingresos">$<?= number_format($c['total_ingresos_general'], 0, ',', '.') ?></td>
                            <td class="gd-num fw-bold gd-neg" data-label="Egresos">$<?= number_format($c['total_egresos_general'], 0, ',', '.') ?></td>
                            <td class="gd-num fw-bold font-outfit <?= ($c['saldo_neto_reunion'] >= 0) ? 'gd-pos' : 'gd-neg' ?>" data-label="Flujo neto">$<?= number_format($c['saldo_neto_reunion'], 0, ',', '.') ?></td>
                            <td class="gd-num fw-bold font-outfit gd-pine" data-label="Saldo acumulado">$<?= number_format($c['saldo_acumulado_caja'], 0, ',', '.') ?></td>
                            <td data-label="Cerrado por"><?= htmlspecialchars($c['cerrado_por_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
