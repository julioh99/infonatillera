<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-7 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-arrows-split-up-and-left text-warning"></i>
            Transferencias y Préstamos Entre Cajas (Caja Mayor ↔ Actividades)
        </h2>
        <p class="text-muted m-0 fs-7">Control de préstamos <strong>sin interés</strong> otorgados por la Caja Mayor a la Caja de Actividades e ingresos por devoluciones de capital/utilidades.</p>
    </div>
    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
        <button type="button" class="btn btn-warning text-dark rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaTransferencia">
            <i class="fa-solid fa-plus-circle me-1"></i>Registrar Movimiento Entre Cajas
        </button>
    </div>
</div>

<!-- Tarjetas KPI Intercajas -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
            <span class="fs-8 text-muted fw-semibold">Total Prestado a Caja Actividades</span>
            <h4 class="font-outfit fw-bold text-danger m-0 mt-1">$<?= number_format($resumen['total_prestado_actividades'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1">Capital financiado sin interés por la Caja Mayor</small>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
            <span class="fs-8 text-muted fw-semibold">Total Devuelto a la Caja Mayor</span>
            <h4 class="font-outfit fw-bold text-success m-0 mt-1">$<?= number_format($resumen['total_devuelto_caja_mayor'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1">Reembolsos y aportes de utilidades de eventos</small>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 <?= ($resumen['saldo_pendiente_actividades'] > 0) ? 'border-warning' : 'border-info' ?>">
            <span class="fs-8 text-muted fw-semibold">Saldo Pendiente por Saldar entre Cajas</span>
            <h4 class="font-outfit fw-bold <?= ($resumen['saldo_pendiente_actividades'] > 0) ? 'text-warning' : 'text-info' ?> m-0 mt-1">
                $<?= number_format($resumen['saldo_pendiente_actividades'], 0, ',', '.') ?> COP
            </h4>
            <small class="text-muted fs-8 d-block mt-1">
                <?= ($resumen['saldo_pendiente_actividades'] > 0) ? 'Caja de Actividades adeuda a Caja Mayor' : 'Cuentas al día entre cajas' ?>
            </small>
        </div>
    </div>
</div>

<!-- Tabla Principal de Transferencias -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>
                    <th class="ps-4">Quincena / Reunión</th>
                    <th>Tipo de Movimiento</th>
                    <th>Actividad Asociada</th>
                    <th>Monto Transferido</th>
                    <th>Concepto / Detalle</th>
                    <th>Fecha y Hora</th>
                    <th class="text-end pe-4">Registrado Por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transferencias)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4 fs-7">
                            <i class="fa-solid fa-folder-open me-1 fs-5 d-block mb-1"></i>
                            No hay movimientos registrados entre la Caja Mayor y la Caja de Actividades. Presiona <strong>"+ Registrar Movimiento Entre Cajas"</strong> para agregar uno.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transferencias as $t): 
                        $esPrestamo = ($t['tipo_movimiento'] === 'PRESTAMO_A_ACTIVIDAD');
                    ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary font-outfit fs-7">Quincena Q<?= $t['numero_quincena'] ?></span>
                                <small class="d-block text-muted fs-8"><?= date('d/m/Y', strtotime($t['fecha_reunion'])) ?></small>
                            </td>
                            <td>
                                <?php if ($esPrestamo): ?>
                                    <span class="badge bg-danger text-white"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Préstamo a Actividades</span>
                                <?php else: ?>
                                    <span class="badge bg-success text-white"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Devolución a Caja Mayor</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($t['nombre_actividad']) ? htmlspecialchars($t['nombre_actividad']) : '<span class="text-muted fs-8">General / Sin evento específico</span>' ?>
                            </td>
                            <td class="fw-bold font-outfit fs-6 <?= $esPrestamo ? 'text-danger' : 'text-success' ?>">
                                <?= $esPrestamo ? '-' : '+' ?>$<?= number_format($t['monto'], 0, ',', '.') ?> COP
                            </td>
                            <td><?= htmlspecialchars($t['concepto']) ?></td>
                            <td><?= date('d/m/Y g:i a', strtotime($t['fecha_transferencia'])) ?></td>
                            <td class="text-end pe-4 font-outfit text-dark"><?= htmlspecialchars($t['registrado_por_nombre']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Transferencia Entre Cajas -->
<div class="modal fade" id="modalNuevaTransferencia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-arrows-split-up-and-left me-2 text-warning"></i>Registrar Movimiento Entre Cajas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/transferencias-cajas/crear" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="trans_tipo_movimiento" class="form-label fw-semibold fs-7">Tipo de Movimiento</label>
                        <select name="tipo_movimiento" id="trans_tipo_movimiento" class="form-select fw-bold text-dark" required>
                            <option value="PRESTAMO_A_ACTIVIDAD">Caja Mayor ➔ Caja Actividades (Préstamo sin Interés)</option>
                            <option value="DEVOLUCION_A_CAJA_MAYOR">Caja Actividades ➔ Caja Mayor (Devolución de Capital / Utilidades)</option>
                        </select>
                        <small class="text-muted fs-8 mt-1 d-block">Los préstamos salen como egreso de la Caja Mayor; las devoluciones entran como ingreso a la Caja Mayor.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="trans_reunion_id" class="form-label fw-semibold fs-7">Reunión / Quincena</label>
                            <select name="reunion_id" id="trans_reunion_id" class="form-select" required>
                                <?php foreach ($reuniones as $r): ?>
                                    <option value="<?= $r['id'] ?>">Q<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="trans_actividad_id" class="form-label fw-semibold fs-7">Actividad (Opcional)</label>
                            <select name="actividad_id" id="trans_actividad_id" class="form-select">
                                <option value="">-- Ninguna / General --</option>
                                <?php foreach ($actividades as $act): ?>
                                    <option value="<?= $act['id'] ?>"><?= htmlspecialchars($act['nombre_actividad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="trans_monto" class="form-label fw-semibold fs-7">Monto Transferido (COP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold text-dark">$</span>
                            <input type="text" name="monto" id="trans_monto" class="form-control money-input fw-bold text-dark fs-5" placeholder="Ej: 300.000" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="trans_concepto" class="form-label fw-semibold fs-7">Concepto / Motivo</label>
                        <input type="text" name="concepto" id="trans_concepto" class="form-control" placeholder="Ej: Préstamo inicial para compra de ingredientes tamales" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill fw-bold px-4">Guardar Movimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '');
            if (val) {
                e.target.value = new Intl.NumberFormat('es-CO').format(parseInt(val, 10));
            } else {
                e.target.value = '';
            }
        });
    });
});
</script>
