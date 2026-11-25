<link rel="stylesheet" href="/css/pages/gestion-general.css">

<header class="gg-head">
    <div class="gg-head__text">
        <span class="nt-eyebrow">Capital</span>
        <h2 class="nt-page-title gg-head__title">
            <i class="fa-solid fa-chart-line gg-head__icon "></i>
            <span>Inyecciones de Capital de Socios</span>
        </h2>
        <p class="nt-page-sub m-0">Aportes extraordinarios de capital al inicio de natillera con <strong>5% de rendimiento</strong> y retiro a los <strong>6 meses</strong>.</p>
    </div>
    <div class="gg-head__actions">
        <button type="button" class="btn btn-primary px-3" data-bs-toggle="modal" data-bs-target="#modalNuevaInyeccion">
            <i class="fa-solid fa-plus-circle me-1"></i>Nueva Inyección de Capital
        </button>
    </div>
</header>

<!-- Tarjetas KPI de Resumen -->
<div class="row g-3 mb-4 nt-stagger">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="nt-stat gg-kpi ">
            <span class="nt-stat__label">Total Inyectado Acumulado</span>
            <h4 class="nt-stat__value nt-money gg-t-pine m-0">$<?= number_format($resumen['total_inyectado'], 0, ',', '.') ?> COP</h4>
            <small class="gg-kpi__hint"><?= $resumen['total_registros'] ?> aportes registrados</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="nt-stat gg-kpi gg-kpi--gold">
            <span class="nt-stat__label">Rendimientos Generados (5%)</span>
            <h4 class="nt-stat__value nt-money gg-t-gold m-0">$<?= number_format($resumen['total_rendimiento'], 0, ',', '.') ?> COP</h4>
            <small class="gg-kpi__hint">Reconocimiento al inversionista</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="nt-stat gg-kpi ">
            <span class="nt-stat__label">Capital Activo Congelado</span>
            <h4 class="nt-stat__value nt-money gg-t-pine m-0">$<?= number_format($resumen['activo_monto'], 0, ',', '.') ?> COP</h4>
            <small class="gg-kpi__hint">En trabajo financiero</small>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="nt-stat gg-kpi gg-kpi--muted">
            <span class="nt-stat__label">Capital Retirado / Devuelto</span>
            <h4 class="nt-stat__value nt-money gg-t-ink m-0">$<?= number_format($resumen['retirado_monto'], 0, ',', '.') ?> COP</h4>
            <small class="gg-kpi__hint">Tras cumplir 6 meses</small>
        </div>
    </div>
</div>

