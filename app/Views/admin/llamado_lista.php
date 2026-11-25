<link rel="stylesheet" href="/css/pages/gestion-dinero.css">

<div class="gd-head">
    <div class="gd-head__main">
        <span class="gd-head__icon" aria-hidden="true"><i class="fa-solid fa-clipboard-user"></i></span>
        <div class="gd-head__text">
            <h2 class="nt-page-title">Llamado a Lista de Reunión</h2>
            <p class="nt-page-sub">Control rápido mobile-first de cobro de cuotas y autopréstamos.</p>
        </div>
    </div>
    <div class="gd-head__actions">
        <!-- Seleccionar Reunión -->
        <form method="GET" action="/admin/llamado-lista" class="gd-select-inline">
            <label for="reunion_id">Reunión:</label>
            <select name="reunion_id" id="reunion_id" class="form-select form-select-sm" onchange="this.form.submit()">
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
    <div class="nt-hero gd-meeting">
        <div class="gd-meeting__item">
            <span class="gd-meeting__icon" aria-hidden="true"><i class="fa-solid fa-calendar-day"></i></span>
            <div class="min-w-0">
                <span class="gd-meeting__label">Reunión Nº <?= $reunionActual['numero_quincena'] ?></span>
                <strong class="gd-meeting__value"><?= date('d/m/Y', strtotime($reunionActual['fecha_reunion'])) ?> - <?= $reunionActual['hora_reunion'] ?></strong>
            </div>
        </div>
        <div class="gd-meeting__item">
            <span class="gd-meeting__icon" aria-hidden="true"><i class="fa-solid fa-money-bill-wave"></i></span>
            <div class="min-w-0">
                <span class="gd-meeting__label">Cuota Base de Reunión</span>
                <strong class="gd-meeting__value gd-meeting__value--gold nt-money">$<?= number_format($reunionActual['valor_cuota_base'], 0, ',', '.') ?> COP</strong>
            </div>
        </div>
        <div class="gd-meeting__item">
            <span class="gd-meeting__icon" aria-hidden="true"><i class="fa-solid fa-gift"></i></span>
            <div class="min-w-0">
                <span class="gd-meeting__label">Evento / Pasarela</span>
                <?php if ($reunionActual['tipo_evento_extra'] === 'RIFA'): ?>
                    <span class="badge bg-danger fs-6">Rifa Interna ($150k)</span>
                <?php elseif ($reunionActual['tipo_evento_extra'] === 'RONDA'): ?>
                    <span class="badge bg-success fs-6">Ronda de Sorteo ($300k)</span>
                <?php else: ?>
                    <span class="badge bg-secondary fs-6">Reunión Regular</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="gd-meeting__cta">
            <button type="button" class="btn btn-warning fw-bold px-4" id="btnGuardarBatch">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i>Guardar Llamado a Lista
            </button>
        </div>
    </div>

    <!-- Barra de Filtros Rápidos -->
    <div class="gd-toolbar">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="buscarSocio" class="form-control" placeholder="Buscar socio por nombre o cédula..." aria-label="Buscar socio">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-outline-success w-100 fw-semibold" id="btnMarcarTodos">
                    <i class="fa-solid fa-check-double me-1"></i>Marcar Todos Pagó
                </button>
            </div>
            <div class="col-6 col-md-3">
                <button type="button" class="btn btn-outline-secondary w-100 fw-semibold" id="btnDesmarcarTodos">
                    <i class="fa-solid fa-xmark me-1"></i>Limpiar Todo
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla Móvil / Desktop de Socios -->
    <div class="gd-surface">
        <div class="table-responsive">
            <table class="table table-hover align-middle gd-table gd-cards gd-roll" id="tablaLlamadoLista">
                <thead>
                    <tr>
                        <th>Socio / Cédula</th>
                        <th class="text-center">Pagó Cuota ($<?= number_format($reunionActual['valor_cuota_base'], 0, ',', '.') ?>)</th>
                        <th class="text-center" style="min-width: 170px; width: 220px;">Ahorro Extra (COP)</th>
                        <th class="text-center">Generar Autopréstamo</th>
                        <th class="text-end">Regla 24h / Estado</th>
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
                        $autoPrestamo = $regActual ? ($regActual['autoprestamo_generado'] == 1) : false;
                        $pagoCuota = $regActual ? ($regActual['cuota_pagada'] == 1 && !$autoPrestamo) : true;
                        $ahorroExtra = $regActual ? (float)$regActual['monto_ahorro_extra'] : 0.0;
                        $anulado24h = $regActual ? ($regActual['anulado_sin_interes'] == 1) : false;
                    ?>
                        <tr class="socio-row" data-socio-id="<?= $socio['id'] ?>" data-search="<?= strtolower(htmlspecialchars($socio['nombre_completo'] . ' ' . $socio['cedula'])) ?>">
                            <td class="gd-cell-title">
                                <div class="gd-name"><?= htmlspecialchars($socio['nombre_completo']) ?></div>
                                <small class="gd-sub">C.C. <?= htmlspecialchars($socio['cedula']) ?></small>
                            </td>
                            <td class="text-center" data-label="Pagó cuota">
                                <div class="form-check form-switch gd-switch">
                                    <input class="form-check-input chk-pago-cuota cursor-pointer" type="checkbox"
                                           id="pago_<?= $socio['id'] ?>"
                                           data-socio-id="<?= $socio['id'] ?>"
                                           aria-label="Pagó cuota: <?= htmlspecialchars($socio['nombre_completo']) ?>"
                                           <?= $pagoCuota ? 'checked' : '' ?>>
                                </div>
                            </td>
                            <td class="text-center gd-cell-money" data-label="Ahorro extra" style="min-width: 160px;">
                                <div class="input-group gd-money-field">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control input-ahorro-extra money-input text-end"
                                           id="ahorro_<?= $socio['id'] ?>"
                                           inputmode="numeric"
                                           aria-label="Ahorro extra: <?= htmlspecialchars($socio['nombre_completo']) ?>"
                                           value="<?= $ahorroExtra > 0 ? number_format($ahorroExtra, 0, ',', '.') : '' ?>"
                                           placeholder="0">
                                </div>
                            </td>
                            <td class="text-center" data-label="Autopréstamo">
                                <div class="form-check gd-check">
                                    <input class="form-check-input chk-autoprestamo cursor-pointer" type="checkbox"
                                           id="auto_<?= $socio['id'] ?>"
                                           data-socio-id="<?= $socio['id'] ?>"
                                           aria-label="Generar autopréstamo: <?= htmlspecialchars($socio['nombre_completo']) ?>"
                                           <?= $autoPrestamo ? 'checked' : '' ?>
                                           <?= $pagoCuota ? 'disabled' : '' ?>>
                                </div>
                            </td>
                            <td class="text-end gd-cell-state" data-label="Estado">
                                <?php if ($regActual && $autoPrestamo && !$anulado24h): ?>
                                    <form action="/admin/llamado-lista/anular-24h" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmas anular el autopréstamo dentro de las 24 horas por pago recibido?')">
                                        <input type="hidden" name="ahorro_id" value="<?= $regActual['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger rounded-pill gd-act" title="Regla 24 Horas: Anular recargo e interés">
                                            <i class="fa-solid fa-undo"></i>Anular 24h
                                        </button>
                                    </form>
                                <?php elseif ($anulado24h): ?>
                                    <span class="gd-pill gd-pill--ok"><i class="fa-solid fa-check"></i>Anulado 24h (OK)</span>
                                <?php elseif ($pagoCuota): ?>
                                    <span class="gd-pill gd-pill--idle">Al día</span>
                                <?php else: ?>
                                    <span class="gd-pill gd-pill--wait">Pendiente</span>
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
