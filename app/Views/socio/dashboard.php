<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-gear text-primary"></i>
            Mi Estado de Cuenta y Ahorros
        </h2>
        <p class="text-muted m-0 fs-7">Bienvenido(a), <strong><?= htmlspecialchars($user['nombre_completo']) ?></strong> (C.C. <?= htmlspecialchars($user['cedula']) ?>)</p>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-md-end gap-2">
        <button type="button" class="btn btn-outline-primary rounded-pill fw-semibold btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEditarMiPerfil">
            <i class="fa-solid fa-user-pen me-1"></i>Editar Mis Datos
        </button>
        <button type="button" class="btn btn-dark rounded-pill fw-bold btn-sm px-3 shadow-sm" id="btnSimularLiquidacion">
            <i class="fa-solid fa-calculator text-warning me-1"></i>Simulador Liquidación
        </button>
    </div>
</div>

<!-- Tarjetas Principales de Métricas del Socio -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-gradient-navy text-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-white bg-opacity-10 rounded-circle text-warning fs-3">
                    <i class="fa-solid fa-piggy-bank"></i>
                </div>
                <div>
                    <span class="text-white-50 fs-7 d-block">Ahorro Obligatorio (Cuotas)</span>
                    <h3 class="font-outfit fw-bold m-0 text-white">$<?= number_format($resumen['total_cuotas'], 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-gradient-navy text-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-white bg-opacity-10 rounded-circle text-success fs-3">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <div>
                    <span class="text-white-50 fs-7 d-block">Ahorro Voluntario / Extra</span>
                    <h3 class="font-outfit fw-bold m-0 text-success">$<?= number_format($resumen['total_ahorro_extra'], 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-gradient-navy text-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-white bg-opacity-10 rounded-circle text-info fs-3">
                    <i class="fa-solid fa-vault"></i>
                </div>
                <div>
                    <span class="text-white-50 fs-7 d-block">Total Ahorrado Acumulado</span>
                    <h3 class="font-outfit fw-bold m-0 text-info">$<?= number_format($resumen['total_ahorrado'], 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-gradient-navy text-white">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-white bg-opacity-10 rounded-circle text-danger fs-3">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <span class="text-white-50 fs-7 d-block">Deuda Activa Préstamos</span>
                    <h3 class="font-outfit fw-bold m-0 text-warning">$<?= number_format($resumen['deuda_prestamos_capital'], 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Bar de Meta de Intereses ($400.000 COP) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="font-outfit fw-bold m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-bullseye text-danger"></i>
            Avance de Meta Individual de Intereses
        </h5>
        <span class="fw-bold font-outfit text-primary fs-5">
            $<?= number_format($resumen['total_interes_generado'], 0, ',', '.') ?> / $400.000 COP (<?= $resumen['porcentaje_meta'] ?>%)
        </span>
    </div>
    <p class="text-muted fs-7 mb-3">Compromiso anual de abonar al menos $400.000 COP en intereses por concepto de préstamos personales o generados.</p>
    <div class="progress rounded-pill overflow-hidden" style="height: 18px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-gradient-gold text-dark fw-bold font-outfit fs-7" 
             role="progressbar" 
             style="width: <?= $resumen['porcentaje_meta'] ?>%;">
            <?= $resumen['porcentaje_meta'] ?>%
        </div>
    </div>
</div>

<!-- Tabs de Historial -->
<div class="row g-4 mb-4">
    <!-- Historial de Cuotas Quincenales -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-dark text-white p-3">
                <h5 class="font-outfit fw-bold m-0"><i class="fa-solid fa-list-check me-2 text-warning"></i>Historial de Ahorros por Quincena</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light font-outfit fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Quincena</th>
                            <th>Cuota Pagada</th>
                            <th>Ahorro Extra</th>
                            <th class="text-end pe-4">Total Aportado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historialCuotas)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Aún no registras cuotas procesadas en el sistema.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historialCuotas as $c): 
                                $totalCuotaSocio = ($c['cuota_pagada'] ? (float)$c['monto_cuota'] : 0) + (float)$c['monto_ahorro_extra'];
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold font-outfit">Q<?= $c['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($c['fecha_reunion'])) ?></div>
                                        <small class="text-muted"><?= $c['tipo_evento_extra'] !== 'NINGUNO' ? $c['tipo_evento_extra'] : 'Regular' ?></small>
                                    </td>
                                    <td>
                                        <?php if ($c['cuota_pagada']): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>$<?= number_format($c['monto_cuota'], 0, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Autopréstamo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-primary fw-semibold">$<?= number_format($c['monto_ahorro_extra'], 0, ',', '.') ?></td>
                                    <td class="text-end pe-4 fw-bold font-outfit">$<?= number_format($totalCuotaSocio, 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mis Préstamos y Seguimiento de Cuotas -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="font-outfit fw-bold m-0"><i class="fa-solid fa-file-invoice-dollar me-2 text-info"></i>Mis Préstamos</h5>
                <span class="badge bg-secondary"><?= count($misPrestamos) ?> Total</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($misPrestamos)): ?>
                    <div class="p-4 text-center text-muted">No tienes créditos registrados.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($misPrestamos as $index => $mp): 
                            $saldoP = (float)$mp['monto_prestado'] - (float)$mp['capital_pagado'];
                            $collapseId = "collapseCuotasPrestamo_" . $mp['id'];
                            $refText = !empty($mp['nombre_referencia']) ? $mp['nombre_referencia'] : (!empty($mp['tercero_nombre']) ? $mp['tercero_nombre'] : '');
                        ?>
                            <div class="list-group-item p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div>
                                        <strong class="font-outfit fs-6 text-dark">$<?= number_format($mp['monto_prestado'], 0, ',', '.') ?> COP</strong>
                                        <?php if (!empty($refText)): ?>
                                            <span class="badge bg-light text-dark border ms-1 fw-normal"><i class="fa-solid fa-tag text-primary me-1"></i><?= htmlspecialchars($refText) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge <?= $mp['estado'] === 'PAGADO' ? 'bg-success' : 'bg-danger' ?>"><?= $mp['estado'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center text-muted fs-7 mb-2">
                                    <span>Tasa: <strong class="text-info"><?= number_format($mp['tasa_interes_mensual'], 1) ?>%</strong></span>
                                    <span>Saldo Capital: <strong class="text-dark">$<?= number_format($saldoP, 0, ',', '.') ?></strong></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted fs-8">Pagado Cap: $<?= number_format($mp['capital_pagado'], 0, ',', '.') ?> | Int: $<?= number_format($mp['interes_pagado'], 0, ',', '.') ?></small>
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                                        <i class="fa-solid fa-list-check me-1"></i>Ver Cuotas (<?= count($mp['cuotas']) ?>)
                                    </button>
                                </div>

                                <!-- Detalle Colapsable de Cuotas de este Préstamo -->
                                <div class="collapse mt-3" id="<?= $collapseId ?>">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <h6 class="font-outfit fw-bold text-primary fs-7 mb-2">
                                            <i class="fa-solid fa-receipt me-1"></i>Historial de Cuotas / Pagos Realizados:
                                        </h6>
                                        <?php if (empty($mp['cuotas'])): ?>
                                            <p class="text-muted fs-8 mb-0">Aún no registras cuotas o abonos para este crédito.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered align-middle bg-white fs-8 mb-0">
                                                    <thead class="table-dark font-outfit">
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Capital</th>
                                                            <th>Interés</th>
                                                            <th>Recibido Por</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($mp['cuotas'] as $q): ?>
                                                            <tr>
                                                                <td><?= date('d/m/Y H:i', strtotime($q['fecha_abono'])) ?></td>
                                                                <td class="text-success fw-bold">$<?= number_format($q['monto_capital_pagado'], 0, ',', '.') ?></td>
                                                                <td class="text-warning fw-bold">$<?= number_format($q['monto_interes_pagado'], 0, ',', '.') ?></td>
                                                                <td class="fw-semibold text-dark"><i class="fa-solid fa-user-check text-primary me-1"></i><?= htmlspecialchars($q['registrado_por_nombre'] ?? 'Directiva') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sección 2: Mis Actividades Comunitarias y Deudas (Tamales) -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
        <h5 class="font-outfit fw-bold m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-utensils text-info"></i>
            Mis Actividades Comunitarias y Deudas
        </h5>
        <div>
            <?php if ($totalDeudaActividades > 0): ?>
                <span class="badge bg-danger rounded-pill px-3 py-2 fs-7">
                    <i class="fa-solid fa-exclamation-triangle me-1"></i>Deuda Pendiente: $<?= number_format($totalDeudaActividades, 0, ',', '.') ?> COP
                </span>
            <?php else: ?>
                <span class="badge bg-success rounded-pill px-3 py-2 fs-7">
                    <i class="fa-solid fa-circle-check me-1"></i>Sin Deudas de Actividades
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Actividad / Descripción</th>
                    <th>Fecha Evento</th>
                    <th>Cuota Asignada</th>
                    <th>Monto Pagado</th>
                    <th>Saldo Pendiente</th>
                    <th>Ganancia Asignada</th>
                    <th class="text-end pe-4">Estado Pago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($misActividades)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No tienes actividades comunitarias asignadas o registradas aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($misActividades as $act): 
                        $cuota = (float)$act['cuota_asignada'];
                        $pagado = (float)$act['monto_pagado'];
                        $saldoAct = $cuota - $pagado;
                        $ganancia = (float)$act['ganancia_asignada'];
                        $isPagado = ($act['estado_pago'] === 'PAGADO' || $saldoAct <= 0);
                        $abonosSocio = $act['abonos'] ?? [];
                    ?>
                        <tr class="align-middle">
                            <td class="ps-4">
                                <div class="fw-bold font-outfit text-dark"><?= htmlspecialchars($act['nombre_actividad']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($act['descripcion'] ?: 'Venta extraordinaria comunitaria') ?></small>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-success rounded-pill py-0 px-2 mt-1" data-bs-toggle="collapse" data-bs-target="#abonos_socio_act_<?= $act['id'] ?>">
                                        <i class="fa-solid fa-receipt me-1"></i>Ver mis abonados (<?= count($abonosSocio) ?>)
                                    </button>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-dark border"><i class="fa-solid fa-calendar-day me-1"></i><?= date('d/m/Y', strtotime($act['fecha_actividad'])) ?></span></td>
                            <td class="fw-bold text-dark font-outfit">$<?= number_format($cuota, 0, ',', '.') ?></td>
                            <td class="text-success fw-bold">$<?= number_format($pagado, 0, ',', '.') ?></td>
                            <td>
                                <?php if ($saldoAct > 0): ?>
                                    <span class="badge bg-danger fs-7">$<?= number_format($saldoAct, 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="text-muted fs-7">$0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-info fw-semibold">$<?= number_format($ganancia, 0, ',', '.') ?></td>
                            <td class="text-end pe-4">
                                <?php if ($isPagado): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-1"><i class="fa-solid fa-check me-1"></i>Al Día</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1"><i class="fa-solid fa-clock me-1"></i>Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="collapse" id="abonos_socio_act_<?= $act['id'] ?>">
                            <td colspan="7" class="bg-light p-3 border-bottom">
                                <div class="fw-bold text-success mb-2 fs-7"><i class="fa-solid fa-clock-rotate-left me-1"></i>Desglose de Mis Pagos Abonados - <?= htmlspecialchars($act['nombre_actividad']) ?></div>
                                <?php if (empty($abonosSocio)): ?>
                                    <div class="text-muted small py-1"><i class="fa-solid fa-info-circle me-1"></i>Aún no has realizado abonos para esta actividad.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered bg-white fs-7 mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Fecha y Hora</th>
                                                    <th>Valor Abonado</th>
                                                    <th>Recibido / Registrado Por</th>
                                                    <th>Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($abonosSocio as $ab): ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y h:i A', strtotime($ab['fecha_abono'])) ?></td>
                                                        <td class="fw-bold text-success">$<?= number_format($ab['monto_abono'], 0, ',', '.') ?> COP</td>
                                                        <td><?= htmlspecialchars($ab['registrado_por_nombre'] ?? 'Secretaría') ?></td>
                                                        <td><?= htmlspecialchars($ab['observacion'] ?: '-') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Simulador de Liquidación Anual -->
<div class="modal fade" id="modalLiquidacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-gradient-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold text-warning"><i class="fa-solid fa-calculator me-2"></i>Simulador de Liquidación de Fin de Año (Diciembre)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted fs-7 d-block">Gran Total Ahorrado por la Natillera:</span>
                            <h4 id="lblGranTotalAhorro" class="font-outfit fw-bold text-primary m-0">$0</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted fs-7 d-block">Total Ganancias por Intereses Generados:</span>
                            <h4 id="lblTotalUtilidades" class="font-outfit fw-bold text-success m-0">$0</h4>
                        </div>
                    </div>
                </div>

                <h6 class="font-outfit fw-bold mb-3"><i class="fa-solid fa-users text-primary me-2"></i>Reparto Proporcional por Socio:</h6>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-sm table-striped align-middle fs-7 mb-0">
                        <thead class="bg-dark text-white sticky-top">
                            <tr>
                                <th class="ps-3">Socio</th>
                                <th>Cédula</th>
                                <th>Total Ahorrado</th>
                                <th>% Participación</th>
                                <th>Ganancia Intereses</th>
                                <th class="pe-3 text-end">Total a Recibir</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyLiquidacion">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnSimular = document.getElementById('btnSimularLiquidacion');
    if (btnSimular) {
        btnSimular.addEventListener('click', () => {
            Swal.showLoading();
            fetch('/socio/liquidacion-anual')
            .then(res => res.json())
            .then(data => {
                Swal.close();
                document.getElementById('lblGranTotalAhorro').innerText = '$' + new Intl.NumberFormat('es-CO').format(data.gran_total_ahorro);
                document.getElementById('lblTotalUtilidades').innerText = '$' + new Intl.NumberFormat('es-CO').format(data.total_utilidades_intereses);

                const tbody = document.getElementById('tbodyLiquidacion');
                tbody.innerHTML = '';

                data.liquidaciones.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="ps-3 fw-bold">${item.nombre_completo}</td>
                        <td>${item.cedula}</td>
                        <td class="text-primary">$${new Intl.NumberFormat('es-CO').format(item.ahorro_socio)}</td>
                        <td><span class="badge bg-secondary">${item.porcentaje}%</span></td>
                        <td class="text-success fw-bold">+$${new Intl.NumberFormat('es-CO').format(item.ganancia_utilidad)}</td>
                        <td class="pe-3 text-end fw-bold font-outfit text-dark fs-6">$${new Intl.NumberFormat('es-CO').format(item.total_a_recibir)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                const modal = new bootstrap.Modal(document.getElementById('modalLiquidacion'));
                modal.show();
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'No se pudo cargar la simulación de liquidación.', 'error');
            });
        });
    }
});
</script>
