<link rel="stylesheet" href="/css/pages/gestion-general.css">

<header class="gg-head">
    <div class="gg-head__text">
        <span class="nt-eyebrow">Tesorería</span>
        <h2 class="nt-page-title gg-head__title">
            <i class="fa-solid fa-file-signature gg-head__icon gg-head__icon--gold"></i>
            <span>Control de Entregas, Desembolsos y Evidencias</span>
        </h2>
        <p class="nt-page-sub m-0">Firma digital táctil y evidencia fotográfica para desembolsos de préstamos, rondas y rifas.</p>
    </div>
    <div class="gg-head__actions">
        <button type="button" class="btn btn-warning px-3" data-bs-toggle="modal" data-bs-target="#modalRegistrarEntrega">
            <i class="fa-solid fa-signature me-1"></i>Registrar Entrega (Firma y Foto)
        </button>
    </div>
</header>

<!-- Tarjetas de Métricas de Fondos y Préstamos -->
<div class="row g-3 mb-4 nt-stagger">
    <!-- Préstamos (Firma & Foto) -->
    <div class="col-12 col-md-4">
        <div class="nt-hero gg-fund">
            <div class="gg-fund__top">
                <div class="gg-fund__id">
                    <div class="gg-fund__icon">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="gg-fund__title">Desembolso Préstamos</h5>
                        <span class="gg-fund__sub">Firma Digital & Foto Evidencia</span>
                    </div>
                </div>
                <span class="gg-fund__badge"><?= $resumenFondos['prestamo']['entregados_count'] ?> Entregas</span>
            </div>
            <div class="gg-fund__cell">
                <span class="gg-fund__label">Total Desembolsado Registrado</span>
                <strong class="gg-fund__num nt-money">$<?= number_format($resumenFondos['prestamo']['entregados_monto'], 0, ',', '.') ?> COP</strong>
            </div>
        </div>
    </div>

    <!-- Ronda $300.000 -->
    <div class="col-12 col-md-4">
        <div class="nt-hero gg-fund">
            <div class="gg-fund__top">
                <div class="gg-fund__id">
                    <div class="gg-fund__icon">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="gg-fund__title">Fondo Rondas ($300k)</h5>
                        <span class="gg-fund__sub">Total 50 Beneficiados</span>
                    </div>
                </div>
                <span class="gg-fund__badge"><?= $resumenFondos['ronda']['entregados_count'] ?> / 50</span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="gg-fund__cell">
                        <span class="gg-fund__label">Recaudado Total</span>
                        <strong class="gg-fund__num nt-money">$<?= number_format($resumenFondos['ronda']['total_recaudado'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="gg-fund__cell">
                        <span class="gg-fund__label">Entregado</span>
                        <strong class="gg-fund__num gg-fund__num--gold nt-money">$<?= number_format($resumenFondos['ronda']['entregados_monto'], 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rifa $150.000 -->
    <div class="col-12 col-md-4">
        <div class="nt-hero gg-fund">
            <div class="gg-fund__top">
                <div class="gg-fund__id">
                    <div class="gg-fund__icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div class="min-w-0">
                        <h5 class="gg-fund__title">Fondo Rifas ($150k)</h5>
                        <span class="gg-fund__sub">Total 50 Beneficiados</span>
                    </div>
                </div>
                <span class="gg-fund__badge"><?= $resumenFondos['rifa']['entregados_count'] ?> / 50</span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <div class="gg-fund__cell">
                        <span class="gg-fund__label">Recaudado Total</span>
                        <strong class="gg-fund__num nt-money">$<?= number_format($resumenFondos['rifa']['total_recaudado'], 0, ',', '.') ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="gg-fund__cell">
                        <span class="gg-fund__label">Entregado</span>
                        <strong class="gg-fund__num gg-fund__num--gold nt-money">$<?= number_format($resumenFondos['rifa']['entregados_monto'], 0, ',', '.') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs de Navegación -->
<ul class="nav nav-pills gg-tabs custom-pills" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 font-outfit fw-bold" id="tab-entregas-tab" data-bs-toggle="pill" data-bs-target="#tab-entregas" type="button" role="tab">
            <i class="fa-solid fa-file-signature me-2"></i>Historial de Entregas y Evidencias (<?= count($entregas) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link px-4 font-outfit fw-bold" id="tab-cronograma-tab" data-bs-toggle="pill" data-bs-target="#tab-cronograma" type="button" role="tab">
            <i class="fa-solid fa-calendar-days me-2"></i>Cronograma 26 Reuniones
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">
    <!-- Tab 1: Historial de Entregas Realizadas -->
    <div class="tab-pane fade show active" id="tab-entregas" role="tabpanel">
        <div class="gg-panel">
            <div class="table-responsive">
                <table class="table table-hover align-middle gg-table gg-stack">
                    <thead>
                        <tr>
                            <th class="ps-4">Reunión / Fecha</th>
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
                                <td colspan="7" class="gg-empty">
                                    <i class="fa-solid fa-gift"></i>
                                    Aún no hay entregas de préstamos, rondas o rifas registradas con evidencia.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entregas as $e): ?>
                                <tr>
                                    <td class="ps-4 gg-cell-main">
                                        <div>
                                        <div class="fw-bold font-outfit text-dark">R<?= $e['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($e['fecha_reunion'])) ?></div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($e['fecha_entrega'])) ?></small>
                                        </div>
                                    </td>
                                    <td data-label="Socio">
                                        <div class="text-md-start">
                                        <strong class="text-dark font-outfit d-block gg-wrap"><?= htmlspecialchars($e['socio_nombre']) ?></strong>
                                        <small class="text-muted">C.C. <?= htmlspecialchars($e['socio_cedula']) ?></small>
                                        </div>
                                    </td>
                                    <td data-label="Tipo">
                                        <span class="badge bg-<?= $e['tipo_beneficio'] === 'PRESTAMO' ? 'primary' : ($e['tipo_beneficio'] === 'RONDA' ? 'warning text-dark' : 'info text-dark') ?> fw-bold px-3 py-1">
                                            <i class="fa-solid <?= $e['tipo_beneficio'] === 'PRESTAMO' ? 'fa-hand-holding-dollar' : ($e['tipo_beneficio'] === 'RONDA' ? 'fa-arrows-rotate' : 'fa-ticket') ?> me-1"></i><?= $e['tipo_beneficio'] ?>
                                        </span>
                                    </td>
                                    <td data-label="Monto" class="fw-bold gg-t-pine font-outfit fs-6 nt-money">$<?= number_format($e['monto_entregado'], 0, ',', '.') ?> COP</td>
                                    <td class="text-center" data-label="Firma">
                                        <?php if (!empty($e['firma_digital_path'])): ?>
                                            <a href="<?= $e['firma_digital_path'] ?>" target="_blank" title="Ver Firma Digital">
                                                <img src="<?= $e['firma_digital_path'] ?>" alt="Firma" class="gg-evidence" style="height: 40px; max-width: 100px; object-fit: contain;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fs-8">Sin firma</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" data-label="Foto">
                                        <?php if (!empty($e['foto_evidencia_path'])): ?>
                                            <a href="<?= $e['foto_evidencia_path'] ?>" target="_blank" title="Ver Foto Evidencia">
                                                <img src="<?= $e['foto_evidencia_path'] ?>" alt="Foto Evidencia" class="gg-evidence gg-evidence--photo" style="height: 45px; width: 45px; object-fit: cover;">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fs-8">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4" data-label="Entregado por">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <small class="fw-semibold text-muted"><i class="fa-solid fa-user-check gg-t-pine me-1"></i><?= htmlspecialchars($e['entregado_por_nombre']) ?></small>
                                            <form action="/admin/entregas/eliminar" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmas eliminar esta constancia de entrega N° <?= $e['id'] ?>?')">
                                                <input type="hidden" name="entrega_id" value="<?= $e['id'] ?>">
                                                <button type="submit" class="btn btn-xs btn-outline-danger px-2" title="Eliminar constancia de entrega">
                                                    <i class="fa-solid fa-trash me-1"></i>Borrar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Cronograma 26 Reuniones -->
    <div class="tab-pane fade" id="tab-cronograma" role="tabpanel">
        <div class="gg-panel">
            <div class="gg-panel__head">
                <h5 class="gg-panel__title"><i class="fa-solid fa-calendar-check"></i>Cronograma de Recaudos y Liberaciones de Rondas y Rifas</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle gg-table gg-stack fs-7">
                    <thead>
                        <tr>
                            <th class="ps-3">Reunión</th>
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
                                <td class="ps-3 fw-bold font-outfit gg-cell-main">R<?= $c['numero_quincena'] ?> (<?= date('d/m/Y', strtotime($c['fecha_reunion'])) ?>)</td>
                                <td data-label="Tipo fondo">
                                    <span class="badge bg-<?= $c['tipo_beneficio'] === 'RONDA' ? 'warning text-dark' : 'info text-dark' ?>">
                                        <?= $c['tipo_beneficio'] ?>
                                    </span>
                                </td>
                                <td data-label="Cuota base" class="nt-money">$<?= number_format($c['valor_cuota_base'], 0, ',', '.') ?></td>
                                <td data-label="Aporte c/u" class="fw-semibold nt-money">$<?= number_format($c['aporte_por_socio'], 0, ',', '.') ?></td>
                                <td data-label="Recaudo total" class="gg-t-pine fw-bold nt-money">$<?= number_format($c['total_recaudado'], 0, ',', '.') ?></td>
                                <td data-label="Monto premio" class="fw-bold nt-money">$<?= number_format($c['monto_beneficio_unidad'], 0, ',', '.') ?></td>
                                <td data-label="Personas liberadas">
                                    <span class="badge bg-<?= $c['personas_liberadas_planificadas'] > 1 ? 'danger' : ($c['personas_liberadas_planificadas'] === 1 ? 'success' : 'secondary') ?> px-2">
                                        <?= $c['personas_liberadas_planificadas'] ?> Socio(s)
                                    </span>
                                </td>
                                <td data-label="Saldo acumulado" class="pe-3 text-end fw-bold font-outfit nt-money">$<?= number_format($c['saldo_acumulado_fondo'], 0, ',', '.') ?></td>
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
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content gg-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-signature me-2"></i>Registrar Entrega / Desembolso (Firma & Evidencia)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/entregas/guardar" method="POST" enctype="multipart/form-data" id="formRegistrarEntrega">
                <input type="hidden" name="firma_base64" id="firma_base64">
                <input type="hidden" name="prestamo_id" value="<?= $prestamoIdQuery ?? '' ?>">
                <div class="modal-body text-dark">
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
                            <label for="entrega_reunion_id" class="form-label fw-semibold fs-7">Reunión</label>
                            <select name="reunion_id" id="entrega_reunion_id" class="form-select" required>
                                <?php foreach ($reuniones as $r): 
                                    $selR = (!empty($reunionIdQuery) && $reunionIdQuery == $r['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['id'] ?>" <?= $selR ?>>R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="entrega_monto_entregado" class="form-label fw-semibold fs-7">Monto Entregado (COP)</label>
                            <?php $valMonto = ($montoQuery > 0) ? number_format($montoQuery, 0, ',', '.') : (($tipoQuery === 'RONDA') ? '300.000' : (($tipoQuery === 'RIFA') ? '150.000' : '500.000')); ?>
                            <input type="text" name="monto_entregado" id="entrega_monto_entregado" class="form-control money-input fw-bold text-success" inputmode="numeric" value="<?= $valMonto ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="entrega_socio_id" class="form-label fw-semibold fs-7">Socio Beneficiario / Deudor</label>
                        <select name="socio_id" id="entrega_socio_id" class="form-select fw-bold" required>
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
                            <div class="gg-soft gg-soft--bordered">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label fw-semibold fs-7 mb-0 text-dark">
                                        <i class="fa-solid fa-pen-nib gg-t-pine me-1"></i>Firma Digital del Socio:
                                    </label>
                                    <button type="button" class="btn btn-xs btn-outline-danger px-2" id="btnLimpiarFirma">
                                        <i class="fa-solid fa-eraser me-1"></i>Limpiar
                                    </button>
                                </div>
                                <div class="gg-signpad text-center">
                                    <canvas id="canvasFirma" width="360" height="150" class="w-100 cursor-crosshair" style="touch-action: none; background-color: #ffffff;"></canvas>
                                </div>
                                <small class="text-muted fs-8 d-block mt-1">Dibuja la firma del socio directamente sobre la pantalla con el dedo o mouse.</small>
                            </div>
                        </div>

                        <!-- Evidencia Fotográfica (Cámara / Subida) -->
                        <div class="col-12 col-md-5">
                            <div class="gg-soft gg-soft--bordered h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <label for="foto_evidencia" class="form-label fw-semibold fs-7 mb-1 text-dark">
                                        <i class="fa-solid fa-camera gg-t-pine me-1"></i>Foto Evidencia de Entrega:
                                    </label>
                                    <input type="file" name="foto_evidencia" id="foto_evidencia" class="form-control form-control-sm mb-2" accept="image/*" capture="environment">
                                    <small class="text-muted fs-8 d-block">Toma una foto con la cámara del celular o selecciona una foto de la galería.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning px-4">Guardar Entrega y Constancia</button>
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