<!-- Tabla Principal de Inyecciones -->
<div class="gg-panel">
    <div class="table-responsive">
        <table class="table table-hover align-middle gg-table gg-stack">
            <thead>
                <tr>
                    <th class="ps-4">Socio Inversionista</th>
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
                        <td colspan="8" class="gg-empty fs-7">
                            <i class="fa-solid fa-folder-open"></i>
                            No hay inyecciones de capital registradas aún. Presiona <strong>"+ Nueva Inyección de Capital"</strong> para registrar una.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inyecciones as $iny): 
                        $diasRestantes = (int)$iny['dias_para_retiro'];
                        $esEligible = ($iny['estado'] === 'ACTIVA' && $diasRestantes <= 0);
                    ?>
                        <tr>
                            <td class="ps-4 gg-cell-main">
                                <div class="min-w-0">
                                    <div class="gg-name"><?= htmlspecialchars($iny['socio_nombre']) ?></div>
                                    <small class="text-muted">C.C. <?= htmlspecialchars($iny['socio_cedula']) ?></small>
                                </div>
                            </td>
                            <td data-label="Reunión">
                                <span class="badge bg-primary">Reunión R<?= $iny['numero_quincena'] ?></span>
                                <small class="d-block text-muted fs-8"><?= date('d/m/Y', strtotime($iny['fecha_reunion'])) ?></small>
                            </td>
                            <td data-label="Monto inyectado" class="fw-bold font-outfit gg-t-pine fs-6 nt-money">$<?= number_format($iny['monto_inyectado'], 0, ',', '.') ?> COP</td>
                            <td data-label="Rendimiento (5%)" class="fw-bold font-outfit gg-t-gold nt-money">$<?= number_format($iny['monto_rendimiento_generado'], 0, ',', '.') ?> <small class="text-muted fs-8">(5%)</small></td>
                            <td data-label="Fecha inyección"><?= date('d/m/Y g:i a', strtotime($iny['fecha_inyeccion'])) ?></td>
                            <td data-label="Retiro permitido">
                                <span class="nt-chip">
                                    <i class="fa-solid fa-calendar-check me-1"></i><?= date('d/m/Y', strtotime($iny['fecha_retiro_permitido'])) ?>
                                </span>
                            </td>
                            <td data-label="Estado">
                                <?php if ($iny['estado'] === 'RETIRADA'): ?>
                                    <span class="badge bg-secondary">RETIRADA (<?= date('d/m/Y', strtotime($iny['fecha_retiro'])) ?>)</span>
                                <?php elseif ($esEligible): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-unlock me-1"></i>Disponible para Retiro</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-lock me-1"></i>Faltan <?= $diasRestantes ?> días</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4 gg-cell-actions">
                                <?php if ($iny['estado'] === 'ACTIVA'): ?>
                                    <button type="button" class="btn btn-sm btnRetirarInyeccion <?= $esEligible ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" 
                                            data-id="<?= $iny['id'] ?>"
                                            data-socio="<?= htmlspecialchars($iny['socio_nombre']) ?>"
                                            data-monto="$<?= number_format($iny['monto_inyectado'] + $iny['monto_rendimiento_generado'], 0, ',', '.') ?> COP"
                                            data-bs-toggle="modal" data-bs-target="#modalProcesarRetiro"
                                            <?= !$esEligible ? 'title="Aún no han transcurrido los 6 meses requeridos"' : '' ?>>
                                        <i class="fa-solid fa-hand-holding-dollar me-1"></i>Procesar Retiro
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted fs-8"><i class="fa-solid fa-circle-check gg-t-pine me-1"></i>Completado</span>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content gg-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-chart-line me-2"></i>Registrar Inyección de Capital</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/inyecciones/crear" method="POST">
                <div class="modal-body">
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
                                <?php foreach ($reuniones as $r): 
                                    $isActIny = (!empty($reunionActual) && $reunionActual['id'] == $r['id']) ? 'selected' : '';
                                    $lblEstado = ($r['estado'] === 'LLAMADO_CERRADO') ? ' (Llamado Cerrado ★)' : '';
                                ?>
                                    <option value="<?= $r['id'] ?>" <?= $isActIny ?>>
                                        Reunión R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?><?= $lblEstado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label for="iny_tasa" class="form-label fw-semibold fs-7">Tasa Rendimiento %</label>
                            <input type="number" step="0.01" name="tasa_rendimiento_porcentaje" id="iny_tasa" class="form-control fw-bold gg-t-gold" value="5.00" readonly required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="iny_monto_inyectado" class="form-label fw-semibold fs-7">Monto Inyectado (COP)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold gg-t-pine">$</span>
                            <input type="text" name="monto_inyectado" id="iny_monto_inyectado" class="form-control money-input fw-bold gg-t-pine gg-bigmoney" inputmode="numeric" placeholder="Ej: 1.000.000" required>
                        </div>
                        <small class="text-muted fs-8 mt-1 d-block">Rendimiento asignado del 5%. Retiro permitido automáticamente en 6 meses.</small>
                    </div>

                    <div class="mb-3">
                        <label for="iny_observaciones" class="form-label fw-semibold fs-7">Observaciones / Nota Adicional</label>
                        <input type="text" name="observaciones" id="iny_observaciones" class="form-control" placeholder="Opcional (Ej: Aporte inicial natillera)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Inyección</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Procesar Retiro de Capital -->
<div class="modal fade" id="modalProcesarRetiro" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content gg-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-hand-holding-dollar me-2 "></i>Procesar Retiro de Capital</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/inyecciones/retirar" method="POST">
                <input type="hidden" name="inyeccion_id" id="ret_inyeccion_id">
                <div class="modal-body text-dark">
                    <p class="mb-2">Socio Inversionista: <strong id="ret_socio_nombre" class="gg-t-pine font-outfit gg-wrap"></strong></p>
                    <p class="mb-3 text-muted fs-7">Monto a Devolver (Capital + Rendimiento): <strong id="ret_monto_total" class="gg-t-pine fs-6 font-outfit nt-money"></strong></p>

                    <div class="mb-3">
                        <label for="ret_reunion_id" class="form-label fw-semibold fs-7">Reunión en que se efectúa el Retiro / Salida de Caja</label>
                        <select name="reunion_id_retiro" id="ret_reunion_id" class="form-select fw-bold" required>
                            <?php foreach ($reuniones as $r): 
                                $isActR = (!empty($reunionActual) && $reunionActual['id'] == $r['id']) ? 'selected' : '';
                                $lblEstado = ($r['estado'] === 'LLAMADO_CERRADO') ? ' (Llamado Cerrado ★)' : '';
                            ?>
                                <option value="<?= $r['id'] ?>" <?= $isActR ?>>R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?><?= $lblEstado ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted fs-8">Esta salida se registrará directamente en el EGRESO (-) del cierre financiero de la reunión seleccionada.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4">Confirmar y Registrar Retiro</button>
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

    // Cargar datos en modal de retiro de inyección
    document.querySelectorAll('.btnRetirarInyeccion').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ret_inyeccion_id').value = btn.getAttribute('data-id');
            document.getElementById('ret_socio_nombre').textContent = btn.getAttribute('data-socio');
            document.getElementById('ret_monto_total').textContent = btn.getAttribute('data-monto');
        });
    });
});
</script>
