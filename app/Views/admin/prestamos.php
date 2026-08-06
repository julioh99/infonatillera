<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-hand-holding-dollar text-warning"></i>
            Gestión de Préstamos e Intereses
        </h2>
        <p class="text-muted m-0 fs-7">Control de créditos directos, fiadores, autopréstamos y amortizaciones.</p>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-md-end gap-2">
        <?php if (in_array($userRole, ['Presidente', 'Secretaria General'])): ?>
            <button type="button" class="btn btn-outline-dark rounded-pill fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#modalTopeCredit">
                <i class="fa-solid fa-sliders me-1"></i>Ajustar Tope / Tasa Socio
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-warning rounded-pill fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoPrestamo">
            <i class="fa-solid fa-plus me-1"></i>Nuevo Préstamo
        </button>
    </div>
</div>

<!-- Lista de Préstamos -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Socio Deudor / Fiador</th>
                    <th>Tipo / Tercero</th>
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
                        <td colspan="8" class="text-center py-4 text-muted">No hay préstamos registrados aún.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prestamos as $p): 
                        $saldoCapital = (float)$p['monto_prestado'] - (float)$p['total_capital_pagado'];
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($p['deudor_nombre']) ?></div>
                                <?php if (!empty($p['fiador_nombre'])): ?>
                                    <small class="text-muted d-block"><i class="fa-solid fa-user-shield text-info me-1"></i>Fiador: <?= htmlspecialchars($p['fiador_nombre']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-dark border rounded-pill">
                                    <?= htmlspecialchars($p['tipo_prestamo']) ?>
                                </span>
                                <?php if (!empty($p['tercero_nombre'])): ?>
                                    <small class="d-block text-primary fw-semibold"><?= htmlspecialchars($p['tercero_nombre']) ?></small>
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
                                    <span class="badge bg-success">Pagado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Activo (Bal: $<?= number_format($saldoCapital, 0, ',', '.') ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($p['estado'] === 'ACTIVO' && !$p['anulado_sin_interes']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 btnAbonar" 
                                            data-id="<?= $p['id'] ?>" 
                                            data-deudor="<?= htmlspecialchars($p['deudor_nombre']) ?>" 
                                            data-saldo="<?= $saldoCapital ?>"
                                            data-tasa="<?= $p['tasa_interes_mensual'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalAbono">
                                        <i class="fa-solid fa-coins me-1"></i>Abonar
                                    </button>
                                <?php endif; ?>
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
                        <label for="tipo_prestamo" class="form-label fw-semibold fs-7">Tipo de Préstamo</label>
                        <select name="tipo_prestamo" id="tipo_prestamo" class="form-select" onchange="toggleTerceroFields(this.value)">
                            <option value="DIRECTO">Directo al Socio</option>
                            <option value="TERCERO">A Tercero (con Socio Fiador)</option>
                        </select>
                    </div>

                    <div id="divTercero" class="d-none border rounded-3 p-3 bg-light mb-3">
                        <div class="mb-2">
                            <label for="tercero_nombre" class="form-label fw-semibold fs-7">Nombre del Tercero</label>
                            <input type="text" name="tercero_nombre" id="tercero_nombre" class="form-control" placeholder="Ej: Pedro Pérez">
                        </div>
                        <div>
                            <label for="socio_fiador_id" class="form-label fw-semibold fs-7">Socio Fiador Responsable</label>
                            <select name="socio_fiador_id" id="socio_fiador_id" class="form-select">
                                <option value="">-- Seleccionar Fiador --</option>
                                <?php foreach ($socios as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="monto_prestado" class="form-label fw-semibold fs-7">Monto a Prestar (COP)</label>
                            <input type="number" step="50000" min="50000" name="monto_prestado" id="monto_prestado" class="form-control fw-bold" placeholder="Ej: 500000" required>
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

<!-- Modal Registrar Abono -->
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
                    <p class="mb-3">Socio: <strong id="abono_deudor_nombre" class="text-primary font-outfit"></strong></p>
                    <p class="mb-3 text-muted fs-7">Saldo Pendiente Capital: <strong id="abono_saldo_capital" class="text-dark"></strong></p>

                    <div class="mb-3">
                        <label for="monto_interes_pagado" class="form-label fw-semibold fs-7">Abono a Intereses (COP)</label>
                        <input type="number" step="1000" min="0" name="monto_interes_pagado" id="monto_interes_pagado" class="form-control text-warning fw-bold" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label for="monto_capital_pagado" class="form-label fw-semibold fs-7">Abono a Capital (COP)</label>
                        <input type="number" step="1000" min="0" name="monto_capital_pagado" id="monto_capital_pagado" class="form-control text-success fw-bold" placeholder="0">
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
                        <input type="number" step="100000" min="500000" name="nuevo_tope" id="nuevo_tope" class="form-control fw-bold" placeholder="Ej: 3000000" required>
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
function toggleTerceroFields(tipo) {
    const div = document.getElementById('divTercero');
    if (tipo === 'TERCERO') {
        div.classList.remove('d-none');
    } else {
        div.classList.add('d-none');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const btnsAbonar = document.querySelectorAll('.btnAbonar');
    btnsAbonar.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = btn.getAttribute('data-id');
            const deudor = btn.getAttribute('data-deudor');
            const saldo = btn.getAttribute('data-saldo');
            
            document.getElementById('abono_prestamo_id').value = id;
            document.getElementById('abono_deudor_nombre').innerText = deudor;
            document.getElementById('abono_saldo_capital').innerText = '$' + new Intl.NumberFormat('es-CO').format(saldo);
        });
    });
});
</script>
