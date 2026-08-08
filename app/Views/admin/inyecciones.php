<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-7 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-chart-line text-success"></i>
            Inyecciones de Capital de Socios
        </h2>
        <p class="text-muted m-0 fs-7">Aportes extraordinarios de capital al inicio de natillera con <strong>5% de rendimiento</strong> y retiro a los <strong>6 meses</strong>.</p>
    </div>
    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
        <button type="button" class="btn btn-success rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaInyeccion">
            <i class="fa-solid fa-plus-circle me-1"></i>Nueva Inyección de Capital
        </button>
    </div>
</div>

<!-- Tarjetas KPI de Resumen -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
            <span class="fs-8 text-muted fw-semibold">Total Inyectado Acumulado</span>
            <h4 class="font-outfit fw-bold text-success m-0 mt-1">$<?= number_format($resumen['total_inyectado'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1"><?= $resumen['total_registros'] ?> aportes registrados</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
            <span class="fs-8 text-muted fw-semibold">Rendimientos Generados (5%)</span>
            <h4 class="font-outfit fw-bold text-warning m-0 mt-1">$<?= number_format($resumen['total_rendimiento'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1">Reconocimiento al inversionista</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
            <span class="fs-8 text-muted fw-semibold">Capital Activo Congelado</span>
            <h4 class="font-outfit fw-bold text-primary m-0 mt-1">$<?= number_format($resumen['activo_monto'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1">En trabajo financiero</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-secondary">
            <span class="fs-8 text-muted fw-semibold">Capital Retirado / Devuelto</span>
            <h4 class="font-outfit fw-bold text-secondary m-0 mt-1">$<?= number_format($resumen['retirado_monto'], 0, ',', '.') ?> COP</h4>
            <small class="text-muted fs-8 d-block mt-1">Tras cumplir 6 meses</small>
        </div>
    </div>
</div>

<!-- Tabla Principal de Inyecciones -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                <tr>                    <th class="ps-4">Socio Inversionista</th>
                    <th>Reunión</th>
                    <th>Monto Inyectado</th>
                    <th>Rendimiento (5%)</th>
                    <th>Fecha Inyección</th>
                    <th>Fecha Retiro Permitido</th>
                    <th>Permanencia / Estado</th>
                    <th class="text-end pe-4">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inyecciones)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4 fs-7">
                            <i class="fa-solid fa-folder-open me-1 fs-5 d-block mb-1"></i>
                            No hay inyecciones de capital registradas aún. Presiona <strong>"+ Nueva Inyección de Capital"</strong> para registrar una.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inyecciones as $iny): 
                        $diasRestantes = (int)$iny['dias_para_retiro'];
                        $esEligible = ($iny['estado'] === 'ACTIVA' && $diasRestantes <= 0);
                    ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($iny['socio_nombre']) ?></div>
                                <small class="text-muted">C.C. <?= htmlspecialchars($iny['socio_cedula']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary">Reunión R<?= $iny['numero_quincena'] ?></span>
                                <small class="d-block text-muted fs-8"><?= date('d/m/Y', strtotime($iny['fecha_reunion'])) ?></small>
                            </td>
                            <td class="fw-bold font-outfit text-success fs-6">$<?= number_format($iny['monto_inyectado'], 0, ',', '.') ?> COP</td>
                            <td class="fw-bold font-outfit text-warning">$<?= number_format($iny['monto_rendimiento_generado'], 0, ',', '.') ?> <small class="text-muted fs-8">(5%)</small></td>
                            <td><?= date('d/m/Y g:i a', strtotime($iny['fecha_inyeccion'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-calendar-check text-success me-1"></i><?= date('d/m/Y', strtotime($iny['fecha_retiro_permitido'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($iny['estado'] === 'RETIRADA'): ?>
                                    <span class="badge bg-secondary">RETIRADA (<?= date('d/m/Y', strtotime($iny['fecha_retiro'])) ?>)</span>
                                <?php elseif ($esEligible): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-unlock me-1"></i>Disponible para Retiro</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i>Faltan <?= $diasRestantes ?> días</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($iny['estado'] === 'ACTIVA'): ?>
                                    <form action="/admin/inyecciones/retirar" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmas procesar la devolución/retiro de este capital?');">
                                        <input type="hidden" name="inyeccion_id" value="<?= $iny['id'] ?>">
                                        <button type="submit" class="btn btn-sm rounded-pill <?= $esEligible ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" <?= !$esEligible ? 'title="Aún no han transcurrido los 6 meses requeridos"' : '' ?>>
                                            <i class="fa-solid fa-hand-holding-dollar me-1"></i>Procesar Retiro
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted fs-8"><i class="fa-solid fa-circle-check text-success me-1"></i>Completado</span>
                                <?php endif; ?>
                            </td>
                            </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Inyección de Capital -->
<div class="modal fade" id="modalNuevaInyeccion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-chart-line me-2"></i>Registrar Inyección de Capital</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/inyecciones/crear" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="iny_socio_id" class="form-label fw-semibold fs-7">Socio Inversionista</label>
                        <select name="socio_id" id="iny_socio_id" class="form-select fw-bold" required>
                            <option value="">-- Seleccionar Socio --</option>
                            <?php foreach ($socios as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre_completo']) ?> (C.C. <?= htmlspecialchars($s['cedula']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label for="iny_reunion_id" class="form-label fw-semibold fs-7">Reunión (Apertura)</label>
                            <select name="reunion_id" id="iny_reunion_id" class="form-select" required>
                                <?php foreach ($reuniones as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= in_array($r['numero_quincena'], [1, 2]) ? 'class="fw-bold text-success"' : '' ?>>
                                        Reunión R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>       </div>
                        <div class="col-5">
                            <label for="iny_tasa" class="form-label fw-semibold fs-7">Tasa Rendimiento %</label>
                            <input type="number" step="0.01" name="tasa_rendimiento_porcentaje" id="iny_tasa" class="form-control fw-bold text-warning" value="5.00" readonly required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="iny_monto_inyectado" class="form-label fw-semibold fs-7">Monto Inyectado (COP)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold text-success">$</span>
                            <input type="text" name="monto_inyectado" id="iny_monto_inyectado" class="form-control money-input fw-bold text-success fs-5" placeholder="Ej: 1.000.000" required>
                        </div>
                        <small class="text-muted fs-8 mt-1 d-block">Rendimiento asignado del 5%. Retiro permitido automáticamente en 6 meses.</small>
                    </div>

                    <div class="mb-3">
                        <label for="iny_observaciones" class="form-label fw-semibold fs-7">Observaciones / Nota Adicional</label>
                        <input type="text" name="observaciones" id="iny_observaciones" class="form-control" placeholder="Opcional (Ej: Aporte inicial natillera)">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Guardar Inyección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Formato de miles con puntos
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
