<div class="row mb-4 align-items-center">
    <div class="col-12 col-md-6 mb-2 mb-md-0">
        <h2 class="font-outfit fw-bold text-dark m-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-clipboard-user text-primary"></i>
            Llamado a Lista de Reunión
        </h2>
        <p class="text-muted m-0 fs-7">Control rápido mobile-first de cobro de cuotas y autopréstamos.</p>
    </div>
    <div class="col-12 col-md-6 d-flex justify-content-md-end align-items-center gap-2">
        <!-- Seleccionar Reunión -->
        <form method="GET" action="/admin/llamado-lista" class="d-flex align-items-center gap-2 w-100 w-md-auto">
            <label for="reunion_id" class="form-label mb-0 fw-semibold text-nowrap fs-7">Reunión:</label>
            <select name="reunion_id" id="reunion_id" class="form-select form-select-sm border-primary fw-bold shadow-sm" onchange="this.form.submit()">
                <?php foreach ($reuniones as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($reunionActual && $reunionActual['id'] == $r['id']) ? 'selected' : '' ?>>
                        R<?= $r['numero_quincena'] ?> - <?= date('d/m/Y', strtotime($r['fecha_reunion'])) ?> ($<?= number_format($r['valor_cuota_base'], 0, ',', '.') ?>)
                        <?= $r['tipo_evento_extra'] !== 'NINGUNO' ? ' [' . $r['tipo_evento_extra'] . ']' : '' ?>
                        <?= $r['estado'] === 'CERRADA' ? ' ✔' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($reunionActual): ?>
    <!-- Tarjeta Informativa de la Reunión -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-navy text-white overflow-hidden">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-circle text-warning fs-3">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div>
                            <span class="text-white-50 fs-7 d-block">Reunión Nº <?= $reunionActual['numero_quincena'] ?></span>
                            <strong class="fs-5 font-outfit"><?= date('d/m/Y', strtotime($reunionActual['fecha_reunion'])) ?> - <?= $reunionActual['hora_reunion'] ?></strong>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-circle text-success fs-3">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <span class="text-white-50 fs-7 d-block">Cuota Base de Reunión</span>
                            <strong class="fs-4 font-outfit text-warning">$<?= number_format($reunionActual['valor_cuota_base'], 0, ',', '.') ?> COP</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-white bg-opacity-10 rounded-circle text-info fs-3">
                            <i class="fa-solid fa-gift"></i>
                        </div>
                        <div>
                            <span class="text-white-50 fs-7 d-block">Evento / Pasarela</span>
                            <?php if ($reunionActual['tipo_evento_extra'] === 'RIFA'): ?>
                                <span class="badge bg-danger fs-6">Rifa Interna ($150k)</span>
                            <?php elseif ($reunionActual['tipo_evento_extra'] === 'RONDA'): ?>
                                <span class="badge bg-success fs-6">Ronda de Sorteo ($300k)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6">Reunión Regular</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3 text-sm-end">
                    <button type="button" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow" id="btnGuardarBatch">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Guardar Llamado a Lista
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="buscarSocio" class="form-control border-start-0 ps-0" placeholder="Buscar socio por nombre o cédula...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <button type="button" class="btn btn-outline-success w-100 btn-sm fw-semibold" id="btnMarcarTodos">
                        <i class="fa-solid fa-check-double me-1"></i>Marcar Todos Pagó
                    </button>
                </div>
                <div class="col-6 col-md-3">
                    <button type="button" class="btn btn-outline-secondary w-100 btn-sm fw-semibold" id="btnDesmarcarTodos">
                        <i class="fa-solid fa-xmark me-1"></i>Limpiar Todo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Móvil / Desktop de Socios -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaLlamadoLista">
                <thead class="bg-dark text-white font-outfit fs-7 text-uppercase">
                    <tr>
                        <th class="ps-4">Socio / Cédula</th>
                        <th class="text-center">Pagó Cuota ($<?= number_format($reunionActual['valor_cuota_base'], 0, ',', '.') ?>)</th>
                        <th class="text-center" style="min-width: 170px; width: 220px;">Ahorro Extra (COP)</th>
                        <th class="text-center">Generar Autopréstamo</th>
                        <th class="text-end pe-4">Regla 24h / Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Indexar ahorros previamente guardados si los hay
                    $ahorrosMap = [];
                    foreach ($ahorrosRegistrados as $ah) {
                        $ahorrosMap[$ah['socio_id']] = $ah;
                    }
                    ?>

                    <?php foreach ($socios as $socio): 
                        $regActual = $ahorrosMap[$socio['id']] ?? null;
                        $pagoCuota = $regActual ? ($regActual['cuota_pagada'] == 1) : true;
                        $ahorroExtra = $regActual ? (float)$regActual['monto_ahorro_extra'] : 0.0;
                        $autoPrestamo = $regActual ? ($regActual['autoprestamo_generado'] == 1) : false;
                        $anulado24h = $regActual ? ($regActual['anulado_sin_interes'] == 1) : false;
                    ?>
                        <tr class="socio-row" data-socio-id="<?= $socio['id'] ?>" data-search="<?= strtolower(htmlspecialchars($socio['nombre_completo'] . ' ' . $socio['cedula'])) ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark font-outfit"><?= htmlspecialchars($socio['nombre_completo']) ?></div>
                                <small class="text-muted">C.C. <?= htmlspecialchars($socio['cedula']) ?></small>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input chk-pago-cuota fs-4 cursor-pointer" type="checkbox" 
                                           id="pago_<?= $socio['id'] ?>" 
                                           data-socio-id="<?= $socio['id'] ?>" 
                                           <?= $pagoCuota ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-center" style="min-width: 160px;">
                                <div class="input-group input-group-sm mx-auto" style="min-width: 150px; max-width: 220px;">
                                    <span class="input-group-text fw-bold text-dark">$</span>
                                    <input type="text" class="form-control input-ahorro-extra money-input fw-bold text-end text-primary fs-6" 
                                           id="ahorro_<?= $socio['id'] ?>" 
                                           value="<?= $ahorroExtra > 0 ? number_format($ahorroExtra, 0, ',', '.') : '' ?>" 
                                           placeholder="0">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check d-inline-block">
                                    <input class="form-check-input chk-autoprestamo cursor-pointer fs-5" type="checkbox" 
                                           id="auto_<?= $socio['id'] ?>" 
                                           data-socio-id="<?= $socio['id'] ?>" 
                                           <?= $autoPrestamo ? 'checked' : '' ?> 
                                           <?= $pagoCuota ? 'disabled' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($regActual && $autoPrestamo && !$anulado24h): ?>
                                    <form action="/admin/llamado-lista/anular-24h" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmas anular el autopréstamo dentro de las 24 horas por pago recibido?')">
                                        <input type="hidden" name="ahorro_id" value="<?= $regActual['id'] ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 fs-7" title="Regla 24 Horas: Anular recargo e interés">
                                            <i class="fa-solid fa-undo me-1"></i>Anular 24h
                                        </button>
                                    </form>
                                <?php elseif ($anulado24h): ?>
                                    <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2">Anulado 24h (OK)</span>
                                <?php elseif ($pagoCuota): ?>
                                    <span class="badge bg-light text-dark border rounded-pill">Al día</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark rounded-pill">Pendiente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script src="/js/llamado_lista.js"></script>
