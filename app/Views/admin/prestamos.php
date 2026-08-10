<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-hand-holding-dollar text-warning"></i>
            Gestión de Préstamos e Intereses
        </h2>
        <p class="text-muted m-0 fs-7">Control directo a socios, abonos a capital/interés e historial de cuotas.</p>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-md-end gap-2">
        <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
            <button type="button" class="btn btn-outline-dark rounded-pill fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#modalTopeCredit">
                <i class="fa-solid fa-sliders me-1"></i>Ajustar Tope Socio
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-warning rounded-pill fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoPrestamo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo Préstamo
        </button>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda en Préstamos -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarPrestamo" class="form-control border-start-0 ps-0" placeholder="Buscar por socio, cédula, referencia o reunión (ej: R1, R2, Juan)...">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select id="filtrarEstadoPrestamo" class="form-select border-secondary text-dark fw-semibold">
                    <option value="">-- Todos los Préstamos --</option>
                    <option value="ACTIVO">Solo Activos</option>
                    <option value="PAGADO">Solo Pagados</option>
                    <option value="AUTOPRESTAMO">Solo Autopréstamos</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Préstamos -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Socio Deudor</th>
                    <th>Reunión</th>
                    <th>Referencia / Alias</th>
                    <th>Monto Prestado</th>
                    <th>Tasa %</th>
                    <th>Capital Pagado</th>
                    <th>Interés Pagado</th>
                    <th>Estado</th>
                    <th class="text-end pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamos)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No hay préstamos registrados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $p): 
                        $saldoCapital = (float)$p['monto_prestado'] - (float)$p['total_capital_pagado'];
                        $refText = !empty($p['nombre_referencia']) ? $p['nombre_referencia'] : (!empty($p['tercero_nombre']) ? $p['tercero_nombre'] : '');
                        $numR = !empty($p['numero_quincena']) ? 'R' . $p['numero_quincena'] : '';
                    ?>
                        <tr class="prestamo-row" 
                            data-search="<?= strtolower(htmlspecialchars($p['deudor_nombre'] . ' ' . $p['deudor_cedula'] . ' ' . $refText . ' ' . $numR . ' ' . ($p['es_autoprestamo'] ? 'autoprestamo' : ''))) ?>"
                            data-estado="<?= $p['estado'] ?>"
                            data-tipo="<?= $p['tipo_prestamo'] ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($p['deudor_nombre']) ?></div>
                                <small class="text-muted">C.C. <?= htmlspecialchars($p['deudor_cedula']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($p['numero_quincena'])): ?>
                                    <span class="badge bg-primary font-outfit fs-7">R<?= $p['numero_quincena'] ?></span>
                                    <small class="d-block text-muted fs-8"><?= date('d/m/Y', strtotime($p['fecha_reunion'])) ?></small>
                                <?php else: ?>
                                    <span class="text-muted fs-8">General / Sin R</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($refText)): ?>
                                    <span class="badge bg-light text-dark border fw-medium px-2 py-1">
                                        <i class="fa-solid fa-bookmark text-primary me-1"></i><?= htmlspecialchars($refText) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fs-7">---</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark font-outfit">$<?= number_format($p['monto_prestado'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-info text-dark"><?= number_format($p['tasa_interes_mensual'], 1) ?>%</span></td>
                            <td class="text-success fw-semibold">$<?= number_format($p['total_capital_pagado'], 0, ',', '.') ?></td>
                            <td class="text-warning fw-semibold">$<?= number_format($p['total_interes_pagado'], 0, ',', '.') ?></td>
                            <td>
                                <?php if ($p['anulado_sin_interes']): ?>
                                    <span class="badge bg-secondary">Anulado 24h</span>
                                <?php elseif ($p['estado'] === 'PAGADO'): ?>
                                    <span class="badge bg-success">Pagado ✔</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Activo (Bal: $<?= number_format($saldoCapital, 0, ',', '.') ?>)</span>
                                <?php endif; ?>
                                <?php if ($p['es_autoprestamo']): ?>
                                    <small class="d-block text-warning fw-bold fs-8 mt-1"><i class="fa-solid fa-bolt me-1"></i>Autopréstamo</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if ($p['estado'] === 'ACTIVO' && !$p['anulado_sin_interes']): ?>
                                        <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 btnAbonar" 
                                                data-id="<?= $p['id'] ?>" 
                                                data-deudor="<?= htmlspecialchars($p['deudor_nombre']) ?>" 
                                                data-saldo="<?= $saldoCapital ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalAbono">
                                            <i class="fa-solid fa-coins me-1"></i>Abonar
                                        </button>
                                    <?php endif; ?>

                                    <!-- Ver / Editar Cuotas -->
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-1 btnVerCuotas"
                                            data-id="<?= $p['id'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalHistorialCuotas">
                                        <i class="fa-solid fa-list-check me-1"></i>Cuotas
                                    </button>

                                    <!-- Firma Digital y Foto Evidencia -->
                                    <?php $tieneFirmaFoto = !empty($p['tiene_firma_foto']) && $p['tiene_firma_foto'] > 0; ?>
                                    <a href="/admin/entregas?tipo=PRESTAMO&socio_id=<?= $p['socio_deudor_id'] ?>&monto=<?= $p['monto_prestado'] ?>&reunion_id=<?= $p['reunion_id'] ?? '' ?>" 
                                       class="btn btn-xs <?= $tieneFirmaFoto ? 'btn-warning text-dark font-outfit fw-bold shadow-sm' : 'btn-outline-warning text-dark' ?> rounded-pill px-2 py-1"
                                       title="<?= $tieneFirmaFoto ? 'Firma/Foto Registrada - Click para ver/editar en Módulo Entregas' : 'Registrar Firma Digital y Foto Evidencia del Desembolso' ?>">
                                        <i class="fa-solid <?= $tieneFirmaFoto ? 'fa-circle-check text-dark' : 'fa-signature' ?> me-1"></i>Firma/Foto<?= $tieneFirmaFoto ? ' ✔' : '' ?>
                                    </a>

                                    <!-- Editar Préstamo (Presidente / Sec General) -->
                                    <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 btnEditarPrestamo"
                                                data-id="<?= $p['id'] ?>"
                                                data-deudor-id="<?= $p['socio_deudor_id'] ?>"
                                                data-reunion-id="<?= $p['reunion_id'] ?? '' ?>"
                                                data-monto="<?= $p['monto_prestado'] ?>"
                                                data-tasa="<?= $p['tasa_interes_mensual'] ?>"
                                                data-ref="<?= htmlspecialchars($refText) ?>"
                                                data-estado="<?= $p['estado'] ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalEditarPrestamo">
                                            <i class="fa-solid fa-pen me-1"></i>Editar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nuevo Préstamo -->
<div class="modal fade" id="modalNuevoPrestamo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Registrar Nuevo Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/prestamos/guardar" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="socio_deudor_id" class="form-label fw-semibold fs-7">Socio Deudor</label>
                        <select name="socio_deudor_id" id="socio_deudor_id" class="form-select border-primary" required>
                            <option value="">-- Seleccionar Socio --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nombre_completo']) ?> (Tope: $<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reunion_id_nuevo" class="form-label fw-semibold fs-7">Reunión Asociada (Opcional)</label>
                        <select name="reunion_id" id="reunion_id_nuevo" class="form-select">
                            <option value="">-- General / Sin Reunión Específica --</option>
                            <?php foreach ($reuniones as $r): ?>
                                <option value="<?= $r['id'] ?>">R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_referencia" class="form-label fw-semibold fs-7">Persona Referente / Nombre de Referencia (Opcional)</label>
                        <input type="text" name="nombre_referencia" id="nombre_referencia" class="form-control" placeholder="Ej: Para negocio de empanadas / Ref: Juan">
                        <small class="text-muted fs-8">Sirve como alias para identificar rápidamente el motivo o referente del préstamo.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="monto_prestado" class="form-label fw-semibold fs-7">Monto a Prestar (COP)</label>
                            <input type="text" name="monto_prestado" id="monto_prestado" class="form-control money-input fw-bold" placeholder="Ej: 500.000" required>
                        </div>
                        <div class="col-5">
                            <label for="tasa_interes_mensual" class="form-label fw-semibold fs-7">Tasa Interés (%)</label>
                            <input type="number" step="0.5" min="0" name="tasa_interes_mensual" id="tasa_interes_mensual" class="form-control fw-bold" value="10.0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Crear Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Préstamo (Presidente / Secretaria General) -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalEditarPrestamo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Editar Préstamo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/prestamos/actualizar" method="POST">
                <input type="hidden" name="prestamo_id" id="edit_prestamo_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_socio_deudor_id" class="form-label fw-semibold fs-7">Socio Deudor</label>
                        <select name="socio_deudor_id" id="edit_socio_deudor_id" class="form-select" required>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_reunion_id" class="form-label fw-semibold fs-7">Reunión Asociada</label>
                        <select name="reunion_id" id="edit_reunion_id" class="form-select">
                            <option value="">-- General / Sin Reunión --</option>
                            <?php foreach ($reuniones as $r): ?>
                                <option value="<?= $r['id'] ?>">R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nombre_referencia" class="form-label fw-semibold fs-7">Persona Referente / Alias</label>
                        <input type="text" name="nombre_referencia" id="edit_nombre_referencia" class="form-control">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="edit_monto_prestado" class="form-label fw-semibold fs-7">Monto Prestado (COP)</label>
                            <input type="text" name="monto_prestado" id="edit_monto_prestado" class="form-control money-input fw-bold" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_tasa_interes_mensual" class="form-label fw-semibold fs-7">Tasa Interés (%)</label>
                            <input type="number" step="0.5" min="0" name="tasa_interes_mensual" id="edit_tasa_interes_mensual" class="form-control fw-bold" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_estado" class="form-label fw-semibold fs-7">Estado del Préstamo</label>
                        <select name="estado" id="edit_estado" class="form-select fw-bold">
                            <option value="ACTIVO">ACTIVO (Pendiente de Pago)</option>
                            <option value="PAGADO">PAGADO (Finalizado)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Historial y Edición de Cuotas / Abonos -->
<div class="modal fade" id="modalHistorialCuotas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-navy text-white border-0 bg-gradient-navy">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-list-check me-2 text-warning"></i>Historial de Cuotas / Abonos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-dark">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <div>
                        <h6 class="m-0 fw-bold font-outfit text-primary" id="cuotas_deudor_nombre">---</h6>
                        <small class="text-muted" id="cuotas_prestamo_resumen">---</small>
                    </div>
                    <span id="cuotas_estado_badge" class="badge bg-secondary">---</span>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-dark font-outfit fs-7">
                            <tr>
                                <th>Fecha Abono</th>
                                <th>Abono Capital</th>
                                <th>Abono Interés</th>
                                <th>Registrado Por</th>
                                <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
                                    <th class="text-end">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tblCuotasBody">
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Cargando cuotas...</td>
                            </tr>
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

<!-- Modal Registrar Abono Individual -->
<div class="modal fade" id="modalAbono" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-coins me-2"></i>Registrar Abono a Préstamo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/prestamos/abono" method="POST">
                <input type="hidden" name="prestamo_id" id="abono_prestamo_id">
                <div class="modal-body p-4">
                    <p class="mb-2">Socio: <strong id="abono_deudor_nombre" class="text-primary font-outfit"></strong></p>
                    <p class="mb-3 text-muted fs-7">Saldo Pendiente Capital: <strong id="abono_saldo_capital" class="text-dark"></strong></p>

                    <div class="mb-3">
                        <label for="abono_reunion_id" class="form-label fw-semibold fs-7">Reunión Correspondiente</label>
                        <select name="reunion_id" id="abono_reunion_id" class="form-select fw-bold">
                            <option value="">-- Autodetectar por Fecha --</option>
                            <?php if (!empty($reuniones)): ?>
                                <?php foreach ($reuniones as $r): ?>
                                    <option value="<?= $r['id'] ?>">R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="text-muted fs-8">Vincula directamente este abono al arqueo de caja de la reunión seleccionada.</small>
                    </div>

                    <div class="mb-3">
                        <label for="monto_interes_pagado" class="form-label fw-semibold fs-7">Abono a Intereses (COP)</label>
                        <input type="text" name="monto_interes_pagado" id="monto_interes_pagado" class="form-control money-input text-warning fw-bold" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label for="monto_capital_pagado" class="form-label fw-semibold fs-7">Abono a Capital (COP)</label>
                        <input type="text" name="monto_capital_pagado" id="monto_capital_pagado" class="form-control money-input text-success fw-bold" placeholder="0">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Guardar Abono</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cuota Específica -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalEditarCuotaItem" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-pen me-2"></i>Editar Cuota / Abono</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/prestamos/abono/actualizar" method="POST">
                <input type="hidden" name="abono_id" id="edit_abono_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_fecha_abono" class="form-label fw-semibold fs-7">Fecha del Abono</label>
                        <input type="text" name="fecha_abono" id="edit_fecha_abono" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_monto_capital_pagado" class="form-label fw-semibold fs-7">Monto Capital (COP)</label>
                        <input type="text" name="monto_capital_pagado" id="edit_monto_capital_pagado" class="form-control money-input text-success fw-bold" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_monto_interes_pagado" class="form-label fw-semibold fs-7">Monto Interés (COP)</label>
                        <input type="text" name="monto_interes_pagado" id="edit_monto_interes_pagado" class="form-control money-input text-warning fw-bold" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Actualizar Cuota</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Excepción Tope de Crédito -->
<?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
<div class="modal fade" id="modalTopeCredit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-sliders me-2"></i>Excepción de Tope de Crédito</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/prestamos/actualizar-tope" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="socio_id_tope" class="form-label fw-semibold fs-7">Seleccionar Socio</label>
                        <select name="socio_id" id="socio_id_tope" class="form-select" required>
                            <option value="">-- Seleccionar Socio --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?> (Actual: $<?= number_format($s['tope_prestamo_personalizado'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_tope" class="form-label fw-semibold fs-7">Nuevo Tope Personalizado (COP)</label>
                        <input type="text" name="nuevo_tope" id="nuevo_tope" class="form-control money-input fw-bold" placeholder="Ej: 3.000.000" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark rounded-pill fw-bold px-4">Actualizar Tope</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const isCanEdit = <?= json_encode(in_array($userRole, ['Presidente', 'Secretaria General'])) ?>;

document.addEventListener('DOMContentLoaded', () => {
    // Abrir Modal de Abono
    const btnsAbonar = document.querySelectorAll('.btnAbonar');
    btnsAbonar.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const deudor = btn.getAttribute('data-deudor');
            const saldo = btn.getAttribute('data-saldo');
            
            document.getElementById('abono_prestamo_id').value = id;
            document.getElementById('abono_deudor_nombre').innerText = deudor;
            document.getElementById('abono_saldo_capital').innerText = '$' + new Intl.NumberFormat('es-CO').format(saldo);
        });
    });

    // Buscador y Filtro Dinámico de Préstamos
    const inputBuscarPrestamo = document.getElementById('buscarPrestamo');
    const selectFiltrarEstado = document.getElementById('filtrarEstadoPrestamo');
    const prestamoRows = document.querySelectorAll('.prestamo-row');

    function filterPrestamosTable() {
        const query = inputBuscarPrestamo ? inputBuscarPrestamo.value.toLowerCase().trim() : '';
        const estadoFilter = selectFiltrarEstado ? selectFiltrarEstado.value : '';

        prestamoRows.forEach(row => {
            const searchText = row.getAttribute('data-search') || '';
            const rowEstado = row.getAttribute('data-estado') || '';
            const rowTipo = row.getAttribute('data-tipo') || '';

            const matchQuery = (!query || searchText.includes(query));
            let matchEstado = true;

            if (estadoFilter === 'ACTIVO') {
                matchEstado = (rowEstado === 'ACTIVO');
            } else if (estadoFilter === 'PAGADO') {
                matchEstado = (rowEstado === 'PAGADO');
            } else if (estadoFilter === 'AUTOPRESTAMO') {
                matchEstado = (rowTipo === 'AUTOPRESTAMO');
            }

            row.style.display = (matchQuery && matchEstado) ? '' : 'none';
        });
    }

    if (inputBuscarPrestamo) inputBuscarPrestamo.addEventListener('input', filterPrestamosTable);
    if (selectFiltrarEstado) selectFiltrarEstado.addEventListener('change', filterPrestamosTable);

    // Abrir Modal de Editar Préstamo
    const btnsEditarPrestamo = document.querySelectorAll('.btnEditarPrestamo');
    btnsEditarPrestamo.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_prestamo_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_socio_deudor_id').value = btn.getAttribute('data-deudor-id');
            const editReunionEl = document.getElementById('edit_reunion_id');
            if (editReunionEl) editReunionEl.value = btn.getAttribute('data-reunion-id') || '';
            document.getElementById('edit_monto_prestado').value = btn.getAttribute('data-monto');
            document.getElementById('edit_tasa_interes_mensual').value = btn.getAttribute('data-tasa');
            document.getElementById('edit_nombre_referencia').value = btn.getAttribute('data-ref') || '';
            document.getElementById('edit_estado').value = btn.getAttribute('data-estado');
        });
    });

    // Cargar Historial de Cuotas via AJAX
    const btnsVerCuotas = document.querySelectorAll('.btnVerCuotas');
    btnsVerCuotas.forEach(btn => {
        btn.addEventListener('click', () => {
            const prestamoId = btn.getAttribute('data-id');
            const tbody = document.getElementById('tblCuotasBody');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando cuotas...</td></tr>';

            fetch(`/admin/prestamos/abonos-json?prestamo_id=${prestamoId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success || !data.prestamo) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Error al cargar datos.</td></tr>';
                        return;
                    }

                    const p = data.prestamo;
                    document.getElementById('cuotas_deudor_nombre').innerText = p.deudor_nombre;
                    document.getElementById('cuotas_prestamo_resumen').innerText = `Préstamo N° ${p.id} - Monto: $${new Intl.NumberFormat('es-CO').format(p.monto_prestado)} - Tasa: ${p.tasa_interes_mensual}%`;
                    document.getElementById('cuotas_estado_badge').className = `badge bg-${p.estado === 'PAGADO' ? 'success' : 'danger'}`;
                    document.getElementById('cuotas_estado_badge').innerText = p.estado;

                    if (!data.abonos || data.abonos.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Aún no se han registrado abonos a este préstamo.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.abonos.forEach(a => {
                        const capFormatted = new Intl.NumberFormat('es-CO').format(a.monto_capital_pagado);
                        const intFormatted = new Intl.NumberFormat('es-CO').format(a.monto_interes_pagado);
                        const fecha = a.fecha_abono ? a.fecha_abono.substring(0, 16) : '';
                        const usuario = a.registrado_por_nombre || 'Sistema';

                        html += `
                            <tr>
                                <td>${fecha}</td>
                                <td class="text-success fw-bold">$${capFormatted}</td>
                                <td class="text-warning fw-bold">$${intFormatted}</td>
                                <td>${usuario}</td>
                                ${isCanEdit ? `
                                    <td class="text-end">
                                        <button class="btn btn-xs btn-outline-primary me-1 btnEditCuotaItem"
                                                data-id="${a.id}"
                                                data-cap="${a.monto_capital_pagado}"
                                                data-int="${a.monto_interes_pagado}"
                                                data-fecha="${fecha}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form action="/admin/prestamos/abono/eliminar" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta cuota/abono?')">
                                            <input type="hidden" name="abono_id" value="${a.id}">
                                            <button type="submit" class="btn btn-xs btn-outline-danger">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                ` : ''}
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;

                    // Listener para los botones dinámicos de editar cuota
                    document.querySelectorAll('.btnEditCuotaItem').forEach(b => {
                        b.addEventListener('click', () => {
                            document.getElementById('edit_abono_id').value = b.getAttribute('data-id');
                            document.getElementById('edit_monto_capital_pagado').value = b.getAttribute('data-cap');
                            document.getElementById('edit_monto_interes_pagado').value = b.getAttribute('data-int');
                            document.getElementById('edit_fecha_abono').value = b.getAttribute('data-fecha');
                            
                            const modalCuotas = bootstrap.Modal.getInstance(document.getElementById('modalHistorialCuotas'));
                            if (modalCuotas) modalCuotas.hide();

                            const modalEditCuota = new bootstrap.Modal(document.getElementById('modalEditarCuotaItem'));
                            modalEditCuota.show();
                        });
                    });
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-danger">Error de conexión.</td></tr>';
                });
        });
    });
});
</script>
