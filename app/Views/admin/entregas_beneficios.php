<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-file-signature text-warning"></i>
            Control de Entregas, Desembolsos y Evidencias
        </h2>
        <p class="text-muted m-0 fs-7">Firma digital táctil y evidencia fotográfica para desembolsos de préstamos, rondas y rifas.</p>
    </div>
    <div class="col-12 col-md-6 text-md-end">
        <button type="button" class="btn btn-warning text-dark rounded-pill fw-bold btn-sm shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#modalRegistrarEntrega">
            <i class="fa-solid fa-signature me-1"></i>Registrar Entrega (Firma y Foto)
        </button>
    </div>
</div>

<!-- Tarjetas de Métricas de Fondos y Préstamos -->
<div class="row g-3 mb-4">
    <!-- Préstamos (Firma & Foto) -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-gradient-navy text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-10 rounded-circle text-primary fs-4">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h5 class="font-outfit fw-bold m-0 text-white">Desembolso Préstamos</h5>
                        <span class="text-white-50 fs-8">Firma Digital & Foto Evidencia</span>
                    </div>
                </div>
                <span class="badge bg-primary text-white font-outfit fs-7"><?= $resumenFondos['prestamo']['entregados_count'] ?> Entregas</span>
            </div>
            <div class="p-2 bg-white bg-opacity-10 rounded-3 text-center fs-7">
                <span class="text-white-50 d-block fs-8">Total Desembolsado Registrado</span>
                <strong class="font-outfit text-white fs-5">$<?= number_format($resumenFondos['prestamo']['entregados_monto'], 0, ',', '.') ?> COP</strong>
            </div>
        </div>
    </div>

    <!-- Ronda $300.000 -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-gradient-navy text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-10 rounded-circle text-warning fs-4">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <div>
                        <h5 class="font-outfit fw-bold m-0 text-white">Fondo Rondas ($300k)</h5>
                        <span class="text-white-50 fs-8">Total 50 Beneficiados</span>
                    </div>
                </div>
                <span class="badge bg-warning text-dark font-outfit fs-7"><?= $resumenFondos['ronda']['entregados_count'] ?> / 50</span>
            </div>
            <div class="row g-1 text-center fs-7">
                <div class="col-6">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <span class="text-white-50 d-block fs-8">Recaudado Total</span>
                        <strong class="font-outfit text-white fs-6">$<?= number_format($resumenFondos['ronda']['total_recaudado'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <span class="text-white-50 d-block fs-8">Entregado</span>
                        <strong class="font-outfit text-warning fs-6">$<?= number_format($resumenFondos['ronda']['entregados_monto'], 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rifa $150.000 -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-gradient-navy text-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-white bg-opacity-10 rounded-circle text-info fs-4">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div>
                        <h5 class="font-outfit fw-bold m-0 text-white">Fondo Rifas ($150k)</h5>
                        <span class="text-white-50 fs-8">Total 50 Beneficiados</span>
                    </div>
                </div>
                <span class="badge bg-info text-dark font-outfit fs-7"><?= $resumenFondos['rifa']['entregados_count'] ?> / 50</span>
            </div>
            <div class="row g-1 text-center fs-7">
                <div class="col-6">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <span class="text-white-50 d-block fs-8">Recaudado Total</span>
                        <strong class="font-outfit text-white fs-6">$<?= number_format($resumenFondos['rifa']['total_recaudado'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-white bg-opacity-10 rounded-3">
                        <span class="text-white-50 d-block fs-8">Entregado</span>
                        <strong class="font-outfit text-info fs-6">$<?= number_format($resumenFondos['rifa']['entregados_monto'], 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de Navegación -->
<ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4 font-outfit fw-bold" id="tab-entregas-tab" data-bs-toggle="pill" data-bs-target="#tab-entregas" type="button" role="tab">
            <i class="fa-solid fa-file-signature me-2"></i>Historial de Entregas y Evidencias (<?= count($entregas) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4 font-outfit fw-bold" id="tab-cronograma-tab" data-bs-toggle="pill" data-bs-target="#tab-cronograma" type="button" role="tab">
            <i class="fa-solid fa-calendar-days me-2"></i>Cronograma 26 Quincenas
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    <!-- Tab 1: Historial de Entregas Realizadas -->
    <div class="tab-pane fade show active" id="tab-entregas" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                        <tr>
                            <th class="ps-4">Quincena / Fecha</th>
                            <th>Socio Beneficiario</th>
                            <th>Tipo Entrega</th>
                            <th>Monto Entregado</th>
                            <th class="text-center">Firma Digital</th>
                            <th class="text-center">Foto Evidencia</th>
                            <th class="text-end pe-4">Entregado Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entregas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-gift fs-1 text-secondary mb-3 d-block"></i>
                                    Aún no hay entregas de préstamos, rondas o rifas registradas con evidencia.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entregas as $e): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold font-outfit text-dark">Q<?= $e['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($e['fecha_reunion'])) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($e['fecha_entrega'])) ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark font-outfit d-block"><?= htmlspecialchars($e['socio_nombre']) ?></strong>
                                        <small class="text-muted">C.C. <?= htmlspecialchars($e['socio_cedula']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $e['tipo_beneficio'] === 'PRESTAMO' ? 'primary' : ($e['tipo_beneficio'] === 'RONDA' ? 'warning text-dark' : 'info text-dark') ?> fw-bold px-3 py-1">
                                            <i class="fa-solid <?= $e['tipo_beneficio'] === 'PRESTAMO' ? 'fa-hand-holding-dollar' : ($e['tipo_beneficio'] === 'RONDA' ? 'fa-arrows-rotate' : 'fa-ticket') ?> me-1"></i><?= $e['tipo_beneficio'] ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-success font-outfit fs-6">$<?= number_format($e['monto_entregado'], 0, ',', '.') ?> COP</td>
                                    <td class="text-center">
                                        <?php if (!empty($e['firma_digital_path'])): ?>
                                            <a href="<?= $e['firma_digital_path'] ?>" target="_blank" title="Ver Firma Digital">
                                                <img src="<?= $e['firma_digital_path'] ?>" alt="Firma" class="border rounded bg-white shadow-sm p-1" style="height: 40px; max-width: 100px; object-fit: contain;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fs-8">Sin firma</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($e['foto_evidencia_path'])): ?>
                                            <a href="<?= $e['foto_evidencia_path'] ?>" target="_blank" title="Ver Foto Evidencia">
                                                <img src="<?= $e['foto_evidencia_path'] ?>" alt="Foto Evidencia" class="rounded shadow-sm border p-1" style="height: 45px; width: 45px; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fs-8">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <small class="fw-semibold text-muted d-block"><i class="fa-solid fa-user-check text-primary me-1"></i><?= htmlspecialchars($e['entregado_por_nombre']) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Cronograma 26 Quincenas -->
    <div class="tab-pane fade" id="tab-cronograma" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3">
                <h5 class="font-outfit fw-bold m-0"><i class="fa-solid fa-calendar-check text-warning me-2"></i>Cronograma de Recaudos y Liberaciones de Rondas y Rifas</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 fs-7">
                    <thead class="bg-light font-outfit text-uppercase">
                        <tr>
                            <th class="ps-3">Quincena</th>
                            <th>Tipo Fondo</th>
                            <th>Cuota Base</th>
                            <th>Aporte c/u</th>
                            <th>Recaudo Total</th>
                            <th>Monto Premio</th>
                            <th>Personas Liberadas</th>
                            <th class="pe-3 text-end">Saldo Acumulado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cronograma as $c): ?>
                            <tr>
                                <td class="ps-3 fw-bold font-outfit">Q<?= $c['numero_quincena'] ?> (<?= date('d/m/Y', strtotime($c['fecha_reunion'])) ?>)</td>
                                <td>
                                    <span class="badge bg-<?= $c['tipo_beneficio'] === 'RONDA' ? 'warning text-dark' : 'info text-dark' ?>">
                                        <?= $c['tipo_beneficio'] ?>
                                    </span>
                                </td>
                                <td>$<?= number_format($c['valor_cuota_base'], 0, ',', '.') ?></td>
                                <td class="fw-semibold">$<?= number_format($c['aporte_por_socio'], 0, ',', '.') ?></td>
                                <td class="text-success fw-bold">$<?= number_format($c['total_recaudado'], 0, ',', '.') ?></td>
                                <td class="fw-bold">$<?= number_format($c['monto_beneficio_unidad'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge bg-<?= $c['personas_liberadas_planificadas'] > 1 ? 'danger' : ($c['personas_liberadas_planificadas'] === 1 ? 'success' : 'secondary') ?> px-2">
                                        <?= $c['personas_liberadas_planificadas'] ?> Socio(s)
                                    </span>
                                </td>
                                <td class="pe-3 text-end fw-bold font-outfit">$<?= number_format($c['saldo_acumulado_fondo'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Entrega (Con Firma Digital y Captura de Foto) -->
<div class="modal fade" id="modalRegistrarEntrega" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title font-outfit fw-bold"><i class="fa-solid fa-signature me-2"></i>Registrar Entrega / Desembolso (Firma & Evidencia)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/entregas/guardar" method="POST" enctype="multipart/form-data" id="formRegistrarEntrega">
                <input type="hidden" name="firma_base64" id="firma_base64">
                <div class="modal-body p-4 text-dark">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-5">
                            <label for="entrega_tipo_beneficio" class="form-label fw-semibold fs-7">Tipo de Entrega</label>
                            <select name="tipo_beneficio" id="entrega_tipo_beneficio" class="form-select fw-bold text-dark" required>
                                <option value="PRESTAMO" <?= ($tipoQuery === 'PRESTAMO') ? 'selected' : '' ?>>PRÉSTAMO (Desembolso Crédito)</option>
                                <option value="RONDA" <?= ($tipoQuery === 'RONDA') ? 'selected' : '' ?>>RONDA ($300.000 COP)</option>
                                <option value="RIFA" <?= ($tipoQuery === 'RIFA') ? 'selected' : '' ?>>RIFA ($150.000 COP)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="entrega_reunion_id" class="form-label fw-semibold fs-7">Reunión / Quincena</label>
                            <select name="reunion_id" id="entrega_reunion_id" class="form-select" required>
                                <?php foreach ($reuniones as $r): ?>
                                    <option value="<?= $r['id'] ?>">Q<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="entrega_monto_entregado" class="form-label fw-semibold fs-7">Monto Entregado (COP)</label>
                            <?php $valMonto = ($montoQuery > 0) ? number_format($montoQuery, 0, ',', '.') : (($tipoQuery === 'RONDA') ? '300.000' : (($tipoQuery === 'RIFA') ? '150.000' : '500.000')); ?>
                            <input type="text" name="monto_entregado" id="entrega_monto_entregado" class="form-control money-input fw-bold text-success" value="<?= $valMonto ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="entrega_socio_id" class="form-label fw-semibold fs-7">Socio Beneficiario / Deudor</label>
                        <select name="socio_id" id="entrega_socio_id" class="form-select fw-bold text-primary" required>
                            <option value="">-- Seleccionar Socio --</option>
                            <?php 
                            $listSocios = ($tipoQuery === 'RONDA') ? $sociosRonda : (($tipoQuery === 'RIFA') ? $sociosRifa : $sociosPrestamo);
                            foreach ($listSocios as $s): 
                                $sel = ($socioIdQuery > 0 && $socioIdQuery == $s['id']) ? 'selected' : '';
                            ?>
                                <option value="<?= $s['id'] ?>" <?= $sel ?>><?= htmlspecialchars($s['nombre_completo']) ?> (C.C. <?= htmlspecialchars($s['cedula']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Canvas Pad de Firma Digital -->
                        <div class="col-12 col-md-7">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold fs-7 mb-0 text-dark">
                                        <i class="fa-solid fa-pen-nib text-primary me-1"></i>Firma Digital del Socio:
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" id="btnLimpiarFirma">
                                        <i class="fa-solid fa-eraser me-1"></i>Limpiar
                                    </button>
                                </div>
                                <div class="border bg-white rounded-3 p-1 text-center shadow-inner overflow-hidden">
                                    <canvas id="canvasFirma" width="360" height="150" class="w-100 cursor-crosshair" style="touch-action: none; background-color: #ffffff;"></canvas>
                                </div>
                                <small class="text-muted fs-8 d-block mt-1">Dibuja la firma del socio directamente sobre la pantalla con el dedo o mouse.</small>
                            </div>
                        </div>

                        <!-- Evidencia Fotográfica (Cámara / Subida) -->
                        <div class="col-12 col-md-5">
                            <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <label for="foto_evidencia" class="form-label fw-semibold fs-7 mb-1 text-dark">
                                        <i class="fa-solid fa-camera text-info me-1"></i>Foto Evidencia de Entrega:
                                    </label>
                                    <input type="file" name="foto_evidencia" id="foto_evidencia" class="form-control form-control-sm mb-2" accept="image/*" capture="environment">
                                    <small class="text-muted fs-8 d-block">Toma una foto con la cámara del celular o selecciona una foto de la galería.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold px-4">Guardar Entrega y Constancia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/js/firma_canvas.js"></script>
<?php if ($socioIdQuery > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = new bootstrap.Modal(document.getElementById('modalRegistrarEntrega'));
    modal.show();
});
</script>
<?php endif; ?>
